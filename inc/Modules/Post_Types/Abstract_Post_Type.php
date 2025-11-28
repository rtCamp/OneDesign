<?php
/**
 * Abstract class to register post type.
 *
 * @package OneDesign
 */

namespace OneDesign\Modules\Post_Types;

use OneDesign\Contracts\Interfaces\Registrable;

/**
 * Base class to register post types.
 */
abstract class Abstract_Post_Type implements Registrable {

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
	}

	/**
	 * To register post type.
	 *
	 * @return void
	 */
	final public function register_post_type(): void {
		// phpcs:ignore SlevomatCodingStandard.Classes.DisallowLateStaticBindingForConstants.DisallowedLateStaticBindingForConstant -- @todo we don't need this.
		if ( empty( static::SLUG ) ) {
			return;
		}

		$args = $this->get_args();
		$args = ! empty( $args ) && is_array( $args ) ? $args : [];

		$labels = $this->get_labels();
		$labels = ! empty( $labels ) && is_array( $labels ) ? $labels : [];

		if ( ! empty( $labels ) && is_array( $labels ) ) {
			$args['labels'] = $labels;
		}

		// phpcs:ignore WordPress.NamingConventions.ValidPostTypeSlug.NotStringLiteral, SlevomatCodingStandard.Classes.DisallowLateStaticBindingForConstants.DisallowedLateStaticBindingForConstant -- @todo we don't need this.
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
		return [
			'show_in_rest'  => true,
			'public'        => true,
			'has_archive'   => true,
			'menu_position' => 6,
			'supports'      => [ 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ],
		];
	}

	/**
	 * To get slug of post type.
	 *
	 * @return string Slug of post type.
	 */
	public function get_slug(): string {
		// phpcs:ignore SlevomatCodingStandard.Classes.DisallowLateStaticBindingForConstants.DisallowedLateStaticBindingForConstant -- @todo we don't need this.
		return ! empty( static::SLUG ) ? static::SLUG : '';
	}

	/**
	 * To get a list of labels for custom post type.
	 * Must be in child class.
	 *
	 * @return array
	 */
	abstract public function get_labels(): array;
}
