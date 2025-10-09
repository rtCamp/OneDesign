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
	function onedesign_plugin_deletion(): void {

		// get brand site post ids & delete posts.
		$brand_site_post_ids = get_option( 'onedesign_brand_site_post_ids', array() );
		if ( is_array( $brand_site_post_ids ) && ! empty( $brand_site_post_ids ) ) {
			foreach ( $brand_site_post_ids as $post_id ) {

				// delete post meta associated with the post.
				$meta_keys = get_post_meta( $post_id );
				if ( is_array( $meta_keys ) && ! empty( $meta_keys ) ) {
					foreach ( $meta_keys as $meta_key => $meta_value ) {
						delete_post_meta( $post_id, $meta_key );
					}
				}

				wp_delete_post( $post_id, true );
			}
		}

		$options_to_delete = array(
			'onedesign_site_type',
			'consumer_site_patterns',
			'onedesign_child_site_public_key',
			'onedesign_child_sites',
			'onedesign_child_site_api_key',
			'onedesign_shared_sites',
			'onedesign_site_type_transient',
			'onedesign_governing_site_url',
			'onedesign_shared_templates',
			'onedesign_brand_site_post_ids',
			'onedesign_shared_patterns',
			'onedesign_shared_template_parts',
			'onedesign_shared_synced_patterns',
		);

		foreach ( $options_to_delete as $option ) {
			delete_option( $option );
		}
	}
}
/**
 * Uninstall the plugin and clean up options.
 */
onedesign_plugin_deletion();
