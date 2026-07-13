<?php
/**
 * Passkey management UI surfaces and URLs.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Integrations\Integration_Availability;
use LogixFastAuth\Integrations\Tutor_Compat;
use LogixFastAuth\Integrations\WooCommerce_Compat;
use LogixFastAuth\Settings;

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
			'profile' => admin_url( 'profile.php#logixfast-auth-passkey-manager' ),
		);

		if ( Integration_Availability::is_tutor_available() && function_exists( 'tutor_utils' ) ) {
			$urls['tutor'] = Tutor_Compat::get_passkeys_settings_url();
		}

		if ( Integration_Availability::is_woocommerce_available() ) {
			$urls['woocommerce'] = WooCommerce_Compat::get_passkeys_account_url();
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

		$logixfast_auth_pk_button_class = $args['button_class'] ?? 'button button-primary';
		require LOGIXFAST_AUTH_PLUGIN_DIR . 'templates/partials/passkey-manager.php';
	}
}
