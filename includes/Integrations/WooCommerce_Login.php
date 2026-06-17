<?php
/**
 * WooCommerce login replacement.
 *
 * @package SLR
 */

namespace SLR\Integrations; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WooCommerce_Login
 */
class WooCommerce_Login {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_redirect_my_account' ), 1 );
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_login_templates' ), 99, 3 );
		add_filter( 'woocommerce_enable_myaccount_registration', array( $this, 'disable_myaccount_registration' ) );
	}

	/**
	 * Check if enabled.
	 *
	 * @return bool
	 */
	private function is_enabled() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}
		$integrations = Settings::get( 'integrations' );
		return Settings::to_bool( $integrations['replace_woocommerce'] ?? false );
	}

	/**
	 * Whether WooCommerce login should be replaced on this request.
	 *
	 * @return bool
	 */
	private function should_replace_login() {
		if ( ! $this->is_enabled() || is_user_logged_in() ) {
			return false;
		}

		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'lost-password' ) ) {
				return false;
			}
			return true;
		}

		return function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url( 'order-received' );
	}

	/**
	 * Redirect logged-out My Account visitors to the SLR dedicated login page.
	 *
	 * @return void
	 */
	public function maybe_redirect_my_account() {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() || ! $this->is_enabled() ) {
			return;
		}

		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'lost-password' ) ) {
			return;
		}

		$page_url = $this->get_dedicated_page_url();
		if ( ! $page_url ) {
			return;
		}

		$redirect = wp_validate_redirect( $this->get_current_url(), '' );
		if ( $redirect ) {
			$page_url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $page_url );
		}

		wp_safe_redirect( $page_url );
		exit;
	}

	/**
	 * Swap WooCommerce login templates for SLR when no redirect applies.
	 *
	 * @param string $template      Template path.
	 * @param string $template_name Template name.
	 * @param string $template_path Template path.
	 * @return string
	 */
	public function locate_login_templates( $template, $template_name, $template_path ) {
		if ( ! $this->should_replace_login() ) {
			return $template;
		}

		if ( 'myaccount/form-login.php' === $template_name ) {
			if ( $this->get_dedicated_page_url() ) {
				return $template;
			}

			$slr_template = SLR_PLUGIN_DIR . 'templates/woocommerce-myaccount-login-replace.php';
			return file_exists( $slr_template ) ? $slr_template : $template;
		}

		if ( 'checkout/form-login.php' === $template_name ) {
			$slr_template = SLR_PLUGIN_DIR . 'templates/woocommerce-checkout-login-replace.php';
			return file_exists( $slr_template ) ? $slr_template : $template;
		}

		return $template;
	}

	/**
	 * Disable native WooCommerce registration on My Account when SLR is active.
	 *
	 * @param bool $enabled Whether registration is enabled.
	 * @return bool
	 */
	public function disable_myaccount_registration( $enabled ) {
		if ( ! $this->is_enabled() || is_user_logged_in() ) {
			return $enabled;
		}

		return false;
	}

	/**
	 * Output SLR login embed for WooCommerce surfaces.
	 *
	 * @return void
	 */
	public static function render_login_embed() {
		\SLR\Frontend\Frontend_Assets::enqueue_all( 'page' );
		?>
		<div class="slr-wc-login-replace-wrap">
			<div id="slr-root" data-mode="page"></div>
		</div>
		<?php
	}

	/**
	 * Get dedicated page URL.
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
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( '' === $host || '' === $uri ) {
			return function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'myaccount' ) : '';
		}

		$scheme = is_ssl() ? 'https://' : 'http://';
		return $scheme . $host . $uri;
	}
}
