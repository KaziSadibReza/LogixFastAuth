<?php
/**
 * Password-reset email binding.
 *
 * @package LogixFastAuth
 */

use LogixFastAuth\Services\AuthService;
use PHPUnit\Framework\TestCase;

/**
 * Class PasswordResetBindingTest
 */
class PasswordResetBindingTest extends TestCase {

	public function test_matching_email_is_case_insensitive() {
		$this->assertTrue( AuthService::emails_match( 'Admin@Example.com', 'admin@example.com' ) );
	}

	public function test_different_domain_does_not_match_registered_email() {
		$this->assertFalse( AuthService::emails_match( 'admin@attacker.example', 'admin@example.com' ) );
	}

	public function test_local_part_alone_does_not_match() {
		$this->assertFalse( AuthService::emails_match( 'admin', 'admin@example.com' ) );
	}

	public function test_empty_address_does_not_match() {
		$this->assertFalse( AuthService::emails_match( '', 'admin@example.com' ) );
		$this->assertFalse( AuthService::emails_match( 'admin@example.com', '' ) );
	}

	public function test_email_lookup_does_not_fall_back_to_username() {
		$source = file_get_contents( LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/Services/AuthService.php' );
		$method = $this->extract_method( $source, 'resolve_user_by_email' );

		$this->assertStringNotContainsString( "get_user_by( 'login'", $method );
		$this->assertStringNotContainsString( 'local_part', $method );
	}

	public function test_reset_and_login_delivery_use_registered_email() {
		$auth = file_get_contents( LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/Api/AuthController.php' );
		$otp  = file_get_contents( LOGIXFAST_AUTH_PLUGIN_DIR . 'includes/Api/OtpController.php' );

		$this->assertStringContainsString( 'emails_match', $this->extract_method( $auth, 'forgot_password' ) );
		$this->assertStringContainsString( 'emails_match', $this->extract_method( $auth, 'login' ) );
		$this->assertStringContainsString( 'bind_email_delivery', $this->extract_method( $otp, 'send' ) );
		$this->assertStringContainsString( 'bind_email_delivery', $this->extract_method( $otp, 'verify' ) );
	}

	/**
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
