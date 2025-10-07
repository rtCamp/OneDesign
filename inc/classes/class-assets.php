<?php
/**
 * Enqueue assets for OneDesign.
 *
 * @package OneDesign
 */

namespace OneDesign;

use OneDesign\Post_Types\{ Design_Library, Template };
use OneDesign\Traits\Singleton;

/**
 * Class Assets
 */
class Assets {

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
	 * Setup WordPress hooks
	 */
	public function setup_hooks(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_scripts' ), 20, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ), 20, 1 );
	}

	/**
	 * Add admin scripts.
	 *
	 * @return void
	 */
	public function add_admin_scripts(): void {
		$this->register_style( 'onedesign-admin-style', 'css/admin.css' );
		wp_enqueue_style( 'onedesign-admin-style' );
	}

	/**
	 * Add scripts and styles to the page.
	 *
	 * @return void -- register styles and scripts
	 */
	public function enqueue_scripts(): void {

		$current_screen = get_current_screen();

		if ( Design_Library::SLUG === $current_screen->id ) {

			$this->register_script( 'onedesign-editor-script', 'js/editor.js' );
			wp_localize_script(
				'onedesign-editor-script',
				'patternSyncData',
				array(
					'ajaxurl'  => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'onedesign_nonce' ),
					'siteUrl'  => home_url(),
					'adminUrl' => admin_url(),
				)
			);
			wp_enqueue_script( 'onedesign-editor-script' );

			$this->register_style( 'onedesign-editor-style', 'css/editor.css' );
			wp_enqueue_style( 'onedesign-editor-style' );
		}

		if ( Template::SLUG === $current_screen->id ) {
			$this->register_script(
				'onedesign-templates-library-script',
				'js/templates-library.js'
			);
			wp_localize_script(
				'onedesign-templates-library-script',
				'TemplateLibraryData',
				array(
					'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
					'nonce'        => wp_create_nonce( 'wp_rest' ),
					'siteUrl'      => home_url(),
					'adminUrl'     => admin_url(),
					'restUrl'      => esc_url( rest_url( 'onedesign/v1' ) ),
					'settingsLink' => esc_url( admin_url( 'admin.php?page=onedesign-settings' ) ),
				)
			);
			wp_enqueue_script( 'onedesign-templates-library-script' );

			$this->register_style( 'onedesign-template-style', 'css/template.css' );
			wp_enqueue_style( 'onedesign-template-style' );
		}
	}

	/**
	 * Get asset dependencies and version info from {handle}.asset.php if exists.
	 *
	 * @param string      $file File name.
	 * @param array       $deps Script dependencies to merge with.
	 * @param bool|string $ver  Asset version string.
	 *
	 * @return array
	 */
	public function get_asset_meta( string $file, array $deps = array(), bool|string $ver = false ): array {
		$asset_meta_file = sprintf( '%s/js/%s.asset.php', untrailingslashit( ONEDESIGN_DIR_PATH . '/assets/build' ), basename( $file, '.' . pathinfo( $file )['extension'] ) );
		$asset_meta      = is_readable( $asset_meta_file )
			? require $asset_meta_file
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $file, $ver ),
			);

		$asset_meta['dependencies'] = array_merge( $deps, $asset_meta['dependencies'] );

		return $asset_meta;
	}

	/**
	 * Register a new script.
	 *
	 * @param string           $handle    Name of the script. Should be unique.
	 * @param bool|string      $file       script file, path of the script relative to the assets/build/ directory.
	 * @param array            $deps      Optional. An array of the registered script handles on which this script depends on. Default empty array.
	 * @param bool|string|null $ver       Optional. String specifying script version number, if not set, filetime will be used as a version number.
	 * @param bool             $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
	 *                                    Default 'false'.
	 *
	 * @return bool Whether the script has been registered. True on success, false on failure.
	 */
	public function register_script( string $handle, bool|string $file, array $deps = array(), bool|string|null $ver = false, bool $in_footer = true ): bool {

		$file_path   = sprintf( '%s/%s', ONEDESIGN_DIR_PATH . '/assets/build', $file );
		$file_exists = $this->file_exists( $file_path );

		if ( ! $file_exists ) {
			return false;
		}

		$src        = sprintf( ONEDESIGN_DIR_URL . '/assets/build/%s', $file );
		$asset_meta = $this->get_asset_meta( $file, $deps );

		return wp_register_script( $handle, $src, $asset_meta['dependencies'], $asset_meta['version'], $in_footer );
	}

	/**
	 * Register a CSS stylesheet.
	 *
	 * @param string           $handle Name of the stylesheet. Should be unique.
	 * @param bool|string      $file    style file, path of the script relative to the assets/build/ directory.
	 * @param array            $deps   Optional. An array of the registered stylesheet handles on which this stylesheet depends on. Default empty array.
	 * @param bool|string|null $ver    Optional. String specifying script version number, if not set, filetime will be used as a version number.
	 * @param string           $media  Optional. The media for which this stylesheet has been defined.
	 *                                 Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media queries like
	 *                                 '(orientation: portrait)' and '(max-width: 640px)'.
	 *
	 * @return bool Whether the style has been registered. True on success, false on failure.
	 */
	public function register_style( string $handle, bool|string $file, array $deps = array(), bool|string|null $ver = false, string $media = 'all' ): bool {

		$file_path   = sprintf( '%s/%s', ONEDESIGN_DIR_PATH . '/assets/build', $file );
		$file_exists = $this->file_exists( $file_path );

		if ( ! $file_exists ) {
			return false;
		}

		$src     = sprintf( ONEDESIGN_DIR_URL . '/assets/build/%s', $file );
		$version = $this->get_file_version( $file, $ver );

		return wp_register_style( $handle, $src, $deps, $version, $media );
	}

	/**
	 * Get the file version.
	 *
	 * @param string             $file File path.
	 * @param boolean|int|string $ver File version.
	 *
	 * @return bool|int|string
	 */
	public function get_file_version( string $file, bool|int|string $ver = false ): bool|int|string {
		if ( ! empty( $ver ) ) {
			return $ver;
		}

		$file_path   = sprintf( '%s/%s', ONEDESIGN_DIR_PATH . '/assets/build', $file );
		$file_exists = $this->file_exists( $file_path );

		return $file_exists ? filemtime( $file_path ) : false;
	}

	/**
	 * Check if the file exists in the given path.
	 *
	 * @param string $file_path The path to the file to check.
	 *
	 * @return bool True if the file exists, false otherwise.
	 */
	private function file_exists( string $file_path ): bool {
		$file_exists = wp_cache_get( $file_path, 'onedesign_file_exists' );

		if ( false === $file_exists ) {
			$file_exists = \file_exists( $file_path );
			wp_cache_set( $file_path, $file_exists, 'onedesign_file_exists', 3600 ); // Cache for 1 hour.
		}

		return $file_exists;
	}
}
