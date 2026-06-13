<?php
/**
 * PSR-4 style autoloader for SLR plugin classes.
 *
 * @package SLR
 */

namespace SLR;

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
	 * Autoload SLR classes.
	 *
	 * @param string $class Class name.
	 * @return void
	 */
	public static function autoload( $class ) {
		if ( strpos( $class, 'SLR\\' ) !== 0 ) {
			return;
		}

		$relative = str_replace( 'SLR\\', '', $class );
		$relative = str_replace( '\\', '/', $relative );

		$parts      = explode( '/', $relative );
		$class_name = array_pop( $parts );
		$snake      = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $class_name ) );
		$snake      = str_replace( '_', '-', $snake );

		$paths = array();

		if ( ! empty( $parts ) ) {
			$paths[] = SLR_PLUGIN_DIR . 'includes/' . implode( '/', $parts ) . '/class-slr-' . $snake . '.php';
			$paths[] = SLR_PLUGIN_DIR . 'includes/' . implode( '/', $parts ) . '/' . $class_name . '.php';
		}

		$paths[] = SLR_PLUGIN_DIR . 'includes/class-slr-' . $snake . '.php';
		$paths[] = SLR_PLUGIN_DIR . 'includes/' . $relative . '.php';

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
}
