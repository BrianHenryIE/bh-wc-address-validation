<?php
/**
 * Fired during plugin deactivation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BH_WC_Address_Validation
 * @subpackage BH_WC_Address_Validation/includes
 */

namespace BrianHenryIE\WC_Address_Validation\WP_Includes;

use BrianHenryIE\WC_Address_Validation\WooCommerce\Order_Status;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    BH_WC_Address_Validation
 * @subpackage BH_WC_Address_Validation/includes
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class Deactivator {

	const DEACTIVATED_BAD_ADDRESS_META_KEY = 'bh-wc-address-validation-was-bad-address';

	/**
	 * Changes all orders with bad-address status to on-hold.
	 * Add a meta-key indicating they were previously bad-address which is checked when reactivated.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate(): void {

		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}

		/** @var \WC_Order[] $orders */
		$orders = wc_get_orders(
			array(
				'limit'    => -1,
				'status'   => array( 'wc-' . Order_Status::BAD_ADDRESS_STATUS ),
				'paginate' => false,
			)
		);

		foreach ( $orders as $order ) {
			$order_note = 'Changed from Bad Address on plugin deactivation.';
			$order->set_status( 'on-hold', $order_note );
			$order->add_meta_data( self::DEACTIVATED_BAD_ADDRESS_META_KEY, gmdate( DATE_ATOM ), true );
			$order->save();
		}
	}
}
