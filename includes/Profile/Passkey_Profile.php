<?php
/**
 * Passkey management on the WordPress user profile screen.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Profile; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Services\Passkey_Surfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Passkey_Profile
 */
class Passkey_Profile {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'show_user_profile', array( $this, 'render_section' ) );
		add_action( 'edit_user_profile', array( $this, 'render_section' ) );
	}

	/**
	 * Output passkey manager on the logged-in user's own profile only.
	 *
	 * @param \WP_User $user Profile user.
	 * @return void
	 */
	public function render_section( $user ) {
		if ( ! $user instanceof \WP_User || (int) $user->ID !== get_current_user_id() ) {
			return;
		}

		if ( ! Passkey_Surfaces::is_enabled() ) {
			return;
		}
		?>
		<h2 id="logixfast-auth-passkey-manager"><?php esc_html_e( 'Passkeys', 'logixfast-auth' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Add or remove passkeys for your account. Each passkey is tied to this user only.', 'logixfast-auth' ); ?>
		</p>
		<?php
		Passkey_Surfaces::render_manager(
			array(
				'button_class' => 'button button-primary',
			)
		);
	}
}
