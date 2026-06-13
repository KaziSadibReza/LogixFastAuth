<?php
/**
 * Plugin Name: Smart Login Registration (SLR)
 * Plugin URI:  https://github.com/KaziSadibReza
 * Description: Ultra-fast, secure login/registration ecosystem with React UI, OTP, WebAuthn, and deep WooCommerce/Tutor integration.
 * Version:     1.0.1
 * Author:      Kazi Sadib Reza
 * Author URI:  https://github.com/KaziSadibReza
 * Text Domain: smart-login-registration
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package SLR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SLR_VERSION', '1.0.1' );
define( 'SLR_PLUGIN_FILE', __FILE__ );
define( 'SLR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SLR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Optional dev-mode constants (set in wp-config.php only during local development):
 *
 * define( 'SLR_DEV', true );
 * define( 'SLR_DEV_URL', 'http://localhost:5173' );
 *
 * Or use the slr_dev_server_url filter instead of SLR_DEV_URL.
 */


// if ( ! defined( 'SLR_DEV' ) ) {
// 	define( 'SLR_DEV', true );
// }
// if ( ! defined( 'SLR_DEV_URL' ) ) {
// 	define( 'SLR_DEV_URL', 'http://localhost:5173' );
// }


if ( file_exists( SLR_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once SLR_PLUGIN_DIR . 'vendor/autoload.php';
}

require_once SLR_PLUGIN_DIR . 'includes/class-slr-autoloader.php';
SLR\Autoloader::register();

register_activation_hook( __FILE__, array( 'SLR\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SLR\Deactivator', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'SLR\Plugin', 'instance' ) );
