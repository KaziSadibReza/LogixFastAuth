<?php
/**
 * Deactivate / delete glass modals on plugins.php.
 *
 * @package SLR
 */

namespace SLR\Admin; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Services\UninstallService;
use SLR\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin_Lifecycle
 */
class Plugin_Lifecycle {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_ajax_slr_flag_purge_data', array( $this, 'ajax_flag_purge_data' ) );
	}

	/**
	 * Store admin consent to purge SLR data.
	 *
	 * @return void
	 */
	public function ajax_flag_purge_data() {
		check_ajax_referer( 'slr_lifecycle', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'smart-login-registration' ) ), 403 );
		}

		UninstallService::flag_purge_data();
		wp_send_json_success();
	}

	/**
	 * Enqueue modal assets on plugins.php.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( 'plugins.php' !== $hook ) {
			return;
		}

		$css_path = SLR_PLUGIN_DIR . 'assets/css/plugin-lifecycle.css';
		$js_path  = SLR_PLUGIN_DIR . 'assets/js/plugin-lifecycle.js';

		wp_enqueue_style(
			'slr-plugin-lifecycle',
			SLR_PLUGIN_URL . 'assets/css/plugin-lifecycle.css',
			array(),
			file_exists( $css_path ) ? (string) filemtime( $css_path ) : SLR_VERSION
		);

		wp_enqueue_script(
			'slr-plugin-lifecycle',
			SLR_PLUGIN_URL . 'assets/js/plugin-lifecycle.js',
			array(),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : SLR_VERSION,
			true
		);

		$appearance = Settings::get( 'appearance' );
		$preview    = UninstallService::get_preview();

		/* translators: %d: number of passkeys stored by SLR. */
		$passkey_count_label = __( '%d passkey(s) will be removed.', 'smart-login-registration' );

		wp_localize_script(
			'slr-plugin-lifecycle',
			'SLR_LIFECYCLE',
			array(
				'pluginFile'        => SLR_PLUGIN_BASENAME,
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'slr_lifecycle' ),
				'phoneUsers'        => (int) $preview['phone_users'],
				'passkeyRows'       => (int) $preview['passkey_rows'],
				'loginPageTitle'    => (string) $preview['login_page_title'],
				'usesSlrPhone'      => (bool) $preview['uses_slr_phone'],
				'woocommerceActive' => (bool) $preview['woocommerce_active'],
				'tutorActive'       => (bool) $preview['tutor_active'],
				'style'             => array(
					'primary' => (string) ( $appearance['primary'] ?? '#d6336c' ),
					'text'    => (string) ( $appearance['text'] ?? '#1e293b' ),
					'blur'    => (string) ( $appearance['blur'] ?? '24px' ),
					'radius'  => (string) ( $appearance['radius'] ?? '12px' ),
				),
				'i18n'              => array(
					'deactivateTitle' => __( 'Deactivate Smart Login Registration', 'smart-login-registration' ),
					'deleteTitle'     => __( 'Delete Smart Login Registration', 'smart-login-registration' ),
					'purgeToggle'     => __( 'Delete all SLR plugin data', 'smart-login-registration' ),
					'purgeHint'       => __( 'Tables, settings, passkeys, and SLR phones. WooCommerce & Tutor phones stay.', 'smart-login-registration' ),
					'purgeSummary'    => __( 'This permanently deletes all SLR data.', 'smart-login-registration' ),
					'purgeAck'        => __( 'I understand this cannot be undone', 'smart-login-registration' ),
					'cancel'          => __( 'Cancel', 'smart-login-registration' ),
					'deactivateKeep'  => __( 'Deactivate (keep data)', 'smart-login-registration' ),
					'deactivatePurge' => __( 'Deactivate and delete data', 'smart-login-registration' ),
					'deleteKeep'      => __( 'Delete plugin (keep data)', 'smart-login-registration' ),
					'deletePurge'     => __( 'Delete plugin and data', 'smart-login-registration' ),
					'passkeyCount'    => $passkey_count_label,
					'working'         => __( 'Working…', 'smart-login-registration' ),
				),
			)
		);
	}
}
