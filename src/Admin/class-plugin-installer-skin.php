<?php

namespace BrianHenryIE\WC_Address_Validation\Admin;

class Plugin_Installer_Skin {

	/**
	 * When installing a plugin via zip, and the plugin already exists, append the changelog to the warning message.
	 *
	 * Filter the compare table output for overwriting a plugin package on upload.
	 *
	 * @hooked install_plugin_overwrite_comparison
	 * @see Plugin_Installer_Skin::do_overwrite()
	 *
	 * @param string $table               The output table with Name, Version, Author, RequiresWP, and RequiresPHP info.
	 * @param array  $current_plugin_data Array with current plugin data.
	 * @param array  $new_plugin_data     Array with uploaded plugin data.
	 */
	public function add_changelog_entry_to_upgrade_screen( string $table, array $current_plugin_data, array $new_plugin_data ) {

		if ( ! isset( $current_plugin_data['TextDomain'] ) || 'bh-wc-address-validation' !== $current_plugin_data['TextDomain'] ) {
			return $table;
		}

		$table .= '<p>1.4.0 – Add: changelog on plugin install screen; Fix: Accidentally included outdated library files.</p>';

		$table .= '<hr/>';

		return $table;
	}

}
