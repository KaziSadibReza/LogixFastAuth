<?php
/**
 * Isolated PHPUnit bootstrap (no WordPress test suite).
 *
 * @package LogixFastAuth
 */

define( 'ABSPATH', __DIR__ . '/../' );
define( 'LOGIXFAST_AUTH_PLUGIN_DIR', dirname( __DIR__ ) . '/' );

$GLOBALS['logixfast_auth_test_settings'] = array();
$GLOBALS['logixfast_auth_test_options']  = array();

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error stand-in for isolated tests.
	 */
	class WP_Error {
		/**
		 * @var string
		 */
		public $code;

		/**
		 * @var string
		 */
		public $message;

		/**
		 * @var mixed
		 */
		public $data;

		/**
		 * @param string $code    Error code.
		 * @param string $message Message.
		 * @param mixed  $data    Data.
		 */
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		/**
		 * @return string
		 */
		public function get_error_code() {
			return $this->code;
		}

		/**
		 * @return string
		 */
		public function get_error_message() {
			return $this->message;
		}

		/**
		 * @return mixed
		 */
		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * @param mixed $thing Value.
	 * @return bool
	 */
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function __( $text, $domain = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $text;
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	/**
	 * @param string $email Email.
	 * @return string
	 */
	function sanitize_email( $email ) {
		return strtolower( trim( (string) $email ) );
	}
}

if ( ! function_exists( 'is_email' ) ) {
	/**
	 * @param string $email Email.
	 * @return bool
	 */
	function is_email( $email ) {
		return (bool) preg_match( '/^[^\s@]+@[^\s@]+\.[^\s@]+$/', (string) $email );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function sanitize_text_field( $text ) {
		return trim( wp_strip_all_tags( (string) $text ) );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function wp_strip_all_tags( $text ) {
		return trim( strip_tags( (string) $text ) );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * @param string $key Key.
	 * @return string
	 */
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
	}
}

if ( ! function_exists( 'sanitize_user' ) ) {
	/**
	 * @param string $username Username.
	 * @return string
	 */
	function sanitize_user( $username, $strict = false ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return preg_replace( '/[^A-Za-z0-9._\-]/', '', (string) $username );
	}
}

if ( ! function_exists( 'validate_username' ) ) {
	/**
	 * @param string $username Username.
	 * @return bool
	 */
	function validate_username( $username ) {
		return '' !== $username;
	}
}

if ( ! function_exists( 'email_exists' ) ) {
	/**
	 * @return false
	 */
	function email_exists( $email ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return false;
	}
}

if ( ! function_exists( 'username_exists' ) ) {
	/**
	 * @return false
	 */
	function username_exists( $username ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return false;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * @param string $key     Option key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	function get_option( $key, $default = false ) {
		if ( 'logixfast_auth_settings' === $key ) {
			return $GLOBALS['logixfast_auth_test_settings'] ?? array();
		}
		if ( isset( $GLOBALS['logixfast_auth_test_options'][ $key ] ) ) {
			return $GLOBALS['logixfast_auth_test_options'][ $key ];
		}
		if ( 'admin_email' === $key ) {
			return 'admin@example.com';
		}
		return $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * @param string $key   Option key.
	 * @param mixed  $value Value.
	 * @return bool
	 */
	function update_option( $key, $value, $autoload = true ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$GLOBALS['logixfast_auth_test_options'][ $key ] = $value;
		if ( 'logixfast_auth_settings' === $key ) {
			$GLOBALS['logixfast_auth_test_settings'] = $value;
		}
		return true;
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	/**
	 * @return string
	 */
	function get_bloginfo( $show = '' ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return 'Test Site';
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * @param string $hook  Hook.
	 * @param mixed  $value Value.
	 * @return mixed
	 */
	function apply_filters( $hook, $value ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $value;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * @param mixed $value Value.
	 * @return mixed
	 */
	function wp_unslash( $value ) {
		return $value;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	/**
	 * @return false
	 */
	function get_transient( $key ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	/**
	 * @return bool
	 */
	function set_transient( $key, $value, $expiration = 0 ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	/**
	 * @return bool
	 */
	function delete_transient( $key ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return true;
	}
}

if ( ! function_exists( 'rest_ensure_response' ) ) {
	/**
	 * @param mixed $data Data.
	 * @return mixed
	 */
	function rest_ensure_response( $data ) {
		return $data;
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	/**
	 * Minimal REST request stand-in.
	 */
	class WP_REST_Request {
		/**
		 * @var array<string, mixed>
		 */
		private $params = array();

		/**
		 * @param string $key   Key.
		 * @param mixed  $value Value.
		 * @return void
		 */
		public function set_param( $key, $value ) {
			$this->params[ $key ] = $value;
		}

		/**
		 * @param string $key Key.
		 * @return mixed
		 */
		public function get_param( $key ) {
			return $this->params[ $key ] ?? null;
		}

		/**
		 * @return array<string, mixed>
		 */
		public function get_json_params() {
			return $this->params;
		}
	}
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
	/**
	 * Minimal REST server constants.
	 */
	class WP_REST_Server {
		const CREATABLE = 'POST';
		const READABLE  = 'GET';
	}
}

require_once LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/class-logixfast-auth-settings.php';
require_once LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/Services/EmailProviderPolicy.php';
require_once LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/Services/AuthService.php';
require_once LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/Services/RateLimiter.php';
require_once LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/Api/OtpController.php';
require_once LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/Api/AuthController.php';

/**
 * Replace stored auth settings for a test.
 *
 * @param array<string, mixed> $auth Auth overrides.
 * @return void
 */
function logixfast_auth_set_test_auth( array $auth ) {
	$stored = \LogixFastAuth\Settings::get_default_settings();
	$stored['auth'] = array_merge( $stored['auth'], $auth );
	$GLOBALS['logixfast_auth_test_settings'] = $stored;
}
