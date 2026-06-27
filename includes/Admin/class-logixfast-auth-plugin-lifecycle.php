<?php
/**
 * Deactivate / delete glass modals on plugins.php.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Admin; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Services\UninstallService;
use LogixFastAuth\Settings;

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
		add_action( 'wp_ajax_logixfast_auth_flag_purge_data', array( $this, 'ajax_flag_purge_data' ) );
	}

	/**
	 * Store admin consent to purge LogixFastAuth data.
	 *
	 * @return void
	 */
	public function ajax_flag_purge_data() {
		check_ajax_referer( 'logixfast_auth_lifecycle', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'logixfast-auth' ) ), 403 );
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

		$css_path = LOGIXFAST_AUTH_PLUGIN_DIR . 'assets/css/plugin-lifecycle.css';
		$js_path  = LOGIXFAST_AUTH_PLUGIN_DIR . 'assets/js/plugin-lifecycle.js';

		wp_enqueue_style(
			'logixfast-auth-plugin-lifecycle',
			LOGIXFAST_AUTH_PLUGIN_URL . 'assets/css/plugin-lifecycle.css',
			array(),
			file_exists( $css_path ) ? (string) filemtime( $css_path ) : LOGIXFAST_AUTH_VERSION
		);

		wp_enqueue_script(
			'logixfast-auth-plugin-lifecycle',
			LOGIXFAST_AUTH_PLUGIN_URL . 'assets/js/plugin-lifecycle.js',
			array(),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : LOGIXFAST_AUTH_VERSION,
			true
		);

		$appearance = Settings::get( 'appearance' );
		$preview    = UninstallService::get_preview();

		/* translators: %d: number of passkeys stored by LogixFastAuth. */
		$passkey_count_label = __( '%d passkey(s) will be removed.', 'logixfast-auth' );

		wp_localize_script(
			'logixfast-auth-plugin-lifecycle',
			'LOGIXFAST_AUTH_LIFECYCLE',
			array(
				'pluginFile'        => LOGIXFAST_AUTH_PLUGIN_BASENAME,
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'logixfast_auth_lifecycle' ),
				'phoneUsers'        => (int) $preview['phone_users'],
				'passkeyRows'       => (int) $preview['passkey_rows'],
				'loginPageTitle'    => (string) $preview['login_page_title'],
				'usesLogixFastAuthPhone'      => (bool) $preview['uses_logixfast_auth_phone'],
				'woocommerceActive' => (bool) $preview['woocommerce_active'],
				'tutorActive'       => (bool) $preview['tutor_active'],
				'style'             => array(
					'primary' => (string) ( $appearance['primary'] ?? '#d6336c' ),
					'text'    => (string) ( $appearance['text'] ?? '#1e293b' ),
					'blur'    => (string) ( $appearance['blur'] ?? '24px' ),
					'radius'  => (string) ( $appearance['radius'] ?? '12px' ),
				),
				'i18n'              => array(
					'deactivateTitle' => __( 'Deactivate LogixFast Auth', 'logixfast-auth' ),
					'deleteTitle'     => __( 'Delete LogixFast Auth', 'logixfast-auth' ),
					'purgeToggle'     => __( 'Delete all LogixFastAuth plugin data', 'logixfast-auth' ),
					'purgeHint'       => __( 'Tables, settings, passkeys, and LogixFastAuth phones. WooCommerce & Tutor phones stay.', 'logixfast-auth' ),
					'purgeSummary'    => __( 'This permanently deletes all LogixFastAuth data.', 'logixfast-auth' ),
					'purgeAck'        => __( 'I understand this cannot be undone', 'logixfast-auth' ),
					'cancel'          => __( 'Cancel', 'logixfast-auth' ),
					'deactivateKeep'  => __( 'Deactivate (keep data)', 'logixfast-auth' ),
					'deactivatePurge' => __( 'Deactivate and delete data', 'logixfast-auth' ),
					'deleteKeep'      => __( 'Delete plugin (keep data)', 'logixfast-auth' ),
					'deletePurge'     => __( 'Delete plugin and data', 'logixfast-auth' ),
					'passkeyCount'    => $passkey_count_label,
					'working'         => __( 'Working…', 'logixfast-auth' ),
				),
			)
		);
	}
}
