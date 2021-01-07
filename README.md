[![WordPress tested 5.5](https://img.shields.io/badge/WordPress-v5.5%20tested-0073aa.svg)](#) [![PHPUnit ](.github/coverage.svg)](https://brianhenryie.github.io/bh-wc-address-validation/)

# BH WC Address Validation

Uses USPS API to validate and update addresses when an order is marked processing.

* Only changes the address once when run automatically
* Can be run manually repeatedly
* Sets order status to on-hold when deactivated
* Checks on-hold orders for plugin meta-key when activated to re-set orders to bad-address status
* Optionally triggers an email to admin when an order needs attention

TODO: Verify orders with bad-address status are correctly accounted for in reports. 

![Settings Page](./assets/settings-page.png "Setting Page")

![Order Notes After Automatic Check](./assets/order-notes-after-automatic-check.png "Order Notes After Automatic Check")

![Link to USPS](./assets/link-to-usps.png "Link to USPS")

![Order Action Manual Check](./assets/order-action-manual-check.png "Order Action Manual Check")


## Develop

Add a `.env.secret` file in the root of the project containing your USPS username.

```
USPS_USERNAME="123BH0003210"
```