<?php
/**
 *
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Address_Validation\WooCommerce;

use Codeception\Stub\Expected;
use WC_Order;
use BrianHenryIE\WC_Address_Validation\API\API;

/**
 * Class API_Test
 *
 * @see API
 */
class Order_Test extends \Codeception\TestCase\WPTestCase {


	/**
	 * Test the correct entry is added to the array so "Verify address with USPS" will be added to the
	 * actions on the edit order page.
	 */
	public function test_add_admin_ui_order_action() {

		$order = new Order( null, 'bh-wc-address-validation', '1.0.0' );

		$result = $order->add_admin_ui_order_action( array() );

		$this->assertIsArray( $result );
		$this->arrayHasKey( 'bh_wc_address_validate' );
		$this->assertEquals( 'Validate address', $result['bh_wc_address_validate'] );

	}

	public function test_order_action_handler() {

		$this->markTestSkipped( 'unimplemented' );

		$api = $this->make(
			API::class,
			array(
				'check_address_for_order' => Expected::once(),
			)
		);

		$order = new Order( $api, 'bh-wc-address-validation', '1.0.0' );

		$an_order = new WC_Order();

		$order->check_address_on_admin_order_action( $an_order );
	}

}
