<?php
/**
 * Runs when the plugin is deleted from WordPress admin.
 *
 * @package LogixFastAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! file_exists( dirname( __FILE__ ) . '/includes/Services/class-logixfast-auth-uninstall-service.php' ) ) {
	return;
}

require_once dirname( __FILE__ ) . '/includes/Services/class-logixfast-auth-uninstall-service.php';

if ( ! \LogixFastAuth\Services\UninstallService::should_purge_data() ) {
	return;
}

\LogixFastAuth\Services\UninstallService::purge_all_data();
