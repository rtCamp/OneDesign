<?php
/**
 * Register Design Library post type.
 *
 * @package onedesign
 */

namespace OneDesign\Post_Types;

/**
 * Class Design Library
 */
class Design_Library extends Base {

	/**
	 * Slug of post type.
	 *
	 * @var string
	 */
	const SLUG = 'design-library';

	/**
	 * Post type label for internal uses.
	 *
	 * @var string
	 */
	const LABEL = 'Design Library';

	/**
	 * To get a list of labels for Design Library post type.
	 *
	 * @return array
	 */
	public function get_labels(): array {
		return array(
			'name'               => _x( 'Design Library', 'post type general name', 'onedesign' ),
			'singular_name'      => _x( 'Design Library', 'post type singular name', 'onedesign' ),
			'menu_name'          => _x( 'Design Library', 'admin menu', 'onedesign' ),
			'name_admin_bar'     => _x( 'Design Library', 'add new on admin bar', 'onedesign' ),
			'add_new'            => _x( 'Add New', 'Design Library', 'onedesign' ),
			'add_new_item'       => __( 'Add New Design Library', 'onedesign' ),
			'new_item'           => __( 'New Design Library', 'onedesign' ),
			'edit_item'          => __( 'Edit Design Library', 'onedesign' ),
			'view_item'          => __( 'View Design Library', 'onedesign' ),
			'all_items'          => __( 'All Design Library', 'onedesign' ),
			'search_items'       => __( 'Search Design Library', 'onedesign' ),
			'parent_item_colon'  => __( 'Parent Design Library:', 'onedesign' ),
			'not_found'          => __( 'No Design Library found.', 'onedesign' ),
			'not_found_in_trash' => __( 'No Design Library found in trash.', 'onedesign' ),
		);
	}

	/**
	 * Change arguments for Design Library CPT.
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
