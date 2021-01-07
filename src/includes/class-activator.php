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

namespace BH_WC_Address_Validation\includes;

use BH_WC_Address_Validation\api\Settings;

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
	 * Short Description. (use period)
	 *
	 * Does not get called on updates.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

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

		$orders = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => array( 'wc-on-hold' ),
			)
		);

		foreach ( $orders as $order ) {
			// check its address

			// TODO notify admin of changes so we're not messing their actual on-hold reason
			$args = array( $order->get_id() );

			wp_schedule_single_event( time(), Cron::CHECK_ADDRESS_CRON_JOB, $args );
		}

	}
}
