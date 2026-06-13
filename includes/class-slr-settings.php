<?php
/**
 * Plugin settings manager.
 *
 * @package SLR
 */

namespace SLR;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 */
class Settings {

	const OPTION_KEY = 'slr_settings';

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

		$popup_css    = \SLR\Assets::get_entry_css_files( \SLR\Assets::ENTRY_POPUP );
		$integrations = $all['integrations'] ?? array();
		$page_id      = (int) ( $all['general']['dedicated_page_id'] ?? 0 );
		$dedicated_url = $page_id ? ( get_permalink( $page_id ) ?: '' ) : '';
		$redirect_to   = '';

		if ( ! empty( $_GET['redirect_to'] ) ) {
			$redirect_to = wp_validate_redirect( wp_unslash( $_GET['redirect_to'] ), '' );
		}

		return array(
			'apiUrl'      => rest_url( 'slr/v1' ),
			'assets'      => array(
				'popupJs'  => \SLR\Assets::get_entry_file_url( \SLR\Assets::ENTRY_POPUP, 'frontend/popup.js' ),
				'popupCss' => ! empty( $popup_css[0] ) ? $popup_css[0] : \SLR\Assets::get_asset_url( 'frontend/main.css' ),
			),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'homeUrl'     => home_url( '/' ),
			'isDedicated'       => self::is_dedicated_page(),
			'dedicatedLoginUrl' => $dedicated_url,
			'redirectTo'        => $redirect_to,
			'integrations'      => array(
				'replaceTutor' => self::to_bool( $integrations['replace_tutor'] ?? false ),
			),
			'defaultMode' => $all['general']['default_mode'],
			'otpTtl'                 => (int) ( $all['security']['otp_ttl'] ?? 600 ),
			'registrationSessionTtl' => max( (int) ( $all['security']['otp_ttl'] ?? 600 ) * 6, 3600 ),
			'auth'        => array(
				'emailOtp'    => (bool) $all['auth']['email_otp_enabled'],
				'phoneOtp'    => (bool) $all['auth']['phone_otp_enabled'],
				'webauthn'    => false,
				'otpLogin'    => (bool) $all['auth']['otp_login_enabled'],
				'requirePhone' => (bool) $all['auth']['require_phone'],
				'hasSmsProvider' => ! empty( apply_filters( 'slr_sms_providers', array() ) ),
			),
			'style'       => $all['appearance'],
			'redirects'   => \SLR\Services\RedirectService::get_public_config(),
			'i18n'        => array(
				'login'              => __( 'Log In', 'smart-login-registration' ),
				'register'           => __( 'Register', 'smart-login-registration' ),
				'loginTitle'         => __( 'Welcome back', 'smart-login-registration' ),
				'loginSubtitle'      => __( 'Sign in to continue.', 'smart-login-registration' ),
				'registerTitle'      => __( 'Create your account', 'smart-login-registration' ),
				'registerSubtitle'   => __( 'Just a few details to get started.', 'smart-login-registration' ),
				'fullName'           => __( 'Full Name', 'smart-login-registration' ),
				'email'              => __( 'Email', 'smart-login-registration' ),
				'emailOrPhone'       => __( 'Email or phone number', 'smart-login-registration' ),
				'phone'              => __( 'Phone Number', 'smart-login-registration' ),
				'password'           => __( 'Password', 'smart-login-registration' ),
				'confirmPassword'    => __( 'Confirm Password', 'smart-login-registration' ),
				'passwordsMismatch'  => __( 'Passwords do not match.', 'smart-login-registration' ),
				'submitLogin'        => __( 'Sign In', 'smart-login-registration' ),
				'submitRegister'     => __( 'Create Account', 'smart-login-registration' ),
				'close'              => __( 'Close', 'smart-login-registration' ),
				'or'                 => __( 'or', 'smart-login-registration' ),
				'usePasskey'         => __( 'Sign in with Passkey', 'smart-login-registration' ),
				'signInWithCode'     => __( 'Sign in with code', 'smart-login-registration' ),
				'otpLoginTitle'      => __( 'Sign in with code', 'smart-login-registration' ),
				'otpLoginSubtitle'   => __( 'We will send a 6-digit code to verify it is you.', 'smart-login-registration' ),
				'sendLoginCode'      => __( 'Send verification code', 'smart-login-registration' ),
				'usePasswordInstead' => __( 'Use password instead', 'smart-login-registration' ),
				'otpChannelEmail'    => __( 'Email', 'smart-login-registration' ),
				'otpChannelPhone'    => __( 'Phone', 'smart-login-registration' ),
				'verifyVia'          => __( 'Verify your account via', 'smart-login-registration' ),
				'verifyOtp'          => __( 'Verify Code', 'smart-login-registration' ),
				'resendOtp'          => __( 'Resend Code', 'smart-login-registration' ),
				'otpSent'            => __( 'We sent a 6-digit code to', 'smart-login-registration' ),
				'loading'            => __( 'Loading...', 'smart-login-registration' ),
				'remember'           => __( 'Remember me', 'smart-login-registration' ),
				'errorGeneric'       => __( 'Something went wrong. Please try again.', 'smart-login-registration' ),
				'errorRequired'      => __( 'Please fill in all required fields.', 'smart-login-registration' ),
				'errorLoginRequired'   => __( 'Email or phone and password are required.', 'smart-login-registration' ),
				'errorInvalidEmail'    => __( 'Please enter a valid email address.', 'smart-login-registration' ),
				'errorPhone'         => __( 'Please enter a valid phone number.', 'smart-login-registration' ),
				'errorPasswordMin'     => __( 'Password must be at least 8 characters.', 'smart-login-registration' ),
				'errorOtp'           => __( 'Please enter the 6-digit code.', 'smart-login-registration' ),
				'otpResent'          => __( 'A new code has been sent.', 'smart-login-registration' ),
				'forgotPassword'     => __( 'Forgot password?', 'smart-login-registration' ),
				'forgotPasswordHint' => __( 'Enter your email or phone and we will send a verification code.', 'smart-login-registration' ),
				'sendResetCode'      => __( 'Send reset code', 'smart-login-registration' ),
				'backToLogin'        => __( 'Back to sign in', 'smart-login-registration' ),
				'useEmailInstead'    => __( 'Use email instead', 'smart-login-registration' ),
				'usePhoneInstead'    => __( 'Use phone instead', 'smart-login-registration' ),
				'newPassword'        => __( 'Set new password', 'smart-login-registration' ),
				'newPasswordHint'    => __( 'Choose a strong password for your account.', 'smart-login-registration' ),
				'updatePassword'     => __( 'Update password', 'smart-login-registration' ),
				'passwordResetSuccess' => __( 'Password updated. You can sign in now.', 'smart-login-registration' ),
				'continueOtp'      => __( 'Continue verification', 'smart-login-registration' ),
				'otpInProgress'      => __( 'Verification in progress for', 'smart-login-registration' ),
				'sessionExpired'     => __( 'Registration session expired. Please create your account again.', 'smart-login-registration' ),
				'passkeySetupTitle'  => __( 'Set up a passkey', 'smart-login-registration' ),
				'passkeySetupDesc'   => __( 'Sign in faster next time with Face ID, Touch ID, or your device PIN. No password needed.', 'smart-login-registration' ),
				'passkeySetupBtn'    => __( 'Set up passkey', 'smart-login-registration' ),
				'passkeySkip'        => __( 'Not now', 'smart-login-registration' ),
				'passkeyRegistered'  => __( 'Passkey registered! You can use it to sign in next time.', 'smart-login-registration' ),
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
