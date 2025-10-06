<?php
/**
 * This class will have REST endpoints for templates sharing.
 *
 * @package OneDesign
 */

namespace OneDesign\Rest;

use OneDesign\Plugin_Configs\Constants;
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
	 * Use singleton trait.
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
	protected function setup_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {

		$patterns_instance = Patterns::get_instance();

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
				'permission_callback' => array( $patterns_instance, 'manage_options_permission_check' ),
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
				'permission_callback' => array( $patterns_instance, 'manage_options_permission_check' ),
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
					'permission_callback' => array( $patterns_instance, 'api_token_permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_templates' ),
					'permission_callback' => array( $patterns_instance, 'api_token_permission_check' ),
					'args'                => array(
						'templates'      => array(
							'required' => true,
							'type'     => 'array',
						),
						'patterns'       => array(
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
				'permission_callback' => array( $patterns_instance, 'manage_options_permission_check' ),
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
				'permission_callback' => array( $patterns_instance, 'manage_options_permission_check' ),
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
				'permission_callback' => array( $patterns_instance, 'api_token_permission_check' ),
				'args'                => array(
					'template_ids'  => array(
						'required' => true,
						'type'     => 'array',
					),
					'is_remove_all' => array(
						'required' => false,
						'type'     => 'boolean',
					),
				),
			)
		);

		/**
		 * Register a route to re-sync templates to connected sites.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/resync',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'resync_applied_templates' ),
				'permission_callback' => array( $patterns_instance, 'manage_options_permission_check' ),
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
		 * Register a route to create synced patterns.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/create-synced-patterns',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_synced_patterns' ),
				'permission_callback' => array( $patterns_instance, 'api_token_permission_check' ),
				'args'                => array(
					'synced_patterns' => array(
						'required' => true,
						'type'     => 'array',
					),
				),
			)
		);
	}

	/**
	 * Create synced patterns.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response The REST response object.
	 */
	public function create_synced_patterns( \WP_REST_Request $request ): \WP_REST_Response {
		$synced_patterns = $request->get_param( 'synced_patterns' );

		if ( empty( $synced_patterns ) || ! is_array( $synced_patterns ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Synced patterns parameter is required and should be an array.', 'onedesign' ),
				),
				400
			);
		}

		$existing_synced_patterns = get_option( Constants::ONEDESIGN_SHARED_SYNCED_PATTERNS, array() );
		if ( ! is_array( $existing_synced_patterns ) ) {
			$existing_synced_patterns = array();
		}

		// Merge new synced patterns with existing ones, avoiding duplicates based on 'id'.
		foreach ( $synced_patterns as $pattern ) {
			if ( isset( $pattern['id'] ) && ! array_filter( $existing_synced_patterns, fn( $t ) => $t['id'] === $pattern['id'] ) ) {
				$existing_synced_patterns[] = $pattern;
			}
		}

		update_option( Constants::ONEDESIGN_SHARED_SYNCED_PATTERNS, $existing_synced_patterns );

		// need to actual create posts so that in governing site I can map existing synced pattern.
		$created_posts = array();
		$error_logs    = array();
		foreach ( $existing_synced_patterns as $sync_pattern ) {
			// check if same post_name don't exists.
			$existing_post = get_posts(
				array(
					'post_type'   => 'wp_block',
					'post_name'   => $sync_pattern['slug'],
					'post_status' => 'publish',
					'numberposts' => 1,
				),
			);

			if ( ! empty( $existing_post ) ) {
				$created_posts[ $sync_pattern['original_id'] ] = $existing_post[0]->ID;
				continue;
			}

			$post_data = array(
				'post_type'    => 'wp_block',
				'post_title'   => $sync_pattern['title'],
				'post_name'    => $sync_pattern['slug'],
				'post_status'  => 'publish',
				'post_content' => $sync_pattern['content'],
			);

			$post_id = wp_insert_post( $post_data );

			if ( is_wp_error( $post_id ) ) {
				$error_logs[ $sync_pattern['original_id'] ] = sprintf(
					/* translators: %s: error message */
					'Failed to create synced pattern: %s',
					$post_id->get_error_message()
				);
			} else {
				$created_posts[ $sync_pattern['original_id'] ] = $post_id;
			}
		}

		// update brand site post ids option.
		$brand_site_post_ids = get_option( Constants::ONEDESIGN_BRAND_SITE_POST_IDS, array() );
		if ( ! is_array( $brand_site_post_ids ) ) {
			$brand_site_post_ids = array();
		}
		$brand_site_post_ids = array_merge( $brand_site_post_ids, array_values( $created_posts ) );

		update_option( Constants::ONEDESIGN_BRAND_SITE_POST_IDS, $brand_site_post_ids );

		return new \WP_REST_Response(
			array(
				'success'         => true,
				'message'         => __( 'Synced patterns created successfully.', 'onedesign' ),
				'synced_patterns' => $existing_synced_patterns,
				'created_posts'   => $created_posts,
				'error_logs'      => $error_logs,
			),
			200
		);
	}

	/**
	 * Re-sync applied templates to given brand site.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response The REST response object.
	 */
	public function resync_applied_templates( \WP_REST_Request $request ): \WP_REST_Response {
		$sites     = $request->get_param( 'sites' );
		$templates = $request->get_param( 'templates' );

		if ( empty( $sites ) || empty( $templates ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Sites and templates parameters are required.', 'onedesign' ),
				),
				400
			);
		}

		// Create request for remove_template.
		$remove_request = new \WP_REST_Request( 'DELETE', self::NAMESPACE . '/remove' );
		$remove_request->set_param( 'template_ids', $templates );
		$remove_request->set_param( 'site', $sites[0] );
		$remove_request->set_param( 'is_remove_all', true );
		$remove_template_response = $this->remove_template( $remove_request );

		// Create request for apply templates.
		$apply_request = new \WP_REST_Request( 'POST', self::NAMESPACE . '/apply' );
		$apply_request->set_param( 'templates', $templates );
		$apply_request->set_param( 'sites', $sites );
		$apply_template_response = $this->apply_templates_to_sites( $apply_request );

		return new \WP_REST_Response(
			array(
				'success'         => true,
				'message'         => __( 'Templates re-synced successfully to the selected sites.', 'onedesign' ),
				'remove_response' => $remove_template_response->get_data(),
				'apply_response'  => $apply_template_response->get_data(),
			),
			200
		);
	}

	/**
	 * Remove templates.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response The REST response object.
	 */
	public function remove_template_from_brand_site( \WP_REST_Request $request ): \WP_REST_Response {
		$template_ids  = $request->get_param( 'template_ids' );
		$is_remove_all = $request->get_param( 'is_remove_all' );

		if ( empty( $template_ids ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Template IDs parameter is required.',
				),
				400
			);
		}

		$existing_templates = get_option( Constants::ONEDESIGN_SHARED_TEMPLATES, array() );
		if ( ! is_array( $existing_templates ) ) {
			$existing_templates = array();
		}

		// Remove templates based on 'id'.
		$delete_logs = array();
		if ( $is_remove_all ) {
			$updated_templates = array();

			// get brand site post ids and remove them.
			$brand_site_post_ids = get_option( Constants::ONEDESIGN_BRAND_SITE_POST_IDS, array() );
			if ( is_array( $brand_site_post_ids ) ) {
				foreach ( $brand_site_post_ids as $post_id ) {
					$deleted = wp_delete_post( $post_id, true );
					if ( is_wp_error( $deleted ) ) {
						$delete_logs[] = array(
							'post_id' => $post_id,
							'message' => sprintf(
								/* translators: %s: error message */
								__( 'Failed to delete post ID %s.', 'onedesign' ),
								$post_id,
							),
						);
					} else {
						$delete_logs[] = array(
							'post_id' => $post_id,
							'message' => sprintf(
								/* translators: %s: post ID */
								__( 'Deleted post ID %s.', 'onedesign' ),
								$post_id,
							),
						);
					}
				}
			}
			update_option( Constants::ONEDESIGN_BRAND_SITE_POST_IDS, array() );
			update_option( Constants::ONEDESIGN_SHARED_PATTERNS, array() );
			update_option( Constants::ONEDESIGN_SHARED_TEMPLATE_PARTS, array() );
			update_option( Constants::ONEDESIGN_SHARED_SYNCED_PATTERNS, array() );

		} else {
			$updated_templates = array_filter( $existing_templates, fn( $t ) => ! in_array( $t['id'], $template_ids, true ) );
		}

		update_option( Constants::ONEDESIGN_SHARED_TEMPLATES, array_values( $updated_templates ) );

		return new \WP_REST_Response(
			array(
				'success'     => true,
				'message'     => __( 'Templates removed successfully.', 'onedesign' ),
				'templates'   => array_values( $updated_templates ),
				'delete_logs' => $delete_logs,
			),
			200
		);
	}

	/**
	 * Remove templates from selected brand site.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response The REST response object.
	 */
	public function remove_template( \WP_REST_Request $request ): \WP_REST_Response {
		$template_ids  = $request->get_param( 'template_ids' );
		$site          = $request->get_param( 'site' );
		$is_remove_all = $request->get_param( 'is_remove_all' );

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
						'template_ids'  => $template_ids,
						'is_remove_all' => $is_remove_all,
					)
				),
			)
		);

		$handle_response = $this->handle_remote_response( $response );
		if ( $handle_response['success'] ) {
			$response_data = $handle_response['data'];
		} else {
			$error_log[ $request_url ] = $handle_response['error'];
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

	/**
	 * Create or update shared templates.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response The REST response object.
	 */
	public function create_templates( \WP_REST_Request $request ): \WP_REST_Response {
		$templates      = $request->get_param( 'templates' );
		$patterns       = $request->get_param( 'patterns' );
		$template_parts = $request->get_param( 'template_parts' );

		if ( empty( $templates ) || ! is_array( $templates ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Templates parameter is required and should be an array.', 'onedesign' ),
				),
				400
			);
		}

		$existing_templates = get_option( Constants::ONEDESIGN_SHARED_TEMPLATES, array() );
		if ( ! is_array( $existing_templates ) ) {
			$existing_templates = array();
		}

		// Merge new templates with existing ones, avoiding duplicates based on 'id'.
		foreach ( $templates as $template ) {
			if ( isset( $template['id'] ) && ! array_filter( $existing_templates, fn( $t ) => $t['id'] === $template['id'] ) ) {
				$existing_templates[] = $template;
			}
		}

		update_option( Constants::ONEDESIGN_SHARED_TEMPLATES, $existing_templates );

		// get existing patterns.
		$existing_patterns = get_option( Constants::ONEDESIGN_SHARED_PATTERNS, array() );
		if ( ! is_array( $existing_patterns ) ) {
			$existing_patterns = array();
		}

		// Merge new patterns with existing ones, avoiding duplicates based on 'id'.
		if ( is_array( $patterns ) ) {
			foreach ( $patterns as $pattern ) {
				if ( isset( $pattern['name'] ) && ! array_filter( $existing_patterns, fn( $t ) => $t['name'] === $pattern['name'] ) ) {
					$existing_patterns[] = $pattern;
				}
			}
		}

		update_option( Constants::ONEDESIGN_SHARED_PATTERNS, $existing_patterns );

		// get existing template parts.
		$existing_template_parts = get_option( Constants::ONEDESIGN_SHARED_TEMPLATE_PARTS, array() );
		if ( ! is_array( $existing_template_parts ) ) {
			$existing_template_parts = array();
		}

		// Merge new template parts with existing ones, avoiding duplicates based on 'id'.
		if ( is_array( $template_parts ) ) {
			foreach ( $template_parts as $template_part ) {
				if ( isset( $template_part['id'] ) && ! array_filter( $existing_template_parts, fn( $t ) => $t['id'] === $template_part['id'] ) ) {
					$existing_template_parts[] = $template_part;
				}
			}
		}

		update_option( Constants::ONEDESIGN_SHARED_TEMPLATE_PARTS, $existing_template_parts );

		return new \WP_REST_Response(
			array(
				'success'        => true,
				'message'        => __( 'Templates saved successfully.', 'onedesign' ),
				'templates'      => $existing_templates,
				'patterns'       => $existing_patterns,
				'template_parts' => $existing_template_parts,
			),
			200
		);
	}

	/**
	 * Apply selected templates to selected brand sites.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response The REST response object.
	 */
	public function apply_templates_to_sites( \WP_REST_Request $request ): \WP_REST_Response {
		$sites     = $request->get_param( 'sites' );
		$templates = $request->get_param( 'templates' );

		if ( empty( $sites ) || empty( $templates ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Sites and templates parameters are required.', 'onedesign' ),
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
		foreach ( $shared_templates as $template ) {
			$parsed_templates = array_merge( $parsed_templates, onedesign_parse_block_template( $template['content'], $already_tracked ) );
		}

		$template_parts  = array_filter( $parsed_templates, fn( $t ) => 'template-part' === $t['type'] );
		$patterns        = array_filter( $parsed_templates, fn( $t ) => 'pattern' === $t['type'] );
		$synced_patterns = array_filter( $parsed_templates, fn( $t ) => 'block' === $t['type'] );

		// get site info from child sites option.
		$brand_sites = Utils::get_sites_info();

		$error_log     = array();
		$response_data = array();

		foreach ( $brand_sites as $site ) {
			$site_url     = esc_url_raw( trailingslashit( $site['url'] ) );
			$site_api_key = $site['api_key'];
			$site_id      = $site['id'];
			if ( in_array( $site_id, $sites, true ) ) {
				$request_url         = $site_url . 'wp-json/' . self::NAMESPACE . '/shared';
				$new_templates       = Utils::modify_template_template_part_pattern_slug( $shared_templates, $site['name'] );
				$new_patterns        = Utils::modify_template_template_part_pattern_slug( $patterns, $site['name'] );
				$new_template_parts  = Utils::modify_template_template_part_pattern_slug( $template_parts, $site['name'] );
				$new_synced_patterns = Utils::modify_template_template_part_pattern_slug( $synced_patterns, $site['name'] );

				// first make a request to create synced patterns to brand site.
				$synced_patterns_request_url = $site_url . 'wp-json/' . self::NAMESPACE . '/create-synced-patterns';
				$synced_patterns_response    = wp_safe_remote_post(
					$synced_patterns_request_url,
					array(
						'headers' => array(
							'X-OneDesign-API-Key' => 'Bearer ' . $site_api_key,
							'Content-Type'        => 'application/json',
						),
						'body'    => wp_json_encode(
							array(
								'synced_patterns' => $new_synced_patterns,
							)
						),
					)
				);

				$handled_response = $this->handle_remote_response( $synced_patterns_response );
				if ( ! $handled_response['success'] ) {
					$error_log[ $site_url ] = $handled_response['error'];
					continue;
				}
				$synced_patterns_response = $handled_response['data'];

				// replace current site synced pattern ref with created post id.
				if ( isset( $synced_patterns_response['created_posts'] ) && is_array( $synced_patterns_response['created_posts'] ) ) {
					$created_posts = $synced_patterns_response['created_posts'];

					$new_templates      = Utils::replace_block_refs( $new_templates, $created_posts );
					$new_template_parts = Utils::replace_block_refs( $new_template_parts, $created_posts );
					$new_patterns       = Utils::replace_block_refs( $new_patterns, $created_posts );
				}
				$response = wp_safe_remote_post(
					$request_url,
					array(
						'headers' => array(
							'X-OneDesign-API-Key' => 'Bearer ' . $site_api_key,
							'Content-Type'        => 'application/json',
						),
						'body'    => wp_json_encode(
							array(
								'templates'      => $new_templates,
								'patterns'       => $new_patterns,
								'template_parts' => $new_template_parts,
							)
						),
					)
				);

				$handled_response = $this->handle_remote_response( $response );
				if ( ! $handled_response['success'] ) {
					$error_log[ $site_url ] = $handled_response['error'];
				} else {
					$response_data[ $site_url ] = $handled_response['data'];
				}
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
				'template_parts'   => $new_template_parts,
				'patterns'         => $new_patterns,
				'synced_patterns'  => $synced_patterns,
			),
			200
		);
	}

	/**
	 * Get all block templates.
	 *
	 * @return \WP_REST_Response
	 */
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

	/**
	 * Get templates from all connected sites.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_templates_from_connected_sites(): \WP_REST_Response {
		$connected_sites = Utils::get_sites_info();
		$sites_response  = array();
		$error_log       = array();
		foreach ( $connected_sites as $site ) {
			$request_url      = esc_url_raw( trailingslashit( $site['url'] ) ) . '/wp-json/' . self::NAMESPACE . '/shared';
			$api_key          = $site['api_key'];
			$response         = wp_safe_remote_get(
				$request_url,
				array(
					'headers' => array(
						'X-OneDesign-API-Key' => 'Bearer ' . $api_key,
						'Content-Type'        => 'application/json',
					),
					'timeout' => 15,
				)
			);
			$handled_response = $this->handle_remote_response( $response );
			if ( $handled_response['success'] ) {
				if ( isset( $handled_response['data']['templates'] ) ) {
					$sites_response[ $site['id'] ] = $handled_response['data']['templates'];
				} else {
					$error_log[ $site['id'] ] = sprintf(
						/* translators: %s: site name */
						__( 'No templates found in the response from site: %s', 'onedesign' ),
						$site['name']
					);
				}
			} else {
				$error_log[ $site['id'] ] = $handled_response['error'];
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

	/**
	 * Get shared templates.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_shared_templates(): \WP_REST_Response {
		$shared_templates = get_option( Constants::ONEDESIGN_SHARED_TEMPLATES, array() );
		return new \WP_REST_Response(
			array(
				'success'   => true,
				'templates' => $shared_templates,
			),
			200
		);
	}

	/**
	 * Handle remote response.
	 *
	 * @param array|\WP_Error $response The response from wp_remote_get or wp_remote_post.
	 *
	 * @return array The processed response data.
	 */
	private function handle_remote_response( array|\WP_Error $response ): array {
		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return array(
				'success' => false,
				'error'   => 'Unexpected response code: ' . $response_code,
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return array(
				'success' => false,
				'error'   => 'JSON decode error: ' . json_last_error_msg(),
			);
		}

		return array(
			'success' => true,
			'data'    => $data,
		);
	}
}
