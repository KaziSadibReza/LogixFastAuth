<?php
/**
 * Honeypot and spam protection.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Settings;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SpamProtection
 */
class SpamProtection {

	/**
	 * Verify request is not spam.
	 *
	 * @param array $data Request data.
	 * @return true|WP_Error
	 */
	public function verify( $data ) {
		$settings = Settings::get( 'general' );

		if ( ! empty( $settings['honeypot_enabled'] ) ) {
			if ( ! empty( $data['logixfast_auth_hp'] ) ) {
				return new WP_Error( 'logixfast_auth_spam', __( 'Request blocked.', 'logixfast-auth' ), array( 'status' => 403 ) );
			}
		}

		$verified = apply_filters( 'logixfast_auth_spam_verify', true, $data ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LogixFastAuth plugin hook.
		if ( is_wp_error( $verified ) ) {
			return $verified;
		}
		if ( false === $verified ) {
			return new WP_Error( 'logixfast_auth_spam', __( 'Request blocked.', 'logixfast-auth' ), array( 'status' => 403 ) );
		}

		return true;
	}
}
