<?php
/**
 * Honeypot and spam protection.
 *
 * @package SLR
 */

namespace SLR\Services;

use SLR\Settings;
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
			if ( ! empty( $data['slr_hp'] ) ) {
				return new WP_Error( 'slr_spam', __( 'Request blocked.', 'smart-login-registration' ), array( 'status' => 403 ) );
			}
		}

		$verified = apply_filters( 'slr_spam_verify', true, $data );
		if ( is_wp_error( $verified ) ) {
			return $verified;
		}
		if ( false === $verified ) {
			return new WP_Error( 'slr_spam', __( 'Request blocked.', 'smart-login-registration' ), array( 'status' => 403 ) );
		}

		return true;
	}
}
