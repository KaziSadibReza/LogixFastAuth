<?php
/**
 * Admin menu registration.
 *
 * @package SLR
 */

namespace SLR\Admin; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

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
		add_filter( 'plugin_action_links_' . SLR_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
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
			esc_url( admin_url( 'admin.php?page=slr' ) ),
			esc_html__( 'Settings', 'smart-login-registration' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Register top-level SLR menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Smart Login Registration', 'smart-login-registration' ),
			'SLR',
			'manage_options',
			'slr',
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
		wp_register_style( 'slr-admin-menu-icon', false, array(), SLR_VERSION );
		wp_enqueue_style( 'slr-admin-menu-icon' );

		$css = '#adminmenu #toplevel_page_slr .wp-menu-image img{'
			. 'width:20px;height:20px;padding:6px 0 0;object-fit:contain;'
			. 'opacity:1!important;filter:none!important;'
			. '}'
			. '#adminmenu #toplevel_page_slr.current .wp-menu-image img,'
			. '#adminmenu #toplevel_page_slr.wp-has-current-submenu .wp-menu-image img,'
			. '#adminmenu #toplevel_page_slr:hover .wp-menu-image img,'
			. '#adminmenu #toplevel_page_slr a:focus .wp-menu-image img{'
			. 'opacity:1!important;filter:none!important;'
			. '}'
			. '#adminmenu #toplevel_page_slr .wp-menu-image:before{'
			. 'content:none!important;'
			. '}';

		wp_add_inline_style( 'slr-admin-menu-icon', $css );
	}

	/**
	 * Branded rounded-square + key icon (full color gradient).
	 *
	 * @return string
	 */
	private function menu_icon_url() {
		$path = SLR_PLUGIN_DIR . 'assets/images/slr-admin-menu-icon.svg';
		$ver  = file_exists( $path ) ? (string) filemtime( $path ) : SLR_VERSION;

		return add_query_arg( 'ver', rawurlencode( $ver ), SLR_PLUGIN_URL . 'assets/images/slr-admin-menu-icon.svg' );
	}

	/**
	 * Render admin SPA mount point.
	 *
	 * @return void
	 */
	public function render_page() {
		echo '<div class="wrap">';
		echo '<div id="slr-admin-root"></div>';
		echo '</div>';
	}
}
