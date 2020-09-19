<?php

namespace BH_WC_Address_Validation\api;

class Settings implements Settings_Interface {

	const USPS_USERNAME_OPTION = 'bh-wc-address-validation-usps-username';

	const IS_LOGGING_ENABLED_OPTION     = 'bh-wc-address-validation-is-logging-enabled';
	const IS_ADMIN_EMAIL_ENABLED_OPTION = 'bh-wc-address-validation-is-admin-email-enabled';

	/**
	 * @return string
	 */
	public function get_usps_username(): ?string {
		return get_option( self::USPS_USERNAME_OPTION );
	}

	public function is_logging_enabled(): bool {
		return get_option( self::IS_LOGGING_ENABLED_OPTION, false );
	}

	public function is_admin_email_enabled(): bool {
		// TODO: This should set and read from the WC_Email settings.
		return get_option( self::IS_ADMIN_EMAIL_ENABLED_OPTION, false );
	}
}
