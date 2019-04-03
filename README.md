# ComputeStacks WHMCS

The ComputeStacks integration for WHMCS has multiple parts: 
  - WHMCS Addon Module
    + A way to store settings unique to your deployment with WHMCS. 
  - WHMCS Provisioning Module
    + Handles Creating, Terminating, Upgrading, Suspension, and Plan / QTY changes
  - ComputeStacks direct API Integration

### Feature Overview:
  - **User Sync:** When a user updates their name or email address, those settings are synced automatically.
  - **Single Sign On:** Users can login using the same credentials (except for Administrators).
  - **Provisioning:** WHMCS Can trigger an order to be provisioned. It can terminate, suspend, change a plan or quantity.
    + Container orders must be first initially placed with ComputeStacks. They will be 'pending' until WHMCS confirms the order can proceed.


### End-User Flow

When a new order is placed with ComputeStacks (new orders, and changes to existing services), an API call is made to WHMCS to setup the order and determine if any amount is owed. 

If an invoice is generated with a balance, ComputeStacks will craft a signed URL that will allow the user to seamlessly move to the invoice screen within WHMCS as their user. 

When a successful payment is received, a hook in WHMCS will craft a signed URL and send the user back to ComputeStacks.

The normal provisioning module will actually tell ComputeStacks to proceed with the order. 

## Minimum Requirements

  - WHMCS: v7.1+
  - ComputeStacks: v4.0+

## Installation

See [ComputeStacks WHMCS Documentation](https://www.notion.so/computestacks/WHMCS-Plugin-Installation-80e83cbcd4184b2faedb8fc4ff0ea3b9).


