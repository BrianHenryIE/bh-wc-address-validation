<?php
/**
 * Address tests.
 *
 * TODO: This is an integration test - it calls the USPS API.
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Address_Validation\API\Validators;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Address_Validation\USPS\AddressVerify;

/**
 * Class USPS_Address_Validator_Test
 *
 * @coversDefaultClass \BrianHenryIE\WC_Address_Validation\API\Validators\USPS_Address_Validator
 */
class USPS_Address_Validator_Test extends \Codeception\Test\Unit {


	protected function _before() {
		\WP_Mock::setUp();
	}

	protected function _after() {
		\WP_Mock::tearDown();
		\Patchwork\restoreAll();
	}

	/**
	 * Verifies the plugin initialization.
	 */
	public function test_address_verify() {

		$usps_username = $_ENV['USPS_USERNAME'];

		$logger = new ColorLogger();

		$address_verify = new AddressVerify( $usps_username );
		$address_verify->setTestMode( true );

		$usps_address_validator = new USPS_Address_Validator( $address_verify, $logger );

		$address = array(
			'address_1' => '815 E ST',
			'address_2' => '16',
			'city'      => 'Sacramento',
			'state'     => 'CA',
			'postcode'  => '95814',
			'country'   => 'US',
		);

		\WP_Mock::userFunction(
			'normalize_whitespace',
			array(
				'return_arg' => true,
			)
		);

		$result = $usps_address_validator->validate( $address );

		$this->assertTrue( $result['success'] );
	}
}
