<?php
/**
 * Login redirect handler (delegates to WordPress_Login integration).
 *
 * @package SLR
 */

namespace SLR\Frontend; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

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
