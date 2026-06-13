<?php
/**
 * Frontend asset registration and conditional loading.
 *
 * @package SLR
 */

namespace SLR\Frontend;

use SLR\Assets;
use SLR\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Frontend_Assets
 */
class Frontend_Assets {

	/**
	 * Whether main bundle is enqueued.
	 *
	 * @var bool
	 */
	public static $enqueued = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		Assets::init();
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_page_assets' ), 20 );
	}

	/**
	 * Register scripts and styles (not enqueue globally).
	 *
	 * @return void
	 */
	public function register_assets() {
		if ( is_admin() ) {
			return;
		}

		wp_register_style(
			'slr-font-urbanist',
			'https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap',
			array(),
			null
		);

		if ( Assets::is_dev_mode() ) {
			$this->register_dev_assets();
			return;
		}

		$version = Assets::get_entry_version( Assets::ENTRY_BOOTSTRAP, 'frontend/bootstrap.js' );

		$frontend_css = Assets::get_entry_css_files( Assets::ENTRY_POPUP );
		if ( empty( $frontend_css ) ) {
			$frontend_css = array( Assets::get_asset_url( 'frontend/main.css' ) );
		}

		foreach ( $frontend_css as $index => $css_url ) {
			$handle = $index ? 'slr-frontend-' . $index : 'slr-frontend';
			wp_register_style(
				$handle,
				$css_url,
				array(),
				Assets::get_entry_version( Assets::ENTRY_POPUP, 'frontend/main2.css' )
			);
		}

		wp_register_script(
			'slr-bootstrap',
			Assets::get_entry_file_url( Assets::ENTRY_BOOTSTRAP, 'frontend/bootstrap.js' ),
			array(),
			$version,
			true
		);
		Assets::register_module_handle( 'slr-bootstrap' );

		wp_register_script(
			'slr-popup',
			Assets::get_entry_file_url( Assets::ENTRY_POPUP, 'frontend/popup.js' ),
			array(),
			Assets::get_entry_version( Assets::ENTRY_POPUP, 'frontend/popup.js' ),
			true
		);
		Assets::register_module_handle( 'slr-popup' );

		wp_register_script(
			'slr-page',
			Assets::get_entry_file_url( Assets::ENTRY_PAGE, 'frontend/page.js' ),
			array(),
			Assets::get_entry_version( Assets::ENTRY_PAGE, 'frontend/page.js' ),
			true
		);
		Assets::register_module_handle( 'slr-page' );

		wp_localize_script( 'slr-bootstrap', 'SLR_CONFIG', Settings::get_public_config() );
	}

	/**
	 * Register minimal handles for Vite dev mode (HMR + React preamble).
	 *
	 * @return void
	 */
	private function register_dev_assets() {
		wp_register_style( 'slr-frontend', false, array(), null );
		Assets::enqueue_frontend_config( 'slr-config' );
	}

	/**
	 * Enqueue assets on dedicated page.
	 *
	 * @return void
	 */
	public function maybe_enqueue_page_assets() {
		if ( ! Settings::is_dedicated_page() ) {
			return;
		}

		self::enqueue_all( 'page' );
	}

	/**
	 * Enqueue all frontend assets.
	 *
	 * @param string $mode popup|page.
	 * @return void
	 */
	public static function enqueue_all( $mode = 'popup' ) {
		if ( self::$enqueued ) {
			return;
		}

		self::$enqueued = true;
		self::enqueue_font();

		if ( Assets::is_dev_mode() ) {
			self::enqueue_dev_bundle( $mode );
			return;
		}

		$frontend_css = Assets::get_entry_css_files( Assets::ENTRY_POPUP );
		if ( empty( $frontend_css ) ) {
			wp_enqueue_style( 'slr-frontend' );
		} else {
			foreach ( $frontend_css as $index => $css_url ) {
				$handle = $index ? 'slr-frontend-' . $index : 'slr-frontend';
				if ( ! wp_style_is( $handle, 'registered' ) ) {
					wp_register_style( $handle, $css_url, array(), Assets::get_entry_version( Assets::ENTRY_POPUP, 'frontend/main2.css' ) );
				}
				wp_enqueue_style( $handle );
			}
		}

		wp_enqueue_script( 'slr-bootstrap' );

		if ( 'page' === $mode ) {
			wp_enqueue_script( 'slr-page' );
		} else {
			wp_enqueue_script( 'slr-popup' );
		}

		self::print_style_vars();
	}

	/**
	 * Enqueue Vite dev entry with React Fast Refresh preamble.
	 *
	 * @param string $mode popup|page.
	 * @return void
	 */
	private static function enqueue_dev_bundle( $mode ) {
		wp_enqueue_style( 'slr-frontend' );
		self::print_style_vars();

		if ( ! wp_script_is( 'slr-config', 'enqueued' ) ) {
			Assets::enqueue_frontend_config( 'slr-config' );
		}

		if ( 'page' === $mode ) {
			Assets::enqueue_vite_dev_entry( 'slr-page', 'src/frontend/main-page.tsx' );
			return;
		}

		Assets::enqueue_vite_dev_entry( 'slr-popup', 'src/frontend/main-popup.tsx' );
	}

	/**
	 * Enqueue Urbanist font for frontend forms.
	 *
	 * @return void
	 */
	public static function enqueue_font() {
		wp_enqueue_style( 'slr-font-urbanist' );
	}

	/**
	 * Print CSS custom properties.
	 *
	 * @return void
	 */
	public static function print_style_vars() {
		$appearance = Settings::get( 'appearance' );
		$primary    = $appearance['primary'] ?? '#d6336c';
		$rgb        = self::hex_to_rgb( $primary );

		$primary_dark = self::darken_hex( $primary, 0.18 );
		$primary_50   = self::mix_with_white( $primary, 0.92 );
		$primary_100  = self::mix_with_white( $primary, 0.85 );
		$shadow_focus = sprintf( '0 0 0 3px rgba(%d, %d, %d, 0.18)', $rgb[0], $rgb[1], $rgb[2] );
		$shadow_prim  = sprintf( '0 8px 20px rgba(%d, %d, %d, 0.32)', $rgb[0], $rgb[1], $rgb[2] );

		$vars = array(
			'--slr-primary'         => $primary,
			'--slr-primary-dark'    => $primary_dark,
			'--slr-primary-50'      => $primary_50,
			'--slr-primary-100'     => $primary_100,
			'--slr-background'      => $appearance['background'] ?? '#ffffff',
			'--slr-text'            => $appearance['text'] ?? '#111827',
			'--slr-blur'            => $appearance['blur'] ?? '24px',
			'--slr-radius'          => $appearance['radius'] ?? '12px',
			'--slr-spacing'         => $appearance['spacing'] ?? '1rem',
			'--slr-shadow-focus'    => $shadow_focus,
			'--slr-shadow-primary'  => $shadow_prim,
		);

		$css = ':root{';
		foreach ( $vars as $key => $value ) {
			$css .= esc_attr( $key ) . ':' . esc_attr( $value ) . ';';
		}
		$css .= '}';

		wp_add_inline_style( 'slr-frontend', $css );
	}

	/**
	 * Convert a hex color to an [r, g, b] tuple.
	 *
	 * @param string $hex Hex color.
	 * @return array{0:int,1:int,2:int}
	 */
	private static function hex_to_rgb( $hex ) {
		$hex = ltrim( (string) $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( ! preg_match( '/^[0-9a-f]{6}$/i', $hex ) ) {
			return array( 214, 51, 108 );
		}
		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		);
	}

	/**
	 * Convert an [r, g, b] tuple to a hex color.
	 *
	 * @param array $rgb [r, g, b] tuple.
	 * @return string
	 */
	private static function rgb_to_hex( $rgb ) {
		return sprintf( '#%02x%02x%02x', max( 0, min( 255, (int) $rgb[0] ) ), max( 0, min( 255, (int) $rgb[1] ) ), max( 0, min( 255, (int) $rgb[2] ) ) );
	}

	/**
	 * Darken a hex color by amount (0–1).
	 *
	 * @param string $hex    Hex color.
	 * @param float  $amount Darken amount.
	 * @return string
	 */
	private static function darken_hex( $hex, $amount ) {
		$rgb = self::hex_to_rgb( $hex );
		$out = array_map(
			static function ( $v ) use ( $amount ) {
				return (int) round( $v * ( 1 - $amount ) );
			},
			$rgb
		);
		return self::rgb_to_hex( $out );
	}

	/**
	 * Lighten a hex color by mixing it with white (amount 0–1).
	 *
	 * @param string $hex    Hex color.
	 * @param float  $amount White mix amount.
	 * @return string
	 */
	private static function mix_with_white( $hex, $amount ) {
		$rgb = self::hex_to_rgb( $hex );
		$out = array_map(
			static function ( $v ) use ( $amount ) {
				return (int) round( $v + ( 255 - $v ) * $amount );
			},
			$rgb
		);
		return self::rgb_to_hex( $out );
	}
}
