<?php
/**
 * Replaces WooCommerce checkout login when SLR is enabled.
 *
 * @package SLR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Template-scoped variables, not true globals.

use SLR\Integrations\WooCommerce_Login;
use SLR\Settings;

if ( is_user_logged_in() ) {
	return;
}

$slr_general  = Settings::get( 'general' );
$slr_page_id  = (int) ( $slr_general['dedicated_page_id'] ?? 0 );
$slr_page_url = $slr_page_id ? ( get_permalink( $slr_page_id ) ?: '' ) : '';

if ( $slr_page_url && function_exists( 'wc_get_checkout_url' ) ) {
	$slr_login_url = add_query_arg( 'redirect_to', rawurlencode( wc_get_checkout_url() ), $slr_page_url );
	?>
	<div class="woocommerce-form-login-toggle slr-wc-checkout-login-replace">
		<?php
		wc_print_notice(
			sprintf(
				/* translators: %s: login URL */
				__( 'Already have an account? <a href="%s">Sign in to continue checkout</a>', 'smart-login-registration' ),
				esc_url( $slr_login_url )
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
<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals ?>
