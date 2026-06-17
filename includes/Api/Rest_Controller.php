<?php
/**
 * REST API route registration.
 *
 * @package SLR
 */

namespace SLR\Api; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rest_Controller
 */
class Rest_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		$controllers = array(
			new ConfigController(),
			new AuthController(),
			new OtpController(),
			new WebAuthnController(),
			new SettingsController(),
			new SecurityController(),
		);

		foreach ( $controllers as $controller ) {
			$controller->register_routes();
		}
	}
}
