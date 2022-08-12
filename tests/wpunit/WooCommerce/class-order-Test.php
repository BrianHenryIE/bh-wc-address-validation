<?php
/**
 *
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Address_Validation\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Address_Validation\API_Interface;
use BrianHenryIE\WC_Address_Validation\Settings_Interface;
use Codeception\Stub\Expected;
use WC_Order;
use BrianHenryIE\WC_Address_Validation\API\API;

/**
 * Class API_Test
 *
 * @see API
 * @coversDefaultClass \BrianHenryIE\WC_Address_Validation\WooCommerce\Order
 */
class Order_Test extends \Codeception\TestCase\WPTestCase {


	/**
	 * Test the correct entry is added to the array so "Verify address with USPS" will be added to the
	 * actions on the edit order page.
	 */
	public function test_add_admin_ui_order_action() {

		$logger = new ColorLogger();

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );

		$order = new Order( $api, $settings, $logger );

		$result = $order->add_admin_ui_order_action( array() );

		$this->assertIsArray( $result );
		$this->arrayHasKey( 'bh_wc_address_validate' );
		$this->assertEquals( 'Validate address', $result['bh_wc_address_validate'] );

	}

	public function test_order_action_handler() {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'check_address_for_order' => Expected::once(),
			)
		);

		$settings = $this->makeEmpty( Settings_Interface::class );

		$order = new Order( $api, $settings, $logger );

		$an_order = new WC_Order();

		$order->check_address_on_admin_order_action( $an_order );
	}

}
