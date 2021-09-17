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
 * Version:           1.2.2
 * Author:            Brian Henry
 * Author URI:        https://BrianHenry.ie
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bh-wc-address-validation
 * Domain Path:       /languages
 */

namespace BrianHenryIE\WC_Address_Validation;

use BrianHenryIE\WC_Address_Validation\API\API;
use BrianHenryIE\WC_Address_Validation\API\API_Interface;
use BrianHenryIE\WC_Address_Validation\API\Settings;
use BrianHenryIE\WC_Address_Validation\Includes\Activator;
use BrianHenryIE\WC_Address_Validation\Includes\Deactivator;
use BrianHenryIE\WC_Address_Validation\WP_Logger\Logger;
use BrianHenryIE\WC_Address_Validation\Includes\BH_WC_Address_Validation;

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
define( 'BH_WC_ADDRESS_VALIDATION_VERSION', '1.2.2' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-activator.php
 */
register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-deactivator.php
 */
register_deactivation_hook( __FILE__, array( Deactivator::class, 'deactivate' ) );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function instantiate_bh_wc_address_validation(): API_Interface {

	$settings  = new Settings();
	$logger    = Logger::instance( $settings );
	$container = new Container( $settings, $logger );
	$api       = new API( $container, $settings, $logger );

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and frontend-facing site hooks.
	 */
	new BH_WC_Address_Validation( $api, $settings, $logger );

	return $api;
}

/**
 * @var \BrianHenryIE\WC_Address_Validation\API\API_Interface $GLOBALS['bh_wc_address_validation']
 */
$GLOBALS['bh_wc_address_validation'] = instantiate_bh_wc_address_validation();


