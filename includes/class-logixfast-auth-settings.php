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
				'email_otp_enabled'    => true,
				'phone_otp_enabled'    => false,
				'webauthn_enabled'     => false,
				'otp_login_enabled'    => false,
				'require_phone'        => true,
				'login_allow_email'    => true,
				'login_allow_phone'    => true,
				'login_allow_username' => false,
				'show_username_field'      => false,
				'use_custom_placeholders'  => false,
				'placeholders'             => array(
					'login_identifier'   => '',
					'register_username'  => '',
					'register_full_name' => '',
					'register_email'     => '',
					'register_phone'     => '',
					'register_password'  => '',
					'login_password'     => '',
				),
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
	 * Enabled login identifier methods from auth settings.
	 *
	 * @param array|null $auth Auth settings section.
	 * @return array<int, string> email|phone|username
	 */
	public static function get_login_methods( $auth = null ) {
		if ( ! is_array( $auth ) ) {
			$auth = self::get( 'auth' );
		}

		$methods = array();
		if ( self::to_bool( $auth['login_allow_email'] ?? true ) ) {
			$methods[] = 'email';
		}
		if ( self::to_bool( $auth['login_allow_phone'] ?? true ) ) {
			$methods[] = 'phone';
		}
		if ( self::to_bool( $auth['login_allow_username'] ?? false ) ) {
			$methods[] = 'username';
		}

		if ( empty( $methods ) ) {
			$methods[] = 'email';
		}

		return $methods;
	}

	/**
	 * Human-readable login identifier label from enabled methods.
	 *
	 * @param array|null $auth Auth settings section.
	 * @return string
	 */
	public static function build_login_identifier_label( $auth = null ) {
		$labels = array();
		foreach ( self::get_login_methods( $auth ) as $method ) {
			if ( 'email' === $method ) {
				$labels[] = __( 'Email', 'logixfast-auth' );
			} elseif ( 'phone' === $method ) {
				$labels[] = __( 'Phone', 'logixfast-auth' );
			} elseif ( 'username' === $method ) {
				$labels[] = __( 'Username', 'logixfast-auth' );
			}
		}

		return self::join_list( $labels );
	}

	/**
	 * Default login identifier placeholder from enabled methods.
	 *
	 * @param array|null $auth Auth settings section.
	 * @return string
	 */
	public static function build_login_identifier_placeholder_default( $auth = null ) {
		$methods = self::get_login_methods( $auth );

		if ( count( $methods ) > 1 ) {
			return self::build_login_identifier_label( $auth );
		}

		$method = $methods[0] ?? 'email';

		if ( 'email' === $method ) {
			return 'you@example.com';
		}

		if ( 'phone' === $method ) {
			return __( 'Phone number', 'logixfast-auth' );
		}

		return __( 'Username', 'logixfast-auth' );
	}

	/**
	 * Default placeholder strings for auth forms.
	 *
	 * @param array|null $auth Auth settings section.
	 * @return array<string, string>
	 */
	public static function get_placeholder_defaults( $auth = null ) {
		return array(
			'login_identifier'   => self::build_login_identifier_placeholder_default( $auth ),
			'register_username'  => __( 'Username', 'logixfast-auth' ),
			'register_full_name' => __( 'Jane Doe', 'logixfast-auth' ),
			'register_email'     => __( 'you@example.com', 'logixfast-auth' ),
			'register_phone'     => __( 'Phone number', 'logixfast-auth' ),
			'register_password'  => __( 'Min 8 characters', 'logixfast-auth' ),
			'login_password'     => __( 'Enter your password', 'logixfast-auth' ),
		);
	}

	/**
	 * Resolve admin placeholder overrides with smart defaults.
	 *
	 * @param array|null $auth Auth settings section.
	 * @return array<string, string>
	 */
	public static function resolve_placeholders( $auth = null ) {
		if ( ! is_array( $auth ) ) {
			$auth = self::get( 'auth' );
		}

		$defaults  = self::get_placeholder_defaults( $auth );
		$use_custom = self::to_bool( $auth['use_custom_placeholders'] ?? false );

		if ( ! $use_custom ) {
			return $defaults;
		}

		$overrides = is_array( $auth['placeholders'] ?? null ) ? $auth['placeholders'] : array();
		$resolved  = array();

		foreach ( $defaults as $key => $default ) {
			$custom = isset( $overrides[ $key ] ) ? trim( (string) $overrides[ $key ] ) : '';
			$resolved[ $key ] = '' !== $custom ? $custom : $default;
		}

		return $resolved;
	}

	/**
	 * Build login-required error message from enabled methods.
	 *
	 * @param array|null $auth Auth settings section.
	 * @return string
	 */
	public static function build_login_required_message( $auth = null ) {
		$label = self::build_login_identifier_label( $auth );

		/* translators: %s: login identifier label, e.g. Email, phone, or username. */
		return sprintf( __( '%1$s and password are required.', 'logixfast-auth' ), $label );
	}

	/**
	 * Join a list with commas and "or".
	 *
	 * @param array<int, string> $items List items.
	 * @return string
	 */
	private static function join_list( $items ) {
		$count = count( $items );
		if ( 0 === $count ) {
			return '';
		}
		if ( 1 === $count ) {
			return $items[0];
		}
		if ( 2 === $count ) {
			/* translators: 1: first item, 2: second item. */
			return sprintf( __( '%1$s or %2$s', 'logixfast-auth' ), $items[0], $items[1] );
		}

		$last = array_pop( $items );
		/* translators: 1: comma-separated items, 2: last item. */
		return sprintf( __( '%1$s, or %2$s', 'logixfast-auth' ), implode( ', ', $items ), $last );
	}

	/**
	 * Sanitize auth settings section.
	 *
	 * @param array $section Raw auth settings.
	 * @return array
	 */
	public static function sanitize_auth_section( $section ) {
		if ( ! is_array( $section ) ) {
			return self::get_default_settings()['auth'];
		}

		$defaults = self::get_default_settings()['auth'];
		$bool_keys = array(
			'email_otp_enabled',
			'phone_otp_enabled',
			'webauthn_enabled',
			'otp_login_enabled',
			'require_phone',
			'login_allow_email',
			'login_allow_phone',
			'login_allow_username',
			'show_username_field',
			'use_custom_placeholders',
		);

		foreach ( $bool_keys as $key ) {
			if ( array_key_exists( $key, $section ) ) {
				$section[ $key ] = self::to_bool( $section[ $key ] );
			}
		}

		$placeholders = is_array( $section['placeholders'] ?? null ) ? $section['placeholders'] : array();
		$sanitized_placeholders = array();
		foreach ( $defaults['placeholders'] as $key => $default_value ) {
			$sanitized_placeholders[ $key ] = isset( $placeholders[ $key ] )
				? sanitize_text_field( (string) $placeholders[ $key ] )
				: (string) $default_value;
		}
		$section['placeholders'] = $sanitized_placeholders;

		if (
			empty( $section['login_allow_email'] )
			&& empty( $section['login_allow_phone'] )
			&& empty( $section['login_allow_username'] )
		) {
			$section['login_allow_email'] = true;
		}

		return array_merge( $defaults, $section );
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

		$auth         = $all['auth'] ?? array();
		$placeholders = self::resolve_placeholders( $auth );
		$login_label  = self::build_login_identifier_label( $auth );

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
				'emailOtp'           => self::to_bool( $auth['email_otp_enabled'] ?? false ),
				'phoneOtp'           => self::to_bool( $auth['phone_otp_enabled'] ?? false ),
				'webauthn'           => self::to_bool( $auth['webauthn_enabled'] ?? false ),
				'otpLogin'           => self::to_bool( $auth['otp_login_enabled'] ?? false ),
				'requirePhone'       => self::to_bool( $auth['require_phone'] ?? true ),
				'loginAllowEmail'    => self::to_bool( $auth['login_allow_email'] ?? true ),
				'loginAllowPhone'    => self::to_bool( $auth['login_allow_phone'] ?? true ),
				'loginAllowUsername' => self::to_bool( $auth['login_allow_username'] ?? false ),
				'showUsernameField'  => self::to_bool( $auth['show_username_field'] ?? false ),
				'hasSmsProvider'     => ! empty( apply_filters( 'logixfast_auth_sms_providers', array() ) ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LogixFastAuth plugin hook.
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
				'emailOrPhone'       => $login_label,
				'loginIdentifier'    => $login_label,
				'username'           => __( 'Username', 'logixfast-auth' ),
				'phone'              => __( 'Phone Number', 'logixfast-auth' ),
				'usernameAvailable'  => __( 'Username is available.', 'logixfast-auth' ),
				'usernameTaken'      => __( 'Username is already taken.', 'logixfast-auth' ),
				'usernameInvalid'    => __( 'Please enter a valid username.', 'logixfast-auth' ),
				'usernameSuggested'  => __( 'Suggested from your email. You can change it if you like.', 'logixfast-auth' ),
				'placeholders'       => array(
					'loginIdentifier'   => $placeholders['login_identifier'],
					'registerUsername'  => $placeholders['register_username'],
					'registerFullName'  => $placeholders['register_full_name'],
					'registerEmail'     => $placeholders['register_email'],
					'registerPhone'     => $placeholders['register_phone'],
					'registerPassword'  => $placeholders['register_password'],
					'loginPassword'     => $placeholders['login_password'],
				),
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
				'errorLoginRequired'   => self::build_login_required_message( $auth ),
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
