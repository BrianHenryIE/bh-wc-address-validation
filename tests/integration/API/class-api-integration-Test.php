<?php
/**
 * Tests for BH_WC_Address_Validation main setup class. Tests the actions are correctly added.
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Address_Validation\API;

use Psr\Log\NullLogger;
use BrianHenryIE\WC_Address_Validation\USPS\AddressVerify;


/**
 * @coversNothing
 */
class API_Integration_Test extends \Codeception\TestCase\WPTestCase {


	public function test_usps_1() {

		$logger = new NullLogger();

		$usps_username = $_ENV['USPS_USERNAME'];

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_usps_username' => function() use ( $usps_username ) {
						return $usps_username;
				},
			)
		);

		$address_verify = $this->make( AddressVerify::class );

		$api = new API( $settings, $logger );

	}
}
