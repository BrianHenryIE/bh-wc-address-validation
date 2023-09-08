# Changelog

## 1.4.0 2023-09-07

* Add: show customer's last order's address on order page
* Add: twice-daily cron job to re-check bad addresses, e.g. if USPS API was offline
* Add: changelog on plugin install screen
* Fix: bulk-mark-processing was not passing the list of orders to cron as an array

## 1.3.1 2023-06-01

* Exclude AO, AA military addresses
* Fix: uppercase in regex
* Update libraries

...

...

## 1.2.2 2021-09-17

* Do not show settings link on plugins.php when WooCommerce is inactive.

## 1.2.1 2021-09-13

* When no validator is available for the address, do not mark the order "bad address"! Just add an order note.


## 1.2.0 2021-08-20

* Updated address now saves to customer user account
* Updates billing address if it matches shipping address
* Fix: bulk mark-processing now using the correct hook
* Add address validation using EasyPost API
* 
