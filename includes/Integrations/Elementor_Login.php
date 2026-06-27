<?php
/**
 * Elementor login widget replacement.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Integrations; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Elementor_Login
 */
class Elementor_Login {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'elementor/widget/render_content', array( $this, 'replace_login_widget' ), 10, 2 );
		add_action( 'elementor/dynamic_tags/register', array( $this, 'register_dynamic_tags' ) );
	}

	/**
	 * Check if enabled.
	 *
	 * @return bool
	 */
	private function is_enabled() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return false;
		}
		$integrations = Settings::get( 'integrations' );
		return Settings::to_bool( $integrations['replace_elementor'] ?? false );
	}

	/**
	 * Whether the Elementor Pro login widget should be replaced.
	 *
	 * @return bool
	 */
	private function is_widget_replacement_enabled() {
		return $this->is_enabled() && Integration_Availability::is_elementor_pro_available();
	}

	/**
	 * Replace Elementor login widget content.
	 *
	 * @param string              $content Widget content.
	 * @param \Elementor\Widget_Base $widget Widget instance.
	 * @return string
	 */
	public function replace_login_widget( $content, $widget ) {
		if ( ! $this->is_widget_replacement_enabled() || is_user_logged_in() ) {
			return $content;
		}

		if ( 'login' !== $widget->get_name() ) {
			return $content;
		}

		ob_start();
		?>
		<div class="logixfast-auth-elementor-login-replace">
			<button type="button" class="elementor-button elementor-size-sm" data-logixfast-auth-open="login">
				<?php esc_html_e( 'Log In', 'logixfast-auth' ); ?>
			</button>
			<button type="button" class="elementor-button elementor-button-link elementor-size-sm" data-logixfast-auth-open="register">
				<?php esc_html_e( 'Register', 'logixfast-auth' ); ?>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Register Elementor dynamic tags.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $manager Tags manager.
	 * @return void
	 */
	public function register_dynamic_tags( $manager ) {
		if ( ! $this->is_enabled() || ! class_exists( '\Elementor\Core\DynamicTags\Data_Tag' ) ) {
			return;
		}

		if ( method_exists( $manager, 'register_group' ) ) {
			$manager->register_group(
				'logixfastauth',
				array(
					'title' => __( 'LogixFastAuth', 'logixfast-auth' ),
				)
			);
		} elseif ( method_exists( $manager, 'register_tag_group' ) ) {
			$manager->register_tag_group(
				'logixfastauth',
				array(
					'title' => __( 'LogixFastAuth', 'logixfast-auth' ),
				)
			);
		}

		require_once LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/Elementor/DynamicTags/LogixFastAuthOpenPopupTag.php';
		$manager->register( new \LogixFastAuth\Elementor\DynamicTags\LogixFastAuthOpenPopupTag() );
	}
}
