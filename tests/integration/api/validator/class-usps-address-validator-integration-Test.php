<?php
/**
 * Mostly for manually running tests against known problem addresses.
 *
 * Uses the live USPS API, in test mode.
 */

namespace BrianHenryIE\WC_Address_Validation\API\Validators;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Address_Validation\USPS\Address;
use BrianHenryIE\WC_Address_Validation\USPS\AddressVerify;


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

		if ( ! isset( $_ENV['USPS_USERNAME'] ) ) {
			$this->markTestIncomplete( 'Needs USPS_USERNAME in .env.secret file.' );
		}

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

	public function bad_addresses(): array {

		$addresses = array(
			array(
				// Removing apostrophe fixes this one... no it didn't. (anon).
				array(
					'address_1' => '60 Taylor\'s Pond Drive',
					'address_2' => '',
					'city'      => 'Cary',
					'state'     => 'NC',
					'postcode'  => '27513',
					'country'   => 'US',
				),
			),
			array(
				// Military address cannot be validated so assume correct. (anon).
				array(
					'address_1' => 'Air Defense Company Z',
					'address_2' => 'Unit 12345',
					'city'      => 'FPO',
					'state'     => 'AP',
					'postcode'  => '96372',
					'country'   => 'US',
				),
			),
			array(
				// Missing space after E. (anon).
				array(
					'address_1' => '556 E.22nd st',
					'address_2' => 'Apt 1',
					'city'      => 'Ogden',
					'state'     => 'UT',
					'postcode'  => '94401',
					'country'   => 'US',
				),
			),
			array(
				// Number must come after the apartment.
				array(
					'address_1' => '4611 Brown street',
					'address_2' => '1 Apt',
					'city'      => 'Union City',
					'state'     => 'NJ',
					'postcode'  => '07087',
					'country'   => 'US',
				),
			),
			array(

				array(
					'address_1' => '3614 US 74',
					'address_2' => 'Suite I',
					'city'      => 'Wingate',
					'state'     => 'NC',
					'postcode'  => '28176',
					'country'   => 'US',
				),
			),
			array(
				// Passes with no space between the W/N part. (anon)
				array(
					'address_1' => 'W159 N4940 Graysland Dr',
					'address_2' => '',
					'city'      => 'menomnee falls',
					'state'     => 'WI',
					'postcode'  => '53051',
					'country'   => 'US',
				),
			),
			array(
				// Accidental space in street number. (anon)
				array(
					'address_1' => '13 12 63rd St',
					'address_2' => '',
					'city'      => 'Sacramento',
					'state'     => 'CA',
					'postcode'  => '95819',
					'country'   => 'US',
				),
			),
			array(
				// Replace Interstate with I and it passes.
				array(
					'address_1' => '4800 East Interstate 240 Service Rd',
					'address_2' => '',
					'city'      => 'Oklahoma City',
					'state'     => 'OK',
					'postcode'  => '73135',
					'country'   => 'US',
				),
			),
			array(
				// Remove duplicate information.
				array(
					'address_1' => '815 E St #16 Sacramento CA 95819',
					'address_2' => '#16',
					'city'      => 'Sacramento',
					'state'     => 'CA',
					'postcode'  => '95819',
					'country'   => 'US',
				),
			),
			array(
				// Failing with "no validator"... (anon).
				// TODO: doesn't correctly remove the duplicatation.
				array(
					'address_1' => '560 people\'s plz suite 130',
					'address_2' => 'Suite 130',
					'city'      => 'Newark',
					'state'     => 'DE',
					'postcode'  => '19702',
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

		if ( ! isset( $_ENV['USPS_USERNAME'] ) ) {
			$this->markTestIncomplete( 'Needs USPS_USERNAME in .env.secret file.' );
		}

		$usps_username = $_ENV['USPS_USERNAME'];

		$b = 'asd';

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
		$address_2->setAddress( '1312 63rd St' );
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
