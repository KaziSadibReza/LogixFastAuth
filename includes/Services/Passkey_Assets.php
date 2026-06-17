<?php
/**
 * Enqueue passkey manager CSS/JS on supported screens.
 *
 * @package SLR
 */

namespace SLR\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Passkey_Assets
 */
class Passkey_Assets {

	const STYLE_HANDLE  = 'slr-passkey-manager';
	const SCRIPT_HANDLE = 'slr-passkey-manager';

	/**
	 * Whether assets were already enqueued this request.
	 *
	 * @var bool
	 */
	private static $enqueued = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_admin' ) );
		add_action( 'wp', array( $this, 'maybe_enqueue_public' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_public' ), 20 );
	}

	/**
	 * Enqueue on profile screens in wp-admin.
	 *
	 * @param string $hook Admin page hook.
	 * @return void
	 */
	public function maybe_enqueue_admin( $hook ) {
		if ( ! Passkey_Surfaces::is_enabled() || ! is_user_logged_in() ) {
			return;
		}

		if ( 'profile.php' === $hook ) {
			self::enqueue();
			return;
		}

		if ( 'user-edit.php' === $hook ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen routing.
			$user_id = isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : 0;
			if ( $user_id > 0 && $user_id === get_current_user_id() ) {
				self::enqueue();
			}
		}
	}

	/**
	 * Enqueue on WooCommerce / Tutor passkey screens.
	 *
	 * @return void
	 */
	public function maybe_enqueue_public() {
		if ( ! Passkey_Surfaces::is_enabled() || ! is_user_logged_in() ) {
			return;
		}

		if ( self::is_public_passkeys_screen() ) {
			self::enqueue();
		}
	}

	/**
	 * Whether the current front-end request is a passkey management screen.
	 *
	 * @return bool
	 */
	public static function is_public_passkeys_screen() {
		if ( function_exists( 'is_account_page' ) && is_account_page() && is_user_logged_in() ) {
			global $wp;
			if ( is_object( $wp ) && array_key_exists( 'passkeys', $wp->query_vars ) ) {
				return true;
			}
		}

		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'passkeys' ) ) {
			return true;
		}

		global $wp_query;
		if ( ! $wp_query instanceof \WP_Query ) {
			return false;
		}

		$page_slug = $wp_query->get( 'tutor_dashboard_page' );
		$sub_page  = $wp_query->get( 'tutor_dashboard_sub_page' );

		return 'settings' === $page_slug && 'passkeys' === $sub_page;
	}

	/**
	 * Inline passkey icon SVG (uses currentColor — matches SLR accent via CSS).
	 *
	 * @return string
	 */
	public static function get_icon_svg() {
		static $svg = null;

		if ( null !== $svg ) {
			return $svg;
		}

		$path = SLR_PLUGIN_DIR . 'assets/images/passkey-icon.svg';
		if ( ! file_exists( $path ) ) {
			$svg = '';
			return $svg;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin asset.
		$svg = (string) file_get_contents( $path );

		return $svg;
	}

	/**
	 * Register and enqueue passkey manager assets.
	 *
	 * @return void
	 */
	public static function enqueue() {
		if ( self::$enqueued ) {
			return;
		}

		self::$enqueued = true;

		$css_path = SLR_PLUGIN_DIR . 'assets/css/passkey-manager.css';
		$js_path  = SLR_PLUGIN_DIR . 'assets/js/passkey-manager.js';

		wp_register_style(
			self::STYLE_HANDLE,
			SLR_PLUGIN_URL . 'assets/css/passkey-manager.css',
			array(),
			file_exists( $css_path ) ? (string) filemtime( $css_path ) : SLR_VERSION
		);

		wp_register_script(
			self::SCRIPT_HANDLE,
			SLR_PLUGIN_URL . 'assets/js/passkey-manager.js',
			array(),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : SLR_VERSION,
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'SLR_PASSKEY_MANAGER',
			array(
				'apiUrl'  => esc_url_raw( trailingslashit( rest_url( 'slr/v1' ) ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'iconSvg' => self::get_icon_svg(),
				'i18n'    => array(
					'passkeyLabel'   => __( 'Passkey', 'smart-login-registration' ),
					'added'          => __( 'Added', 'smart-login-registration' ),
					'lastUsed'       => __( 'Last used', 'smart-login-registration' ),
					'remove'         => __( 'Remove', 'smart-login-registration' ),
					'loading'        => __( 'Loading…', 'smart-login-registration' ),
					'loadFailed'     => __( 'Failed to load passkeys:', 'smart-login-registration' ),
					'confirmRemove'  => __( 'Remove this passkey? You won\'t be able to sign in with it anymore.', 'smart-login-registration' ),
					'removed'        => __( 'Passkey removed.', 'smart-login-registration' ),
					'removeFailed'   => __( 'Could not remove passkey.', 'smart-login-registration' ),
					'networkError'   => __( 'Network error.', 'smart-login-registration' ),
					'unsupported'    => __( 'Your browser does not support passkeys.', 'smart-login-registration' ),
					'registering'    => __( 'Registering…', 'smart-login-registration' ),
					'invalidOptions' => __( 'Invalid registration options received.', 'smart-login-registration' ),
					'passkeyAdded'   => __( 'Passkey added!', 'smart-login-registration' ),
					'addFailed'      => __( 'Could not add passkey.', 'smart-login-registration' ),
				),
			)
		);

		wp_enqueue_style( self::STYLE_HANDLE );
		wp_enqueue_script( self::SCRIPT_HANDLE );

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( self::SCRIPT_HANDLE, 'smart-login-registration', SLR_PLUGIN_DIR . 'languages' );
		}
	}
}
