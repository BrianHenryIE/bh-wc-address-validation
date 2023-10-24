<?php

namespace BrianHenryIE\WC_Address_Validation;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Address_Validation\Admin\Plugins_Page;
use BrianHenryIE\WC_Address_Validation\API\API;
use BrianHenryIE\WC_Address_Validation\API\Settings;
use BrianHenryIE\WC_Address_Validation\lucatume\DI52\Container;
use BrianHenryIE\WC_Address_Validation\Psr\Container\ContainerInterface;
use BrianHenryIE\WC_Address_Validation\WooCommerce\Order;
use BrianHenryIE\WC_Address_Validation\WooCommerce\Order_Status;
use BrianHenryIE\WC_Address_Validation\WP_Includes\I18n;
use BrianHenryIE\WC_Address_Validation\WP_Logger\Logger;
use BrianHenryIE\WC_Address_Validation\WP_Logger\Logger_Settings_Interface;
use Psr\Log\LoggerInterface;
use WP_Mock\Matcher\AnyInstance;

/**
 * Class BH_WC_Address_Validation_Unit_Test
 *
 * @coversDefaultClass \BrianHenryIE\WC_Address_Validation\BH_WC_Address_Validation
 */
class Plugin_Unit_Test extends \Codeception\Test\Unit {

	protected function _before() {
		// parent::setUp();
		\WP_Mock::setUp();
	}

	protected function _after() {
		\WP_Mock::tearDown();
		// parent::tearDown();
	}

	protected function getContainer(): ContainerInterface {

		$container = new Container();

		$container->bind(
			API_Interface::class,
			$this->makeEmpty( API_Interface::class )
		);
		$container->bind(
			Settings_Interface::class,
			$this->makeEmpty(
				Settings_Interface::class,
				array( 'get_plugin_basename' => 'bh-wc-address-validation/bh-wc-address-validation.php' )
			)
		);
		$container->singleton(
			LoggerInterface::class,
			static function () {
				return new ColorLogger();
			}
		);

		return $container;
	}

	/**
	 * @covers ::set_locale
	 */
	public function test_set_locale_hooked() {

		\WP_Mock::expectActionAdded(
			'init',
			array( new AnyInstance( I18n::class ), 'load_plugin_textdomain' )
		);

		new BH_WC_Address_Validation( $this->getContainer() );
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

		new BH_WC_Address_Validation( $this->getContainer() );
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

		new BH_WC_Address_Validation( $this->getContainer() );
	}



	/**
	 * @covers ::define_woocommerce_order_hooks
	 */
	public function test_order_hooks() {

		\WP_Mock::expectActionAdded(
			'woocommerce_order_status_changed',
			array( new AnyInstance( Order::class ), 'check_address_on_single_order_processing' ),
			10,
			3
		);

		\WP_Mock::expectActionAdded(
			'admin_action_mark_processing',
			array( new AnyInstance( Order::class ), 'check_address_on_bulk_order_processing' )
		);

		\WP_Mock::expectFilterAdded(
			'woocommerce_order_actions',
			array( new AnyInstance( Order::class ), 'add_admin_ui_order_action' )
		);

		new BH_WC_Address_Validation( $this->getContainer() );
	}
}
