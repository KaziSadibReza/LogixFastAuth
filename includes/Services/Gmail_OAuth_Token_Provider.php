<?php
/**
 * PHPMailer OAuth2 token provider for Gmail SMTP.
 *
 * @package SLR
 */

namespace SLR\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use PHPMailer\PHPMailer\OAuthTokenProvider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( 'PHPMailer\PHPMailer\OAuthTokenProvider' ) ) {
	require_once ABSPATH . WPINC . '/PHPMailer/OAuthTokenProvider.php';
}

/**
 * Supplies a base64-encoded XOAUTH2 string for Gmail SMTP.
 */
class Gmail_OAuth_Token_Provider implements OAuthTokenProvider {

	/**
	 * Gmail account email.
	 *
	 * @var string
	 */
	private $email;

	/**
	 * OAuth access token.
	 *
	 * @var string
	 */
	private $access_token;

	/**
	 * Constructor.
	 *
	 * @param string $email        Gmail address.
	 * @param string $access_token OAuth access token.
	 */
	public function __construct( $email, $access_token ) {
		$this->email         = (string) $email;
		$this->access_token  = (string) $access_token;
	}

	/**
	 * Generate base64-encoded OAuth token for SMTP AUTH XOAUTH2.
	 *
	 * @return string
	 */
	public function getOauth64() {
		return base64_encode( 'user=' . $this->email . "\001auth=Bearer " . $this->access_token . "\001\001" );
	}
}
