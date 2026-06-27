<?php
/**
 * Manages third-party login replacement integrations.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Integrations; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Integration_Manager
 */
class Integration_Manager {

	/**
	 * Whether Elementor integration has been bootstrapped.
	 *
	 * @var bool
	 */
	private $elementor_loaded = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		new WordPress_Login();

		add_action( 'plugins_loaded', array( $this, 'load_third_party_integrations' ), 20 );
		add_action( 'elementor/init', array( $this, 'load_elementor_integration' ) );

		if ( did_action( 'elementor/init' ) ) {
			$this->load_elementor_integration();
		}
	}

	/**
	 * Load WooCommerce and Tutor integrations after other plugins bootstrap.
	 *
	 * @return void
	 */
	public function load_third_party_integrations() {
		if ( Integration_Availability::is_woocommerce_available() ) {
			new WooCommerce_Login();
			new WooCommerce_Passkeys();
		}

		if ( Integration_Availability::is_tutor_available() ) {
			new Tutor_Login();
			new Tutor_Passkeys();
		}
	}

	/**
	 * Load Elementor integration after Elementor components are ready.
	 *
	 * @return void
	 */
	public function load_elementor_integration() {
		if ( $this->elementor_loaded || ! Integration_Availability::is_elementor_available() ) {
			return;
		}

		$this->elementor_loaded = true;
		new Elementor_Login();
	}
}
