# Upgrading from v1 to v2

## Overview
Version 2 of our WHMCS plugin is a major change from V1 and will require that updates are made to both ComputeStacks and WHMCS.

### Feature Table

| Feature                    | v1                                                                                                                                                                                                                                 | v2                                                                                                               |
|----------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------|
| Accounts                   | 1 WHMCS user = 1 CS User                                                                                                                                                                                                           | WHMCS Users can have multiple CS Accounts (Service = CS Account)                                                 |
| ClientArea                 | No ClientArea integration                                                                                                                                                                                                          | ClientArea Integration with SSO for both users and admins                                                        |
| Pre-Paid Billing (Monthly) | ComputeStacks would generate an order for each billable action (upgrades/new orders/scaling), redirect the user to the invoice (if it created). Our WHMCS plugin would then attempt to redirect the user back to CS after payment. | no longer available, hourly only.                                                                                |
| Hourly Billing             | CS will aggregate usage and create billable items                                                                                                                                                                                  | No Change                                                                                                        |
| Ordering in WHMCS          | Not available                                                                                                                                                                                                                      | Supported                                                                                                        |
| CS Registration            | Users who signed up in CS would automatically be created in WHMCS. Logging into CS used their WHMCS ClientArea credentials                                                                                                         | CS Users are _not_ created in WHMCS. Logging into CS uses the username/password set by the WHMCS service module. |

## Upgrading

If you do not have any pre-paid monthly users with active services, then the upgrade process is fairly straight forward. Simply remove the old plugin and install our new one following the installation instructions found in our `README.md` file.

You may then use the [Server Sync Tool](https://docs.whmcs.com/Server_Sync_Tool) to create local services to enable ClientArea support.

If there are existing monthly pre-paid customers, then you will want to work through the following items:

1. Create a new metered billing plan in CS (or update the old one and change all the prices to hourly) and assign all of those users to that billing plan.
2. Delete all the existing pre-paid monthly services in WHMCS.
    * **DO NOT RUN ANY MODULE COMMANDS!** This will also delete the service in ComputeStacks!
3. Create the product in WHMCS, following our `README.md`.
4. Run the server sync tool in WHMCS
