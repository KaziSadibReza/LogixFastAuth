<?php
/**
 * Rate limiting for auth endpoints.
 *
 * @package SLR
 */

namespace SLR\Services;

use SLR\Settings;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RateLimiter
 */
class RateLimiter {

	const BLOCKS_OPTION = 'slr_rate_blocks';

	/**
	 * Actions that share the global rate limit (Create Account, Sign In, etc.).
	 *
	 * @var string[]
	 */
	private static $limited_actions = array( 'register', 'login', 'forgot_password', 'reset_password', 'otp_send', 'otp_verify', 'webauthn' );

	/**
	 * Check rate limit for an action.
	 *
	 * @param string $action Action identifier.
	 * @param string $key    Unique key (IP, email, etc.).
	 * @return true|WP_Error
	 */
	public function check( $action, $key ) {
		if ( ! in_array( $action, self::$limited_actions, true ) ) {
			return true;
		}

		$security = Settings::get( 'security' );
		$max      = (int) ( $security['rate_limit_attempts'] ?? 10 );
		$window   = (int) ( $security['rate_limit_window'] ?? 300 );

		$block_id = $this->get_block_id( $action, $key );

		if ( $this->is_manually_blocked( $block_id ) ) {
			return new WP_Error(
				'slr_rate_limited',
				__( 'Too many attempts. Please try again later.', 'smart-login-registration' ),
				array( 'status' => 429 )
			);
		}

		$transient_key = $this->get_transient_key( $action, $key );
		$attempts      = (int) get_transient( $transient_key );

		if ( $attempts >= $max ) {
			$this->log_block( $action, $key, $window );
			return new WP_Error(
				'slr_rate_limited',
				__( 'Too many attempts. Please try again later.', 'smart-login-registration' ),
				array( 'status' => 429 )
			);
		}

		set_transient( $transient_key, $attempts + 1, $window );

		return true;
	}

	/**
	 * List active rate-limit blocks for admin.
	 *
	 * @return array
	 */
	public static function get_blocks() {
		$blocks = get_option( self::BLOCKS_OPTION, array() );
		if ( ! is_array( $blocks ) ) {
			return array();
		}

		$now     = time();
		$active  = array();

		foreach ( $blocks as $id => $block ) {
			if ( empty( $block['expires_at'] ) || (int) $block['expires_at'] <= $now ) {
				continue;
			}
			$active[] = array(
				'id'         => (string) $id,
				'action'     => (string) ( $block['action'] ?? '' ),
				'key'        => (string) ( $block['key'] ?? '' ),
				'ip'         => (string) ( $block['ip'] ?? '' ),
				'blocked_at' => (int) ( $block['blocked_at'] ?? 0 ),
				'expires_at' => (int) ( $block['expires_at'] ?? 0 ),
			);
		}

		usort(
			$active,
			function ( $a, $b ) {
				return $b['blocked_at'] <=> $a['blocked_at'];
			}
		);

		return $active;
	}

	/**
	 * Unblock a recorded rate-limit entry.
	 *
	 * @param string $block_id Block ID.
	 * @return bool
	 */
	public static function unblock( $block_id ) {
		$blocks = get_option( self::BLOCKS_OPTION, array() );
		if ( ! is_array( $blocks ) || empty( $blocks[ $block_id ] ) ) {
			return false;
		}

		$block = $blocks[ $block_id ];
		unset( $blocks[ $block_id ] );
		update_option( self::BLOCKS_OPTION, $blocks, false );

		if ( ! empty( $block['action'] ) && ! empty( $block['key'] ) ) {
			delete_transient( ( new self() )->get_transient_key( $block['action'], $block['key'] ) );
		}

		return true;
	}

	/**
	 * @param string $action Action.
	 * @param string $key    Key.
	 * @param int    $window Window seconds.
	 * @return void
	 */
	private function log_block( $action, $key, $window ) {
		$blocks   = get_option( self::BLOCKS_OPTION, array() );
		$block_id = $this->get_block_id( $action, $key );

		if ( ! is_array( $blocks ) ) {
			$blocks = array();
		}

		$blocks[ $block_id ] = array(
			'action'     => $action,
			'key'        => $key,
			'ip'         => self::get_client_ip(),
			'blocked_at' => time(),
			'expires_at' => time() + $window,
		);

		update_option( self::BLOCKS_OPTION, $blocks, false );
	}

	/**
	 * @param string $block_id Block ID.
	 * @return bool
	 */
	private function is_manually_blocked( $block_id ) {
		$blocks = get_option( self::BLOCKS_OPTION, array() );
		if ( ! is_array( $blocks ) || empty( $blocks[ $block_id ] ) ) {
			return false;
		}

		return (int) ( $blocks[ $block_id ]['expires_at'] ?? 0 ) > time();
	}

	/**
	 * @param string $action Action.
	 * @param string $key    Key.
	 * @return string
	 */
	private function get_block_id( $action, $key ) {
		return md5( $action . '_' . $key );
	}

	/**
	 * @param string $action Action.
	 * @param string $key    Key.
	 * @return string
	 */
	private function get_transient_key( $action, $key ) {
		return 'slr_rl_' . md5( $action . '_' . $key );
	}

	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	public static function get_client_ip() {
		$ip = '';

		if ( apply_filters( 'slr_trust_proxy_headers', false ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip  = trim( $ips[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}
}
