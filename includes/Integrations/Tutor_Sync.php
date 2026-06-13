<?php
/**
 * Tutor LMS integration hooks.
 *
 * @package SLR
 */

namespace SLR\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tutor_Sync
 */
class Tutor_Sync {

	/**
	 * Handle post-registration Tutor hooks.
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Registration data.
	 * @return void
	 */
	public static function on_register( $user_id, $data = array() ) {
		if ( ! empty( $data['phone'] ) ) {
			update_user_meta( $user_id, 'phone_number', sanitize_text_field( $data['phone'] ) );
		}

		if ( function_exists( 'tutor_utils' ) ) {
			do_action( 'tutor_after_student_signup', $user_id );
		}
	}

	/**
	 * Handle post-login Tutor hooks.
	 *
	 * @param \WP_User $user User object.
	 * @return void
	 */
	public static function on_login_success( $user ) {
		if ( function_exists( 'tutor_utils' ) ) {
			do_action( 'tutor_after_login_success', $user );
		}
	}
}
