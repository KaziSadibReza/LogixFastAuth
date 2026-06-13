<?php
/**
 * Dedicated SLR login page creation and WordPress page labeling.
 *
 * @package SLR
 */

namespace SLR\Services;

use SLR\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LoginPageService
 */
class LoginPageService {

	const META_PAGE_TYPE = '_slr_page_type';
	const PAGE_TYPE_LOGIN  = 'login';

	/**
	 * Default dedicated page title (Sign In + Register tabs in one UI).
	 */
	const DEFAULT_PAGE_TITLE = 'Sign In & Register';

	/**
	 * Default dedicated page slug.
	 */
	const DEFAULT_PAGE_SLUG = 'sign-in';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'display_post_states', array( $this, 'add_post_state_label' ), 10, 2 );
	}

	/**
	 * Mark SLR login pages in the WordPress pages list (like WooCommerce).
	 *
	 * @param array    $post_states Post states.
	 * @param \WP_Post $post        Post object.
	 * @return array
	 */
	public function add_post_state_label( $post_states, $post ) {
		if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type ) {
			return $post_states;
		}

		if ( self::is_login_page( $post->ID ) ) {
			$post_states['slr_login_page'] = __( 'SLR Sign In Page', 'smart-login-registration' );
		}

		return $post_states;
	}

	/**
	 * Whether a page is the SLR dedicated login page.
	 *
	 * @param int $page_id Page ID.
	 * @return bool
	 */
	public static function is_login_page( $page_id ) {
		$page_id = (int) $page_id;
		if ( $page_id <= 0 ) {
			return false;
		}

		$general   = Settings::get( 'general' );
		$configured = (int) ( $general['dedicated_page_id'] ?? 0 );

		if ( $configured === $page_id ) {
			return true;
		}

		return self::PAGE_TYPE_LOGIN === get_post_meta( $page_id, self::META_PAGE_TYPE, true );
	}

	/**
	 * Ensure a dedicated login page exists and is linked in settings.
	 *
	 * @return array{id:int,title:string,url:string,created:bool}
	 */
	public static function ensure_dedicated_page() {
		$general     = Settings::get( 'general' );
		$existing_id = (int) ( $general['dedicated_page_id'] ?? 0 );

		if ( $existing_id > 0 && get_post( $existing_id ) instanceof \WP_Post ) {
			self::mark_login_page( $existing_id );
			self::maybe_upgrade_legacy_page_title( $existing_id );
			return self::format_page_response( $existing_id, false );
		}

		$by_meta = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'meta_key'       => self::META_PAGE_TYPE,
				'meta_value'     => self::PAGE_TYPE_LOGIN,
				'posts_per_page' => 1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		if ( ! empty( $by_meta[0] ) ) {
			$page_id = (int) $by_meta[0]->ID;
			if ( 'publish' !== $by_meta[0]->post_status ) {
				wp_update_post(
					array(
						'ID'          => $page_id,
						'post_status' => 'publish',
					)
				);
			}
			Settings::update( 'general', array( 'dedicated_page_id' => $page_id ) );
			self::mark_login_page( $page_id );
			return self::format_page_response( $page_id, false );
		}

		$page_id = wp_insert_post(
			array(
				'post_title'   => __( self::DEFAULT_PAGE_TITLE, 'smart-login-registration' ),
				'post_name'    => self::DEFAULT_PAGE_SLUG,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
				'post_author'  => get_current_user_id() ?: 1,
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			return array(
				'id'      => 0,
				'title'   => '',
				'url'     => '',
				'created' => false,
				'error'   => $page_id->get_error_message(),
			);
		}

		self::mark_login_page( (int) $page_id );
		Settings::update( 'general', array( 'dedicated_page_id' => (int) $page_id ) );

		return self::format_page_response( (int) $page_id, true );
	}

	/**
	 * Tag a page as the SLR login page.
	 *
	 * @param int $page_id Page ID.
	 * @return void
	 */
	public static function mark_login_page( $page_id ) {
		update_post_meta( (int) $page_id, self::META_PAGE_TYPE, self::PAGE_TYPE_LOGIN );
	}

	/**
	 * Rename legacy auto-created "Login" pages to reflect Sign In + Register UI.
	 *
	 * @param int $page_id Page ID.
	 * @return void
	 */
	private static function maybe_upgrade_legacy_page_title( $page_id ) {
		$post = get_post( $page_id );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$legacy_titles = array( 'Login', __( 'Login', 'smart-login-registration' ) );
		if ( ! in_array( $post->post_title, $legacy_titles, true ) ) {
			return;
		}

		wp_update_post(
			array(
				'ID'         => $page_id,
				'post_title' => __( self::DEFAULT_PAGE_TITLE, 'smart-login-registration' ),
			)
		);
	}

	/**
	 * Build REST response payload for a page.
	 *
	 * @param int  $page_id Page ID.
	 * @param bool $created Whether the page was newly created.
	 * @return array{id:int,title:string,url:string,created:bool}
	 */
	private static function format_page_response( $page_id, $created ) {
		$post = get_post( $page_id );

		return array(
			'id'      => (int) $page_id,
			'title'   => $post ? $post->post_title : '',
			'url'     => get_permalink( $page_id ) ?: '',
			'created' => (bool) $created,
		);
	}
}
