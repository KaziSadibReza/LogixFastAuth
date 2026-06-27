<?php
/**
 * Remove LogixFastAuth plugin data. Preserves WooCommerce billing_phone and Tutor phone_number.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Database\WebAuthnRepository;
use LogixFastAuth\Integrations\Integration_Availability;
use LogixFastAuth\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UninstallService
 */
class UninstallService {

	const PURGE_FLAG_OPTION = 'logixfast_auth_purge_data_on_removal';
	const SETTINGS_OPTION   = 'logixfast_auth_settings';
	const LOGIXFAST_AUTH_PHONE_META    = 'logixfast_auth_phone';
	const META_PAGE_TYPE    = '_logixfast_auth_page_type';
	const PAGE_TYPE_LOGIN   = 'login';

	/**
	 * Admin chose to purge LogixFastAuth data on deactivate or delete.
	 *
	 * @return void
	 */
	public static function flag_purge_data() {
		update_option( self::PURGE_FLAG_OPTION, '1', false );
	}

	/**
	 * Whether purge was requested before deactivate/uninstall.
	 *
	 * @return bool
	 */
	public static function should_purge_data() {
		return (bool) get_option( self::PURGE_FLAG_OPTION, false );
	}

	/**
	 * Data summary for lifecycle modals.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_preview() {
		$general    = Settings::get( 'general' );
		$page_id    = (int) ( $general['dedicated_page_id'] ?? 0 );
		$login_page = $page_id > 0 ? get_post( $page_id ) : null;

		return array(
			'phone_users'        => Phone_Profile::count_logixfast_auth_phone_users(),
			'passkey_rows'       => ( new WebAuthnRepository() )->count_all(),
			'login_page_title'   => $login_page instanceof \WP_Post ? $login_page->post_title : '',
			'uses_logixfast_auth_phone'     => Phone_Profile::uses_logixfast_auth_field(),
			'woocommerce_active' => Integration_Availability::is_woocommerce_available(),
			'tutor_active'       => Integration_Availability::is_tutor_available(),
		);
	}

	/**
	 * Drop LogixFastAuth tables, options, logixfast_auth_phone meta, and login page.
	 *
	 * @return void
	 */
	public static function purge_all_data() {
		global $wpdb;

		$settings = get_option( self::SETTINGS_OPTION, array() );
		$page_id  = 0;
		if ( is_array( $settings ) && ! empty( $settings['general']['dedicated_page_id'] ) ) {
			$page_id = (int) $settings['general']['dedicated_page_id'];
		}

		$tables = array(
			$wpdb->prefix . 'logixfast_auth_otp_codes',
			$wpdb->prefix . 'logixfast_auth_pending_registrations',
			$wpdb->prefix . 'logixfast_auth_webauthn_credentials',
		);

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- LogixFastAuth-owned tables from $wpdb->prefix; uninstall cleanup.
			$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
		}

		$options = array(
			self::SETTINGS_OPTION,
			'logixfast_auth_db_version',
			'logixfast_auth_rate_blocks',
			'logixfast_auth_stats',
			self::PURGE_FLAG_OPTION,
		);

		foreach ( $options as $option ) {
			delete_option( $option );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_logixfast_auth_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_logixfast_auth_' ) . '%'
			)
		);

		// LogixFastAuth field only — never billing_phone (WooCommerce) or phone_number (Tutor LMS).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Uninstall cleanup.
		$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => self::LOGIXFAST_AUTH_PHONE_META ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Uninstall cleanup.
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => self::META_PAGE_TYPE ) );

		if ( $page_id > 0 ) {
			wp_delete_post( $page_id, true );
		}

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Remove LogixFastAuth login pages.
		$legacy_pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft', 'private', 'trash' ),
				'meta_key'       => self::META_PAGE_TYPE,
				'meta_value'     => self::PAGE_TYPE_LOGIN,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value

		foreach ( $legacy_pages as $legacy_page_id ) {
			wp_delete_post( (int) $legacy_page_id, true );
		}
	}
}
