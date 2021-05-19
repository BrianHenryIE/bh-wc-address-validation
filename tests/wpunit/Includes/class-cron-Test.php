<?php
/**
 *
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Address_Validation\Includes;

use BrianHenryIE\WC_Address_Validation\API\API_Interface;
use BrianHenryIE\WC_Address_Validation\API\Settings_Interface;
use Psr\Log\NullLogger;
use Codeception\Stub\Expected;
use WC_Order;
use BrianHenryIE\WC_Address_Validation\API\API;

/**
 * Class Cron_Test
 *
 * @see Cron
 */
class Cron_Test extends \Codeception\TestCase\WPTestCase {

	public function test_cron_calls_api() {

		$this->markTestSkipped();

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

	public function test_cron_is_registered() {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new NullLogger();

		$cron = new Cron( $api, $settings, $logger );

		assert( ! wp_next_scheduled( Cron::RECHECK_BAD_ADDRESSES_CRON_JOB ) );

		$cron->add_cron_jon();

		$this->assertNotFalse( wp_next_scheduled( Cron::RECHECK_BAD_ADDRESSES_CRON_JOB ) );
	}

	public function test_recheck_bad_addresses_cron_calls_api() {

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'recheck_bad_address_orders' => Expected::once(),
			)
		);

		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new NullLogger();

		$cron = new Cron( $api, $settings, $logger );

		$cron->recheck_bad_address_orders();

	}


}
