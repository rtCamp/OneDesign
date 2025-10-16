<?php
/**
 * Hooks class to handle all the hooks related functionalities.
 *
 * @package OneDesign
 */

namespace OneDesign;

use OneDesign\Plugin_Configs\Constants;
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

		// Create templates, patterns and template parts from saved options.
		add_action( 'after_setup_theme', array( $this, 'create_template' ), 99 );

		// add container for modal for site selection on activation.
		add_action( 'admin_footer', array( $this, 'add_site_selection_modal' ) );

		// add body class for site selection modal.
		add_filter( 'admin_body_class', array( $this, 'add_body_class_for_modal' ) );
		add_filter( 'admin_body_class', array( $this, 'add_body_class_for_missing_sites' ) );

		// add setup page link to plugins page.
		add_filter( 'plugin_action_links_' . ONEDESIGN_PLUGIN_LOADER_PLUGIN_BASENAME, array( $this, 'add_setup_page_link' ) );
	}

	/**
	 * Add setup page link to plugins page.
	 *
	 * @param array $links Existing plugin action links.
	 *
	 * @return array Modified plugin action links.
	 */
	public function add_setup_page_link( $links ): array {
		$setup_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=onedesign-settings' ) ),
			__( 'Settings', 'onedesign' )
		);
		array_unshift( $links, $setup_link );
		return $links;
	}

	/**
	 * Add site selection modal to admin footer.
	 *
	 * @return void
	 */
	public function add_site_selection_modal(): void {
		$current_screen = get_current_screen();
		if ( ! $current_screen || 'plugins' !== $current_screen->base ) {
			return;
		}

		// get current site type.
		$site_type = Utils::get_current_site_type();
		if ( ! empty( $site_type ) ) {
			return;
		}

		?>
		<div class="wrap">
			<div id="onedesign-site-selection-modal" class="onedesign-modal"></div>
		</div>
		<?php
	}

	/**
	 * Create global variable onedesign_sites with site info.
	 *
	 * @param string $classes Existing body classes.
	 *
	 * @return string
	 */
	public function add_body_class_for_modal( $classes ): string {
		$current_screen = get_current_screen();
		if ( ! $current_screen || 'plugins' !== $current_screen->base ) {
			return $classes;
		}

		// get current site type.
		$site_type = Utils::get_current_site_type();

		if ( ! empty( $site_type ) ) {
			return $classes;
		}

		// add onedesign-site-selection-modal class to body.
		$classes .= ' onedesign-site-selection-modal ';
		return $classes;
	}

	/**
	 * Add body class for missing sites.
	 *
	 * @param string $classes Existing body classes.
	 *
	 * @return string
	 */
	public function add_body_class_for_missing_sites( $classes ): string {
		$current_screen = get_current_screen();

		if ( ! $current_screen ) {
			return $classes;
		}

		// get onedesign_shared_sites option.
		$shared_sites = get_option( Constants::ONEDESIGN_SHARED_SITES, array() );

		// if shared_sites is empty or not an array, return the classes.
		if ( empty( $shared_sites ) || ! is_array( $shared_sites ) ) {
			$classes .= ' onedesign-missing-brand-sites ';

			// remove submenu pages.
			remove_submenu_page( 'onedesign', 'design-library' );
			remove_submenu_page( 'onedesign', 'onedesign-templates' );

			return $classes;
		}

		return $classes;
	}

	/**
	 * Create templates, patterns and template parts from saved options.
	 *
	 * @return void
	 */
	public function create_template(): void {

		if ( Utils::is_governing_site() ) {
			return;
		}

		$brand_site_post_ids = get_option( Constants::ONEDESIGN_BRAND_SITE_POST_IDS, array() );

		$shared_templates = get_option( Constants::ONEDESIGN_SHARED_TEMPLATES, array() );

		$logs = array();

		$all_post_types = get_post_types( array( 'public' => true ), 'names' );

		// Remove post types that shouldn't have templates.
		$excluded_types = array( 'attachment', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation' );
		$all_post_types = array_values( array_diff( $all_post_types, $excluded_types ) );

		foreach ( $shared_templates as $template ) {
			$res = register_block_template(
				sanitize_text_field( $template['id'] ),
				array(
					'slug'        => isset( $template['slug'] ) ? sanitize_text_field( $template['slug'] ) : '',
					'title'       => isset( $template['title'] ) ? sanitize_text_field( $template['title'] ) : '',
					'description' => isset( $template['description'] ) ? sanitize_textarea_field( $template['description'] ) : '',
					'content'     => $template['content'] ?? '',
					'post_types'  => isset( $template['post_types'] ) ? array_map( 'sanitize_textarea_field', $template['post_types'] ) : $all_post_types,
				)
			);

			$logs[] = sprintf(
				/* translators: 1: Template slug. 2: Result. */
				__( 'Template %1$s registration result: %2$s', 'onedesign' ),
				sanitize_text_field( $template['slug'] ) ?? '',
				wp_json_encode( $res )
			);
		}

		$shared_patterns = get_option( Constants::ONEDESIGN_SHARED_PATTERNS, array() );
		foreach ( $shared_patterns as $pattern ) {
			if ( ! class_exists( '\WP_Block_Patterns_Registry' ) ) {
				require_once ABSPATH . 'wp-includes/class-wp-block-patterns-registry.php';
			}
			$res    = register_block_pattern(
				sanitize_text_field( $pattern['slug'] ),
				array(
					'title'       => isset( $pattern['title'] ) ? sanitize_text_field( $pattern['title'] ) : '',
					'content'     => $pattern['content'] ?? '',
					'description' => isset( $pattern['description'] ) ? sanitize_textarea_field( $pattern['description'] ) : '',
					'postTypes'   => isset( $pattern['post_types'] ) ? array_map( 'sanitize_textarea_field', $pattern['post_types'] ) : array(),
				)
			);
			$logs[] = sprintf(
				/* translators: 1: Pattern slug. 2: Result. */
				__( 'Pattern %1$s registration result: %2$s', 'onedesign' ),
				sanitize_text_field( $pattern['slug'] ),
				$res
			);
		}

		$shared_template_parts = get_option( Constants::ONEDESIGN_SHARED_TEMPLATE_PARTS, array() );
		foreach ( $shared_template_parts as $template_part ) {
			// Check if template part already exists.
			$existing = get_posts(
				array(
					'post_type'   => 'wp_template_part',
					'name'        => sanitize_text_field( $template_part['slug'] ),
					'post_status' => 'any',
					'numberposts' => 1,
					'fields'      => 'ids',
				)
			);

			if ( ! empty( $existing ) ) {
				$logs[] = sprintf(
					/* translators: 1: Template part slug. */
					__( 'Template part already exists: %s', 'onedesign' ),
					sanitize_text_field( $template_part['slug'] ),
				);
				continue;
			}

			// Create the template part post.
			$post_data = array(
				'post_type'    => 'wp_template_part',
				'post_title'   => isset( $template_part['title'] ) ? sanitize_text_field( $template_part['title'] ) : '',
				'post_name'    => isset( $template_part['slug'] ) ? sanitize_text_field( $template_part['slug'] ) : '',
				'post_status'  => 'publish',
				'post_content' => $template_part['content'] ?? '',
			);

			$post_id = wp_insert_post( $post_data );

			if ( is_wp_error( $post_id ) ) {
				$logs[] = sprintf(
					/* translators: 1: Error message. */
					__( 'Error creating template part %1$s: %2$s', 'onedesign' ),
					sanitize_text_field( $template_part['slug'] ),
					$post_id->get_error_message()
				);
				continue;
			} else {
				$logs[] = sprintf(
					/* translators: 1: Template part slug. 2: Post ID. */
					__( 'Template part created successfully: %1$s (ID: %2$d)', 'onedesign' ),
					sanitize_text_field( $template_part['slug'] ),
					$post_id
				);
				$brand_site_post_ids[] = $post_id;
			}

			$current_theme = get_option( 'stylesheet' );
			$theme_slug    = get_option( 'template' );

			// add required meta & assign taxonomy terms.
			update_post_meta( $post_id, '_wp_template_part_area', sanitize_text_field( $template_part['area'] ) ?? 'uncategorized' );
			update_post_meta( $post_id, '_wp_theme', $current_theme );
			update_post_meta( $post_id, '_wp_template_part_theme', $theme_slug );
			wp_set_object_terms( $post_id, sanitize_text_field( $template_part['area'] ) ?? 'uncategorized', 'wp_template_part_area' );
			wp_set_object_terms( $post_id, $current_theme, 'wp_theme' );

			// Store description if provided.
			if ( isset( $template_part['description'] ) ) {
				update_post_meta( $post_id, 'description', sanitize_textarea_field( $template_part['description'] ) );
			}

			$logs[] = sprintf(
				/* translators: 1: Template part slug. 2: Post ID. */
				__( 'Template part setup completed: %1$s (ID: %2$d)', 'onedesign' ),
				sanitize_text_field( $template_part['slug'] ),
				$post_id
			);
		}
		update_option( Constants::ONEDESIGN_BRAND_SITE_POST_IDS, array_unique( $brand_site_post_ids ), false );
	}

	/**
	 * Allow only specific block types.
	 *
	 * @param bool|array               $allowed_block_types Array of allowed block types or boolean to allow all or disallow all.
	 * @param \WP_Block_Editor_Context $editor_context               The post being edited, provided by the 'allowed_block_types_all' filter.
	 *
	 * @return array|bool
	 */
	public function allowed_block_types( bool|array $allowed_block_types, \WP_Block_Editor_Context $editor_context ): array|bool {
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
		$site_patterns = get_option( Constants::ONEDESIGN_BRAND_SITE_PATTERNS );
		if ( ! is_array( $site_patterns ) ) {
			return;
		}

		foreach ( $site_patterns as $pattern_name => $pattern_data ) {
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
