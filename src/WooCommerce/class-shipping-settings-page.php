<?php
/**
 * The setting pages for the plugin.
 *
 * @link       https://github.com/BrianHenryIE/bh-wc-address-validation
 * @since      1.0.0
 *
 * @package    BH_WC_Address_Validation
 * @subpackage BH_WC_Address_Validation/admin
 */

namespace BrianHenryIE\WC_Address_Validation\WooCommerce;

use BrianHenryIE\WC_Address_Validation\API\Settings;
use Psr\Log\LogLevel;

/**
 * The settings page for the plugin.
 *
 * @package    BH_WC_Address_Validation
 * @subpackage BH_WC_Address_Validation/admin
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class Shipping_Settings_Page {

	/**
	 * @hooked woocommerce_get_sections_shipping
	 *
	 * @param array<string, string> $sections
	 * @return array<string, string>
	 */
	public function address_validation_section( $sections ): array {

		$sections['bh-wc-address-validation'] = __( 'Address Validation', 'bh-wc-address-validation' );

		return $sections;
	}

	/**
	 *
	 * @hooked woocommerce_get_settings_shipping
	 *
	 * @param array  $settings
	 * @param string $current_section
	 */
	public function address_validation_settings( array $settings, string $current_section ): array {

		/**
		 * Check the current section is what we want
		 */
		if ( 'bh-wc-address-validation' === $current_section ) {

			$settings = array();

			// Add Title to the Settings
			$settings[] = array(
				'name' => __( 'Address Validation', 'text-domain' ),
				'type' => 'title',
				'desc' => __( 'The following options are used to configure USPS address verification. You must sign up at <a target="_blank" href="https://registration.shippingapis.com/">USPS Web Tools Registration Page</a>.', 'bh-wc-address-validation' ),
				'id'   => 'bh-wc-address-validation',
			);

			// USPS username text input.
			$settings[] = array(
				'name' => __( 'USPS Username', 'bh-wc-address-validation' ),
				'desc' => __( 'Your USPS Web Tools API username', 'bh-wc-address-validation' ),
				'id'   => Settings::USPS_USERNAME_OPTION,
				'type' => 'text',
			);

			$settings[] = array(
				'name' => __( 'EasyPost API Key', 'bh-wc-address-validation' ),
				'desc' => __( 'Your EasyPost API Key', 'bh-wc-address-validation' ),
				'id'   => Settings::EASYPOST_API_KEY_OPTION,
				'type' => 'text',
			);

			$log_levels        = array( 'none', LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG );
			$log_levels_option = array();
			foreach ( $log_levels as $log_level ) {
				$log_levels_option[ $log_level ] = ucfirst( $log_level );
			}

			$settings[] = array(
				'title'    => __( 'Log Level', 'text-domain' ),
				'label'    => __( 'Enable Logging', 'text-domain' ),
				'type'     => 'select',
				'options'  => $log_levels_option,
				'desc'     => __( 'Increasing levels of logs.', 'text-domain' ),
				'desc_tip' => true,
				'default'  => 'notice',
				'id'       => 'bh-wc-address-validation-log-level',
			);

			$settings[] = array(
				'type' => 'sectionend',
				'id'   => 'bh-wc-address-validation',
			);

		}

		return $settings;
	}

}
