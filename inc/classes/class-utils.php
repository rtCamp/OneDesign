<?php
/**
 * Common utility functions.
 *
 * @package OneDesign
 */

namespace OneDesign;

use OneDesign\Traits\Singleton;
use OneDesign\Plugin_Configs\Constants;

/**
 * Class Utils
 */
class Utils {

	/**
	 * Use Singleton trait.
	 */
	use Singleton;

	/**
	 * Protected class constructor
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Function to setup hooks.
	 *
	 * @return void
	 */
	public function setup_hooks(): void {
		// Add any hooks if needed in the future.
	}

	/**
	 * Get the current site type.
	 *
	 * @return string
	 */
	public static function get_current_site_type(): string {
		$onedesign_site_type = get_option( Constants::ONEDESIGN_SITE_TYPE, '' );
		return $onedesign_site_type;
	}

	/**
	 * Check if the current site is a brand site.
	 *
	 * @return bool
	 */
	public static function is_brand_site(): bool {
		// using consumer as it's legacy naming convention used in initial releases.
		return hash_equals( 'consumer', self::get_current_site_type() );
	}

	/**
	 * Check if the current site is a governing site.
	 *
	 * @return bool
	 */
	public static function is_governing_site(): bool {
		// using dashboard as it's legacy naming convention used in initial releases.
		return hash_equals( 'dashboard', self::get_current_site_type() );
	}

	/**
	 * Get site info by site ID.
	 *
	 * @param string $site_id Site ID.
	 *
	 * @return array|null Site info array or null if not found.
	 */
	public static function get_site_by_id( string $site_id ): array|null {
		$sites    = self::get_sites_info();
		$filtered = array_filter(
			$sites,
			function ( $site ) use ( $site_id ) {
				return $site['id'] === $site_id;
			}
		);

		return ! empty( $filtered ) ? array_values( $filtered )[0] : null;
	}

	/**
	 * Get all sites info.
	 *
	 * @return array Array of sites info.
	 */
	public static function get_sites_info(): array {
		$sites_info = get_option( Constants::ONEDESIGN_CHILD_SITES, array() );
		return is_array( $sites_info ) ? $sites_info : array();
	}

	/**
	 * Get current site name in lowercase.
	 *
	 * @return string Current site name in lowercase.
	 */
	public static function get_current_site_name(): string {
		$site_name = get_bloginfo( 'name' );
		// convert to lowercase letters.
		return sanitize_title( strtolower( $site_name ) );
	}

	/**
	 * Generate a unique slug for template patterns and template parts.
	 *
	 * @param string $base_slug The base slug (e.g., 'header', 'footer').
	 * @param string $sharing_site_name The name of the site to which template is going to be shared.
	 * @param bool   $is_slug Whether this is for slug (true) or id (false).
	 *
	 * @return string Unique slug combining current site name, sharing site name, and base slug.
	 */
	public static function generate_unique_slug_for_template_patterns_template_parts( $base_slug, $sharing_site_name, bool $is_slug = true ) {
		// Sanitize the base slug to ensure it's URL-friendly.
		$sanitized_slug = sanitize_title( $base_slug );

		// Convert sharing site name to lowercase and sanitize it.
		$sanitized_site_name = sanitize_title( strtolower( $sharing_site_name ) );

		// Combine the sanitized base slug with the sanitized site name.
		$unique_slug = '';
		if ( $is_slug ) {
			$unique_slug = self::get_current_site_name() . '-' . $sanitized_site_name . '-' . $sanitized_slug;
		} else {
			$unique_slug = self::get_current_site_name() . '-' . $sanitized_site_name . '//' . $sanitized_slug;
		}

		return $unique_slug;
	}

	/**
	 * Modify template part and pattern references within block content.
	 *
	 * @param string $content The block content containing WordPress block markup.
	 * @param string $shared_site_name The name of the site to which template is going to be shared.
	 *
	 * @return string Modified content with updated slugs and themes.
	 */
	public static function modify_content_references( $content, $shared_site_name ) {

		$content_string = '';

		if ( is_string( $content ) ) {
			$content_string = $content;
		} elseif ( is_object( $content ) ) {
			// Handle WP_Block_Template object.
			if ( isset( $content->content ) ) {
				$content_string = $content->content;
			} elseif ( isset( $content->post_content ) ) {
				// Handle WP_Post object (for patterns/blocks).
				$content_string = $content->post_content;
			} else {
				// Return empty string if we can't find content.
				return '';
			}
		} elseif ( is_array( $content ) ) {
			// Handle array format.
			if ( isset( $content['content'] ) ) {
				$content_string = $content['content'];
			} else {
				return '';
			}
		} else {
			// Unsupported content type.
			return '';
		}

		// Pattern to match template-part and pattern blocks.
		$pattern = '/<!--\s*wp:(template-part|pattern)\s*(\{[^}]*\})\s*\/?-->/';

		return preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $shared_site_name ) {
				$block_type      = $matches[1];
				$attributes_json = $matches[2];

				// Decode the attributes.
				$attributes = json_decode( $attributes_json, true );

				if ( ! $attributes ) {
					return $matches[0]; // Return original if JSON decode fails.
				}

				// Modify slug if present.
				if ( isset( $attributes['slug'] ) ) {
					$attributes['slug'] = self::generate_unique_slug_for_template_patterns_template_parts(
						$attributes['slug'],
						$shared_site_name
					);
				}

				// Encode back to JSON.
				$new_attributes_json = wp_json_encode( $attributes, JSON_UNESCAPED_SLASHES );

				// Return the modified block.
				return "<!-- wp:{$block_type} {$new_attributes_json} /-->";
			},
			$content_string
		);
	}

	/**
	 * Modify the slug and id of templates, template parts, and patterns to ensure uniqueness across shared sites.
	 * Also modifies references within the content.
	 *
	 * @param array  $templates Array of template objects.
	 * @param string $shared_site_name The name of the site to which template is going to be shared.
	 *
	 * @return array The modified template array with unique slugs, ids, and updated content references.
	 */
	public static function modify_template_template_part_pattern_slug( $templates, $shared_site_name ) {
		foreach ( $templates as $index => $template ) {

			// set original slug field to keep track of original slugs.
			if ( isset( $template['slug'] ) && ! isset( $template['original_slug'] ) ) {
				$templates[ $index ]['original_slug'] = $template['slug'];
			}

			// set original id field to keep track of original ids.
			if ( isset( $template['id'] ) && ! isset( $template['original_id'] ) ) {
				$templates[ $index ]['original_id'] = $template['id'];
			}

			// Modify top-level slug and id.
			if ( isset( $template['slug'] ) ) {
				$templates[ $index ]['slug'] = self::generate_unique_slug_for_template_patterns_template_parts(
					$template['slug'],
					$shared_site_name
				);
			}

			if ( isset( $template['id'] ) ) {
				$templates[ $index ]['id'] = self::generate_unique_slug_for_template_patterns_template_parts(
					$template['id'],
					$shared_site_name,
					false
				);
			}

			// Modify content references.
			if ( isset( $template['content'] ) ) {
				$templates[ $index ]['content'] = self::modify_content_references(
					$template['content'],
					$shared_site_name
				);
			}
		}

		return $templates;
	}

	/**
	 * Replace wp:block ref IDs - handles multiple WordPress block comment formats.
	 *
	 * @param array  $items        Array of items to process. Passed by reference.
	 * @param array  $id_map       Map of old_id => new_id.
	 * @param string $content_key  Key for the content field (default: 'content').
	 * @return array Modified items with updated block refs.
	 */
	public static function replace_block_refs( $items, $id_map = array(), $content_key = 'content' ): array {
		if ( empty( $id_map ) || ! is_array( $items ) ) {
			return $items;
		}

		foreach ( $items as $key => $item ) {
			if ( isset( $item[ $content_key ] ) && ! empty( $item[ $content_key ] ) ) {
				$content = $item[ $content_key ];

				foreach ( $id_map as $old_id => $new_id ) {
					$pattern1 = '/(<!--\s*wp:block\s*\{\s*"ref"\s*:\s*)' . preg_quote( $old_id, '/' ) . '(\s*\}\s*\/-->)/';
					$content  = preg_replace( $pattern1, '${1}' . $new_id . '${2}', $content );
				}

				$items[ $key ][ $content_key ] = $content;
			}
		}

		return $items;
	}
}
