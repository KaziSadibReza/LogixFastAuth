<?php
/**
 * Manages third-party login replacement integrations.
 *
 * @package SLR
 */

namespace SLR\Integrations;

use SLR\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Integration_Manager
 */
class Integration_Manager {

	/**
	 * Constructor.
	 */
	public function __construct() {
		new WordPress_Login();

		add_action( 'plugins_loaded', array( $this, 'load_third_party_integrations' ), 20 );
		add_action( 'elementor/loaded', array( $this, 'load_elementor_integration' ) );
		add_action( 'admin_notices', array( $this, 'conflict_notices' ) );
	}

	/**
	 * Load WooCommerce and Tutor integrations after other plugins bootstrap.
	 *
	 * @return void
	 */
	public function load_third_party_integrations() {
		if ( Integration_Availability::is_woocommerce_available() ) {
			new WooCommerce_Login();
		}

		if ( Integration_Availability::is_tutor_available() ) {
			new Tutor_Login();
			new Tutor_Passkeys();
		}
	}

	/**
	 * Load Elementor integration once Elementor has booted.
	 *
	 * @return void
	 */
	public function load_elementor_integration() {
		if ( Integration_Availability::is_elementor_available() ) {
			new Elementor_Login();
		}
	}

	/**
	 * Show admin conflict notices.
	 *
	 * @return void
	 */
	public function conflict_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'slr' ) === false ) {
			return;
		}

		$auth = Settings::get( 'auth' );
		if ( ! empty( $auth['email_otp_enabled'] ) && class_exists( 'TUTOR_PRO_VERSION' ) ) {
			echo '<div class="notice notice-warning"><p>';
			echo esc_html__( 'SLR Email OTP is enabled. Disable Tutor Pro Auth addon 2FA to avoid double verification.', 'smart-login-registration' );
			echo '</p></div>';
		}
	}
}
