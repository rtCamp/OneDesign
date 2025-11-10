<?php
/**
 * This file will have helper functions which are re-usable for OneDesign.
 *
 * @package OneDesign
 */

use OneDesign\Utils;
use OneDesign\Plugin_Configs\Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Parse the block template content to extract blocks, template parts, and patterns.
 *
 * This function identifies and extracts blocks, template parts, and patterns from the provided content.
 * It handles nested structures and ensures that each unique content is processed only once to avoid duplication.
 *
 * @param string $content The block template content to parse.
 * @param array  $already_tracked An array to keep track of already processed content to avoid duplication.
 *                                This should be passed by reference to maintain state across recursive calls.
 *
 * @return array An array of parsed elements, each containing type, attributes, and content.
 */
function onedesign_parse_block_template( string $content, array &$already_tracked ): array {
	$results = array();

	// to process template parts and patterns.
	$pattern = '/<!--\s*wp:(template-part|pattern|block)\s*(\{[^}]*\})?\s*\/?-->/';

	if ( preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $match ) {
			$block_type      = $match[1];
			$attributes_json = isset( $match[2] ) ? $match[2] : '{}';

			// Decode JSON attributes.
			$attributes = json_decode( $attributes_json, true );

			$result = array(
				'type'       => $block_type,
				'full_match' => $match[0],
				'attributes' => $attributes ? $attributes : array(),
			);

			// Create unique tracking key based on content identity.
			$tracking_key = '';

			if ( 'template-part' === $block_type ) {
				$template_id           = $result['attributes']['theme'] . '//' . $result['attributes']['slug'];
				$result['content']     = get_block_template(
					id: $template_id,
					template_type: 'wp_template_part'
				) ?? '';
				$result['id']          = $result['content']->id ?? null;
				$result['slug']        = $result['content']->slug ?? null;
				$result['theme']       = $result['content']->theme ?? null;
				$result['title']       = $result['content']->title ?? null;
				$result['description'] = $result['content']->description ?? null;
				$result['post_types']  = $result['content']->post_types ?? null;
				$result['area']        = $result['content']->area ?? null;
				$tracking_key          = 'template-part_' . $template_id;
			}

			if ( 'pattern' === $block_type ) {
				$result['content']     = WP_Block_Patterns_Registry::get_instance()->get_registered( $result['attributes']['slug'] ) ?? '';
				$result['title']       = $result['content']['title'] ?? null;
				$result['slug']        = $result['content']['slug'] ?? null;
				$result['description'] = $result['content']['description'] ?? null;
				$result['name']        = $result['content']['name'] ?? null;
				$result['post_types']  = $result['content']->post_types ?? null;
				$tracking_key          = 'pattern_' . $result['attributes']['slug'];
			}

			if ( 'block' === $block_type ) {
				$result['content']     = get_post( $attributes['ref'] );
				$result['id']          = $result['content']->ID ?? null;
				$result['slug']        = $result['content']->post_name ?? null;
				$result['title']       = $result['content']->post_title ?? null;
				$result['description'] = $result['content']->post_excerpt ?? null;
				$result['content']     = $result['content']->post_content ?? null;
				$tracking_key          = 'block_' . $attributes['ref'];
			}

			// Check if this specific content has already been processed.
			if ( ! in_array( $tracking_key, $already_tracked, true ) ) {
				// Add to tracking to prevent processing again.
				$already_tracked[] = $tracking_key;

				// Add to results only if not already processed.
				$results[] = $result;

				// Recursively parse nested blocks and merge them at the same level.
				if ( ! empty( $result['content'] ) ) {
					$nested_blocks = array();

					if ( isset( $result['content']->content ) ) {
						$nested_blocks = onedesign_parse_block_template( $result['content']->content, $already_tracked );
					}

					if ( isset( $result['content']->post_content ) ) {
						$nested_blocks = onedesign_parse_block_template( $result['content']->post_content, $already_tracked );
					}

					// Flatten the nested results into the main array.
					$results = array_merge( $results, $nested_blocks );
				}
			}
		}
	}

	return $results;
}

/**
 * Validate API key for general request.
 *
 * @return bool
 */
function onedesign_validate_api_key(): bool {
	return onedesign_key_validation( false );
}

/**
 * Validate API key for health check.
 *
 * @return bool
 */
function onedesign_validate_api_key_health_check(): bool {
	return onedesign_key_validation( true );
}

/**
 * Validate API key.
 *
 * @param bool $is_health_check Whether the request is for health check or not.
 *
 * @return bool
 */
function onedesign_key_validation( bool $is_health_check ): bool {
	// check if the request is from same site.
	if ( Utils::is_governing_site() ) {
		return current_user_can( 'manage_options' );
	}

	// check X-OneDesign-Token header.
	if ( isset( $_SERVER['HTTP_X_ONEDESIGN_TOKEN'] ) && ! empty( $_SERVER['HTTP_X_ONEDESIGN_TOKEN'] ) ) {
		$token = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_ONEDESIGN_TOKEN'] ) );
		// Get the api key from options.
		$api_key = get_option( Constants::ONEDESIGN_API_KEY, 'default_api_key' );

		// governing site url.
		$governing_site_url = get_option( Constants::ONEDESIGN_GOVERNING_SITE_URL, '' );

		// check if governing site is set and matches with request origin.
		$request_origin   = isset( $_SERVER['HTTP_ORIGIN'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
		$current_site_url = get_site_url();
		$user_agent       = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : ''; // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__ -- this is to know requesting user domain for request which are generated from server.
		$is_token_valid   = hash_equals( $token, $api_key );
		$is_same_domain   = ! empty( $request_origin ) && Utils::is_same_domain( $current_site_url, $request_origin );

		// if token is valid and from same domain return true.
		if ( Utils::is_brand_site() && $is_same_domain && $is_token_valid ) {
			return true;
		}

		// if token is valid and request is from different domain then save it as governing site.
		if ( Utils::is_brand_site() && ! $is_same_domain && $is_token_valid && empty( $governing_site_url ) && $is_health_check ) {
			update_option( Constants::ONEDESIGN_GOVERNING_SITE_URL, $request_origin, false );
			return true;
		}

		// if token is valid and request is from different domain then check if it matches governing site url.
		if ( Utils::is_brand_site() && ! $is_same_domain && $is_token_valid && ! empty( $governing_site_url ) && ( Utils::is_same_domain( $governing_site_url, $request_origin ) || false !== strpos( $user_agent, $governing_site_url ) ) ) {
			return true;
		}

		// if its multisite and token is valid then check governing site url and request origin is in multisite url list.
		if ( Utils::is_multisite() && $is_token_valid && ! empty( $governing_site_url ) ) {
			$all_multisite_urls = Utils::get_all_multisite_urls();
			if ( ( in_array( $request_origin, $all_multisite_urls, true ) && in_array( $governing_site_url, $all_multisite_urls, true ) ) || false !== strpos( $user_agent, $request_origin ) ) {
				return true;
			}
		}
	}
	return false;
}
