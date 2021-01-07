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

namespace BH_WC_Address_Validation\woocommerce;

use BH_WC_Address_Validation\api\Settings;
use BH_WC_Address_Validation\BrianHenryIE\WPPB\WPPB_Object;

/**
 * The settings page for the plugin.
 *
 * @package    BH_WC_Address_Validation
 * @subpackage BH_WC_Address_Validation/admin
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class Shipping_Settings_Page extends WPPB_Object {

	/**
	 * @hooked woocommerce_get_sections_shipping
	 *
	 * @param $sections
	 * @return mixed
	 */
	function address_validation_section( $sections ) {

		$sections['bh-wc-address-validation'] = __( 'Address Validation', 'text-domain' );
		return $sections;

	}

	/**
	 *
	 * @hooked woocommerce_get_settings_shipping
	 *
	 * @param $settings
	 * @param $current_section
	 * @return array
	 */
	function address_validation_settings( $settings, $current_section ) {

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

			// Enable logging checkbox.
			$settings[] = array(
				'name'     => __( 'Enable logging', 'bh-wc-address-validation' ),
				'desc_tip' => __( 'Logs each action to the WooCommerce logs', 'bh-wc-address-validation' ),
				'id'       => Settings::IS_LOGGING_ENABLED_OPTION,
				'type'     => 'checkbox',
				'css'      => 'min-width:300px;',
				'desc'     => __( 'Enable Logging', 'bh-wc-address-validation' ),
			);

			$settings[] = array(
				'type' => 'sectionend',
				'id'   => 'bh-wc-address-validation',
			);

		}

		return $settings;
	}
}
