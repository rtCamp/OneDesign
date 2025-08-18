<?php
/**
 * Plugin main class.
 *
 * @package OneDesign
 */

namespace OneDesign;

use OneDesign\Traits\Singleton;
use OneDesign\Post_Types\Design_Library;
use OneDesign\Post_Types\Meta;

/**
 * Class Plugin
 */
class Plugin {

	use Singleton;

	/**
	 * Construct method.
	 */
	protected function __construct() {
		$this->setup();
	}

	/**
	 * Setup hooks for the plugin.
	 */
	private function setup(): void {
		// Load plugin classes.
		Assets::get_instance();
		Rest::get_instance();
		Hooks::get_instance();
		CPT_Restriction::get_instance();
		Settings::get_instance();
		Design_Library::get_instance();
		Meta::get_instance();
	}
}
