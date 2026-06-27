<?php
/**
 * OTP REST endpoints.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Api; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Services\AuthService;
use LogixFastAuth\Services\RedirectService;
use LogixFastAuth\Services\StatsService;
use LogixFastAuth\Services\OtpService;
use LogixFastAuth\Services\PendingRegistrationService;
use LogixFastAuth\Services\RateLimiter;
use LogixFastAuth\Services\SpamProtection;
use LogixFastAuth\Settings;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OtpController
 */
class OtpController {

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'logixfast-auth/v1',
			'/otp/send',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'send' ),
				'permission_callback' => '__return_true', // Public OTP request endpoint; guarded by identifier/IP rate limiting.
			)
		);

		register_rest_route(
			'logixfast-auth/v1',
			'/otp/verify',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'verify' ),
				'permission_callback' => '__return_true', // Public OTP verification endpoint; requires matching hashed code/session state.
			)
		);
	}

	/**
	 * Send OTP.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function send( $request ) {
		$data = $request->get_json_params() ?: array();

		$spam = ( new SpamProtection() )->verify( $data );
		if ( is_wp_error( $spam ) ) {
			return $spam;
		}

		$identifier    = $this->sanitize_identifier( $data['identifier'] ?? '', $data['channel'] ?? 'email' );
		$channel       = sanitize_key( $data['channel'] ?? 'email' );
		$purpose       = sanitize_key( $data['purpose'] ?? 'verify' );
		$pending_token = sanitize_text_field( $data['pending_token'] ?? '' );

		$validated = $this->validate_request_context( $identifier, $channel, $purpose, $pending_token );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$rate = $this->check_rate_limit( 'otp_send', $identifier, $purpose );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		if ( 'register' === $purpose ) {
			$pending_check = ( new PendingRegistrationService() )->peek( $pending_token, $identifier, $channel );
			if ( is_wp_error( $pending_check ) ) {
				return $pending_check;
			}
		}

		if ( 'login' === $purpose ) {
			$user_id = ( new AuthService() )->resolve_user_for_login_otp( $identifier, $channel );
			if ( is_wp_error( $user_id ) ) {
				$security = Settings::get( 'security' );
				return rest_ensure_response( array(
					'sent'     => true,
					'channel'  => $channel,
					'cooldown' => (int) ( $security['otp_resend_cooldown'] ?? 60 ),
				) );
			}
		}

		if ( 'reset' === $purpose ) {
			$user_id = ( new AuthService() )->resolve_user_for_reset( $identifier, $channel );
			if ( is_wp_error( $user_id ) ) {
				return rest_ensure_response( array(
					'sent'    => true,
					'channel' => $channel,
				) );
			}
		}

		$result = ( new OtpService() )->send( $identifier, $channel, $purpose );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( 'register' === $purpose ) {
			$extended = ( new PendingRegistrationService() )->extend( $pending_token, $identifier, $channel );
			if ( is_wp_error( $extended ) ) {
				return $extended;
			}
			$result['session_expires_at'] = $extended['session_expires_at'];
			$result['pending_token']      = $extended['pending_token'];
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Verify OTP and authenticate.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function verify( $request ) {
		$data = $request->get_json_params() ?: array();

		$spam = ( new SpamProtection() )->verify( $data );
		if ( is_wp_error( $spam ) ) {
			return $spam;
		}

		$identifier    = $this->sanitize_identifier( $data['identifier'] ?? '', $data['channel'] ?? 'email' );
		$code          = sanitize_text_field( $data['code'] ?? '' );
		$channel       = sanitize_key( $data['channel'] ?? 'email' );
		$pending_token = sanitize_text_field( $data['pending_token'] ?? '' );
		$purpose       = sanitize_key( $data['purpose'] ?? 'verify' );

		$auth_service = new AuthService();

		$validated = $this->validate_request_context( $identifier, $channel, $purpose, $pending_token );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$rate = $this->check_rate_limit( 'otp_verify', $identifier, $purpose );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		// For register: make sure we still have a valid pending session BEFORE
		// burning the OTP, so a stuck/expired session does not look like an
		// "invalid code" error.
		if ( 'register' === $purpose ) {
			$pending_check = ( new PendingRegistrationService() )->peek( $pending_token, $identifier, $channel );
			if ( is_wp_error( $pending_check ) ) {
				return $pending_check;
			}
		}

		$verified = ( new OtpService() )->verify( $identifier, $code, $channel, $purpose );
		if ( is_wp_error( $verified ) ) {
			$limiter = new RateLimiter();
			$ip      = RateLimiter::get_client_ip();
			$limiter->record_failure( 'otp_verify', $ip );
			$limiter->record_failure( 'otp_verify', $purpose . ':' . strtolower( $identifier ) );
			return $verified;
		}

		if ( 'reset' === $purpose ) {
			$user_id = $auth_service->resolve_user_for_reset( $identifier, $channel );
			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			$reset_token = $auth_service->issue_password_reset_token( $user_id );
			if ( is_wp_error( $reset_token ) ) {
				return $reset_token;
			}

			return rest_ensure_response( array(
				'requiresPasswordReset' => true,
				'reset_token'           => $reset_token,
			) );
		}

		if ( ! empty( $pending_token ) || 'register' === $purpose ) {
			$user_id = $auth_service->create_user_from_pending( $pending_token, $identifier, $channel );
			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			$auth_result = $auth_service->authenticate_user( $user_id, ! empty( $data['remember'] ) );
			if ( is_wp_error( $auth_result ) ) {
				return $auth_result;
			}

			$redirect = RedirectService::resolve_register( $user_id );
			$auth_result['redirect']        = $redirect['url'];
			$auth_result['redirect_action'] = $redirect['action'];
			StatsService::record_registration();

			return rest_ensure_response( $auth_result );
		}

		if ( 'login' !== $purpose ) {
			return rest_ensure_response( array( 'verified' => true ) );
		}

		$user_id = $auth_service->resolve_user_for_login_otp( $identifier, $channel );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$auth_result = $auth_service->authenticate_user( $user_id, ! empty( $data['remember'] ) );

		return rest_ensure_response( $auth_result );
	}

	/**
	 * Validate channel, purpose, and feature toggles for OTP requests.
	 *
	 * @param string $identifier    Email or phone.
	 * @param string $channel       email|phone.
	 * @param string $purpose       register|login|reset|verify.
	 * @param string $pending_token Pending registration token.
	 * @return true|\WP_Error
	 */
	private function validate_request_context( $identifier, $channel, $purpose, $pending_token = '' ) {
		if ( ! in_array( $channel, array( 'email', 'phone' ), true ) ) {
			return new \WP_Error( 'logixfast_auth_invalid_channel', __( 'Invalid verification channel.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		if ( ! in_array( $purpose, array( 'register', 'login', 'reset' ), true ) ) {
			return new \WP_Error( 'logixfast_auth_invalid_purpose', __( 'Invalid verification request.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		if ( empty( $identifier ) ) {
			return new \WP_Error( 'logixfast_auth_missing_identifier', __( 'Email or phone is required.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		if ( 'email' === $channel && ! is_email( $identifier ) ) {
			return new \WP_Error( 'logixfast_auth_invalid_email', __( 'Please enter a valid email address.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		if ( 'register' === $purpose && empty( $pending_token ) ) {
			return new \WP_Error( 'logixfast_auth_missing_pending_token', __( 'Registration session expired. Please sign up again.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		$auth = Settings::get( 'auth' );
		if ( 'login' === $purpose && empty( $auth['otp_login_enabled'] ) ) {
			return new \WP_Error( 'logixfast_auth_otp_login_disabled', __( 'Code login is not available.', 'logixfast-auth' ), array( 'status' => 403 ) );
		}

		if ( 'phone' === $channel && empty( $auth['phone_otp_enabled'] ) ) {
			return new \WP_Error( 'logixfast_auth_phone_otp_disabled', __( 'Phone verification is not available.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Sanitize identifier according to channel.
	 *
	 * @param mixed  $identifier Identifier.
	 * @param string $channel    Channel.
	 * @return string
	 */
	private function sanitize_identifier( $identifier, $channel ) {
		return 'email' === sanitize_key( $channel )
			? sanitize_email( $identifier )
			: sanitize_text_field( $identifier );
	}

	/**
	 * Apply both IP and identifier-scoped rate limits.
	 *
	 * @param string $action     Action.
	 * @param string $identifier Identifier.
	 * @param string $purpose    Purpose.
	 * @return true|\WP_Error
	 */
	private function check_rate_limit( $action, $identifier, $purpose ) {
		return RateLimiter::throttle_scoped( $action, $purpose . ':' . strtolower( $identifier ) );
	}
}
