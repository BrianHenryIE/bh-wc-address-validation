<?php
/**
 * Tests for BH_WC_Address_Validation main setup class. Tests the actions are correctly added.
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WC_Address_Validation\includes;

use BH_WC_Address_Validation\woocommerce\Order;

/**
 * Class Develop_Test
 */
class BH_WC_Address_Validation_Integration_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Verify action to call load textdomain is added.
	 */
	public function test_action_plugins_loaded_load_plugin_textdomain() {

		$action_name       = 'plugins_loaded';
		$expected_priority = 10;

		$class_type  = I18n::class;
		$method_name = 'load_plugin_textdomain';

		$hooked = $this->is_function_hooked_on_action( $class_type, $method_name, $action_name, $expected_priority );

		$this->assertTrue( $hooked );
	}

	/**
	 * Verify the key order status change action is added.
	 */
	public function test_action_order_status_change() {

		$action_name       = 'woocommerce_order_status_changed';
		$expected_priority = 10;
		$class_type        = Order::class;
		$method_name       = 'check_address_on_single_order_processing';

		$hooked = $this->is_function_hooked_on_action( $class_type, $method_name, $action_name, $expected_priority );

		$this->assertTrue( $hooked );

	}


	public function test_the_usps_tools_link_below_the_address() {

		$action_name       = 'woocommerce_admin_order_data_after_shipping_address';
		$expected_priority = 10;
		$class_type        = Order::class;
		$method_name       = 'add_link_to_usps_tools_zip_lookup';

		$hooked = $this->is_function_hooked_on_action( $class_type, $method_name, $action_name, $expected_priority );

		$this->assertTrue( $hooked );

	}


	protected function is_function_hooked_on_action( $class_type, $method_name, $action_name, $expected_priority = 10 ) {

		global $wp_filter;

		$this->assertArrayHasKey( $action_name, $wp_filter, "$method_name definitely not hooked to $action_name" );

		$actions_hooked = $wp_filter[ $action_name ];

		$this->assertArrayHasKey( $expected_priority, $actions_hooked, "$method_name definitely not hooked to $action_name priority $expected_priority" );

		$hooked_to_class = false;
		$hooked_method   = null;
		foreach ( $actions_hooked[ $expected_priority ] as $action ) {
			$action_function = $action['function'];
			if ( is_array( $action_function ) ) {
				$action_class = $action_function[0];
				if ( $action_class instanceof $class_type ) {
					$hooked_to_class = true;
					$action_method   = $action_function[1];
					if ( $method_name === $action_method ) {
						$hooked_method = $action_method;
						break;
					}
				}
			}
		}

		$this->assertTrue( $hooked_to_class, "No methods on an instance of $class_type hooked to $action_name" );

		$this->assertEquals( $method_name, $hooked_method, "Unexpected method name for $class_type class hooked to $action_name" );

		return true;
	}
}
