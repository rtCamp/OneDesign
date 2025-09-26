<?php
/**
 * This class will have REST endpoints for templates sharing.
 *
 * @package OneDesign
 */

namespace OneDesign\Rest;

use OneDesign\Traits\Singleton;
use OneDesign\Utils;

/**
 * Class Templates
 */
class Templates {

	/**
	 * REST namespace.
	 */
	const NAMESPACE = 'onedesign/v1/templates';

	/**
	 * use singleton trait.
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		/**
		 * Register route to get all templates.
		 *
		 * @return void
		 */
		register_rest_route(
			self::NAMESPACE,
			'/all',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_all_templates' ),
				'permission_callback' => array( __CLASS__, 'permission_check' ),
			)
		);

		/**
		 * Register a route to get templates from all connected sites.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/connected-sites',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_templates_from_connected_sites' ),
				'permission_callback' => array( __CLASS__, 'permission_check' ),
			)
		);

		/**
		 * Register a route to get shared templates.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/shared',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_shared_templates' ),
					'permission_callback' => array( __CLASS__, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_templates' ),
					'permission_callback' => array( __CLASS__, 'permission_check' ),
					'args'                => array(
						'templates' => array(
							'required' => true,
							'type'     => 'array',
						),
						'patterns'  => array(
							'required' => false,
							'type'     => 'array',
						),
						'template_parts' => array(
							'required' => false,
							'type'     => 'array',
						),
					),
				),
			)
		);

		/**
		 * Register a route to apply templates to brand sites.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/apply',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'apply_templates_to_sites' ),
				'permission_callback' => array( __CLASS__, 'permission_check' ),
				'args'                => array(
					'sites'     => array(
						'required' => true,
						'type'     => 'array',
					),
					'templates' => array(
						'required' => true,
						'type'     => 'array',
					),
				),
			)
		);

		/**
		 * Register a route to remove template from shared site.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/remove',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'remove_template' ),
				'permission_callback' => array( __CLASS__, 'permission_check' ),
				'args'                => array(
					'template_ids' => array(
						'required' => true,
						'type'     => 'array',
					),
					'site'         => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);

		/**
		 * Register a route to remove template from shared site.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/remove-site-templates',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'remove_template_from_brand_site' ),
				'permission_callback' => array( __CLASS__, 'permission_check' ),
				'args'                => array(
					'template_ids' => array(
						'required' => true,
						'type'     => 'array',
					),
				),
			)
		);
	}

	public static function permission_check() {
		return true;
		// return current_user_can( 'manage_options' );
	}

	public function remove_template_from_brand_site( \WP_REST_Request $request ): \WP_REST_Response {
		$template_ids = $request->get_param( 'template_ids' );

		if ( empty( $template_ids ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Template IDs parameter is required.',
				),
				400
			);
		}

		$existing_templates = get_option( 'onedesign_shared_templates', array() );
		if ( ! is_array( $existing_templates ) ) {
			$existing_templates = array();
		}

		// Remove templates based on 'id'.
		$updated_templates = array_filter( $existing_templates, fn( $t ) => ! in_array( $t['id'], $template_ids ) );

		update_option( 'onedesign_shared_templates', array_values( $updated_templates ) );

		return new \WP_REST_Response(
			array(
				'success'   => true,
				'message'   => __( 'Templates removed successfully.', 'onedesign' ),
				'templates' => array_values( $updated_templates ),
			),
			200
		);
	}


	public function remove_template( \WP_REST_Request $request ): \WP_REST_Response {
		$template_ids = $request->get_param( 'template_ids' );
		$site         = $request->get_param( 'site' );

		if ( empty( $template_ids ) || empty( $site ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Template IDs and site parameters are required.',
				),
				400
			);
		}

		$error_log     = array();
		$response_data = array();

		// get site info from child sites option.
		$site_info = Utils::get_site_by_id( $site );
		if ( ! $site_info ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Site not found.', 'onedesign' ),
				),
				404
			);
		}

		$request_url = esc_url_raw( trailingslashit( $site_info['url'] ) ) . '/wp-json/' . self::NAMESPACE . '/remove-site-templates';
		$api_key     = $site_info['api_key'] ?? '';

		$response = wp_safe_remote_request(
			$request_url,
			array(
				'headers' => array(
					'X-OneDesign-API-Key' => 'Bearer ' . $api_key,
					'Content-Type'        => 'application/json',
				),
				'method'  => 'DELETE',
				'body'    => wp_json_encode(
					array(
						'template_ids' => $template_ids,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_log[ $request_url ] = $response->get_error_message();
		} else {
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( $response_code === 200 ) {
				$body = wp_remote_retrieve_body( $response );
				$data = json_decode( $body, true );
				if ( isset( $data['success'] ) && $data['success'] ) {
					$response_data = $data;
				} else {
					$error_log[ $request_url ] = $response;
				}
			} else {
				$error_log[ $request_url ] = $response;
			}
		}

		return new \WP_REST_Response(
			array(
				'success'      => count( $error_log ) === 0,
				'message'      => __( 'Templates removed successfully from the site.', 'onedesign' ),
				'template_ids' => $template_ids,
				'site'         => $site,
				'response'     => $response_data,
				'errors'       => $error_log,
			),
			200
		);
	}

	public function create_templates( \WP_REST_Request $request ): \WP_REST_Response {
		$templates = $request->get_param( 'templates' );
		$patterns = $request->get_param( 'patterns' );
		$template_parts = $request->get_param( 'template_parts' );
		
		if( empty( $templates ) || ! is_array( $templates ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __('Templates parameter is required and should be an array.', 'onedesign' ),
				),
				400
			);
		}

		$existing_templates = get_option( 'onedesign_shared_templates', array() );
		if ( ! is_array( $existing_templates ) ) {
			$existing_templates = array();
		}

		// Merge new templates with existing ones, avoiding duplicates based on 'id'.
		foreach ( $templates as $template ) {
			if ( isset( $template['id'] ) && ! array_filter( $existing_templates, fn( $t ) => $t['id'] === $template['id'] ) ) {
				$existing_templates[] = $template;
			}
		}

		update_option( 'onedesign_shared_templates', $existing_templates );


		// get existing patterns.
		$existing_patterns = get_option( 'onedesign_shared_patterns', array() );
		if ( ! is_array( $existing_patterns ) ) {
			$existing_patterns = array();
		}

		// Merge new patterns with existing ones, avoiding duplicates based on 'id'.
		if( is_array( $patterns ) ) {
			foreach ( $patterns as $pattern ) {
				if ( isset( $pattern['name'] ) && ! array_filter( $existing_patterns, fn( $t ) => $t['name'] === $pattern['name'] ) ) {
					$existing_patterns[] = $pattern;
				}
			}
		}

		update_option( 'onedesign_shared_patterns', $existing_patterns );

		// get existing template parts.
		$existing_template_parts = get_option( 'onedesign_shared_template_parts', array() );
		if ( ! is_array( $existing_template_parts ) ) {
			$existing_template_parts = array();
		}

		// Merge new template parts with existing ones, avoiding duplicates based on 'id'.
		if( is_array( $template_parts ) ) {
			foreach ( $template_parts as $template_part ) {
				if ( isset( $template_part['id'] ) && ! array_filter( $existing_template_parts, fn( $t ) => $t['id'] === $template_part['id'] ) ) {
					$existing_template_parts[] = $template_part;
				}
			}
		}

		update_option( 'onedesign_shared_template_parts', $existing_template_parts );


		return new \WP_REST_Response(
			array(
				'success'   => true,
				'message'   => __( 'Templates saved successfully.', 'onedesign' ),
				'templates' => $existing_templates,
				'patterns'  => $existing_patterns,
				'template_parts' => $existing_template_parts,
				// 'invalid_templates' => array_map(fn($t) => $t['id'], $invalid_templates),
				// 'valid_templates' => array_map(fn($t) => $t['id'], $valid_templates),
			),
			200
		);
	}

	public function apply_templates_to_sites( \WP_REST_Request $request ): \WP_REST_Response {
		$sites     = $request->get_param( 'sites' );
		$templates = $request->get_param( 'templates' );

		if ( empty( $sites ) || empty( $templates ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Sites and templates parameters are required.',
				),
				400
			);
		}

		$all_templates    = get_block_templates();
		$shared_templates = array();
		foreach ( $all_templates as $template ) {
			$template = (array) $template;
			if ( in_array( $template['id'], $templates, true ) ) {
				$shared_templates[] = $template;
			}
		}

		// process templates to have all info about its template parts/patterns.
		$parsed_templates = array();
		$already_tracked  = array();
		foreach( $shared_templates as $template ) {
			$parsed_templates = array_merge($parsed_templates, onedesign_parse_block_template( $template['content'], $already_tracked ));
		}

		// print_r($parsed_templates);

		$template_parts = array_filter( $parsed_templates, fn( $t ) => $t['type'] === 'template-part' );
		$patterns       = array_filter( $parsed_templates, fn( $t ) => $t['type'] === 'pattern' );

		// print_r($shared_templates);
		// print_r($template_parts);
		// print_r($patterns);

		// get site info from child sites option.
		$brand_sites = Utils::get_sites_info();

		$error_log     = array();
		$response_data = array();

		foreach ( $brand_sites as $site ) {
			$site_url     = esc_url_raw( trailingslashit( $site['url'] ) );
			$site_api_key = $site['api_key'];
			$site_id      = $site['id'];
			if ( in_array( $site_id, $sites, true ) ) {
				$request_url = $site_url . 'wp-json/' . self::NAMESPACE . '/shared';
				$new_templates = Utils::modify_template_template_part_pattern_slug($shared_templates, $site['name']);
				$new_patterns = Utils::modify_template_template_part_pattern_slug($patterns, $site['name']);
				$new_template_parts = Utils::modify_template_template_part_pattern_slug($template_parts, $site['name']);
				$response    = wp_safe_remote_post(
					$request_url,
					array(
						'headers' => array(
							'X-OneDesign-API-Key' => 'Bearer ' . $site_api_key,
							'Content-Type'        => 'application/json',
						),
						'body'    => wp_json_encode(
							array(
								'templates' => $new_templates,
								'patterns'  => $new_patterns,
								'template_parts' => $new_template_parts,
							)
						),
					)
				);
				if ( is_wp_error( $response ) ) {
					$error_log[ $site_url ] = $response->get_error_message();
				} else {
					$response_code = wp_remote_retrieve_response_code( $response );
					if ( $response_code === 200 ) {
						$body = wp_remote_retrieve_body( $response );
						$data = json_decode( $body, true );
						if ( isset( $data['success'] ) && $data['success'] ) {
							$response_data[ $site_url ] = $data;
						} else {
							$error_log[ $site_url ] = $response;
						}
					} else {
						$error_log[ $site_url ] = $response;
					}
				}
				$response_data[ $site_url ] = $response;
			}
		}

		return new \WP_REST_Response(
			array(
				'success'          => count( $error_log ) === 0,
				'message'          => __( 'Templates applied successfully to the selected sites.', 'onedesign' ),
				'sites'            => $sites,
				'templates'        => $templates,
				'shared_templates' => $shared_templates,
				'responses'        => $response_data,
				'errors'           => $error_log,
				'template_parts' => $new_template_parts,
				'patterns' => $new_patterns,
				// 'parsed_template' => $parsed_templates,
			),
			200
		);
	}

	public function get_all_templates(): \WP_REST_Response {
		$templates = get_block_templates();
		return new \WP_REST_Response(
			array(
				'success'   => true,
				'templates' => $templates,
			),
			200
		);
	}

	public function get_templates_from_connected_sites(): \WP_REST_Response {
		$connected_sites = get_option( 'onedesign_child_sites', array() );
		$sites_response  = array();
		$error_log       = array();
		foreach ( $connected_sites as $site ) {
			$request_url = esc_url_raw( trailingslashit( $site['url'] ) ) . '/wp-json/' . self::NAMESPACE . '/shared';
			$api_key     = $site['api_key'];
			$response    = wp_safe_remote_get(
				$request_url,
				array(
					'headers' => array(
						'X-OneDesign-API-Key' => 'Bearer ' . $api_key,
						'Content-Type'        => 'application/json',
					),
					'timeout' => 15,
				)
			);
			if ( is_wp_error( $response ) ) {
				$error_log[ $site['id'] ] = $response->get_error_message();
			} else {
				$response_code = wp_remote_retrieve_response_code( $response );
				if ( $response_code === 200 ) {
					$body = wp_remote_retrieve_body( $response );
					$data = json_decode( $body, true );
					if ( isset( $data['success'] ) && $data['success'] && isset( $data['templates'] ) ) {
						$sites_response[ $site['id'] ] = $data['templates'];
					} else {
						$error_log[ $site['id'] ] = $response;
					}
				} else {
					$error_log[ $site['id'] ] = $response;
				}
			}
		}
		return new \WP_REST_Response(
			array(
				'success'   => true,
				'templates' => $sites_response,
				'errors'    => $error_log,
			),
			200
		);
	}

	public function get_shared_templates(): \WP_REST_Response {
		$shared_templates = get_option( 'onedesign_shared_templates', array() );
		return new \WP_REST_Response(
			array(
				'success'   => true,
				'templates' => $shared_templates,
			),
			200
		);
	}
}
