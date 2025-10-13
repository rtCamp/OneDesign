<?php
/**
 * Plugin main class.
 *
 * @package OneDesign
 */

namespace OneDesign;

use OneDesign\Plugin_Configs\{ Secret_Key, Constants };
use OneDesign\Post_Types\{ Template, Design_Library, Meta };
use OneDesign\Traits\Singleton;

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

		// load plugin classes.
		$this->load_classes();

		// load post types.
		$this->load_post_types();

		// Load configs.
		$this->load_configs();
	}

	/**
	 * Load all necessary classes for the plugin.
	 *
	 * @return void
	 */
	private function load_classes(): void {
		Assets::get_instance();
		Rest::get_instance();
		Hooks::get_instance();
		CPT_Restriction::get_instance();
		Settings::get_instance();
	}

	/**
	 * Load all custom post types.
	 *
	 * @return void
	 */
	private function load_post_types(): void {
		Design_Library::get_instance();
		Template::get_instance();
		Meta::get_instance();
	}

	/**
	 * Load all configuration classes.
	 *
	 * @return void
	 */
	private function load_configs(): void {
		Secret_Key::get_instance();
		Constants::get_instance();
	}
}
