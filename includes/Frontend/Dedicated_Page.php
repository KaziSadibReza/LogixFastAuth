<?php
/**
 * Dedicated login page template loader.
 *
 * @package SLR
 */

namespace SLR\Frontend; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Services\RedirectService;
use SLR\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dedicated_Page
 */
class Dedicated_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'redirect_logged_in_users' ), -1 );
		add_action( 'template_redirect', array( $this, 'prepare_minimal_page' ), 0 );
		add_action( 'template_redirect', array( $this, 'load_template' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'strip_conflicting_assets' ), 9999 );
	}

	/**
	 * Redirect logged-in users away from the sign-in page.
	 *
	 * @return void
	 */
	public function redirect_logged_in_users() {
		if ( ! self::is_dedicated_page() || ! is_user_logged_in() ) {
			return;
		}

		$url = RedirectService::resolve_logged_in_login_page();
		if ( empty( $url ) ) {
			return;
		}

		$login_page = get_permalink();
		if ( $login_page && untrailingslashit( $url ) === untrailingslashit( $login_page ) ) {
			return;
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Strip non-essential WP head/footer output on the dedicated page.
	 *
	 * @return void
	 */
	public function prepare_minimal_page() {
		if ( ! self::is_dedicated_page() ) {
			return;
		}

		add_filter( 'show_admin_bar', '__return_false' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
	}

	/**
	 * Keep only SLR plugin assets on the dedicated login page.
	 *
	 * @return void
	 */
	public function strip_conflicting_assets() {
		if ( ! self::is_dedicated_page() ) {
			return;
		}

		global $wp_scripts, $wp_styles;

		if ( $wp_scripts instanceof \WP_Scripts && ! empty( $wp_scripts->queue ) ) {
			foreach ( array_values( $wp_scripts->queue ) as $handle ) {
				if ( 0 !== strpos( $handle, 'slr-' ) ) {
					wp_dequeue_script( $handle );
					wp_deregister_script( $handle );
				}
			}
		}

		if ( $wp_styles instanceof \WP_Styles && ! empty( $wp_styles->queue ) ) {
			foreach ( array_values( $wp_styles->queue ) as $handle ) {
				if ( 0 !== strpos( $handle, 'slr-' ) ) {
					wp_dequeue_style( $handle );
					wp_deregister_style( $handle );
				}
			}
		}
	}

	/**
	 * Check if current page is dedicated login page.
	 *
	 * @return bool
	 */
	public static function is_dedicated_page() {
		return Settings::is_dedicated_page();
	}

	/**
	 * Load minimal template for dedicated page.
	 *
	 * @return void
	 */
	public function load_template() {
		if ( ! self::is_dedicated_page() ) {
			return;
		}

		$template = SLR_PLUGIN_DIR . 'templates/dedicated-login.php';
		if ( file_exists( $template ) ) {
			include $template;
			exit;
		}
	}
}
