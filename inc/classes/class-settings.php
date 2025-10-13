<?php
/**
 * Settings class.
 * This class handles the settings page for the OneDesign plugin,
 *
 * @package OneDesign
 */

namespace OneDesign;

use OneDesign\Traits\Singleton;
use OneDesign\Post_Types\{ Design_Library, Template };

/**
 * Class Settings
 */
class Settings {

	/**
	 * Use Singleton trait.
	 */
	use Singleton;

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'onedesign';

	/**
	 * Construct method.
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Function to setup hooks.
	 */
	public function setup_hooks(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'handle_design_library_redirect' ) );
		add_action( 'admin_init', array( $this, 'templates_page_redirection' ) );
	}

	/**
	 * Add a settings page.
	 *
	 * @return void
	 */
	public function add_settings_page(): void {
		add_menu_page(
			__( 'OneDesign', 'onedesign' ),
			__( 'OneDesign', 'onedesign' ),
			'manage_options',
			self::PAGE_SLUG,
			'__return_null',
			'',
			2
		);

		// Add submenu for opening design library only for dashboard sites.
		if ( Utils::is_governing_site() ) {
			add_submenu_page(
				self::PAGE_SLUG,
				__( 'Design Library', 'onedesign' ),
				__( 'Design Library', 'onedesign' ),
				'manage_options',
				'design-library',
				'__return_null'
			);
			add_submenu_page(
				self::PAGE_SLUG,
				__( 'Templates', 'onedesign' ),
				__( 'Template Library', 'onedesign' ),
				'manage_options',
				'onedesign-templates',
				'__return_null'
			);
		}

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Settings', 'onedesign' ),
			__( 'Settings', 'onedesign' ),
			'manage_options',
			'onedesign-settings',
			array( $this, 'settings_page_content' )
		);

		remove_submenu_page( 'onedesign', 'onedesign' );
	}

	/**
	 * Handle the redirect to create or open the Templates post.
	 *
	 * This function checks if the Templates post exists and redirects to it,
	 * or creates a new one if it doesn't exist.
	 *
	 * @return void
	 */
	public function templates_page_redirection(): void {
		$pages = array( 'onedesign-templates' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || ! in_array( $_GET['page'], $pages, true ) ) {
			return;
		}

		// Only run for users with proper permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if a Design Library post already exists.
		$existing_posts = get_posts(
			array(
				'post_type'        => Template::SLUG,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
				'numberposts'      => 1,
				'suppress_filters' => false,
			)
		);

		if ( ! empty( $existing_posts ) ) {
			// Redirect to edit the existing post.
			wp_safe_redirect( admin_url( 'post.php?post=' . $existing_posts[0]->ID . '&action=edit' ) );
			exit;
		}

		// If no post exists, create a new one.
		$new_post_id = wp_insert_post(
			array(
				'post_type'    => Template::SLUG,
				'post_title'   => esc_html__( 'Templates', 'onedesign' ),
				'post_content' => '',
				'post_status'  => 'draft',
			)
		);

		if ( is_wp_error( $new_post_id ) ) {
			wp_die( esc_html__( 'Error creating template post.', 'onedesign' ) );
		}

		// Redirect to the newly created post for editing.
		wp_safe_redirect( admin_url( 'post.php?post=' . $new_post_id . '&action=edit' ) );
		exit;
	}

	/**
	 * Handle the redirect to create or open the Design Library post.
	 *
	 * This function checks if the Design Library post exists and redirects to it,
	 * or creates a new one if it doesn't exist.
	 *
	 * @return void
	 */
	public function handle_design_library_redirect(): void {
		$pages = array( 'design-library', 'onedesign' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || ! in_array( $_GET['page'], $pages, true ) ) {
			return;
		}

		// Only run for users with proper permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Your existing create/redirect logic here.
		$this->create_and_open_design_library_post();
	}

	/**
	 * Render settings page content.
	 *
	 * @return void
	 */
	public function settings_page_content(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'onedesign' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Settings', 'onedesign' ); ?></h1>
			<div id="onedesign-settings-page"></div>
		</div>
		<?php
	}

	/**
	 * Callback function to create and open a new Design Library post.
	 *
	 * @return void
	 */
	public function create_and_open_design_library_post(): void {
		// Check if a Design Library post already exists.
		$existing_posts = get_posts(
			array(
				'post_type'        => Design_Library::SLUG,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
				'numberposts'      => 1,
				'suppress_filters' => false,
			)
		);

		if ( ! empty( $existing_posts ) ) {
			// Redirect to edit the existing post.
			wp_safe_redirect( admin_url( 'post.php?post=' . $existing_posts[0]->ID . '&action=edit' ) );
			exit;
		}

		// If no post exists, create a new one.
		$new_post_id = wp_insert_post(
			array(
				'post_type'    => Design_Library::SLUG,
				'post_title'   => esc_html__( 'Design Library', 'onedesign' ),
				'post_content' => '<!-- wp:heading {"level":2} --><h2>Click on the "Patterns Selection" to push patterns to consumer site.</h2><!-- /wp:heading -->',
				'post_status'  => 'draft',
			)
		);

		if ( is_wp_error( $new_post_id ) ) {
			wp_die( esc_html__( 'Error creating Design Library post.', 'onedesign' ) );
		}

		// Redirect to the newly created post for editing.
		wp_safe_redirect( admin_url( 'post.php?post=' . $new_post_id . '&action=edit' ) );
		exit;
	}
}
