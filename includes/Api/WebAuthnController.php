<?php
/**
 * WebAuthn REST endpoints.
 *
 * @package SLR
 */

namespace SLR\Api; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Database\WebAuthnRepository;
use SLR\Services\AuthService;
use SLR\Services\RateLimiter;
use SLR\Services\SpamProtection;
use SLR\Services\WebAuthnService;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WebAuthnController
 */
class WebAuthnController {

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'slr/v1',
			'/webauthn/register/options',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'register_options' ),
				'permission_callback' => array( $this, 'logged_in_permission' ),
			)
		);

		register_rest_route(
			'slr/v1',
			'/webauthn/register/verify',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'register_verify' ),
				'permission_callback' => array( $this, 'logged_in_permission' ),
			)
		);

		register_rest_route(
			'slr/v1',
			'/webauthn/credentials',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_credentials' ),
					'permission_callback' => array( $this, 'logged_in_permission' ),
				),
			)
		);

		register_rest_route(
			'slr/v1',
			'/webauthn/credentials/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_credential' ),
					'permission_callback' => array( $this, 'logged_in_permission' ),
				),
			)
		);

		register_rest_route(
			'slr/v1',
			'/webauthn/login/options',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'login_options' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'slr/v1',
			'/webauthn/login/verify',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'login_verify' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Logged-in permission check.
	 *
	 * @return bool
	 */
	public function logged_in_permission() {
		return is_user_logged_in();
	}

	/**
	 * List current user's passkeys.
	 *
	 * @return \WP_REST_Response
	 */
	public function list_credentials() {
		$repo  = new WebAuthnRepository();
		$creds = $repo->get_by_user( get_current_user_id() );
		$list  = array();

		foreach ( $creds as $cred ) {
			$list[] = array(
				'id'           => (int) $cred->id,
				'created_at'   => $cred->created_at,
				'last_used_at' => $cred->last_used_at,
			);
		}

		return rest_ensure_response( $list );
	}

	/**
	 * Delete a passkey.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_credential( $request ) {
		$id   = (int) $request['id'];
		$repo = new WebAuthnRepository();

		if ( ! $repo->delete( $id, get_current_user_id() ) ) {
			return new \WP_Error( 'slr_passkey_not_found', __( 'Passkey not found.', 'smart-login-registration' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Get registration options.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function register_options() {
		$result = ( new WebAuthnService() )->get_register_options( get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response( $result );
	}

	/**
	 * Verify registration.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function register_verify( $request ) {
		$data   = $request->get_json_params() ?: array();
		$result = ( new WebAuthnService() )->verify_register( get_current_user_id(), $data );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Get login options.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function login_options( $request ) {
		$data = $request->get_json_params() ?: array();

		$spam = ( new SpamProtection() )->verify( $data );
		if ( is_wp_error( $spam ) ) {
			return $spam;
		}

		$rate = RateLimiter::throttle_scoped( 'webauthn' );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$email  = sanitize_email( $data['email'] ?? '' );

		$result = ( new WebAuthnService() )->get_login_options( $email );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response( $result );
	}

	/**
	 * Verify login.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function login_verify( $request ) {
		$data = $request->get_json_params() ?: array();

		$spam = ( new SpamProtection() )->verify( $data );
		if ( is_wp_error( $spam ) ) {
			return $spam;
		}

		$rate = RateLimiter::throttle_scoped( 'webauthn' );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$session_key = sanitize_text_field( $data['sessionKey'] ?? '' );
		$response    = is_array( $data['response'] ?? null ) ? $data['response'] : array();

		$user_id = ( new WebAuthnService() )->verify_login( $session_key, $response );
		if ( is_wp_error( $user_id ) ) {
			( new RateLimiter() )->record_failure( 'webauthn', RateLimiter::get_client_ip() );
			return $user_id;
		}

		$auth_result = ( new AuthService() )->authenticate_user( $user_id, ! empty( $data['remember'] ) );
		if ( is_wp_error( $auth_result ) ) {
			return $auth_result;
		}

		return rest_ensure_response( $auth_result );
	}
}
