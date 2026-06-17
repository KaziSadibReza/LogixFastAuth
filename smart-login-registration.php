<?php
/**
 * Plugin Name: Smart Login Registration
 * Plugin URI:  https://github.com/KaziSadibReza
 * Description: Ultra-fast, secure login/registration ecosystem with React UI, OTP, WebAuthn, and deep WooCommerce/Tutor integration.
 * Version:     1.0.1
 * Author:      Kazi Sadib Reza
 * Author URI:  https://github.com/KaziSadibReza
 * Text Domain: smart-login-registration
 * Requires at least: 6.8
 * Requires PHP: 8.1
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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

if ( file_exists( SLR_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once SLR_PLUGIN_DIR . 'vendor/autoload.php';
}

require_once SLR_PLUGIN_DIR . 'includes/class-slr-autoloader.php';
SLR\Autoloader::register();

register_activation_hook( __FILE__, array( 'SLR\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SLR\Deactivator', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'SLR\Plugin', 'instance' ) );
