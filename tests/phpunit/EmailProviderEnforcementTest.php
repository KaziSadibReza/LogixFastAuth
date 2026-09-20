<?php
/**
 * Isolated caller tests for email provider restriction.
 *
 * @package LogixFastAuth
 */

use LogixFastAuth\Api\AuthController;
use LogixFastAuth\Api\OtpController;
use LogixFastAuth\Services\AuthService;
use PHPUnit\Framework\TestCase;

if ( ! class_exists( '\\LogixFastAuth\\Services\\PendingRegistrationService', false ) ) {
	/**
	 * Test double used only for pending-user creation checks.
	 */
	class LogixFastAuthPendingRegistrationServiceDouble {
		/**
		 * @var array<string, mixed>|WP_Error
		 */
		public static $payload = array();

		/**
		 * @param string $token      Token.
		 * @param string $identifier Identifier.
		 * @param string $channel    Channel.
		 * @return array<string, mixed>|WP_Error
		 */
		public function consume( $token, $identifier, $channel = 'email' ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			return self::$payload;
		}
	}

	class_alias( LogixFastAuthPendingRegistrationServiceDouble::class, 'LogixFastAuth\\Services\\PendingRegistrationService' );
}

/**
 * Class EmailProviderEnforcementTest
 */
class EmailProviderEnforcementTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		logixfast_auth_set_test_auth(
			array(
				'require_phone'                      => false,
				'otp_login_enabled'                  => true,
				'email_provider_restriction_enabled' => true,
				'email_allowed_providers'            => array( 'gmail' ),
				'email_allowed_custom_domains'       => array(),
			)
		);
	}

	/**
	 * @param array<string, string> $overrides Registration fields.
	 * @return array|WP_Error
	 */
	private function register( array $overrides = array() ) {
		$data = array_merge(
			array(
				'full_name' => 'Test User',
				'email'     => 'test@gmail.com',
				'password'  => 'password123',
			),
			$overrides
		);

		return ( new AuthService() )->validate_registration( $data );
	}

	/**
	 * @param string $identifier Identifier.
	 * @param string $channel    Channel.
	 * @param string $purpose    Purpose.
	 * @return true|WP_Error
	 */
	private function otp_context( $identifier, $channel, $purpose ) {
		$controller = new OtpController();
		$method     = new ReflectionMethod( OtpController::class, 'validate_request_context' );

		return $method->invoke( $controller, $identifier, $channel, $purpose, 'pending-token' );
	}

	public function test_registration_calls_provider_validation() {
		$allowed = $this->register( array( 'email' => 'test@gmail.com' ) );
		$this->assertIsArray( $allowed );
		$this->assertSame( 'test@gmail.com', $allowed['email'] );

		$blocked = $this->register( array( 'email' => 'test@yahoo.com' ) );
		$this->assertTrue( is_wp_error( $blocked ) );
		$this->assertSame( 'logixfast_auth_email_provider_not_allowed', $blocked->get_error_code() );
	}

	public function test_invalid_registration_email_keeps_existing_error() {
		$invalid = $this->register( array( 'email' => 'not-an-email' ) );
		$this->assertTrue( is_wp_error( $invalid ) );
		$this->assertSame( 'logixfast_auth_invalid_email', $invalid->get_error_code() );
	}

	public function test_register_purpose_email_otp_calls_provider_validation() {
		$allowed = $this->otp_context( 'test@gmail.com', 'email', 'register' );
		$this->assertTrue( $allowed );

		$blocked = $this->otp_context( 'test@yahoo.com', 'email', 'register' );
		$this->assertTrue( is_wp_error( $blocked ) );
		$this->assertSame( 'logixfast_auth_email_provider_not_allowed', $blocked->get_error_code() );
	}

	public function test_login_purpose_email_otp_does_not_call_provider_validation() {
		$result = $this->otp_context( 'test@yahoo.com', 'email', 'login' );
		$this->assertTrue( $result );
	}

	public function test_reset_purpose_email_otp_does_not_call_provider_validation() {
		$result = $this->otp_context( 'test@yahoo.com', 'email', 'reset' );
		$this->assertTrue( $result );
	}

	public function test_pending_user_creation_rechecks_provider() {
		LogixFastAuthPendingRegistrationServiceDouble::$payload = array(
			'full_name' => 'Test User',
			'email'     => 'test@yahoo.com',
			'phone'     => '',
			'password'  => 'password123',
			'username'  => 'testuser',
		);

		$result = ( new AuthService() )->create_user_from_pending( 'token', 'test@yahoo.com', 'email' );
		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'logixfast_auth_email_provider_not_allowed', $result->get_error_code() );
	}

	public function test_username_suggestion_rejects_blocked_domains() {
		$request = new WP_REST_Request();
		$request->set_param( 'email', 'test@yahoo.com' );

		$result = ( new AuthController() )->suggest_username( $request );
		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'logixfast_auth_email_provider_not_allowed', $result->get_error_code() );
	}

	public function test_username_suggestion_allows_approved_domains() {
		$request = new WP_REST_Request();
		$request->set_param( 'email', 'test@gmail.com' );

		$result = ( new AuthController() )->suggest_username( $request );
		$this->assertIsArray( $result );
		$this->assertSame( 'test', $result['username'] );
	}

	public function test_existing_account_login_paths_do_not_use_provider_policy() {
		$auth_service    = file_get_contents( LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/Services/AuthService.php' );
		$auth_controller = file_get_contents( LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/Api/AuthController.php' );
		$otp_controller  = file_get_contents( LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/Api/OtpController.php' );
		$webauthn        = file_get_contents( LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/Api/WebAuthnController.php' );

		$this->assertSame( 2, substr_count( $auth_service, 'EmailProviderPolicy' ) );
		$this->assertStringContainsString( 'function validate_registration', $auth_service );
		$this->assertStringContainsString( 'function create_user_from_pending', $auth_service );
		$this->assertSame( 2, substr_count( $auth_controller, 'EmailProviderPolicy' ) );
		$this->assertStringContainsString( 'function suggest_username', $auth_controller );
		$this->assertStringNotContainsString( 'EmailProviderPolicy', $this->extract_method( $auth_controller, 'login' ) );
		$this->assertStringNotContainsString( 'EmailProviderPolicy', $this->extract_method( $auth_controller, 'forgot_password' ) );
		$this->assertStringNotContainsString( 'EmailProviderPolicy', $this->extract_method( $auth_service, 'login' ) );
		$this->assertStringNotContainsString( 'EmailProviderPolicy', $this->extract_method( $auth_service, 'resolve_user_for_login_otp' ) );
		$this->assertStringNotContainsString( 'EmailProviderPolicy', $this->extract_method( $auth_service, 'resolve_user_for_reset' ) );
		$this->assertStringNotContainsString( 'EmailProviderPolicy', $webauthn );
		$this->assertStringContainsString( "if ( 'email' === \$channel && 'register' === \$purpose )", $otp_controller );
	}

	/**
	 * Extract a PHP method body for source assertions.
	 *
	 * @param string $source File contents.
	 * @param string $method Method name.
	 * @return string
	 */
	private function extract_method( $source, $method ) {
		if ( ! preg_match( '/function\s+' . preg_quote( $method, '/' ) . '\s*\(/', $source, $match, PREG_OFFSET_CAPTURE ) ) {
			$this->fail( 'Method ' . $method . ' was not found.' );
		}

		$start = strpos( $source, '{', $match[0][1] );
		$this->assertNotFalse( $start );

		$depth  = 0;
		$length = strlen( $source );
		for ( $i = $start; $i < $length; $i++ ) {
			if ( '{' === $source[ $i ] ) {
				++$depth;
			} elseif ( '}' === $source[ $i ] ) {
				--$depth;
				if ( 0 === $depth ) {
					return substr( $source, $start, $i - $start + 1 );
				}
			}
		}

		$this->fail( 'Could not extract method ' . $method . '.' );
	}
}
