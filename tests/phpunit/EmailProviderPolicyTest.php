<?php
/**
 * Isolated EmailProviderPolicy tests.
 *
 * @package LogixFastAuth
 */

use LogixFastAuth\Services\EmailProviderPolicy;
use LogixFastAuth\Settings;
use PHPUnit\Framework\TestCase;

/**
 * Class EmailProviderPolicyTest
 */
class EmailProviderPolicyTest extends TestCase {

	/**
	 * @param array<string, mixed> $auth Auth overrides.
	 * @return EmailProviderPolicy
	 */
	private function policy( array $auth = array() ) {
		return new EmailProviderPolicy( $auth );
	}

	public function test_restriction_disabled_allows_any_provider() {
		$policy = $this->policy(
			array(
				'email_provider_restriction_enabled' => false,
				'email_allowed_providers'            => array( 'gmail' ),
			)
		);

		$this->assertFalse( $policy->is_enabled() );
		$this->assertTrue( $policy->is_allowed( 'test@yahoo.com' ) );
		$this->assertTrue( $policy->validate( 'test@yahoo.com' ) );
	}

	public function test_gmail_preset_allows_gmail() {
		$policy = $this->policy(
			array(
				'email_provider_restriction_enabled' => true,
				'email_allowed_providers'            => array( 'gmail' ),
			)
		);

		$this->assertTrue( $policy->is_allowed( 'test@gmail.com' ) );
		$this->assertTrue( $policy->is_allowed( 'test@googlemail.com' ) );
		$this->assertTrue( $policy->validate( 'test@gmail.com' ) );
	}

	public function test_yahoo_rejected_when_only_gmail_allowed() {
		$policy = $this->policy(
			array(
				'email_provider_restriction_enabled' => true,
				'email_allowed_providers'            => array( 'gmail' ),
			)
		);

		$this->assertFalse( $policy->is_allowed( 'test@yahoo.com' ) );
		$result = $policy->validate( 'test@yahoo.com' );
		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'logixfast_auth_email_provider_not_allowed', $result->get_error_code() );
		$this->assertSame( 'This email provider is not allowed. Please use an approved email address.', $result->get_error_message() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	public function test_custom_domain_allowed() {
		$policy = $this->policy(
			array(
				'email_provider_restriction_enabled' => true,
				'email_allowed_providers'            => array(),
				'email_allowed_custom_domains'       => array( 'company.com' ),
			)
		);

		$this->assertTrue( $policy->is_allowed( 'test@company.com' ) );
		$this->assertFalse( $policy->is_allowed( 'test@mail.company.com' ) );
	}

	public function test_matching_is_case_insensitive() {
		$policy = $this->policy(
			array(
				'email_provider_restriction_enabled' => true,
				'email_allowed_providers'            => array( 'gmail' ),
			)
		);

		$this->assertTrue( $policy->is_allowed( 'test@Gmail.COM' ) );
	}

	public function test_whitespace_normalization() {
		$policy = $this->policy(
			array(
				'email_provider_restriction_enabled' => true,
				'email_allowed_custom_domains'       => array( '  COMPANY.COM  ' ),
			)
		);

		$this->assertTrue( $policy->is_allowed( '  person@company.com  ' ) );
		$this->assertSame( array( 'company.com' ), $policy->get_allowed_domains() );
	}

	public function test_optional_leading_at_normalization() {
		$policy = $this->policy(
			array(
				'email_provider_restriction_enabled' => true,
				'email_allowed_custom_domains'       => array( '@company.com' ),
			)
		);

		$this->assertSame( array( 'company.com' ), $policy->get_allowed_domains() );
		$this->assertTrue( $policy->is_allowed( 'person@company.com' ) );
	}

	public function test_exact_match_rejects_fakegmail() {
		$policy = $this->policy(
			array(
				'email_provider_restriction_enabled' => true,
				'email_allowed_providers'            => array( 'gmail' ),
			)
		);

		$this->assertFalse( $policy->is_allowed( 'test@fakegmail.com' ) );
	}

	public function test_exact_match_rejects_mail_subdomain() {
		$policy = $this->policy(
			array(
				'email_provider_restriction_enabled' => true,
				'email_allowed_providers'            => array( 'gmail' ),
			)
		);

		$this->assertFalse( $policy->is_allowed( 'person@mail.gmail.com' ) );
	}

	public function test_invalid_email_does_not_generate_provider_error() {
		$policy = $this->policy(
			array(
				'email_provider_restriction_enabled' => true,
				'email_allowed_providers'            => array( 'gmail' ),
			)
		);

		$this->assertTrue( $policy->is_allowed( 'not-an-email' ) );
		$this->assertTrue( $policy->validate( 'not-an-email' ) );
		$this->assertTrue( $policy->validate( '' ) );
	}

	public function test_enabled_empty_allowlist_blocks() {
		$policy = $this->policy(
			array(
				'email_provider_restriction_enabled' => true,
				'email_allowed_providers'            => array(),
				'email_allowed_custom_domains'       => array(),
			)
		);

		$this->assertSame( array(), $policy->get_allowed_domains() );
		$this->assertFalse( $policy->is_allowed( 'test@gmail.com' ) );
		$this->assertTrue( is_wp_error( $policy->validate( 'test@gmail.com' ) ) );
	}

	public function test_duplicate_domains_are_deduplicated() {
		$policy = $this->policy(
			array(
				'email_provider_restriction_enabled' => true,
				'email_allowed_providers'            => array( 'gmail', 'gmail' ),
				'email_allowed_custom_domains'       => array( 'gmail.com', 'GMAIL.COM', '@gmail.com' ),
			)
		);

		$this->assertSame( array( 'gmail.com', 'googlemail.com' ), $policy->get_allowed_domains() );
	}

	public function test_invalid_configured_domains_are_removed() {
		$sanitized = EmailProviderPolicy::sanitize_custom_domains(
			array(
				'company.com',
				'https://company.com',
				'company.com/path',
				'company.com:8080',
				'user@company.com',
				'*.company.com',
				'not a domain',
				'',
				'---',
			)
		);

		$this->assertSame( array( 'company.com' ), $sanitized );
	}

	public function test_settings_sanitization_rejects_url_path_port_wildcard() {
		$section = Settings::sanitize_auth_section(
			array(
				'email_provider_restriction_enabled' => '1',
				'email_allowed_providers'            => array( 'gmail', 'unknown', 'YAHOO' ),
				'email_allowed_custom_domains'       => "company.com\nhttps://evil.com\ncompany.com/path\ncompany.com:8080\n*.company.com\nuniversity.edu.",
			)
		);

		$this->assertTrue( $section['email_provider_restriction_enabled'] );
		$this->assertSame( array( 'gmail', 'yahoo' ), $section['email_allowed_providers'] );
		$this->assertSame( array( 'company.com', 'university.edu' ), $section['email_allowed_custom_domains'] );
	}

	public function test_subdomain_can_be_allowed_independently() {
		$policy = $this->policy(
			array(
				'email_provider_restriction_enabled' => true,
				'email_allowed_custom_domains'       => array( 'mail.company.com' ),
			)
		);

		$this->assertTrue( $policy->is_allowed( 'person@mail.company.com' ) );
		$this->assertFalse( $policy->is_allowed( 'person@company.com' ) );
	}
}
