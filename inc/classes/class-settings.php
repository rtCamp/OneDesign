<?php
/**
 * Settings class.
 * This class handles the settings page for the OneDesign plugin,
 *
 * @package onedesign
 */

namespace OneDesign;

use OneDesign\Traits\Singleton;
use OneDesign\Post_Types\Design_Library;

/**
 * Class Settings
 */
class Settings {

	use Singleton;

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'onedesign-settings';

	/**
	 * Option name for site type.
	 *
	 * @var string
	 */
	const OPTION_SITE_TYPE = 'onedesign_site_type';

	/**
	 * Option name for public key.
	 *
	 * @var string
	 */
	const OPTION_OWN_API_KEY = 'onedesign_child_site_public_key';

	/**
	 * Option name for child sites storage.
	 *
	 * @var string
	 */
	const OPTION_CHILD_SITES = 'onedesign_child_sites';

	/**
	 * Option name for site logo.
	 *
	 * @var string
	 */
	const OPTION_SITE_LOGO = 'onedesign_site_logo';

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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_init', array( $this, 'handle_form_actions' ) );
		add_action( 'admin_init', array( $this, 'handle_design_library_redirect' ) );
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
			array( $this, 'settings_page_content' ),
			'dashicons-admin-generic',
			100
		);

		// Add submenu for opening design library only for dashboard sites.
		$site_type = get_option( self::OPTION_SITE_TYPE, 'consumer' );
		if ( 'dashboard' === $site_type ) {
			add_submenu_page(
				self::PAGE_SLUG,
				__( 'Design Library', 'onedesign' ),
				__( 'Design Library', 'onedesign' ),
				'manage_options',
				'design-library',
				'__return_null'
			);
		}
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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		// Only run on our specific admin page
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'design-library' ) {
			return;
		}

		// Only run for users with proper permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Your existing create/redirect logic here
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

		$site_type   = get_option( self::OPTION_SITE_TYPE, 'consumer' );
		$api_key     = get_option( self::OPTION_OWN_API_KEY, '' );
		$child_sites = get_option( self::OPTION_CHILD_SITES, array() );

		if ( '' === $api_key && 'consumer' === $site_type ) {
			// Generate an API key if it doesn't exist for consumer sites.
			$api_key = $this->generate_api_key();
			update_option( self::OPTION_OWN_API_KEY, $api_key );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="">
				<?php wp_nonce_field( 'onedesign_settings', 'onedesign_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="site_type"><?php esc_html_e( 'Site Type', 'onedesign' ); ?></label>
						</th>
						<td>
							<select name="site_type" id="site_type">
								<option value="consumer" <?php selected( $site_type, 'consumer' ); ?>>
									<?php esc_html_e( 'Consumer Site', 'onedesign' ); ?></option>
								<option value="dashboard" <?php selected( $site_type, 'dashboard' ); ?>>
									<?php esc_html_e( 'Dashboard Site', 'onedesign' ); ?></option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Choose whether this is a consumer site or dashboard site.', 'onedesign' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<!-- Consumer Site Options -->
				<div id="consumer-options" style="<?php echo 'consumer' === $site_type ? '' : 'display: none;'; ?>">
					<h2><?php esc_html_e( 'Consumer Site Settings', 'onedesign' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="api_key"><?php esc_html_e( 'API Key', 'onedesign' ); ?></label>
							</th>
							<td>
								<input type="text" name="api_key" id="api_key" value="<?php echo esc_attr( $api_key ); ?>"
									class="regular-text" readonly />
								<button type="submit" name="regenerate_api_key" class="button button-secondary"
									style="margin-left: 10px;">
									<?php esc_html_e( 'Regenerate API Key', 'onedesign' ); ?>
								</button>
								<p class="description">
									<?php esc_html_e( 'This API key will be used by dashboard sites to connect to this consumer site.', 'onedesign' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<!-- Dashboard Site Options -->
				<div id="dashboard-options" style="<?php echo 'dashboard' === $site_type ? '' : 'display: none;'; ?>">
					<h2><?php esc_html_e( 'Dashboard Site Settings', 'onedesign' ); ?></h2>
					<p><?php esc_html_e( 'Manage child sites that this dashboard will distribute patterns to.', 'onedesign' ); ?>
					</p>

					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Site Name', 'onedesign' ); ?></th>
								<th scope="col"><?php esc_html_e( 'URL', 'onedesign' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Logo', 'onedesign' ); ?></th>
								<th scope="col"><?php esc_html_e( 'API Key', 'onedesign' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Actions', 'onedesign' ); ?></th>
							</tr>
						</thead>
						<tbody id="child-sites-table">
							<?php if ( ! empty( $child_sites ) ) : ?>
								<?php foreach ( $child_sites as $site ) : ?>
									<tr data-id="<?php echo esc_attr( $site['id'] ); ?>">
										<td>
											<input type="text" name="child_sites[<?php echo esc_attr( $site['id'] ); ?>][name]"
												value="<?php echo esc_attr( $site['name'] ); ?>" class="regular-text" required />
											<input type="hidden" name="child_sites[<?php echo esc_attr( $site['id'] ); ?>][id]"
												value="<?php echo esc_attr( $site['id'] ); ?>" required />
										</td>
										<td>
											<input type="url" name="child_sites[<?php echo esc_attr( $site['id'] ); ?>][url]"
												value="<?php echo esc_attr( $site['url'] ); ?>" class="regular-text" required />
										</td>
										<td>
											<?php $site_logo = isset( $site['logo'] ) ? $site['logo'] : ''; ?>
											<div class="logo-container">
												<div class="logo-preview" id="child-logo-preview-<?php echo esc_attr( $site['id'] ); ?>"
													style="<?php echo empty( $site_logo ) ? 'display:none;' : ''; ?>">
													<?php if ( ! empty( $site_logo ) ) : ?>
														<img src="<?php echo esc_url( $site_logo ); ?>" alt="Site Logo" />
													<?php endif; ?>
												</div>
											</div>
											<input type="button" class="button button-secondary logo-upload-button child-logo-upload"
												data-id="<?php echo esc_attr( $site['id'] ); ?>" value="<?php esc_attr_e( 'Upload Logo', 'onedesign' ); ?>" data-logo-id="<?php echo esc_attr( attachment_url_to_postid( $site_logo ) ); ?>" />
											<input type="button" class="button button-link logo-remove-button child-logo-remove"
												data-id="<?php echo esc_attr( $site['id'] ); ?>" value="<?php esc_attr_e( 'Remove Logo', 'onedesign' ); ?>"
												style="<?php echo empty( $site_logo ) ? 'display:none;' : ''; ?>" />
											<input type="hidden" name="child_sites[<?php echo esc_attr( $site['id'] ); ?>][logo]" class="child-site-logo-url"
												id="child_site_logo_<?php echo esc_attr( $site['id'] ); ?>" value="<?php echo esc_attr( $site_logo ); ?>" />
											</td>
											<td>
											<input type="text" name="child_sites[<?php echo esc_attr( $site['id'] ); ?>][api_key]"
												value="<?php echo esc_attr( $site['api_key'] ); ?>" class="regular-text" required />
										</td>
										<td>
											<button type="button" class="button button-small delete-site"><?php esc_html_e( 'Delete', 'onedesign' ); ?></button>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr id="no-sites-row">
									<td colspan="4"><?php esc_html_e( 'No child sites configured yet.', 'onedesign' ); ?></td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>

					<p class="submit">
						<button type="button" class="button button-secondary add-site"><?php esc_html_e( 'Add Site', 'onedesign' ); ?></button>
					</p>
				</div>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle form actions.
	 *
	 * @return void
	 */
	public function handle_form_actions(): void {
		if ( ! isset( $_POST['onedesign_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['onedesign_nonce'] ) ), 'onedesign_settings' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle site type update.
		if ( isset( $_POST['site_type'] ) ) {
			$site_type = sanitize_text_field( wp_unslash( $_POST['site_type'] ) );
			if ( in_array( $site_type, array( 'consumer', 'dashboard' ), true ) ) {
				update_option( self::OPTION_SITE_TYPE, $site_type );
			}
		}

		// Handle API key regeneration.
		if ( isset( $_POST['regenerate_api_key'] ) ) {
			$new_api_key = $this->generate_api_key();
			update_option( self::OPTION_OWN_API_KEY, $new_api_key );
			add_action( 'admin_notices', array( $this, 'api_key_regenerated_notice' ) );
			return;
		}

		// Handle API key update for consumer sites.
		if ( isset( $_POST['api_key'] ) && get_option( self::OPTION_SITE_TYPE ) === 'consumer' ) {
			$api_key = sanitize_text_field( wp_unslash( $_POST['api_key'] ) );
			update_option( self::OPTION_OWN_API_KEY, $api_key );
		}

		// Handle site logo update (only for dashboard sites).
		if ( isset( $_POST['site_logo'] ) && get_option( self::OPTION_SITE_TYPE ) === 'dashboard' ) {
			$site_logo = esc_url_raw( wp_unslash( $_POST['site_logo'] ) );
			update_option( self::OPTION_SITE_LOGO, $site_logo );
		}

		// Handle child sites update for dashboard sites.
		if ( isset( $_POST['child_sites'] ) && get_option( self::OPTION_SITE_TYPE ) === 'dashboard' ) {
			$child_sites = array();

			foreach ( $_POST['child_sites'] as $site_data ) {
				$site_id      = isset( $site_data['id'] ) ? sanitize_text_field( $site_data['id'] ) : '';
				$site_name    = isset( $site_data['name'] ) ? sanitize_text_field( $site_data['name'] ) : '';
				$site_url     = isset( $site_data['url'] ) ? esc_url_raw( $site_data['url'] ) : '';
				$site_logo    = isset( $site_data['logo'] ) ? esc_url_raw( $site_data['logo'] ) : '';
				$site_api_key = isset( $site_data['api_key'] ) ? sanitize_text_field( $site_data['api_key'] ) : '';

				// Only save if all fields are filled
				if ( ! empty( $site_name ) && ! empty( $site_url ) && ! empty( $site_api_key ) ) {
					$child_sites[] = array(
						'id'      => $site_id,
						'name'    => $site_name,
						'url'     => $site_url,
						'logo'    => $site_logo,
						'api_key' => $site_api_key,
					);
				}
			}

			update_option( self::OPTION_CHILD_SITES, $child_sites );
		}

		// Generate an API key if the consumer site doesn't have one.
		if ( get_option( self::OPTION_SITE_TYPE ) === 'consumer' && empty( get_option( self::OPTION_OWN_API_KEY ) ) ) {
			$api_key = $this->generate_api_key();
			update_option( self::OPTION_OWN_API_KEY, $api_key );
		}

		add_action( 'admin_notices', array( $this, 'settings_saved_notice' ) );
	}

	/**
	 * Generate a random API key.
	 *
	 * @return string
	 */
	private function generate_api_key(): string {
		return 'ps_' . wp_generate_password( 32, false );
	}

	/**
	 * Show settings saved notice.
	 *
	 * @return void
	 */
	public function settings_saved_notice(): void {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'onedesign' ) . '</p></div>';
	}

	/**
	 * Show API key regenerated notice.
	 *
	 * @return void
	 */
	public function api_key_regenerated_notice(): void {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'API key regenerated successfully.', 'onedesign' ) . '</p></div>';
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts( string $hook ): void {
		if ( ( 'toplevel_page_' . self::PAGE_SLUG ) !== $hook && 'onedesign_page_design-library' !== $hook ) {
			return;
		}

		// Enqueue WordPress media scripts.
		wp_enqueue_media();

		// Register and enqueue our custom script.
		wp_register_script(
			'onedesign-admin',
			plugin_dir_url( dirname( __DIR__ ) ) . 'assets/build/js/admin.js',
			array( 'wp-i18n' ),
			'1.0.0',
			true
		);
		wp_enqueue_script( 'onedesign-admin' );

		wp_register_style(
			'onedesign-admin',
			plugin_dir_url( dirname( __DIR__ ) ) . 'assets/build/css/admin.css',
			array(),
			'1.0.0'
		);
		wp_enqueue_style( 'onedesign-admin' );
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
