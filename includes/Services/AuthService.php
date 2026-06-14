<?php
/**
 * Core authentication service.
 *
 * @package SLR
 */

namespace SLR\Services;

use SLR\Database\WebAuthnRepository;
use SLR\Integrations\Tutor_Sync;
use SLR\Integrations\WooCommerce_Sync;
use SLR\Settings;
use WP_Error;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AuthService
 */
class AuthService {

	/**
	 * Validate registration input.
	 *
	 * @param array $data Registration data.
	 * @return array|WP_Error Sanitized data.
	 */
	public function validate_registration( $data ) {
		$full_name = sanitize_text_field( $data['full_name'] ?? '' );
		$email     = sanitize_email( $data['email'] ?? '' );
		$phone     = sanitize_text_field( $data['phone'] ?? '' );
		$password  = $data['password'] ?? '';

		if ( empty( $full_name ) || empty( $email ) || empty( $password ) ) {
			return new WP_Error( 'slr_missing_fields', __( 'Please fill in all required fields.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		$settings = Settings::get( 'auth' );
		if ( ! empty( $settings['require_phone'] ) && empty( $phone ) ) {
			return new WP_Error( 'slr_missing_phone', __( 'Phone number is required.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'slr_invalid_email', __( 'Please enter a valid email address.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		if ( strlen( (string) $password ) < 8 ) {
			return new WP_Error( 'slr_password_short', __( 'Password must be at least 8 characters.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		if ( email_exists( $email ) ) {
			return new WP_Error( 'slr_email_exists', __( 'An account with this email already exists.', 'smart-login-registration' ), array( 'status' => 409 ) );
		}

		if ( ! empty( $phone ) ) {
			$phone_validator = new PhoneValidator();
			$validated       = $phone_validator->validate( $phone );
			if ( is_wp_error( $validated ) ) {
				return $validated;
			}
			$phone = $validated;

			if ( $this->resolve_user_id_by_phone( $phone ) ) {
				return new WP_Error( 'slr_phone_exists', __( 'An account with this phone number already exists.', 'smart-login-registration' ), array( 'status' => 409 ) );
			}
		}

		return array(
			'full_name' => $full_name,
			'email'     => $email,
			'phone'     => $phone,
			'password'  => $password,
		);
	}

	/**
	 * Stage registration until OTP verification (no WordPress user created).
	 *
	 * @param array  $data    Registration data.
	 * @param string $channel email|phone.
	 * @return array|WP_Error
	 */
	public function stage_registration( $data, $channel = 'email' ) {
		$validated = $this->validate_registration( $data );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$validated['otp_channel'] = $channel;

		return ( new PendingRegistrationService() )->stage( $validated );
	}

	/**
	 * Create user from staged registration after OTP verification.
	 *
	 * @param string $token       Pending token.
	 * @param string $identifier  OTP identifier.
	 * @param string $channel     OTP channel.
	 * @return int|WP_Error User ID.
	 */
	public function create_user_from_pending( $token, $identifier, $channel = 'email' ) {
		$pending = ( new PendingRegistrationService() )->consume( $token, $identifier, $channel );
		if ( is_wp_error( $pending ) ) {
			return $pending;
		}

		if ( email_exists( $pending['email'] ) ) {
			return new WP_Error( 'slr_email_exists', __( 'An account with this email already exists.', 'smart-login-registration' ), array( 'status' => 409 ) );
		}

		if ( ! empty( $pending['phone'] ) && $this->resolve_user_id_by_phone( $pending['phone'] ) ) {
			return new WP_Error( 'slr_phone_exists', __( 'An account with this phone number already exists.', 'smart-login-registration' ), array( 'status' => 409 ) );
		}

		return $this->create_user( $pending );
	}

	/**
	 * Register a new user immediately (no OTP).
	 *
	 * @param array $data Registration data.
	 * @return array|WP_Error
	 */
	public function register( $data ) {
		$validated = $this->validate_registration( $data );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$user_id = $this->create_user( $validated );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$redirect = RedirectService::resolve_register( $user_id );

		return array(
			'user_id'         => $user_id,
			'email'           => $validated['email'],
			'redirect'        => $redirect['url'],
			'redirect_action' => $redirect['action'],
		);
	}

	/**
	 * Insert WordPress user and run integrations.
	 *
	 * @param array $data Registration data.
	 * @return int|WP_Error User ID.
	 */
	private function create_user( $data ) {
		$full_name = $data['full_name'];
		$email     = $data['email'];
		$phone     = $data['phone'] ?? '';
		$password  = $data['password'];

		$name_parts = $this->split_name( $full_name );
		$username   = $this->generate_username( $email );

		if ( ! empty( $phone ) && $this->resolve_user_id_by_phone( $phone ) ) {
			return new WP_Error( 'slr_phone_exists', __( 'An account with this phone number already exists.', 'smart-login-registration' ), array( 'status' => 409 ) );
		}

		$user_id = wp_insert_user(
			array(
				'user_login' => $username,
				'user_email' => $email,
				'user_pass'  => $password,
				'first_name' => $name_parts['first'],
				'last_name'  => $name_parts['last'],
				'role'       => 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return new WP_Error( 'slr_register_failed', $user_id->get_error_message(), array( 'status' => 500 ) );
		}

		if ( ! empty( $phone ) ) {
			update_user_meta( $user_id, 'slr_phone', $phone );
		}

		$profile_data = array(
			'full_name' => $full_name,
			'email'     => $email,
			'phone'     => $phone,
		);

		$this->finalize_registration( $user_id, $profile_data, $data );

		return $user_id;
	}

	/**
	 * Run post-registration integrations and hooks.
	 *
	 * @param int   $user_id      User ID.
	 * @param array $profile_data Profile fields for integrations.
	 * @param array $data         Full registration payload for hooks.
	 * @return void
	 */
	private function finalize_registration( $user_id, $profile_data, $data ) {
		WooCommerce_Sync::sync_user( $user_id, $profile_data );

		Tutor_Sync::on_register(
			$user_id,
			array(
				'phone' => $profile_data['phone'] ?? '',
			)
		);

		do_action( 'slr_user_registered', $user_id, $data );
	}

	/**
	 * Login with email/phone and password.
	 *
	 * @param array $data Login data.
	 * @return array|WP_Error
	 */
	public function login( $data ) {
		$identifier = trim( (string) ( $data['email'] ?? '' ) );
		$phone      = sanitize_text_field( $data['phone'] ?? '' );
		$password = $data['password'] ?? '';
		$remember = ! empty( $data['remember'] );

		if ( '' === $identifier && '' !== $phone ) {
			$identifier = $phone;
		}

		if ( empty( $identifier ) || empty( $password ) ) {
			return new WP_Error( 'slr_missing_credentials', __( 'Email or phone and password are required.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		$user = $this->resolve_user_for_password_login( $identifier );
		if ( ! $user ) {
			return new WP_Error( 'slr_invalid_credentials', __( 'Invalid email/phone or password.', 'smart-login-registration' ), array( 'status' => 401 ) );
		}

		$credentials = array(
			'user_login'    => $user->user_login,
			'user_password' => $password,
			'remember'      => $remember,
		);

		$credentials = apply_filters( 'slr_login_credentials', $credentials, $user );

		// Clear any existing auth cookies before issuing a new session.
		wp_clear_auth_cookie();

		$new_logged_in_cookie = '';
		$cookie_capture       = function ( $cookie ) use ( &$new_logged_in_cookie ) {
			$new_logged_in_cookie = $cookie;
		};
		add_action( 'set_logged_in_cookie', $cookie_capture );

		$signed_in = wp_signon( $credentials, is_ssl() );

		remove_action( 'set_logged_in_cookie', $cookie_capture );

		if ( is_wp_error( $signed_in ) ) {
			do_action( 'slr_login_failed', $signed_in, $user );
			return new WP_Error( 'slr_login_failed', __( 'Invalid email/phone or password.', 'smart-login-registration' ), array( 'status' => 401 ) );
		}

		if ( $new_logged_in_cookie ) {
			$_COOKIE[ LOGGED_IN_COOKIE ] = $new_logged_in_cookie;
		}

		// wp_signon() does NOT call wp_set_current_user(), so the global
		// $current_user is still the anonymous user from the start of this REST
		// request. Without this, wp_create_nonce() below would bind the nonce
		// to uid=0, and the next REST call (which authenticates as the real
		// user via the new cookie) would fail with "Cookie check failed".
		wp_set_current_user( $signed_in->ID );

		Tutor_Sync::on_login_success( $signed_in );
		StatsService::record_login();

		$redirect     = RedirectService::resolve_login( $signed_in );
		$has_passkeys = ! empty( ( new WebAuthnRepository() )->get_by_user( $signed_in->ID ) );

		return array(
			'user_id'         => $signed_in->ID,
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'has_passkeys'    => $has_passkeys,
			'redirect'        => $redirect['url'],
			'redirect_action' => $redirect['action'],
		);
	}

	/**
	 * Resolve a password-login identifier to a WordPress user.
	 *
	 * @param string $identifier Email or phone number.
	 * @return WP_User|null
	 */
	private function resolve_user_for_password_login( $identifier ) {
		$identifier = trim( (string) $identifier );

		if ( is_email( $identifier ) ) {
			$user = get_user_by( 'email', sanitize_email( $identifier ) );
			return $user instanceof WP_User ? $user : null;
		}

		$user_id = $this->resolve_user_id_by_phone( $identifier );
		if ( ! $user_id ) {
			return null;
		}

		$user = get_user_by( 'id', $user_id );
		return $user instanceof WP_User ? $user : null;
	}

	/**
	 * Resolve a user by phone across SLR, WooCommerce, and Tutor profile fields.
	 *
	 * @param string $phone Phone number.
	 * @return int
	 */
	private function resolve_user_id_by_phone( $phone ) {
		$candidates = $this->get_phone_login_candidates( $phone );
		if ( empty( $candidates ) ) {
			return 0;
		}

		foreach ( array( 'slr_phone', 'billing_phone', 'phone_number' ) as $meta_key ) {
			$users = get_users(
				array(
					'meta_key'     => $meta_key,
					'meta_value'   => $candidates,
					'meta_compare' => 'IN',
					'number'       => 1,
					'fields'       => 'ID',
				)
			);

			if ( ! empty( $users ) ) {
				return (int) $users[0];
			}
		}

		return 0;
	}

	/**
	 * Build possible stored phone formats for login lookup.
	 *
	 * @param string $phone Phone number.
	 * @return string[]
	 */
	private function get_phone_login_candidates( $phone ) {
		$phone      = sanitize_text_field( $phone );
		$candidates = array_filter( array( $phone ) );

		$validated = ( new PhoneValidator() )->validate( $phone );
		if ( ! is_wp_error( $validated ) ) {
			$candidates[] = $validated;
		}

		$digits = preg_replace( '/\D+/', '', $phone );
		if ( ! empty( $digits ) ) {
			$candidates[] = $digits;
			$candidates[] = '+' . $digits;
		}

		return array_values( array_unique( array_filter( $candidates ) ) );
	}

	/**
	 * Set auth cookie for a user (after OTP/WebAuthn).
	 *
	 * @param int  $user_id  User ID.
	 * @param bool $remember Remember login.
	 * @return array
	 */
	public function authenticate_user( $user_id, $remember = false ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return new WP_Error( 'slr_user_not_found', __( 'User not found.', 'smart-login-registration' ), array( 'status' => 404 ) );
		}

		wp_clear_auth_cookie();
		wp_set_current_user( $user_id );

		$new_logged_in_cookie = '';
		$cookie_capture       = function ( $cookie ) use ( &$new_logged_in_cookie ) {
			$new_logged_in_cookie = $cookie;
		};
		add_action( 'set_logged_in_cookie', $cookie_capture );

		wp_set_auth_cookie( $user_id, $remember, is_ssl() );

		remove_action( 'set_logged_in_cookie', $cookie_capture );

		// Populate $_COOKIE with the freshly-issued logged-in cookie so that
		// wp_get_session_token() returns the NEW session token, otherwise the
		// nonce we generate here would be bound to the OLD (anonymous) session
		// and fail validation on the next REST call ("Cookie check failed").
		if ( $new_logged_in_cookie ) {
			$_COOKIE[ LOGGED_IN_COOKIE ] = $new_logged_in_cookie;
		}

		do_action( 'wp_login', $user->user_login, $user );

		Tutor_Sync::on_login_success( $user );
		StatsService::record_login();

		$redirect     = RedirectService::resolve_login( $user );
		$has_passkeys = ! empty( ( new WebAuthnRepository() )->get_by_user( $user_id ) );

		return array(
			'user_id'         => $user_id,
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'has_passkeys'    => $has_passkeys,
			'redirect'        => $redirect['url'],
			'redirect_action' => $redirect['action'],
		);
	}

	/**
	 * Find user for passwordless OTP login.
	 *
	 * @param string $identifier Email or phone.
	 * @param string $channel    email|phone.
	 * @return int|WP_Error
	 */
	public function resolve_user_for_login_otp( $identifier, $channel = 'email' ) {
		if ( 'phone' === $channel ) {
			$user_id = $this->resolve_user_id_by_phone( $identifier );

			if ( ! $user_id ) {
				return new WP_Error(
					'slr_user_not_found',
					__( 'No account found with that phone number.', 'smart-login-registration' ),
					array( 'status' => 404 )
				);
			}

			return $user_id;
		}

		$user = get_user_by( 'email', sanitize_email( $identifier ) );
		if ( ! $user ) {
			return new WP_Error(
				'slr_user_not_found',
				__( 'No account found with that email.', 'smart-login-registration' ),
				array( 'status' => 404 )
			);
		}

		return (int) $user->ID;
	}

	/**
	 * Find user for password reset by email or phone.
	 *
	 * @param string $identifier Email or phone.
	 * @param string $channel    email|phone.
	 * @return int|WP_Error
	 */
	public function resolve_user_for_reset( $identifier, $channel = 'email' ) {
		if ( 'email' === $channel ) {
			$user = get_user_by( 'email', sanitize_email( $identifier ) );
			if ( ! $user ) {
				return new WP_Error( 'slr_user_not_found', __( 'No account found with that email.', 'smart-login-registration' ), array( 'status' => 404 ) );
			}
			return (int) $user->ID;
		}

		$user_id = $this->resolve_user_id_by_phone( $identifier );
		if ( ! $user_id ) {
			return new WP_Error( 'slr_user_not_found', __( 'No account found with that phone number.', 'smart-login-registration' ), array( 'status' => 404 ) );
		}

		return $user_id;
	}

	/**
	 * Issue a short-lived token after reset OTP verification.
	 *
	 * @param int $user_id User ID.
	 * @return string|WP_Error
	 */
	public function issue_password_reset_token( $user_id ) {
		$security = Settings::get( 'security' );
		$ttl      = (int) ( $security['otp_ttl'] ?? 600 );
		$token    = bin2hex( random_bytes( 32 ) );

		set_transient(
			'slr_pwreset_' . $token,
			array(
				'user_id' => (int) $user_id,
			),
			$ttl
		);

		return $token;
	}

	/**
	 * Complete password reset with verified token.
	 *
	 * @param string $reset_token Reset token.
	 * @param string $password    New password.
	 * @return array|WP_Error
	 */
	public function complete_password_reset( $reset_token, $password ) {
		if ( empty( $reset_token ) || empty( $password ) ) {
			return new WP_Error( 'slr_missing_fields', __( 'Please fill in all required fields.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		if ( strlen( $password ) < 8 ) {
			return new WP_Error( 'slr_password_short', __( 'Password must be at least 8 characters.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		$payload = get_transient( 'slr_pwreset_' . $reset_token );
		if ( ! is_array( $payload ) || empty( $payload['user_id'] ) ) {
			return new WP_Error( 'slr_reset_expired', __( 'Reset session expired. Please start again.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		$user_id = (int) $payload['user_id'];
		$user    = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return new WP_Error( 'slr_user_not_found', __( 'User not found.', 'smart-login-registration' ), array( 'status' => 404 ) );
		}

		wp_set_password( $password, $user_id );
		delete_transient( 'slr_pwreset_' . $reset_token );

		// Invalidate all existing sessions for this user so that any
		// previously-issued auth cookies can no longer be used after the
		// password has been changed.
		$session_manager = \WP_Session_Tokens::get_instance( $user_id );
		$session_manager->destroy_all();

		return array(
			'success' => true,
			'message' => __( 'Password updated. You can sign in now.', 'smart-login-registration' ),
		);
	}

	/**
	 * Logout current user.
	 *
	 * @return array
	 */
	public function logout() {
		wp_logout();
		return array(
			'redirect' => home_url( '/' ),
		);
	}

	/**
	 * Split full name into first/last.
	 *
	 * @param string $full_name Full name.
	 * @return array
	 */
	private function split_name( $full_name ) {
		$parts = preg_split( '/\s+/', trim( $full_name ), 2 );
		return array(
			'first' => $parts[0] ?? '',
			'last'  => $parts[1] ?? '',
		);
	}

	/**
	 * Generate unique username from email.
	 *
	 * @param string $email Email address.
	 * @return string
	 */
	private function generate_username( $email ) {
		$base = sanitize_user( strstr( $email, '@', true ), true );
		if ( empty( $base ) ) {
			$base = 'user';
		}

		$username = $base;
		$counter  = 1;

		while ( username_exists( $username ) ) {
			$username = $base . $counter;
			++$counter;
		}

		return $username;
	}

}
