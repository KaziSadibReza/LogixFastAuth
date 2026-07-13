<?php
/**
 * LogixFastAuth usage statistics tracker.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Integrations\Integration_Availability;
use LogixFastAuth\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class StatsService
 */
class StatsService {

	const OPTION_KEY        = 'logixfast_auth_stats';
	const STATS_CACHE_KEY   = 'logixfast_auth_dashboard_stats';
	const STATS_CACHE_TTL   = 60;

	/**
	 * Record a successful login via LogixFastAuth.
	 *
	 * @return void
	 */
	public static function record_login() {
		$stats = self::get_raw();
		$today = gmdate( 'Y-m-d' );

		++$stats['logins_total'];
		if ( ( $stats['logins_today_date'] ?? '' ) !== $today ) {
			$stats['logins_today_date'] = $today;
			$stats['logins_today']      = 1;
		} else {
			++$stats['logins_today'];
		}

		update_option( self::OPTION_KEY, $stats, false );
		self::bust_dashboard_cache();
	}

	/**
	 * Record a registration via LogixFastAuth.
	 *
	 * @return void
	 */
	public static function record_registration() {
		$stats = self::get_raw();
		$today = gmdate( 'Y-m-d' );

		++$stats['registrations_total'];
		if ( ( $stats['registrations_today_date'] ?? '' ) !== $today ) {
			$stats['registrations_today_date'] = $today;
			$stats['registrations_today']      = 1;
		} else {
			++$stats['registrations_today'];
		}

		update_option( self::OPTION_KEY, $stats, false );
		self::bust_dashboard_cache();
	}

	/**
	 * Get dashboard stats for admin.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_dashboard_stats() {
		$cached = get_transient( self::STATS_CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$raw      = self::get_raw();
		$today    = gmdate( 'Y-m-d' );
		$counts   = count_users();
		$general  = Settings::get( 'general' );
		$integ    = Settings::get( 'integrations' );
		$page_id  = (int) ( $general['dedicated_page_id'] ?? 0 );
		$page     = $page_id > 0 ? get_post( $page_id ) : null;

		$active_integrations = 0;
		foreach ( array( 'replace_wp_login', 'replace_woocommerce', 'replace_tutor', 'replace_elementor' ) as $key ) {
			if ( ! empty( $integ[ $key ] ) && Integration_Availability::is_setting_available( $key ) ) {
				++$active_integrations;
			}
		}

		$new_users_today = count(
			get_users(
				array(
					'fields'     => 'ID',
					'date_query' => array(
						array(
							'after'     => $today,
							'inclusive' => true,
							'column'    => 'user_registered',
						),
					),
				)
			)
		);

		$stats = array(
			'total_users'          => (int) ( $counts['total_users'] ?? 0 ),
			'new_users_today'      => $new_users_today,
			'logixfast_auth_logins_total'     => (int) ( $raw['logins_total'] ?? 0 ),
			'logixfast_auth_logins_today'     => ( $raw['logins_today_date'] ?? '' ) === $today ? (int) ( $raw['logins_today'] ?? 0 ) : 0,
			'logixfast_auth_registrations_total' => (int) ( $raw['registrations_total'] ?? 0 ),
			'logixfast_auth_registrations_today' => ( $raw['registrations_today_date'] ?? '' ) === $today ? (int) ( $raw['registrations_today'] ?? 0 ) : 0,
			'active_integrations'  => $active_integrations,
			'dedicated_page_set'   => $page_id > 0 && $page instanceof \WP_Post,
			'dedicated_page_title' => $page instanceof \WP_Post ? $page->post_title : '',
			'plugin_version'       => defined( 'LOGIXFAST_AUTH_VERSION' ) ? LOGIXFAST_AUTH_VERSION : '1.0.3',
		);

		set_transient( self::STATS_CACHE_KEY, $stats, self::STATS_CACHE_TTL );

		return $stats;
	}

	/**
	 * Clear cached dashboard stats.
	 *
	 * @return void
	 */
	private static function bust_dashboard_cache() {
		delete_transient( self::STATS_CACHE_KEY );
	}

	/**
	 * Get raw stored stats.
	 *
	 * @return array<string, int|string>
	 */
	private static function get_raw() {
		$defaults = array(
			'logins_total'              => 0,
			'logins_today'              => 0,
			'logins_today_date'         => '',
			'registrations_total'       => 0,
			'registrations_today'       => 0,
			'registrations_today_date'  => '',
		);

		$stored = get_option( self::OPTION_KEY, array() );
		return is_array( $stored ) ? wp_parse_args( $stored, $defaults ) : $defaults;
	}
}
