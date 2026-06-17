<?php
/**
 * Pending registration storage (before OTP verification).
 *
 * @package SLR
 */

namespace SLR\Database; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Activator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PendingRegistrationRepository
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery
class PendingRegistrationRepository {

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	private function table() {
		global $wpdb;
		return $wpdb->prefix . 'slr_pending_registrations';
	}

	/**
	 * Create pending registration record.
	 *
	 * @param array $data Record data.
	 * @return int|false
	 */
	public function create( $data ) {
		global $wpdb;

		$row = array(
			'token'           => $data['token'],
			'identifier_hash' => $data['identifier_hash'],
			'channel'         => $data['channel'],
			'data_encrypted'  => $data['data_encrypted'],
			'expires_at'      => $data['expires_at'],
			'created_at'      => current_time( 'mysql', true ),
		);
		$formats = array( '%s', '%s', '%s', '%s', '%s', '%s' );

		$inserted = $wpdb->insert( $this->table(), $row, $formats );

		if ( false === $inserted ) {
			if ( class_exists( Activator::class ) && Activator::ensure_tables() ) {
				$inserted = $wpdb->insert( $this->table(), $row, $formats );
			}
		}

		if ( false === $inserted ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Last database error from this connection.
	 *
	 * @return string
	 */
	public function last_error() {
		global $wpdb;
		return (string) $wpdb->last_error;
	}

	/**
	 * Get pending registration by token.
	 *
	 * @param string $token Registration token.
	 * @return object|null
	 */
	public function get_by_token( $token ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is wpdb-prefixed and internal.
				"SELECT * FROM {$this->table()} WHERE token = %s LIMIT 1",
				$token
			)
		);
	}

	/**
	 * Get latest pending registration for an identifier.
	 *
	 * @param string $identifier_hash Identifier hash.
	 * @param string $channel         Channel.
	 * @return object|null
	 */
	public function get_by_identifier( $identifier_hash, $channel ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is wpdb-prefixed and internal.
				"SELECT * FROM {$this->table()} WHERE identifier_hash = %s AND channel = %s ORDER BY id DESC LIMIT 1",
				$identifier_hash,
				$channel
			)
		);
	}

	/**
	 * Delete pending registrations for identifier.
	 *
	 * @param string $identifier_hash Identifier hash.
	 * @return void
	 */
	public function delete_by_identifier( $identifier_hash ) {
		global $wpdb;
		$wpdb->delete( $this->table(), array( 'identifier_hash' => $identifier_hash ), array( '%s' ) );
	}

	/**
	 * Extend pending registration expiry.
	 *
	 * @param string $token      Token.
	 * @param string $expires_at UTC expiry datetime.
	 * @return bool
	 */
	public function update_expires_at( $token, $expires_at ) {
		global $wpdb;

		$updated = $wpdb->update(
			$this->table(),
			array( 'expires_at' => $expires_at ),
			array( 'token' => $token ),
			array( '%s' ),
			array( '%s' )
		);

		return false !== $updated && $updated > 0;
	}

	/**
	 * Delete pending registration by token.
	 *
	 * @param string $token Token.
	 * @return void
	 */
	public function delete_by_token( $token ) {
		global $wpdb;
		$wpdb->delete( $this->table(), array( 'token' => $token ), array( '%s' ) );
	}

	/**
	 * Remove expired pending registrations.
	 *
	 * @return void
	 */
	public function purge_expired() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is wpdb-prefixed; purge job scans expiry.
		$rows = $wpdb->get_results( "SELECT id, expires_at FROM {$this->table()}" );
		if ( empty( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			if ( \SLR\Services\PendingRegistrationService::is_expired( $row->expires_at ) ) {
				$wpdb->delete( $this->table(), array( 'id' => (int) $row->id ), array( '%d' ) );
			}
		}
	}
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery
