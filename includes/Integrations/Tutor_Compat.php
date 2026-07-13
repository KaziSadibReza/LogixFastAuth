<?php
/**
 * Tutor LMS version compatibility helpers.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Integrations; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tutor_Compat
 */
class Tutor_Compat {

	const PASSKEYS_TAB_ID       = 'passkeys';
	const PASSKEYS_TEMPLATE     = 'dashboard.account.settings.passkeys';
	const PASSKEYS_TEMPLATE_REL = 'templates/tutor/account-settings-passkeys.php';

	/**
	 * Whether Tutor settings use inline Alpine tabs (Tutor 4.0+ schema).
	 *
	 * @param array<string, mixed> $tabs Optional settings tabs from the filter.
	 * @return bool
	 */
	public static function uses_inline_settings_tabs( array $tabs = array() ) {
		if ( ! empty( $tabs ) ) {
			$sample = reset( $tabs );

			return is_array( $sample )
				&& array_key_exists( 'id', $sample )
				&& array_key_exists( 'template', $sample );
		}

		return defined( 'TUTOR_VERSION' ) && version_compare( TUTOR_VERSION, '4.0.0', '>=' );
	}

	/**
	 * URL where users manage passkeys in the Tutor dashboard.
	 *
	 * @return string
	 */
	public static function get_passkeys_settings_url() {
		if ( ! function_exists( 'tutor_utils' ) ) {
			return '';
		}

		if ( self::uses_inline_settings_tabs() && class_exists( '\TUTOR\Dashboard' ) ) {
			return add_query_arg(
				'tab',
				self::PASSKEYS_TAB_ID,
				\TUTOR\Dashboard::get_account_page_url( 'settings' )
			);
		}

		return (string) tutor_utils()->get_tutor_dashboard_page_permalink( 'settings/passkeys' );
	}

	/**
	 * Whether the current request is a Tutor passkeys settings screen.
	 *
	 * @return bool
	 */
	public static function is_passkeys_settings_screen() {
		global $wp_query;

		if ( ! $wp_query instanceof \WP_Query ) {
			return false;
		}

		$page_slug = (string) $wp_query->get( 'tutor_dashboard_page' );
		$sub_page  = (string) $wp_query->get( 'tutor_dashboard_sub_page' );

		if ( self::uses_inline_settings_tabs() ) {
			return 'account' === $page_slug && 'settings' === $sub_page;
		}

		return 'settings' === $page_slug && 'passkeys' === $sub_page;
	}

	/**
	 * Absolute path to the inline passkeys settings template.
	 *
	 * @return string
	 */
	public static function get_passkeys_template_path() {
		return LOGIXFAST_AUTH_PLUGIN_DIR . self::PASSKEYS_TEMPLATE_REL;
	}

	/**
	 * Normalize a Tutor template identifier for comparison.
	 *
	 * @param string $template Template name from Tutor APIs.
	 * @return string
	 */
	public static function normalize_template_name( $template ) {
		return str_replace( array( '/', '\\' ), '.', (string) $template );
	}
}
