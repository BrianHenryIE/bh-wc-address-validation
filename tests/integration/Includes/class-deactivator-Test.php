<?php
/**
 * Needs order status added via WooCommerce fitler to work.
 */

namespace BrianHenryIE\WC_Address_Validation\Includes;

use WC_Order;
use BrianHenryIE\WC_Address_Validation\WooCommerce\Order_Status;

/**
 * Class Deactivator_Test
 * @package BrianHenryIE\WC_Address_Validation\Includes
 * @coversNothing
 */
class Deactivator_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * The Deactivator should set all orders whose status is bad-address
	 * to on-hold.
	 *
	 * Make some orders with bad-address status.
	 * Run deactivate.
	 * Verify they are now on-hold.
	 */
	public function test_deactivate() {

		for ( $i = 0; $i < 10; $i++ ) {
			$order = new WC_Order();
			$order->set_status( Order_Status::BAD_ADDRESS_STATUS );
			$order->save();
		}

		$bad_address_status_orders = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => array( 'wc-' . Order_Status::BAD_ADDRESS_STATUS ),
			)
		);
		$this->assertCount( 10, $bad_address_status_orders );

		Deactivator::deactivate();

		$bad_address_status_orders = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => array( 'wc-' . Order_Status::BAD_ADDRESS_STATUS ),
			)
		);
		$this->assertCount( 0, $bad_address_status_orders );

		$on_hold_status_orders = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => array( 'wc-on-hold' ),
			)
		);
		$this->assertCount( 10, $on_hold_status_orders );
	}
}
