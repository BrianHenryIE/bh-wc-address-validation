<?php

namespace BrianHenryIE\WC_Address_Validation\API;

/**
 * Class Settings_WPUnit_Test
 *
 * @package BrianHenryIE\WC_Address_Validation\API
 * @coversDefaultClass \BrianHenryIE\WC_Address_Validation\API\Settings
 */
class Settings_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Verify the version in settings matches the versions in the main plugin file.
	 *
	 * @see BH_WC_ADDRESS_VALIDATION_VERSION
	 *
	 * @covers ::get_plugin_version
	 */
	public function test_settings_version() {

		global $plugin_root_dir;

		$settings = new Settings();

		$plugin_data = get_plugin_data( "$plugin_root_dir/{$settings->get_plugin_slug()}.php", false, false );

		$this->assertEquals( $settings->get_plugin_version(), $plugin_data['Version'] );

		$plugin_file = file_get_contents( "$plugin_root_dir/{$settings->get_plugin_slug()}.php" );

		if ( 1 !== preg_match( '/define\( \'BH_WC_ADDRESS_VALIDATION_VERSION\', \'(\d+\.\d+\.\d+)\' \);/', $plugin_file, $output_array ) ) {
			$this->fail();
		}

		$bh_wc_gateway_load_balancer_version = $output_array[1];

		$this->assertEquals( $bh_wc_gateway_load_balancer_version, $plugin_data['Version'] );
		$this->assertEquals( $settings->get_plugin_version(), $bh_wc_gateway_load_balancer_version );

	}

	/**
	 * @covers ::get_plugin_name
	 */
	public function test_plugin_name_matches() {

		global $plugin_root_dir;

		$settings = new Settings();

		$plugin_data = get_plugin_data( "$plugin_root_dir/{$settings->get_plugin_slug()}.php", false, false );

		$this->assertEquals( $settings->get_plugin_name(), $plugin_data['Name'] );

	}
}
