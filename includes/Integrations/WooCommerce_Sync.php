<?php
/**
 * WooCommerce user field synchronization.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Integrations; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WooCommerce_Sync
 */
class WooCommerce_Sync {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'logixfast_auth_after_registration', array( $this, 'on_register' ), 10, 2 );
	}

	/**
	 * Sync user data to WooCommerce customer (profile fields only — no welcome email).
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Profile data (full_name, email, phone).
	 * @return void
	 */
	public function on_register( $user_id, $data ) {
		$user = get_user_by( 'id', $user_id );
		if ( $user && ! in_array( 'customer', (array) $user->roles, true ) ) {
			$user->set_role( 'customer' );
		}

		$full_name = $data['full_name'] ?? '';
		$email     = $data['email'] ?? '';
		$phone     = $data['phone'] ?? '';

		$name_parts = $this->split_name( $full_name );

		update_user_meta( $user_id, 'billing_first_name', $name_parts['first'] );
		update_user_meta( $user_id, 'billing_last_name', $name_parts['last'] );
		update_user_meta( $user_id, 'billing_email', $email );

		if ( ! empty( $phone ) ) {
			update_user_meta( $user_id, 'billing_phone', $phone );
		}

		update_user_meta( $user_id, 'shipping_first_name', $name_parts['first'] );
		update_user_meta( $user_id, 'shipping_last_name', $name_parts['last'] );
	}

	/**
	 * Split full name.
	 *
	 * @param string $full_name Full name.
	 * @return array
	 */
	private function split_name( $full_name ) {
		$parts = preg_split( '/\s+/', trim( $full_name ), 2 );
		return array(
			'first' => $parts[0] ?? '',
			'last'  => $parts[1] ?? '',
		);
	}
}
