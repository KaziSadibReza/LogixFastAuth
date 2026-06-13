<?php
/**
 * WebAuthn / passkey authentication.
 *
 * @package SLR
 */

namespace SLR\Services;

use SLR\Database\WebAuthnRepository;
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
	 * Passkey auth must remain off until server-side WebAuthn assertion and
	 * attestation verification is implemented.
	 */
	const SERVER_VERIFICATION_AVAILABLE = false;

	/**
	 * @var WebAuthnRepository
	 */
	private $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new WebAuthnRepository();
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

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return new WP_Error( 'slr_user_not_found', __( 'User not found.', 'smart-login-registration' ), array( 'status' => 404 ) );
		}

		$challenge = $this->generate_challenge();
		set_transient( 'slr_webauthn_reg_' . $user_id, $challenge, 300 );

		return array(
			'challenge'        => $challenge,
			'rp'               => array(
				'name' => get_bloginfo( 'name' ),
				'id'   => $this->get_rp_id(),
			),
			'user'             => array(
				'id'          => base64_encode( (string) $user_id ),
				'name'        => $user->user_email,
				'displayName' => $user->display_name,
			),
			'pubKeyCredParams' => array(
				array( 'type' => 'public-key', 'alg' => -7 ),
				array( 'type' => 'public-key', 'alg' => -257 ),
			),
			'timeout'          => 300000,
			'attestation'      => 'none',
		);
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

		$challenge = get_transient( 'slr_webauthn_reg_' . $user_id );
		if ( ! $challenge ) {
			return new WP_Error( 'slr_webauthn_expired', __( 'Registration session expired.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		delete_transient( 'slr_webauthn_reg_' . $user_id );

		$credential_id = sanitize_text_field( $response['id'] ?? '' );
		if ( empty( $credential_id ) ) {
			return new WP_Error( 'slr_webauthn_invalid', __( 'Invalid passkey response.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		$this->repository->create( array(
			'user_id'       => $user_id,
			'credential_id' => $credential_id,
			'public_key'    => wp_json_encode( $response ),
			'counter'       => 0,
			'transports'    => implode( ',', $response['transports'] ?? array() ),
		) );

		return true;
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

		$challenge  = $this->generate_challenge();
		$session_key = 'slr_webauthn_login_' . wp_generate_password( 16, false );
		set_transient( $session_key, array( 'challenge' => $challenge, 'email' => $email ), 300 );

		$allow_credentials = array();
		if ( ! empty( $email ) ) {
			$user = get_user_by( 'email', sanitize_email( $email ) );
			if ( $user ) {
				$creds = $this->repository->get_by_user( $user->ID );
				foreach ( $creds as $cred ) {
					$allow_credentials[] = array(
						'type' => 'public-key',
						'id'   => $cred->credential_id,
					);
				}
			}
		}

		return array(
			'options' => array(
				'challenge'        => $challenge,
				'timeout'          => 300000,
				'rpId'             => $this->get_rp_id(),
				'allowCredentials' => $allow_credentials,
				'userVerification' => 'preferred',
			),
			'sessionKey' => $session_key,
		);
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

		$session = get_transient( $session_key );
		if ( ! $session ) {
			return new WP_Error( 'slr_webauthn_expired', __( 'Login session expired.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		delete_transient( $session_key );

		$credential_id = sanitize_text_field( $response['id'] ?? '' );
		$credential    = $this->repository->get_by_credential_id( $credential_id );

		if ( ! $credential ) {
			return new WP_Error( 'slr_webauthn_invalid', __( 'Passkey not recognized.', 'smart-login-registration' ), array( 'status' => 401 ) );
		}

		$this->repository->update_counter( $credential->id, (int) $credential->counter + 1 );

		return (int) $credential->user_id;
	}

	/**
	 * Check if WebAuthn is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$auth = Settings::get( 'auth' );
		return self::SERVER_VERIFICATION_AVAILABLE && ! empty( $auth['webauthn_enabled'] );
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
		return $host ?: 'localhost';
	}

	/**
	 * Generate random challenge (base64url).
	 *
	 * @return string
	 */
	private function generate_challenge() {
		return rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );
	}
}
