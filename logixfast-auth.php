<?php
/**
 * Plugin Name: LogixFast Auth
 * Plugin URI:  https://github.com/KaziSadibReza/LogixFastAuth
 * Description: Fast, secure authentication ecosystem with React UI, OTP, WebAuthn, and deep WooCommerce/Tutor integration.
 * Version:     1.0.3
 * Author:      Kazi Sadib Reza
 * Author URI:  https://github.com/KaziSadibReza
 * Text Domain: logixfast-auth
 * Requires at least: 6.8
 * Requires PHP: 8.1
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package LogixFastAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Shared LOGIXFAST_AUTH_ bootstrap constants; Plugin Check infers longer logixfast_auth_* sub-prefixes from hooks/options.
define( 'LOGIXFAST_AUTH_VERSION', '1.0.3' );
define( 'LOGIXFAST_AUTH_PLUGIN_FILE', __FILE__ );
define( 'LOGIXFAST_AUTH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOGIXFAST_AUTH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LOGIXFAST_AUTH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals

if ( file_exists( LOGIXFAST_AUTH_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once LOGIXFAST_AUTH_PLUGIN_DIR . 'vendor/autoload.php';
}

require_once LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/class-logixfast-auth-autoloader.php';
LogixFastAuth\Autoloader::register();

register_activation_hook( __FILE__, array( 'LogixFastAuth\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LogixFastAuth\Deactivator', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'LogixFastAuth\Plugin', 'instance' ) );
