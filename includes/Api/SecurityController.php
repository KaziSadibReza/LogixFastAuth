<?php
/**
 * Admin security REST endpoints.
 *
 * @package SLR
 */

namespace SLR\Api; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Services\RateLimiter;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SecurityController
 */
class SecurityController {

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'slr/v1',
			'/security/blocks',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_blocks' ),
					'permission_callback' => array( $this, 'admin_permission' ),
				),
			)
		);

		register_rest_route(
			'slr/v1',
			'/security/blocks/(?P<id>[a-f0-9]{32})/unblock',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'unblock' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);
	}

	/**
	 * Admin permission check.
	 *
	 * @return bool
	 */
	public function admin_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * List active blocks.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_blocks() {
		return rest_ensure_response( array(
			'blocks' => RateLimiter::get_blocks(),
		) );
	}

	/**
	 * Unblock an IP/action.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function unblock( $request ) {
		$id = sanitize_text_field( $request['id'] ?? '' );

		if ( ! RateLimiter::unblock( $id ) ) {
			return new \WP_Error( 'slr_block_not_found', __( 'Block not found.', 'smart-login-registration' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( array( 'unblocked' => true ) );
	}
}
