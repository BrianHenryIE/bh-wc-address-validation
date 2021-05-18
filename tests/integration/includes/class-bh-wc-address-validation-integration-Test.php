<?php
/**
 * Tests for BH_WC_Address_Validation main setup class. Tests the actions are correctly added.
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WC_Address_Validation\includes;

use BH_WC_Address_Validation\woocommerce\Order;
use BH_WC_Address_Validation\woocommerce\Order_Status;

/**
 * Class Develop_Test
 */
class BH_WC_Address_Validation_Integration_Test extends \Codeception\TestCase\WPTestCase {


    public function hooks() {
        $hooks = array(
            array( 'plugins_loaded', I18n::class, 'load_plugin_textdomain'),
            array( 'woocommerce_order_status_changed', Order::class, 'check_address_on_single_order_processing' ),
            array( 'woocommerce_admin_order_data_after_shipping_address', Order::class, 'add_link_to_usps_tools_zip_lookup' ),
            array( 'woocommerce_reports_order_statuses', Order_Status::class, 'add_to_reports_status_list' ),
        );
        return $hooks;
    }

    /**
     * @dataProvider hooks
     */
    public function test_is_function_hooked_on_action( $action_name, $class_type, $method_name, $expected_priority = 10 ) {

        global $wp_filter;

        $this->assertArrayHasKey( $action_name, $wp_filter, "$method_name definitely not hooked to $action_name" );

        $actions_hooked = $wp_filter[ $action_name ];

        $this->assertArrayHasKey( $expected_priority, $actions_hooked, "$method_name definitely not hooked to $action_name priority $expected_priority" );

        $hooked_method = null;
        foreach ( $actions_hooked[ $expected_priority ] as $action ) {
            $action_function = $action['function'];
            if ( is_array( $action_function ) ) {
                if ( $action_function[0] instanceof $class_type ) {
                    if( $method_name === $action_function[1] ) {
                        $hooked_method = $action_function[1];
                        break;
                    }
                }
            }
        }

        $this->assertNotNull( $hooked_method, "No methods on an instance of $class_type hooked to $action_name" );

        $this->assertEquals( $method_name, $hooked_method, "Unexpected method name for $class_type class hooked to $action_name" );

    }
}
