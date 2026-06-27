<?php
/**
 * Admin menu registration.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Admin; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_Menu
 */
class Admin_Menu {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_menu_icon_styles' ) );
		add_filter( 'plugin_action_links_' . LOGIXFAST_AUTH_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Add Settings link on the Plugins list screen.
	 *
	 * @param array<string, string> $links Existing action links.
	 * @return array<string, string>
	 */
	public function plugin_action_links( $links ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $links;
		}

		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=logixfastauth' ) ),
			esc_html__( 'Settings', 'logixfast-auth' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Register top-level LogixFastAuth menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'LogixFast Auth', 'logixfast-auth' ),
			'LogixFast',
			'manage_options',
			'logixfastauth',
			array( $this, 'render_page' ),
			$this->menu_icon_url(),
			58
		);
	}

	/**
	 * Branded menu icon styles (file URL, not data: URI — core svg-painter.js skips img tags).
	 *
	 * @return void
	 */
	public function enqueue_menu_icon_styles() {
		wp_register_style( 'logixfast-auth-admin-menu-icon', false, array(), LOGIXFAST_AUTH_VERSION );
		wp_enqueue_style( 'logixfast-auth-admin-menu-icon' );

		$css = '#adminmenu #toplevel_page_logixfastauth .wp-menu-image img{'
			. 'width:20px;height:20px;padding:6px 0 0;object-fit:contain;'
			. 'opacity:1!important;filter:none!important;'
			. '}'
			. '#adminmenu #toplevel_page_logixfastauth.current .wp-menu-image img,'
			. '#adminmenu #toplevel_page_logixfastauth.wp-has-current-submenu .wp-menu-image img,'
			. '#adminmenu #toplevel_page_logixfastauth:hover .wp-menu-image img,'
			. '#adminmenu #toplevel_page_logixfastauth a:focus .wp-menu-image img{'
			. 'opacity:1!important;filter:none!important;'
			. '}'
			. '#adminmenu #toplevel_page_logixfastauth .wp-menu-image:before{'
			. 'content:none!important;'
			. '}';

		wp_add_inline_style( 'logixfast-auth-admin-menu-icon', $css );
	}

	/**
	 * Branded rounded-square + key icon (full color gradient).
	 *
	 * @return string
	 */
	private function menu_icon_url() {
		$path = LOGIXFAST_AUTH_PLUGIN_DIR . 'assets/images/logixfast-auth-admin-menu-icon.svg';
		$ver  = file_exists( $path ) ? (string) filemtime( $path ) : LOGIXFAST_AUTH_VERSION;

		return add_query_arg( 'ver', rawurlencode( $ver ), LOGIXFAST_AUTH_PLUGIN_URL . 'assets/images/logixfast-auth-admin-menu-icon.svg' );
	}

	/**
	 * Render admin SPA mount point.
	 *
	 * @return void
	 */
	public function render_page() {
		echo '<div class="wrap">';
		echo '<div id="logixfast-auth-admin-root"></div>';
		echo '</div>';
	}
}
