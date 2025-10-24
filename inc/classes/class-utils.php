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
	 * Build OneDesign REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = Constants::ONEDESIGN_REST_NAMESPACE . '/' . Constants::ONEDESIGN_REST_VERSION;

	/**
	 * Check if governing site is selected in multisite setup.
	 *
	 * @return bool True if governing site is selected, false otherwise.
	 */
	public static function is_governing_site_selected(): bool {
		if ( ! self::is_multisite() ) {
			return false;
		}
		$governing_site_id = self::get_multisite_governing_site();
		return $governing_site_id > 0;
	}

	/**
	 * Get shared sites information.
	 *
	 * @return array Array of shared sites information.
	 */
	public static function get_shared_sites_info(): array {
		$shared_sites = get_option( Constants::ONEDESIGN_SHARED_SITES, array() );
		return is_array( $shared_sites ) ? $shared_sites : array();
	}

	/**
	 * Check if the current setup is multisite.
	 *
	 * @return bool True if multisite, false otherwise.
	 */
	public static function is_multisite(): bool {
		return is_multisite();
	}

	/**
	 * Get the governing site for multisite setup.
	 *
	 * @return int Governing site ID, or 0 if not set.
	 */
	public static function get_multisite_governing_site(): int {
		if ( ! self::is_multisite() ) {
			return 0;
		}

		$governing_site_id = get_site_option( Constants::ONEDESIGN_MULTISITE_GOVERNING_SITE, 0 );
		return is_numeric( $governing_site_id ) ? (int) $governing_site_id : 0;
	}

	/**
	 * Get information of all multisites in the network.
	 *
	 * @return array Array of multisite information.
	 */
	public static function get_all_multisites_info(): array {
		if ( ! self::is_multisite() ) {
			return array();
		}

		$sites      = get_sites( array( 'number' => 0 ) );
		$sites_info = array();

		foreach ( $sites as $site ) {
			$site_details = get_blog_details( $site->blog_id );
			if ( $site_details ) {
				$sites_info[] = array(
					'id'   => (string) $site_details->blog_id,
					'name' => $site_details->blogname,
					'url'  => $site_details->siteurl,
				);
			}
		}

		return $sites_info;
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
		return hash_equals( 'brand-site', self::get_current_site_type() );
	}

	/**
	 * Check if the current site is a governing site.
	 *
	 * @return bool
	 */
	public static function is_governing_site(): bool {
		return hash_equals( 'governing-site', self::get_current_site_type() );
	}

	/**
	 * Build API endpoint URL.
	 *
	 * @param string $site_url The base URL of the site.
	 * @param string $endpoint The specific endpoint path.
	 * @param string $rest_namespace The REST namespace (default: self::NAMESPACE).
	 *
	 * @return string Full API endpoint URL.
	 */
	public static function build_api_endpoint( string $site_url, string $endpoint, string $rest_namespace = self::NAMESPACE ): string {
		return esc_url_raw( trailingslashit( $site_url ) ) . '/wp-json/' . $rest_namespace . '/' . ltrim( $endpoint, '/' );
	}

	/**
	 * Check if two URLs belong to the same domain.
	 *
	 * @param string $url1 First URL.
	 * @param string $url2 Second URL.
	 *
	 * @return bool True if both URLs belong to the same domain, false otherwise.
	 */
	public static function is_same_domain( string $url1, string $url2 ): bool {
		$parsed_url1 = wp_parse_url( $url1 );
		$parsed_url2 = wp_parse_url( $url2 );

		if ( ! isset( $parsed_url1['host'] ) || ! isset( $parsed_url2['host'] ) ) {
			return false;
		}
		return hash_equals( $parsed_url1['host'], $parsed_url2['host'] );
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
			function ( $site ) use ( $site_id ): bool {
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
		$sites_info = get_option( Constants::ONEDESIGN_SHARED_SITES, array() );
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
	public static function generate_unique_slug_for_template_patterns_template_parts( string $base_slug, string $sharing_site_name, bool $is_slug = true ): string {
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
	 * @param string|array|\WP_Block_Template $content The block content containing WordPress block markup.
	 * @param string                          $shared_site_name The name of the site to which template is going to be shared.
	 *
	 * @return array|string|null Modified content with updated slugs and themes.
	 */
	public static function modify_content_references( string|array|\WP_Block_Template $content, string $shared_site_name ): array|string|null {

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
			function ( $matches ) use ( $shared_site_name ): string|null {
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
	public static function modify_template_template_part_pattern_slug( array $templates, string $shared_site_name ): array {
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

			/**
			 * Removes the "theme" attribute from WordPress block comments in the content.
			 *
			 * For example, transforms:
			 * <!-- wp:template-part {"slug":"onedesign-onepress-2-ut","theme":"rtcamp-2024","area":"uncategorized"} /-->
			 * into:
			 * <!-- wp:template-part {"slug":"onedesign-onepress-2-ut","area":"uncategorized"} /-->
			 */
			$content = '';
			if ( $template instanceof \WP_Block_Template && isset( $template->content ) ) {
				$content = $template->content;
			} elseif ( is_array( $template ) && isset( $template['content'] ) ) {
				$content = $template['content'];
			}

			if ( ! empty( $content ) && is_string( $content ) ) {
				// Remove theme attribute from block comments.
				$pattern = '/<!--\s*wp:(template-part|pattern)\s*(\{[^}]*\})\s*\/?-->/';

				$content = preg_replace_callback(
					$pattern,
					function ( $matches ): string|null {
						$block_type      = $matches[1];
						$attributes_json = $matches[2];

						// Decode the attributes.
						$attributes = json_decode( $attributes_json, true );
						if ( ! $attributes ) {
							return $matches[0]; // Return original if JSON decode fails.
						}

						// Remove theme attribute if present.
						if ( isset( $attributes['theme'] ) ) {
							unset( $attributes['theme'] );
						}

						// Re-encode and return.
						return '<!-- wp:' . $block_type . ' ' . wp_json_encode( $attributes, JSON_UNESCAPED_SLASHES ) . ' /-->';
					},
					$content
				);

				// Assign cleaned content back.
				if ( $template instanceof \WP_Block_Template ) {
					$templates[ $index ]->content = $content;
				} else {
					$templates[ $index ]['content'] = $content;
				}
			}

			// Modify content references (uses the cleaned content from above).
			$current_content = '';
			if ( $template instanceof \WP_Block_Template && isset( $templates[ $index ]->content ) ) {
				$current_content = $templates[ $index ]->content;
			} elseif ( is_array( $template ) && isset( $templates[ $index ]['content'] ) ) {
				$current_content = $templates[ $index ]['content'];
			}

			if ( ! empty( $current_content ) ) {
				$modified_content = self::modify_content_references(
					$current_content,
					$shared_site_name
				);

				if ( $template instanceof \WP_Block_Template ) {
					$templates[ $index ]->content = $modified_content;
				} else {
					$templates[ $index ]['content'] = $modified_content;
				}
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
	public static function replace_block_refs( array $items, array $id_map = array(), string $content_key = 'content' ): array {
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
