<?php
/**
 * SLR admin screen shell — focused UI without third-party notice clutter.
 *
 * @package SLR
 */

namespace SLR\Admin; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin namespace prefix.

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
		add_action( 'load-toplevel_page_slr', array( $this, 'prepare_screen' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Prepare the SLR settings screen.
	 *
	 * @return void
	 */
	public function prepare_screen() {
		add_action( 'in_admin_header', array( $this, 'strip_foreign_notices' ), 0 );
		add_action( 'admin_print_styles', array( $this, 'hide_notice_styles' ), 99 );
	}

	/**
	 * Remove other plugins' admin notice hooks on the SLR screen.
	 *
	 * @return void
	 */
	public function strip_foreign_notices() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'toplevel_page_slr' !== $screen->id ) {
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
		if ( ! $screen || 'toplevel_page_slr' !== $screen->id ) {
			return;
		}

		echo '<style id="slr-admin-notice-guard">'
			. 'body.slr-admin-screen .wrap > *:not(#slr-admin-root){display:none!important}'
			. 'body.slr-admin-screen #wpbody-content > .notice,'
			. 'body.slr-admin-screen #wpbody-content > .updated,'
			. 'body.slr-admin-screen #wpbody-content > .error,'
			. 'body.slr-admin-screen #wpbody-content > .update-nag,'
			. 'body.slr-admin-screen #wpbody-content > div[class*="notice"]{display:none!important}'
			. '</style>';
	}

	/**
	 * Add body class for scoped admin styles.
	 *
	 * @param string $classes Body classes.
	 * @return string
	 */
	public function body_class( $classes ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'toplevel_page_slr' === $screen->id ) {
			$classes .= ' slr-admin-screen';
		}

		return $classes;
	}
}
