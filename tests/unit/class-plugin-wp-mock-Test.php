<?php
/**
 * Tests for the root plugin file.
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WC_Address_Validation;

use BH_WC_Address_Validation\api\API;
use BH_WC_Address_Validation\includes\BH_WC_Address_Validation;

/**
 * Class Plugin_WP_Mock_Test
 */
class Plugin_WP_Mock_Test extends \Codeception\Test\Unit {

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

		$plugin_root_dir = dirname( __DIR__, 2 ) . '/src';

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
			)
		);

		// \WP_Mock::userFunction(
		// 'get_option'
		// );

		\WP_Mock::userFunction(
			'register_activation_hook'
		);

		\WP_Mock::userFunction(
			'register_deactivation_hook'
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh-wc-address-validation-log-level', 'notice' ),
				'return' => 'notice',
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'active_plugins' ),
				'return' => array(),
			)
		);


		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh-wc-address-validation-usps-username' ),
				'return' => null,
			)
		);

		require_once $plugin_root_dir . '/bh-wc-address-validation.php';

		$this->assertArrayHasKey( 'bh_wc_address_validation', $GLOBALS );

		$this->assertInstanceOf( API::class, $GLOBALS['bh_wc_address_validation'] );

	}


	/**
	 * Verifies the plugin does not output anything to screen.
	 */
	public function test_plugin_include_no_output() {

		$plugin_root_dir = dirname( __DIR__, 2 ) . '/src';

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
			)
		);

		\WP_Mock::userFunction(
			'register_activation_hook'
		);

		\WP_Mock::userFunction(
			'register_deactivation_hook'
		);

		ob_start();

		require_once $plugin_root_dir . '/bh-wc-address-validation.php';

		$printed_output = ob_get_contents();

		ob_end_clean();

		$this->assertEmpty( $printed_output );

	}

}
