<?php
/**
 * ComputeStacks Provisioning Module
 *
 * @see https://www.notion.so/WHMCS-Integration-80e83cbcd4184b2faedb8fc4ff0ea3b9
 *
 * @copyright Copyright (c)2017 Compute Stacks, LLC.
 *
 */

require_once 'lib/vendor/autoload.php';
require_once 'lib/computestacks_api.php';

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function computestacks_MetaData()
{
  return array(
    'DisplayName' => 'ComputeStacks',
    'APIVersion' => '1.1', // Use API Version 1.1
    'RequiresServer' => true, // Set true if module requires a server to work
    'DefaultNonSSLPort' => '80', // Default Non-SSL Connection Port
    'DefaultSSLPort' => '443', // Default SSL Connection Port
    'ServiceSingleSignOnLabel' => 'Login to ComputeStacks as User',
    'AdminSingleSignOnLabel' => 'Login to ComputeStacks as Admin',
  );
}

function computestacks_ConfigOptions() {
    return [
        "service_kind" => [
            "FriendlyName" => "Service Type",
            "Type" => "dropdown",
            "Options" => [
                "package" => "Package",
                "metered" => "Hourly Metered",
            ],
            "Default" => "package",
        ],
        "klass" => [
            "FriendlyName" => "Product Kind",
            "Type" => "dropdown",
            "Options" => [
                "container" => "Container",
                "vm" => "Cloud Server",
            ],
            "Default" => "container",
        ],
    ];
}

function computestacks_CreateAccount(array $params)
{
    try {
        if ($params['configoption1'] == 'metered') {
          return 'success'; // Do nothing for metered orders.
        }        
        $orderid = $params['model']['attributes']['orderid'];
        if ($orderid == null) {
            $serviceid = $params['serviceid'];
            $clientid = $params['userid'];
            // Determine order ID from the serviceid
            $results = localAPI('GetClientsProducts', ['clientid' => $clientid, 'serviceid' => $serviceid], 'cstacks');
            if ($results['numreturned'] < 1) {
                return "Failed to find OrderID for service: " . $serviceid;
            } else {
                $orderid = $results['products']['product'][0]['orderid'];
            }
        }
        // Final sanity check
        if ($orderid == null) {
            return "Failed to find OrderID. Unable to provision.";
        } else {
            $cs = new CSApi();
            return $cs->orderProvision($orderid);
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

function computestacks_SuspendAccount(array $params)
{
    try {
        if ($params['configoption1'] == 'metered') {
          return 'success'; // Do nothing for metered orders.
        }
        $subscription_id = $params['serviceid'];
        $cs = new CSApi();
        return $cs->toggleSuspendedService($subscription_id, 'POST');
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

function computestacks_UnsuspendAccount(array $params)
{
    try {
        if ($params['configoption1'] == 'metered') {
          return 'success'; // Do nothing for metered orders.
        }
        $subscription_id = $params['serviceid'];
        $cs = new CSApi();
        return $cs->toggleSuspendedService($subscription_id, 'DELETE');
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

function computestacks_TerminateAccount(array $params)
{
    try {
        if ($params['configoption1'] == 'metered') {
          return 'success'; // Do nothing for metered orders.
        }
        $subscription_id = $params['serviceid'];
        $cs = new CSApi();
        return $cs->destroyService($subscription_id);
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

function computestacks_ChangePackage(array $params)
{
    try {
        if ($params['configoption1'] == 'metered') {
          return 'success'; // Do nothing for metered orders.
        }
        $cs = new CSApi();
        $subscription_id = $params['serviceid'];
        $product_id = $params['pid'];
        if ($params['configoption2'] == 'vm') {
            return $cs->modifyDeviceService($subscription_id, $product_id);
        } else {
            $container_qty = $params['configoptions']['Container'];
            return $cs->modifyContainerService($subscription_id, $product_id, $container_qty);
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

function computestacks_TestConnection(array $params)
{
    try {
        $cs = new CSApi();
        $result = $cs->testConnection();
        if ($result == 'success') {
            $success = 'success';
        } else {
            $errorMsg = 'Failed to connect';
        }
    } catch (Exception $e) {
        logModuleCall(
            'computestacks',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        $success = false;
        $errorMsg = $e->getMessage();
    }
    return array(
        'success' => $success,
        'error' => $errorMsg,
    );
}