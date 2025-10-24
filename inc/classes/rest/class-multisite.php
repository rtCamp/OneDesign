<?php
/**
 * This file will contain routes for OneDesign Multisite handling.
 *
 * @package OneDesign
 */

namespace OneDesign\Rest;

use OneDesign\Plugin_Configs\Constants;
use OneDesign\Traits\Singleton;
use OneDesign\Utils;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Multisite
 */
class Multisite {

	/**
	 * Use Singleton trait.
	 */
	use Singleton;

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = Utils::NAMESPACE . '/multisite';

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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		/**
		 * Register a route to store governing site in multisite setup.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/governing-site',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_multisite_governing_site' ),
					'permission_callback' => array( Basic_Options::class, 'permission_callback' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'set_multisite_governing_site' ),
					'permission_callback' => array( Basic_Options::class, 'permission_callback' ),
					'args'                => array(
						'governing_site_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		/**
		 * Register a route to add-sites.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/add-sites',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_multisite_sites' ),
					'permission_callback' => array( Basic_Options::class, 'permission_callback' ),
					'args'                => array(
						'site_ids' => array(
							'required' => true,
							'type'     => 'array',
						),
					),
				),
			)
		);

		/**
		 * Register a route to get all sites from current multisite setup.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/sites',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_all_multisite_sites' ),
					'permission_callback' => array( Basic_Options::class, 'permission_callback' ),
				),
			)
		);
	}

	/**
	 * Get the governing site for multisite setup.
	 *
	 * @return WP_REST_Response
	 */
	public function get_multisite_governing_site(): WP_REST_Response {

		// get site wide option of onedesign_multisite_governing_site.
		$governing_site = get_site_option( Constants::ONEDESIGN_MULTISITE_GOVERNING_SITE, '' );

		return new WP_REST_Response(
			array(
				'success'        => true,
				'governing_site' => $governing_site,
			)
		);
	}

	/**
	 * Set the governing site for multisite setup.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function set_multisite_governing_site( \WP_REST_Request $request ): WP_REST_Response|WP_Error {

		$governing_site_id = filter_var( $request->get_param( 'governing_site_id' ), FILTER_SANITIZE_NUMBER_INT );

		if ( empty( $governing_site_id ) || ! is_numeric( $governing_site_id ) ) {
			return new WP_Error(
				'invalid_governing_site_id',
				__( 'Invalid site info provided.', 'onedesign' ),
				array( 'status' => 400 )
			);
		}

		// update site wide option of onedesign_multisite_governing_site.
		$is_updated = update_site_option( Constants::ONEDESIGN_MULTISITE_GOVERNING_SITE, $governing_site_id );

		if ( ! $is_updated ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update governing site.', 'onedesign' ),
				array( 'status' => 500 )
			);
		}

		// set all existing sites site-type as brand-site and current site as governing-site.
		$multisite_info = Utils::get_all_multisites_info();
		foreach ( $multisite_info as $site ) {
			switch_to_blog( $site['id'] );
			if ( intval( $site['id'] ) === intval( $governing_site_id ) ) {
				update_option( Constants::ONEDESIGN_SITE_TYPE, 'governing-site', false );
				delete_option( Constants::ONEDESIGN_GOVERNING_SITE_URL );
				delete_option( Constants::ONEDESIGN_SHARED_SITES );
			} else {
				update_option( Constants::ONEDESIGN_SITE_TYPE, 'brand-site', false );
				delete_option( Constants::ONEDESIGN_GOVERNING_SITE_URL );
				delete_option( Constants::ONEDESIGN_SHARED_SITES );
			}
			restore_current_blog();
		}

		return new WP_REST_Response(
			array(
				'success'        => true,
				'governing_site' => $governing_site_id,
			)
		);
	}

	/**
	 * Add sites to multisite setup.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_multisite_sites( \WP_REST_Request $request ): WP_REST_Response|WP_Error {

		$site_ids = array_map( 'absint', (array) $request->get_param( 'site_ids' ) );

		if ( empty( $site_ids ) || ! is_array( $site_ids ) ) {
			return new WP_Error(
				'invalid_site_ids',
				__( 'Invalid site info provided.', 'onedesign' ),
				array( 'status' => 400 )
			);
		}

		// get governing site id.
		$governing_site_id = get_site_option( Constants::ONEDESIGN_MULTISITE_GOVERNING_SITE, 0 );

		if ( ! $governing_site_id ) {
			return new WP_Error(
				'no_governing_site',
				__( 'No governing site set. Please set a governing site first.', 'onedesign' ),
				array( 'status' => 400 )
			);
		}

		$governing_site_url = get_blog_details( $governing_site_id )->siteurl;

		$shared_sites = get_option( Constants::ONEDESIGN_SHARED_SITES, array() );

		foreach ( $site_ids as $site_id ) {

			// switch to each site and update option of onedesign_site_type as brand-site.
			switch_to_blog( $site_id );

			$shared_sites[] = array(
				'id'          => $site_id,
				'name'        => get_bloginfo( 'name' ),
				'url'         => get_bloginfo( 'url' ),
				'api_key'     => get_option( Constants::ONEDESIGN_API_KEY, '' ),
				'is_editable' => false,
			);

			update_option( Constants::ONEDESIGN_SITE_TYPE, 'brand-site', false );
			update_option( Constants::ONEDESIGN_GOVERNING_SITE_URL, $governing_site_url, false );

			// remove shared sites options if exists to avoid conflicts.
			delete_option( Constants::ONEDESIGN_SHARED_SITES );

			restore_current_blog();
		}

		// update shared sites in governing site.
		switch_to_blog( $governing_site_id );
		update_option( Constants::ONEDESIGN_SHARED_SITES, $shared_sites, false );
		restore_current_blog();

		return new WP_REST_Response(
			array(
				'success'     => true,
				'added_sites' => $site_ids,
			)
		);
	}

	/**
	 * Get all sites from current multisite setup.
	 *
	 * @return WP_REST_Response
	 */
	public function get_all_multisite_sites(): WP_REST_Response {
		$all_multisites = Utils::get_all_multisites_info();
		return new WP_REST_Response(
			array(
				'success' => true,
				'sites'   => $all_multisites,
			)
		);
	}
}
