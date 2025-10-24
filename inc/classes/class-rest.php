<?php
/**
 * REST class to call all REST classes.
 *
 * @package OneDesign
 */

namespace OneDesign;

use OneDesign\Traits\Singleton;
use OneDesign\Rest\{ Patterns, Templates, Basic_Options, Multisite };

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

		Basic_Options::get_instance();
		Patterns::get_instance();
		Templates::get_instance();

		// only load multisite REST routes if multisite is enabled.
		if ( Utils::is_multisite() ) {
			Multisite::get_instance();
		}

		// allow cors header for all REST API requests.
		add_filter( 'rest_pre_serve_request', array( $this, 'add_cors_headers' ), PHP_INT_MAX - 30, 4 );
	}

	/**
	 * Add CORS headers to REST API responses.
	 *
	 * @param bool $served Whether the request has been served.
	 * @return bool
	 */
	public function add_cors_headers( $served ): bool {
		header( 'Access-Control-Allow-Headers: X-OneDesign-Token, Content-Type, Authorization', false );
		return $served;
	}
}
