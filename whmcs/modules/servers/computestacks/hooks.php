<?php
/**
 * ComputeStacks Provisioning Module Hooks
 *
 * @copyright Copyright (c)2020 Compute Stacks, LLC.
 *
 */
require_once 'lib/computestacks_api.php';

use WHMCS\Database\Capsule;

if ( !defined('WHMCS') ) {
  die("Invalid Access.");
}

/**
 * When an order is placed, credit any $ amount spent on CS.
 */
add_hook('OrderPaid', 1, function($vars) {
  try {
    $credit_amount = 0.0;

    // Load the associated invoice
    $invoice = localAPI( 'GetInvoice', array( 'invoiceid' => $vars['invoiceId'] ) );
    foreach($invoice['items']['item'] as $item) {
      // Load the service
      $services = localAPI('GetClientsProducts', array( 'serviceid' => $item['relid'] ));
      foreach($services['products']['product'] as $service) {
        // Load the product
        $product = Capsule::table('tblproducts')->where('id', $service['pid'])->first();
        $product_module = $product->servertype; // In the db `module` appears to be `servertype`.
        $issue_credit = $product->configoption2;
        if ($product_module == 'computestacks' && $issue_credit == 'on') {
          // Add invoice line item amount to credit amount
          $credit_amount += floatval($item['amount']);
        }
      };
    };
    // With a credit of > 0, assign it to the client.
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
  } catch (Exception $e) {
    logModuleCall(
        'computestacks apply credit',
        __FUNCTION__,
        $vars,
        $e->getMessage(),
        $e->getTraceAsString()
    );
  }


});
