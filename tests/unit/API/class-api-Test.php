<?php
/**
 * Tests
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Address_Validation\Includes;

use BrianHenryIE\WC_Address_Validation\API\API;
use BrianHenryIE\WC_Address_Validation\API\Settings_Interface;
use BrianHenryIE\WC_Address_Validation\USPS\AddressVerify;

/**
 * Class API_Test
 *
 * @coversDefaultClass \BrianHenryIE\WC_Address_Validation\API\API
 */
class API_Test extends \Codeception\Test\Unit {
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

		$this->markTestIncomplete();

		$usps_username = $_ENV['USPS_USERNAME'];

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_usps_username' => function() use ( $usps_username ) {
												return $usps_username; },
			)
		);

		$address_verify = $this->make( AddressVerify::class );

		$api = new API( $settings, $address_verify );

	}


}
