<?php
/**
 * Tests for the root plugin file.
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Address_Validation;

use BrianHenryIE\WC_Address_Validation\API\API;
use BrianHenryIE\WC_Address_Validation\WP_Logger\Logger;
use Psr\Log\NullLogger;

/**
 * Class Plugin_WP_Mock_Test
 */
class BH_WC_Address_Validation_Unit_Test extends \Codeception\Test\Unit {

	protected function _before() {
		\WP_Mock::setUp();
	}

	protected function _after() {
		\WP_Mock::tearDown();
	}

	/**
	 * Verifies the plugin initialization.
	 */
	public function test_plugin_include() {

		// Prevents code-coverage counting, and removes the need to define the WordPress functions that are used in that class.
		\Patchwork\redefine(
			array( BH_WC_Address_Validation::class, '__construct' ),
			function ( $api, $settings, $logger ) {}
		);

		\Patchwork\redefine(
			array( Upgrader::class, 'do_upgrade' ),
			function () {}
		);

		\Patchwork\redefine(
			array( Logger::class, 'instance' ),
			function ( $settings ) {
				return new NullLogger();
			}
		);

		global $plugin_root_dir;

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'register_activation_hook',
			array( 'times' => 1 )
		);

		\WP_Mock::userFunction(
			'register_deactivation_hook',
			array(
				'times' => 1,
			)
		);

		ob_start();

		include $plugin_root_dir . '/bh-wc-address-validation.php';

		$printed_output = ob_get_contents();

		ob_end_clean();

		$this->assertEmpty( $printed_output );

		$this->assertArrayHasKey( 'bh_wc_address_validation', $GLOBALS );

		$this->assertInstanceOf( API::class, $GLOBALS['bh_wc_address_validation'] );
	}
}
