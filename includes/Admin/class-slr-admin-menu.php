<?php
/**
 * Admin menu registration.
 *
 * @package SLR
 */

namespace SLR\Admin;

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
			'dashicons-lock',
			58
		);
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
