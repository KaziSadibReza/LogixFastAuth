<?php
/**
 * Tutor LMS login replacement.
 *
 * @package SLR
 */

namespace SLR\Integrations;

use SLR\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tutor_Login
 */
class Tutor_Login {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'tutor_login_url', array( $this, 'filter_login_url' ), 99 );
		add_filter( 'enable_tutor_native_login', array( $this, 'disable_tutor_native_login' ), 99 );
		add_filter( 'slr_should_load_tutor_login_modal', array( $this, 'should_load_tutor_login_modal' ) );
		add_action( 'wp_head', array( $this, 'hide_native_login_modal' ), 99 );
		add_action( 'template_redirect', array( $this, 'maybe_redirect_dashboard' ), 1 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_tutor_course_cart_login' ), 10, 3 );
		add_filter( 'template_include', array( $this, 'replace_login_template' ), 999 );
		add_action( 'tutor/template/login/before/wrap', array( $this, 'intercept_native_login' ), 1 );
		add_filter( 'tutor_login_form', array( $this, 'replace_login_form' ), 99 );
		add_action( 'init', array( $this, 'override_shortcode' ), 20 );
	}

	/**
	 * Check if enabled.
	 *
	 * @return bool
	 */
	private function is_enabled() {
		if ( ! function_exists( 'tutor_utils' ) ) {
			return false;
		}
		$integrations = Settings::get( 'integrations' );
		return Settings::to_bool( $integrations['replace_tutor'] ?? false );
	}

	/**
	 * Force Tutor login triggers to use SLR popup (never a redirect URL).
	 *
	 * @param string $url Login URL.
	 * @return string
	 */
	public function filter_login_url( $url ) {
		if ( ! $this->is_enabled() ) {
			return $url;
		}

		return '';
	}

	/**
	 * Disable Tutor's native login modal when SLR replaces Tutor login.
	 *
	 * @param mixed $value Tutor option value.
	 * @return bool
	 */
	public function disable_tutor_native_login( $value ) {
		if ( ! $this->is_enabled() ) {
			return (bool) $value;
		}

		return false;
	}

	/**
	 * Skip loading Tutor's native login modal markup when SLR handles login.
	 *
	 * @param bool $load Whether to load the modal.
	 * @return bool
	 */
	public function should_load_tutor_login_modal( $load ) {
		if ( ! $this->is_enabled() ) {
			return $load;
		}

		return false;
	}

	/**
	 * Hide any leftover Tutor login modal markup.
	 *
	 * @return void
	 */
	public function hide_native_login_modal() {
		if ( ! $this->is_enabled() || is_user_logged_in() ) {
			return;
		}

		echo '<style id="slr-hide-tutor-login-modal">.tutor-login-modal{display:none!important;visibility:hidden!important}</style>';
	}

	/**
	 * Redirect logged-out Tutor dashboard visitors to the SLR login page.
	 *
	 * Falls back to auto-opening the SLR popup when no dedicated page is set.
	 *
	 * @return void
	 */
	public function maybe_redirect_dashboard() {
		if ( ! $this->is_enabled() || is_user_logged_in() || ! $this->is_tutor_dashboard_page() ) {
			return;
		}

		if ( Settings::is_dedicated_page() ) {
			return;
		}

		$page_url = $this->get_dedicated_page_url();
		if ( $page_url ) {
			$redirect = wp_validate_redirect( $this->get_current_url(), '' );
			if ( $redirect ) {
				$page_url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $page_url );
			}

			wp_safe_redirect( $page_url );
			exit;
		}

		$this->queue_login_popup();
	}

	/**
	 * Swap Tutor login template for a popup trigger shell.
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public function replace_login_template( $template ) {
		if ( ! $this->should_replace_dashboard_login() ) {
			return $template;
		}

		if ( $this->get_dedicated_page_url() && $this->is_tutor_dashboard_page() ) {
			return $template;
		}

		if ( ! $this->is_tutor_login_template( $template ) && ! $this->is_tutor_dashboard_page() ) {
			return $template;
		}

		$slr_template = SLR_PLUGIN_DIR . 'templates/tutor-login-replace.php';
		return file_exists( $slr_template ) ? $slr_template : $template;
	}

	/**
	 * Stop Tutor native login markup and open SLR popup instead.
	 *
	 * @return void
	 */
	public function intercept_native_login() {
		if ( ! $this->should_replace_dashboard_login() ) {
			return;
		}

		$this->queue_login_popup();
		self::render_login_popup_shell( true );
		exit;
	}

	/**
	 * Output a minimal Tutor shell that opens the SLR popup.
	 *
	 * @param bool $skip_header Header already rendered by Tutor login template.
	 * @return void
	 */
	public static function render_login_popup_shell( $skip_header = false ) {
		if ( ! $skip_header && function_exists( 'tutor_utils' ) ) {
			tutor_utils()->tutor_custom_header();
		}
		?>
		<div <?php tutor_post_class( 'tutor-page-wrap' ); ?>>
			<div class="tutor-template-segment tutor-login-wrap slr-tutor-login-replace-wrap slr-tutor-login-popup-only">
				<p class="slr-tutor-login-popup-only__hint">
					<?php esc_html_e( 'Sign in to access your dashboard.', 'smart-login-registration' ); ?>
				</p>
				<button type="button" class="tutor-btn tutor-btn-primary" data-slr-open="login">
					<?php esc_html_e( 'Log In', 'smart-login-registration' ); ?>
				</button>
				<button type="button" class="tutor-btn tutor-btn-ghost" data-slr-open="register">
					<?php esc_html_e( 'Register', 'smart-login-registration' ); ?>
				</button>
			</div>
		</div>
		<?php
		if ( function_exists( 'tutor_utils' ) ) {
			tutor_utils()->tutor_custom_footer();
		}
	}

	/**
	 * Replace Tutor login form output (shortcodes, widgets).
	 *
	 * @param string $form Form HTML.
	 * @return string
	 */
	public function replace_login_form( $form ) {
		if ( ! $this->is_enabled() ) {
			return $form;
		}

		ob_start();
		?>
		<div class="slr-tutor-login-replace">
			<button type="button" class="tutor-btn tutor-btn-primary" data-slr-open="login">
				<?php esc_html_e( 'Log In', 'smart-login-registration' ); ?>
			</button>
			<button type="button" class="tutor-btn tutor-btn-ghost" data-slr-open="register">
				<?php esc_html_e( 'Register', 'smart-login-registration' ); ?>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Queue SLR popup auto-open on the current page.
	 *
	 * @return void
	 */
	private function queue_login_popup() {
		add_action( 'wp_footer', array( $this, 'print_auto_open_popup_script' ), 99 );
	}

	/**
	 * Print inline script that opens the SLR login popup.
	 *
	 * @return void
	 */
	public function print_auto_open_popup_script() {
		if ( is_user_logged_in() || ! $this->is_enabled() ) {
			return;
		}
		?>
		<script id="slr-tutor-auto-open-login">
		(function () {
			function openSlrLogin() {
				if (window.SLR && typeof window.SLR.open === 'function') {
					window.SLR.open('login');
					return;
				}
				window.addEventListener('slr:ready', function () {
					if (window.SLR && typeof window.SLR.open === 'function') {
						window.SLR.open('login');
					}
				}, { once: true });
			}
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', openSlrLogin);
			} else {
				openSlrLogin();
			}
		})();
		</script>
		<?php
	}

	/**
	 * Replace tutor_login shortcode when enabled.
	 *
	 * @return void
	 */
	public function override_shortcode() {
		if ( ! $this->is_enabled() ) {
			return;
		}
		remove_shortcode( 'tutor_login' );
		add_shortcode( 'tutor_login', array( $this, 'shortcode_override' ) );
	}

	/**
	 * Override tutor_login shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_override( $atts = array() ) {
		return $this->replace_login_form( '' );
	}

	/**
	 * Whether current request is the Tutor student dashboard page.
	 *
	 * @return bool
	 */
	private function is_tutor_dashboard_page() {
		if ( ! function_exists( 'tutor_utils' ) ) {
			return false;
		}

		$dashboard_id = (int) tutor_utils()->get_option( 'tutor_dashboard_page_id' );
		return $dashboard_id > 0 && is_page( $dashboard_id );
	}

	/**
	 * Whether the template is Tutor's login view.
	 *
	 * @param string $template Template path.
	 * @return bool
	 */
	private function is_tutor_login_template( $template ) {
		$normalized = wp_normalize_path( (string) $template );
		return false !== strpos( $normalized, 'tutor' ) && false !== strpos( $normalized, 'login.php' );
	}

	/**
	 * Whether Tutor login should be replaced on this request.
	 *
	 * @return bool
	 */
	private function should_replace_dashboard_login() {
		return $this->is_enabled() && ! is_user_logged_in();
	}

	/**
	 * Get dedicated SLR login page URL.
	 *
	 * @return string
	 */
	private function get_dedicated_page_url() {
		$general = Settings::get( 'general' );
		$page_id = (int) ( $general['dedicated_page_id'] ?? 0 );
		return $page_id ? ( get_permalink( $page_id ) ?: '' ) : '';
	}

	/**
	 * Current request URL for post-login redirects.
	 *
	 * @return string
	 */
	private function get_current_url() {
		if ( function_exists( 'tutor_utils' ) ) {
			$dashboard_url = (string) tutor_utils()->get_tutor_dashboard_page_permalink();
			if ( $dashboard_url ) {
				return $dashboard_url;
			}
		}

		if ( empty( $_SERVER['HTTP_HOST'] ) || empty( $_SERVER['REQUEST_URI'] ) ) {
			return '';
		}

		$scheme = is_ssl() ? 'https' : 'http';
		return $scheme . '://' . wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] );
	}

	/**
	 * Whether guest users must log in before adding Tutor courses to cart.
	 *
	 * @return bool
	 */
	public static function requires_login_for_course_cart() {
		if ( is_user_logged_in() || ! function_exists( 'tutor_utils' ) ) {
			return false;
		}

		return ! (bool) tutor_utils()->get_option( 'enable_guest_course_cart', false, true, true );
	}

	/**
	 * Prevent WooCommerce from adding Tutor course products for guests when login is required.
	 *
	 * @param bool $passed     Validation result.
	 * @param int  $product_id Product ID.
	 * @param int  $quantity   Quantity.
	 * @return bool
	 */
	public function validate_tutor_course_cart_login( $passed, $product_id, $quantity ) {
		unset( $quantity );

		if ( ! $passed || ! self::requires_login_for_course_cart() ) {
			return $passed;
		}

		if ( ! self::is_tutor_course_product( $product_id ) ) {
			return $passed;
		}

		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( __( 'Please log in to enroll in this course.', 'smart-login-registration' ), 'error' );
		}

		return false;
	}

	/**
	 * Check whether a WooCommerce product belongs to a Tutor course.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	private static function is_tutor_course_product( $product_id ) {
		global $wpdb;

		$product_id = absint( $product_id );
		if ( ! $product_id ) {
			return false;
		}

		$course_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
				'_tutor_course_product_id',
				(string) $product_id
			)
		);

		return $course_id > 0;
	}
}
