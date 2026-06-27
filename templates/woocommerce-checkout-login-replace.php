<?php
/**
 * Replaces WooCommerce checkout login when LogixFastAuth is enabled.
 *
 * @package LogixFastAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Template-scoped variables, not true globals.

use LogixFastAuth\Integrations\WooCommerce_Login;
use LogixFastAuth\Settings;

if ( is_user_logged_in() ) {
	return;
}

$logixfast_auth_general  = Settings::get( 'general' );
$logixfast_auth_page_id  = (int) ( $logixfast_auth_general['dedicated_page_id'] ?? 0 );
$logixfast_auth_page_url = $logixfast_auth_page_id ? ( get_permalink( $logixfast_auth_page_id ) ?: '' ) : '';

if ( $logixfast_auth_page_url && function_exists( 'wc_get_checkout_url' ) ) {
	$logixfast_auth_login_url = add_query_arg( 'redirect_to', rawurlencode( wc_get_checkout_url() ), $logixfast_auth_page_url );
	?>
	<div class="woocommerce-form-login-toggle logixfast-auth-wc-checkout-login-replace">
		<?php
		wc_print_notice(
			sprintf(
				/* translators: %s: login URL */
				__( 'Already have an account? <a href="%s">Sign in to continue checkout</a>', 'logixfast-auth' ),
				esc_url( $logixfast_auth_login_url )
			),
			'notice'
		);
		?>
	</div>
	<?php
	return;
}

?>
<div class="logixfast-auth-wc-checkout-login-replace">
	<?php WooCommerce_Login::render_login_embed(); ?>
</div>
<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals ?>
