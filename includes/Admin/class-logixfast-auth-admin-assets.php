<?php
/**
 * Admin SPA asset enqueuing.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Admin; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin namespace prefix.

use LogixFastAuth\Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_Assets
 */
class Admin_Assets {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue admin assets on LogixFastAuth pages only.
	 *
	 * Production: single IIFE bundle (classic script).
	 * Dev (LOGIXFAST_AUTH_DEV): Vite HMR via @vite/client + src/admin/main.tsx.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( 'toplevel_page_logixfastauth' !== $hook ) {
			return;
		}

		Assets::init();

		if ( Assets::is_dev_mode() ) {
			Assets::enqueue_vite_dev_entry( 'logixfast-auth-admin', 'src/admin/main.tsx' );
		} else {
			$css_path = LOGIXFAST_AUTH_PLUGIN_DIR . 'assets/dist/admin/admin.css';
			if ( file_exists( $css_path ) ) {
				wp_enqueue_style(
					'logixfast-auth-admin',
					Assets::get_asset_url( 'admin/admin.css' ),
					array(),
					(string) filemtime( $css_path )
				);
			}

			Assets::enqueue_classic_script( 'logixfast-auth-admin', 'admin.js', array(), true );
		}

		wp_localize_script(
			'logixfast-auth-admin',
			'LOGIXFAST_AUTH_ADMIN',
			array(
				'apiUrl'           => rest_url( 'logixfast-auth/v1' ),
				'nonce'            => wp_create_nonce( 'wp_rest' ),
				'homeUrl'            => admin_url(),
				'profilePasskeysUrl' => admin_url( 'profile.php#logixfast-auth-passkey-manager' ),
				'pages'              => rest_url( 'logixfast-auth/v1/settings/pages' ),
				'i18n'    => array(
					'title'        => __( 'LogixFast Auth', 'logixfast-auth' ),
					'general'      => __( 'General', 'logixfast-auth' ),
					'auth'         => __( 'Authentication', 'logixfast-auth' ),
					'mail'         => __( 'Email / SMTP', 'logixfast-auth' ),
					'sms'          => __( 'SMS Providers', 'logixfast-auth' ),
					'integrations' => __( 'Integrations', 'logixfast-auth' ),
					'appearance'   => __( 'Appearance', 'logixfast-auth' ),
					'security'     => __( 'Security', 'logixfast-auth' ),
					'save'         => __( 'Save Settings', 'logixfast-auth' ),
					'saved'        => __( 'Settings saved.', 'logixfast-auth' ),
					'testSmtp'     => __( 'Send Test Email', 'logixfast-auth' ),
					'loading'      => __( 'Loading...', 'logixfast-auth' ),
				),
			)
		);
	}
}
