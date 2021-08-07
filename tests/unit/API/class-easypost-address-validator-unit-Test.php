<?php
/**
 * Address tests.
 *
 * TODO: This is an integration test!
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Address_Validation\API;

use BrianHenryIE\ColorLogger\ColorLogger;

/**
 * Class API_Test
 *
 * @coversDefaultClass \BrianHenryIE\WC_Address_Validation\API\EasyPost_Address_Validator
 */
class EasyPost_Address_Validator_Unit_Test extends \Codeception\Test\Unit {

	public function test_a_us_address() {

		$easypost_api_key = $_ENV['EASYPOST_API_KEY'];

		$logger = new ColorLogger();

		$address = array(
			'address_1' => '815 E ST',
			'address_2' => '16',
			'city'      => 'Sacramento',
			'state'     => 'CA',
			'postcode'  => '95814',
			'country'   => 'US',
		);

		$sut = new EasyPost_Address_Validator( $easypost_api_key, $logger );

		$result = $sut->validate( $address );

		$this->assertTrue( $result['success'] );

		$this->assertEquals( 'SACRAMENTO', $result['updated_address']['city'] );

	}



	public function test_am_international_address() {

		$easypost_api_key = $_ENV['EASYPOST_API_KEY'];

		$logger = new ColorLogger();

		$address = array(
			'address_1' => '1 Palace St',
			'address_2' => '',
			'city'      => 'Dublin',
			'state'     => 'Dublin',
			'postcode'  => '2',
			'country'   => 'IE',
		);

		$sut = new EasyPost_Address_Validator( $easypost_api_key, $logger );

		$result = $sut->validate( $address );

		$this->assertTrue( $result['success'] );

		$this->assertEquals( 'DUBLIN', $result['updated_address']['city'] );

	}

}
