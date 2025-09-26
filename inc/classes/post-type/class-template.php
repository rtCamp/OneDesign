<?php
/**
 * Register template post type.
 *
 * @package OneDesign
 */

namespace OneDesign\Post_Types;

/**
 * Class Template
 */
class Template extends Base {

	/**
	 * Slug of post type.
	 *
	 * @var string
	 */
	const SLUG = 'onedesign-template';

	/**
	 * Post type label for internal uses.
	 *
	 * @var string
	 */
	const LABEL = 'Template';

	/**
	 * To get a list of labels for template post type.
	 *
	 * @return array
	 */
	public function get_labels(): array {
		return array(
			'name'               => _x( 'Template', 'post type general name', 'onedesign' ),
			'singular_name'      => _x( 'Template', 'post type singular name', 'onedesign' ),
			'menu_name'          => _x( 'Template', 'admin menu', 'onedesign' ),
			'name_admin_bar'     => _x( 'Template', 'add new on admin bar', 'onedesign' ),
			'add_new'            => _x( 'Add New', 'Template', 'onedesign' ),
			'add_new_item'       => __( 'Add New Template', 'onedesign' ),
			'new_item'           => __( 'New Template', 'onedesign' ),
			'edit_item'          => __( 'Edit Template', 'onedesign' ),
			'view_item'          => __( 'View Template', 'onedesign' ),
			'all_items'          => __( 'All Template', 'onedesign' ),
			'search_items'       => __( 'Search Template', 'onedesign' ),
			'parent_item_colon'  => __( 'Parent Template:', 'onedesign' ),
			'not_found'          => __( 'No Template found.', 'onedesign' ),
			'not_found_in_trash' => __( 'No Template found in trash.', 'onedesign' ),
		);
	}

	/**
	 * Change arguments for Template CPT.
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
			'supports'      => array( 'title', 'editor', 'custom-fields' ),
			'menu_icon'     => 'dashicons-media-text',
		);
	}
}
