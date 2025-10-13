<?php
/**
 * This is routes for Settings options.
 *
 * @package OneDesign
 */

namespace OneDesign\Rest;

use OneDesign\Plugin_Configs\{ Constants, Secret_Key };
use OneDesign\Traits\Singleton;
use WP_REST_Server;
use WP_REST_Response;
use WP_REST_Request;
use WP_Error;

/**
 * Class Basic_Options
 */
class Basic_Options {

	/**
	 * Use Singleton trait.
	 */
	use Singleton;

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'onedesign/v1';

	/**
	 * Protected class constructor
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Function to setup hooks.
	 */
	public function setup_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		/**
		 * Register a route to get site type and set site type.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/site-type',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_site_type' ),
					'permission_callback' => array( self::class, 'permission_callback' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'set_site_type' ),
					'permission_callback' => array( self::class, 'permission_callback' ),
					'args'                => array(
						'site_type' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		/**
		 * Register a route which will store array of sites data like site name, site url, its GitHub repo and api key.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/shared-sites',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_shared_sites' ),
					'permission_callback' => array( self::class, 'permission_callback' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'set_shared_sites' ),
					'permission_callback' => array( self::class, 'permission_callback' ),
					'args'                => array(
						'sites_data' => array(
							'required'          => true,
							'type'              => 'array',
							'sanitize_callback' => function ( $value ) {
								return is_array( $value );
							},
						),
					),
				),
			)
		);

		/**
		 * Register a route for health-check.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/health-check',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'health_check' ),
				'permission_callback' => 'onedesign_validate_api_key_health_check',
			)
		);

		/**
		 * Register a route to get api key option.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/secret-key',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( Secret_Key::class, 'get_secret_key' ),
					'permission_callback' => array( self::class, 'permission_callback' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( Secret_Key::class, 'regenerate_secret_key' ),
					'permission_callback' => array( self::class, 'permission_callback' ),
				),
			)
		);

		/**
		 * Register a route to manage governing site.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/governing-site',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_governing_site' ),
					'permission_callback' => array( self::class, 'permission_callback' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'remove_governing_site' ),
					'permission_callback' => array( self::class, 'permission_callback' ),
				),
			),
		);
	}

	/**
	 * Permission callback to check if the user has manage_options capability.
	 *
	 * @return bool
	 */
	public static function permission_callback(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get the site type.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_site_type(): WP_REST_Response|WP_Error {

		$site_type = get_option( Constants::ONEDESIGN_SITE_TYPE, '' );

		return rest_ensure_response(
			array(
				'success'   => true,
				'site_type' => $site_type,
			)
		);
	}

	/**
	 * Set the site type.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function set_site_type( WP_REST_Request $request ): WP_REST_Response|WP_Error {

		$site_type = sanitize_text_field( $request->get_param( 'site_type' ) );

		update_option( Constants::ONEDESIGN_SITE_TYPE, $site_type, false );

		return rest_ensure_response(
			array(
				'success'   => true,
				'site_type' => $site_type,
			)
		);
	}

	/**
	 * Get shared sites data.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_shared_sites(): WP_REST_Response|WP_Error {
		$shared_sites = get_option( Constants::ONEDESIGN_SHARED_SITES, array() );
		return rest_ensure_response(
			array(
				'success'      => true,
				'shared_sites' => $shared_sites,
			)
		);
	}

	/**
	 * Set shared sites data.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function set_shared_sites( WP_REST_Request $request ): WP_REST_Response|WP_Error {

		$body         = $request->get_body();
		$decoded_body = json_decode( $body, true );
		$sites_data   = $decoded_body['sites_data'] ?? array();

		// check if same url exists more than once or not.
		$urls = array();
		foreach ( $sites_data as $site ) {
			if ( isset( $site['siteUrl'] ) && in_array( $site['siteUrl'], $urls, true ) ) {
				return new WP_Error( 'duplicate_site_url', __( 'Brand Site already exists.', 'onedesign' ), array( 'status' => 400 ) );
			}
			$urls[] = $site['siteUrl'] ?? '';
		}

		// add unique id to each site if not exists.
		foreach ( $sites_data as &$site ) {
			if ( ! isset( $site['id'] ) || empty( $site['id'] ) ) {
				$site['id'] = wp_generate_uuid4();
			}
		}

		update_option( Constants::ONEDESIGN_SHARED_SITES, $sites_data, false );

		return rest_ensure_response(
			array(
				'success'    => true,
				'sites_data' => $sites_data,
			)
		);
	}

	/**
	 * Health check endpoint.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function health_check(): WP_REST_Response|WP_Error {

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Health check passed successfully.', 'onedesign' ),
			)
		);
	}

		/**
		 * Get governing site url.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
	public function get_governing_site(): WP_REST_Response|WP_Error {
		$governing_site_url = get_option( Constants::ONEDESIGN_GOVERNING_SITE_URL, '' );

		return rest_ensure_response(
			array(
				'success'            => true,
				'governing_site_url' => $governing_site_url,
			)
		);
	}

	/**
	 * Remove governing site url.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function remove_governing_site(): WP_REST_Response|WP_Error {
		update_option( Constants::ONEDESIGN_GOVERNING_SITE_URL, '', false );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Governing site removed successfully.', 'onedesign' ),
			)
		);
	}
}
