<?php
/**
 * Tutor LMS integration hooks.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Integrations; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tutor_Sync
 */
class Tutor_Sync {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'logixfast_auth_after_registration', array( $this, 'on_register' ), 10, 2 );
		add_action( 'logixfast_auth_after_login', array( $this, 'on_login_success' ) );
	}

	/**
	 * Handle post-registration Tutor hooks.
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Profile data.
	 * @return void
	 */
	public function on_register( $user_id, $data = array() ) {
		if ( ! empty( $data['phone'] ) ) {
			update_user_meta( $user_id, 'phone_number', sanitize_text_field( $data['phone'] ) );
		}

		do_action( 'tutor_after_student_signup', $user_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Tutor LMS hook.
	}

	/**
	 * Handle post-login Tutor hooks.
	 *
	 * @param \WP_User $user User object.
	 * @return void
	 */
	public function on_login_success( $user ) {
		do_action( 'tutor_after_login_success', $user->ID, array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Tutor LMS hook.
	}
}
