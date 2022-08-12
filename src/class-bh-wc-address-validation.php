<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * woocommerce-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BH_WC_Address_Validation
 * @subpackage BH_WC_Address_Validation/includes
 */

namespace BrianHenryIE\WC_Address_Validation;

use BrianHenryIE\WC_Address_Validation\API_Interface;
use BrianHenryIE\WC_Address_Validation\Settings_Interface;
use BrianHenryIE\WC_Address_Validation\Admin\Plugins_Page;
use BrianHenryIE\WC_Address_Validation\WooCommerce\Email\Emails;
use BrianHenryIE\WC_Address_Validation\WooCommerce\Order;
use BrianHenryIE\WC_Address_Validation\WooCommerce\Order_Status;
use BrianHenryIE\WC_Address_Validation\WooCommerce\Shipping_Settings_Page;
use BrianHenryIE\WC_Address_Validation\WP_Includes\CLI;
use BrianHenryIE\WC_Address_Validation\WP_Includes\Cron;
use BrianHenryIE\WC_Address_Validation\WP_Includes\I18n;
use Psr\Log\LoggerInterface;
use WP_CLI;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * woocommerce-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    BH_WC_Address_Validation
 * @subpackage BH_WC_Address_Validation/includes
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class BH_WC_Address_Validation {

	/**
	 * @var LoggerInterface
	 */
	protected LoggerInterface $logger;

	/**
	 * @var Settings_Interface
	 */
	protected Settings_Interface $settings;

	/**
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the woocommerce-facing side of the site.
	 *
	 * @since    1.0.0
	 *
	 * @param API_Interface      $api
	 * @param Settings_Interface $settings
	 * @param LoggerInterface    $logger
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger ) {

		$this->logger   = $logger;
		$this->settings = $settings;
		$this->api      = $api;

		$this->set_locale();

		$this->define_admin_hooks();
		$this->define_woocommerce_hooks();
		$this->define_woocommerce_order_hooks();
		$this->define_cron_hooks();
		$this->define_cli_commands();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	protected function set_locale(): void {

		$plugin_i18n = new I18n();

		add_action( 'init', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	protected function define_admin_hooks(): void {

		$plugins_page    = new Plugins_Page( $this->settings );
		$plugin_basename = $this->settings->get_plugin_basename();
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $plugins_page, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( $plugins_page, 'row_meta' ), 20, 4 );
	}

	/**
	 *
	 */
	protected function define_woocommerce_hooks(): void {

		$shipping_settings_page = new Shipping_Settings_Page();
		add_filter( 'woocommerce_get_sections_shipping', array( $shipping_settings_page, 'address_validation_section' ), 10, 1 );
		add_filter( 'woocommerce_get_settings_shipping', array( $shipping_settings_page, 'address_validation_settings' ), 10, 2 );

		/**
		 * The Order_Status class defines one new order status, wc-bad-address.
		 */
		$order_status = new Order_Status( $this->api, $this->settings, $this->logger );
		add_action( 'woocommerce_init', array( $order_status, 'register_status' ) );
		add_filter( 'wc_order_statuses', array( $order_status, 'add_order_status_to_woocommerce' ) );
		add_filter( 'woocommerce_order_is_paid_statuses', array( $order_status, 'add_to_paid_status_list' ) );
		add_filter( 'woocommerce_reports_order_statuses', array( $order_status, 'add_to_reports_status_list' ) );

		$woocommerce_email = new Emails();
		add_filter( 'woocommerce_email_classes', array( $woocommerce_email, 'register_email' ), 10, 1 );

	}

	/**
	 *
	 */
	protected function define_woocommerce_order_hooks(): void {
		$woocommerce_order = new Order( $this->api, $this->settings, $this->logger );

		add_action( 'woocommerce_order_status_changed', array( $woocommerce_order, 'check_address_on_single_order_processing' ), 10, 3 );
		add_action( 'admin_action_mark_processing', array( $woocommerce_order, 'check_address_on_bulk_order_processing' ) );

		add_filter( 'woocommerce_order_actions', array( $woocommerce_order, 'add_admin_ui_order_action' ) );
		add_action( 'woocommerce_order_action_bh_wc_address_validate', array( $woocommerce_order, 'check_address_on_admin_order_action' ) );

		add_filter( 'woocommerce_admin_order_data_after_shipping_address', array( $woocommerce_order, 'print_link_to_usps_tools_zip_lookup' ) );
	}

	/**
	 * Register the cron hook so it can run.
	 *
	 * @since    1.0.0
	 */
	protected function define_cron_hooks(): void {

		$cron = new Cron( $this->api, $this->settings, $this->logger );

		add_action( Cron::CHECK_SINGLE_ADDRESS_CRON_JOB, array( $cron, 'check_address_for_single_order' ) );
		add_action( Cron::CHECK_MULTIPLE_ADDRESSES_CRON_JOB, array( $cron, 'check_address_for_multiple_orders' ) );

		add_action( 'plugins_loaded', array( $cron, 'add_cron_jon' ) );

	}

	protected function define_cli_commands(): void {

		if ( class_exists( WP_CLI::class ) ) {
			CLI::$api = $this->api;
			// e.g. `vendor/bin/wp address_validation check_order 123`.
			WP_CLI::add_command( 'address_validation', CLI::class );
		}
	}

}
