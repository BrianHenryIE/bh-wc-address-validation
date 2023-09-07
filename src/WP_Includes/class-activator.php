<?php
/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BH_WC_Address_Validation
 * @subpackage BH_WC_Address_Validation/includes
 */

namespace BrianHenryIE\WC_Address_Validation\WP_Includes;

use BrianHenryIE\WC_Address_Validation\API\Settings;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    BH_WC_Address_Validation
 * @subpackage BH_WC_Address_Validation/includes
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class Activator {

	/**
	 * Checks was a user using woocommerce-usps-address-verification plugin and ports their settings.
	 * // TODO: email is disabled by default, enable it for these users.
	 *
	 * Checks on-hold orders to see if they were previously marked bad-address before a plugin-deactivation,
	 * and if so, checks them again (schedules a cron).
	 *
	 * @see https://wordpress.org/plugins/woocommerce-usps-address-verification/
	 *
	 * Activation hook does not get called on updates!
	 *
	 * @since    1.0.0
	 */
	public static function activate(): void {

		$earlier_version_usps_username = get_option( 'usps_id' );

		if ( ! empty( $earlier_version_usps_username ) ) {
			update_option( Settings::USPS_USERNAME_OPTION, $earlier_version_usps_username );
			delete_option( 'usps_id' );
		}

		$earlier_version_notification_email_address = get_option( 'notif_email' );

		if ( ! empty( $earlier_version_notification_email_address ) ) {

			$email_settings_option_key = 'woocommerce_bad_address_admin_settings';

			// Extremely unlikely this will already exist.
			$email_settings = get_option( $email_settings_option_key, array() );

			$email_settings['recipient'] = $earlier_version_notification_email_address;

			update_option( $email_settings_option_key, $email_settings );

			delete_option( 'notif_email' );
		}

		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}

		$orders = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => array( 'on-hold' ),
			)
		);

		if ( ! is_array( $orders ) ) {
			return;
		}

		$orders_to_check = array();

		foreach ( $orders as $order ) {

			$had_bad_address_status = $order->get_meta( Deactivator::DEACTIVATED_BAD_ADDRESS_META_KEY );

			if ( ! empty( $had_bad_address_status ) ) {
				$orders_to_check[] = $order->get_id();
			}
		}

		if ( ! empty( $orders_to_check ) ) {
			wp_schedule_single_event( time(), Cron::CHECK_MULTIPLE_ADDRESSES_CRON_JOB, array( $orders_to_check ) );
		}
	}
}
