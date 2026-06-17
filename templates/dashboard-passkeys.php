<?php
/**
 * Tutor LMS Dashboard – Settings – Passkeys tab.
 *
 * @package SLR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="tutor-fs-4 tutor-fw-medium tutor-mb-24"><?php esc_html_e( 'Settings', 'smart-login-registration' ); ?></div>

<div class="tutor-dashboard-content-inner">
	<div class="tutor-mb-32">
		<?php tutor_load_template( 'dashboard.settings.nav-bar', array( 'active_setting_nav' => 'passkeys' ) ); ?>
	</div>

	<?php
	// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Template-scoped variable passed to partial.
	$slr_pk_button_class = 'tutor-btn tutor-btn-primary tutor-btn-sm';
	// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
	require SLR_PLUGIN_DIR . 'templates/partials/passkey-manager.php';
	?>
</div>
