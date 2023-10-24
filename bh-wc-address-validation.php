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
 * Version:           1.4.1
 * Author:            BrianHenryIE
 * Author URI:        https://BrianHenry.ie
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bh-wc-address-validation
 * Domain Path:       /languages
 */


namespace BrianHenryIE\WC_Address_Validation;

use BrianHenryIE\WC_Address_Validation\API\Address_Validator_Interface;
use BrianHenryIE\WC_Address_Validation\API\API;
use BrianHenryIE\WC_Address_Validation\API\Settings;
use BrianHenryIE\WC_Address_Validation\API\Validators\EasyPost_Address_Validator;
use BrianHenryIE\WC_Address_Validation\API\Validators\Null_Validator;
use BrianHenryIE\WC_Address_Validation\API\Validators\USPS_Address_Validator;
use BrianHenryIE\WC_Address_Validation\lucatume\DI52\Container;
use BrianHenryIE\WC_Address_Validation\Psr\Container\ContainerInterface;
use BrianHenryIE\WC_Address_Validation\WP_Includes\Activator;
use BrianHenryIE\WC_Address_Validation\WP_Includes\Deactivator;
use BrianHenryIE\WC_Address_Validation\WP_Logger\Logger;
use BrianHenryIE\WC_Address_Validation\WP_Logger\Logger_Settings_Interface;
use Psr\Log\LoggerInterface;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	throw new \Exception( 'WordPress not loaded.' );
}

require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'BH_WC_ADDRESS_VALIDATION_VERSION', '1.4.1' );

/**
 * The code that runs during plugin activation, deactivation.
 */
register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Deactivator::class, 'deactivate' ) );


// Create container.
$container = new Container();

$container->singleton(
	ContainerInterface::class,
	static function () use( $container ) {
		return $container;
	}
);

// Define ambiguous (interface) binds.
$container->bind( API_Interface::class, API::class );
$container->bind( Settings_Interface::class, Settings::class );
$container->bind( Logger_Settings_Interface::class, Settings::class );
// Define more complex bind.
$container->singleton(
	LoggerInterface::class,
	static function ( Container $container ) {
		return Logger::instance( $container->get( Logger_Settings_Interface::class ) );
	}
);

$settings = $container->get( Settings_Interface::class );

// Address_Validator_Interface
if ( ! empty( $settings->get_easypost_api_key() ) ) {
	$container->bind( Address_Validator_Interface::class, EasyPost_Address_Validator::class );
} elseif ( ! empty( $settings->get_usps_username() ) ) {
	$container->bind( Address_Validator_Interface::class, USPS_Address_Validator::class );
} else {
	$container->bind( Address_Validator_Interface::class, Null_Validator::class );
}


// Instantiate the main plugin class which itself uses the container to instantiate (::get())
// classes and adds the actions and filters.
$app = $container->get( BH_WC_Address_Validation::class );

$GLOBALS['bh_wc_address_validation'] = $container->get( API_Interface::class );

add_action(
	'init',
	function () use ( $container ) {
		$upgrader = $container->get( Upgrader::class );
		$upgrader->do_upgrade();
	}
);
