<?php
/**
 * SLR phone fields on the WordPress user profile screen.
 *
 * @package SLR
 */

namespace SLR\Profile; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Services\Phone_Profile as Phone_Profile_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Phone_Profile
 */
class Phone_Profile {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'show_user_profile', array( $this, 'render_field' ) );
		add_action( 'edit_user_profile', array( $this, 'render_field' ) );
		add_action( 'personal_options_update', array( $this, 'save_field' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_field' ) );
	}

	/**
	 * Output SLR-related phone fields on the profile screen.
	 *
	 * @param \WP_User $user Profile user.
	 * @return void
	 */
	public function render_field( $user ) {
		if ( ! $user instanceof \WP_User || ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		$fields = Phone_Profile_Service::get_profile_fields();
		if ( empty( $fields ) ) {
			return;
		}
		?>
		<h2><?php esc_html_e( 'Smart Login Registration', 'smart-login-registration' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php foreach ( $fields as $meta_key => $field ) : ?>
				<tr>
					<th>
						<label for="slr_profile_<?php echo esc_attr( $meta_key ); ?>">
							<?php echo esc_html( $field['label'] ); ?>
						</label>
					</th>
					<td>
						<input
							type="tel"
							name="slr_profile_<?php echo esc_attr( $meta_key ); ?>"
							id="slr_profile_<?php echo esc_attr( $meta_key ); ?>"
							value="<?php echo esc_attr( (string) get_user_meta( $user->ID, $meta_key, true ) ); ?>"
							class="regular-text"
							autocomplete="tel"
						/>
						<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Save SLR profile phone fields.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function save_field( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 || ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if (
			! isset( $_POST['_wpnonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-user_' . $user_id )
		) {
			return;
		}

		foreach ( Phone_Profile_Service::get_profile_fields() as $meta_key => $field ) {
			$input_name = 'slr_profile_' . $meta_key;

			if ( ! isset( $_POST[ $input_name ] ) ) {
				continue;
			}

			$phone = sanitize_text_field( wp_unslash( $_POST[ $input_name ] ) );
			if ( '' === $phone ) {
				delete_user_meta( $user_id, $meta_key );
				continue;
			}

			update_user_meta( $user_id, $meta_key, $phone );
		}
	}
}
