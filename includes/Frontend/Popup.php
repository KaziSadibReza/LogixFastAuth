<?php
/**
 * Popup portal and bootstrap.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Frontend; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Assets;
use LogixFastAuth\Integrations\Integration_Availability;
use LogixFastAuth\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Popup
 */
class Popup {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_footer', array( $this, 'render_portal' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_bootstrap' ), 5 );
	}

	/**
	 * Enqueue lightweight bootstrap on all frontend pages.
	 *
	 * @return void
	 */
	public function enqueue_bootstrap() {
		if ( is_admin() || \LogixFastAuth\Settings::is_dedicated_page() ) {
			return;
		}

		if ( Assets::is_dev_mode() ) {
			Frontend_Assets::enqueue_all( 'popup' );
			$this->enqueue_early_click_guard( 'logixfast-auth-popup' );
			return;
		}

		wp_enqueue_script( 'logixfast-auth-bootstrap' );
		$this->enqueue_early_click_guard( 'logixfast-auth-bootstrap' );
	}

	/**
	 * Render popup mount point.
	 *
	 * @return void
	 */
	public function render_portal() {
		if ( is_admin() || Settings::is_dedicated_page() ) {
			return;
		}

		echo '<div id="logixfast-auth-root" data-mode="popup" aria-hidden="true"></div>';
	}

	/**
	 * Intercept Tutor login triggers before deferred LogixFastAuth scripts load.
	 *
	 * @return void
	 */
	private function enqueue_early_click_guard( $handle ) {
		$integrations = Settings::get( 'integrations' );
		if ( ! Integration_Availability::is_tutor_available() || ! Settings::to_bool( $integrations['replace_tutor'] ?? false ) ) {
			return;
		}

		wp_add_inline_script(
			$handle,
			"(function () {
			var selectors = '.tutor-open-login-modal, .tutor-course-entry-box-login button, .tutor-course-entry-box-login a';
			function tryOpen() {
				if (window.LogixFastAuth && typeof window.LogixFastAuth.open === 'function') {
					window.LogixFastAuth.open('login');
					return true;
				}
				return false;
			}
			document.addEventListener('click', function (event) {
				var el = event.target.closest(selectors);
				if (!el) return;
				event.preventDefault();
				event.stopImmediatePropagation();
				if (!tryOpen()) {
					window.__LOGIXFAST_AUTH_EARLY_OPEN__ = 'login';
				}
			}, true);
		})();",
			'before'
		);
	}
}
