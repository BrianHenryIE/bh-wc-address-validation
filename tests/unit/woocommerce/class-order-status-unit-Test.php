<?php
/**
 * Tests
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Address_Validation\WooCommerce;

use BrianHenryIE\WC_Address_Validation\API_Interface;
use BrianHenryIE\WC_Address_Validation\Settings_Interface;
use Psr\Log\NullLogger;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Address_Validation\WooCommerce\Order_Status
 */
class Order_Status_Unit_Test extends \Codeception\Test\Unit {
	//
	// protected function _before() {
	// \WP_Mock::setUp();
	// }
	//
	// protected function _after() {
	// \WP_Mock::tearDown();
	// }

	/**
	 * The bad-address status should only be added to reports with paid statuses
	 */
	public function test_reports_status_filter_empty() {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new NullLogger();

		$sut = new Order_Status( $api, $settings, $logger );

		$result = $sut->add_to_reports_status_list( array() );

		$this->assertEmpty( $result );
	}

	public function test_reports_status_filter() {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new NullLogger();

		$sut = new Order_Status( $api, $settings, $logger );

		$result = $sut->add_to_reports_status_list( array( 'completed', 'processing', 'on-hold' ) );

		$this->assertContains( 'bad-address', $result );
	}
}
