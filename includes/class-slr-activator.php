<?php
/**
 * Plugin activation.
 *
 * @package SLR
 */

namespace SLR;

use SLR\Services\LoginPageService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Activator
 */
class Activator {

	/**
	 * Activate plugin.
	 *
	 * @return void
	 */
	public static function activate() {
		self::create_tables();
		Settings::set_defaults();
		LoginPageService::ensure_dedicated_page();
		flush_rewrite_rules();
	}

	/**
	 * Run database upgrades when the plugin version changes.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		global $wpdb;

		$pending_table  = $wpdb->prefix . 'slr_pending_registrations';
		$otp_table      = $wpdb->prefix . 'slr_otp_codes';
		$webauthn_table = $wpdb->prefix . 'slr_webauthn_credentials';

		$has_pending  = self::table_exists( $pending_table );
		$has_otp      = self::table_exists( $otp_table );
		$has_webauthn = self::table_exists( $webauthn_table );

		if ( $has_pending && $has_otp && $has_webauthn && get_option( 'slr_db_version' ) === SLR_VERSION ) {
			return;
		}

		self::create_tables();
		update_option( 'slr_db_version', SLR_VERSION );
	}

	/**
	 * Ensure all required SLR tables exist. Safe to call on every request;
	 * exits fast when tables are present.
	 *
	 * @return bool True if all tables exist after the call.
	 */
	public static function ensure_tables() {
		global $wpdb;

		$pending_table  = $wpdb->prefix . 'slr_pending_registrations';
		$otp_table      = $wpdb->prefix . 'slr_otp_codes';
		$webauthn_table = $wpdb->prefix . 'slr_webauthn_credentials';

		if (
			self::table_exists( $pending_table ) &&
			self::table_exists( $otp_table ) &&
			self::table_exists( $webauthn_table )
		) {
			return true;
		}

		self::create_tables();

		return self::table_exists( $pending_table )
			&& self::table_exists( $otp_table )
			&& self::table_exists( $webauthn_table );
	}

	/**
	 * Check if a table exists.
	 *
	 * @param string $table_name Fully-qualified table name.
	 * @return bool
	 */
	private static function table_exists( $table_name ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
	}

	/**
	 * Create custom database tables.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$otp_table       = $wpdb->prefix . 'slr_otp_codes';
		$webauthn_table  = $wpdb->prefix . 'slr_webauthn_credentials';

		$sql_otp = "CREATE TABLE {$otp_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			identifier varchar(255) NOT NULL,
			channel varchar(20) NOT NULL DEFAULT 'email',
			purpose varchar(20) NOT NULL DEFAULT 'verify',
			code_hash varchar(255) NOT NULL,
			expires_at datetime NOT NULL,
			attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY identifier (identifier),
			KEY identifier_purpose (identifier, channel, purpose),
			KEY expires_at (expires_at)
		) {$charset_collate};";

		$pending_table = $wpdb->prefix . 'slr_pending_registrations';

		$sql_pending = "CREATE TABLE {$pending_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			token varchar(64) NOT NULL,
			identifier_hash varchar(64) NOT NULL,
			channel varchar(20) NOT NULL DEFAULT 'email',
			data_encrypted longtext NOT NULL,
			expires_at datetime NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY token (token),
			KEY identifier_hash (identifier_hash),
			KEY expires_at (expires_at)
		) {$charset_collate};";

		$sql_webauthn = "CREATE TABLE {$webauthn_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			credential_id varchar(512) NOT NULL,
			public_key longtext NOT NULL,
			counter bigint(20) unsigned NOT NULL DEFAULT 0,
			transports varchar(255) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			last_used_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY credential_id (credential_id),
			KEY user_id (user_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_otp );
		dbDelta( $sql_pending );
		dbDelta( $sql_webauthn );

		update_option( 'slr_db_version', SLR_VERSION );
	}
}
