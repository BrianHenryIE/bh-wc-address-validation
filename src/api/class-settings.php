<?php

namespace BH_WC_Address_Validation\api;

use BH_WC_Address_Validation\BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use BH_WC_Address_Validation\Psr\Log\LogLevel;

class Settings implements Settings_Interface, Logger_Settings_Interface {

	const USPS_USERNAME_OPTION = 'bh-wc-address-validation-usps-username';

	const IS_ADMIN_EMAIL_ENABLED_OPTION = 'bh-wc-address-validation-is-admin-email-enabled';

	/**
	 * @return string
	 */
	public function get_usps_username(): ?string {
		return get_option( self::USPS_USERNAME_OPTION );
	}

	public function is_admin_email_enabled(): bool {
		// TODO: This should set and read from the WC_Email settings.
		return get_option( self::IS_ADMIN_EMAIL_ENABLED_OPTION, false );
	}

	// @see Logger_Settings_Interface


	public function get_log_level(): string {

		return get_option( 'bh-wc-address-validation-log-level', LogLevel::NOTICE );
	}

	public function get_plugin_name(): string {
		return 'Address Validation';
	}

	public function get_plugin_slug(): string {
		return 'bh-wc-address-validation';
	}

	/**
	 * The plugin basename is used by the logger to add the plugins page action link.
	 * (and maybe for PHP errors)
	 *
	 * @return string
	 * @see Logger
	 */
	public function get_plugin_basename(): string {
		return 'bh-wc-address-validation/bh-wc-address-validation.php';
	}
}
