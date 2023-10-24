<?php
/**
 *
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Address_Validation\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Address_Validation\API\Validators\No_Validator_Exception;
use BrianHenryIE\WC_Address_Validation\Settings_Interface;
use BrianHenryIE\WC_Address_Validation\WP_Includes\Deactivator;
use Mockery;
use WC_Order;
use function Patchwork\restoreAll;

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

		$sut = new API( $address_validator, $settings, $logger );

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

		$sut = new API( $address_validator, $settings, $logger );

		$order = new WC_Order();
		$order->set_shipping_country( 'IE' );

		$sut->check_address_for_order( $order );
	}


	/**
	 * Mock a successful validated address from USPS, but no changes to the address
	 */
	public function test_simple_success_validated_not_updated() {

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
				'address_1' => 'ADDRESS 1',
				'address_2' => 'ADDRESS 2',
				'city'      => 'CITY',
				'state'     => 'STATE',
				'postcode'  => '12345',
				'country'   => 'US',
			),
			'message'         => '',
		);

		$address_validator = Mockery::mock( Address_Validator_Interface::class );
		$address_validator->shouldReceive( 'validate' )->andReturn( $result );

		$sut = new API( $address_validator, $settings, $logger );

		$order = Mockery::mock( WC_Order::class );

		$order->shouldReceive( 'get_meta' )->andReturn( array() );
		$order->shouldReceive( 'get_id' )->andReturn( '2' );

		$order->shouldReceive( 'get_shipping_country' )->andReturn( 'US' );

		$order->shouldReceive( 'get_shipping_address_1' )->andReturn( 'address 1' );
		$order->shouldReceive( 'get_shipping_address_2' )->andReturn( 'address 2' );
		$order->shouldReceive( 'get_shipping_city' )->andReturn( 'city' );
		$order->shouldReceive( 'get_shipping_state' )->andReturn( 'state' );
		$order->shouldReceive( 'get_shipping_postcode' )->andReturn( '12345' );

		$order->shouldReceive( 'get_customer_id' )->andReturn( 0 );

		$order->shouldReceive( 'get_status' )->andReturn( 'processing' );

		$order->shouldReceive( 'add_order_note' );

		$order->shouldReceive( 'update_meta_data' );
		$order->shouldReceive( 'save' );

		$sut->check_address_for_order( $order );
	}



	/**
	 * Mock a successful validated address from USPS, with a change to the address.
	 *
	 * Different billing address, so that will not be updated.
	 */
	public function test_simple_success_validated_and_updated() {

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
				'address_1' => 'ADDRESS 1',
				'address_2' => 'ADDRESS 2',
				'city'      => 'CITY',
				'state'     => 'STATE',
				'postcode'  => '54321',
				'country'   => 'US',
			),
			'message'         => '',
		);

		$address_validator = Mockery::mock( Address_Validator_Interface::class );
		$address_validator->shouldReceive( 'validate' )->andReturn( $result );

		$sut = new API( $address_validator, $settings, $logger );

		$order = Mockery::mock( WC_Order::class );

		$order->shouldReceive( 'get_meta' )->andReturn( array() );
		$order->shouldReceive( 'get_id' )->andReturn( '2' );

		$order->shouldReceive( 'get_shipping_country' )->andReturn( 'US' );

		$order->shouldReceive( 'get_shipping_address_1' )->andReturn( 'address 1' );
		$order->shouldReceive( 'get_shipping_address_2' )->andReturn( 'address 2' );
		$order->shouldReceive( 'get_shipping_city' )->andReturn( 'city' );
		$order->shouldReceive( 'get_shipping_state' )->andReturn( 'state' );
		$order->shouldReceive( 'get_shipping_postcode' )->andReturn( '12345' );

		$order->shouldReceive( 'get_billing_address_1' )->andReturn( 'address 3' );
		$order->shouldReceive( 'get_billing_address_2' )->andReturn( 'address 4' );
		$order->shouldReceive( 'get_billing_city' )->andReturn( 'city' );
		$order->shouldReceive( 'get_billing_state' )->andReturn( 'state' );
		$order->shouldReceive( 'get_billing_postcode' )->andReturn( '12345' );
		$order->shouldReceive( 'get_billing_country' )->andReturn( 'US' );

		$order->shouldReceive( 'get_customer_id' )->andReturn( 0 );

		$order->shouldReceive( 'set_shipping_address_1' )->with( 'ADDRESS 1' );
		$order->shouldReceive( 'set_shipping_address_2' )->with( 'ADDRESS 2' );
		$order->shouldReceive( 'set_shipping_city' )->with( 'CITY' );
		$order->shouldReceive( 'set_shipping_state' )->with( 'STATE' );
		$order->shouldReceive( 'set_shipping_postcode' )->with( '54321' );

		$order->shouldReceive( 'get_status' )->andReturn( 'processing' );

		$order->shouldReceive( 'add_order_note' );

		$order->shouldReceive( 'update_meta_data' );
		$order->shouldReceive( 'save' );

		$sut->check_address_for_order( $order );
	}



	/**
	 * Mock a successful validated address from USPS, with a change to the address.
	 *
	 * Same billing address, so that _will_ be updated.
	 */
	public function test_simple_success_validated_and_updated_matching_billing() {

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
				'address_1' => 'ADDRESS 1',
				'address_2' => 'ADDRESS 2',
				'city'      => 'CITY',
				'state'     => 'STATE',
				'postcode'  => '54321',
				'country'   => 'US',
			),
			'message'         => '',
		);

		$address_validator = Mockery::mock( Address_Validator_Interface::class );
		$address_validator->shouldReceive( 'validate' )->andReturn( $result );

		$sut = new API( $address_validator, $settings, $logger );

		$order = Mockery::mock( WC_Order::class );

		$order->shouldReceive( 'get_meta' )->andReturn( array() );
		$order->shouldReceive( 'get_id' )->andReturn( '2' );

		$order->shouldReceive( 'get_shipping_country' )->andReturn( 'US' );

		$order->shouldReceive( 'get_shipping_address_1' )->andReturn( 'address 1' );
		$order->shouldReceive( 'get_shipping_address_2' )->andReturn( 'address 2' );
		$order->shouldReceive( 'get_shipping_city' )->andReturn( 'city' );
		$order->shouldReceive( 'get_shipping_state' )->andReturn( 'state' );
		$order->shouldReceive( 'get_shipping_postcode' )->andReturn( '12345' );

		$order->shouldReceive( 'get_billing_address_1' )->andReturn( 'address 1' );
		$order->shouldReceive( 'get_billing_address_2' )->andReturn( 'address 2' );
		$order->shouldReceive( 'get_billing_city' )->andReturn( 'city' );
		$order->shouldReceive( 'get_billing_state' )->andReturn( 'state' );
		$order->shouldReceive( 'get_billing_postcode' )->andReturn( '12345' );
		$order->shouldReceive( 'get_billing_country' )->andReturn( 'US' );

		$order->shouldReceive( 'get_customer_id' )->andReturn( 0 );

		$order->shouldReceive( 'set_shipping_address_1' )->with( 'ADDRESS 1' );
		$order->shouldReceive( 'set_shipping_address_2' )->with( 'ADDRESS 2' );
		$order->shouldReceive( 'set_shipping_city' )->with( 'CITY' );
		$order->shouldReceive( 'set_shipping_state' )->with( 'STATE' );
		$order->shouldReceive( 'set_shipping_postcode' )->with( '54321' );

		$order->shouldReceive( 'set_billing_address_1' )->with( 'ADDRESS 1' );
		$order->shouldReceive( 'set_billing_address_2' )->with( 'ADDRESS 2' );
		$order->shouldReceive( 'set_billing_city' )->with( 'CITY' );
		$order->shouldReceive( 'set_billing_state' )->with( 'STATE' );
		$order->shouldReceive( 'set_billing_postcode' )->with( '54321' );

		$order->shouldReceive( 'get_status' )->andReturn( 'processing' );

		$order->shouldReceive( 'add_order_note' );

		$order->shouldReceive( 'update_meta_data' );
		$order->shouldReceive( 'save' );

		$sut->check_address_for_order( $order );
	}



	/**
	 * Mock a successful validated address from USPS, with a change to the address.
	 *
	 * Same billing address, so that _will_ be updated. Customer usetr object exists.
	 */
	public function test_simple_success_validated_and_updated_matching_billing_customer() {

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
				'address_1' => 'ADDRESS 1',
				'address_2' => 'ADDRESS 2',
				'city'      => 'CITY',
				'state'     => 'STATE',
				'postcode'  => '54321',
				'country'   => 'US',
			),
			'message'         => '',
		);

		$address_validator = Mockery::mock( Address_Validator_Interface::class );
		$address_validator->shouldReceive( 'validate' )->andReturn( $result );

		$sut = new API( $address_validator, $settings, $logger );

		$order = Mockery::mock( WC_Order::class );

		$order->shouldReceive( 'get_meta' )->andReturn( array() );
		$order->shouldReceive( 'get_id' )->andReturn( '2' );

		// $store = apply_filters( 'woocommerce_' . $object_type . '_data_store', $this->stores[ $object_type ] );
		$store_mock = Mockery::mock( \WC_Customer_Data_Store::class );

		// need to set object_read true here so changes will be available later
		$store_mock->shouldReceive( 'read' )->with(
			Mockery::on(
				function ( \WC_Customer $customer ) {

					$customer->set_object_read();

					return true;
				}
			)
		);

		$store_mock->shouldReceive( 'update' )->with(
			Mockery::on(
				function ( $customer ) {

					$changes = $customer->get_changes();

					return '54321' === $changes['billing']['postcode'] && '54321' === $changes['shipping']['postcode'];
				}
			)
		);

		add_filter(
			'woocommerce_customer_data_store',
			function ( $store ) use ( $store_mock ) {
				return $store_mock;
			}
		);

		$order->shouldReceive( 'get_shipping_country' )->andReturn( 'US' );

		$order->shouldReceive( 'get_shipping_address_1' )->andReturn( 'address 1' );
		$order->shouldReceive( 'get_shipping_address_2' )->andReturn( 'address 2' );
		$order->shouldReceive( 'get_shipping_city' )->andReturn( 'city' );
		$order->shouldReceive( 'get_shipping_state' )->andReturn( 'state' );
		$order->shouldReceive( 'get_shipping_postcode' )->andReturn( '12345' );

		$order->shouldReceive( 'get_billing_address_1' )->andReturn( 'address 1' );
		$order->shouldReceive( 'get_billing_address_2' )->andReturn( 'address 2' );
		$order->shouldReceive( 'get_billing_city' )->andReturn( 'city' );
		$order->shouldReceive( 'get_billing_state' )->andReturn( 'state' );
		$order->shouldReceive( 'get_billing_postcode' )->andReturn( '12345' );
		$order->shouldReceive( 'get_billing_country' )->andReturn( 'US' );

		$order->shouldReceive( 'get_customer_id' )->andReturn( 2 );

		$order->shouldReceive( 'set_shipping_address_1' )->with( 'ADDRESS 1' );
		$order->shouldReceive( 'set_shipping_address_2' )->with( 'ADDRESS 2' );
		$order->shouldReceive( 'set_shipping_city' )->with( 'CITY' );
		$order->shouldReceive( 'set_shipping_state' )->with( 'STATE' );
		$order->shouldReceive( 'set_shipping_postcode' )->with( '54321' );

		$order->shouldReceive( 'set_billing_address_1' )->with( 'ADDRESS 1' );
		$order->shouldReceive( 'set_billing_address_2' )->with( 'ADDRESS 2' );
		$order->shouldReceive( 'set_billing_city' )->with( 'CITY' );
		$order->shouldReceive( 'set_billing_state' )->with( 'STATE' );
		$order->shouldReceive( 'set_billing_postcode' )->with( '54321' );

		$order->shouldReceive( 'get_status' )->andReturn( 'processing' );

		$order->shouldReceive( 'add_order_note' );

		$order->shouldReceive( 'update_meta_data' );
		$order->shouldReceive( 'save' );

		$sut->check_address_for_order( $order );
	}


	/**
	 *
	 */
	public function test_should_return_early_when_already_checked() {

		$logger = new ColorLogger();

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_usps_username' => '123',
			)
		);

		$address_validator = Mockery::mock( Address_Validator_Interface::class );

		$sut = new API( $address_validator, $settings, $logger );

		$order = Mockery::mock( WC_Order::class );

		$order->shouldReceive( 'get_meta' )->with( API::BH_WC_ADDRESS_VALIDATION_CHECKED_META, true )->andReturn( array( '123' ) );
		$order->shouldReceive( 'get_meta' )->with( Deactivator::DEACTIVATED_BAD_ADDRESS_META_KEY, true )->andReturn( null );

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

		$sut = new API( $address_validator, $settings, $logger );

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

	/**
	 * When only USPS validator was present, all international orders were being marked as bad address!
	 */
	public function test_international_address_with_no_validator() {

		self::markTestSkipped( 'Change from exception to null validator' );

		$logger            = new ColorLogger();
		$settings          = $this->makeEmpty( Settings_Interface::class );
		$address_validator = $this->makeEmpty( Address_Validator_Interface::class );

		$sut = new API( $address_validator, $settings, $logger );

		$address = array(
			'address_1' => 'ADDRESS 1',
			'address_2' => 'ADDRESS 2',
			'city'      => 'CITY',
			'state'     => 'Dublin',
			'postcode'  => '2',
			'country'   => 'IE',
		);

		$exception = null;

		try {
			$result = $sut->validate_address( $address );
		} catch ( No_Validator_Exception $e ) {
			$exception = $e;
		}

		$this->assertNotNull( $exception );
	}
}
