<?php
/**
 * Tutor LMS 4.0+ inline Settings tab – Passkeys.
 *
 * @package LogixFastAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LogixFastAuth\Services\Passkey_Surfaces;

?>

<section class="tutor-flex tutor-flex-column tutor-gap-8">
	<div class="tutor-flex tutor-flex-column tutor-gap-4">
		<h5 class="tutor-h5 tutor-my-none"><?php esc_html_e( 'Passkeys', 'logixfast-auth' ); ?></h5>

		<div class="tutor-card tutor-card-rounded-2xl tutor-flex tutor-flex-column tutor-gap-5">
			<?php
			Passkey_Surfaces::render_manager(
				array(
					'button_class' => 'tutor-btn tutor-btn-primary tutor-btn-sm',
				)
			);
			?>
		</div>
	</div>
</section>
