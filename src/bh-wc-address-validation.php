<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           BH_WC_Address_Validation
 *
 * @wordpress-plugin
 * Plugin Name:       Address Validation
 * Plugin URI:        http://github.com/BrianHenryIE/bh-wc-address-validation/
 * Description:       Uses USPS API to verify and correct shipping addresses.
 * Version:           1.1.0
 * Author:            Brian Henry
 * Author URI:        https://BrianHenry.ie
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bh-wc-address-validation
 * Domain Path:       /languages
 */

namespace {

	use BH_WC_Address_Validation\includes\Activator;
	use BH_WC_Address_Validation\includes\Deactivator;

	// If this file is called directly, abort.
	if ( ! defined( 'WPINC' ) ) {
		die;
	}

	require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

	/**
	 * Currently plugin version.
	 * Start at version 1.0.0 and use SemVer - https://semver.org
	 * Rename this for your plugin and update it as you release new versions.
	 */
	define( 'BH_WC_ADDRESS_VALIDATION_VERSION', '1.1.0' );

	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/class-activator.php
	 */
	function activate_bh_wc_address_validation() {

		Activator::activate();
	}

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-deactivator.php
	 */
	function deactivate_bh_wc_address_validation() {

		Deactivator::deactivate();
	}

	register_activation_hook( __FILE__, 'activate_bh_wc_address_validation' );
	register_deactivation_hook( __FILE__, 'deactivate_bh_wc_address_validation' );

}

namespace BH_WC_Address_Validation {

	use BH_WC_Address_Validation\api\API;
	use BH_WC_Address_Validation\api\Settings;
	use BH_WC_Address_Validation\BrianHenryIE\WP_Logger\Logger;
	use BH_WC_Address_Validation\includes\BH_WC_Address_Validation;
	use BH_WC_Address_Validation\BrianHenryIE\WPPB\WPPB_Loader;

	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    1.0.0
	 */
	function instantiate_bh_wc_address_validation() {

		$settings = new Settings();
		$logger   = Logger::instance( $settings );
		$api      = new API( $settings, $logger );

		$loader = new WPPB_Loader();

		/**
		 * The core plugin class that is used to define internationalization,
		 * admin-specific hooks, and frontend-facing site hooks.
		 */
		$plugin = new BH_WC_Address_Validation( $loader, $api, $settings, $logger );
		$plugin->run();

		return $api;
	}

	/**
	 * @var BH_WC_Address_Validation\api\API
	 */
	$GLOBALS['bh_wc_address_validation'] = instantiate_bh_wc_address_validation();

}
