<?php
/**
 * This file is to handle OneDesign Multisite related functionality.
 *
 * @package OneDesign
 */

namespace OneDesign;

use OneDesign\Plugin_Configs\Constants;
use OneDesign\Traits\Singleton;

/**
 * Class Multisite
 */
class Multisite {

	/**
	 * Use Singleton trait.
	 */
	use Singleton;

	/**
	 * Protected class constructor
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Setup hooks.
	 *
	 * @return void
	 */
	protected function setup_hooks(): void {

		// check if current site setup is multisite or not.
		if ( ! Utils::is_multisite() ) {
			return;
		}

		// add governing site selection modal on network admin plugins page.
		add_action( 'admin_footer', array( $this, 'render_governing_site_modal' ) );

		// add admin_body_class class of onedesign-multisite-selection-modal on network admin plugins page.
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );

		// add onedesign_multisite_api_key_generated action to change same key in governing site.
		add_action( 'onedesign_multisite_api_key_generated', array( $this, 'sync_api_key_to_governing_site' ), 10, 2 );

		// auto assign brand-site on new site creation if governing site is set.
		add_action( 'wp_initialize_site', array( $this, 'assign_brand_site_on_new_site_creation' ), 10, 2 );

		// listen to option changes for blogname, siteurl and home to update into governing site table.
		add_action( 'updated_option', array( $this, 'update_site_details_in_governing_site_table' ), 10, 3 );
	}

	/**
	 * Render governing site selection modal.
	 *
	 * @return void
	 */
	public function render_governing_site_modal(): void {

		if ( ! is_network_admin() ) {
			return;
		}

		$current_screen = get_current_screen();

		if ( 'plugins-network' !== $current_screen->id ) {
			return;
		}

		if ( Utils::is_governing_site_selected() ) {
			return;
		}

		?>
		<div class="wrap">
			<div id="onedesign-multisite-selection-modal" class="onedesign-modal"></div>
		</div>
		<?php
	}

	/**
	 * Add admin body class for governing site selection modal.
	 *
	 * @param string $classes Existing admin body classes.
	 * @return string Modified admin body classes.
	 */
	public function add_admin_body_class( string $classes ): string {

		if ( Utils::is_governing_site_selected() ) {
			return $classes;
		}

		$current_screen = get_current_screen();

		if ( is_network_admin() && 'plugins-network' === $current_screen->id ) {
			$classes .= ' onedesign-multisite-selection-modal ';
		}
		return $classes;
	}

	/**
	 * Sync API key to governing site when a new key is generated in any child site.
	 *
	 * @param string $secret_key The generated secret key.
	 * @param int    $blog_id The blog ID where the key is generated.
	 * @return void
	 */
	public function sync_api_key_to_governing_site( string $secret_key, int $blog_id ): void {
		// get the governing site id.
		$governing_site_id = get_site_option( Constants::ONEDESIGN_MULTISITE_GOVERNING_SITE, 0 );

		// go to governing site and update shared_sites option secret_key of blog_id site.
		if ( $governing_site_id && $secret_key ) {
			if ( ! switch_to_blog( (int) $governing_site_id ) ) {
				return;
			}
			$shared_sites = get_option( Constants::ONEDESIGN_SHARED_SITES, array() );
			foreach ( $shared_sites as &$site ) {
				if ( (int) $site['id'] === (int) $blog_id ) {
					$site['api_key'] = $secret_key;
					break;
				}
			}

			update_option( Constants::ONEDESIGN_SHARED_SITES, $shared_sites, false );

			restore_current_blog();
		}
	}

	/**
	 * Assign brand-site on new site creation if governing site is set.
	 *
	 * @param \WP_Site $new_site The new site object.
	 *
	 * @return void
	 */
	public function assign_brand_site_on_new_site_creation( \WP_Site $new_site ): void {

		$governing_site_id = get_site_option( Constants::ONEDESIGN_MULTISITE_GOVERNING_SITE, 0 );

		if ( $governing_site_id && $new_site->blog_id !== $governing_site_id ) {
			if ( ! switch_to_blog( (int) $new_site->blog_id ) ) {
				return;
			}

			update_option( Constants::ONEDESIGN_SITE_TYPE, 'brand-site', false );

			restore_current_blog();
		}
	}

	/**
	 * Update site details in governing site table on option changes.
	 *
	 * @param string $option_name The name of the updated option.
	 * @param mixed  $old_value The old value of the option.
	 * @param mixed  $new_value The new value of the option.
	 *
	 * @return void
	 */
	public function update_site_details_in_governing_site_table( string $option_name, $old_value, $new_value ): void {

		$governing_site_id = get_site_option( Constants::ONEDESIGN_MULTISITE_GOVERNING_SITE, 0 );

		// If governing site is not set or we are on governing site, return.
		if ( ! $governing_site_id || get_current_blog_id() === (int) $governing_site_id ) {
			return;
		}

		$relevant_options = array( 'blogname', 'siteurl', 'home' );
		$current_site_id  = get_current_blog_id();

		if ( in_array( $option_name, $relevant_options, true ) ) {
			if ( ! switch_to_blog( (int) $governing_site_id ) ) {
				return;
			}

			// get shared sites from governing site.
			$shared_sites = get_option( Constants::ONEDESIGN_SHARED_SITES, array() );

			foreach ( $shared_sites as &$site ) {
				if ( (int) $site['id'] === $current_site_id ) {
					if ( 'blogname' === $option_name ) {
						$site['name'] = sanitize_text_field( $new_value );
					} elseif ( in_array( $option_name, array( 'siteurl', 'home' ), true ) ) {
						$site['url'] = esc_url_raw( $new_value );
					}
					break;
				}
			}

			// save the updated shared_sites option.
			update_option( Constants::ONEDESIGN_SHARED_SITES, $shared_sites, false );

			// restore blog.
			restore_current_blog();
		}
	}
}
