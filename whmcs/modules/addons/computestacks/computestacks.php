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

  $output = "Endpoint: " . $configEndpoint . "<br />";
  $output .= "Api Key: " . $configApiKey . "<br />";
  $output .= "Api Secret: " . $configApiSecret . "<br />";
  $output .= "Shared Secret: " . $configSharedSecret;

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