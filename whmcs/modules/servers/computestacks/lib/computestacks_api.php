<?php
/**
 * ComputeStacks API
 *
 * @see https://computestacks.atlassian.net/wiki/display/BILLING/WHMCS
 *
 * @copyright Copyright (c)2017 Compute Stacks, LLC.
 *
 */

require_once 'vendor/autoload.php';
use \Firebase\JWT\JWT;
use WHMCS\Database\Capsule;

class CSApi
{

  private static $endpoint = null;
  private static $api_key = null;
  private static $api_secret = null;
  private static $shared_secret = null;

  // Load credentials from Addon.
  function __construct() {
    $configs = Capsule::table('tbladdonmodules')->where('module', 'computestacks')->get();
    foreach($configs as $config) {
      switch ($config->setting) {
        case 'endpoint':
          self::$endpoint = $config->value;
          break;
        case 'api_key':
          self::$api_key = $config->value;
          break;
        case 'api_secret':
          self::$api_secret = $config->value;
          break;
        case 'shared_secret':
          self::$shared_secret = $config->value;
          break;
      }
    }
  }

  public function settings() {
    return array(
      'endpoint' => self::$endpoint,
      'api_key' => self::$api_key
    );
  }

  // Generate Redirect URL to send the user BACK to computestacks, after payment is confirmed.
  public function buildOrderRedirect() {
    try {
      $data = array(
        "status" => 'ok',
        "exp" => time() + 3600, // 1 hour TTL.
      );
      $jwt = JWT::encode($data, self::$shared_secret);
      return self::$endpoint . "/api/webhooks/order_redirects?token=" . $jwt;
    } catch (Exception $e) {
      logModuleCall(
        'computestacks',
        __FUNCTION__,
        $params,
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return $e->getMessage();
    }    
  }


  public function orderProvision($orderid) {
    try {
      if ($orderid == null) {
        return "Missing OrderID";
      }
      $path = 'orders/' . $orderid . '/process?find_by_external_id=true';
      $auth_token = $this->authToken();
      $result = $this->connect($path, $auth_token, null, 'PUT');
      if ($result->getStatusCode() == 201) {
        return 'success';
      } else {
        $errorMsg = json_decode($result->getBody());
        logModuleCall(
          'computestacks',
          __FUNCTION__,
          $errorMsg,
          "Order Error",
          null
        );
        return implode(" ", $errorMsg);
      }
    } catch (Exception $e) {
      logModuleCall(
        'computestacks',
        __FUNCTION__,
        $params,
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return $e->getMessage();
    }    
  }

  public function modifyDeviceService($subscription_id, $product_id) {
    try {
      $auth_token = $this->authToken();
      $data = array(
        'device' => array(
          'package_id' => $product_id,
          'external_product' => true
        )
      );
      $path = 'subscriptions/' . $subscription_id . '?find_by_external_id=true&from_billing=true';
      $result = $this->connect($path, $auth_token, $data, 'PUT');
      if ($result->getStatusCode() == 200) {
        return 'success';
      } else {
        $errorMsg = json_decode($result->getBody());
        logModuleCall(
          'computestacks',
          __FUNCTION__,
          $errorMsg,
          "Modify Service Error",
          null
        );
        return implode(" ", $errorMsg);
      }
      
    } catch (Exception $e) {
      logModuleCall(
        'computestacks',
        __FUNCTION__,
        $params,
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return $e->getMessage();
    } 
  }

  public function modifyContainerService($subscription_id, $product_id, $qty) {
    try {
      $auth_token = $this->authToken();
      $data = array(
        'container_service' => array(
          'package_id' => $product_id,
          'external_product' => true,
          'qty' => $qty
        )
      );
      $path = 'subscriptions/' . $subscription_id . '?find_by_external_id=true&from_billing=true';
      $result = $this->connect($path, $auth_token, $data, 'PUT');
      if ($result->getStatusCode() == 200) {
        return 'success';
      } else {
        $errorMsg = json_decode($result->getBody());
        logModuleCall(
          'computestacks',
          __FUNCTION__,
          $errorMsg,
          "Modify Service Error",
          null
        );
        return implode(" ", $errorMsg);
      }
      
    } catch (Exception $e) {
      logModuleCall(
        'computestacks',
        __FUNCTION__,
        $params,
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return $e->getMessage();
    }    
  }

  public function destroyService($subscription_id, $order_id) {
    try {
      $auth_token = $this->authToken();

      $path = 'subscriptions/' . $subscription_id . '?find_by_external_id=true&from_billing=true';
      $result = $this->connect($path, $auth_token, null, 'DELETE');
      if ($result->getStatusCode() == 202) {
        return 'success';
      } else {
        $errorMsg = json_decode($result->getBody());
        logModuleCall(
          'computestacks',
          __FUNCTION__,
          $errorMsg,
          "Delete Service Error",
          null
        );
        return implode(" ", $errorMsg);
      }
      
    } catch (Exception $e) {
      logModuleCall(
        'computestacks',
        __FUNCTION__,
        $params,
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return $e->getMessage();
    }    
  }

  public function toggleSuspendedService($subscription_id, $action) {
    try {
      $auth_token = $this->authToken();
      $path = 'subscriptions/' . $subscription_id . '/' . $action . '?find_by_external_id=true';
      $result = $this->connect($path, $auth_token, null, 'PUT');

      if ($result->getStatusCode() == 202) {
        return 'success';
      } else {
        $errorMsg = json_decode($result->getBody());
        logModuleCall(
          'computestacks',
          __FUNCTION__,
          $errorMsg,
          "Toggle Service Suspension Error",
          null
        );
        return implode(" ", $errorMsg);
      }

    } catch (Exception $e) {
      logModuleCall(
        'computestacks',
        __FUNCTION__,
        $params,
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return $e->getMessage();
    }    

  }

  public function editClient($params) {    
    try {
      $auth_token = $this->authToken();
      // Determine if this is a real user..
      $remote_user_path = 'users/' . $params['userid'] . '?find_by_external_id=true';
      $remote_user = $this->connect($remote_user_path, $auth_token, null, 'GET');
      if ($remote_user->getStatusCode() == 200) {
        $remote_data = json_decode($remote_user->getBody());
        $update_data = [
          'user' => [
            'fname' => $params['firstname'],
            'lname' => $params['lastname'],
            'email' => $params['email'],
            'country' => $params['country']
          ]
        ];
        $update_path = 'users/' . $remote_data->user->id;
        $update_user = $this->connect($update_path, $auth_token, $update_data, 'PUT');
        return 'success';
      }
    } catch (Exception $e) {
      logModuleCall(
        'computestacks',
        __FUNCTION__,
        $params,
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return $e->getMessage();
    }   
    
  }

  public function testConnection() {    
    $auth_token = $this->authToken();
    if (strlen($auth_token) > 1) {
      return 'success';
    } else {
      return 'Failed to connect.';
    }
  }

  // Generate auth token to log into CS using apikey / secret.
  public function authToken() {
    $headers = ['Accept' => 'application/json'];
    $data = [
      'json' => [
        'api_key' => self::$api_key,
        'api_secret' => self::$api_secret
      ]
    ];
    $client = new GuzzleHttp\Client(['base_uri' => self::$endpoint]);
    $response = $client->request('POST', 'api/auth', $data);
    $result = json_decode($response->getBody());
    return $result->token;

  }

  // API Call to CS.
  private function connect($path, $token, $body, $method = 'POST') {

    $headers = array(
      'Accept' => 'application/json',
      'Authorization' => $token
    );
    $data = array(
      'headers' => $headers
    );
    if ($body != null) {
      $data['json'] = $body;
    }
    $base_uri = self::$endpoint . '/api/admin/';
    $client = new GuzzleHttp\Client(['base_uri' => $base_uri]);
    return $client->request($method, $path, $data);

  }

}