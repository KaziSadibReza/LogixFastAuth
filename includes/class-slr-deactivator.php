<?php
/**
 * Plugin deactivation.
 *
 * @package SLR
 */

namespace SLR; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Services\UninstallService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Deactivator
 */
class Deactivator {

	/**
	 * Deactivate plugin.
	 *
	 * @return void
	 */
	public static function deactivate() {
		if ( UninstallService::should_purge_data() ) {
			UninstallService::purge_all_data();
		}

		flush_rewrite_rules();
	}
}
