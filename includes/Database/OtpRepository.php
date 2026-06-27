<?php
/**
 * OTP codes database repository.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Database; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Activator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OtpRepository
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery
class OtpRepository {

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	private function table() {
		global $wpdb;
		return $wpdb->prefix . 'logixfast_auth_otp_codes';
	}

	/**
	 * Create OTP record.
	 *
	 * @param array $data Record data.
	 * @return int|false
	 */
	public function create( $data ) {
		global $wpdb;

		$row = array(
			'identifier' => $data['identifier'],
			'channel'    => $data['channel'],
			'purpose'    => $data['purpose'],
			'code_hash'  => $data['code_hash'],
			'expires_at' => $data['expires_at'],
			'attempts'   => 0,
			'created_at' => current_time( 'mysql', true ),
		);
		$formats = array( '%s', '%s', '%s', '%s', '%s', '%d', '%s' );

		$inserted = $wpdb->insert( $this->table(), $row, $formats );

		if ( false === $inserted && class_exists( Activator::class ) && Activator::ensure_tables() ) {
			$inserted = $wpdb->insert( $this->table(), $row, $formats );
		}

		if ( false === $inserted ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Delete all OTP records for an identifier.
	 *
	 * @param string $identifier Identifier hash.
	 * @param string $channel    Channel.
	 * @param string $purpose    Purpose.
	 * @return void
	 */
	public function delete_by_identifier( $identifier, $channel, $purpose ) {
		global $wpdb;
		$wpdb->delete(
			$this->table(),
			array(
				'identifier' => $identifier,
				'channel'    => $channel,
				'purpose'    => $purpose,
			),
			array( '%s', '%s', '%s' )
		);
	}

	/**
	 * Last DB error.
	 *
	 * @return string
	 */
	public function last_error() {
		global $wpdb;
		return (string) $wpdb->last_error;
	}

	/**
	 * Get latest OTP for identifier.
	 *
	 * @param string $identifier Identifier hash.
	 * @param string $channel    Channel.
	 * @param string $purpose    Purpose.
	 * @return object|null
	 */
	public function get_latest( $identifier, $channel, $purpose ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is wpdb-prefixed and internal.
				"SELECT * FROM {$this->table()} WHERE identifier = %s AND channel = %s AND purpose = %s ORDER BY id DESC LIMIT 1",
				$identifier,
				$channel,
				$purpose
			)
		);
	}

	/**
	 * Increment attempt count.
	 *
	 * @param int $id Record ID.
	 * @return void
	 */
	public function increment_attempts( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is wpdb-prefixed and internal.
		$wpdb->query( $wpdb->prepare( "UPDATE {$this->table()} SET attempts = attempts + 1 WHERE id = %d", $id ) );
	}

	/**
	 * Delete OTP record.
	 *
	 * @param int $id Record ID.
	 * @return void
	 */
	public function delete( $id ) {
		global $wpdb;
		$wpdb->delete( $this->table(), array( 'id' => $id ), array( '%d' ) );
	}
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery
