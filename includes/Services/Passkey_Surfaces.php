<?php
/**
 * Passkey management UI surfaces and URLs.
 *
 * @package SLR
 */

namespace SLR\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Integrations\Integration_Availability;
use SLR\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Passkey_Surfaces
 */
class Passkey_Surfaces {

	/**
	 * Whether passkey management should be shown.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$auth = Settings::get( 'auth' );
		return ! empty( $auth['webauthn_enabled'] );
	}

	/**
	 * URLs where users can manage their own passkeys.
	 *
	 * @return array<string, string>
	 */
	public static function get_manage_urls() {
		$urls = array(
			'profile' => admin_url( 'profile.php#slr-passkey-manager' ),
		);

		if ( Integration_Availability::is_tutor_available() && function_exists( 'tutor_utils' ) ) {
			$urls['tutor'] = (string) tutor_utils()->get_tutor_dashboard_page_permalink( 'settings/passkeys' );
		}

		if ( Integration_Availability::is_woocommerce_available() && function_exists( 'wc_get_account_endpoint_url' ) ) {
			$urls['woocommerce'] = (string) wc_get_account_endpoint_url( 'passkeys' );
		}

		return $urls;
	}

	/**
	 * Render the shared passkey manager partial.
	 *
	 * @param array<string, string> $args Template arguments.
	 * @return void
	 */
	public static function render_manager( $args = array() ) {
		if ( ! self::is_enabled() || ! is_user_logged_in() ) {
			return;
		}

		$slr_pk_button_class = $args['button_class'] ?? 'button button-primary';
		require SLR_PLUGIN_DIR . 'templates/partials/passkey-manager.php';
	}
}
