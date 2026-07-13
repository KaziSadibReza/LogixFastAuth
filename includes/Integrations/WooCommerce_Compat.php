<?php
/**
 * WooCommerce compatibility helpers for optional plugin functions.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Integrations; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WooCommerce_Compat
 */
class WooCommerce_Compat {

	const PASSKEYS_ENDPOINT = 'passkeys';

	/**
	 * Whether the current request is the WooCommerce passkeys account screen.
	 *
	 * @return bool
	 */
	public static function is_passkeys_screen() {
		if ( ! Integration_Availability::is_woocommerce_available() || ! is_user_logged_in() ) {
			return false;
		}

		if ( function_exists( 'is_account_page' ) && call_user_func( 'is_account_page' ) ) {
			global $wp;
			if ( is_object( $wp ) && array_key_exists( self::PASSKEYS_ENDPOINT, $wp->query_vars ) ) {
				return true;
			}
		}

		if ( function_exists( 'is_wc_endpoint_url' ) && call_user_func( 'is_wc_endpoint_url', self::PASSKEYS_ENDPOINT ) ) {
			return true;
		}

		return false;
	}

	/**
	 * URL for the WooCommerce My Account passkeys endpoint.
	 *
	 * @return string
	 */
	public static function get_passkeys_account_url() {
		if ( ! Integration_Availability::is_woocommerce_available() || ! function_exists( 'wc_get_account_endpoint_url' ) ) {
			return '';
		}

		return (string) call_user_func( 'wc_get_account_endpoint_url', self::PASSKEYS_ENDPOINT );
	}
}
