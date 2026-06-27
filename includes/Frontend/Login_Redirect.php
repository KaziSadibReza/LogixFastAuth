<?php
/**
 * Login redirect handler (delegates to WordPress_Login integration).
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Frontend; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Login_Redirect
 *
 * Placeholder class — redirect logic lives in Integrations\WordPress_Login.
 */
class Login_Redirect {
	// Intentionally empty; integration class handles redirects.
}
