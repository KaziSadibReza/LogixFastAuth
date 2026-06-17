<?php
/**
 * Rate limiting for auth endpoints.
 *
 * @package SLR
 */

namespace SLR\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

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
	 * Check whether an action is currently rate limited (read-only).
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

		$attempts = $this->get_attempt_count( $action, $key );

		if ( $attempts >= $max ) {
			if ( ! $this->is_manually_blocked( $block_id ) ) {
				$this->log_block( $action, $key, $window );
			}
			return new WP_Error(
				'slr_rate_limited',
				__( 'Too many attempts. Please try again later.', 'smart-login-registration' ),
				array( 'status' => 429 )
			);
		}

		return true;
	}

	/**
	 * Check rate limit and record this attempt (for send/spam-prone endpoints).
	 *
	 * @param string $action Action identifier.
	 * @param string $key    Unique key (IP, email, etc.).
	 * @return true|WP_Error
	 */
	public function throttle( $action, $key ) {
		$result = $this->check( $action, $key );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->record_attempt( $action, $key );
		return true;
	}

	/**
	 * Apply IP + optional identifier scoped throttles.
	 *
	 * @param string      $action            Action identifier.
	 * @param string|null $identifier_scoped Optional scoped key (e.g. purpose:email).
	 * @return true|WP_Error
	 */
	public static function throttle_scoped( $action, $identifier_scoped = null ) {
		$limiter = new self();
		$ip      = self::get_client_ip();

		$ip_rate = $limiter->throttle( $action, $ip );
		if ( is_wp_error( $ip_rate ) ) {
			return $ip_rate;
		}

		if ( null !== $identifier_scoped && '' !== $identifier_scoped ) {
			return $limiter->throttle( $action, $identifier_scoped );
		}

		return true;
	}

	/**
	 * Record a request attempt for an action.
	 *
	 * @param string $action Action identifier.
	 * @param string $key    Unique key (IP, email, etc.).
	 * @return void
	 */
	public function record_attempt( $action, $key ) {
		if ( ! in_array( $action, self::$limited_actions, true ) ) {
			return;
		}

		$security = Settings::get( 'security' );
		$max      = (int) ( $security['rate_limit_attempts'] ?? 10 );
		$window   = (int) ( $security['rate_limit_window'] ?? 300 );

		$transient_key = $this->get_transient_key( $action, $key );
		$attempts      = $this->get_attempt_count( $action, $key ) + 1;

		set_transient( $transient_key, $attempts, $window );

		if ( $attempts >= $max ) {
			$this->log_block( $action, $key, $window );
		}
	}

	/**
	 * Record a failed attempt for an action.
	 *
	 * @param string $action Action identifier.
	 * @param string $key    Unique key (IP, email, etc.).
	 * @return void
	 */
	public function record_failure( $action, $key ) {
		$this->record_attempt( $action, $key );
	}

	/**
	 * Clear rate-limit counters and active blocks for a key.
	 *
	 * @param string      $action Action identifier.
	 * @param string      $key    Unique key.
	 * @param string|null $ip     Optional IP to clear matching blocks.
	 * @return void
	 */
	public static function clear_for_key( $action, $key, $ip = null ) {
		$limiter = new self();
		delete_transient( $limiter->get_transient_key( $action, $key ) );

		$blocks   = get_option( self::BLOCKS_OPTION, array() );
		$block_id = $limiter->get_block_id( $action, $key );
		$changed  = false;

		if ( is_array( $blocks ) && isset( $blocks[ $block_id ] ) ) {
			unset( $blocks[ $block_id ] );
			$changed = true;
		}

		if ( is_array( $blocks ) && $ip ) {
			foreach ( $blocks as $id => $block ) {
				if ( ! empty( $block['ip'] ) && $block['ip'] === $ip ) {
					unset( $blocks[ $id ] );
					$changed = true;
				}
			}
		}

		if ( $changed ) {
			update_option( self::BLOCKS_OPTION, $blocks, false );
		}
	}

	/**
	 * Clear all auth rate limits for an IP address.
	 *
	 * @param string $ip Client IP.
	 * @return void
	 */
	public static function clear_for_ip( $ip ) {
		if ( '' === $ip ) {
			return;
		}

		foreach ( self::$limited_actions as $action ) {
			self::clear_for_key( $action, $ip, $ip );
		}
	}

	/**
	 * Remove expired blocks from storage.
	 *
	 * @return bool Whether any blocks were removed.
	 */
	public static function prune_expired_blocks() {
		$blocks = get_option( self::BLOCKS_OPTION, array() );
		if ( ! is_array( $blocks ) || empty( $blocks ) ) {
			return false;
		}

		$now     = time();
		$changed = false;

		foreach ( $blocks as $id => $block ) {
			if ( empty( $block['expires_at'] ) || (int) $block['expires_at'] <= $now ) {
				unset( $blocks[ $id ] );
				$changed = true;
			}
		}

		if ( ! $changed ) {
			return false;
		}

		update_option( self::BLOCKS_OPTION, $blocks, false );

		return true;
	}

	/**
	 * List active rate-limit blocks for admin.
	 *
	 * @return array
	 */
	public static function get_blocks() {
		self::prune_expired_blocks();

		$blocks = get_option( self::BLOCKS_OPTION, array() );
		if ( ! is_array( $blocks ) ) {
			return array();
		}

		$now    = time();
		$active = array();

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
	 * @return int
	 */
	private function get_attempt_count( $action, $key ) {
		return (int) get_transient( $this->get_transient_key( $action, $key ) );
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

		if ( $this->is_manually_blocked( $block_id ) ) {
			return;
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

		if ( apply_filters( 'slr_trust_proxy_headers', false ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- SLR plugin hook.
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip  = trim( $ips[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}
}
