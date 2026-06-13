<?php
/**
 * WebAuthn credentials database repository.
 *
 * @package SLR
 */

namespace SLR\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WebAuthnRepository
 */
class WebAuthnRepository {

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	private function table() {
		global $wpdb;
		return $wpdb->prefix . 'slr_webauthn_credentials';
	}

	/**
	 * Create credential.
	 *
	 * @param array $data Credential data.
	 * @return int|false
	 */
	public function create( $data ) {
		global $wpdb;

		$wpdb->insert(
			$this->table(),
			array(
				'user_id'       => $data['user_id'],
				'credential_id' => $data['credential_id'],
				'public_key'    => $data['public_key'],
				'counter'       => $data['counter'] ?? 0,
				'transports'    => $data['transports'] ?? '',
				'created_at'    => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Get credentials for user.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public function get_by_user( $user_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE user_id = %d", $user_id )
		);
	}

	/**
	 * Get credential by ID.
	 *
	 * @param string $credential_id Credential ID.
	 * @return object|null
	 */
	public function get_by_credential_id( $credential_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE credential_id = %s", $credential_id )
		);
	}

	/**
	 * Update sign counter.
	 *
	 * @param int $id      Record ID.
	 * @param int $counter New counter.
	 * @return void
	 */
	public function update_counter( $id, $counter ) {
		global $wpdb;
		$wpdb->update(
			$this->table(),
			array(
				'counter'      => $counter,
				'last_used_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $id ),
			array( '%d', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Delete a credential by ID and user.
	 *
	 * @param int $id      Record ID.
	 * @param int $user_id User ID (ownership check).
	 * @return bool
	 */
	public function delete( $id, $user_id ) {
		global $wpdb;
		$rows = $wpdb->delete(
			$this->table(),
			array( 'id' => (int) $id, 'user_id' => (int) $user_id ),
			array( '%d', '%d' )
		);
		return $rows > 0;
	}

	/**
	 * Count credentials for a user.
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	public function count_by_user( $user_id ) {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$this->table()} WHERE user_id = %d", $user_id )
		);
	}
}
