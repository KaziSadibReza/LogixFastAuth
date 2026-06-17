<?php
/**
 * Runs when the plugin is deleted from WordPress admin.
 *
 * @package SLR
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$service_file = dirname( __FILE__ ) . '/includes/Services/class-slr-uninstall-service.php';
if ( ! file_exists( $service_file ) ) {
	return;
}

require_once $service_file;

if ( ! \SLR\Services\UninstallService::should_purge_data() ) {
	return;
}

\SLR\Services\UninstallService::purge_all_data();
