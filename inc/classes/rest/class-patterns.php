<?php
/**
 * Patterns class to handle all the REST API related to patterns sharing.
 *
 * @package OneDesign
 */

namespace OneDesign\Rest;

use OneDesign\Plugin_Configs\Constants;
use OneDesign\Traits\Singleton;
use OneDesign\Utils;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_REST_Server;

/**
 * Class Patterns
 */
class Patterns {

	/**
	 * Use Singleton trait.
	 */
	use Singleton;

	/**
	 * REST namespace.
	 */
	const NAMESPACE = Utils::NAMESPACE;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Setup hooks for the class.
	 */
	public function setup_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST routes for the plugin.
	 */
	public function register_rest_routes(): void {

		/**
		 * Get all local patterns both registered and user-created.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/local-patterns',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_local_patterns' ),
				'permission_callback' => array( Basic_Options::class, 'permission_callback' ),
			)
		);

		/**
		 * Get brand site patterns.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/brand-site-patterns',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_brand_site_patterns' ),
				'permission_callback' => 'onedesign_validate_api_key',
			)
		);

		/**
		 * Get all brand site patterns from child sites.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/get-all-brand-site-patterns',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_all_brand_site_patterns' ),
				'permission_callback' => array( Basic_Options::class, 'permission_callback' ),
			)
		);

		/**
		 * Route to request to brand sites to remove patterns.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/request-remove-brand-site-patterns',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'request_remove_brand_site_patterns' ),
				'permission_callback' => array( Basic_Options::class, 'permission_callback' ),
				'args'                => array(
					'pattern_names' => array(
						'required'          => true,
						'type'              => 'array',
						'validate_callback' => function ( $param ): bool {
							return is_array( $param ) && ! empty( $param );
						},
					),
					'site_id'       => array(
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => function ( $param ): bool {
							return is_string( $param ) && ! empty( $param );
						},
					),
				),
			)
		);

		/**
		 * Remove patterns from brand site patterns.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/remove-brand-site-patterns',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'remove_brand_site_patterns' ),
				'permission_callback' => 'onedesign_validate_api_key',
				'args'                => array(
					'pattern_names' => array(
						'required'          => true,
						'type'              => 'array',
						'validate_callback' => function ( $param ): bool {
							return is_array( $param ) && ! empty( $param );
						},
					),
				),
			)
		);

		/**
		 * Get pattern categories.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/pattern-categories',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_pattern_categories' ),
				'permission_callback' => array( Basic_Options::class, 'permission_callback' ),
			)
		);

		/**
		 * Get configured child sites.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/configured-sites',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_configured_child_sites' ),
				'permission_callback' => array( Basic_Options::class, 'permission_callback' ),
			)
		);

		/**
		 * Push patterns to target sites.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/push-patterns',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'push_patterns_to_targets' ),
				'permission_callback' => array( Basic_Options::class, 'permission_callback' ),
				'args'                => array(
					'pattern_names'   => array(
						'required'          => true,
						'type'              => 'array',
						'items'             => array( 'type' => 'string' ),
						'validate_callback' => function ( $param ): bool {
							return is_array( $param ) && ! empty( $param );
						},
					),
					'target_site_ids' => array(
						'required'          => true,
						'type'              => 'array',
						'items'             => array(
							'oneOf' => array(
								array( 'type' => 'string' ),
								array( 'type' => 'integer' ),
							),
						),
						'validate_callback' => function ( $param ): bool {
							return is_array( $param ) && ! empty( $param );
						},
					),
				),
			)
		);

		/**
		 * Receive patterns from parent site.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/receive-patterns',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'receive_patterns' ),
				'permission_callback' => 'onedesign_validate_api_key',
				'args'                => array(
					'patterns_data'    => array(
						'required'          => true,
						'type'              => 'array',
						'validate_callback' => function ( $param ): bool {
							return is_array( $param );
						},
					),
					'source_site_name' => array(
						'required' => false,
						'type'     => 'string',
					),
				),
			)
		);
	}

	/**
	 * Request to remove patterns from brand sites.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function request_remove_brand_site_patterns( WP_REST_Request $request ): WP_Error|WP_REST_Response {

		if ( ! Utils::is_governing_site() ) {
			return new WP_Error( 'not_parent_site', __( 'This site is not configured as a parent site.', 'onedesign' ), array( 'status' => 403 ) );
		}

		$pattern_name = $request->get_param( 'pattern_names' );
		$site_id      = $request->get_param( 'site_id' );

		if ( empty( $pattern_name ) || empty( $site_id ) ) {
			return new WP_Error( 'invalid_params', __( 'Pattern name and site ID are required.', 'onedesign' ), array( 'status' => 400 ) );
		}

		// Use the option name from your settings class.
		$child_sites = $this->get_compatible_sites_object();
		foreach ( $child_sites as $site ) {
			if ( isset( $site['id'] ) && $site['id'] === $site_id ) {
				$remote_api_key = $site['api_key'] ?? '';
				if ( empty( $remote_api_key ) ) {
					return new WP_Error( 'no_api_key', __( 'API key for the target site is missing in configuration.', 'onedesign' ), array( 'status' => 400 ) );
				}
				break;
			}
		}

		if ( ! isset( $remote_api_key ) ) {
			return new WP_Error( 'site_not_found', __( 'Target site not found in configuration.', 'onedesign' ), array( 'status' => 404 ) );
		}

		$remote_url = Utils::build_api_endpoint( $site['url'], 'remove-brand-site-patterns' );

		$response = wp_safe_remote_request(
			$remote_url,
			array(
				'method'  => 'DELETE',
				'headers' => array(
					'X-OneDesign-Token' => $remote_api_key,
					'Content-Type'      => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'pattern_names' => $pattern_name,
					)
				),
				'timeout' => 45,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'request_failed', __( 'Failed to communicate with the target site.', 'onedesign' ), array( 'status' => 500 ) );
		}

		$status_code  = wp_remote_retrieve_response_code( $response );
		$body         = wp_remote_retrieve_body( $response );
		$decoded_body = json_decode( $body, true );

		if ( $status_code >= 200 && $status_code < 300 ) {
			return new WP_REST_Response(
				array(
					'success'  => true,
					'message'  => __( 'Pattern removal request sent successfully.', 'onedesign' ),
					'response' => $decoded_body,
				),
				200
			);
		} else {
			$error_message = isset( $decoded_body['message'] ) ? $decoded_body['message'] : __( 'Unknown error from remote site.', 'onedesign' );
			return new WP_Error(
				'remote_error',
				// translators: %1$s is the error message, %2$d is the HTTP status code.
				sprintf( __( 'Error removing pattern: %1$s (Status: %2$d)', 'onedesign' ), $error_message, $status_code ),
				array( 'status' => $status_code )
			);
		}
	}

	/**
	 * Remove patterns from brand site patterns.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function remove_brand_site_patterns( WP_REST_Request $request ): WP_Error|WP_REST_Response {
		$pattern_names = $request->get_param( 'pattern_names' );

		if ( empty( $pattern_names ) || ! is_array( $pattern_names ) ) {
			return new WP_Error( 'invalid_params', __( 'Pattern names must be provided as an array.', 'onedesign' ), array( 'status' => 400 ) );
		}

		// Remove the patterns from the brand site patterns option.
		$brand_patterns = get_option( Constants::ONEDESIGN_BRAND_SITE_PATTERNS, array() );
		if ( empty( $brand_patterns ) ) {
			return new WP_Error( 'no_patterns_found', __( 'No patterns found for this brand site.', 'onedesign' ), array( 'status' => 404 ) );
		}

		$updated_patterns = array();
		foreach ( $brand_patterns as $pattern ) {
			if ( ! in_array( $pattern['name'], $pattern_names, true ) ) {
				$updated_patterns[ $pattern['name'] ] = $pattern; // Keep patterns not in the removal list.
			}
		}

		if ( count( $updated_patterns ) === count( $brand_patterns ) ) {
			return new WP_Error( 'no_patterns_removed', __( 'No patterns were removed. Please check the pattern names provided.', 'onedesign' ), array( 'status' => 400 ) );
		}

		update_option( Constants::ONEDESIGN_BRAND_SITE_PATTERNS, $updated_patterns, false );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Brand site patterns removed successfully.', 'onedesign' ),
			),
			200
		);
	}

	/**
	 * Get all brand site patterns from child sites.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_all_brand_site_patterns(): WP_Error|WP_REST_Response {

		if ( ! Utils::is_governing_site() ) {
			return new WP_Error( 'not_parent_site', __( 'This site is not configured as a parent site.', 'onedesign' ), array( 'status' => 403 ) );
		}

		// Call every child site to get their patterns.
		$child_sites = $this->get_compatible_sites_object();
		if ( empty( $child_sites ) ) {
			return new WP_Error( 'no_child_sites', __( 'No child sites configured to receive patterns.', 'onedesign' ), array( 'status' => 404 ) );
		}

		$all_patterns = array();
		$error_logs   = array();
		foreach ( $child_sites as $site ) {
			$site_patterns  = array();
			$remote_api_key = $site['api_key'] ?? '';
			$remote_url     = Utils::build_api_endpoint( $site['url'], 'brand-site-patterns' ) . '?timestamp=' . time(); // Add timestamp to avoid caching issues.

			if ( empty( $remote_api_key ) ) {
				continue; // Skip sites without API key.
			}

			$response = wp_safe_remote_get(
				$remote_url,
				array(
					'headers' => array(
						'X-OneDesign-Token' => $remote_api_key,
						'Content-Type'      => 'application/json',
					),
					'timeout' => 45,
				)
			);
			if ( is_wp_error( $response ) ) {
				continue; // Skip sites with errors.
			}

			$status_code = wp_remote_retrieve_response_code( $response );
			$body        = wp_remote_retrieve_body( $response );
			if ( $status_code >= 200 && $status_code < 300 ) {
				$decoded_body = json_decode( $body, true );

				if ( isset( $decoded_body['patterns'] ) && is_array( $decoded_body['patterns'] ) ) {
					foreach ( $decoded_body['patterns'] as $pattern ) {

						// Ensure the pattern has a name and title.
						if ( ! empty( $pattern['name'] ) && ! empty( $pattern['title'] ) ) {
							// Use name as a key to avoid duplicates.
							$site_patterns[] = array(
								'name'          => $pattern['name'],
								'title'         => $pattern['title'],
								'content'       => $pattern['content'] ?? '',
								'description'   => $pattern['description'] ?? '',
								'categories'    => $pattern['categories'] ?? array(),
								'keywords'      => $pattern['keywords'] ?? array(),
								'viewportWidth' => $pattern['viewportWidth'] ?? null,
								'blockTypes'    => $pattern['blockTypes'] ?? null,
								'postTypes'     => $pattern['postTypes'] ?? null,
								'templateTypes' => $pattern['templateTypes'] ?? null,
								'inserter'      => $pattern['inserter'] ?? true,
								'source_site'   => $site['name'] ?? 'Unknown Site', // Add the source site name for context.
							);
						}
					}
				}
			} else {
				// Log or handle error response from a child site.
				$error_message = $decoded_body['message'] ?? __( 'Unknown error from remote site.', 'onedesign' );
				$error_logs[]  = array(
					'site'        => $site['name'] ?? __( 'Unknown Site', 'onedesign' ),
					'status_code' => $status_code,
					'message'     => $error_message,
				);
			}
			$all_patterns[ $site['id'] ] = $site_patterns; // Store patterns by site ID.
		}

		return new WP_REST_Response(
			array(
				'success'    => true,
				'patterns'   => $all_patterns,
				'error_logs' => $error_logs,
			),
			200
		);
	}

	/**
	 * Get brand site patterns.
	 *
	 * @return WP_REST_Response
	 */
	public function get_brand_site_patterns(): WP_REST_Response {
		// Use the option name from your settings class.
		$brand_patterns = get_option( Constants::ONEDESIGN_BRAND_SITE_PATTERNS, array() );

		if ( empty( $brand_patterns ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No patterns found for this brand site.', 'onedesign' ),
				),
				404
			);
		}

		return new WP_REST_Response(
			array(
				'success'  => true,
				'patterns' => $brand_patterns,
			),
			200
		);
	}

	/**
	 * Get all local patterns (both registered and user-created).
	 *
	 * @return WP_REST_Response
	 */
	public function get_local_patterns(): WP_REST_Response {
		$patterns = $this->get_all_local_patterns_map();

		// Map to an indexed array for consistency with other endpoints.
		$patterns = array_values( $patterns ); // Convert an associative array to an indexed array.

		return new \WP_REST_Response( $patterns, 200 );
	}

	/**
	 * Get pattern categories.
	 *
	 * @return WP_REST_Response
	 */
	public function get_pattern_categories(): WP_REST_Response {

		if ( ! class_exists( 'WP_Block_Pattern_Categories_Registry' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Pattern categories registry not found.', 'onedesign' ),
				)
			);
		}

		$categories = \WP_Block_Pattern_Categories_Registry::get_instance()->get_all_registered();

		$user_created_categories = get_terms(
			array(
				'taxonomy'   => 'wp_pattern_category',
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $user_created_categories ) && ! empty( $user_created_categories ) && is_array( $user_created_categories ) ) {
			foreach ( $user_created_categories as $category ) {
				// Ensure the category has a name and label.
				if ( ! empty( $category->name ) && ! empty( $category->slug ) ) {
					// Check if category already exists to avoid duplicates.
					$exists = false;
					foreach ( $categories as $existing_category ) {
						if ( $existing_category['name'] === $category->slug ) {
							$exists = true;
							break;
						}
					}
					if ( $exists ) {
						continue; // Skip if the category already exists.
					}
					// Add user-created category to the list.
					$categories[] = array(
						'name'  => $category->slug,
						'label' => $category->name,
					);
				}
			}
		}

		return new WP_REST_Response(
			array(
				'success'    => true,
				'categories' => $categories,
			)
		);
	}

	/**
	 * Get configured child sites (for a parent site type).
	 *
	 * @return WP_REST_Response
	 */
	public function get_configured_child_sites(): WP_REST_Response {

		if ( ! Utils::is_governing_site() ) {
			return new WP_REST_Response( array(), 200 ); // Return empty if not a parent site.
		}

		// Use the option name from your settings class.
		$child_sites = $this->get_compatible_sites_object();

		return new WP_REST_Response( $child_sites, 200 );
	}

	/**
	 * Push patterns to target sites.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function push_patterns_to_targets( WP_REST_Request $request ) {

		if ( ! Utils::is_governing_site() ) {
			return new WP_Error( 'not_parent_site', __( 'This site is not configured as a parent site.', 'onedesign' ), array( 'status' => 403 ) );
		}

		$pattern_names = $request->get_param( 'pattern_names' );
		// Expecting numeric ids based on your settings UI.
		$target_site_ids = $request->get_param( 'target_site_ids' );

		// Use the option name from your settings class.
		$configured_child_sites = $this->get_compatible_sites_object();

		// Get all patterns (both registered and user-created).
		$local_patterns_map = $this->get_all_local_patterns_map();

		$patterns_to_push = array();
		foreach ( $pattern_names as $name ) {
			if ( isset( $local_patterns_map[ $name ] ) ) {
				$pattern_data       = $local_patterns_map[ $name ];
				$patterns_to_push[] = array(
					'name'          => $pattern_data['name'] ?? '',
					'title'         => $pattern_data['title'] ?? '',
					'content'       => $pattern_data['content'] ?? '',
					'description'   => $pattern_data['description'] ?? '',
					'categories'    => $pattern_data['categories'] ?? array(),
					'keywords'      => $pattern_data['keywords'] ?? array(),
					'viewportWidth' => $pattern_data['viewportWidth'] ?? null,
					'blockTypes'    => $pattern_data['blockTypes'] ?? null,
					'postTypes'     => $pattern_data['postTypes'] ?? null,
					'templateTypes' => $pattern_data['templateTypes'] ?? null,
					'inserter'      => $pattern_data['inserter'] ?? true,
					'source'        => $pattern_data['source'] ?? 'registered',
					'id'            => $pattern_data['id'] ?? null, // For user patterns.
				);
			}
		}

		if ( empty( $patterns_to_push ) ) {
			return new WP_Error( 'no_patterns_selected', __( 'No valid patterns selected to push.', 'onedesign' ), array( 'status' => 400 ) );
		}

		$results           = array();
		$current_site_name = get_bloginfo( 'name' );

		foreach ( $target_site_ids as $site_id ) {
			$target_site = array_filter(
				$configured_child_sites,
				function ( $site ) use ( $site_id ) {
					return isset( $site['id'] ) && $site['id'] === $site_id;
				}
			);

			$target_site = reset( $target_site ); // Get the first match, should be unique by ID.

			if ( empty( $target_site ) ) {
				$results[ $site_id ] = array(
					'success' => false,
					'message' => __( 'Target site not found in configuration.', 'onedesign' ),
				);
				continue;
			}

			// The 'api_key' in $target_site is the token for the remote child site.
			$remote_api_key = $target_site['api_key'] ?? '';
			$remote_url     = Utils::build_api_endpoint( $target_site['url'], 'receive-patterns' );

			if ( empty( $remote_api_key ) ) {
				$results[ $site_id ] = array(
					'success' => false,
					'message' => __( 'API key for the target site is missing in configuration.', 'onedesign' ),
				);
				continue;
			}

			$response = wp_safe_remote_post(
				$remote_url,
				array(
					'method'  => 'POST',
					'headers' => array(
						'X-OneDesign-Token' => $remote_api_key,
						'Content-Type'      => 'application/json',
					),
					'body'    => wp_json_encode(
						array(
							'patterns_data'    => $patterns_to_push,
							'source_site_name' => $current_site_name,
						)
					),
					'timeout' => 45,
				)
			);

			if ( is_wp_error( $response ) ) {
				$results[ $site_id ] = array(
					'success' => false,
					'message' => $response->get_error_message(),
				);
			} else {
				$status_code  = wp_remote_retrieve_response_code( $response );
				$body         = wp_remote_retrieve_body( $response );
				$decoded_body = json_decode( $body, true );

				if ( $status_code >= 200 && $status_code < 300 ) {
					$results[ $site_id ] = array(
						'success'  => true,
						'message'  => __( 'Patterns pushed successfully.', 'onedesign' ),
						'response' => $decoded_body,
					);
				} else {
					$error_message       = $decoded_body['message'] ?? __( 'Unknown error from remote site.', 'onedesign' );
					$results[ $site_id ] = array(
						'success'       => false,
						// translators: %1$s is the error message, %2$d is the HTTP status code.
						'message'       => sprintf( __( 'Error pushing patterns: %1$s (Status: %2$d)', 'onedesign' ), $error_message, $status_code ),
						'response_body' => $body,
					);
				}
			}
		}

		return new WP_REST_Response( $results, 200 );
	}

	/**
	 * Get all local patterns as a map (both registered and user-created).
	 *
	 * @return array
	 */
	private function get_all_local_patterns_map(): array {
		$patterns_map = array();

		$pattern_categories = \WP_Block_Pattern_Categories_Registry::get_instance()->get_all_registered();

		$user_created_categories = get_terms(
			array(
				'taxonomy'   => 'wp_pattern_category',
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $user_created_categories ) && ! empty( $user_created_categories ) && is_array( $user_created_categories ) ) {
			foreach ( $user_created_categories as $category ) {
				// Ensure the category has a name and label.
				if ( ! empty( $category->name ) && ! empty( $category->slug ) ) {
					$pattern_categories[] = array(
						'name'  => $category->slug,
						'label' => $category->name,
					);
				}
			}
		}

		$pattern_category_name_to_label = array();
		if ( ! empty( $pattern_categories ) && is_array( $pattern_categories ) ) {
			foreach ( $pattern_categories as $category ) {
				$pattern_category_name_to_label[ $category['name'] ] = $category['label'];
			}
		}

		// Get all registered patterns.
		$registered_patterns = \WP_Block_Patterns_Registry::get_instance()->get_all_registered();

		foreach ( $registered_patterns as $pattern ) {
			if ( isset( $pattern['inserter'] ) && false === $pattern['inserter'] ) {
				continue; // Skip patterns not intended for inserter.
			}

			// Map pattern category name to labels.
			$pattern['category_labels'] = array();
			if ( ! empty( $pattern['categories'] ) && is_array( $pattern['categories'] ) ) {
				foreach ( $pattern['categories'] as $category_name ) {
					$pattern['category_labels'][ $category_name ] = $pattern_category_name_to_label[ $category_name ] ?? $category_name;
				}
			} else {
				$pattern['category_labels'] = array(); // Ensure it's an empty array if no categories.
			}

			$patterns_map[ $pattern['name'] ] = array(
				'name'            => $pattern['name'],
				'title'           => $pattern['title'],
				'description'     => $pattern['description'] ?? '',
				'content'         => $pattern['content'],
				'categories'      => $pattern['categories'] ?? array(),
				'category_labels' => $pattern['category_labels'],
				'keywords'        => $pattern['keywords'] ?? array(),
				'viewportWidth'   => $pattern['viewportWidth'] ?? null,
				'blockTypes'      => $pattern['blockTypes'] ?? null,
				'postTypes'       => $pattern['postTypes'] ?? null,
				'templateTypes'   => $pattern['templateTypes'] ?? null,
				'inserter'        => $pattern['inserter'] ?? true,
				'source'          => 'registered',
			);
		}

		// Get user-created patterns from wp_block posts.
		$user_patterns = get_posts(
			array(
				'post_type'        => 'wp_block',
				'post_status'      => 'publish',
				'posts_per_page'   => -1,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);

		foreach ( $user_patterns as $pattern_post ) {
			// Get pattern categories (stored as wp_pattern_category taxonomy).
			$categories = wp_get_object_terms( $pattern_post->ID, 'wp_pattern_category', array( 'fields' => 'slugs' ) );
			if ( is_wp_error( $categories ) ) {
				$categories = array();
			}

			// Map pattern category name to labels.
			$pattern['category_labels'] = array();
			if ( ! empty( $categories ) && is_array( $categories ) ) {
				foreach ( $categories as $category_name ) {
					$pattern['category_labels'][ $category_name ] = $pattern_category_name_to_label[ $category_name ] ?? $category_name;
				}
			} else {
				$pattern['category_labels'] = array(); // Ensure it's an empty array if no categories.
			}

			// Get pattern keywords (stored as meta).
			$keywords = get_post_meta( $pattern_post->ID, 'wp_pattern_keywords', true );
			if ( ! is_array( $keywords ) ) {
				$keywords = array();
			}

			$pattern_name = 'user-pattern-' . $pattern_post->ID;

			$patterns_map[ $pattern_name ] = array(
				'name'            => $pattern_name,
				'title'           => $pattern_post->post_title,
				'description'     => $pattern_post->post_excerpt,
				'content'         => $pattern_post->post_content,
				'categories'      => $categories,
				'category_labels' => $pattern['category_labels'],
				'keywords'        => $keywords,
				'viewportWidth'   => null,
				'blockTypes'      => null,
				'postTypes'       => null,
				'templateTypes'   => null,
				'inserter'        => true,
				'source'          => 'user',
				'id'              => $pattern_post->ID,
			);
		}

		return $patterns_map;
	}

	/**
	 * Receive patterns from the parent site.
	 *
	 * This endpoint is primarily for child sites to receive patterns pushed from the parent site.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function receive_patterns( WP_REST_Request $request ): WP_Error|WP_REST_Response {

		// This endpoint is primarily for child sites, but a site could technically receive even if set as parent if token matches.
		if ( ! Utils::is_brand_site() ) {
			return new WP_Error( 'not_child_site', __( 'This site is not configured as a child site to receive patterns.', 'onedesign' ), array( 'status' => 403 ) );
		}

		$patterns_data = $request->get_param( 'patterns_data' );

		if ( empty( $patterns_data ) || ! is_array( $patterns_data ) ) {
			return new WP_Error( 'no_patterns_data', __( 'No patterns data received.', 'onedesign' ), array( 'status' => 400 ) );
		}

		// Get existing patterns from option.
		$existing_patterns = get_option( Constants::ONEDESIGN_BRAND_SITE_PATTERNS, array() );

		foreach ( $patterns_data as $pattern ) {
			if ( empty( $pattern['name'] ) || empty( $pattern['title'] ) || ! isset( $pattern['content'] ) ) {
				continue;
			}

			$name = sanitize_key( $pattern['name'] );
			// Prepare args, same as before.
			$pattern_args = array(
				'name'          => $name,
				'title'         => sanitize_text_field( $pattern['title'] ),
				'content'       => $pattern['content'],
				'description'   => isset( $pattern['description'] ) ? sanitize_text_field( $pattern['description'] ) : '',
				'categories'    => isset( $pattern['categories'] ) && is_array( $pattern['categories'] ) ? array_map( 'sanitize_text_field', $pattern['categories'] ) : array(),
				'keywords'      => isset( $pattern['keywords'] ) && is_array( $pattern['keywords'] ) ? array_map( 'sanitize_text_field', $pattern['keywords'] ) : array(),
				'viewportWidth' => isset( $pattern['viewportWidth'] ) ? intval( $pattern['viewportWidth'] ) : null,
				'blockTypes'    => isset( $pattern['blockTypes'] ) && is_array( $pattern['blockTypes'] ) ? array_map( 'sanitize_text_field', $pattern['blockTypes'] ) : null,
				'postTypes'     => isset( $pattern['postTypes'] ) && is_array( $pattern['postTypes'] ) ? array_map( 'sanitize_text_field', $pattern['postTypes'] ) : null,
				'templateTypes' => isset( $pattern['templateTypes'] ) && is_array( $pattern['templateTypes'] ) ? array_map( 'sanitize_text_field', $pattern['templateTypes'] ) : null,
				'inserter'      => isset( $pattern['inserter'] ) ? (bool) $pattern['inserter'] : true,
			);
			$pattern_args = array_filter(
				$pattern_args,
				function ( $value ) {
					return ! is_null( $value );
				}
			);

			// Merge/update pattern - this will add new patterns or update existing ones.
			$existing_patterns[ $name ] = $pattern_args;
		}

		// Save merged patterns back to the option.
		update_option( Constants::ONEDESIGN_BRAND_SITE_PATTERNS, $existing_patterns, false );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => sprintf(
					__( 'Patterns processed.', 'onedesign' ),
				),
			),
			200
		);
	}

	/**
	 * Get child sites configured for this parent site.
	 *
	 * @return array List of configured child sites.
	 */
	public function get_compatible_sites_object(): array {
		$children = get_option( Constants::ONEDESIGN_SHARED_SITES, array() );
		if ( empty( $children ) || ! is_array( $children ) ) {
			return array(); // Return an empty array if no children configured.
		}

		return $children;
	}
}
