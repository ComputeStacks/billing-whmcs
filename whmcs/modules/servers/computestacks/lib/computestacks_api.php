<?php
/**
 * ComputeStacks API
 *
 * @copyright Copyright (c)2020 Compute Stacks, LLC.
 *
 */

use GuzzleHttp\Client; // whmcs v7.10.2 uses guzzle v5.3.3
class CSApi
{

  private static $endpoint = null;
  private static $api_key = null;
  private static $api_secret = null;
  private static $context = null;

  function __construct($params) {
    self::$endpoint = $params['serverhostname'];
    self::$api_key = $params['serverusername'];
    self::$api_secret = $params['serverpassword'];
    self::$context = $params;
  }

  /*
   * Create Account
   *
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
        )
      );
      $result = $this->client('admin/users', $data, 'POST');
      if ( !$this->apiSuccess($result->getStatusCode()) ) {
        return $result->getBody();
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

  public function setPassword() {
    $data = array(
      'user' => array(
        'password' => self::$context['password'],
        'password_confirmation' => self::$context['password'],
      )
    );
    return $this->updateUser($data);
  }

  // Suspend Account
  public function suspendAccount() {
    $data = array(
      'user' => array(
        'active' => false,
      )
    );
    return $this->updateUser($data);
  }

  // Activate Account
  public function activateAccount() {
    $data = array(
      'user' => array(
        'active' => true,
      )
    );
    return $this->updateUser($data);
  }

  // Generate User SSO Link
  public function serviceLoginRedirect(): array {

    try {
      $result = $this->client('admin/users/' . self::$context['serviceid'] . '/user_sso?find_by_external_id=true', $data, 'POST');
      if ( $this->apiSuccess($result->getStatusCode()) ) {
        $response = json_decode($result->getBody());
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

  /*
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
          'error' => "Status Code: " . $result->getStatusCode() . " | ERROR: " . $result->getBody()
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
    return $username;
  }

  // Update a user with the given data
  private function updateUser(array $data): string {
    try {
      $result = $this->client('admin/users/' . self::$context['serviceid'] . '?find_by_external_id=true', $data, 'PATCH');
      if ( !$this->apiSuccess($result->getStatusCode()) ) {
        return $result->getBody();
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

  // API Client
  private function client($path, $body, $method = 'POST') {
    $basic_auth = base64_encode(self::$api_key . ':' . self::$api_secret);
    $data = array(
      'headers' => [
        'Accept' => 'application/json; api_version=51',
        'Content-Type' => 'application/json',
        'Authorization' => 'Basic ' . $basic_auth
      ]
    );
    if ($body != null) {
      $data['json'] = $body;
    }
    $full_uri = 'https://' . self::$endpoint . '/api/' . $path;
    $client = new Client();
    $request = $client->createRequest($method, $full_uri, $data);
    return $client->send($request);
  }

}
