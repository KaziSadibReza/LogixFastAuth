<?php
/**
 * Adds a "Passkeys" tab to the Tutor LMS dashboard Settings page.
 *
 * @package SLR
 */

namespace SLR\Integrations; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tutor_Passkeys {

	public function __construct() {
		if ( ! function_exists( 'tutor_utils' ) ) {
			return;
		}

		$auth = Settings::get( 'auth' );
		if ( empty( $auth['webauthn_enabled'] ) ) {
			return;
		}

		add_filter( 'tutor_dashboard/nav_items/settings/nav_items', array( $this, 'register_nav' ) );
		add_filter( 'load_dashboard_template_part_from_other_location', array( $this, 'load_template' ) );
	}

	/**
	 * Register "Passkeys" nav menu item under Settings.
	 */
	public static function register_nav( $tabs ) {
		$url = tutor_utils()->get_tutor_dashboard_page_permalink( 'settings/passkeys' );

		$tabs['passkeys'] = array(
			'url'   => esc_url( $url ),
			'title' => __( 'Passkeys', 'smart-login-registration' ),
			'role'  => false,
		);

		return $tabs;
	}

	/**
	 * Load the passkeys template when on the passkeys settings sub-page.
	 */
	public static function load_template( $location ) {
		global $wp_query;

		$page_slug = isset( $wp_query->query_vars['tutor_dashboard_page'] )
			? $wp_query->query_vars['tutor_dashboard_page']
			: '';

		$sub_page = isset( $wp_query->query_vars['tutor_dashboard_sub_page'] )
			? $wp_query->query_vars['tutor_dashboard_sub_page']
			: '';

		if ( 'settings' === $page_slug && 'passkeys' === $sub_page ) {
			$template = SLR_PLUGIN_DIR . 'templates/dashboard-passkeys.php';
			if ( file_exists( $template ) ) {
				return $template;
			}
		}

		return $location;
	}
}
