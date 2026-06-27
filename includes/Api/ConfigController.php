<?php
/**
 * Public config REST endpoint.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Api; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Settings;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConfigController
 */
class ConfigController {

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'logixfast-auth/v1',
			'/config',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_config' ),
				'permission_callback' => '__return_true', // Public frontend config only; Settings::get_public_config() excludes credentials and secrets.
			)
		);
	}

	/**
	 * Get public config.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_config() {
		return rest_ensure_response( Settings::get_public_config() );
	}
}
