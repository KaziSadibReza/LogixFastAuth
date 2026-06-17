<?php
/**
 * Shared asset URL helpers and Vite manifest loader.
 *
 * @package SLR
 */

namespace SLR; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Assets
 */
class Assets {

	/**
	 * Cached manifest data.
	 *
	 * @var array|null
	 */
	private static $manifest = null;

	/**
	 * Vite dev entries queued for inline module loading.
	 *
	 * @var array<string, string>
	 */
	private static $vite_dev_entries = array();

	/**
	 * Vite manifest entry keys.
	 */
	const ENTRY_POPUP     = 'src/frontend/main-popup.tsx';
	const ENTRY_PAGE      = 'src/frontend/main-page.tsx';
	const ENTRY_BOOTSTRAP = 'src/frontend/bootstrap.ts';

	/**
	 * Whether Vite dev mode is active.
	 *
	 * @return bool
	 */
	public static function is_dev_mode() {
		if ( ! defined( 'SLR_DEV' ) || ! constant( 'SLR_DEV' ) ) {
			return false;
		}

		return '' !== self::get_dev_server_url();
	}

	/**
	 * Get Vite dev server base URL (no trailing slash).
	 *
	 * @return string
	 */
	public static function get_dev_server_url() {
		if ( defined( 'SLR_DEV_URL' ) ) {
			return rtrim( (string) constant( 'SLR_DEV_URL' ), '/' );
		}

		return rtrim( (string) apply_filters( 'slr_dev_server_url', '' ), '/' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- SLR plugin hook.
	}

	/**
	 * Base URL for compiled dist assets.
	 *
	 * @return string
	 */
	public static function get_dist_base_url() {
		if ( self::is_dev_mode() ) {
			return self::get_dev_server_url() . '/assets/dist/';
		}

		return SLR_PLUGIN_URL . 'assets/dist/';
	}

	/**
	 * Path to Vite manifest.json on disk.
	 *
	 * @return string
	 */
	public static function get_manifest_path() {
		$paths = array(
			SLR_PLUGIN_DIR . 'assets/dist/manifest.json',
			SLR_PLUGIN_DIR . 'assets/dist/.vite/manifest.json',
		);

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return $paths[0];
	}

	/**
	 * Load Vite build manifest.
	 *
	 * @return array
	 */
	public static function get_manifest() {
		if ( null !== self::$manifest ) {
			return self::$manifest;
		}

		$path = self::get_manifest_path();

		if ( ! file_exists( $path ) ) {
			self::$manifest = array();
			return self::$manifest;
		}

		$data = json_decode( (string) file_get_contents( $path ), true );
		self::$manifest = is_array( $data ) ? $data : array();

		return self::$manifest;
	}

	/**
	 * Get a manifest entry by source key.
	 *
	 * @param string $entry_key Manifest source key.
	 * @return array|null
	 */
	public static function get_entry( $entry_key ) {
		$manifest = self::get_manifest();
		return isset( $manifest[ $entry_key ] ) ? $manifest[ $entry_key ] : null;
	}

	/**
	 * Resolve built file URL from manifest or legacy fallback.
	 *
	 * @param string $entry_key    Manifest entry key.
	 * @param string $fallback_path Legacy relative dist path.
	 * @return string
	 */
	public static function get_entry_file_url( $entry_key, $fallback_path ) {
		if ( self::is_dev_mode() ) {
			return self::get_dev_server_url() . '/' . ltrim( $entry_key, '/' );
		}

		$entry = self::get_entry( $entry_key );

		if ( $entry && ! empty( $entry['file'] ) ) {
			return self::get_dist_base_url() . ltrim( $entry['file'], '/' );
		}

		return self::get_asset_url( $fallback_path );
	}

	/**
	 * Get CSS files for a manifest entry (includes imported chunk CSS).
	 *
	 * @param string $entry_key Manifest entry key.
	 * @return array
	 */
	public static function get_entry_css_files( $entry_key ) {
		$manifest = self::get_manifest();
		$urls     = array();
		$visited  = array();

		self::collect_entry_css( $entry_key, $manifest, $urls, $visited );

		return array_values( array_unique( $urls ) );
	}

	/**
	 * Recursively collect CSS URLs from manifest entry and its imports.
	 *
	 * @param string $key      Manifest key.
	 * @param array  $manifest Full manifest.
	 * @param array  $urls     Collected URLs.
	 * @param array  $visited  Visited keys.
	 * @return void
	 */
	private static function collect_entry_css( $key, $manifest, &$urls, &$visited ) {
		if ( in_array( $key, $visited, true ) ) {
			return;
		}

		$visited[] = $key;
		$entry     = isset( $manifest[ $key ] ) ? $manifest[ $key ] : null;

		if ( ! $entry ) {
			return;
		}

		if ( ! empty( $entry['css'] ) && is_array( $entry['css'] ) ) {
			foreach ( $entry['css'] as $css ) {
				$urls[] = self::get_dist_base_url() . ltrim( $css, '/' );
			}
		}

		if ( ! empty( $entry['imports'] ) && is_array( $entry['imports'] ) ) {
			foreach ( $entry['imports'] as $import_key ) {
				self::collect_entry_css( $import_key, $manifest, $urls, $visited );
			}
		}
	}

	/**
	 * Script handles that must load as ES modules (Vite code-split frontend).
	 *
	 * @var array<string, bool>
	 */
	private static $module_handles = array();

	/**
	 * Register script_loader_tag filter once.
	 *
	 * @return void
	 */
	public static function init() {
		static $initialized = false;
		if ( $initialized ) {
			return;
		}
		$initialized = true;
		add_filter( 'script_loader_tag', array( __CLASS__, 'filter_script_loader_tag' ), 10, 3 );
	}

	/**
	 * Force type="module" on Vite frontend scripts.
	 *
	 * WordPress wp_script_add_data( ..., 'type', 'module' ) is not supported —
	 * only the script_loader_tag filter works.
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL.
	 * @return string
	 */
	public static function filter_script_loader_tag( $tag, $handle, $src ) {
		if ( empty( self::$module_handles[ $handle ] ) ) {
			return $tag;
		}

		if ( false !== strpos( $tag, ' type=' ) ) {
			return $tag;
		}

		return str_replace( '<script ', '<script type="module" ', $tag );
	}

	/**
	 * Enqueue a Vite frontend entry as an ES module.
	 *
	 * @param string $handle       Script handle.
	 * @param string $entry_key    Manifest entry key.
	 * @param string $fallback_path Legacy dist path.
	 * @param array  $deps         Script dependencies.
	 * @param bool   $in_footer    Load in footer.
	 * @return void
	 */
	public static function register_module_handle( $handle ) {
		self::init();
		self::$module_handles[ $handle ] = true;
	}

	/**
	 * Enqueue a Vite frontend entry as an ES module.
	 *
	 * @param string $handle        Script handle.
	 * @param string $entry_key     Manifest entry key.
	 * @param string $fallback_path Legacy dist path.
	 * @param array  $deps          Script dependencies.
	 * @param bool   $in_footer     Load in footer.
	 * @return void
	 */
	public static function enqueue_module_script( $handle, $entry_key, $fallback_path, $deps = array(), $in_footer = true ) {
		self::register_module_handle( $handle );

		$version = self::get_entry_version( $entry_key, $fallback_path );

		wp_enqueue_script(
			$handle,
			self::get_entry_file_url( $entry_key, $fallback_path ),
			$deps,
			$version,
			$in_footer
		);
	}

	/**
	 * Localize SLR_CONFIG for a script handle (used with Vite dev entries).
	 *
	 * @param string $handle Script handle.
	 * @return void
	 */
	public static function enqueue_frontend_config( $handle = 'slr-config' ) {
		wp_register_script( $handle, false, array(), SLR_VERSION, true );
		wp_enqueue_script( $handle );
		wp_localize_script( $handle, 'SLR_CONFIG', \SLR\Settings::get_public_config() );
	}

	/**
	 * Enqueue a Vite dev-server entry (HMR) as an ES module.
	 *
	 * @param string $handle   Script handle (also used for wp_localize_script).
	 * @param string $src_path Source path relative to plugin root, e.g. src/admin/main.tsx.
	 * @return void
	 */
	public static function enqueue_vite_dev_entry( $handle, $src_path ) {
		if ( ! self::is_dev_mode() ) {
			return;
		}

		$dev_url = self::get_dev_server_url();
		if ( '' === $dev_url ) {
			return;
		}

		self::$vite_dev_entries[ $handle ] = ltrim( $src_path, '/' );
		self::init_vite_dev_script_printer();

		// Handle exists for wp_localize_script; actual module loads via print_vite_dev_scripts().
		wp_register_script( $handle, false, array(), SLR_VERSION, true );
		wp_enqueue_script( $handle );
	}

	/**
	 * Register footer hook once to print Vite dev module scripts.
	 *
	 * @return void
	 */
	private static function init_vite_dev_script_printer() {
		static $initialized = false;
		if ( $initialized ) {
			return;
		}
		$initialized = true;

		// After wp_print_footer_scripts (priority 20) so localized data is available first.
		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'print_vite_dev_scripts' ), 21 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'print_vite_dev_scripts' ), 21 );
	}

	/**
	 * Print React refresh preamble + Vite client + entry in one module script.
	 *
	 * Sequential dynamic imports avoid race conditions between separate module tags.
	 *
	 * @return void
	 */
	public static function print_vite_dev_scripts() {
		if ( empty( self::$vite_dev_entries ) || ! self::is_dev_mode() ) {
			return;
		}

		$dev_url = self::get_dev_server_url();
		if ( '' === $dev_url ) {
			return;
		}

		$refresh_url = $dev_url . '/@react-refresh';
		$client_url  = $dev_url . '/@vite/client';

		foreach ( self::$vite_dev_entries as $handle => $src_path ) {
			$entry_url = $dev_url . '/' . $src_path;
			$script_id = $handle . '-vite';
			?>
<script type="module" id="<?php echo esc_attr( $script_id ); ?>">
import RefreshRuntime from <?php echo wp_json_encode( $refresh_url ); ?>;
RefreshRuntime.injectIntoGlobalHook(window);
window.$RefreshReg$ = () => {};
window.$RefreshSig$ = () => (type) => type;
window.__vite_plugin_react_preamble_installed__ = true;
await import(<?php echo wp_json_encode( $client_url ); ?>);
await import(<?php echo wp_json_encode( $entry_url ); ?>);
</script>
			<?php
		}
	}

	/**
	 * Enqueue a classic (IIFE) script — used for admin bundle.
	 *
	 * @param string $handle       Script handle.
	 * @param string $relative_path Path relative to assets/dist/.
	 * @param array  $deps         Dependencies.
	 * @param bool   $in_footer    Load in footer.
	 * @return void
	 */
	public static function enqueue_classic_script( $handle, $relative_path, $deps = array(), $in_footer = true ) {
		$path = SLR_PLUGIN_DIR . 'assets/dist/' . ltrim( $relative_path, '/' );
		$version = file_exists( $path ) ? (string) filemtime( $path ) : SLR_VERSION;

		wp_enqueue_script(
			$handle,
			self::get_asset_url( $relative_path ),
			$deps,
			$version,
			$in_footer
		);
	}

	/**
	 * Enqueue CSS files listed in manifest for an entry.
	 *
	 * @param string $handle_prefix Style handle prefix.
	 * @param string $entry_key     Manifest entry key.
	 * @param string $fallback_css  Legacy CSS path.
	 * @return void
	 */
	public static function enqueue_entry_styles( $handle_prefix, $entry_key, $fallback_css = '' ) {
		$css_files = self::get_entry_css_files( $entry_key );
		$version   = self::get_entry_version( $entry_key, '' );

		if ( empty( $css_files ) && $fallback_css ) {
			wp_enqueue_style(
				$handle_prefix,
				self::get_asset_url( $fallback_css ),
				array(),
				$version
			);
			return;
		}

		foreach ( $css_files as $index => $url ) {
			wp_enqueue_style(
				$handle_prefix . ( $index ? '-' . $index : '' ),
				$url,
				array(),
				$version
			);
		}
	}

	/**
	 * Resolve a dist asset URL.
	 *
	 * @param string $path Relative path inside assets/dist/.
	 * @return string
	 */
	public static function get_asset_url( $path ) {
		$path = ltrim( $path, '/' );
		$full_path = SLR_PLUGIN_DIR . 'assets/dist/' . $path;

		// In dev mode, only serve built dist files that exist locally (e.g. fallback).
		// Live HMR entries use enqueue_vite_dev_entry() with /src/... paths instead.
		if ( self::is_dev_mode() && file_exists( $full_path ) ) {
			return SLR_PLUGIN_URL . 'assets/dist/' . $path;
		}

		if ( ! self::is_dev_mode() && file_exists( $full_path ) ) {
			return SLR_PLUGIN_URL . 'assets/dist/' . $path;
		}

		if ( ! self::is_dev_mode() ) {
			return SLR_PLUGIN_URL . 'assets/dist/' . $path;
		}

		return self::get_dist_base_url() . $path;
	}

	/**
	 * Get cache-busting version for an entry file.
	 *
	 * @param string $entry_key     Manifest key.
	 * @param string $fallback_path Legacy path.
	 * @return string
	 */
	public static function get_entry_version( $entry_key, $fallback_path ) {
		$entry = self::get_entry( $entry_key );

		if ( $entry && ! empty( $entry['file'] ) ) {
			$path = SLR_PLUGIN_DIR . 'assets/dist/' . ltrim( $entry['file'], '/' );
			if ( file_exists( $path ) ) {
				return (string) filemtime( $path );
			}
		}

		if ( $fallback_path ) {
			$path = SLR_PLUGIN_DIR . 'assets/dist/' . ltrim( $fallback_path, '/' );
			if ( file_exists( $path ) ) {
				return (string) filemtime( $path );
			}
		}

		return SLR_VERSION;
	}
}
