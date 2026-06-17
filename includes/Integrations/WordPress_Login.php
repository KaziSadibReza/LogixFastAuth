<?php
/**
 * WordPress core login replacement.
 *
 * @package SLR
 */

namespace SLR\Integrations; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WordPress_Login
 */
class WordPress_Login {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'login_url', array( $this, 'filter_login_url' ), 99, 3 );
		add_action( 'login_init', array( $this, 'redirect_wp_login' ), 1 );
	}

	/**
	 * Check if replacement is enabled.
	 *
	 * @return bool
	 */
	private function is_enabled() {
		$integrations = Settings::get( 'integrations' );
		return Settings::to_bool( $integrations['replace_wp_login'] ?? false );
	}

	/**
	 * Filter login URL to SLR dedicated page.
	 *
	 * @param string $login_url Login URL.
	 * @param string $redirect  Redirect URL.
	 * @param bool   $force_reauth Force reauth.
	 * @return string
	 */
	public function filter_login_url( $login_url, $redirect, $force_reauth ) {
		if ( ! $this->is_enabled() ) {
			return $login_url;
		}

		$page_url = $this->get_dedicated_page_url();
		if ( ! $page_url ) {
			return $login_url;
		}

		if ( ! empty( $redirect ) ) {
			$redirect = wp_validate_redirect( $redirect, '' );
			if ( $redirect ) {
				$page_url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $page_url );
			}
		}

		return $page_url;
	}

	/**
	 * Redirect direct wp-login.php access.
	 *
	 * @return void
	 */
	public function redirect_wp_login() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- wp-login.php public query args.
		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : 'login';
		$allowed = array( 'logout', 'postpass', 'confirm_admin_email', 'rp', 'resetpass' );

		if ( in_array( $action, $allowed, true ) ) {
			return;
		}

		$page_url = $this->get_dedicated_page_url();
		if ( ! $page_url ) {
			return;
		}

		$raw_redirect = filter_input( INPUT_GET, 'redirect_to', FILTER_UNSAFE_RAW );
		if ( ! is_string( $raw_redirect ) || '' === $raw_redirect ) {
			$raw_redirect = filter_input( INPUT_POST, 'redirect_to', FILTER_UNSAFE_RAW );
		}

		if ( is_string( $raw_redirect ) && '' !== $raw_redirect ) {
			$raw_redirect = sanitize_url( wp_unslash( $raw_redirect ) );
			if ( '' !== $raw_redirect ) {
				$redirect = wp_validate_redirect( $raw_redirect, '' );
				if ( $redirect ) {
					$page_url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $page_url );
				}
			}
		}

		wp_safe_redirect( $page_url );
		exit;
	}

	/**
	 * Get dedicated login page URL.
	 *
	 * @return string
	 */
	private function get_dedicated_page_url() {
		$general = Settings::get( 'general' );
		$page_id = (int) ( $general['dedicated_page_id'] ?? 0 );

		if ( ! $page_id ) {
			return '';
		}

		return get_permalink( $page_id ) ?: '';
	}
}
