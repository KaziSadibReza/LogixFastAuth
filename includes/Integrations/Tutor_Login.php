<?php
/**
 * Tutor LMS login replacement.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Integrations; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Settings;

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
		add_filter( 'logixfast_auth_should_load_tutor_login_modal', array( $this, 'should_load_tutor_login_modal' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'hide_native_login_modal' ), 20 );
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
	 * Force Tutor login triggers to use LogixFastAuth popup (never a redirect URL).
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
	 * Disable Tutor's native login modal when LogixFastAuth replaces Tutor login.
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
	 * Skip loading Tutor's native login modal markup when LogixFastAuth handles login.
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

		wp_register_style( 'logixfast-auth-tutor-login', false, array(), LOGIXFAST_AUTH_VERSION );
		wp_enqueue_style( 'logixfast-auth-tutor-login' );
		wp_add_inline_style( 'logixfast-auth-tutor-login', '.tutor-login-modal{display:none!important;visibility:hidden!important}' );
	}

	/**
	 * Redirect logged-out Tutor dashboard visitors to the LogixFastAuth login page.
	 *
	 * Falls back to auto-opening the LogixFastAuth popup when no dedicated page is set.
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

		$logixfast_auth_template = LOGIXFAST_AUTH_PLUGIN_DIR . 'templates/tutor-login-replace.php';
		return file_exists( $logixfast_auth_template ) ? $logixfast_auth_template : $template;
	}

	/**
	 * Stop Tutor native login markup and open LogixFastAuth popup instead.
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
	 * Output a minimal Tutor shell that opens the LogixFastAuth popup.
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
			<div class="tutor-template-segment tutor-login-wrap logixfast-auth-tutor-login-replace-wrap logixfast-auth-tutor-login-popup-only">
				<p class="logixfast-auth-tutor-login-popup-only__hint">
					<?php esc_html_e( 'Sign in to access your dashboard.', 'logixfast-auth' ); ?>
				</p>
				<button type="button" class="tutor-btn tutor-btn-primary" data-logixfast-auth-open="login">
					<?php esc_html_e( 'Log In', 'logixfast-auth' ); ?>
				</button>
				<button type="button" class="tutor-btn tutor-btn-ghost" data-logixfast-auth-open="register">
					<?php esc_html_e( 'Register', 'logixfast-auth' ); ?>
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
		<div class="logixfast-auth-tutor-login-replace">
			<button type="button" class="tutor-btn tutor-btn-primary" data-logixfast-auth-open="login">
				<?php esc_html_e( 'Log In', 'logixfast-auth' ); ?>
			</button>
			<button type="button" class="tutor-btn tutor-btn-ghost" data-logixfast-auth-open="register">
				<?php esc_html_e( 'Register', 'logixfast-auth' ); ?>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Queue LogixFastAuth popup auto-open on the current page.
	 *
	 * @return void
	 */
	private function queue_login_popup() {
		$this->enqueue_auto_open_popup_script();
	}

	/**
	 * Enqueue inline script that opens the LogixFastAuth login popup.
	 *
	 * @return void
	 */
	public function enqueue_auto_open_popup_script() {
		if ( is_user_logged_in() || ! $this->is_enabled() ) {
			return;
		}

		wp_register_script( 'logixfast-auth-tutor-auto-open-login', false, array(), LOGIXFAST_AUTH_VERSION, true );
		wp_enqueue_script( 'logixfast-auth-tutor-auto-open-login' );
		wp_add_inline_script(
			'logixfast-auth-tutor-auto-open-login',
			"(function () {
			function openLogixFastAuthLogin() {
				if (window.LogixFastAuth && typeof window.LogixFastAuth.open === 'function') {
					window.LogixFastAuth.open('login');
					return;
				}
				window.addEventListener('logixfastauth:ready', function () {
					if (window.LogixFastAuth && typeof window.LogixFastAuth.open === 'function') {
						window.LogixFastAuth.open('login');
					}
				}, { once: true });
			}
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', openLogixFastAuthLogin);
			} else {
				openLogixFastAuthLogin();
			}
		})();"
		);
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
	 * Get dedicated LogixFastAuth login page URL.
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

		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( '' === $host || '' === $uri ) {
			return '';
		}

		$scheme = is_ssl() ? 'https' : 'http';
		return $scheme . '://' . $host . $uri;
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
			wc_add_notice( __( 'Please log in to enroll in this course.', 'logixfast-auth' ), 'error' );
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Tutor product-to-course mapping uses postmeta.
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
