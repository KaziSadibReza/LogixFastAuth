<?php
/**
 * Tutor LMS Dashboard – Settings – Passkeys tab (legacy sub-page).
 *
 * @package LogixFastAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LogixFastAuth\Services\Passkey_Surfaces;

?>

<div class="tutor-fs-4 tutor-fw-medium tutor-mb-24"><?php esc_html_e( 'Settings', 'logixfast-auth' ); ?></div>

<div class="tutor-dashboard-content-inner">
	<?php
	$logixfast_auth_nav_template = function_exists( 'tutor_get_template_path' )
		? tutor_get_template_path( 'dashboard.settings.nav-bar' )
		: '';

	if ( is_string( $logixfast_auth_nav_template ) && '' !== $logixfast_auth_nav_template && file_exists( $logixfast_auth_nav_template ) ) {
		?>
		<div class="tutor-mb-32">
			<?php tutor_load_template( 'dashboard.settings.nav-bar', array( 'active_setting_nav' => 'passkeys' ) ); ?>
		</div>
		<?php
	}
	?>

	<?php
	Passkey_Surfaces::render_manager(
		array(
			'button_class' => 'tutor-btn tutor-btn-primary tutor-btn-sm',
		)
	);
	?>
</div>
