<?php
/**
 * Admin settings REST endpoints.
 *
 * @package SLR
 */

namespace SLR\Api;

use SLR\Services\GoogleOAuthService;
use SLR\Services\LoginPageService;
use SLR\Services\MailService;
use SLR\Services\StatsService;
use SLR\Settings;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SettingsController
 */
class SettingsController {

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'slr/v1',
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'admin_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'admin_permission' ),
				),
			)
		);

		register_rest_route(
			'slr/v1',
			'/settings/test-smtp',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'test_smtp' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);

		register_rest_route(
			'slr/v1',
			'/settings/pages',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_pages' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);

		register_rest_route(
			'slr/v1',
			'/settings/login-page',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_login_page' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);

		register_rest_route(
			'slr/v1',
			'/settings/sms-providers',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_sms_providers' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);

		register_rest_route(
			'slr/v1',
			'/settings/stats',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_stats' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);

		register_rest_route(
			'slr/v1',
			'/settings/google/oauth-url',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'google_oauth_url' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);

		register_rest_route(
			'slr/v1',
			'/settings/google/callback',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'google_oauth_callback' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'slr/v1',
			'/settings/google/disconnect',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'google_disconnect' ),
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
	 * Cast integration toggles to real booleans so false is persisted.
	 *
	 * @param array $section Integration settings.
	 * @return array
	 */
	private function sanitize_integrations( $section ) {
		$keys = array( 'replace_wp_login', 'replace_woocommerce', 'replace_tutor', 'replace_elementor' );

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $section ) ) {
				$section[ $key ] = Settings::to_bool( $section[ $key ] );
			}
		}

		return $section;
	}

	/**
	 * Sanitize authentication settings.
	 *
	 * @param array $section Auth settings.
	 * @return array
	 */
	private function sanitize_auth( $section ) {
		// Keep passkeys unavailable until server-side WebAuthn verification exists.
		$section['webauthn_enabled'] = false;

		return $section;
	}

	/**
	 * Get all settings (sanitized for admin).
	 *
	 * @return \WP_REST_Response
	 */
	public function get_settings() {
		$settings = Settings::get_all();

		if ( ! empty( $settings['integrations'] ) && is_array( $settings['integrations'] ) ) {
			$settings['integrations'] = $this->sanitize_integrations( $settings['integrations'] );
		}

		if ( ! empty( $settings['auth'] ) && is_array( $settings['auth'] ) ) {
			$settings['auth'] = $this->sanitize_auth( $settings['auth'] );
		}

		if ( ! empty( $settings['mail']['smtp_pass'] ) ) {
			$settings['mail']['smtp_pass'] = '********';
		}
		if ( ! empty( $settings['mail']['google_refresh_token'] ) ) {
			$settings['mail']['google_refresh_token'] = '********';
		}
		if ( ! empty( $settings['mail']['google_client_secret'] ) ) {
			$settings['mail']['google_client_secret'] = '********';
		}

		$google_oauth                         = new GoogleOAuthService();
		$settings['mail']['google_configured'] = $google_oauth->is_configured();
		$settings['mail']['google_redirect_uri'] = $google_oauth->get_redirect_uri();

		$settings['auth']['has_sms_provider'] = ! empty( apply_filters( 'slr_sms_providers', array() ) );
		$settings['auth']['has_tutor_lms']    = function_exists( 'tutor_utils' );

		return rest_ensure_response( $settings );
	}

	/**
	 * Update settings.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function update_settings( $request ) {
		$data     = $request->get_json_params() ?: array();
		$current  = Settings::get_all();
		$sections = array( 'general', 'auth', 'mail', 'integrations', 'appearance', 'security' );

		foreach ( $sections as $section ) {
			if ( ! isset( $data[ $section ] ) || ! is_array( $data[ $section ] ) ) {
				continue;
			}

			$section_data = array_merge( $current[ $section ] ?? array(), $data[ $section ] );

			if ( 'integrations' === $section ) {
				$section_data = $this->sanitize_integrations( $section_data );
			}

			if ( 'auth' === $section ) {
				$section_data = $this->sanitize_auth( $section_data );
			}

			if ( 'mail' === $section ) {
				if ( isset( $section_data['smtp_pass'] ) && '********' === $section_data['smtp_pass'] ) {
					unset( $section_data['smtp_pass'] );
				} elseif ( ! empty( $section_data['smtp_pass'] ) ) {
					$section_data['smtp_pass'] = MailService::encrypt_secret( $section_data['smtp_pass'] );
				}

				if ( isset( $section_data['google_refresh_token'] ) && '********' === $section_data['google_refresh_token'] ) {
					unset( $section_data['google_refresh_token'] );
				} elseif ( ! empty( $section_data['google_refresh_token'] ) ) {
					$section_data['google_refresh_token'] = MailService::encrypt_secret( $section_data['google_refresh_token'] );
				}

				if ( isset( $section_data['google_client_secret'] ) && '********' === $section_data['google_client_secret'] ) {
					unset( $section_data['google_client_secret'] );
				} elseif ( ! empty( $section_data['google_client_secret'] ) ) {
					$section_data['google_client_secret'] = MailService::encrypt_secret( $section_data['google_client_secret'] );
				}
			}

			Settings::update( $section, $section_data );

			if ( 'general' === $section && ! empty( $section_data['dedicated_page_id'] ) ) {
				LoginPageService::mark_login_page( (int) $section_data['dedicated_page_id'] );
			}
		}

		$result = Settings::get_all();

		if ( ! empty( $result['integrations'] ) && is_array( $result['integrations'] ) ) {
			$result['integrations'] = $this->sanitize_integrations( $result['integrations'] );
		}

		if ( ! empty( $result['auth'] ) && is_array( $result['auth'] ) ) {
			$result['auth'] = $this->sanitize_auth( $result['auth'] );
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Test SMTP connection.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function test_smtp( $request ) {
		$data = $request->get_json_params() ?: array();
		$to   = sanitize_email( $data['email'] ?? get_option( 'admin_email' ) );

		$result = ( new MailService() )->send_test( $to );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Get pages for dedicated page picker.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_pages() {
		$pages = get_pages( array( 'post_status' => 'publish' ) );
		$list  = array();

		foreach ( $pages as $page ) {
			$list[] = array(
				'id'     => $page->ID,
				'title'  => $page->post_title,
				'url'    => get_permalink( $page->ID ),
				'is_slr' => LoginPageService::is_login_page( $page->ID ),
			);
		}

		return rest_ensure_response( $list );
	}

	/**
	 * Create or resolve the dedicated SLR login page.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_login_page() {
		$result = LoginPageService::ensure_dedicated_page();

		if ( ! empty( $result['error'] ) ) {
			return new \WP_Error( 'slr_login_page', $result['error'], array( 'status' => 500 ) );
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Get registered SMS providers.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_sms_providers() {
		$providers = apply_filters( 'slr_sms_providers', array() );
		$list      = array();

		foreach ( $providers as $provider ) {
			if ( is_object( $provider ) && method_exists( $provider, 'get_name' ) ) {
				$list[] = array( 'name' => $provider->get_name() );
			}
		}

		return rest_ensure_response( $list );
	}

	/**
	 * Get dashboard statistics.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_stats() {
		return rest_ensure_response( StatsService::get_dashboard_stats() );
	}

	/**
	 * Get Google OAuth authorization URL.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function google_oauth_url() {
		$url = ( new GoogleOAuthService() )->get_authorization_url();
		if ( is_wp_error( $url ) ) {
			return $url;
		}
		return rest_ensure_response( array( 'url' => $url ) );
	}

	/**
	 * Google OAuth callback — exchanges code and redirects to admin.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return void
	 */
	public function google_oauth_callback( $request ) {
		$service = new GoogleOAuthService();
		$code    = sanitize_text_field( $request->get_param( 'code' ) ?? '' );
		$state   = sanitize_text_field( $request->get_param( 'state' ) ?? '' );
		$error   = sanitize_text_field( $request->get_param( 'error' ) ?? '' );

		if ( $error ) {
			wp_safe_redirect( $service->admin_redirect_url( 'error', $error ) );
			exit;
		}

		$result = $service->handle_callback( $code, $state );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( $service->admin_redirect_url( 'error', $result->get_error_message() ) );
			exit;
		}

		wp_safe_redirect( $service->admin_redirect_url( 'connected' ) );
		exit;
	}

	/**
	 * Disconnect Google SMTP.
	 *
	 * @return \WP_REST_Response
	 */
	public function google_disconnect() {
		( new GoogleOAuthService() )->disconnect();
		return rest_ensure_response( array( 'success' => true ) );
	}
}
