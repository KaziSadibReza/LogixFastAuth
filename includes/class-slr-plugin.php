<?php
/**
 * Main plugin bootstrap.
 *
 * @package SLR
 */

namespace SLR; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Admin\Admin_Menu;
use SLR\Admin\Admin_Assets;
use SLR\Admin\Admin_Shell;
use SLR\Api\Rest_Controller;
use SLR\Frontend\Frontend_Assets;
use SLR\Frontend\Popup;
use SLR\Frontend\Dedicated_Page;
use SLR\Frontend\Login_Redirect;
use SLR\Integrations\Integration_Manager;
use SLR\Activator;
use SLR\Profile\Passkey_Profile;
use SLR\Services\LoginPageService;
use SLR\Services\MailService;
use SLR\Services\Passkey_Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		Activator::maybe_upgrade();
		add_action( 'init', array( $this, 'register_script_translations' ) );

		if ( is_admin() ) {
			new Admin_Menu();
			new Admin_Assets();
			new Admin_Shell();
		}

		new Passkey_Profile();
		new Passkey_Assets();
		MailService::register();

		new Rest_Controller();
		new Frontend_Assets();
		new Popup();
		new Dedicated_Page();
		new Login_Redirect();
		new Integration_Manager();
		new LoginPageService();
	}

	/**
	 * Register JS translations (WP.org loads PHP translations automatically).
	 *
	 * @return void
	 */
	public function register_script_translations() {
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'slr-admin', 'smart-login-registration', SLR_PLUGIN_DIR . 'languages' );
			wp_set_script_translations( 'slr-popup', 'smart-login-registration', SLR_PLUGIN_DIR . 'languages' );
			wp_set_script_translations( 'slr-page', 'smart-login-registration', SLR_PLUGIN_DIR . 'languages' );
		}
	}
}
