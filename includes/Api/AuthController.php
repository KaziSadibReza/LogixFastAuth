<?php
/**
 * Authentication REST endpoints.
 *
 * @package SLR
 */

namespace SLR\Api;

use SLR\Services\AuthService;
use SLR\Services\StatsService;
use SLR\Services\OtpService;
use SLR\Services\RateLimiter;
use SLR\Services\SpamProtection;
use SLR\Settings;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AuthController
 */
class AuthController {

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'slr/v1',
			'/auth/register',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'register' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'slr/v1',
			'/auth/login',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'login' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'slr/v1',
			'/auth/logout',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'logout' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'slr/v1',
			'/auth/forgot-password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'forgot_password' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'slr/v1',
			'/auth/reset-password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'reset_password' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Register user.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function register( $request ) {
		$data = $request->get_json_params() ?: array();

		$spam = ( new SpamProtection() )->verify( $data );
		if ( is_wp_error( $spam ) ) {
			return $spam;
		}

		$rate = ( new RateLimiter() )->check( 'register', RateLimiter::get_client_ip() );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$auth           = Settings::get( 'auth' );
		$email_otp_on   = ! empty( $auth['email_otp_enabled'] );
		$phone_otp_on   = ! empty( $auth['phone_otp_enabled'] );
		$has_sms        = $phone_otp_on && ! empty( apply_filters( 'slr_sms_providers', array() ) );
		$has_phone_data = ! empty( $data['phone'] );
		$preferred      = sanitize_text_field( $data['preferred_channel'] ?? '' );

		// Determine which channel to send the OTP through.
		$otp_channel = null;
		if ( $email_otp_on && $has_sms && $has_phone_data ) {
			// Both channels are available — respect user's choice.
			$otp_channel = 'phone' === $preferred ? 'phone' : 'email';
		} elseif ( $email_otp_on ) {
			$otp_channel = 'email';
		} elseif ( $has_sms && $has_phone_data ) {
			$otp_channel = 'phone';
		}

		$auth_service = new AuthService();

		if ( $otp_channel ) {
			$result = $auth_service->stage_registration( $data, $otp_channel );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$identifier = 'phone' === $otp_channel
				? sanitize_text_field( $data['phone'] )
				: sanitize_email( $data['email'] );

			$otp = ( new OtpService() )->send( $identifier, $otp_channel, 'register' );
			if ( is_wp_error( $otp ) ) {
				return $otp;
			}

			return rest_ensure_response( array(
				'requiresOtp'        => true,
				'otpChannel'         => $otp_channel,
				'pending_token'      => $result['pending_token'],
				'email'              => $result['email'],
				'session_expires_at' => $result['session_expires_at'] ?? null,
			) );
		}

		$result = $auth_service->register( $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$login_result = $auth_service->authenticate_user( $result['user_id'] );

		if ( is_wp_error( $login_result ) ) {
			return $login_result;
		}

		StatsService::record_registration();

		return rest_ensure_response( array_merge( $result, $login_result ) );
	}

	/**
	 * Login user.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function login( $request ) {
		$data = $request->get_json_params() ?: array();

		$spam = ( new SpamProtection() )->verify( $data );
		if ( is_wp_error( $spam ) ) {
			return $spam;
		}

		$rate = ( new RateLimiter() )->check( 'login', RateLimiter::get_client_ip() );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$auth = Settings::get( 'auth' );

		if ( ! empty( $auth['otp_login_enabled'] ) && empty( $data['password'] ) ) {
			$channel = sanitize_text_field( $data['channel'] ?? '' );
			if ( ! in_array( $channel, array( 'email', 'phone' ), true ) ) {
				$channel = ! empty( $data['phone'] ) && empty( $data['email'] ) ? 'phone' : 'email';
			}

			$has_sms = ! empty( apply_filters( 'slr_sms_providers', array() ) );

			if ( 'phone' === $channel ) {
				if ( empty( $auth['phone_otp_enabled'] ) || ! $has_sms ) {
					return new \WP_Error(
						'slr_phone_otp_disabled',
						__( 'Phone verification is not available.', 'smart-login-registration' ),
						array( 'status' => 400 )
					);
				}
				$identifier = sanitize_text_field( $data['phone'] ?? '' );
			} else {
				$identifier = sanitize_email( $data['email'] ?? '' );
				if ( ! is_email( $identifier ) ) {
					return new \WP_Error(
						'slr_invalid_email',
						__( 'Please enter a valid email address.', 'smart-login-registration' ),
						array( 'status' => 400 )
					);
				}
			}

			if ( empty( $identifier ) ) {
				return new \WP_Error(
					'slr_missing_identifier',
					__( 'Email or phone is required.', 'smart-login-registration' ),
					array( 'status' => 400 )
				);
			}

			$user_id = ( new AuthService() )->resolve_user_for_login_otp( $identifier, $channel );
			if ( is_wp_error( $user_id ) ) {
				return rest_ensure_response( array(
					'requiresOtp' => true,
					'otpChannel'  => $channel,
				) );
			}

			$otp = ( new OtpService() )->send( $identifier, $channel, 'login' );
			if ( is_wp_error( $otp ) ) {
				return $otp;
			}

			return rest_ensure_response( array(
				'requiresOtp' => true,
				'otpChannel'  => $channel,
			) );
		}

		$result = ( new AuthService() )->login( $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Logout user.
	 *
	 * @return \WP_REST_Response
	 */
	public function logout() {
		$result = ( new AuthService() )->logout();
		return rest_ensure_response( $result );
	}

	/**
	 * Send password reset OTP.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function forgot_password( $request ) {
		$data = $request->get_json_params() ?: array();

		$spam = ( new SpamProtection() )->verify( $data );
		if ( is_wp_error( $spam ) ) {
			return $spam;
		}

		$rate = ( new RateLimiter() )->check( 'forgot_password', RateLimiter::get_client_ip() );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$email = sanitize_email( $data['email'] ?? '' );
		$phone = sanitize_text_field( $data['phone'] ?? '' );

		$channel    = ! empty( $phone ) ? 'phone' : 'email';
		$identifier = 'phone' === $channel ? $phone : $email;

		if ( empty( $identifier ) ) {
			return new \WP_Error( 'slr_missing_identifier', __( 'Email or phone is required.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		$auth = Settings::get( 'auth' );
		if ( 'phone' === $channel && empty( $auth['phone_otp_enabled'] ) ) {
			return new \WP_Error( 'slr_phone_reset_disabled', __( 'Phone reset is not available.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		$auth_service = new AuthService();
		$user_id      = $auth_service->resolve_user_for_reset( $identifier, $channel );

		if ( is_wp_error( $user_id ) ) {
			return rest_ensure_response( array(
				'sent'    => true,
				'channel' => $channel,
			) );
		}

		$otp = ( new OtpService() )->send( $identifier, $channel, 'reset' );
		if ( is_wp_error( $otp ) ) {
			return $otp;
		}

		return rest_ensure_response( array(
			'sent'    => true,
			'channel' => $channel,
		) );
	}

	/**
	 * Set new password after OTP verification.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function reset_password( $request ) {
		$data = $request->get_json_params() ?: array();

		$rate = ( new RateLimiter() )->check( 'reset_password', RateLimiter::get_client_ip() );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$reset_token = sanitize_text_field( $data['reset_token'] ?? '' );
		$password    = $data['password'] ?? '';

		$result = ( new AuthService() )->complete_password_reset( $reset_token, $password );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}
}
