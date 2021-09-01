<?php
/**
 * ComputeStacks API
 *
 * @copyright Copyright (c)2020 Compute Stacks, LLC.
 *
 */

class CSApi
{

  private static $endpoint = null;
  private static $api_key = null;
  private static $api_secret = null;
  private static $context = null;

  // How we find the user in CS
  private static $selector = null;
  private static $selector_kind = 'find_by_external_id=true'; // or 'find_by_email=true'

  function __construct($params) {
    self::$endpoint = $params['serverhostname'];
    self::$api_key = $params['serverusername'];
    self::$api_secret = $params['serverpassword'];
    self::$context = $params;

    /**
     * If the username exists in the params hash, we will use that to find the user
     * Otherwise, default to serviceid. However, serviceid won't work for imported accounts unless you select 'reset password' during import.
     */
    if (array_key_exists('username', $params)) {
      self::$selector = urlencode(base64_encode($params['username']));
      self::$selector_kind = 'find_by_email=true';
    } else {
      self::$selector = $params['serviceid'];
    }
  }

  /*
   * Account Stats
   */
  public function accountStats(): array {
    $return = array(
      'projects' => '-',
      'services' => '-',
      'bill_estimate' => '-',
      'balance' => '-',
    );
    if ( !$this->hasValidUsername() ) {
      return $return;
    }
    try {
      $result = $this->client('admin/users/' . self::$selector . '?include=balance&' . self::$selector_kind, null, 'GET');
      if ( $this->apiSuccess($result->getStatusCode()) ) {
        $data = json_decode($result->getBody()->getContents());
        $return['projects'] = number_format($data->user->services->deployments, 0, '.', ',');
        $return['services'] = number_format($data->user->services->container_services, 0, '.', ',');
        $return['bill_estimate'] = $data->user->currency_symbol . number_format($data->user->run_rate, 2, '.', ',');
        $return['balance'] = $data->user->currency_symbol . number_format($data->user->unprocessed_usage, 2, '.', ',');
      }
    } catch(Exception $e) {
      logModuleCall(
        'computestacks accountStats',
        __FUNCTION__,
        $data,
        $e->getMessage(),
        $e->getTraceAsString()
      );
    }
    return $return;
  }

  /**
   * Create Account
   */
  public function createAccount() {
    try {
      $data = array(
        'user' => array(
          'skip_email_confirm' => true,
          'external_id' => self::$context['serviceid'],
          'fname' => self::$context['clientsdetails']['firstname'],
          'lname' => self::$context['clientsdetails']['lastname'],
          'email' => $this->generateUsername(),
          'password' => self::$context['password'],
          'password_confirmation' => self::$context['password'],
          'address1' => self::$context['clientsdetails']['address1'],
          'address2' => self::$context['clientsdetails']['address2'],
          'city' => self::$context['clientsdetails']['city'],
          'state' => self::$context['clientsdetails']['fullstate'],
          'zip' => self::$context['clientsdetails']['postcode'],
          'country' => self::$context['clientsdetails']['country'],
          'phone' => self::$context['clientdetails']['telephoneNumber'],
          'currency' => self::$context['clientdetails']['currency_code'],
          'merge_labels' => array(
            'whmcs' => array(
              'client_id' => self::$context['clientsdetails']['client_id'],
              'service_id' => self::$context['serviceid']
            ),
          ),
        )
      );
      $result = $this->client('admin/users', $data, 'POST');
      if ( !$this->apiSuccess($result->getStatusCode()) ) {
        return $result->getBody()->getContents();
      }
    } catch(Exception $e) {
      logModuleCall(
        'computestacks',
        __FUNCTION__,
        $data,
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return $e->getMessage();
    }
    return 'success';
  }

  public function listAccounts(): array {

    try {
      $response = $this->client('admin/users', null, 'GET');
      if ( !$this->apiSuccess($response->getStatusCode()) ) {
        return array( 'success' => false, 'error' => $response->getBody()->getContents());
      }
      $accounts = [];
      $users = json_decode($response->getBody()->getContents());
      foreach($users->users as $user) {
        // Dont include admin users
        if ( !($user->is_admin) ) {
          if ($user->active) {
            $status = WHMCS\Service\Status::ACTIVE;
          } else {
            $status = WHMCS\Service\Status::SUSPENDED;
          }
          $accounts[] = [
            'email' => $user->email,
            'username' => $user->email,
            'domain' => '',
            'uniqueIdentifier' => $user->email,
            'product' => $user->user_group->id,
            'primaryip' => '',
            'created' => date('Y-m-d H:i:s', strtotime($user->created_at)),
            'status' => $status,
          ];
        }
      }
    } catch(Exception $e) {
      logModuleCall(
        'computestacks',
        __FUNCTION__,
        'Fatal error generating account list for ComputeStacks',
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return array( 'success' => false, 'error' => $e->getMessage());
    }
    return array( 'success' => true, 'accounts' => $accounts);
  }

  public function listUserGroups(): array {
    $response = $this->client('admin/user_groups', null, 'GET');
    if ( !$this->apiSuccess($response->getStatusCode()) ) {
      return array( 'success' => false, 'error' => $response->getBody()->getContents());
    }
    $groups = json_decode($response->getBody()->getContents());
    return array(
      'success' => true,
      'groups' => $groups,
    );
  }

  public function setPassword() {
    $data = array(
      'user' => array(
        'external_id' => self::$context['serviceid'],
        'password' => self::$context['password'],
        'password_confirmation' => self::$context['password'],
        'merge_labels' => array(
          'whmcs' => array(
            'client_id' => self::$context['clientsdetails']['client_id'],
            'service_id' => self::$context['serviceid']
          ),
        ),
      )
    );
    return $this->updateUser($data);
  }

  // Suspend Account
  public function suspendAccount() {
    $data = array(
      'user' => array(
        'external_id' => self::$context['serviceid'],
        'active' => false,
        'merge_labels' => array(
          'whmcs' => array(
            'client_id' => self::$context['clientsdetails']['client_id'],
            'service_id' => self::$context['serviceid']
          ),
        ),
      )
    );
    return $this->updateUser($data);
  }

  // Activate Account
  public function activateAccount() {
    $data = array(
      'user' => array(
        'external_id' => self::$context['serviceid'],
        'active' => true,
        'merge_labels' => array(
          'whmcs' => array(
            'client_id' => self::$context['clientsdetails']['client_id'],
            'service_id' => self::$context['serviceid']
          ),
        ),
      )
    );
    return $this->updateUser($data);
  }

  // Generate User SSO Link
  public function serviceLoginRedirect(): array {
    try {
      $result = $this->client('admin/users/' . self::$selector . '/user_sso?' . self::$selector_kind, null, 'POST');
      if ( $this->apiSuccess($result->getStatusCode()) ) {
        $response = json_decode($result->getBody()->getContents());
        $redirectUrl = 'https://' . self::$endpoint . '/?username=' . $response->username . '&token=' . $response->token;
        return array( 'success' => true, 'redirectTo' => $redirectUrl );
      } else {
        return array( 'success' => false );
      }
    } catch (Exception $e) {
      logModuleCall(
        'computestacks',
        __FUNCTION__,
        "generate SSO token",
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return array( 'success' => false );
    }
  }

  /**
   * Account Termination
   *
   * We don't let WHMCS actually delete accounts. This will suspend them and require manual removal later.
   *
   */
  public function terminateAccount() {
    return $this->suspendAccount();
  }

  // Test connection to CS
  public function testConnection() {
    try {
      $result = $this->client('admin/locations', null, 'GET');
      if ( $this->apiSuccess($result->getStatusCode()) ) {
        return array(
          'success' => true,
          'error' => ''
        );
      } else {
        return array(
          'success' => false,
          'error' => "Status Code: " . $result->getStatusCode() . " | ERROR: " . $result->getBody()->getContents()
        );
      }
    } catch (Exception $e) {
      logModuleCall(
        'computestacks',
        __FUNCTION__,
        "computestacks test connection",
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return false;
    }
  }

  /**
   * Determine if we have a username
   *
   * We generate these during account creation, so if none exists, dont try and load any stats.
   */
  private function hasValidUsername(): bool {
    $username_check = filter_var(self::$context['username'], FILTER_SANITIZE_EMAIL);
    return filter_var( $username_check, FILTER_VALIDATE_EMAIL );
  }

  // Generate a salted username for this service
  private function generateUsername(): string {
    $customer_email = self::$context['clientsdetails']['email'];

    // Determine if the current context username is an email address
    $username_check = filter_var(self::$context['username'], FILTER_SANITIZE_EMAIL);

    // If Username is already an email, use that, otherwise lets create one.
    if( !filter_var( $username_check, FILTER_VALIDATE_EMAIL ) ) {
      // Provide some uniqueness to the email to avoid collisions.
      $email_salt = self::$context['clientsdetails']['userid'] . '-' . self::$context['serviceid'];
      // If Username is blank, we will create one, otherewise use the username as the first part of the email
      if (empty(self::$context['username'])) {
        $username = explode('@', $customer_email)[0] . $email_salt . '@' . self::$endpoint;
      } else {
        $username = self::$context['username'] . $email_salt . '@' . self::$endpoint;
      }
      self::$context['model']->serviceProperties->save(['Username' => $username]);
    } else {
      $username = self::$context['username'];
    }
    self::$selector = $username;
    self::$selector_kind = 'find_by_email=true';
    return $username;
  }

  // Update a user with the given data
  private function updateUser(array $data): string {
    try {
      $result = $this->client('admin/users/' . self::$selector . '?' . self::$selector_kind, $data, 'PATCH');
      if ( !$this->apiSuccess($result->getStatusCode()) ) {
        return $result->getBody()->getContents();
      }
    return 'success';
    } catch(Exception $e) {
      logModuleCall(
        'computestacks',
        __FUNCTION__,
        $data,
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return $e->getMessage();
    }
  }

  // Determine api status based on response code
  private function apiSuccess(int $statusCode): bool {
    if ( $statusCode < 200 || $statusCode > 204 ) {
      return false;
    }
    return true;
  }

  /**
   * API Client
   *
   * $path: path after url, without leading or trailing slashes
   * $data: json encoded data
   * $method: One of (GET, POST, PATCH, PUT, DELETE)
   */
  private function client($path, $body, $method) {
    $basic_auth = base64_encode(self::$api_key . ':' . self::$api_secret);
    $client = new GuzzleHttp\Client([
      'base_uri' => 'https://' . self::$endpoint . '/api/',
      'headers' => [
        'Accept' => 'application/json; api_version=60',
        'Content-Type' => 'application/json',
        'Authorization' => 'Basic ' . $basic_auth
      ]
    ]);
    if ($body == null) {
      return $client->request($method, $path);
    } else {
      return $client->request($method, $path, [ 'json' => $body ]);
    }
  }

}
