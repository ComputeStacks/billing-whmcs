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

function computestacks_MetaData()
{
  return array(
    'DisplayName' => 'ComputeStacks',
    'APIVersion' => '1.1',
    'RequiresServer' => true,
    'ServiceSingleSignOnLabel' => 'Login',
    // The display name of the unique identifier to be displayed on the table output
    'ListAccountsUniqueIdentifierDisplayName' => 'Username',
    // The field in the return that matches the unique identifier
    'ListAccountsUniqueIdentifierField' => 'username',
    // The config option indexed field from the _ConfigOptions function that identifies the product on the remote system
    'ListAccountsProductField' => 'configoption1', // configoption1 = package
  );
}

function computestacks_ConfigOptions() {
  return [
    'package' => [ // configoption1
      'FriendlyName' => 'User Group',
      'Type' => 'text',
      'Size' => '25',
      'Loader' => 'computestacks_LoadUserGroups',
      'SimpleMode' => true,
    ],
    'credit_amount' => [ // configoption2
      'FriendlyName' => 'Apply Credit',
      'Type' => 'yesno',
      'Default' => 'yes',
      'Description' => 'Apply product price as account credit',
      'SimpleMode' => true,
    ],
  ];
}

/**
 * Return an array of User Groups
 */
function computestacks_LoadUserGroups(array $params) {
  /**
   * Intentionally does not use `try{}`!
   * WHMCS expects this to return an exception if there is a problem
   */
  $cs = new CSApi($params);
  $result = $cs->listUserGroups();
  if ( $result['success'] ) {
    $groups = [];
    foreach ($result['groups']->user_groups as $group) {
      $groups[$group->id] = ucfirst($group->name);
    }
    return $groups;
  } else {
    throw new Exception($result['error']);
  }
}

function computestacks_ListAccounts(array $params) {
  try {
    $cs = new CSApi($params);
    return $cs->listAccounts();
  } catch (Exception $e) {
    logModuleCall(
        'computestacks list accounts',
        __FUNCTION__,
        $params,
        $e->getMessage(),
        $e->getTraceAsString()
    );
    return array( 'success' => true, 'error' => $e->getMessage() );
  }
}

function computestacks_ClientArea(array $params): array {
  $cs = new CSApi($params);
  return array(
    'templatefile' => 'clientarea',
    'vars' => $cs->accountStats(),
  );
}

function computestacks_AdminServicesTabFields($params): array {
  $cs = new CSApi($params);
  $stats = $cs->accountStats();
  return array(
    'Projects' => $stats['projects'],
    'Services' => $stats['services'],
    'Run Rate' => $stats['bill_estimate'],
  );
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
    return $e->getMessage();
  }
}
