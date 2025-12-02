<?php
/**
 * This will be executed when the plugin is uninstalled.
 *
 * @package OneDesign
 */

declare( strict_types=1 );

namespace OneDesign;

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Multisite loop for uninstalling from all sites.
 */
function multisite_uninstall(): void {
	if ( ! is_multisite() ) {
		uninstall();
		return;
	}

	delete_network_plugin_data();

	$site_ids = get_sites(
		[
			'fields' => 'ids',
			'number' => 0,
		]
	) ?: [];

	foreach ( $site_ids as $site_id ) {
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.switch_to_blog_switch_to_blog
		if ( ! switch_to_blog( (int) $site_id ) ) {
			continue;
		}

		uninstall();
		restore_current_blog();
	}
}

/**
 * The (site-specific) uninstall function.
 */
function uninstall(): void {
	delete_plugin_data();
}

/**
 * Delete multisite network plugin data.
 */
function delete_network_plugin_data(): void {
	$options = [
		'onedesign_multisite_governing_site',
	];

	foreach ( $options as $option ) {
		delete_site_option( $option );
	}
}

/**
 * Deletes meta, options, transients, etc.
 */
function delete_plugin_data(): void {
	// First delete posts from brand sites.
	$brand_site_post_ids = (array) get_option( 'onedesign_brand_site_post_ids', [] );
	foreach ( $brand_site_post_ids as $post_id ) {
		wp_delete_post( (int) $post_id, true );
	}

	$options = [
		'onedesign_site_type',
		'onedesign_consumer_api_key',
		'onedesign_parent_site_url',
		'onedesign_shared_sites',

		'onedesign_brand_site_patterns',
		'onedesign_child_site_public_key',
		'onedesign_shared_templates',
		'onedesign_brand_site_post_ids',
		'onedesign_shared_patterns',
		'onedesign_shared_template_parts',
		'onedesign_shared_synced_patterns',
		'onedesign_multisite_governing_site',
	];

	foreach ( $options as $option ) {
		delete_option( $option );
	}
}

// Run the uninstaller.
multisite_uninstall();
