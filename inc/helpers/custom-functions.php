<?php
/**
 * This file will have helper functions which are re-usable for OneDesign.
 *
 * @package OneDesign
 */

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
	$pattern = '/<!--\s*wp:(template-part|pattern)\s*(\{[^}]*\})?\s*\/?-->/';

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

					// Use flatten the nested results into the main array.
					$results = array_merge( $results, $nested_blocks );
				}
			}
		}
	}

	return $results;
}
