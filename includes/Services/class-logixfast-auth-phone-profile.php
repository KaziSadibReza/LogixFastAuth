<?php
/**
 * LogixFastAuth phone user-meta: used when WooCommerce and Tutor LMS are not active.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Integrations\Integration_Availability;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Phone_Profile
 */
class Phone_Profile {

	const META_KEY = 'logixfast_auth_phone';

	/**
	 * LogixFastAuth stores phone in its own field when no WooCommerce / Tutor LMS.
	 *
	 * @return bool
	 */
	public static function uses_logixfast_auth_field() {
		return ! Integration_Availability::is_woocommerce_available()
			&& ! Integration_Availability::is_tutor_available();
	}

	/**
	 * Save phone from LogixFastAuth registration to the correct profile field.
	 *
	 * @param int    $user_id User ID.
	 * @param string $phone   Phone number.
	 * @return void
	 */
	public static function save_registration_phone( $user_id, $phone ) {
		$phone = sanitize_text_field( $phone );
		if ( '' === $phone || $user_id <= 0 ) {
			return;
		}

		if ( self::uses_logixfast_auth_field() ) {
			update_user_meta( $user_id, self::META_KEY, $phone );
		}
	}

	/**
	 * Meta keys searched for phone login (includes legacy LogixFastAuth data).
	 *
	 * @return string[]
	 */
	public static function get_lookup_meta_keys() {
		$keys = array( self::META_KEY );

		if ( Integration_Availability::is_woocommerce_available() ) {
			$keys[] = 'billing_phone';
		}

		if ( Integration_Availability::is_tutor_available() ) {
			$keys[] = 'phone_number';
		}

		return array_values( array_unique( $keys ) );
	}

	/**
	 * Count users with LogixFastAuth-owned phone meta.
	 *
	 * @return int
	 */
	public static function count_logixfast_auth_phone_users() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin lifecycle preview count.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value <> ''",
				self::META_KEY
			)
		);
	}

	/**
	 * Phone fields shown on the WordPress user profile screen.
	 *
	 * @return array<string, array{label:string,description:string}>
	 */
	public static function get_profile_fields() {
		$fields = array();

		if ( self::uses_logixfast_auth_field() ) {
			$fields[ self::META_KEY ] = array(
				'label'       => __( 'Phone number', 'logixfast-auth' ),
				'description' => __( 'Used for LogixFastAuth sign-in and registration.', 'logixfast-auth' ),
			);
		}

		if ( Integration_Availability::is_woocommerce_available() ) {
			$fields['billing_phone'] = array(
				'label'       => __( 'Billing phone', 'logixfast-auth' ),
				'description' => __( 'WooCommerce customer phone used for LogixFastAuth login.', 'logixfast-auth' ),
			);
		}

		if ( Integration_Availability::is_tutor_available() ) {
			$fields['phone_number'] = array(
				'label'       => __( 'Tutor phone', 'logixfast-auth' ),
				'description' => __( 'Tutor LMS profile phone used for LogixFastAuth login.', 'logixfast-auth' ),
			);
		}

		return $fields;
	}
}
