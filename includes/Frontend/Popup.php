<?php
/**
 * Popup portal and bootstrap.
 *
 * @package SLR
 */

namespace SLR\Frontend;

use SLR\Assets;
use SLR\Integrations\Integration_Availability;
use SLR\Settings;

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
		if ( is_admin() || \SLR\Settings::is_dedicated_page() ) {
			return;
		}

		if ( Assets::is_dev_mode() ) {
			Frontend_Assets::enqueue_all( 'popup' );
			return;
		}

		Frontend_Assets::enqueue_font();
		wp_enqueue_script( 'slr-bootstrap' );
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

		$this->print_early_click_guard();
		echo '<div id="slr-root" data-mode="popup" aria-hidden="true"></div>';
	}

	/**
	 * Intercept Tutor login triggers before deferred SLR scripts load.
	 *
	 * @return void
	 */
	private function print_early_click_guard() {
		$integrations = Settings::get( 'integrations' );
		if ( ! Integration_Availability::is_tutor_available() || ! Settings::to_bool( $integrations['replace_tutor'] ?? false ) ) {
			return;
		}
		?>
		<script id="slr-early-click-guard">
		(function () {
			var selectors = '.tutor-open-login-modal, .tutor-course-entry-box-login button, .tutor-course-entry-box-login a';
			function tryOpen() {
				if (window.SLR && typeof window.SLR.open === 'function') {
					window.SLR.open('login');
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
					window.__SLR_EARLY_OPEN__ = 'login';
				}
			}, true);
		})();
		</script>
		<?php
	}
}
