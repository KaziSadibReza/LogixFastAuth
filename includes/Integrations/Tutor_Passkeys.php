<?php
/**
 * Adds a "Passkeys" tab to the Tutor LMS dashboard Settings page.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Integrations; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tutor_Passkeys {

	/**
	 * Constructor.
	 */
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
		add_filter( 'tutor_get_template_path', array( $this, 'filter_template_path' ), 100, 2 );
		add_filter( 'tutor_not_found_template_warning_msg', array( $this, 'suppress_passkeys_template_warning' ), 10, 1 );
	}

	/**
	 * Register "Passkeys" nav menu item under Settings.
	 *
	 * @param array<string, mixed> $tabs Settings navigation tabs.
	 * @return array<string, mixed>
	 */
	public static function register_nav( $tabs ) {
		if ( Tutor_Compat::uses_inline_settings_tabs( $tabs ) ) {
			return self::register_modern_nav( $tabs );
		}

		return self::register_legacy_nav( $tabs );
	}

	/**
	 * Register passkeys tab for Tutor 4.0+ inline settings.
	 *
	 * @param array<string, mixed> $tabs Settings navigation tabs.
	 * @return array<string, mixed>
	 */
	private static function register_modern_nav( $tabs ) {
		$id = Tutor_Compat::PASSKEYS_TAB_ID;

		$new_tab = array(
			'id'       => $id,
			'label'    => __( 'Passkeys', 'logixfast-auth' ),
			'icon'     => class_exists( '\TUTOR\Icon' ) ? \TUTOR\Icon::KEY : 'key',
			'text'     => __( 'Manage passkeys for passwordless sign-in', 'logixfast-auth' ),
			'template' => Tutor_Compat::PASSKEYS_TEMPLATE,
			'role'     => false,
		);

		$position = array_search( 'security', array_keys( $tabs ), true );
		if ( false === $position ) {
			$position = array_search( 'social-accounts', array_keys( $tabs ), true );
		}

		if ( false === $position ) {
			$tabs[ $id ] = $new_tab;
			return $tabs;
		}

		return array_slice( $tabs, 0, $position + 1, true )
			+ array( $id => $new_tab )
			+ array_slice( $tabs, $position + 1, null, true );
	}

	/**
	 * Register passkeys nav for legacy Tutor settings sub-pages.
	 *
	 * @param array<string, mixed> $tabs Settings navigation tabs.
	 * @return array<string, mixed>
	 */
	private static function register_legacy_nav( $tabs ) {
		$tabs[ Tutor_Compat::PASSKEYS_TAB_ID ] = array(
			'url'   => esc_url( Tutor_Compat::get_passkeys_settings_url() ),
			'title' => __( 'Passkeys', 'logixfast-auth' ),
			'role'  => false,
		);

		return $tabs;
	}

	/**
	 * Map the inline passkeys template to LogixFastAuth.
	 *
	 * @param string $path     Resolved template path.
	 * @param string $template Template identifier.
	 * @return string
	 */
	public static function filter_template_path( $path, $template ) {
		if ( ! self::is_passkeys_template( $template ) ) {
			return $path;
		}

		$custom = Tutor_Compat::get_passkeys_template_path();
		if ( file_exists( $custom ) ) {
			return $custom;
		}

		return $path;
	}

	/**
	 * Suppress Tutor's missing-template notice for the passkeys settings tab.
	 *
	 * Tutor echoes this warning before tutor_get_template_path can redirect to our plugin file.
	 *
	 * @param string $message Warning message HTML.
	 * @return string
	 */
	public static function suppress_passkeys_template_warning( $message ) {
		if ( ! is_string( $message ) || '' === $message ) {
			return $message;
		}

		if ( preg_match( '#account[/\\\\]settings[/\\\\]passkeys\.php#i', $message ) ) {
			return '';
		}

		return $message;
	}

	/**
	 * Whether a Tutor template identifier refers to the passkeys settings tab.
	 *
	 * @param string $template Template name or path fragment.
	 * @return bool
	 */
	private static function is_passkeys_template( $template ) {
		return Tutor_Compat::PASSKEYS_TEMPLATE === Tutor_Compat::normalize_template_name( $template );
	}

	/**
	 * Load the passkeys template when on the passkeys settings sub-page.
	 *
	 * @param string $location Template path from other integrations.
	 * @return string
	 */
	public static function load_template( $location ) {
		if ( ! empty( $location ) || Tutor_Compat::uses_inline_settings_tabs() ) {
			return $location;
		}

		global $wp_query;

		$page_slug = isset( $wp_query->query_vars['tutor_dashboard_page'] )
			? $wp_query->query_vars['tutor_dashboard_page']
			: '';

		$sub_page = isset( $wp_query->query_vars['tutor_dashboard_sub_page'] )
			? $wp_query->query_vars['tutor_dashboard_sub_page']
			: '';

		if ( 'settings' === $page_slug && 'passkeys' === $sub_page ) {
			$template = LOGIXFAST_AUTH_PLUGIN_DIR . 'templates/dashboard-passkeys.php';
			if ( file_exists( $template ) ) {
				return $template;
			}
		}

		return $location;
	}
}
