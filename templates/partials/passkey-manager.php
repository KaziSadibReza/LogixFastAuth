<?php
/**
 * Shared passkey list / add / delete UI (frontend surfaces).
 *
 * @package LogixFastAuth
 *
 * @var string $logixfast_auth_pk_button_class CSS classes for the add button.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Template-scoped variables.

$logixfast_auth_pk_button_class = isset( $logixfast_auth_pk_button_class ) ? (string) $logixfast_auth_pk_button_class : 'button button-primary';
$logixfast_auth_pk_icon_svg     = \LogixFastAuth\Services\Passkey_Assets::get_icon_svg();
?>

<div class="logixfast-auth-passkey-manager" id="logixfast-auth-passkey-manager">
	<div class="logixfast-auth-pk-header">
		<div>
			<h3 class="logixfast-auth-pk-title"><?php esc_html_e( 'Your Passkeys', 'logixfast-auth' ); ?></h3>
			<p class="logixfast-auth-pk-subtitle"><?php esc_html_e( 'Manage the passkeys linked to your account.', 'logixfast-auth' ); ?></p>
		</div>
		<button type="button" class="logixfast-auth-pk-add <?php echo esc_attr( $logixfast_auth_pk_button_class ); ?>">
			<?php esc_html_e( 'Add passkey', 'logixfast-auth' ); ?>
		</button>
	</div>

	<div class="logixfast-auth-pk-list">
		<div class="logixfast-auth-pk-loading"><?php esc_html_e( 'Loading…', 'logixfast-auth' ); ?></div>
	</div>

	<div class="logixfast-auth-pk-empty" hidden>
		<div class="logixfast-auth-pk-empty-icon" aria-hidden="true">
			<?php
			if ( '' !== $logixfast_auth_pk_icon_svg ) {
				echo \LogixFastAuth\Services\Passkey_Assets::get_sanitized_icon_svg();
			}
			?>
		</div>
		<p class="logixfast-auth-pk-empty-title"><?php esc_html_e( 'No passkeys yet', 'logixfast-auth' ); ?></p>
		<p class="logixfast-auth-pk-empty-desc"><?php esc_html_e( 'Add a passkey to sign in faster next time.', 'logixfast-auth' ); ?></p>
	</div>

	<div class="logixfast-auth-pk-toast" hidden></div>
</div>
<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals ?>
