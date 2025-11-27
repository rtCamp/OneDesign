<?php
/**
 * Enqueue assets for OneDesign.
 *
 * @package OneDesign
 */

namespace OneDesign\Modules\Core;

use OneDesign\Contracts\Interfaces\Registrable;
use OneDesign\Plugin_Configs\Constants;
use OneDesign\Modules\Post_Types\{ Pattern, Template };
use OneDesign\Utils;

/**
 * Class Assets
 */
class Assets implements Registrable {

	/**
	 * Localized data for scripts.
	 *
	 * @var array
	 */
	private static array $localized_data = [];

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->build_localized_data();
	}

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_scripts' ], 20, 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'add_admin_scripts' ], 20, 1 );
	}

	/**
	 * Prepare localized data.
	 *
	 * @return void
	 */
	private static function build_localized_data(): void {
		self::$localized_data = [
			'restUrl'      => esc_url( home_url( '/wp-json' ) ),
			'restNonce'    => wp_create_nonce( 'wp_rest' ),
			'apiKey'       => get_option( Constants::ONEDESIGN_API_KEY, 'default_api_key' ),
			'settingsLink' => esc_url( admin_url( 'admin.php?page=onedesign-settings' ) ),
		];
	}

	/**
	 * Add admin scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 *
	 * @return void
	 */
	public function add_admin_scripts( $hook_suffix ): void {

		$current_screen = Utils::get_current_screen();

		if ( strpos( $hook_suffix, 'onedesign-settings' ) !== false ) {

			// remove all notices.
			remove_all_actions( 'admin_notices' );

			$this->register_script(
				'onedesign-settings-script',
				'settings.js'
			);

			wp_localize_script(
				'onedesign-settings-script',
				'OneDesignSettings',
				array_merge(
					self::$localized_data,
					[
						'multisites'              => Utils::get_all_multisites_info(),
						'isMultisite'             => Utils::is_multisite(),
						'isGoverningSiteSelected' => Utils::is_governing_site_selected(),
						'currentSiteId'           => Utils::is_multisite() ? get_current_blog_id() : null,
					]
				)
			);

			wp_enqueue_script( 'onedesign-settings-script' );

			// Enqueue the settings page styles.
			$this->register_style( 'onedesign-settings-style', 'settings.css' );
			wp_enqueue_style( 'onedesign-settings-style' );

			// only load media uploader in governing site settings page.
			if ( Utils::is_governing_site() ) {
				wp_enqueue_media();
			}
		}

		if ( strpos( $hook_suffix, 'plugins' ) !== false && empty( Utils::get_current_site_type() ) && ( $current_screen && 'plugins-network' !== $current_screen->id ) ) {

			// remove all notices.
			remove_all_actions( 'admin_notices' );

			$this->register_script(
				'onedesign-setup-script',
				'plugin.js',
			);

			wp_localize_script(
				'onedesign-setup-script',
				'OneDesignSettings',
				self::$localized_data
			);

			wp_enqueue_script( 'onedesign-setup-script' );
		}

		if ( Utils::is_multisite() && 'plugins-network' === $current_screen->id && ! Utils::is_governing_site_selected() ) {

			// remove all notices.
			remove_all_actions( 'admin_notices' );

			$this->register_script(
				'onedesign-multisite-setup-script',
				'multisite-plugin.js',
			);

			wp_localize_script(
				'onedesign-multisite-setup-script',
				'OneDesignMultiSiteSettings',
				array_merge(
					self::$localized_data,
					[
						'multisites' => Utils::get_all_multisites_info(),
					]
				)
			);

			wp_enqueue_script( 'onedesign-multisite-setup-script' );
		}

		$this->register_style( 'onedesign-admin-style', 'admin.css' );
		wp_enqueue_style( 'onedesign-admin-style' );
	}

	/**
	 * Add scripts and styles to the page.
	 *
	 * @return void -- register styles and scripts
	 */
	public function enqueue_scripts(): void {

		$current_screen = Utils::get_current_screen();

		if ( $current_screen && Pattern::SLUG === $current_screen->id ) {
			$this->register_script(
				'onedesign-patterns-library-script',
				'patterns-library.js'
			);

			wp_localize_script(
				'onedesign-patterns-library-script',
				'patternSyncData',
				self::$localized_data
			);

			wp_enqueue_script( 'onedesign-patterns-library-script' );

			$this->register_style( 'onedesign-editor-style', 'editor.css' );
			wp_enqueue_style( 'onedesign-editor-style' );
		}

		if ( Template::SLUG !== $current_screen->id ) {
			return;
		}

		$this->register_script(
			'onedesign-templates-library-script',
			'templates-library.js'
		);

		wp_localize_script(
			'onedesign-templates-library-script',
			'TemplateLibraryData',
			self::$localized_data
		);

		wp_enqueue_script( 'onedesign-templates-library-script' );

		$this->register_style( 'onedesign-template-style', 'template.css' );
		wp_enqueue_style( 'onedesign-template-style' );
	}

	/**
	 * Get asset dependencies and version info from {handle}.asset.php if exists.
	 *
	 * @param string $file File name.
	 * @param array  $deps Script dependencies to merge with.
	 * @param string $ver  Asset version string.
	 *
	 * @return array
	 */
	public function get_asset_meta( $file, $deps = [], $ver = false ): array {
		$asset_meta_file = sprintf( '%s/%s.asset.php', untrailingslashit( ONEDESIGN_BUILD_PATH ), basename( $file, '.' . pathinfo( $file )['extension'] ) );
		$asset_meta      = is_readable( $asset_meta_file )
			? require_once $asset_meta_file
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $file, $ver ),
			];

		$asset_meta['dependencies'] = array_merge( $deps, $asset_meta['dependencies'] );

		return $asset_meta;
	}

	/**
	 * Register a new script.
	 *
	 * @param string           $handle    Name of the script. Should be unique.
	 * @param string|bool      $file       script file, path of the script relative to the assets/build/ directory.
	 * @param array            $deps      Optional. An array of registered script handles this script depends on. Default empty array.
	 * @param string|bool|null $ver       Optional. String specifying script version number, if not set, filetime will be used as version number.
	 * @param bool             $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
	 *                                    Default 'false'.
	 * @return bool Whether the script has been registered. True on success, false on failure.
	 */
	public function register_script( $handle, $file, $deps = [], $ver = false, $in_footer = true ): bool {

		$file_path = sprintf( '%s/%s', ONEDESIGN_BUILD_PATH, $file );

		if ( ! \file_exists( $file_path ) ) {
			return false;
		}

		$src        = sprintf( ONEDESIGN_BUILD_URI . '/%s', $file );
		$asset_meta = $this->get_asset_meta( $file, $deps );

		// register each dependency styles.
		if ( ! empty( $asset_meta['dependencies'] ) ) {
			foreach ( $asset_meta['dependencies'] as $dependency ) {
				wp_enqueue_style( $dependency );
			}
		}

		return wp_register_script( $handle, $src, $asset_meta['dependencies'], $asset_meta['version'], $in_footer );
	}

	/**
	 * Register a CSS stylesheet.
	 *
	 * @param string           $handle Name of the stylesheet. Should be unique.
	 * @param string|bool      $file    style file, path of the script relative to the assets/build/ directory.
	 * @param array            $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string|bool|null $ver    Optional. String specifying script version number, if not set, filetime will be used as version number.
	 * @param string           $media  Optional. The media for which this stylesheet has been defined.
	 *                                 Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media queries like
	 *                                 '(orientation: portrait)' and '(max-width: 640px)'.
	 *
	 * @return bool Whether the style has been registered. True on success, false on failure.
	 */
	public function register_style( $handle, $file, $deps = [], $ver = false, $media = 'all' ): bool {

		$file_path = sprintf( '%s/%s', ONEDESIGN_BUILD_PATH, $file );

		if ( ! \file_exists( $file_path ) ) {
			return false;
		}

		$src     = sprintf( ONEDESIGN_BUILD_URI . '/%s', $file );
		$version = $this->get_file_version( $file, $ver );

		return wp_register_style( $handle, $src, $deps, $version, $media );
	}

	/**
	 * Get file version.
	 *
	 * @param string          $file File path.
	 * @param int|string|bool $ver  File version.
	 *
	 * @return bool|int|string
	 */
	public function get_file_version( $file, $ver = false ): bool|int|string {
		if ( ! empty( $ver ) ) {
			return $ver;
		}

		$file_path = sprintf( '%s/%s', ONEDESIGN_BUILD_PATH, $file );

		return file_exists( $file_path ) ? filemtime( $file_path ) : false;
	}
}
