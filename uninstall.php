<?php
/**
 * Runs when the plugin is deleted from WordPress admin.
 *
 * @package SLR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! file_exists( dirname( __FILE__ ) . '/includes/Services/class-slr-uninstall-service.php' ) ) {
	return;
}

require_once dirname( __FILE__ ) . '/includes/Services/class-slr-uninstall-service.php';

if ( ! \SLR\Services\UninstallService::should_purge_data() ) {
	return;
}

\SLR\Services\UninstallService::purge_all_data();
