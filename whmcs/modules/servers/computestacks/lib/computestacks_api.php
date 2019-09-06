<?php
/**
 * ComputeStacks API
 *
 * @see https://www.notion.so/WHMCS-Integration-80e83cbcd4184b2faedb8fc4ff0ea3b9
 *
 * @copyright Copyright (c)2017 Compute Stacks, LLC.
 *
 */

require_once 'vendor/autoload.php';
use \Firebase\JWT\JWT;
use \GuzzleHttp\Client;
use WHMCS\Database\Capsule;

class CSApi
{

  private static $endpoint = null;
  private static $api_key = null;
  private static $api_secret = null;
  private static $shared_secret = null;
  private static $require_auth = false;

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
        case 'require_auth':
          if ($config->value == 'on') {
            self::$require_auth = true;
          } else {
            self::$require_auth = false;
          }
          break;
      }
    }
  }

  public function settings() {
    return array(
      'endpoint' => self::$endpoint,
      'api_key' => self::$api_key,
      'require_auth' => self::$require_auth,
    );
  }

  // Generate Redirect URL to send the user BACK to computestacks, after payment is confirmed.
  public function buildOrderRedirect($product_id) {
    try {
      $data = array(
        "status" => 'ok',
        "product_id" => $product_id,
        "exp" => time() + 3600, // 1 hour TTL.
      );
      $jwt = JWT::encode($data, self::$shared_secret);
      return self::$endpoint . "/api/webhooks/order_redirects?token=" . $jwt;
    } catch (Exception $e) {
      logModuleCall(
        'computestacks',
        __FUNCTION__,
        $product_id,
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
      $path = 'orders/' . $orderid . '/process_order?find_by_external_id=true';
      $result = $this->connect($path, null, 'POST');
      if ($result->getStatusCode() == 202) {
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
      $data = array(
        'device' => array(
          'package_id' => $product_id,
          'external_product' => true
        )
      );
      $path = 'subscriptions/' . $subscription_id . '?find_by_external_id=true&from_billing=true';
      $result = $this->connect($path, $data, 'PUT');
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
        "modifyDeviceServices Fatal Error",
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return $e->getMessage();
    } 
  }

  public function modifyContainerService($subscription_id, $product_id, $qty) {
    try {
      $data = array(
        'container_service' => array(
          'package_id' => $product_id,
          'external_product' => true,
          'qty' => $qty
        )
      );
      $path = 'subscriptions/' . $subscription_id . '?find_by_external_id=true&from_billing=true';
      $result = $this->connect($path, $data, 'PUT');
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
        "modifyContainerService fatal error",
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return $e->getMessage();
    }    
  }

  public function destroyService($subscription_id) {
    try {
      $path = 'subscriptions/' . $subscription_id . '?find_by_external_id=true&from_billing=true';
      $result = $this->connect($path, null, 'DELETE');
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
        "destroyService fatal error",
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return $e->getMessage();
    }    
  }

  public function toggleSuspendedService($subscription_id, $action) {
    try {
      $path = 'subscriptions/' . $subscription_id . '/suspension?find_by_external_id=true';
      $response = $this->connect($path, null, $action);      
      if ($response->getStatusCode() == 202) {
        return 'success';
      } else {
        $errorMsg = json_decode($response->getBody());
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
        "toggleSuspendedService fatal error",
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return $e->getMessage();
    }    

  }

  public function editClient($params) {    
    try {
      // Determine if this is a real user..
      $remote_user_path = 'users/' . $params['userid'] . '?find_by_external_id=true';
      $remote_user = $this->connect($remote_user_path, null, 'GET');
      if ($remote_user->getStatusCode() == 200) {
        $remote_data = json_decode($remote_user->getBody());
        $update_data = [
          'user' => [
            'fname' => $params['firstname'],
            'lname' => $params['lastname'],
            'email' => $params['email'],
            'country' => $params['country'],
            'city' => $params['city'],
            'state' => $params['state'],
            'address1' => $params['address1'],
            'address2' => $params['address2'],
            'zip' => $params['postcode']
          ]
        ];
        $update_path = 'users/' . $remote_data->user->id;
        $response = $this->connect($update_path, $update_data, 'PUT');
        if ($response->getStatusCode() == 202) {
          return 'success';
        } else {
          $errorMsg = json_decode($response->getBody());
          logModuleCall(
            'computestacks',
            __FUNCTION__,
            $errorMsg,
            "error updating user",
            null
          );
          return implode(" ", $errorMsg);
        }
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

  public function clientHas2fa($clientid, $remote_ip) {
    try {
      $data = array(
        'headers' => [
          'Accept' => 'application/json; api_version=51',
          'Content-Type' => 'application/json',
          'Authorization' => 'Basic ' + base64_encode(self::$api_key + ':' + self::$api_secret)
        ]
      );
      $client = new Client();
      $path = self::$endpoint . '/api/user/' . $clientid . '/authcheck?rip=' . $remote_ip;
      $request = $client->createRequest('POST', $path, $data);
      $response = $client->send($request);
      if ($response->getStatusCode() == 202) {
        return 'success';
      } else {
        return 'failed';
      }
    } catch (Exception $e) {
      logModuleCall(
        'computestacks',
        __FUNCTION__,
        "clientHas2FA fatal error",
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return $e->getMessage(); 
    }
  }

  public function testConnection() {
    try {
      $result = $this->connect('locations', null, 'GET');
      if ($result->getStatusCode() == 200) {
        return 'success';
      } else {
        return 'Failed to connect';
      }
    } catch (Exception $e) {
      logModuleCall(
        'computestacks',
        __FUNCTION__,
        "computestacks test",
        $e->getMessage(),
        $e->getTraceAsString()
      );
      return $e->getMessage(); 
    }
  }

  // API Call to CS.
  private function connect($path, $body, $method = 'POST') {
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
    $base_uri = self::$endpoint . '/api/admin/';
    // ['base_uri' => $base_uri]
    $client = new Client();
    $request = $client->createRequest($method, $base_uri . $path, $data);
    return $client->send($request);
  }

}