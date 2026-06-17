<?php
/**
 * Shared passkey list / add / delete UI (frontend surfaces).
 *
 * @package SLR
 *
 * @var string $slr_pk_button_class CSS classes for the add button.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Template-scoped variables.

$slr_pk_button_class = isset( $slr_pk_button_class ) ? (string) $slr_pk_button_class : 'button button-primary';
$slr_pk_icon_svg     = \SLR\Services\Passkey_Assets::get_icon_svg();
?>

<div class="slr-passkey-manager" id="slr-passkey-manager">
	<div class="slr-pk-header">
		<div>
			<h3 class="slr-pk-title"><?php esc_html_e( 'Your Passkeys', 'smart-login-registration' ); ?></h3>
			<p class="slr-pk-subtitle"><?php esc_html_e( 'Manage the passkeys linked to your account.', 'smart-login-registration' ); ?></p>
		</div>
		<button type="button" class="slr-pk-add <?php echo esc_attr( $slr_pk_button_class ); ?>">
			<?php esc_html_e( 'Add passkey', 'smart-login-registration' ); ?>
		</button>
	</div>

	<div class="slr-pk-list">
		<div class="slr-pk-loading"><?php esc_html_e( 'Loading…', 'smart-login-registration' ); ?></div>
	</div>

	<div class="slr-pk-empty" hidden>
		<div class="slr-pk-empty-icon" aria-hidden="true">
			<?php
			if ( '' !== $slr_pk_icon_svg ) {
				echo $slr_pk_icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted local SVG asset.
			}
			?>
		</div>
		<p class="slr-pk-empty-title"><?php esc_html_e( 'No passkeys yet', 'smart-login-registration' ); ?></p>
		<p class="slr-pk-empty-desc"><?php esc_html_e( 'Add a passkey to sign in faster next time.', 'smart-login-registration' ); ?></p>
	</div>

	<div class="slr-pk-toast" hidden></div>
</div>
<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals ?>
