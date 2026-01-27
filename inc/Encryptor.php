<?php
/**
 * WordPress-safe encryption utilities.
 *
 * Runtime exceptions are _not_ translated to allow this class to be used whenever needed.
 *
 * @package OneDesign
 */

declare( strict_types = 1 );

namespace OneDesign;

/**
 * Class - Encryptor
 */
final class Encryptor {
	/**
	 * The OpenSSL encryption method.
	 */
	private const METHOD = 'aes-256-ctr';

	/**
	 * Encrypts a value using WordPress's built-in encryption.
	 *
	 * @param string $raw_value The value to encrypt.
	 *
	 * @return string|bool The encrypted value, or false on failure.
	 */
	public static function encrypt( string $raw_value ): string|bool {
		if ( ! extension_loaded( 'openssl' ) ) {
			return $raw_value;
		}

		$ivlength = openssl_cipher_iv_length( self::METHOD );
		$iv       = openssl_random_pseudo_bytes( $ivlength );

		$value = openssl_encrypt( $raw_value . self::get_salt(), self::METHOD, self::get_key(), 0, $iv );

		if ( ! $value ) {
			return false;
		}

		return base64_encode( $iv . $value );
	}

	/**
	 * Decrypts a value from the database.
	 *
	 * @param string $raw_value The value to decrypt.
	 *
	 * @return string|false The decrypted value, or false on failure.
	 */
	public static function decrypt( string $raw_value ): string|bool {
		if ( ! extension_loaded( 'openssl' ) ) {
			return $raw_value;
		}

		$decoded_value = base64_decode( $raw_value, true );
		if ( false === $decoded_value ) {
			return $raw_value;
		}

		$ivlength = openssl_cipher_iv_length( self::METHOD );
		$iv       = substr( $decoded_value, 0, $ivlength );

		$decoded_value = substr( $decoded_value, $ivlength );

		$value = openssl_decrypt(
			$decoded_value,
			self::METHOD,
			self::get_key(),
			0,
			$iv,
		);

		$salt = self::get_salt();

		if ( ! $value || substr( $value, - strlen( $salt ) ) !== $salt ) {
			return false;
		}

		return substr( $value, 0, - strlen( $salt ) );
	}

	/**
	 * Gets the encryption key.
	 *
	 * Uses ONEPRESS_ENCRYPTION_KEY if defined, otherwise falls back to LOGGED_IN_KEY.
	 */
	private static function get_key(): string {
		if ( defined( 'ONEPRESS_ENCRYPTION_KEY' ) && '' !== ONEPRESS_ENCRYPTION_KEY ) {
			return ONEPRESS_ENCRYPTION_KEY;
		}

		if ( defined( 'LOGGED_IN_KEY' ) && '' !== LOGGED_IN_KEY ) {
			return LOGGED_IN_KEY;
		}

		// If you're here, you're either not on a live site or have a serious security issue.
		return 'this-is-not-a-real-key-change-me';
	}

	/**
	 * Gets the encryption salt.
	 *
	 * Uses ONEPRESS_ENCRYPTION_SALT if defined, otherwise falls back to LOGGED_IN_SALT.
	 */
	private static function get_salt(): string {
		if ( defined( 'ONEPRESS_ENCRYPTION_SALT' ) && '' !== ONEPRESS_ENCRYPTION_SALT ) {
			return ONEPRESS_ENCRYPTION_SALT;
		}

		if ( defined( 'LOGGED_IN_SALT' ) && '' !== LOGGED_IN_SALT ) {
			return LOGGED_IN_SALT;
		}

		// If you're here, you're either not on a live site or have a serious security issue.
		return 'this-is-not-a-real-salt-change-me';
	}
}
