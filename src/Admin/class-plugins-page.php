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

namespace BrianHenryIE\WC_Address_Validation\Admin;

use BrianHenryIE\WC_Address_Validation\API\Settings_Interface;

/**
 * This class adds a `Settings` link on the plugins.php page.
 *
 * @package    BH_WC_Address_Validation
 * @subpackage BH_WC_Address_Validation/admin
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class Plugins_Page {

	protected Settings_Interface $settings;

	public function __construct( Settings_Interface $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Add link to settings page in plugins.php list.
	 *
	 * @param array<int|string, string> $links_array The existing plugin links (usually "Deactivate").
	 *
	 * @return array<int|string, string> The links to display below the plugin name on plugins.php.
	 */
	public function action_links( array $links_array ): array {

		$settings_url = admin_url( '/admin.php?page=wc-settings&tab=shipping&section=' . $this->settings->get_plugin_slug() );

		array_unshift( $links_array, '<a href="' . $settings_url . '">Settings</a>' );

		return $links_array;
	}

	/**
	 * Add a link to GitHub repo on the plugins list.
	 *
	 * @see https://rudrastyh.com/wordpress/plugin_action_links-plugin_row_meta.html
	 *
	 * @param array<int|string, string>  $plugin_meta The meta information/links displayed by the plugin description.
	 * @param string                     $plugin_file_name The plugin filename to match when filtering.
	 * @param array<string, string|bool> $_plugin_data Associative array including PluginURI, slug, Author, Version.
	 * @param string                     $_status The plugin status, e.g. 'Inactive'.
	 *
	 * @return array<int|string, string> The filtered $plugin_meta.
	 */
	public function row_meta( array $plugin_meta, string $plugin_file_name, array $_plugin_data, string $_status ): array {

		if ( $this->settings->get_plugin_basename() === $plugin_file_name ) {

			$plugin_meta[] = '<a target="_blank" href="https://github.com/BrianHenryIE/' . $this->settings->get_plugin_slug() . '">View plugin on GitHub</a>';
		}

		return $plugin_meta;
	}

}
