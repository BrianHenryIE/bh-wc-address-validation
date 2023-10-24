<?php


namespace BrianHenryIE\WC_Address_Validation\API;

/**
 *
 * @coversDefaultClass \BrianHenryIE\WC_Address_Validation\API\Settings
 */
class Settings_Unit_Test extends \Codeception\Test\Unit {


	protected function _before() {
		\WP_Mock::setUp();
	}

	protected function _after() {
		\WP_Mock::tearDown();
	}


	public function test_get_usps_username() {
		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( Settings::USPS_USERNAME_OPTION ),
				'return' => false,
			)
		);

		$this->assertEquals( 'bh_wc_address_validation_usps_username', Settings::USPS_USERNAME_OPTION );

		$sut = new Settings();
		$sut->get_usps_username();
	}
}
