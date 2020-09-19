<?php

namespace BH_WC_Address_Validation\woocommerce;

use BH_WC_Address_Validation\includes\Cron;
use WC_Order;

class Order_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * When an order status changes to "processing" (awaiting payment), it should create a cron job to check the order's address
	 */
	public function test_new_order_job_is_scheduled() {

		$order = new WC_Order();
		$order->set_status( 'awaiting-payment' );
		$order->save();

		$wp_cron_before    = _get_cron_array();
		$cron_hooks_before = array_keys( call_user_func_array( 'array_merge', $wp_cron_before ) );
		$this->assertNotContains( Cron::CHECK_ADDRESS_CRON_JOB, $cron_hooks_before );

		// Act
		$order->set_status( 'processing' );
		$order->save();

		$wp_cron_after    = _get_cron_array();
		$cron_hooks_after = array_keys( call_user_func_array( 'array_merge', $wp_cron_after ) );

		$this->assertContains( CRON::CHECK_ADDRESS_CRON_JOB, $cron_hooks_after, 'cron job was not scheduled when order was marked processing.' );

	}

	/**
	 * Check the bad-address status is properly added to wc_get_is_paid_statuses() for reporting.
	 */
	public function test_check_paid_order_statuses() {

		$statuses = wc_get_is_paid_statuses();

		$this->assertContains( 'bad-address', $statuses );
	}

	/**
	 * Test the order action display is hooked to the right filter.
	 */
	public function test_admin_ui_order_actions_filter_added() {

		$action_name       = 'woocommerce_order_actions';
		$expected_priority = 10;
		$class_type        = Order::class;
		$function_name     = 'add_admin_ui_order_action';

		global $wp_filter;

		$this->assertArrayHasKey( $action_name, $wp_filter, "$function_name definitely not hooked to $action_name – nothing hooked to it" );

		$actions_hooked = $wp_filter[ $action_name ]->callbacks;

		$this->assertArrayHasKey( $expected_priority, $actions_hooked, "$function_name definitely not hooked to $action_name priority $expected_priority – nothing hooked at that priority" );

		$hooked_methods = array();

		foreach ( array_merge( ...$actions_hooked ) as $action ) {
			if ( is_array( $action['function'] ) ) {
				if ( $action['function'][0] instanceof $class_type ) {
					$hooked_methods[] = $action['function'][1];
				}
			}
		}

		$this->assertNotEmpty( $hooked_methods, "No methods on an instance of $class_type hooked to $action_name" );

		$this->assertContains( $function_name, $hooked_methods, "{$class_type}->{$function_name} not hooked to {$action_name}[{$expected_priority}]" );
	}


	/**
	 * Test the order action handler is hooked to the right action.
	 */
	public function test_order_action_handler_hook_added() {

		$action_name       = 'woocommerce_order_action_bh_wc_address_verify';
		$expected_priority = 10;
		$class_type        = Order::class;
		$function_name     = 'check_address_on_admin_order_action';

		global $wp_filter;

		$this->assertArrayHasKey( $action_name, $wp_filter, "$function_name definitely not hooked to $action_name – nothing hooked to it" );

		$actions_hooked = $wp_filter[ $action_name ]->callbacks;

		$this->assertArrayHasKey( $expected_priority, $actions_hooked, "$function_name definitely not hooked to $action_name priority $expected_priority – nothing hooked at that priority" );

		$hooked_methods = array();

		foreach ( array_merge( ...$actions_hooked ) as $action ) {
			if ( is_array( $action['function'] ) ) {
				if ( $action['function'][0] instanceof $class_type ) {
					$hooked_methods[] = $action['function'][1];
				}
			}
		}

		$this->assertNotEmpty( $hooked_methods, "No methods on an instance of $class_type hooked to $action_name" );

		$this->assertContains( $function_name, $hooked_methods, "{$class_type}->{$function_name} not hooked to {$action_name}[{$expected_priority}]" );

	}

}
