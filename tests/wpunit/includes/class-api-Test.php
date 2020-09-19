<?php
/**
 *
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WC_Address_Validation\api;

use Codeception\Stub\Expected;
use WC_Order;
use BH_WC_Address_Validation\USPS\AddressVerify;

/**
 * Class API_Test
 *
 * @see API
 */
class API_Test extends \Codeception\TestCase\WPTestCase {


	/**
	 * Mock a successful response from USPS.
	 */
	public function test_simple_success() {

		$this->markTestIncomplete();

		$address_verify = $this->make( AddressVerify::class );

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_usps_username' => '123',
			)
		);

		$api = new API( $settings, $address_verify );

		$order = $this->make( WC_Order::class );

		$api->check_address_for_order( $order );



		// USPS Should respond with an object with the updated address

		// $order should expect the appropriate methods called with that info, then saved
	}

	/**
	 * Mock a failure from USPS.
	 *
	 * Should add an order note, set the bad-address status and save the order.
	 */
	public function test_simple_failures() {

		$address_verify = $this->make(
			AddressVerify::class,
			array(
				'isSuccess'       => false,
				'getErrorMessage' => 'Failed because ABC...',
			)
		);

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_usps_username' => '123',
			)
		);

		$api = new API( $settings, $address_verify );

		$order = $this->make(
			WC_Order::class,
			array(
				'get_shipping_country' => 'US',
				'add_order_note'       => Expected::once(),
				'set_status'           => Expected::once(),
				'save'                 => Expected::once(),
			)
		);

		$api->check_address_for_order( $order );
	}
}
