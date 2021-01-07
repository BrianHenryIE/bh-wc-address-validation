<?php
/**
 * The only real setting is the USPS username.
 * The log level is part of the Logger settings.
 * The email-enabled setting is part of WooCommerce's standard email configuration.
 */

namespace BH_WC_Address_Validation\api;

interface Settings_Interface {

	public function get_usps_username(): ?string;
}
