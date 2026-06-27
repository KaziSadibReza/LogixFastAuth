<?php
/**
 * Replaces WooCommerce My Account login when LogixFastAuth is enabled and no dedicated page redirect.
 *
 * @package LogixFastAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LogixFastAuth\Integrations\WooCommerce_Login;

do_action( 'woocommerce_before_customer_login_form' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.

WooCommerce_Login::render_login_embed();

do_action( 'woocommerce_after_customer_login_form' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
