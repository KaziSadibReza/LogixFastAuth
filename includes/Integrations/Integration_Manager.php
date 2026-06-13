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
		new WooCommerce_Login();
		new Tutor_Login();
		new Tutor_Passkeys();
		new Elementor_Login();

		add_action( 'admin_notices', array( $this, 'conflict_notices' ) );
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
