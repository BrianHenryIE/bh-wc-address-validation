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

namespace BH_WC_Address_Validation\includes;

use BH_WC_Address_Validation\api\Settings_Interface;
use PharIo\Manifest\Email;
use stdClass;
use WC_Logger;
use BH_WC_Address_Validation\admin\Plugins_Page;
use BH_WC_Address_Validation\api\API;
use BH_WC_Address_Validation\api\CLI;
use BH_WC_Address_Validation\api\Settings;
use BH_WC_Address_Validation\woocommerce\email\Emails;
use BH_WC_Address_Validation\woocommerce\Order;
use BH_WC_Address_Validation\woocommerce\Order_Status;
use BH_WC_Address_Validation\woocommerce\Shipping_Settings_Page;
use BH_WC_Address_Validation\BrianHenryIE\WPPB\WPPB_Loader_Interface;
use BH_WC_Address_Validation\BrianHenryIE\WPPB\WPPB_Plugin_Abstract;
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
class BH_WC_Address_Validation extends WPPB_Plugin_Abstract {

	/**
	 * @var WC_Logger
	 */
	private static $logger;

	/**
	 * @var bool
	 */
	private static $is_logging_enabled;

	/**
	 * @var Cron
	 */
	public $cron;

	/**
	 * @var Email
	 */
	protected $woocommerce_email;

	/**
	 * Logging method.
	 *
	 * When in CLI, everything is logged but without the level.
	 *
	 * @param string|stdClass|array $message Log message.
	 * @param string                $level   Log level.
	 *                                       Available options: 'emergency', 'alert',
	 *                                       'critical', 'error', 'warning', 'notice',
	 *                                       'info' and 'debug'.
	 *                                       Defaults to 'info'.
	 */
	public static function log( $message, $level = 'info' ) {

		if ( ! is_string( $message ) ) {
			$message = json_encode( $message );
		}

		$message = strip_tags( $message );

		if ( class_exists( WP_CLI::class ) ) {
			WP_CLI::line( $message );
		}

		if ( ! isset( self::$is_logging_enabled ) || false === self::$is_logging_enabled ) {
			return;
		}

		// If the logger is used before WooCommerce is loaded.
		if ( is_null( self::$logger ) && function_exists( 'wc_get_logger' ) ) {
			self::$logger = wc_get_logger();
		} elseif ( ! function_exists( 'wc_get_logger' ) ) {
			error_log( $message );
			return;
		}

		self::$logger->log( $level, $message, array( 'source' => 'bh-wc-address-validation' ) );
	}

	/**
	 * Allow access for testing and unhooking.
	 *
	 * @var I18n The plugin I18n object instance.
	 */
	public $i18n;

	/**
	 * @var Plugins_Page
	 */
	public $plugins_page;

	/**
	 * @var Shipping_Settings_Page
	 */
	public $shipping_settings_page;

	/**
	 * @var Settings
	 */
	public $settings;

	/**
	 * @var API
	 */
	public $api;

	/**
	 * @var Order_Status
	 */
	public $order_status;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the woocommerce-facing side of the site.
	 *
	 * @since    1.0.0
	 *
	 * @param Settings_Interface    $settings
	 * @param WPPB_Loader_Interface $loader The WPPB class which adds the hooks and filters to WordPress.
	 */
	public function __construct( $settings, $loader ) {
		if ( defined( 'BH_WC_ADDRESS_VALIDATION_VERSION' ) ) {
			$version = BH_WC_ADDRESS_VALIDATION_VERSION;
		} else {
			$version = '1.0.0';
		}
		$plugin_name = 'bh-wc-address-validation';

		parent::__construct( $loader, $plugin_name, $version );

		$this->loader = $loader;

		$this->settings = $settings;

		$this->api = new API( $this->settings );

		self::$is_logging_enabled = $this->settings->is_logging_enabled();

		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_woocommerce_hooks();
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
	 * @access   private
	 */
	private function set_locale() {

		$this->i18n = $plugin_i18n = new I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$this->plugins_page = new Plugins_Page( $this->get_plugin_name(), $this->get_version() );
		$plugin_basename    = $this->get_plugin_name() . '/' . $this->get_plugin_name() . '.php';
		$this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $this->plugins_page, 'action_links' );
		$this->loader->add_filter( 'plugin_row_meta', $this->plugins_page, 'row_meta', 20, 4 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_woocommerce_hooks() {

		$woocommerce_order = new Order( $this->api, $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'woocommerce_order_status_changed', $woocommerce_order, 'check_address_on_single_order_processing', 10, 3 );
		$this->loader->add_action( 'admin_action_mark_processing', $woocommerce_order, 'check_address_on_bulk_order_processing' );

		$this->loader->add_filter( 'woocommerce_order_actions', $woocommerce_order, 'add_admin_ui_order_action' );
		$this->loader->add_action( 'woocommerce_order_action_bh_wc_address_validate', $woocommerce_order, 'check_address_on_admin_order_action' );

		$this->loader->add_filter( 'woocommerce_admin_order_data_after_shipping_address', $woocommerce_order, 'add_link_to_usps_tools_zip_lookup' );

		$this->shipping_settings_page = new Shipping_Settings_Page( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_filter( 'woocommerce_get_sections_shipping', $this->shipping_settings_page, 'address_validation_section', 10, 1 );
		$this->loader->add_filter( 'woocommerce_get_settings_shipping', $this->shipping_settings_page, 'address_validation_settings', 10, 2 );

		/**
		 * The Order_Status class defines one new order status, wc-bad-address.
		 */
		$this->order_status = new Order_Status( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'woocommerce_init', $this->order_status, 'register_status' );
		$this->loader->add_filter( 'wc_order_statuses', $this->order_status, 'add_order_status_to_woocommerce' );
		$this->loader->add_filter( 'woocommerce_order_is_paid_statuses', $this->order_status, 'add_to_paid_status_list' );

		$this->woocommerce_email = new Emails( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_filter( 'woocommerce_email_classes', $this->woocommerce_email, 'register_email', 10, 1 );

	}

	/**
	 * Register the cron hook so it can run.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_cron_hooks() {

		$this->cron = new Cron( $this->api, $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( CRON::CHECK_SINGLE_ADDRESS_CRON_JOB, $this->cron, 'check_address_for_single_order' );
		$this->loader->add_action( CRON::CHECK_MULTIPLE_ADDRESSES_CRON_JOB, $this->cron, 'check_address_for_multiple_orders' );

	}

	private function define_cli_commands() {

		if ( class_exists( WP_CLI::class ) ) {
			CLI::$api = $this->api;
			// vendor/bin/wp validate_address check_order 123 --path=vendor/wordpress/wordpress/build
			WP_CLI::add_command( 'validate_address', CLI::class );
		}
	}

}
