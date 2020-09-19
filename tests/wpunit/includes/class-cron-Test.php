<?php
/**
 *
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WC_Address_Validation\includes;

use Codeception\Stub\Expected;
use WC_Order;
use BH_WC_Address_Validation\api\API;

/**
 * Class Cron_Test
 *
 * @see Cron
 */
class Cron_Test extends \Codeception\TestCase\WPTestCase {

	public function test_cron_calls_api() {

		$order = new WC_Order();
		$order->save();

		$order_id = $order->get_id();

		$api = $this->make(
			API::class,
			array(
				'check_address_for_order' => Expected::atLeastOnce(
					function( $order ) use ( $order_id ) {
						if ( ! $order instanceof WC_Order ) {
							throw new \Exception( 'order not passed to function' );
						}
						if ( $order->get_id() !== $order_id ) {
							throw new \Exception( 'order with incorrect id passed to function' );
						}
					}
				),
			)
		);

		$cron = new Cron( $api, 'bh-wc-address-validation', '1.0.0' );

		$cron->check_address_for_single_order( $order->get_id() );

	}

}
