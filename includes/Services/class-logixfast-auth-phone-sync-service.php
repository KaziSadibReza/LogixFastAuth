<?php
/**
 * Bulk-sync user phone fields across LogixFastAuth, WooCommerce, and Tutor LMS.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Integrations\Integration_Availability;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Phone_Sync_Service
 */
class Phone_Sync_Service {

	const TARGET_WOOCOMMERCE = 'woocommerce';
	const TARGET_TUTOR       = 'tutor';
	const TARGET_ALL         = 'all';

	/**
	 * Summary counts for the integrations admin UI.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_preview() {
		$preview = array(
			'users_with_phone' => 0,
			'woocommerce'      => array(
				'available' => Integration_Availability::is_woocommerce_available(),
				'pending'   => 0,
			),
			'tutor'            => array(
				'available' => Integration_Availability::is_tutor_available(),
				'pending'   => 0,
			),
		);

		foreach ( self::get_user_ids_with_phone_data() as $user_id ) {
			$canonical = self::resolve_canonical_phone( (int) $user_id );
			if ( '' === $canonical ) {
				continue;
			}

			++$preview['users_with_phone'];

			if ( $preview['woocommerce']['available'] && self::needs_sync( (int) $user_id, 'billing_phone', $canonical ) ) {
				++$preview['woocommerce']['pending'];
			}

			if ( $preview['tutor']['available'] && self::needs_sync( (int) $user_id, 'phone_number', $canonical ) ) {
				++$preview['tutor']['pending'];
			}
		}

		return $preview;
	}

	/**
	 * Sync phone numbers to WooCommerce and/or Tutor profile fields.
	 *
	 * @param string $target woocommerce|tutor|all
	 * @return array<string, int>|\WP_Error
	 */
	public static function sync( $target ) {
		$target = sanitize_key( $target );
		if ( ! in_array( $target, array( self::TARGET_WOOCOMMERCE, self::TARGET_TUTOR, self::TARGET_ALL ), true ) ) {
			return new \WP_Error( 'logixfast_auth_phone_sync_invalid', __( 'Invalid phone sync target.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		$meta_keys = self::get_target_meta_keys( $target );
		if ( empty( $meta_keys ) ) {
			return new \WP_Error( 'logixfast_auth_phone_sync_unavailable', __( 'No phone sync targets are available.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		$updated_users = 0;
		$updated_fields  = 0;

		foreach ( self::get_user_ids_with_phone_data() as $user_id ) {
			$user_id   = (int) $user_id;
			$canonical = self::resolve_canonical_phone( $user_id );
			if ( '' === $canonical ) {
				continue;
			}

			$user_updated = false;
			foreach ( $meta_keys as $meta_key ) {
				if ( ! self::needs_sync( $user_id, $meta_key, $canonical ) ) {
					continue;
				}

				update_user_meta( $user_id, $meta_key, $canonical );
				++$updated_fields;
				$user_updated = true;
			}

			if ( $user_updated ) {
				++$updated_users;
			}
		}

		return array(
			'updated_users'  => $updated_users,
			'updated_fields' => $updated_fields,
			'preview'        => self::get_preview(),
		);
	}

	/**
	 * @param string $target Sync target.
	 * @return string[]
	 */
	private static function get_target_meta_keys( $target ) {
		$keys = array();

		if ( self::TARGET_ALL === $target || self::TARGET_WOOCOMMERCE === $target ) {
			if ( Integration_Availability::is_woocommerce_available() ) {
				$keys[] = 'billing_phone';
			}
		}

		if ( self::TARGET_ALL === $target || self::TARGET_TUTOR === $target ) {
			if ( Integration_Availability::is_tutor_available() ) {
				$keys[] = 'phone_number';
			}
		}

		return array_values( array_unique( $keys ) );
	}

	/**
	 * @return int[]
	 */
	private static function get_user_ids_with_phone_data() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is wpdb-prefixed; meta keys are fixed.
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE ( meta_key = %s OR meta_key = %s OR meta_key = %s ) AND meta_value <> ''",
				Phone_Profile::META_KEY,
				'billing_phone',
				'phone_number'
			)
		);

		return array_map( 'intval', $rows ?: array() );
	}

	/**
	 * Prefer LogixFastAuth legacy data, then WooCommerce, then Tutor.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function resolve_canonical_phone( $user_id ) {
		foreach ( array( Phone_Profile::META_KEY, 'billing_phone', 'phone_number' ) as $meta_key ) {
			$phone = sanitize_text_field( (string) get_user_meta( $user_id, $meta_key, true ) );
			if ( '' !== $phone ) {
				return $phone;
			}
		}

		return '';
	}

	/**
	 * @param int    $user_id   User ID.
	 * @param string $meta_key  Target meta key.
	 * @param string $canonical Canonical phone.
	 * @return bool
	 */
	private static function needs_sync( $user_id, $meta_key, $canonical ) {
		$current = sanitize_text_field( (string) get_user_meta( $user_id, $meta_key, true ) );
		return $current !== $canonical;
	}
}
