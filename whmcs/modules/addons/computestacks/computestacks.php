<?php
/**
 * ComputeStacks Addon Module
 *
 * @see https://computestacks.atlassian.net/wiki/display/BILLING/WHMCS
 *
 * @copyright Copyright (c)2017 Compute Stacks, LLC.
 *
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Module\Addon\AddonModule\Admin\AdminDispatcher;
use WHMCS\Module\Addon\AddonModule\Client\ClientDispatcher;

function computestacks_config() {

  return array(
    'name' => 'ComputeStacks',
    'description' => "ComputeStack Docker Containers",
    'author' => "ComputeStacks",
    'language' => 'english',
    'version' => '1.0',
    'fields' => array(
      'endpoint' => array(
        'FriendlyName' => 'ComputeStacks URL',
        'Type' => 'text',
        'Size' => 25,
      ),
      'api_key' => array(
        'FriendlyName' => 'API Key',
        'Type' => 'text',
        'Size' => 25
      ),
      'api_secret' => array(
        'FriendlyName' => 'API Secret',
        'Type' => 'password',
        'Size' => 25
      ),
      'shared_secret' => array(
        'FriendlyName' => 'Shared Secret',
        'Type' => 'password',
        'Size' => 256
      ),
      'require_auth' => array(
        'FriendlyName' => 'Require 2FA',
        'Description' => 'Users with 2FA enabled in ComputeStacks will be preventd from logging into WHMCS directly until they successfully authenticate with ComputeStacks.',
        'Type' => 'yesno'
      ),
    )
  );

}

function computestacks_output($vars) {

  $modulelink = $vars['modulelink'];
  $version = $vars['version'];
  $_lang = $vars['_lang'];

  $configApiKey = $vars['api_key'];
  $configEndpoint = $vars['endpoint'];
  $configApiSecret = $vars['api_secret'];
  $configSharedSecret = $vars['shared_secret'];
  $configRequireAuth = $vars['require_auth'];

  $output = "Endpoint: " . $configEndpoint . "<br />";
  $output .= "Api Key: " . $configApiKey . "<br />";
  $output .= "Api Secret: " . $configApiSecret . "<br />";
  $output .= "Shared Secret: " . $configSharedSecret . "<br />";
  $output .= "Require 2FA" . $configRequireAuth;

  echo $output;     
}

function computestacks_activate() {
  return array(
    'status' => 'success',
    'description' => 'ComputeStacks activated'
  );
}

function computestacks_deactivate() {
  return array(
    'status' => 'success',
    'description' => 'ComputeStacks deactivated'
  );
}