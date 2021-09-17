<?php
/**
 * Mostly for manually running tests against known problem addresses.
 *
 * Uses the live USPS API, in test mode.
 */

namespace BrianHenryIE\WC_Address_Validation\API\Validators;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Address_Validation\Container;
use BrianHenryIE\WC_Address_Validation\USPS\Address;
use BrianHenryIE\WC_Address_Validation\USPS\AddressVerify;
use BrianHenryIE\WC_Address_Validation\USPS\USPSBase;


/**
 * @coversNothing
 */
class USPS_Address_Validator_Integration_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Takes addresses that were known to fail in the past and verifies they are handled correctly.
	 *
	 * e.g. where the apartment number is not on its own line.
	 *
	 * @param array $address_array
	 *
	 * @dataProvider bad_addresses
	 */
	public function test_bad_addresses( array $address_array ) {

		$usps_username = $_ENV['USPS_USERNAME'];

		$logger = new ColorLogger();

		$address_verify = new AddressVerify( $usps_username );
		$address_verify->setTestMode( true );

		$container = \Mockery::mock( Container::class );
		$container->shouldReceive( 'get' )->with( Container::USPS_API_ADDRESS_VERIFY )->andReturn( $address_verify );

		$usps_address_validator = new USPS_Address_Validator( $container, $logger );

		$result = $usps_address_validator->validate( $address_array );

		$this->assertTrue( $result['success'] );

	}

	public function bad_addresses() : array {

		$addresses = array(
			array(
				// Adding # or APT fixes this one.
				array(
					'address_1' => '4640 White Plains Road',
					'address_2' => '3S',
					'city'      => 'Bronx',
					'state'     => 'NY',
					'postcode'  => '10470',
					'country'   => 'US',
				),
				// Removing apostrophe fixes this one.
				array(
					'address_1' => '48 Taylor\'s Pond Drive',
					'address_2' => '',
					'city'      => 'Cary',
					'state'     => 'NC',
					'postcode'  => '27513',
					'country'   => 'US',
				),
			),

		);

		return $addresses;
	}

	/**
	 * The API seems to return two responses in one if requests are too close together.
	 *
	 * Let's make a request with two addresses to look at and understand the response better.
	 */
	public function test_two_addresses() {

		$usps_username  = $_ENV['USPS_USERNAME'];
		$address_verify = new AddressVerify( $usps_username );

		$address_1 = new Address();
		$address_1->setApt( '16' );
		$address_1->setAddress( '815 E St' );
		$address_1->setCity( 'Sacramento' );
		$address_1->setState( 'CA' );
		$address_1->setZip5( '95814' );
		$address_1->setZip4( '' );

		$address_2 = new Address();
		$address_2->setApt( '' );
		$address_2->setAddress( '1339 63rd St' );
		$address_2->setCity( 'Folsom' );
		$address_2->setState( 'NV' );
		$address_2->setZip5( '15819' );
		$address_2->setZip4( '' );

		$address_verify->addAddress( $address_1 );
		$address_verify->addAddress( $address_2 );

		$address_verify->setRevision( 1 );

		// Perform the request and return result
		$xml_response   = $address_verify->verify();
		$array_response = $address_verify->convertResponseToArray();

		// One address:
		// [ 'AddressValidateRespone => [ Address => [ Address1, Address2 ...

		// Two addresses:
		// [ 'AddressValidateRespone => [ Address => [ 0 => [ Address1, Address2 ...

		// See if it was successful
		$success = $address_verify->isSuccess();

		// If both addresses are valid, isSuccess is true.

		// isSuccess is false if one of the set is incorrect
	}
}
