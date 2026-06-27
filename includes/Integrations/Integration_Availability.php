<?php
/**
 * Detect whether third-party plugins required for LogixFastAuth integrations are active.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Integrations; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Integration_Availability
 */
class Integration_Availability {

	/**
	 * Map integration setting keys to availability slugs.
	 *
	 * @var array<string, string>
	 */
	private static $setting_map = array(
		'replace_wp_login'    => 'wordpress',
		'replace_woocommerce' => 'woocommerce',
		'replace_tutor'       => 'tutor',
		'replace_elementor'   => 'elementor',
	);

	/**
	 * Whether WordPress login replacement is available.
	 *
	 * @return bool
	 */
	public static function is_wordpress_available() {
		return true;
	}

	/**
	 * Whether WooCommerce is installed and active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_available() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Whether Tutor LMS is installed and active.
	 *
	 * @return bool
	 */
	public static function is_tutor_available() {
		return function_exists( 'tutor_utils' );
	}

	/**
	 * Whether Elementor is installed and active.
	 *
	 * @return bool
	 */
	public static function is_elementor_available() {
		if ( did_action( 'elementor/loaded' ) ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'elementor/elementor.php' );
	}

	/**
	 * Whether Elementor Pro is installed and active.
	 *
	 * @return bool
	 */
	public static function is_elementor_pro_available() {
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return true;
		}

		return class_exists( '\ElementorPro\Plugin' );
	}

	/**
	 * Availability keyed by plugin slug for the admin UI.
	 *
	 * @return array<string, bool>
	 */
	public static function get_plugin_map() {
		return array(
			'wordpress'   => self::is_wordpress_available(),
			'woocommerce' => self::is_woocommerce_available(),
			'tutor'       => self::is_tutor_available(),
			'elementor'   => self::is_elementor_available(),
		);
	}

	/**
	 * Whether an integration setting key can be enabled.
	 *
	 * @param string $setting_key Integration setting key.
	 * @return bool
	 */
	public static function is_setting_available( $setting_key ) {
		$plugin = self::$setting_map[ $setting_key ] ?? '';
		if ( '' === $plugin ) {
			return false;
		}

		$map = self::get_plugin_map();
		return ! empty( $map[ $plugin ] );
	}

	/**
	 * Force unavailable integrations off before save or API output.
	 *
	 * @param array $integrations Integration settings.
	 * @return array
	 */
	public static function sanitize_settings( $integrations ) {
		if ( ! is_array( $integrations ) ) {
			return array();
		}

		foreach ( self::$setting_map as $setting_key => $plugin ) {
			if ( ! array_key_exists( $setting_key, $integrations ) ) {
				continue;
			}

			$integrations[ $setting_key ] = (bool) $integrations[ $setting_key ];

			if ( ! self::is_setting_available( $setting_key ) ) {
				$integrations[ $setting_key ] = false;
			}
		}

		return $integrations;
	}
}
