<?php
/**
 * This will be executed when the plugin is uninstalled.
 *
 * @package OneDesign
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'onedesign_plugin_deletion' ) ) {

	/**
	 * Function to clean up options when the plugin is uninstalled.
	 */
	function onedesign_plugin_deletion() {
		delete_option( 'onedesign_site_type' );
		delete_option( 'consumer_site_patterns' );
		delete_option( 'onedesign_child_site_public_key' );
		delete_option( 'onedesign_child_sites' );
	}
}
/**
 * Uninstall the plugin and clean up options.
 */
onedesign_plugin_deletion();
