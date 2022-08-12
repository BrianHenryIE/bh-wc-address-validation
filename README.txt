=== Plugin Name ===
Contributors: BrianHenryIE, boris.smirnoff
Donate link:
Tags: usps, woocommerce, address verification, address standardization
Requires at least: 3.0.1
Tested up to: 5.4
WC tested up to: 4.2.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A plugin that contacts USPS API and verifies the address, fixes address mistakes such as typos etc., and adds the full 5+4 zip code.

== Description ==

This plugin is using the USPS API to verify the address customer entered on WooCommerce checkout page, without disturbing the customer and blocking payment.
If USPS API can correct and standardize the address it does just that, if not then it sets the order status to USPS-Bad-Address and sends an email to the admin
that order is waiting for manual inspection.

We were getting 1 mistake out of 20 orders. What this plugin does is simple, it takes the address written like this:

205 bagwel ave (bagwel is spelled bagwell, I intentionally made a mistake)
nutter fort
ZIP: 26301

and converts it to:

205 BAGWELL AVE
NUTTER FORT
26301

It standardizes all addresses and fixes minor problems such as typos, or even a wrong zip code if possible.

You must register on USPS web site to use this, here:
https://registration.shippingapis.com/

And in plugin settings you just type in your username and email on which you wish notifications.

This plugin was originally written by Boris Smirnoff – http://lb.geek.rs.ba/ – smirnoff@geek.rs.ba

== Installation ==

Click install plugin on this page, or if you wish to do it manually, upload folder bh-wc-address-validation into your wp-content/plugins folder
and on plugins page in your admin area, click activate.

== Screenshots ==

== Changelog ==

= 0.2 =
* Complete rewrite.

= 0.1.1 =
* First release. Code requires some reorganization and cleanup but it's quite simple and it's working.

== Upgrade Notice ==

== Arbitrary section ==

