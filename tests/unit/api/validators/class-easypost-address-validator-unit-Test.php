<?php
/**
 * Address tests.
 *
 * TODO: This is an integration test!
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Address_Validation\API\Validators;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Address_Validation\EasyPost\Requestor;

/**
 * Class API_Test
 *
 * @coversDefaultClass \BrianHenryIE\WC_Address_Validation\API\Validators\EasyPost_Address_Validator
 */
class EasyPost_Address_Validator_Unit_Test extends \Codeception\Test\Unit {

	protected function _after() {
		\Patchwork\restoreAll();
	}

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

		/**
		 * @see Requestor::request()
		 */
		\Patchwork\redefine(
			array( \BrianHenryIE\WC_Address_Validation\EasyPost\Requestor::class, 'request' ),
			function ( $method, $url, $params = null, $apiKeyRequired = true ) {
				$response = array(
					'address' =>
						array(
							'id'               => 'adr_1bac2bc50c1d4b2786502b6e2248c201',
							'object'           => 'Address',
							'created_at'       => '2021-08-09T23:55:19+00:00',
							'updated_at'       => '2021-08-09T23:55:19+00:00',
							'name'             => null,
							'company'          => null,
							'street1'          => '815 E ST APT 16',
							'street2'          => '',
							'city'             => 'SACRAMENTO',
							'state'            => 'CA',
							'zip'              => '95814-1341',
							'country'          => 'US',
							'phone'            => null,
							'email'            => null,
							'mode'             => 'test',
							'carrier_facility' => null,
							'residential'      => true,
							'federal_tax_id'   => null,
							'state_tax_id'     => null,
							'verifications'    =>
								array(
									'zip4'     =>
										array(
											'success' => true,
											'errors'  =>
												array(),
											'details' => null,
										),
									'delivery' =>
										array(
											'success' => true,
											'errors'  =>
												array(),
											'details' =>
												array(
													'latitude' => 38.58644,
													'longitude' => -121.49357,
													'time_zone' => 'America/Los_Angeles',
												),
										),
								),
						),
				);

				return array( $response, $_ENV['EASYPOST_API_KEY'] );
			}
		);

		$sut = new EasyPost_Address_Validator( $easypost_api_key, $logger );

		$result = $sut->validate( $address );

		$this->assertTrue( $result['success'] );

		$this->assertEquals( 'SACRAMENTO', $result['updated_address']['city'] );
	}



	public function test_an_international_address() {

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
