<?php
/**
 * Post-auth redirect resolution.
 *
 * @package SLR
 */

namespace SLR\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RedirectService
 */
class RedirectService {

	const TYPE_STAY    = 'stay';
	const TYPE_DEFAULT = 'default';
	const TYPE_PAGE    = 'page';
	const TYPE_URL     = 'url';

	/**
	 * Resolve post-login redirect.
	 *
	 * @param \WP_User $user User object.
	 * @return array{url:string,action:string}
	 */
	public static function resolve_login( $user ) {
		return self::resolve( 'login', $user );
	}

	/**
	 * Resolve post-registration redirect.
	 *
	 * @param int $user_id User ID.
	 * @return array{url:string,action:string}
	 */
	public static function resolve_register( $user_id ) {
		return self::resolve( 'register', $user_id );
	}

	/**
	 * Where to send logged-in users who open the dedicated login page.
	 *
	 * @return string
	 */
	public static function resolve_logged_in_login_page() {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$user    = wp_get_current_user();
		$general = Settings::get( 'general' );
		$type    = (string) ( $general['login_page_logged_in_redirect_type'] ?? 'default' );

		switch ( $type ) {
			case self::TYPE_PAGE:
				$page_id = (int) ( $general['login_page_logged_in_redirect_page_id'] ?? 0 );
				$url     = $page_id > 0 ? get_permalink( $page_id ) : '';
				return $url ? (string) $url : self::get_dashboard_default_url( $user );

			case self::TYPE_URL:
				$url = esc_url_raw( $general['login_page_logged_in_redirect_url'] ?? '' );
				return $url ? $url : self::get_dashboard_default_url( $user );

			case self::TYPE_DEFAULT:
			default:
				return self::get_dashboard_default_url( $user );
		}
	}

	/**
	 * Public redirect rules for frontend (popup can honor "stay" before navigation).
	 *
	 * @return array{login:array,register:array}
	 */
	public static function get_public_config() {
		$general = Settings::get( 'general' );

		return array(
			'login'    => self::format_public_rule( 'login', $general ),
			'register' => self::format_public_rule( 'register', $general ),
		);
	}

	/**
	 * @param string               $kind     login|register.
	 * @param \WP_User|int|null    $context  User or user ID.
	 * @return array{url:string,action:string}
	 */
	private static function resolve( $kind, $context ) {
		$general = Settings::get( 'general' );
		$type    = self::get_type( $kind, $general );

		switch ( $type ) {
			case self::TYPE_STAY:
				return array(
					'url'    => '',
					'action' => 'stay',
				);

			case self::TYPE_PAGE:
				$page_id = (int) ( $general[ $kind . '_redirect_page_id' ] ?? 0 );
				$url     = $page_id > 0 ? get_permalink( $page_id ) : '';
				return array(
					'url'    => $url ? (string) $url : self::get_default_url( $kind, $context ),
					'action' => 'navigate',
				);

			case self::TYPE_URL:
				$url = esc_url_raw( $general[ $kind . '_redirect_url' ] ?? '' );
				return array(
					'url'    => $url ? $url : self::get_default_url( $kind, $context ),
					'action' => 'navigate',
				);

			case self::TYPE_DEFAULT:
			default:
				return array(
					'url'    => self::get_default_url( $kind, $context ),
					'action' => 'navigate',
				);
		}
	}

	/**
	 * @param string $kind login|register.
	 * @param array  $general General settings.
	 * @return array{type:string,url:string,page_id:int,page_url:string}
	 */
	private static function format_public_rule( $kind, $general ) {
		$type    = self::get_type( $kind, $general );
		$page_id = (int) ( $general[ $kind . '_redirect_page_id' ] ?? 0 );
		$page_url = $page_id > 0 ? (string) get_permalink( $page_id ) : '';

		return array(
			'type'     => $type,
			'url'      => (string) ( $general[ $kind . '_redirect_url' ] ?? '' ),
			'page_id'  => $page_id,
			'page_url' => $page_url,
		);
	}

	/**
	 * @param string $kind login|register.
	 * @param array  $general Settings section.
	 * @return string
	 */
	private static function get_type( $kind, $general ) {
		$key = $kind . '_redirect_type';
		if ( ! empty( $general[ $key ] ) ) {
			return (string) $general[ $key ];
		}

		$url_key = $kind . '_redirect_url';
		if ( ! empty( $general[ $url_key ] ) ) {
			return self::TYPE_URL;
		}

		return self::TYPE_DEFAULT;
	}

	/**
	 * WordPress / integration default destination.
	 *
	 * @param string            $kind    login|register.
	 * @param \WP_User|int|null $context User or user ID.
	 * @return string
	 */
	private static function get_default_url( $kind, $context ) {
		$url = '';

		if ( 'login' === $kind && $context instanceof \WP_User && function_exists( 'tutor_utils' ) ) {
			$url = apply_filters( 'tutor_after_login_redirect_url', '', $context );
		}

		if ( 'register' === $kind && function_exists( 'tutor_utils' ) ) {
			$user_id = is_int( $context ) ? $context : 0;
			$url     = apply_filters( 'tutor_student_register_redirect_url', '', $user_id );
		}

		if ( empty( $url ) ) {
			$url = home_url( '/' );
		}

		if ( 'login' === $kind && $context instanceof \WP_User ) {
			$url = apply_filters( 'slr_login_redirect', $url, $context ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- SLR plugin hook.
		}

		if ( 'register' === $kind ) {
			$user_id = is_int( $context ) ? $context : 0;
			$url     = apply_filters( 'slr_register_redirect', $url, $user_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- SLR plugin hook.
		}

		return (string) $url;
	}

	/**
	 * Tutor dashboard, WooCommerce My Account, or home — for logged-in login page visits.
	 *
	 * @param \WP_User $user User object.
	 * @return string
	 */
	private static function get_dashboard_default_url( $user ) {
		$url = '';

		if ( function_exists( 'tutor_utils' ) ) {
			$url = (string) tutor_utils()->get_tutor_dashboard_page_permalink();
		}

		if ( empty( $url ) && function_exists( 'wc_get_page_permalink' ) ) {
			$url = (string) wc_get_page_permalink( 'myaccount' );
		}

		if ( empty( $url ) ) {
			$url = home_url( '/' );
		}

		return (string) apply_filters( 'slr_logged_in_login_page_redirect', $url, $user ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- SLR plugin hook.
	}
}
