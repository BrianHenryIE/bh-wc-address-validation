<?php
/**
 *
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Address_Validation\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Address_Validation\Container;
use Codeception\Stub\Expected;
use Mockery;
use WC_Order;
use BrianHenryIE\WC_Address_Validation\USPS\AddressVerify;

/**
 * Class API_Test
 *
 * @see API
 * @coversDefaultClass \BrianHenryIE\WC_Address_Validation\API\API
 */
class API_Test extends \Codeception\TestCase\WPTestCase {

	public function test_uses_usps_for_us_orders() {

		$logger = new ColorLogger();

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_usps_username' => '123',
			)
		);

		$result = array(
			'success'         => true,
			'updated_address' => array(
				'address_1' => '',
				'address_2' => '',
				'city'      => '',
				'state'     => '',
				'postcode'  => '',
				'country'   => '',
			),
			'message'         => '',

		);

		$address_validator = Mockery::mock( Address_Validator_Interface::class );
		$address_validator->shouldReceive( 'validate' )->andReturn( $result );

		$container = Mockery::mock( Container::class );
		$container->shouldReceive( 'get' )->with( Container::USPS_ADDRESS_VALIDATOR )->andReturn( $address_validator );

		$sut = new API( $container, $settings, $logger );

		$order = new WC_Order();
		$order->set_shipping_country( 'US' );

		$sut->check_address_for_order( $order );

	}


	public function test_uses_easypost_for_international_orders() {

		$logger = new ColorLogger();

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_usps_username' => '123',
			)
		);

		$result = array(
			'success'         => true,
			'updated_address' => array(
				'address_1' => '',
				'address_2' => '',
				'city'      => '',
				'state'     => '',
				'postcode'  => '',
				'country'   => '',
			),
			'message'         => '',
		);

		$address_validator = Mockery::mock( Address_Validator_Interface::class );
		$address_validator->shouldReceive( 'validate' )->andReturn( $result );

		$container = Mockery::mock( Container::class );
		$container->shouldReceive( 'get' )->with( Container::EASYPOST_ADDRESS_VALIDATOR )->andReturn( $address_validator );

		$sut = new API( $container, $settings, $logger );

		$order = new WC_Order();
		$order->set_shipping_country( 'IE' );

		$sut->check_address_for_order( $order );

	}


	/**
	 * Mock a successful response from USPS.
	 */
	public function test_simple_success() {

		$logger = new ColorLogger();

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_usps_username' => '123',
			)
		);

		$result = array(
			'success'         => true,
			'updated_address' => array(
				'address_1' => 'address 1',
				'address_2' => 'address 2',
				'city'      => 'city',
				'state'     => 'state',
				'postcode'  => 'zip',
				'country'   => 'US',
			),
			'message'         => '',
		);

		$address_validator = Mockery::mock( Address_Validator_Interface::class );
		$address_validator->shouldReceive( 'validate' )->andReturn( $result );

		$container = Mockery::mock( Container::class );
		$container->shouldReceive( 'get' )->with( Container::USPS_ADDRESS_VALIDATOR )->andReturn( $address_validator );

		$sut = new API( $container, $settings, $logger );

		$order = Mockery::mock( WC_Order::class );

		$order->shouldReceive( 'get_meta' )->andReturn( array() );
		$order->shouldReceive( 'get_id' )->andReturn( '2' );

		$order->shouldReceive( 'get_shipping_country' )->andReturn( 'US' );

		$order->shouldReceive( 'get_shipping_address_1' )->andReturn( 'address 1' );
		$order->shouldReceive( 'get_shipping_address_2' )->andReturn( 'address 2' );
		$order->shouldReceive( 'get_shipping_city' )->andReturn( 'city' );
		$order->shouldReceive( 'get_shipping_state' )->andReturn( 'state' );
		$order->shouldReceive( 'get_shipping_postcode' )->andReturn( 'zip' );

		$order->shouldReceive( 'get_status' )->andReturn( 'processing' );

		$order->shouldReceive( 'add_order_note' );

		$order->shouldReceive( 'update_meta_data' );
		$order->shouldReceive( 'save' );

		$sut->check_address_for_order( $order );

	}

	/**
	 * Mock a failure
	 *
	 * Should add an order note, set the bad-address status and save the order.
	 */
	public function test_simple_failures() {

		$logger = new ColorLogger();

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_usps_username' => '123',
			)
		);

		$result = array(
			'success'       => false,
			'error_message' => 'error-message',
		);

		$address_validator = Mockery::mock( Address_Validator_Interface::class );
		$address_validator->shouldReceive( 'validate' )->andReturn( $result );

		$container = Mockery::mock( Container::class );
		$container->shouldReceive( 'get' )->with( Container::USPS_ADDRESS_VALIDATOR )->andReturn( $address_validator );

		$sut = new API( $container, $settings, $logger );

		$order = Mockery::mock( WC_Order::class );

		$order->shouldReceive( 'get_meta' )->andReturn( array() );
		$order->shouldReceive( 'get_id' )->andReturn( '2' );

		$order->shouldReceive( 'get_shipping_country' )->andReturn( 'US' );

		$order->shouldReceive( 'get_shipping_address_1' )->andReturn( 'address 1' );
		$order->shouldReceive( 'get_shipping_address_2' )->andReturn( 'address 2' );
		$order->shouldReceive( 'get_shipping_city' )->andReturn( 'city' );
		$order->shouldReceive( 'get_shipping_state' )->andReturn( 'state' );
		$order->shouldReceive( 'get_shipping_postcode' )->andReturn( 'zip' );

		$order->shouldReceive( 'get_status' )->andReturn( 'processing' );

		$order->shouldReceive( 'set_status' )->with( 'bad-address' );
		$order->shouldReceive( 'add_order_note' );

		$order->shouldReceive( 'update_meta_data' );
		$order->shouldReceive( 'save' );

		$sut->check_address_for_order( $order );

	}
}
