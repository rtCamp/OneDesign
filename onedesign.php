<?php
/**
 * Plugin Name: OneDesign
 * Plugin URI: https://github.com/rtCamp/OneDesign/
 * Description: Sync patterns across multiple WordPress sites and manage them from a single dashboard.
 * Version: 1.0.0
 * Author: rtCamp
 * Author URI: https://rtcamp.com
 * Text Domain: onedesign
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Tested up to: 6.8
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package OneDesign
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants.
define( 'ONEDESIGN_VERSION', '1.0.0' );
define( 'ONEDESIGN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'ONEDESIGN_RELATIVE_PATH', dirname( plugin_basename( __FILE__ ) ) );
define( 'ONEDESIGN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'ONEDESIGN_BUILD_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/build/' );
define( 'ONEDESIGN_BUILD_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) . 'assets/build/' );
define( 'ONEDESIGN_BUILD_JS_URI', trailingslashit( ONEDESIGN_BUILD_URI ) . 'js/' );
define( 'ONEDESIGN_BUILD_JS_DIR_PATH', trailingslashit( ONEDESIGN_BUILD_PATH ) . 'js/' );
define( 'ONEDESIGN_BUILD_CSS_URI', trailingslashit( ONEDESIGN_BUILD_URI ) . 'css/' );
define( 'ONEDESIGN_BUILD_CSS_DIR_PATH', trailingslashit( ONEDESIGN_BUILD_PATH ) . 'css/' );
define( 'ONEDESIGN_PLUGIN_LOADER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'ONEDESIGN_PLUGIN_TEMPLATES_PATH', ONEDESIGN_DIR_PATH . '/inc/templates' );

/**
 * Load the plugin files.
 *
 * @return void
 */
function onedesign_plugin_loader(): void {

	if ( ! file_exists( ONEDESIGN_DIR_PATH . '/vendor/autoload.php' ) ) {

		// load template-functions file to use onedesign_get_template_content function.
		require_once ONEDESIGN_DIR_PATH . '/inc/helpers/template-functions.php';

		echo onedesign_get_template_content( 'notices/no-assets' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- we are escaping the output in the template file.
		return;
	}

	// load vendor/autoload.php file.
	require_once ONEDESIGN_DIR_PATH . '/vendor/autoload.php';

	// initialize the main plugin class.
	\OneDesign\Plugin::get_instance();

	// load plugin text domain.
	load_plugin_textdomain( 'onedesign', false, ONEDESIGN_RELATIVE_PATH . '/languages/' );
}

add_action( 'plugins_loaded', 'onedesign_plugin_loader' );
