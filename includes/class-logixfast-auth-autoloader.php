<?php
/**
 * PSR-4 style autoloader for LogixFastAuth plugin classes.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Autoloader
 */
class Autoloader {

	/**
	 * Register autoloader.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload LogixFastAuth classes.
	 *
	 * @param string $class Class name.
	 * @return void
	 */
	public static function autoload( $class ) {
		if ( strpos( $class, 'LogixFastAuth\\' ) !== 0 ) {
			return;
		}

		$relative = str_replace( 'LogixFastAuth\\', '', $class );
		$relative = str_replace( '\\', '/', $relative );

		$parts      = explode( '/', $relative );
		$class_name = array_pop( $parts );
		$snake      = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $class_name ) );
		$snake      = str_replace( '_', '-', $snake );

		$paths = array();

		if ( ! empty( $parts ) ) {
			$paths[] = LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/' . implode( '/', $parts ) . '/class-logixfast-auth-' . $snake . '.php';
			$paths[] = LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/' . implode( '/', $parts ) . '/' . $class_name . '.php';
		}

		$paths[] = LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/class-logixfast-auth-' . $snake . '.php';
		$paths[] = LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/' . $relative . '.php';

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
}
