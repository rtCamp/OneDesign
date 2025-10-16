<?php
/**
 * Restrict CPTs creation for more than 1 site.
 *
 * @package OneDesign
 */

namespace OneDesign;

use OneDesign\Traits\Singleton;
use OneDesign\Post_Types\{ Pattern, Template };

/**
 * Class CPT_Restriction
 */
class CPT_Restriction {

	/**
	 * Use Singleton trait.
	 */
	use Singleton;

	/**
	 * Slug for the Design Sync menu.
	 *
	 * @var string
	 */
	const MENU_SLUG = 'onedesign-design-sync';

	/**
	 * Protected class constructor
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Function to setup hooks.
	 */
	public function setup_hooks(): void {
		add_filter( 'register_post_type_args', array( $this, 'restrict_cpt' ), 5, 2 );
		add_action( 'init', array( $this, 'unregister_cpt' ), 20 );
		add_action( 'current_screen', array( $this, 'limit_pattern_library_posts' ) );
		add_action( 'current_screen', array( $this, 'limit_template_posts' ) );
		add_filter( 'register_post_type_args', array( $this, 'modify_pattern_library_labels' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'modify_pattern_library_admin_menu' ), 999 );
		add_filter( 'default_content', array( $this, 'add_default_content_to_editor' ), 10, 2 );
		add_filter( 'default_title', array( $this, 'add_default_title_to_editor' ), 10, 2 );
	}

	/**
	 * Callback function to add default title to the editor.
	 *
	 * @param string $title The default title.
	 *
	 * @return string Modified title.
	 */
	public function add_default_title_to_editor( string $title ): string {
		if ( Pattern::SLUG === get_current_screen()->post_type ) {
			return esc_html__( 'Pattern Library', 'onedesign' );
		}
		return $title;
	}

	/**
	 * Callback function to restrict CPT creation.
	 *
	 * @param array  $args      Array of arguments for registering a post type.
	 * @param string $post_type Post type key.
	 *
	 * @return array Modified arguments.
	 */
	public function restrict_cpt( array $args, string $post_type ): array {
		if ( ! in_array( $post_type, array( Pattern::SLUG, Template::SLUG ), true ) ) {
			return $args;
		}

		if ( Utils::is_governing_site() ) {
			// Only remove it from the menu if it's a governing site.
			$args['show_in_menu']        = false;
			$args['show_in_admin_bar']   = false;
			$args['show_in_nav_menus']   = false;
			$args['has_archive']         = false;
			$args['exclude_from_search'] = true;
			$args['publicly_queryable']  = false;

			return $args;
		}

		if ( in_array( $post_type, array( Pattern::SLUG, Template::SLUG ), true ) ) {
			$args['public']              = false;
			$args['show_ui']             = false;
			$args['show_in_menu']        = false;
			$args['show_in_admin_bar']   = false;
			$args['show_in_nav_menus']   = false;
			$args['can_export']          = false;
			$args['has_archive']         = false;
			$args['exclude_from_search'] = true;
			$args['publicly_queryable']  = false;
			$args['show_in_rest']        = false;
			$args['capabilities']        = array(
				'edit_post'          => 'do_not_allow',
				'read_post'          => 'do_not_allow',
				'delete_post'        => 'do_not_allow',
				'edit_posts'         => 'do_not_allow',
				'edit_others_posts'  => 'do_not_allow',
				'publish_posts'      => 'do_not_allow',
				'read_private_posts' => 'do_not_allow',
			);
		}

		return $args;
	}

	/**
	 * Callback function to remove CPT support.
	 */
	public function unregister_cpt(): void {
		if ( Utils::is_governing_site() ) {
			return;
		}

		unregister_post_type( Pattern::SLUG );
		unregister_post_type( Template::SLUG );
	}

	/**
	 * Callback function to limit pattern library posts.
	 *
	 * @return void
	 */
	public function limit_pattern_library_posts(): void {
		// Check if we're trying to create a new pattern library post.
		$screen = get_current_screen();
		if ( ! $screen || Pattern::SLUG !== $screen->post_type || 'add' !== $screen->action ) {
			return;
		}

		// Count existing pattern library posts.
		$existing_posts = wp_count_posts( Pattern::SLUG );
		$post_count     = $existing_posts->publish + $existing_posts->draft + $existing_posts->pending + $existing_posts->private;

		// If a post already exists, redirect to edit screen.
		if ( $post_count > 0 ) {
			// Get the existing post.
			$existing_post = get_posts(
				array(
					'post_type'        => Pattern::SLUG,
					'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
					'numberposts'      => 1,
					'suppress_filters' => false,
				)
			);

			if ( ! empty( $existing_post ) ) {
				// Redirect to edit screen of the existing post.
				wp_safe_redirect( admin_url( 'post.php?post=' . $existing_post[0]->ID . '&action=edit' ) );
				exit;
			}
		}
	}

	/**
	 * Callback function to limit template posts.
	 *
	 * @return void
	 */
	public function limit_template_posts(): void {
		$screen = get_current_screen();
		if ( ! $screen || Template::SLUG !== $screen->post_type || 'add' !== $screen->action ) {
			return;
		}

		// Count existing pattern library posts.
		$existing_posts = wp_count_posts( Template::SLUG );

		// check if $existing_posts is not null to avoid errors.
		if ( ! $existing_posts ) {
			return;
		}

		$post_count = $existing_posts->publish + $existing_posts->draft + $existing_posts->pending + $existing_posts->private;

		// If a post already exists, redirect to edit screen.
		if ( $post_count > 0 ) {
			// Get the existing post.
			$existing_post = get_posts(
				array(
					'post_type'        => Template::SLUG,
					'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
					'numberposts'      => 1,
					'suppress_filters' => false,
				)
			);

			if ( ! empty( $existing_post ) ) {
				// Redirect to edit screen of the existing post.
				wp_safe_redirect( admin_url( 'post.php?post=' . $existing_post[0]->ID . '&action=edit' ) );
				exit;
			}
		}
	}

	/**
	 * Callback function to modify pattern library labels.
	 *
	 * @param array  $args      Array of arguments for registering a post type.
	 * @param string $post_type Post type key.
	 *
	 * @return array Modified arguments.
	 */
	public function modify_pattern_library_labels( array $args, string $post_type ): array {
		// Only modify if it's our post type.
		if ( Pattern::SLUG !== $post_type ) {
			return $args;
		}

		// Use direct database query instead of wp_count_posts() as wp_count_posts() doesn't work within 'register_post_type_args' filter.
		global $wpdb;
		$post_count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- We need to get the latest count.
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = %s AND post_status IN ('publish', 'draft', 'pending', 'private')",
				Pattern::SLUG
			)
		);

		if ( $post_count > 0 ) {
			// Change "Add New" text to "Edit Pattern Library".
			if ( isset( $args['labels'] ) ) {
				$args['labels']['add_new']      = esc_html__( 'Edit Pattern Library', 'onedesign' );
				$args['labels']['add_new_item'] = esc_html__( 'Edit Pattern Library', 'onedesign' );
			}
		}

		return $args;
	}

	/**
	 * Callback function to modify a pattern library admin menu.
	 */
	public function modify_pattern_library_admin_menu(): void {
		global $submenu;

		// Make sure the submenu exists and contains our post type.
		if ( ! isset( $submenu[ 'edit.php?post_type=' . Pattern::SLUG ] ) ) {
			return;
		}

		// Count existing pattern library posts.
		$existing_posts = wp_count_posts( Pattern::SLUG );

		// check if $existing_posts is not null to avoid errors.
		if ( ! $existing_posts ) {
			return;
		}

		$post_count = $existing_posts->publish + $existing_posts->draft + $existing_posts->pending + $existing_posts->private;

		if ( $post_count > 0 ) {
			// Find the "Add New" menu item.
			foreach ( $submenu[ 'edit.php?post_type=' . Pattern::SLUG ] as $key => $item ) {
				if ( 'post-new.php?post_type=' . Pattern::SLUG === $item[2] ) {
					// Get the existing post.
					$existing_post = get_posts(
						array(
							'post_type'        => Pattern::SLUG,
							'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
							'numberposts'      => 1,
							'suppress_filters' => false,
						)
					);

					if ( ! empty( $existing_post ) ) {
						// Change the "Add New" link to edit the existing post.
						$submenu['edit.php?post_type=onedesign-pattern'][ $key ][2] = 'post.php?post=' . $existing_post[0]->ID . '&action=edit'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- We need to modify for pattern library post type.
						$submenu['edit.php?post_type=onedesign-pattern'][ $key ][0] = esc_html__( 'Edit Pattern Library', 'onedesign' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- We need to modify for pattern library post type.
					}
					break;
				}
			}
		}
	}

	/**
	 * Callback function to add default content to the editor.
	 *
	 * @param string $content The default content.
	 * @param object $post    The post object.
	 *
	 * @return string Modified content.
	 */
	public function add_default_content_to_editor( string $content, object $post ): string {
		if ( Pattern::SLUG === $post->post_type && empty( $content ) ) {
			$content = '<!-- wp:heading {"level":2} -->
			<h2>Click on the "Patterns Selection" to push patterns to brand site.</h2>
			<!-- /wp:heading -->';
		}

		return $content;
	}
}
