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

use BrianHenryIE\WC_Address_Validation\Admin\Plugin_Installer_Skin;
use BrianHenryIE\WC_Address_Validation\Psr\Container\ContainerInterface;
use BrianHenryIE\WC_Address_Validation\Admin\Plugins_Page;
use BrianHenryIE\WC_Address_Validation\WooCommerce\Email\Emails;
use BrianHenryIE\WC_Address_Validation\WooCommerce\Order;
use BrianHenryIE\WC_Address_Validation\WooCommerce\Order_Status;
use BrianHenryIE\WC_Address_Validation\WooCommerce\Shipping_Settings_Page;
use BrianHenryIE\WC_Address_Validation\WP_Includes\CLI;
use BrianHenryIE\WC_Address_Validation\WP_Includes\Cron;
use BrianHenryIE\WC_Address_Validation\WP_Includes\I18n;
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

	protected ContainerInterface $container;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the woocommerce-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct( ContainerInterface $container ) {

		$this->container = $container;

		$this->set_locale();

		$this->define_admin_hooks();
		$this->define_plugin_installer_hooks();
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

		$plugin_i18n = $this->container->get( I18n::class );

		add_action( 'init', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	protected function define_admin_hooks(): void {

		$plugins_page = $this->container->get( Plugins_Page::class );

		$settings        = $this->container->get( Settings_Interface::class );
		$plugin_basename = $settings->get_plugin_basename();

		add_filter( 'plugin_action_links_' . $plugin_basename, array( $plugins_page, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( $plugins_page, 'row_meta' ), 20, 4 );
	}

	protected function define_plugin_installer_hooks(): void {
		$plugin_installer_skin = $this->container->get( Plugin_Installer_Skin::class );
		add_filter( 'install_plugin_overwrite_comparison', array( $plugin_installer_skin, 'add_changelog_entry_to_upgrade_screen' ), 10, 3 );
	}

	/**
	 *
	 */
	protected function define_woocommerce_hooks(): void {

		$shipping_settings_page = $this->container->get( Shipping_Settings_Page::class );
		add_filter( 'woocommerce_get_sections_shipping', array( $shipping_settings_page, 'address_validation_section' ), 10, 1 );
		add_filter( 'woocommerce_get_settings_shipping', array( $shipping_settings_page, 'address_validation_settings' ), 10, 2 );

		/**
		 * The Order_Status class defines one new order status, wc-bad-address.
		 */
		$order_status = $this->container->get( Order_Status::class );
		add_action( 'woocommerce_init', array( $order_status, 'register_status' ) );
		add_filter( 'wc_order_statuses', array( $order_status, 'add_order_status_to_woocommerce' ) );
		add_filter( 'woocommerce_order_is_paid_statuses', array( $order_status, 'add_to_paid_status_list' ) );
		add_filter( 'woocommerce_reports_order_statuses', array( $order_status, 'add_to_reports_status_list' ) );

		$woocommerce_email = $this->container->get( Emails::class );
		add_filter( 'woocommerce_email_classes', array( $woocommerce_email, 'register_email' ), 10, 1 );
	}

	/**
	 *
	 */
	protected function define_woocommerce_order_hooks(): void {
		$woocommerce_order = $this->container->get( Order::class );

		add_action( 'woocommerce_order_status_changed', array( $woocommerce_order, 'check_address_on_single_order_processing' ), 10, 3 );
		add_action( 'admin_action_mark_processing', array( $woocommerce_order, 'check_address_on_bulk_order_processing' ) );

		add_filter( 'woocommerce_order_actions', array( $woocommerce_order, 'add_admin_ui_order_action' ) );
		add_action( 'woocommerce_order_action_bh_wc_address_validate', array( $woocommerce_order, 'check_address_on_admin_order_action' ) );

		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $woocommerce_order, 'print_link_to_usps_tools_zip_lookup' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $woocommerce_order, 'add_previous_address_to_order' ) );
	}

	/**
	 * Register the cron hook so it can run.
	 *
	 * @since    1.0.0
	 */
	protected function define_cron_hooks(): void {
		$cron = $this->container->get( Cron::class );

		add_action( Cron::CHECK_SINGLE_ADDRESS_CRON_JOB, array( $cron, 'check_address_for_single_order' ) );
		add_action( Cron::CHECK_MULTIPLE_ADDRESSES_CRON_JOB, array( $cron, 'check_address_for_multiple_orders' ) );
		add_action( Cron::RECHECK_BAD_ADDRESSES_CRON_JOB, array( $cron, 'recheck_bad_address_orders' ) );

		add_action( 'plugins_loaded', array( $cron, 'add_cron_jon' ) );
	}

	protected function define_cli_commands(): void {

		if ( class_exists( WP_CLI::class ) ) {
			CLI::$api = $this->container->get( API_Interface::class );
			// e.g. `vendor/bin/wp address_validation check_order 123`.
			WP_CLI::add_command( 'address_validation', CLI::class );
		}
	}
}
