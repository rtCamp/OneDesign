<?php
/**
 * Class Meta to register all the metas based on post type.
 *
 * @package OneDesign
 */

namespace OneDesign\Post_Types;

use OneDesign\Traits\Singleton;

/**
 * Class Meta
 */
class Meta {

	use Singleton;

	/**
	 * Construct method.
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Function to setup hook for registering the meta.
	 *
	 * @return void
	 */
	public function setup_hooks(): void {
		// Register all the meta based on post type here.
		add_action( 'init', array( $this, 'register_custom_meta' ), 10 );
	}

	/**
	 * Callback function to register the custom meta for all post types.
	 *
	 * @return void
	 */
	public function register_custom_meta(): void {

		// Get all the post meta with required information.
		$post_meta_array = $this->get_post_meta_array();

		foreach ( $post_meta_array as $meta_info ) {
			$args = array(
				'show_in_rest'  => $meta_info['show_in_rest'] ?? true,
				'type'          => $meta_info['type'] ?? '',
				'single'        => $meta_info['single'] ?? true,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			);

			if ( array_key_exists( 'default', $meta_info ) ) {
				$args['default'] = $meta_info['default'];
			}

			$post_types = $meta_info['post_type'];

			if ( is_array( $post_types ) && ! empty( $post_types ) ) {
				foreach ( $post_types as $post_type ) {
					register_post_meta( $post_type, $meta_info['meta'], $args );
				}
			}
		}
	}

	/**
	 * Function to register the meta array with
	 * required information to posttype, metakey, type and default values.
	 *
	 * @return array
	 */
	private function get_post_meta_array(): array {
		$post_meta_array = array(
			array(
				'post_type'    => array( Design_Library::SLUG ),
				'meta'         => 'consumer_site',
				'type'         => 'array',
				'show_in_rest' => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'integer',
						),
					),
				),
				'single'       => true,
			),
		);

		return $post_meta_array;
	}
}
