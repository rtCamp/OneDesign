<?php
/**
 * REST class to call all REST classes.
 *
 * @package OneDesign
 */

namespace OneDesign;

use OneDesign\Traits\Singleton;
use OneDesign\Rest\{Patterns, Templates};

/**
 * Class Rest
 */
class Rest {

	/**
	 * Use Singleton trait.
	 */
	use Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Setup hooks.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		Patterns::get_instance();
		Templates::get_instance();
	}
}
