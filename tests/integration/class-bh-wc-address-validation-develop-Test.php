<?php
/**
 * Class Plugin_Test. Tests the root plugin setup.
 *
 * @package BH_WC_Address_Validation
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WC_Address_Validation;

use BH_WC_Address_Validation\includes\BH_WC_Address_Validation;

/**
 * Verifies the plugin has been instantiated and added to PHP's $GLOBALS variable.
 */
class Plugin_Load_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test the main plugin object is added to PHP's GLOBALS and that it is the correct class.
	 */
	public function test_plugin_instantiated() {

		$this->assertArrayHasKey( 'bh_wc_address_validation', $GLOBALS );

		$this->assertInstanceOf( BH_WC_Address_Validation::class, $GLOBALS['bh_wc_address_validation'] );
	}

}
