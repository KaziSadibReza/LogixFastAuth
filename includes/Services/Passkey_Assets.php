<?php
/**
 * Enqueue passkey manager CSS/JS on supported screens.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Passkey_Assets
 */
class Passkey_Assets {

	const STYLE_HANDLE  = 'logixfast-auth-passkey-manager';
	const SCRIPT_HANDLE = 'logixfast-auth-passkey-manager';

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
	 * Inline passkey icon SVG (uses currentColor — matches LogixFastAuth accent via CSS).
	 *
	 * @return string
	 */
	public static function get_icon_svg() {
		static $svg = null;

		if ( null !== $svg ) {
			return $svg;
		}

		$path = LOGIXFAST_AUTH_PLUGIN_DIR . 'assets/images/passkey-icon.svg';
		if ( ! file_exists( $path ) ) {
			$svg = '';
			return $svg;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin asset.
		$svg = (string) file_get_contents( $path );

		return $svg;
	}

	/**
	 * Get sanitized inline passkey icon SVG.
	 *
	 * @return string
	 */
	public static function get_sanitized_icon_svg() {
		return wp_kses(
			self::get_icon_svg(),
			array(
				'svg'   => array(
					'aria-hidden' => true,
					'class'       => true,
					'fill'        => true,
					'focusable'   => true,
					'height'      => true,
					'role'        => true,
					'viewbox'     => true,
					'viewBox'     => true,
					'width'       => true,
					'xmlns'       => true,
				),
				'path'  => array(
					'd'               => true,
					'fill'            => true,
					'fill-rule'       => true,
					'clip-rule'       => true,
					'opacity'         => true,
					'stroke'          => true,
					'stroke-linecap'  => true,
					'stroke-linejoin' => true,
					'stroke-width'    => true,
				),
				'circle' => array(
					'cx'      => true,
					'cy'      => true,
					'fill'    => true,
					'opacity' => true,
					'r'       => true,
				),
				'g'     => array(
					'fill'      => true,
					'opacity'   => true,
					'transform' => true,
				),
			)
		);
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

		$css_path = LOGIXFAST_AUTH_PLUGIN_DIR . 'assets/css/passkey-manager.css';
		$js_path  = LOGIXFAST_AUTH_PLUGIN_DIR . 'assets/js/passkey-manager.js';

		wp_register_style(
			self::STYLE_HANDLE,
			LOGIXFAST_AUTH_PLUGIN_URL . 'assets/css/passkey-manager.css',
			array(),
			file_exists( $css_path ) ? (string) filemtime( $css_path ) : LOGIXFAST_AUTH_VERSION
		);

		wp_register_script(
			self::SCRIPT_HANDLE,
			LOGIXFAST_AUTH_PLUGIN_URL . 'assets/js/passkey-manager.js',
			array(),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : LOGIXFAST_AUTH_VERSION,
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'LOGIXFAST_AUTH_PASSKEY_MANAGER',
			array(
				'apiUrl'  => esc_url_raw( trailingslashit( rest_url( 'logixfast-auth/v1' ) ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'iconSvg' => self::get_icon_svg(),
				'i18n'    => array(
					'passkeyLabel'   => __( 'Passkey', 'logixfast-auth' ),
					'added'          => __( 'Added', 'logixfast-auth' ),
					'lastUsed'       => __( 'Last used', 'logixfast-auth' ),
					'remove'         => __( 'Remove', 'logixfast-auth' ),
					'loading'        => __( 'Loading…', 'logixfast-auth' ),
					'loadFailed'     => __( 'Failed to load passkeys:', 'logixfast-auth' ),
					'confirmRemove'  => __( 'Remove this passkey? You won\'t be able to sign in with it anymore.', 'logixfast-auth' ),
					'removed'        => __( 'Passkey removed.', 'logixfast-auth' ),
					'removeFailed'   => __( 'Could not remove passkey.', 'logixfast-auth' ),
					'networkError'   => __( 'Network error.', 'logixfast-auth' ),
					'unsupported'    => __( 'Your browser does not support passkeys.', 'logixfast-auth' ),
					'registering'    => __( 'Registering…', 'logixfast-auth' ),
					'invalidOptions' => __( 'Invalid registration options received.', 'logixfast-auth' ),
					'passkeyAdded'   => __( 'Passkey added!', 'logixfast-auth' ),
					'addFailed'      => __( 'Could not add passkey.', 'logixfast-auth' ),
				),
			)
		);

		wp_enqueue_style( self::STYLE_HANDLE );
		wp_enqueue_script( self::SCRIPT_HANDLE );

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( self::SCRIPT_HANDLE, 'logixfast-auth', LOGIXFAST_AUTH_PLUGIN_DIR . 'languages' );
		}
	}
}
