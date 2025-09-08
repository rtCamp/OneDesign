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
 * Requires PHP: 7.4
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



// if autoload file does not exist then show notice that you are running the plugin from github repo so you need to build assets and install composer dependencies.
if ( ! file_exists( ONEDESIGN_DIR_PATH . '/vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		function () {
			?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: %s is the plugin name. */
					esc_html__( 'You are running the %s plugin from the GitHub repository. Please build the assets and install composer dependencies to use the plugin.', 'onedesign' ),
					'<strong>' . esc_html__( 'OneDesign', 'onedesign' ) . '</strong>'
				);
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: %s is the command to run. */
					esc_html__( 'Run the following commands in the plugin directory: %s', 'onedesign' ),
					'<code>composer install && npm install && npm run build:prod</code>'
				);
				?>
			<p>
				<?php
				printf(
					/* translators: %s is the plugin name. */
					esc_html__( 'Please refer to the %s for more information.', 'onedesign' ),
					sprintf(
						'<a href="%s" target="_blank">%s</a>',
						esc_url( 'https://github.com/rtCamp/OneDesign' ),
						esc_html__( 'OneDesign GitHub repository', 'onedesign' )
					)
				);
				?>
			</p>
		</div>
			<?php
		}
	);
	return;
}

// Load autoloader.
if ( file_exists( ONEDESIGN_DIR_PATH . '/vendor/autoload.php' ) ) {
	require_once ONEDESIGN_DIR_PATH . '/vendor/autoload.php';
}

/**
 * Load the plugin.
 */
function onedesign_plugin_loader() {
	\OneDesign\Plugin::get_instance();

	// load plugin text domain.
	load_plugin_textdomain( 'onedesign', false, ONEDESIGN_RELATIVE_PATH . '/languages/' );
}

add_action( 'plugins_loaded', 'onedesign_plugin_loader' );
