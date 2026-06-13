<?php
/**
 * Replaces WooCommerce checkout login when SLR is enabled.
 *
 * @package SLR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SLR\Integrations\WooCommerce_Login;
use SLR\Settings;

if ( is_user_logged_in() ) {
	return;
}

$general  = Settings::get( 'general' );
$page_id  = (int) ( $general['dedicated_page_id'] ?? 0 );
$page_url = $page_id ? ( get_permalink( $page_id ) ?: '' ) : '';

if ( $page_url && function_exists( 'wc_get_checkout_url' ) ) {
	$login_url = add_query_arg( 'redirect_to', rawurlencode( wc_get_checkout_url() ), $page_url );
	?>
	<div class="woocommerce-form-login-toggle slr-wc-checkout-login-replace">
		<?php
		wc_print_notice(
			sprintf(
				/* translators: %s: login URL */
				__( 'Already have an account? <a href="%s">Sign in to continue checkout</a>', 'smart-login-registration' ),
				esc_url( $login_url )
			),
			'notice'
		);
		?>
	</div>
	<?php
	return;
}

?>
<div class="slr-wc-checkout-login-replace">
	<?php WooCommerce_Login::render_login_embed(); ?>
</div>
