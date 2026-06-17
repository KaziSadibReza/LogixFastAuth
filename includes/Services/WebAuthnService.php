<?php
/**
 * WebAuthn / passkey authentication.
 *
 * @package SLR
 */

namespace SLR\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Settings;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WebAuthnService
 */
class WebAuthnService {

	/**
	 * @var WebAuthnCrypto
	 */
	private $crypto;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->crypto = new WebAuthnCrypto();
	}

	/**
	 * Get registration options for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error
	 */
	public function get_register_options( $user_id ) {
		if ( ! $this->is_enabled() ) {
			return new WP_Error( 'slr_webauthn_disabled', __( 'Passkey authentication is disabled.', 'smart-login-registration' ), array( 'status' => 403 ) );
		}

		return $this->crypto->build_register_options( $user_id, $this->get_rp_id() );
	}

	/**
	 * Verify registration response.
	 *
	 * @param int   $user_id  User ID.
	 * @param array $response Client response.
	 * @return true|WP_Error
	 */
	public function verify_register( $user_id, $response ) {
		if ( ! $this->is_enabled() ) {
			return new WP_Error( 'slr_webauthn_disabled', __( 'Passkey authentication is disabled.', 'smart-login-registration' ), array( 'status' => 403 ) );
		}

		$source = $this->crypto->verify_register( $user_id, $response, $this->get_rp_id() );
		if ( is_wp_error( $source ) ) {
			return $source;
		}

		return $this->crypto->store_credential_source( $user_id, $source, $response );
	}

	/**
	 * Get login options.
	 *
	 * @param string $email User email (optional).
	 * @return array|WP_Error
	 */
	public function get_login_options( $email = '' ) {
		if ( ! $this->is_enabled() ) {
			return new WP_Error( 'slr_webauthn_disabled', __( 'Passkey authentication is disabled.', 'smart-login-registration' ), array( 'status' => 403 ) );
		}

		return $this->crypto->build_login_options( $email, $this->get_rp_id() );
	}

	/**
	 * Verify login response.
	 *
	 * @param string $session_key Session key.
	 * @param array  $response    Client response.
	 * @return int|WP_Error User ID on success.
	 */
	public function verify_login( $session_key, $response ) {
		if ( ! $this->is_enabled() ) {
			return new WP_Error( 'slr_webauthn_disabled', __( 'Passkey authentication is disabled.', 'smart-login-registration' ), array( 'status' => 403 ) );
		}

		return $this->crypto->verify_login( $session_key, $response, $this->get_rp_id() );
	}

	/**
	 * Check if WebAuthn is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		if ( ! WebAuthnCrypto::is_available() ) {
			return false;
		}

		$auth = Settings::get( 'auth' );
		return ! empty( $auth['webauthn_enabled'] );
	}

	/**
	 * Get relying party ID.
	 *
	 * @return string
	 */
	private function get_rp_id() {
		$security = Settings::get( 'security' );
		if ( ! empty( $security['webauthn_rp_id'] ) ) {
			return $security['webauthn_rp_id'];
		}
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		return $host ? $host : 'localhost';
	}
}
