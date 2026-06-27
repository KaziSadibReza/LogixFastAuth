<?php
/**
 * Google OAuth for Gmail SMTP.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Settings;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GoogleOAuthService
 */
class GoogleOAuthService {

	const SCOPE = 'https://mail.google.com/ https://www.googleapis.com/auth/userinfo.email';

	/**
	 * Get OAuth client ID.
	 *
	 * @return string
	 */
	public function get_client_id() {
		$mail = Settings::get( 'mail' );
		if ( ! empty( $mail['google_client_id'] ) ) {
			return (string) $mail['google_client_id'];
		}
		return (string) apply_filters( 'logixfast_auth_google_client_id', '' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LogixFastAuth plugin hook.
	}

	/**
	 * Get OAuth client secret.
	 *
	 * @return string
	 */
	public function get_client_secret() {
		$mail = Settings::get( 'mail' );
		if ( ! empty( $mail['google_client_secret'] ) ) {
			return ( MailService::instance() )->decrypt_secret( $mail['google_client_secret'] );
		}
		return (string) apply_filters( 'logixfast_auth_google_client_secret', '' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LogixFastAuth plugin hook.
	}

	/**
	 * OAuth redirect URI.
	 *
	 * @return string
	 */
	public function get_redirect_uri() {
		return trailingslashit( rest_url( 'logixfast-auth/v1/settings/google/callback' ) );
	}

	/**
	 * Whether Google OAuth credentials are configured.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return ! empty( $this->get_client_id() ) && ! empty( $this->get_client_secret() );
	}

	/**
	 * Build authorization URL.
	 *
	 * @return string|WP_Error
	 */
	public function get_authorization_url() {
		if ( ! $this->is_configured() ) {
			return new WP_Error(
				'logixfast_auth_google_not_configured',
				__( 'Add your Google OAuth Client ID and Client Secret first.', 'logixfast-auth' ),
				array( 'status' => 400 )
			);
		}

		$state = wp_generate_password( 32, false );
		set_transient(
			'logixfast_auth_google_oauth_' . $state,
			array(
				'user_id' => get_current_user_id(),
				'time'    => time(),
			),
			600
		);

		$params = array(
			'client_id'     => $this->get_client_id(),
			'redirect_uri'  => $this->get_redirect_uri(),
			'response_type' => 'code',
			'scope'         => self::SCOPE,
			'access_type'   => 'offline',
			'prompt'        => 'consent',
			'state'         => $state,
		);

		return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * Handle OAuth callback and store tokens.
	 *
	 * @param string $code  Authorization code.
	 * @param string $state State token.
	 * @return true|WP_Error
	 */
	public function handle_callback( $code, $state ) {
		$session = get_transient( 'logixfast_auth_google_oauth_' . sanitize_text_field( $state ) );
		delete_transient( 'logixfast_auth_google_oauth_' . sanitize_text_field( $state ) );

		if ( empty( $session ) || empty( $session['user_id'] ) ) {
			return new WP_Error( 'logixfast_auth_google_state_invalid', __( 'OAuth session expired. Please try again.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		$owner_id = (int) $session['user_id'];
		$owner    = get_user_by( 'id', $owner_id );

		if ( ! $owner || ! user_can( $owner, 'manage_options' ) ) {
			return new WP_Error(
				'logixfast_auth_google_unauthorized',
				__( 'OAuth session is not valid for an administrator.', 'logixfast-auth' ),
				array( 'status' => 403 )
			);
		}

		$current_id = get_current_user_id();

		// REST callbacks often arrive without auth cookies; trust the signed state owner instead.
		if ( $current_id > 0 && $current_id !== $owner_id ) {
			return new WP_Error(
				'logixfast_auth_google_unauthorized',
				__( 'You must finish Google authorization from the same administrator session that started it.', 'logixfast-auth' ),
				array( 'status' => 403 )
			);
		}

		if ( empty( $code ) ) {
			return new WP_Error( 'logixfast_auth_google_code_missing', __( 'Google did not return an authorization code.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'body' => array(
					'code'          => $code,
					'client_id'     => $this->get_client_id(),
					'client_secret' => $this->get_client_secret(),
					'redirect_uri'  => $this->get_redirect_uri(),
					'grant_type'    => 'authorization_code',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $data['refresh_token'] ) ) {
			return new WP_Error(
				'logixfast_auth_google_no_refresh_token',
				__( 'Google did not return a refresh token. Revoke app access in your Google account and try again.', 'logixfast-auth' ),
				array( 'status' => 500 )
			);
		}

		$account_email = $this->fetch_account_email( $data['access_token'] ?? '' );
		$mail          = Settings::get( 'mail' );

		$updates = array(
			'transport'            => 'google',
			'google_connected'     => true,
			'google_refresh_token' => MailService::encrypt_secret( $data['refresh_token'] ),
		);

		if ( $account_email ) {
			$updates['google_account_email'] = $account_email;
			$updates['from_email']           = $account_email;
		}

		Settings::update( 'mail', wp_parse_args( $updates, $mail ) );

		return true;
	}

	/**
	 * Disconnect Google account.
	 *
	 * @return void
	 */
	public function disconnect() {
		$mail = Settings::get( 'mail' );
		Settings::update(
			'mail',
			wp_parse_args(
				array(
					'google_connected'     => false,
					'google_refresh_token' => '',
					'google_account_email' => '',
				),
				$mail
			)
		);
	}

	/**
	 * Admin redirect URL after OAuth.
	 *
	 * @param string $status success|error.
	 * @param string $message Optional message.
	 * @return string
	 */
	public function admin_redirect_url( $status, $message = '' ) {
		$base = add_query_arg( 'page', 'logixfastauth', admin_url( 'admin.php' ) );
		$hash = array( 'google' => $status );

		if ( $message ) {
			$hash['google_message'] = $message;
		}

		return $base . '#/mail?' . http_build_query( $hash, '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * Fetch Google account email from access token.
	 *
	 * @param string $access_token Access token.
	 * @return string
	 */
	private function fetch_account_email( $access_token ) {
		if ( empty( $access_token ) ) {
			return '';
		}

		$response = wp_remote_get(
			'https://www.googleapis.com/oauth2/v2/userinfo',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return ! empty( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
	}
}
