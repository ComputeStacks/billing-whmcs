<?php
/**
 * ComputeStacks Provisioning Module Hooks
 *
 * @see https://computestacks.atlassian.net/wiki/display/BILLING/WHMCS
 *
 * @copyright Copyright (c)2017 Compute Stacks, LLC.
 *
 */
require_once 'lib/vendor/autoload.php';
require_once 'lib/computestacks_api.php';

use WHMCS\Database\Capsule;

if ( !defined('WHMCS') ) {
  die("Invalid Access.");
}

/* Redirect the user after a successful payment, only if:
 *  - The current user logged in = the invoice owner
 *  - The invoice containers a product with the ComputeStacks module.
 */
function computestacks_order_redirect($vars) {
  if ($_SERVER['HTTP_USER_AGENT'] != 'Ruby') { /* Prevent API calls from being redirected */    
    $invoice = localAPI('GetInvoice', array( 'invoiceid' => $vars['invoiceid'] ), 'cstacks');
    if ($invoice['result'] == 'error') {
        logModuleCall(
          'computestacks',
          __FUNCTION__,
          $invoice,
          'Error Redirecting user.',
          ''
        );
    }
    if ($invoice['result'] == 'success' && $_SESSION['uid'] == $invoice['userid']) {
      foreach($invoice['items']['item'] as $item) {
        if ($item['type'] == 'Upgrade') {
          $service_id = Capsule::table('tblupgrades')->where('id', $item['relid'])->value('relid');        
        } else {
          $service_id = $item['relid'];
        }
        if (!empty($service_id)) {
          $services = localAPI('GetClientsProducts', array( 'serviceid' =>  $service_id), 'cstacks');
          foreach ($services['products']['product'] as $service) {
            $products = localAPI('GetProducts', array( 'pid' => $service['pid'] ), 'cstacks');
            $product = $products['products']['product'][0];
            if ($product['module'] == 'computestacks') {
              // Build redirect
              $cs = new CSApi();
              $redirect_url = "Location: " . $cs->buildOrderRedirect($product['pid']);
              header($redirect_url);
              print_r($product);
              exit; 
            }
          }   
        }      
      }
      unset($item);
      unset($service);
    }
  }       
}

function computestacks_update_client($vars) {
  $cs = new CSApi();
  $cs->editClient($vars);
}

function computestacks_view_invoice($vars) {
  $cs = new CSApi();
  $cs_vars = $cs->settings();
  // logModuleCall(
  //     'computestacks',
  //     __FUNCTION__,
  //     $cs_vars,
  //     $cs_vars['endpoint'],
  //     ''
  // );
  return array('cs_return' => $cs_vars['endpoint']);
}

add_hook('ClientAreaPageViewInvoice', 1, "computestacks_view_invoice");
add_hook("InvoicePaid", 1, "computestacks_order_redirect");
add_hook("ClientEdit", 1, "computestacks_update_client");