<?php
/**
 * Hooks class to handle all the hooks related functionalities.
 *
 * @package onedesign
 */

namespace OneDesign;

use OneDesign\Traits\Singleton;

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
		add_action( 'wp_ajax_register_block_patterns', array( $this, 'ajax_register_block_patterns' ) );
		add_action( 'wp_ajax_nopriv_register_block_patterns', array( $this, 'ajax_register_block_patterns' ) );
		add_action( 'init', array( $this, 'register_block_patterns_if_not_exist' ) );
		add_filter( 'should_load_remote_block_patterns', '__return_false' );
		add_action( 'after_setup_theme', array( $this, 'remove_core_block_patterns' ) );
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
