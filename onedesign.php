<?php
/**
 * Plugin Name: OneDesign
 * Description: Sync patterns across multiple WordPress sites and manage them from a single dashboard.
 * Version: 1.0.0
 * Author: rtCamp
 * Author URI: https://rtcamp.com
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: onedesign
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
define( 'ONEDESIGN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'ONEDESIGN_BUILD_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/build/' );
define( 'ONEDESIGN_BUILD_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) . 'assets/build/' );
define( 'ONEDESIGN_BUILD_JS_URI', trailingslashit( ONEDESIGN_BUILD_URI ) . 'js/' );
define( 'ONEDESIGN_BUILD_JS_DIR_PATH', trailingslashit( ONEDESIGN_BUILD_PATH ) . 'js/' );
define( 'ONEDESIGN_BUILD_CSS_URI', trailingslashit( ONEDESIGN_BUILD_URI ) . 'css/' );
define( 'ONEDESIGN_BUILD_CSS_DIR_PATH', trailingslashit( ONEDESIGN_BUILD_PATH ) . 'css/' );

// Load autoloader.
if ( file_exists( ONEDESIGN_DIR_PATH . '/vendor/autoload.php' ) ) {
	require_once ONEDESIGN_DIR_PATH . '/vendor/autoload.php';
}

// Initialize the plugin.
\OneDesign\Plugin::get_instance();
