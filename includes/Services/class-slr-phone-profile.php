<?php
/**
 * SLR phone user-meta: used when WooCommerce and Tutor LMS are not active.
 *
 * @package SLR
 */

namespace SLR\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Integrations\Integration_Availability;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Phone_Profile
 */
class Phone_Profile {

	const META_KEY = 'slr_phone';

	/**
	 * SLR stores phone in its own field when no WooCommerce / Tutor LMS.
	 *
	 * @return bool
	 */
	public static function uses_slr_field() {
		return ! Integration_Availability::is_woocommerce_available()
			&& ! Integration_Availability::is_tutor_available();
	}

	/**
	 * Save phone from SLR registration to the correct profile field.
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

		if ( self::uses_slr_field() ) {
			update_user_meta( $user_id, self::META_KEY, $phone );
		}
	}

	/**
	 * Meta keys searched for phone login (includes legacy SLR data).
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
	 * Count users with SLR-owned phone meta.
	 *
	 * @return int
	 */
	public static function count_slr_phone_users() {
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

		if ( self::uses_slr_field() ) {
			$fields[ self::META_KEY ] = array(
				'label'       => __( 'Phone number', 'smart-login-registration' ),
				'description' => __( 'Used for SLR sign-in and registration.', 'smart-login-registration' ),
			);
		}

		if ( Integration_Availability::is_woocommerce_available() ) {
			$fields['billing_phone'] = array(
				'label'       => __( 'Billing phone', 'smart-login-registration' ),
				'description' => __( 'WooCommerce customer phone used for SLR login.', 'smart-login-registration' ),
			);
		}

		if ( Integration_Availability::is_tutor_available() ) {
			$fields['phone_number'] = array(
				'label'       => __( 'Tutor phone', 'smart-login-registration' ),
				'description' => __( 'Tutor LMS profile phone used for SLR login.', 'smart-login-registration' ),
			);
		}

		return $fields;
	}
}
