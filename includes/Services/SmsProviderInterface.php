<?php
/**
 * SMS provider interface.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface SmsProviderInterface
 */
interface SmsProviderInterface {

	/**
	 * Provider display name.
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Send SMS message.
	 *
	 * @param string $phone   E.164 phone number.
	 * @param string $message Message body.
	 * @return true|\WP_Error
	 */
	public function send( $phone, $message );
}
