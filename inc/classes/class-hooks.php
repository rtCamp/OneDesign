<?php
/**
 * Hooks class to handle all the hooks related functionalities.
 *
 * @package onedesign
 */

namespace OneDesign;

use OneDesign\Traits\Singleton;
use OneDesign\Post_Types\{ Design_Library, Template };

/**
 * Class Hooks
 */
class Hooks {

	/**
	 * Use Singleton trait.
	 */
	use Singleton;

	/**
	 * Protected class constructor
	 *
	 * @return void
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
		add_action( 'admin_footer', array( $this, 'print_design_library_button_in_editor_js_template' ) );
		add_action( 'admin_footer', array( $this, 'add_templates_button_to_editor' ) );
		add_action( 'wp_ajax_register_block_patterns', array( $this, 'ajax_register_block_patterns' ) );
		add_action( 'wp_ajax_nopriv_register_block_patterns', array( $this, 'ajax_register_block_patterns' ) );
		add_action( 'init', array( $this, 'register_block_patterns_if_not_exist' ) );
		add_filter( 'should_load_remote_block_patterns', '__return_false' );
		add_action( 'after_setup_theme', array( $this, 'remove_core_block_patterns' ) );
		add_filter( 'allowed_block_types_all', array( $this, 'allowed_block_types' ), 10, 2 );

		// on admin init create template, pattern and template part.
		add_action( 'admin_init', array( $this, 'create_template' ) );
	}

	/**
	 * Create templates, patterns and template parts from saved options.
	 *
	 * @return void
	 */
	public function create_template(): void {

		$brand_site_post_ids = get_option( 'onedesign_brand_site_post_ids', array() );

		$shared_templates = get_option( 'onedesign_shared_templates', array() );

		$logs = array();

		foreach ( $shared_templates as $template ) {
			$res    = register_block_template(
				$template['id'],
				$template
			);
			$logs[] = sprintf(
				/* translators: 1: Template slug. 2: Result. */
				__( 'Template %1$s registration result: %2$s', 'onedesign' ),
				$template['slug'] ?? '',
				wp_json_encode( $res )
			);
		}

		$shared_patterns = get_option( 'onedesign_shared_patterns', array() );
		foreach ( $shared_patterns as $pattern ) {
			if ( ! class_exists( '\WP_Block_Patterns_Registry' ) ) {
				require_once ABSPATH . 'wp-includes/class-wp-block-patterns-registry.php';
			}
			$res    = register_block_pattern(
				$pattern['slug'],
				array(
					'title'       => $pattern['title'] ?? '',
					'content'     => $pattern['content'] ?? '',
					'description' => $pattern['description'] ?? '',
					'postTypes'   => $pattern['post_types'] ?? array(),
				)
			);
			$logs[] = sprintf(
				/* translators: 1: Pattern slug. 2: Result. */
				__( 'Pattern %1$s registration result: %2$s', 'onedesign' ),
				$pattern['slug'],
				$res
			);
		}

		$shared_template_parts = get_option( 'onedesign_shared_template_parts', array() );
		foreach ( $shared_template_parts as $template_part ) {
			// Check if template part already exists.
			$existing = get_posts(
				array(
					'post_type'   => 'wp_template_part',
					'name'        => $template_part['slug'],
					'post_status' => 'any',
					'numberposts' => 1,
					'fields'      => 'ids',
				)
			);

			if ( ! empty( $existing ) ) {
				$logs[] = sprintf(
					/* translators: 1: Template part slug. */
					__( 'Template part already exists: %s', 'onedesign' ),
					$template_part['slug']
				);
				continue;
			}

			// Create the template part post.
			$post_data = array(
				'post_type'    => 'wp_template_part',
				'post_title'   => $template_part['title'],
				'post_name'    => $template_part['slug'],
				'post_status'  => 'publish',
				'post_content' => $template_part['content'],
			);

			$post_id = wp_insert_post( $post_data );

			if ( is_wp_error( $post_id ) ) {
				$logs[] = sprintf(
					/* translators: 1: Error message. */
					__( 'Error creating template part %1$s: %2$s', 'onedesign' ),
					$template_part['slug'],
					$post_id->get_error_message()
				);
				continue;
			} else {
				$logs[] = sprintf(
					/* translators: 1: Template part slug. 2: Post ID. */
					__( 'Template part created successfully: %1$s (ID: %2$d)', 'onedesign' ),
					$template_part['slug'],
					$post_id
				);
				$brand_site_post_ids[] = $post_id;
			}

			$current_theme = get_option( 'stylesheet' );
			$theme_slug    = get_option( 'template' );
			// CRITICAL: Add all required meta fields.
			update_post_meta( $post_id, '_wp_template_part_area', $template_part['area'] ?? 'uncategorized' );
			update_post_meta( $post_id, '_wp_theme', $current_theme );

			// Add these additional meta fields that might be required.
			update_post_meta( $post_id, '_wp_template_part_theme', $theme_slug );

			// Set the correct taxonomy terms.
			wp_set_object_terms( $post_id, $template_part['area'] ?? 'uncategorized', 'wp_template_part_area' );
			wp_set_object_terms( $post_id, $current_theme, 'wp_theme' );

			// Store theme information.
			if ( isset( $template_part['theme'] ) ) {
				update_post_meta( $post_id, 'theme', $template_part['theme'] );
			} else {
				update_post_meta( $post_id, 'theme', get_stylesheet() );
			}

			// Store description if provided.
			if ( isset( $template_part['description'] ) ) {
				update_post_meta( $post_id, 'description', $template_part['description'] );
			}

			// Store post types if provided.
			if ( isset( $template_part['post_types'] ) && is_array( $template_part['post_types'] ) ) {
				update_post_meta( $post_id, 'post_types', $template_part['post_types'] );
			}

			$logs[] = sprintf(
				/* translators: 1: Template part slug. 2: Post ID. */
				__( 'Template part setup completed: %1$s (ID: %2$d)', 'onedesign' ),
				$template_part['slug'],
				$post_id
			);
		}
		update_option( 'onedesign_brand_site_post_ids', array_unique( $brand_site_post_ids ) );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			foreach ( $logs as $log_entry ) {
				error_log( $log_entry ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- only adding logs in debug mode.
			}
		}
	}

	/**
	 * Allow only specific block types.
	 *
	 * @param bool|array               $allowed_block_types Array of allowed block types or boolean to allow all or disallow all.
	 * @param \WP_Block_Editor_Context $editor_context               The post being edited, provided by the 'allowed_block_types_all' filter.
	 * @return array
	 */
	public function allowed_block_types( $allowed_block_types, $editor_context ) {
		// Allow all block types in the Design Library post type.
		if ( isset( $editor_context->post->post_type ) && ( Template::SLUG === $editor_context->post->post_type ) ) {
			return array();
		}
		return $allowed_block_types;
	}

	/**
	 * Remove core block patterns.
	 *
	 * @return void
	 */
	public function remove_core_block_patterns(): void {
		remove_theme_support( 'core-block-patterns' );
	}

	/**
	 * Prints the Design Library Button Template.
	 *
	 * @return void
	 */
	public function print_design_library_button_in_editor_js_template(): void {
		$current_screen = get_current_screen();
		if ( ! $current_screen || Design_Library::SLUG !== $current_screen->post_type ) {
			return;
		}
		?>
		<script id="design-library-gutenberg-button" type="text/html">
			<div id="design-library-button">
				<button id="design-library-main-button" type="button" class="button button-primary button-large">
					<span class="design-library-main-button-active">
					<?php esc_html_e( 'Patterns Selection', 'onedesign' ); ?>
				</button>
			</div>
		</script>
		<?php
	}

	/**
	 * Add templates button to editor.
	 *
	 * @return void
	 */
	public function add_templates_button_to_editor(): void {
		$current_screen = get_current_screen();
		if ( ! $current_screen || Template::SLUG !== $current_screen->post_type ) {
			return;
		}
		?>
		<script id="onedesign-template-button" type="text/html">
			<div id="onedesign-template-render">
				<button id="template-main-button" type="button" class="button button-primary button-large">
					<span class="onedesign-template-main-button-active">
					<?php esc_html_e( 'Templates', 'onedesign' ); ?>
				</button>
			</div>
		</script>
		<?php
	}

	/**
	 * Register block patterns via ajax.
	 *
	 * @return void
	 */
	public function ajax_register_block_patterns(): void {
		// Verify nonce for security.
		if ( ! check_ajax_referer( 'onedesign_nonce', 'security', false ) ) {
			wp_send_json_error( array( 'message' => 'Invalid security token.' ), 403 );
			return;
		}

		// Call the registration function.
		$this->register_block_patterns_if_not_exist();

		// Return success.
		wp_send_json_success( array( 'message' => 'Patterns registered successfully' ) );
	}

	/**
	 * Register block patterns if not exist.
	 *
	 * @return void
	 */
	public function register_block_patterns_if_not_exist(): void {
		$consumer_site_patterns = get_option( 'consumer_site_patterns' );
		if ( ! is_array( $consumer_site_patterns ) ) {
			return;
		}

		foreach ( $consumer_site_patterns as $pattern_name => $pattern_data ) {
			if ( ! is_array( $pattern_data ) || empty( $pattern_name ) ) {
				continue;
			}

			// Check if pattern already registered.
			if ( class_exists( '\WP_Block_Patterns_Registry' ) && \WP_Block_Patterns_Registry::get_instance()->is_registered( $pattern_name ) ) {
				continue;
			}

			// Prepare pattern args.
			$pattern_args = array(
				'title'       => $pattern_data['title'] ?? '',
				'content'     => $pattern_data['content'] ?? '',
				'categories'  => $pattern_data['categories'] ?? array(),
				'keywords'    => $pattern_data['keywords'] ?? array(),
				'description' => $pattern_data['description'] ?? '',
			);

			// Register categories if they are not registered.
			if ( ! empty( $pattern_args['categories'] ) ) {
				foreach ( $pattern_args['categories'] as $category ) {
					if ( ! term_exists( $category, 'wp_pattern_category' ) ) {
						wp_insert_term( $category, 'wp_pattern_category' );
					}
				}
			}

			// Add optional properties if they exist.
			if ( isset( $pattern_data['viewportWidth'] ) ) {
				$pattern_args['viewportWidth'] = $pattern_data['viewportWidth'];
			}
			if ( isset( $pattern_data['blockTypes'] ) ) {
				$pattern_args['blockTypes'] = $pattern_data['blockTypes'];
			}
			if ( isset( $pattern_data['postTypes'] ) ) {
				$pattern_args['postTypes'] = $pattern_data['postTypes'];
			}
			if ( isset( $pattern_data['templateTypes'] ) ) {
				$pattern_args['templateTypes'] = $pattern_data['templateTypes'];
			}
			if ( isset( $pattern_data['inserter'] ) ) {
				$pattern_args['inserter'] = $pattern_data['inserter'];
			}

			// Only register if we have required content.
			if ( ! empty( $pattern_args['title'] ) && ! empty( $pattern_args['content'] ) ) {
				register_block_pattern( $pattern_name, $pattern_args );
			}
		}
	}
}
