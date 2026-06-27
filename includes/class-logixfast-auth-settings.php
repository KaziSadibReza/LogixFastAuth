<?php
/**
 * Plugin settings manager.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Integrations\Integration_Availability;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 */
class Settings {

	const OPTION_KEY = 'logixfast_auth_settings';

	/**
	 * Set default options on activation.
	 *
	 * @return void
	 */
	public static function set_defaults() {
		if ( get_option( self::OPTION_KEY ) ) {
			return;
		}

		update_option( self::OPTION_KEY, self::get_default_settings() );
	}

	/**
	 * Default settings array.
	 *
	 * @return array
	 */
	public static function get_default_settings() {
		return array(
			'general'        => array(
				'dedicated_page_id'        => 0,
				'default_mode'             => 'login',
				'login_redirect_type'      => 'stay',
				'login_redirect_page_id'   => 0,
				'login_redirect_url'       => '',
				'register_redirect_type'     => 'stay',
				'register_redirect_page_id'  => 0,
				'register_redirect_url'      => '',
				'login_page_logged_in_redirect_type'     => 'default',
				'login_page_logged_in_redirect_page_id'  => 0,
				'login_page_logged_in_redirect_url'      => '',
				'honeypot_enabled'         => true,
			),
			'auth'           => array(
				'email_otp_enabled'   => false,
				'phone_otp_enabled'   => false,
				'webauthn_enabled'    => false,
				'otp_login_enabled'   => false,
				'require_phone'       => true,
			),
			'mail'           => array(
				'transport'       => 'wp_mail',
				'smtp_host'       => '',
				'smtp_port'       => 587,
				'smtp_encryption' => 'tls',
				'smtp_user'       => '',
				'smtp_pass'       => '',
				'from_email'      => get_option( 'admin_email' ),
				'from_name'       => get_bloginfo( 'name' ),
				'google_connected'       => false,
				'google_refresh_token'   => '',
				'google_client_id'       => '',
				'google_client_secret'   => '',
				'google_account_email'   => '',
			),
		'integrations'   => array(
			'replace_wp_login'        => false,
			'replace_woocommerce'     => false,
			'replace_tutor'           => false,
			'replace_elementor'       => false,
		),
			'appearance'     => array(
				'primary'    => '#d6336c',
				'background' => '#ffffff',
				'text'       => '#1e293b',
				'blur'       => '24px',
				'radius'     => '12px',
				'spacing'    => '1rem',
			),
			'security'       => array(
				'rate_limit_attempts' => 10,
				'rate_limit_window'   => 300,
				'otp_ttl'             => 600,
				'otp_resend_cooldown' => 60,
				'otp_max_attempts'      => 5,
				'webauthn_rp_id'      => '',
			),
		);
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_all() {
		$stored   = get_option( self::OPTION_KEY, array() );
		$defaults = self::get_default_settings();
		return self::array_merge_deep( $defaults, is_array( $stored ) ? $stored : array() );
	}

	/**
	 * Get a settings section.
	 *
	 * @param string $section Section key.
	 * @return array
	 */
	public static function get( $section ) {
		$all = self::get_all();
		return isset( $all[ $section ] ) ? $all[ $section ] : array();
	}

	/**
	 * Update settings section.
	 *
	 * @param string $section Section key.
	 * @param array  $data    Section data.
	 * @return bool
	 */
	public static function update( $section, $data ) {
		$stored   = get_option( self::OPTION_KEY, array() );
		$defaults = self::get_default_settings();

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$stored[ $section ] = array_merge(
			$defaults[ $section ] ?? array(),
			$stored[ $section ] ?? array(),
			$data
		);

		return update_option( self::OPTION_KEY, $stored );
	}

	/**
	 * Normalize a stored settings flag to boolean.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public static function to_bool( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Get public frontend config (no secrets).
	 *
	 * @return array
	 */
	public static function get_public_config() {
		$all = self::get_all();

		$popup_css    = \LogixFastAuth\Assets::get_entry_css_files( \LogixFastAuth\Assets::ENTRY_POPUP );
		$integrations = $all['integrations'] ?? array();
		$page_id      = (int) ( $all['general']['dedicated_page_id'] ?? 0 );
		$dedicated_url = $page_id ? ( get_permalink( $page_id ) ?: '' ) : '';
		$redirect_to = '';
		$raw_redirect = filter_input( INPUT_GET, 'redirect_to', FILTER_UNSAFE_RAW );
		if ( is_string( $raw_redirect ) && '' !== $raw_redirect ) {
			$raw_redirect = sanitize_url( wp_unslash( $raw_redirect ) );
			if ( '' !== $raw_redirect ) {
				$redirect_to = wp_validate_redirect( $raw_redirect, '' );
			}
		}

		return array(
			'apiUrl'      => rest_url( 'logixfast-auth/v1' ),
			'assets'      => array(
				'popupJs'  => \LogixFastAuth\Assets::get_entry_file_url( \LogixFastAuth\Assets::ENTRY_POPUP, 'frontend/popup.js' ),
				'popupCss' => ! empty( $popup_css[0] ) ? $popup_css[0] : \LogixFastAuth\Assets::get_asset_url( 'frontend/main.css' ),
			),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'homeUrl'     => home_url( '/' ),
			'isDedicated'       => self::is_dedicated_page(),
			'dedicatedLoginUrl' => $dedicated_url,
			'redirectTo'        => $redirect_to,
			'integrations'      => array(
				'replaceTutor'     => Integration_Availability::is_tutor_available() && self::to_bool( $integrations['replace_tutor'] ?? false ),
				'replaceElementor' => Integration_Availability::is_elementor_available() && self::to_bool( $integrations['replace_elementor'] ?? false ),
			),
			'defaultMode' => $all['general']['default_mode'],
			'otpTtl'                 => (int) ( $all['security']['otp_ttl'] ?? 600 ),
			'registrationSessionTtl' => max( (int) ( $all['security']['otp_ttl'] ?? 600 ) * 6, 3600 ),
			'auth'        => array(
				'emailOtp'    => (bool) $all['auth']['email_otp_enabled'],
				'phoneOtp'    => (bool) $all['auth']['phone_otp_enabled'],
				'webauthn'    => (bool) $all['auth']['webauthn_enabled'],
				'otpLogin'    => (bool) $all['auth']['otp_login_enabled'],
				'requirePhone' => (bool) $all['auth']['require_phone'],
				'hasSmsProvider' => ! empty( apply_filters( 'logixfast_auth_sms_providers', array() ) ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LogixFastAuth plugin hook.
			),
			'style'       => $all['appearance'],
			'redirects'   => \LogixFastAuth\Services\RedirectService::get_public_config(),
			'i18n'        => array(
				'login'              => __( 'Log In', 'logixfast-auth' ),
				'register'           => __( 'Register', 'logixfast-auth' ),
				'loginTitle'         => __( 'Welcome back', 'logixfast-auth' ),
				'loginSubtitle'      => __( 'Sign in to continue.', 'logixfast-auth' ),
				'registerTitle'      => __( 'Create your account', 'logixfast-auth' ),
				'registerSubtitle'   => __( 'Just a few details to get started.', 'logixfast-auth' ),
				'fullName'           => __( 'Full Name', 'logixfast-auth' ),
				'email'              => __( 'Email', 'logixfast-auth' ),
				'emailOrPhone'       => __( 'Email or phone number', 'logixfast-auth' ),
				'phone'              => __( 'Phone Number', 'logixfast-auth' ),
				'password'           => __( 'Password', 'logixfast-auth' ),
				'confirmPassword'    => __( 'Confirm Password', 'logixfast-auth' ),
				'passwordsMismatch'  => __( 'Passwords do not match.', 'logixfast-auth' ),
				'submitLogin'        => __( 'Sign In', 'logixfast-auth' ),
				'submitRegister'     => __( 'Create Account', 'logixfast-auth' ),
				'close'              => __( 'Close', 'logixfast-auth' ),
				'or'                 => __( 'or', 'logixfast-auth' ),
				'usePasskey'         => __( 'Sign in with Passkey', 'logixfast-auth' ),
				'signInWithCode'     => __( 'Sign in with code', 'logixfast-auth' ),
				'otpLoginTitle'      => __( 'Sign in with code', 'logixfast-auth' ),
				'otpLoginSubtitle'   => __( 'We will send a 6-digit code to verify it is you.', 'logixfast-auth' ),
				'sendLoginCode'      => __( 'Send verification code', 'logixfast-auth' ),
				'usePasswordInstead' => __( 'Use password instead', 'logixfast-auth' ),
				'otpChannelEmail'    => __( 'Email', 'logixfast-auth' ),
				'otpChannelPhone'    => __( 'Phone', 'logixfast-auth' ),
				'verifyVia'          => __( 'Verify your account via', 'logixfast-auth' ),
				'verifyOtp'          => __( 'Verify Code', 'logixfast-auth' ),
				'resendOtp'          => __( 'Resend Code', 'logixfast-auth' ),
				'otpSent'            => __( 'We sent a 6-digit code to', 'logixfast-auth' ),
				'loading'            => __( 'Loading...', 'logixfast-auth' ),
				'remember'           => __( 'Remember me', 'logixfast-auth' ),
				'errorGeneric'       => __( 'Something went wrong. Please try again.', 'logixfast-auth' ),
				'errorRequired'      => __( 'Please fill in all required fields.', 'logixfast-auth' ),
				'errorLoginRequired'   => __( 'Email or phone and password are required.', 'logixfast-auth' ),
				'errorInvalidEmail'    => __( 'Please enter a valid email address.', 'logixfast-auth' ),
				'errorPhone'         => __( 'Please enter a valid phone number.', 'logixfast-auth' ),
				'errorPasswordMin'     => __( 'Password must be at least 8 characters.', 'logixfast-auth' ),
				'errorOtp'           => __( 'Please enter the 6-digit code.', 'logixfast-auth' ),
				'otpResent'          => __( 'A new code has been sent.', 'logixfast-auth' ),
				'forgotPassword'     => __( 'Forgot password?', 'logixfast-auth' ),
				'forgotPasswordHint' => __( 'Enter your email or phone and we will send a verification code.', 'logixfast-auth' ),
				'sendResetCode'      => __( 'Send reset code', 'logixfast-auth' ),
				'backToLogin'        => __( 'Back to sign in', 'logixfast-auth' ),
				'useEmailInstead'    => __( 'Use email instead', 'logixfast-auth' ),
				'usePhoneInstead'    => __( 'Use phone instead', 'logixfast-auth' ),
				'newPassword'        => __( 'Set new password', 'logixfast-auth' ),
				'newPasswordHint'    => __( 'Choose a strong password for your account.', 'logixfast-auth' ),
				'updatePassword'     => __( 'Update password', 'logixfast-auth' ),
				'passwordResetSuccess' => __( 'Password updated. You can sign in now.', 'logixfast-auth' ),
				'continueOtp'      => __( 'Continue verification', 'logixfast-auth' ),
				'otpInProgress'      => __( 'Verification in progress for', 'logixfast-auth' ),
				'sessionExpired'     => __( 'Registration session expired. Please create your account again.', 'logixfast-auth' ),
				'passkeySetupTitle'  => __( 'Set up a passkey', 'logixfast-auth' ),
				'passkeySetupDesc'   => __( 'Sign in faster next time with Face ID, Touch ID, or your device PIN. No password needed.', 'logixfast-auth' ),
				'passkeySetupBtn'    => __( 'Set up passkey', 'logixfast-auth' ),
				'passkeySkip'        => __( 'Not now', 'logixfast-auth' ),
				'passkeyRegistered'  => __( 'Passkey registered! You can use it to sign in next time.', 'logixfast-auth' ),
			),
		);
	}

	/**
	 * Check if current request is the dedicated login page.
	 *
	 * @return bool
	 */
	public static function is_dedicated_page() {
		if ( ! is_page() ) {
			return false;
		}
		$general = self::get( 'general' );
		$page_id = (int) ( $general['dedicated_page_id'] ?? 0 );
		return $page_id > 0 && is_page( $page_id );
	}

	/**
	 * Deep merge arrays.
	 *
	 * @param array $defaults Defaults.
	 * @param array $custom   Custom values.
	 * @return array
	 */
	private static function array_merge_deep( $defaults, $custom ) {
		foreach ( $custom as $key => $value ) {
			if ( is_array( $value ) && isset( $defaults[ $key ] ) && is_array( $defaults[ $key ] ) ) {
				$defaults[ $key ] = self::array_merge_deep( $defaults[ $key ], $value );
			} else {
				$defaults[ $key ] = $value;
			}
		}
		return $defaults;
	}
}
