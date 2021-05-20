<?php
/**
 * The only real setting is the USPS username.
 * The log level is part of the Logger settings.
 * The email-enabled setting is part of WooCommerce's standard email configuration.
 */

namespace BrianHenryIE\WC_Address_Validation\API;

interface Settings_Interface {

	public function get_usps_username(): ?string;

	public function get_plugin_slug(): string;

	public function get_plugin_basename(): string;
}
