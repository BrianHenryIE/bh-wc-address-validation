<?php

namespace BrianHenryIE\WC_Address_Validation\Admin;

use BrianHenryIE\WC_Address_Validation\Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Address_Validation\Admin\Plugins_Page
 */
class Plugins_Page_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::action_links
	 */
	public function test_settings_action_link_added_when_woocommerce_active() {

		$plugin_basename = 'bh-wc-address-validation/bh-wc-address-validation.php';
		$plugin_slug     = 'bh-wc-address-validation';

		\WP_Mock::userFunction(
			'is_plugin_active',
			array(
				'return' => true,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'args'       => array( 'admin.php?page=wc-settings&tab=shipping&section=bh-wc-address-validation' ),
				'return_arg' => 0,
			)
		);

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_slug' => $plugin_slug,
			)
		);

		$sut = new Plugins_Page( $settings );

		$links_array = array();
		$plugin_data = array();
		$context     = '';

		$result = $sut->action_links( $links_array, $plugin_basename, $plugin_data, $context );

		$this->assertIsArray( $result );

		$link_html = $result[0];

		$this->assertStringContainsString( 'Settings', $link_html );

		$this->assertStringContainsString( 'admin.php?page=wc-settings&tab=shipping&section=bh-wc-address-validation', $link_html );
	}


	/**
	 * @covers ::action_links
	 */
	public function test_settings_action_link_not_added_when_woocommerce_inactive() {

		$plugin_basename = 'bh-wc-address-validation/bh-wc-address-validation.php';

		\WP_Mock::userFunction(
			'is_plugin_active',
			array(
				'return' => false,
				'times'  => 1,
			)
		);

		$settings = $this->makeEmpty( Settings_Interface::class );

		$sut = new Plugins_Page( $settings );

		$links_array = array();
		$plugin_data = array();
		$context     = '';

		$result = $sut->action_links( $links_array, $plugin_basename, $plugin_data, $context );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );

	}

	/**
	 * @covers ::row_meta
	 */
	public function test_github_row_meta_link_added() {

		$plugin_basename = 'bh-wc-address-validation/bh-wc-address-validation.php';

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => $plugin_basename,
				'get_plugin_slug'     => 'bh-wc-address-validation',
			)
		);

		$sut = new Plugins_Page( $settings );

		$links_array = array();
		$plugin_data = array();
		$context     = '';

		$result = $sut->row_meta( $links_array, $plugin_basename, $plugin_data, $context );

		$this->assertIsArray( $result );

		$link_html = $result[0];

		$this->assertStringContainsString( 'View plugin on GitHub', $link_html );

		$this->assertStringContainsString( 'https://github.com/BrianHenryIE/bh-wc-address-validation', $link_html );
	}


}
