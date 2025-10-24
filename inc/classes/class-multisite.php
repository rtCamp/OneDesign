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

		// assign secret_key is the new api key.
		$api_key = $secret_key;

		// go to governing site and update shared_sites option api_key of blog_id site.
		if ( $governing_site_id && $api_key ) {
			switch_to_blog( $governing_site_id );
			$shared_sites = get_option( Constants::ONEDESIGN_SHARED_SITES, array() );
			foreach ( $shared_sites as &$site ) {
				if ( (int) $site['id'] === (int) $blog_id ) {
					$site['api_key'] = $api_key;
					break;
				}
			}
			update_option( Constants::ONEDESIGN_SHARED_SITES, $shared_sites, false );
			restore_current_blog();
		}
	}
}