<?php
/**
 * Main plugin bootstrap.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Admin\Admin_Menu;
use LogixFastAuth\Admin\Admin_Assets;
use LogixFastAuth\Admin\Admin_Shell;
use LogixFastAuth\Admin\Plugin_Lifecycle;
use LogixFastAuth\Api\Rest_Controller;
use LogixFastAuth\Frontend\Frontend_Assets;
use LogixFastAuth\Frontend\Popup;
use LogixFastAuth\Frontend\Dedicated_Page;
use LogixFastAuth\Frontend\Login_Redirect;
use LogixFastAuth\Integrations\Integration_Manager;
use LogixFastAuth\Activator;
use LogixFastAuth\Profile\Passkey_Profile;
use LogixFastAuth\Profile\Phone_Profile;
use LogixFastAuth\Services\LoginPageService;
use LogixFastAuth\Services\MailService;
use LogixFastAuth\Services\Passkey_Assets;

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
			new Plugin_Lifecycle();
		}

		new Passkey_Profile();
		new Phone_Profile();
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
			wp_set_script_translations( 'logixfast-auth-admin', 'logixfast-auth', LOGIXFAST_AUTH_PLUGIN_DIR . 'languages' );
			wp_set_script_translations( 'logixfast-auth-popup', 'logixfast-auth', LOGIXFAST_AUTH_PLUGIN_DIR . 'languages' );
			wp_set_script_translations( 'logixfast-auth-page', 'logixfast-auth', LOGIXFAST_AUTH_PLUGIN_DIR . 'languages' );
		}
	}
}
