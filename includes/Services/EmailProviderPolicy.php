<?php
/**
 * Email provider / domain allowlist for new registrations.
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
 * Class EmailProviderPolicy
 */
class EmailProviderPolicy {

	/**
	 * Auth settings section used for this policy instance.
	 *
	 * @var array<string, mixed>
	 */
	private $auth_settings;

	/**
	 * @param array<string, mixed>|null $auth_settings Optional auth settings. Defaults to stored auth settings.
	 */
	public function __construct( $auth_settings = null ) {
		if ( is_array( $auth_settings ) ) {
			$this->auth_settings = $auth_settings;
			return;
		}

		$stored = Settings::get( 'auth' );
		$this->auth_settings = is_array( $stored ) ? $stored : array();
	}

	/**
	 * Known preset providers and their exact domains.
	 *
	 * @return array<string, array<int, string>>
	 */
	public static function get_preset_providers() {
		return array(
			'gmail'     => array( 'gmail.com', 'googlemail.com' ),
			'microsoft' => array( 'outlook.com', 'hotmail.com', 'live.com' ),
			'yahoo'     => array( 'yahoo.com' ),
			'icloud'    => array( 'icloud.com' ),
		);
	}

	/**
	 * Whether provider restriction is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) filter_var( $this->auth_settings['email_provider_restriction_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Whether an email is allowed by the current provider policy.
	 *
	 * Invalid or unparseable addresses return true so existing is_email() errors stay authoritative.
	 *
	 * @param string $email Email address.
	 * @return bool
	 */
	public function is_allowed( $email ) {
		if ( ! $this->is_enabled() ) {
			return true;
		}

		$domain = $this->extract_domain( (string) $email );
		if ( '' === $domain ) {
			return true;
		}

		return in_array( $domain, $this->get_allowed_domains(), true );
	}

	/**
	 * Validate that a new registration email is allowed.
	 *
	 * Callers must run sanitize_email() / is_email() first.
	 *
	 * @param string $email Email address.
	 * @return true|WP_Error
	 */
	public function validate( $email ) {
		if ( $this->is_allowed( $email ) ) {
			return true;
		}

		return new WP_Error(
			'logixfast_auth_email_provider_not_allowed',
			__( 'This email provider is not allowed. Please use an approved email address.', 'logixfast-auth' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Resolved exact-match allowlist: preset domains + custom domains.
	 *
	 * @return array<int, string>
	 */
	public function get_allowed_domains() {
		$domains = array();
		$presets = self::get_preset_providers();

		foreach ( self::sanitize_provider_keys( $this->auth_settings['email_allowed_providers'] ?? array() ) as $provider ) {
			if ( empty( $presets[ $provider ] ) ) {
				continue;
			}
			foreach ( $presets[ $provider ] as $domain ) {
				$domains[] = $domain;
			}
		}

		foreach ( self::sanitize_custom_domains( $this->auth_settings['email_allowed_custom_domains'] ?? array() ) as $domain ) {
			$domains[] = $domain;
		}

		return array_values( array_unique( $domains ) );
	}

	/**
	 * Keep only known preset provider keys.
	 *
	 * @param mixed $providers Raw provider list.
	 * @return array<int, string>
	 */
	public static function sanitize_provider_keys( $providers ) {
		if ( ! is_array( $providers ) ) {
			return array();
		}

		$known = array_keys( self::get_preset_providers() );
		$clean = array();

		foreach ( $providers as $key ) {
			if ( ! is_string( $key ) && ! is_numeric( $key ) ) {
				continue;
			}

			$key = strtolower( trim( (string) $key ) );
			if ( in_array( $key, $known, true ) ) {
				$clean[] = $key;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * Normalize and keep only exact valid hostnames.
	 *
	 * @param mixed $domains Raw domain list or newline/comma-separated string.
	 * @return array<int, string>
	 */
	public static function sanitize_custom_domains( $domains ) {
		if ( is_string( $domains ) ) {
			$parts   = preg_split( '/[\r\n,]+/', $domains );
			$domains = is_array( $parts ) ? $parts : array();
		}

		if ( ! is_array( $domains ) ) {
			return array();
		}

		$clean = array();
		foreach ( $domains as $domain ) {
			if ( ! is_string( $domain ) && ! is_numeric( $domain ) ) {
				continue;
			}

			$normalized = self::sanitize_custom_domain( (string) $domain );
			if ( '' !== $normalized ) {
				$clean[] = $normalized;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * Normalize one configured domain, or return empty string when invalid.
	 *
	 * Accepts `company.com`, `subdomain.company.com`, and optional `@company.com`.
	 * Rejects URLs, paths, ports, emails, wildcards, spaces, and invalid hostnames.
	 *
	 * @param string $domain Raw domain.
	 * @return string
	 */
	public static function sanitize_custom_domain( $domain ) {
		$value = strtolower( trim( (string) $domain ) );
		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '/\s/', $value ) ) {
			return '';
		}

		if ( preg_match( '#\A[a-z][a-z0-9+.-]*://#', $value ) ) {
			return '';
		}

		if ( isset( $value[0] ) && '@' === $value[0] ) {
			$value = substr( $value, 1 );
		}

		if ( str_contains( $value, '@' ) ) {
			return '';
		}

		$value = rtrim( $value, '.' );
		if ( '' === $value ) {
			return '';
		}

		if ( str_contains( $value, '/' ) || str_contains( $value, ':' ) || str_contains( $value, '*' ) || str_contains( $value, '?' ) || str_contains( $value, '#' ) ) {
			return '';
		}

		return self::is_valid_hostname( $value ) ? $value : '';
	}

	/**
	 * Extract a normalized domain from an email address.
	 *
	 * @param string $email Email address.
	 * @return string Empty when a domain cannot be extracted.
	 */
	private function extract_domain( $email ) {
		$email = strtolower( trim( (string) $email ) );
		$at    = strrpos( $email, '@' );
		if ( false === $at ) {
			return '';
		}

		$domain = rtrim( trim( substr( $email, $at + 1 ) ), '.' );
		if ( '' === $domain || str_contains( $domain, ' ' ) ) {
			return '';
		}

		return $domain;
	}

	/**
	 * Whether a value is an exact DNS hostname suitable for allowlisting.
	 *
	 * @param string $host Hostname.
	 * @return bool
	 */
	private static function is_valid_hostname( $host ) {
		$length = strlen( $host );
		if ( $length < 3 || $length > 253 || ! str_contains( $host, '.' ) ) {
			return false;
		}

		if ( ! preg_match( '/\A[a-z0-9.-]+\z/', $host ) ) {
			return false;
		}

		$labels = explode( '.', $host );
		foreach ( $labels as $label ) {
			$label_length = strlen( $label );
			if ( $label_length < 1 || $label_length > 63 ) {
				return false;
			}
			if ( '-' === $label[0] || '-' === $label[ $label_length - 1 ] ) {
				return false;
			}
			if ( ! preg_match( '/\A[a-z0-9-]+\z/', $label ) ) {
				return false;
			}
		}

		$tld = $labels[ count( $labels ) - 1 ];
		return strlen( $tld ) >= 2 && ! ctype_digit( $tld );
	}
}
