<?php
/**
 * Plugin deactivation.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Services\UninstallService;

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
