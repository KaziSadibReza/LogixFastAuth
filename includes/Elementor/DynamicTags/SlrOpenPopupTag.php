<?php
/**
 * Elementor dynamic tag to open SLR popup.
 *
 * @package SLR
 */

namespace SLR\Elementor\DynamicTags; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use SLR\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SlrOpenPopupTag
 */
class SlrOpenPopupTag extends Data_Tag {

	/**
	 * Get tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'slr-open-popup';
	}

	/**
	 * Get tag title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'SLR Open Popup', 'smart-login-registration' );
	}

	/**
	 * Get tag group.
	 *
	 * @return string
	 */
	public function get_group() {
		return 'slr';
	}

	/**
	 * Get tag categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( TagsModule::URL_CATEGORY, TagsModule::TEXT_CATEGORY );
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'mode',
			array(
				'label'   => __( 'Mode', 'smart-login-registration' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'login',
				'options' => array(
					'login'    => __( 'Login', 'smart-login-registration' ),
					'register' => __( 'Register', 'smart-login-registration' ),
				),
			)
		);
	}

	/**
	 * Return URL hash consumed by SLR bootstrap click handler.
	 *
	 * @param array $options Render options.
	 * @return string
	 */
	public function get_value( array $options = array() ) {
		$integrations = Settings::get( 'integrations' );
		if ( ! Settings::to_bool( $integrations['replace_elementor'] ?? false ) ) {
			return '#';
		}

		$mode = $this->get_settings( 'mode' );

		if ( ! in_array( $mode, array( 'login', 'register' ), true ) ) {
			$mode = 'login';
		}

		return '#slr-open-' . $mode;
	}
}
