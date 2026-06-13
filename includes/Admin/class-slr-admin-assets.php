<?php
/**
 * Admin SPA asset enqueuing.
 *
 * @package SLR
 */

namespace SLR\Admin;

use SLR\Assets;

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
	 * Enqueue admin assets on SLR pages only.
	 *
	 * Production: single IIFE bundle (classic script).
	 * Dev (SLR_DEV): Vite HMR via @vite/client + src/admin/main.tsx.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( 'toplevel_page_slr' !== $hook ) {
			return;
		}

		Assets::init();

		wp_enqueue_style(
			'slr-font-urbanist',
			'https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap',
			array(),
			null
		);

		if ( Assets::is_dev_mode() ) {
			Assets::enqueue_vite_dev_entry( 'slr-admin', 'src/admin/main.tsx' );
		} else {
			$css_path = SLR_PLUGIN_DIR . 'assets/dist/admin/admin.css';
			if ( file_exists( $css_path ) ) {
				wp_enqueue_style(
					'slr-admin',
					Assets::get_asset_url( 'admin/admin.css' ),
					array(),
					(string) filemtime( $css_path )
				);
			}

			Assets::enqueue_classic_script( 'slr-admin', 'admin.js', array(), true );
		}

		$frontend_css_files = Assets::get_entry_css_files( Assets::ENTRY_POPUP );
		$frontend_css_urls  = ! empty( $frontend_css_files ) ? array_values( $frontend_css_files ) : array();

		wp_localize_script(
			'slr-admin',
			'SLR_ADMIN',
			array(
				'apiUrl'           => rest_url( 'slr/v1' ),
				'nonce'            => wp_create_nonce( 'wp_rest' ),
				'homeUrl'          => admin_url(),
				'pages'            => rest_url( 'slr/v1/settings/pages' ),
				'frontendCssUrls'  => $frontend_css_urls,
				'i18n'    => array(
					'title'        => __( 'Smart Login Registration', 'smart-login-registration' ),
					'general'      => __( 'General', 'smart-login-registration' ),
					'auth'         => __( 'Authentication', 'smart-login-registration' ),
					'mail'         => __( 'Email / SMTP', 'smart-login-registration' ),
					'sms'          => __( 'SMS Providers', 'smart-login-registration' ),
					'integrations' => __( 'Integrations', 'smart-login-registration' ),
					'appearance'   => __( 'Appearance', 'smart-login-registration' ),
					'security'     => __( 'Security', 'smart-login-registration' ),
					'save'         => __( 'Save Settings', 'smart-login-registration' ),
					'saved'        => __( 'Settings saved.', 'smart-login-registration' ),
					'testSmtp'     => __( 'Send Test Email', 'smart-login-registration' ),
					'loading'      => __( 'Loading...', 'smart-login-registration' ),
				),
			)
		);
	}
}
