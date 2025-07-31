<?php
/**
 * Abstract class to register post type.
 *
 * @package onedesign
 */

namespace OneDesign\Post_Types;

use OneDesign\Traits\Singleton;

/**
 * Base class to register post types.
 */
abstract class Base {

	/**
	 * Use Singleton trait.
	 */
	use Singleton;

	/**
	 * Construct method.
	 */
	final protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * To register action/filters.
	 *
	 * @return void
	 */
	protected function setup_hooks(): void {

		/**
		 * Actions
		 */
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * To register post type.
	 *
	 * @return void
	 */
	final public function register_post_type(): void {

		if ( empty( static::SLUG ) ) {
			return;
		}

		$args = $this->get_args();
		$args = ( ! empty( $args ) && is_array( $args ) ) ? $args : array();

		$labels = $this->get_labels();
		$labels = ( ! empty( $labels ) && is_array( $labels ) ) ? $labels : array();

		if ( ! empty( $labels ) && is_array( $labels ) ) {
			$args['labels'] = $labels;
		}

		// phpcs:ignore WordPress.NamingConventions.ValidPostTypeSlug.NotStringLiteral
		register_post_type( static::SLUG, $args );
	}

	/**
	 * To get argument to register custom post type.
	 *
	 * To override arguments, define this method in a child class and override args.
	 *
	 * @return array
	 */
	public function get_args(): array {
		return array(
			'show_in_rest'  => true,
			'public'        => true,
			'has_archive'   => true,
			'menu_position' => 6,
			'supports'      => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
		);
	}

	/**
	 * To get slug of post type.
	 *
	 * @return string Slug of post type.
	 */
	public function get_slug(): string {
		return ( ! empty( static::SLUG ) ) ? static::SLUG : '';
	}

	/**
	 * To get a list of labels for custom post type.
	 * Must be in child class.
	 *
	 * @return array
	 */
	abstract public function get_labels(): array;
}
