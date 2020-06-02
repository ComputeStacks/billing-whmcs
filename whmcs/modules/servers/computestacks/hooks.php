<?php
/**
 * ComputeStacks Provisioning Module Hooks
 *
 * @copyright Copyright (c)2020 Compute Stacks, LLC.
 *
 */
require_once 'lib/computestacks_api.php';

if ( !defined('WHMCS') ) {
  die("Invalid Access.");
}

/**
 * When an order is placed, credit any $ amount spent on CS.
 */
add_hook('OrderPaid', 1, function($vars) {
  $credit_amount = 0.0;

  // Load the associated invoice
  $invoice = localAPI( 'GetInvoice', array( 'invoiceid' => $vars['invoiceId'] ) );
  foreach($invoice['items']['item'] as $item) {
    // Load the service
    $services = localAPI('GetClientsProducts', array( 'serviceid' => $item['relid'] ));
    foreach($services['products']['product'] as $service) {
      // Load the product
      $products = localAPI('GetProducts', array( 'pid' => $service['pid'] ));
      foreach($products['products']['product'] as $product) {
        if ($product['module'] == 'computestacks') {
          if ($product['configoption2'] == 'on') {
            // Add invoice line item amount to credit amount
            $credit_amount += floatval($item['amount']);
          }
        }
      };
    };
  };

  if ($credit_amount > 0.0) {
    localAPI(
      'AddCredit',
      array(
        'clientid' => $vars['userId'],
        'description' => 'Credit for Cloud Services Order #' . $vars['orderId'],
        'amount' => $credit_amount,
        'type' => 'add',
      )
    );
  }

});
