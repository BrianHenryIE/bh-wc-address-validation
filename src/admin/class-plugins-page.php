<?php
/**
 * The plugin page output of the plugin.
 *
 * @link
 * @since      2.0.0
 *
 * @package    BH_WC_Address_Validation
 * @subpackage BH_WC_Address_Validation/admin
 */


namespace BH_WC_Address_Validation\admin;

use BH_WC_Address_Validation\BrianHenryIE\WPPB\WPPB_Object;

/**
 * This class adds a `Settings` link on the plugins.php page.
 *
 * @package    BH_WC_Address_Validation
 * @subpackage BH_WC_Address_Validation/admin
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class Plugins_Page extends WPPB_Object {

	/**
	 * Add link to settings page in plugins.php list.
	 *
	 * @param array $links_array The existing plugin links (usually "Deactivate").
	 *
	 * @return array The links to display below the plugin name on plugins.php.
	 */
	public function action_links( $links_array ) {

		$settings_url = admin_url( '/admin.php?page=wc-settings&tab=shipping&section=' . $this->get_plugin_name() );

		array_unshift( $links_array, '<a href="' . $settings_url . '">Settings</a>' );

		return $links_array;
	}

	/**
	 * Add a link to GitHub repo on the plugins list.
	 *
	 * @see https://rudrastyh.com/wordpress/plugin_action_links-plugin_row_meta.html
	 *
	 * @param string[] $plugin_meta The meta information/links displayed by the plugin description.
	 * @param string   $plugin_file_name The plugin filename to match when filtering.
	 * @param array    $plugin_data Associative array including PluginURI, slug, Author, Version.
	 * @param string   $status The plugin status, e.g. 'Inactive'.
	 *
	 * @return array The filtered $plugin_meta.
	 */
	public function row_meta( $plugin_meta, $plugin_file_name, $plugin_data, $status ) {

		if ( $this->plugin_name . '/' . $this->plugin_name . '.php' === $plugin_file_name ) {

			$plugin_meta[] = '<a target="_blank" href="https://github.com/BrianHenryIE/' . $this->get_plugin_name() . '">View plugin on GitHub</a>';
		}

		return $plugin_meta;
	}

}
