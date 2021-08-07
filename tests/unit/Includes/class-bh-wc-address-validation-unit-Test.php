<?php

namespace BrianHenryIE\WC_Address_Validation\Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Address_Validation\Admin\Plugins_Page;
use BrianHenryIE\WC_Address_Validation\API\API_Interface;
use BrianHenryIE\WC_Address_Validation\API\Settings_Interface;
use BrianHenryIE\WC_Address_Validation\WooCommerce\Order_Status;
use WP_Mock\Matcher\AnyInstance;

/**
 * Class BH_WC_Address_Validation_Unit_Test
 * @coversDefaultClass \BrianHenryIE\WC_Address_Validation\Includes\BH_WC_Address_Validation
 */
class Plugin_Unit_Test extends \Codeception\Test\Unit {

    protected function _before() {
//        parent::setUp();
        \WP_Mock::setUp();
    }

    protected function _after() {
        \WP_Mock::tearDown();
//        parent::tearDown();
    }

    /**
     * @covers ::set_locale
     */
    public function test_set_locale_hooked() {

        \WP_Mock::expectActionAdded(
            'init',
            array( new AnyInstance( I18n::class ), 'load_plugin_textdomain' )
        );

        $api = $this->makeEmpty( API_Interface::class );
        $settings = $this->makeEmpty( Settings_Interface::class );
        $logger = new ColorLogger();
        new BH_WC_Address_Validation( $api, $settings, $logger );
    }

    /**
     * @covers ::define_admin_hooks
     */
    public function test_admin_hooks() {

        \WP_Mock::expectFilterAdded(
            'plugin_action_links_bh-wc-address-validation/bh-wc-address-validation.php',
            array( new AnyInstance( Plugins_Page::class ), 'action_links' )
        );

        \WP_Mock::expectFilterAdded(
            'plugin_row_meta',
            array( new AnyInstance( Plugins_Page::class ), 'row_meta' ),
            20,
            4
        );

        $api = $this->makeEmpty( API_Interface::class );
        $settings = $this->makeEmpty( Settings_Interface::class, array( 'get_plugin_basename'=>'bh-wc-address-validation/bh-wc-address-validation.php'));
        $logger = new ColorLogger();
        new BH_WC_Address_Validation( $api, $settings, $logger );
    }

    /**
     * @covers ::define_woocommerce_hooks
     */
    public function test_woocommerce_hooks() {

        \WP_Mock::expectActionAdded(
            'woocommerce_init',
            array( new AnyInstance( Order_Status::class ), 'register_status' )
        );

        \WP_Mock::expectFilterAdded(
            'wc_order_statuses',
            array( new AnyInstance( Order_Status::class ), 'add_order_status_to_woocommerce' )
        );

        $api = $this->makeEmpty( API_Interface::class );
        $settings = $this->makeEmpty( Settings_Interface::class );
        $logger = new ColorLogger();
        new BH_WC_Address_Validation( $api, $settings, $logger );
    }

}
