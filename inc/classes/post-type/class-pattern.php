<?php
/**
 * Register Pattern post type.
 *
 * @package OneDesign
 */

namespace OneDesign\Post_Types;

/**
 * Class Pattern
 */
class Pattern extends Base {

	/**
	 * Slug of post type.
	 *
	 * @var string
	 */
	const SLUG = 'onedesign-pattern';

	/**
	 * Post type label for internal uses.
	 *
	 * @var string
	 */
	const LABEL = 'Pattern Library';

	/**
	 * To get a list of labels for Pattern post type.
	 *
	 * @return array
	 */
	public function get_labels(): array {
		return array(
			'name'               => _x( 'Pattern Library', 'post type general name', 'onedesign' ),
			'singular_name'      => _x( 'Pattern Library', 'post type singular name', 'onedesign' ),
			'menu_name'          => _x( 'Pattern Library', 'admin menu', 'onedesign' ),
			'name_admin_bar'     => _x( 'Pattern Library', 'add new on admin bar', 'onedesign' ),
			'add_new'            => _x( 'Add New', 'Pattern Library', 'onedesign' ),
			'add_new_item'       => __( 'Add New Pattern Library', 'onedesign' ),
			'new_item'           => __( 'New Pattern Library', 'onedesign' ),
			'edit_item'          => __( 'Edit Pattern Library', 'onedesign' ),
			'view_item'          => __( 'View Pattern Library', 'onedesign' ),
			'all_items'          => __( 'All Pattern Library', 'onedesign' ),
			'search_items'       => __( 'Search Pattern Library', 'onedesign' ),
			'parent_item_colon'  => __( 'Parent Pattern Library:', 'onedesign' ),
			'not_found'          => __( 'No Pattern Library found.', 'onedesign' ),
			'not_found_in_trash' => __( 'No Pattern Library found in trash.', 'onedesign' ),
		);
	}

	/**
	 * Change arguments for Pattern CPT.
	 *
	 * @return array
	 */
	public function get_args(): array {
		return array(
			'public'        => false,
			'show_ui'       => true,
			'has_archive'   => false,
			'show_in_rest'  => true,
			'menu_position' => 6,
			'supports'      => array( 'title', 'editor', 'revisions', 'custom-fields' ),
			'menu_icon'     => 'dashicons-networking',
		);
	}
}
