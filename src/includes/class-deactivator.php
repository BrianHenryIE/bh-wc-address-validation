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

namespace BH_WC_Address_Validation\includes;

use BH_WC_Address_Validation\woocommerce\Order_Status;

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

	/**
	 * Changes all orders with bad-address status to on-hold.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		$orders = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => array( 'wc-' . Order_Status::BAD_ADDRESS_STATUS ),
			)
		);

		// TODO: add a meta key to check on activation.
		foreach ( $orders as $order ) {
			$order_note = 'Changed from Bad Address on plugin deactivation.';
			$order->update_status( 'on-hold', $order_note );
			$order->save();
		}
	}
}
