<?php
/**
 * Address tests.
 *
 * TODO: This is an integration test - it calls the USPS API.
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Address_Validation\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Address_Validation\Container;
use BrianHenryIE\WC_Address_Validation\USPS\AddressVerify;
use Mockery;

/**
 * Class API_Test
 *
 * @coversDefaultClass \BrianHenryIE\WC_Address_Validation\API\USPS_Address_Validator
 */
class USPS_Address_Validator_Test extends \Codeception\Test\Unit {
	//
	// protected function _before() {
	// \WP_Mock::setUp();
	// }
	//
	// protected function _after() {
	// \WP_Mock::tearDown();
	// }

	/**
	 * Verifies the plugin initialization.
	 */
	public function test_address_verify() {

		$usps_username = $_ENV['USPS_USERNAME'];

		$logger = new ColorLogger();

		$address_verify = new AddressVerify( $usps_username );
		$address_verify->setTestMode( true );

		$container = Mockery::mock( Container::class );
		$container->shouldReceive( 'get' )->with( Container::USPS_API_ADDRESS_VERIFY )->andReturn( $address_verify );

		$usps_address_validator = new USPS_Address_Validator( $container, $logger );

		$address = array(
			'address_1' => '815 E ST',
			'address_2' => '16',
			'city'      => 'Sacramento',
			'state'     => 'CA',
			'postcode'  => '95814',
			'country'   => 'US',
		);

		$result = $usps_address_validator->validate( $address );

		$this->assertTrue( $result['success'] );
	}


}
