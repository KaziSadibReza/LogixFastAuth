<?php
/**
 * LogixFastAuth admin screen shell — focused UI without third-party notice clutter.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Admin; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin namespace prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_Shell
 */
class Admin_Shell {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'load-toplevel_page_logixfastauth', array( $this, 'prepare_screen' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Prepare the LogixFastAuth settings screen.
	 *
	 * @return void
	 */
	public function prepare_screen() {
		add_action( 'in_admin_header', array( $this, 'strip_foreign_notices' ), 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'hide_notice_styles' ), 99 );
	}

	/**
	 * Remove other plugins' admin notice hooks on the LogixFastAuth screen.
	 *
	 * @return void
	 */
	public function strip_foreign_notices() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'toplevel_page_logixfastauth' !== $screen->id ) {
			return;
		}

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
		remove_all_actions( 'network_admin_notices' );
	}

	/**
	 * Hide notice markup that was already queued or printed outside hooks.
	 *
	 * @return void
	 */
	public function hide_notice_styles() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'toplevel_page_logixfastauth' !== $screen->id ) {
			return;
		}

		wp_register_style( 'logixfast-auth-admin-notice-guard', false, array(), LOGIXFAST_AUTH_VERSION );
		wp_enqueue_style( 'logixfast-auth-admin-notice-guard' );
		wp_add_inline_style(
			'logixfast-auth-admin-notice-guard',
			'body.logixfast-auth-admin-screen .wrap > *:not(#logixfast-auth-admin-root){display:none!important}'
			. 'body.logixfast-auth-admin-screen #wpbody-content > .notice,'
			. 'body.logixfast-auth-admin-screen #wpbody-content > .updated,'
			. 'body.logixfast-auth-admin-screen #wpbody-content > .error,'
			. 'body.logixfast-auth-admin-screen #wpbody-content > .update-nag,'
			. 'body.logixfast-auth-admin-screen #wpbody-content > div[class*="notice"]{display:none!important}'
		);
	}

	/**
	 * Add body class for scoped admin styles.
	 *
	 * @param string $classes Body classes.
	 * @return string
	 */
	public function body_class( $classes ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'toplevel_page_logixfastauth' === $screen->id ) {
			$classes .= ' logixfast-auth-admin-screen';
		}

		return $classes;
	}
}
