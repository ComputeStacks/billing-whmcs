# ComputeStacks WHMCS Plugin

## Overview

The ComputeStacks integration for WHMCS provides an easy onboarding experience for your customers. It offers a customized ClientArea integration, along with seamless onboarding.

* Each service in WHMCS corresponds to a single ComputeStacks account
* ClientArea output for quick overview
* Easy SingleSignOn buttons in both ClientArea and Admin
* Support for charging an initial deposit on registration
* Import CS accounts into WHMCS using the [Server Sync Tool](https://docs.whmcs.com/Server_Sync_Tool)

### Service Terminations

This plugin will not delete any services in ComputeStacks. When a WHMCS service is terminated, this plugin suspends the account in ComputeStacks, which stops all running services. You will then need to manually delete the user. _If you wish to have WHMCS automatically delete the users in ComputeStacks, please contact your CS support team._

## Installation

_**Note:** We require WHMCS v7.8+_

### Upload Files
Upload the plugin to your WHMCS server and move the `whmcs/modules/servers/computestacks` directory to the corresponding directory within your WHMCS installation.


### WHMCS Server

Create a new server and supply your ComputeStacks installation details.

_**Note:** You will need to generate API Credentials using an admin user in ComputeStacks. The API Key in ComputeStacks corresponds to the username field in WHMCS, and the API Secret is the password._

### WHMCS Product

Our integration is designed to work in conjunction with our User Group functionality within ComputeStacks. This is useful if you decide to offer different tiers of service.

To create your ComputeStacks product, create a new product group and product with the settings:

* Pricing:
  * With Deposit: Choose OneTime fee and set your deposit amount
  * Without Deposit: Choose Free
* Module: ComputeStacks
  * User Group: Your choice
  * Apply Credit: If you added a one-time fee, you can optionally convert that to an account credit.
    * _Note: This will take place at the moment the order is paid._
  * Module Setup:
    * With Account Credits, we recommend either:
      * _**Automatically setup the product as soon as the first payment is received**_ (preferred for best user experience)
      * _Automatically setup the product when you manually accept a pending order_
    * Without Account Credits, we recommend either:
      * _**Automatically setup the product as soon as an order is placed**_ (preferred for best user experience)
      * _Automatically setup the product when you manually accept a pending order_

## Configure ComputeStacks

Before we proceed, you will need to first [generate API credentials](https://docs.whmcs.com/API_Authentication_Credentials) for ComputeStacks. Here are the roles that we need assigned:

* `Billing -> AddBillableItem`
* `Client -> GetClients`
* `Client -> GetClientsDetails`
* `Client -> GetClientsProducts`
* `Products -> GetProducts`
* `Products -> UpdateClientProduct`
* `Servers -> GetHealthStatus`

**WHMCS Access Control:** Please see the [documentation](https://developers.whmcs.com/api/access-control/). You can either allow the IP Address of ComputeStacks, or configure an Access Key.

In ComputeStacks, navigate to the `Administrator -> Advanced Settings -> Billing`.
1. Edit `BILLING_MODULE` and set the value to `Whmcs` and click save. Once you do, additional settings will become available.
2. Set both `WHMCS_API_SECRET` and `WHMCS_API_KEY`
3. If you set an API Access Key in WHMCS, then you can enter that under `WHMCS_ACCESS_KEY`.

With WHMCS enabled, ComputeStacks will by default disable the registration form to ensure all new users come through WHMCS. Administrators can manually create users from within the admin.

If you wish to keep the registration form on, you can manually enable that under `Settings -> Advanced Settings -> General` and setting `SIGNUP_FORM` to `t`. Keep in mind that users will be able to register and will _not_ be charged for their usage unless you manually set their `external_id` to the ID of their WHMCS service.

The final step is to set your prices under `Settings -> Billing Plans`.
