<?php
/**
 * Create a secret key for OneDesign site communication.
 *
 * @package OneDesign
 */

namespace OneDesign\Plugin_Configs;

use OneDesign\Traits\Singleton;
use OneDesign\Utils;

/**
 * Class Secret_Key
 */
class Secret_Key {
	/**
	 * Use Singleton trait.
	 */
	use Singleton;

	/**
	 * Protected class constructor
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Setup WordPress hooks
	 */
	public function setup_hooks(): void {
		add_action( 'admin_init', array( self::class, 'generate_secret_key' ) );
	}

	/**
	 * Generate a secret key for the site.
	 *
	 * @param bool $is_regenerate Whether to regenerate the key or not.
	 *
	 * @return string The generated secret key.
	 */
	public static function generate_secret_key( bool $is_regenerate = false ): string {
		$secret_key = get_option( Constants::ONEDESIGN_API_KEY );
		if ( empty( $secret_key ) || $is_regenerate ) {
			$secret_key = self::generate_key();
			// Store the secret key in the database.
			$is_key_updated = update_option( Constants::ONEDESIGN_API_KEY, $secret_key, false );

			if ( Utils::is_multisite() ) {
				/**
				 * Trigger action when API key is generated in multisite setup.
				 *
				 * @param string $secret_key The generated secret key.
				 * @param int $blog_id The blog ID where the key is generated.
				 *
				 * @hook onedesign_multisite_api_key_generated
				 */
				do_action( 'onedesign_multisite_api_key_generated', $secret_key, get_current_blog_id() );
			}

			if ( ! $is_key_updated ) {
				return '';
			}
		}

		return $secret_key;
	}

	/**
	 * Generate a random key.
	 *
	 * @return string The generated key.
	 */
	public static function generate_key(): string {
		return wp_generate_password( 128, false, false );
	}

	/**
	 * Get the secret key.
	 *
	 * @return \WP_REST_Response| \WP_Error
	 */
	public static function get_secret_key(): \WP_REST_Response|\WP_Error {
		$secret_key = get_option( Constants::ONEDESIGN_API_KEY );
		if ( empty( $secret_key ) ) {
			$secret_key = self::generate_secret_key();
		}
		return new \WP_REST_Response(
			array(
				'success'    => true,
				'secret_key' => $secret_key,
			)
		);
	}

	/**
	 * Regenerate the secret key.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function regenerate_secret_key(): \WP_REST_Response|\WP_Error {

		$regenerated_key = self::generate_secret_key( true );

		return new \WP_REST_Response(
			array(
				'success'    => true,
				'message'    => __( 'Secret key regenerated successfully.', 'onedesign' ),
				'secret_key' => $regenerated_key,
			)
		);
	}
}
