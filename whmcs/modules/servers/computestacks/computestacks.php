<?php
/**
 * ComputeStacks Provisioning Module
 *
 * @copyright Copyright (c)2020 Compute Stacks, LLC.
 *
 */

// require_once 'lib/vendor/autoload.php';
require_once 'lib/computestacks_api.php';

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/*
 * Configuration Functions
 */

function computestacks_MetaData()
{
  return array(
    'DisplayName' => 'ComputeStacks',
    'APIVersion' => '1.1',
    'RequiresServer' => true,
    'ServiceSingleSignOnLabel' => 'Manage Service',
  );
}

function computestacks_ConfigOptions() {
  return [
    "user_group_id" => [
      "FriendlyName" => "User Group ID",
      "Type" => "text",
      "Default" => "", // blank = default
    ],
  ];
}

/*
 * Core Module Functions
 */
function computestacks_CreateAccount(array $params)
{
    try {
      $cs = new CSApi($params);
      return $cs->createAccount();
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
    return 'success';
}

function computestacks_SuspendAccount(array $params)
{
  try {
    $cs = new CSApi($params);
    return $cs->suspendAccount();
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
    $cs = new CSApi($params);
    return $cs->terminateAccount();
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
    $cs = new CSApi();
    return $cs->activateAccount();
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

function computestacks_ChangePassword(array $params)
{
    try {
      $cs = new CSApi($params);
      return $cs->setPassword();
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

function computestacks_ServiceSingleSignOn(array $params)
{
    try {
      $cs = new CSApi($params);
      return $cs->serviceLoginRedirect();
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

/* Test Connection */

function computestacks_TestConnection(array $params)
{

  try {
    $cs = new CSApi($params);
    return $cs->testConnection();
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
}
