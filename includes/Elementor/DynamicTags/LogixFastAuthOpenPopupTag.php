<?php
/**
 * Elementor dynamic tag to open LogixFastAuth popup.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Elementor\DynamicTags; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use LogixFastAuth\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LogixFastAuthOpenPopupTag
 */
class LogixFastAuthOpenPopupTag extends Data_Tag {

	/**
	 * Get tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'logixfast-auth-open-popup';
	}

	/**
	 * Get tag title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'LogixFastAuth Open Popup', 'logixfast-auth' );
	}

	/**
	 * Get tag group.
	 *
	 * @return string
	 */
	public function get_group() {
		return 'logixfastauth';
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
				'label'   => __( 'Mode', 'logixfast-auth' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'login',
				'options' => array(
					'login'    => __( 'Login', 'logixfast-auth' ),
					'register' => __( 'Register', 'logixfast-auth' ),
				),
			)
		);
	}

	/**
	 * Return URL hash consumed by LogixFastAuth bootstrap click handler.
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

		return '#logixfast-auth-open-' . $mode;
	}
}
