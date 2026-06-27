<?php
/**
 * Passkeys tab on WooCommerce My Account.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Integrations; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Services\Passkey_Surfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WooCommerce_Passkeys
 */
class WooCommerce_Passkeys {

	const ENDPOINT = 'passkeys';

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action( 'init', array( $this, 'register_endpoint' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render_endpoint' ) );
	}

	/**
	 * Register rewrite endpoint.
	 *
	 * @return void
	 */
	public function register_endpoint() {
		if ( ! Passkey_Surfaces::is_enabled() ) {
			return;
		}

		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Add Passkeys item to My Account navigation.
	 *
	 * @param array<string, string> $items Menu items.
	 * @return array<string, string>
	 */
	public function add_menu_item( $items ) {
		if ( ! Passkey_Surfaces::is_enabled() || ! is_user_logged_in() ) {
			return $items;
		}

		$new_items = array();
		foreach ( $items as $key => $label ) {
			$new_items[ $key ] = $label;
			if ( 'edit-account' === $key ) {
				$new_items[ self::ENDPOINT ] = __( 'Passkeys', 'logixfast-auth' );
			}
		}

		if ( ! isset( $new_items[ self::ENDPOINT ] ) ) {
			$new_items[ self::ENDPOINT ] = __( 'Passkeys', 'logixfast-auth' );
		}

		return $new_items;
	}

	/**
	 * Render passkey manager on My Account.
	 *
	 * @return void
	 */
	public function render_endpoint() {
		if ( ! Passkey_Surfaces::is_enabled() ) {
			return;
		}

		echo '<div class="woocommerce-MyAccount-content-logixfast-auth-passkeys">';
		Passkey_Surfaces::render_manager(
			array(
				'button_class' => 'woocommerce-button button alt',
			)
		);
		echo '</div>';
	}
}
