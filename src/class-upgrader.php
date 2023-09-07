<?php

namespace BrianHenryIE\WC_Address_Validation;

use BrianHenryIE\WC_Address_Validation\API\Settings;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class Upgrader {
	use LoggerAwareTrait;

	protected Settings_Interface $settings;

	public function __construct( Settings_Interface $settings, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->settings = $settings;
	}

	public function do_upgrade(): void {

		$old_version = get_option( 'bh_wc_address_validation_version', '0.0.0' );

		if ( 1 === version_compare( '1.2.0', $old_version ) ) {
			$this->v1_2_0();
		}

		update_option( 'bh_wc_address_validation_version', $this->settings->get_plugin_version() );
	}

	/**
	 * Rename options so they use the conventional underscores rather than hyphens.
	 */
	public function v1_2_0() {

		$option_name_changes = array(
			'bh-wc-address-validation-usps-username' => Settings::USPS_USERNAME_OPTION,
			'bh-wc-address-validation-is-admin-email-enabled' => Settings::IS_ADMIN_EMAIL_ENABLED_OPTION,
		);

		foreach ( $option_name_changes as $from => $to ) {

			$value = get_option( $from, null );

			if ( ! empty( $value ) ) {
				update_option( $to, $value );
				delete_option( $from );
			}
		}
	}
}
