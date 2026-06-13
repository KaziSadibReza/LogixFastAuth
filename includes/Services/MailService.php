<?php
/**
 * Email transport for OTP and notifications.
 *
 * @package SLR
 */

namespace SLR\Services;

use PHPMailer\PHPMailer\PHPMailer;
use SLR\Settings;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MailService
 */
class MailService {

	/**
	 * Send OTP email.
	 *
	 * @param string $to      Recipient email.
	 * @param string $code    OTP code.
	 * @param string $purpose Purpose.
	 * @return true|WP_Error
	 */
	public function send_otp( $to, $code, $purpose = 'verify' ) {
		$security    = Settings::get( 'security' );
		$ttl_seconds = (int) ( $security['otp_ttl'] ?? 600 );
		$ttl_minutes = max( 1, (int) ceil( $ttl_seconds / 60 ) );

		$subject = sprintf(
			/* translators: %s: site name */
			__( '%s - Verification Code', 'smart-login-registration' ),
			get_bloginfo( 'name' )
		);

		$html_body  = EmailTemplate::otp_verification_html( $code, $purpose, $ttl_minutes );
		$plain_body = EmailTemplate::otp_verification_plain( $code, $purpose, $ttl_minutes );

		return $this->send(
			$to,
			$subject,
			$html_body,
			array(
				'content_type' => 'text/html',
				'alt_body'     => $plain_body,
			)
		);
	}

	/**
	 * Send email via configured transport.
	 *
	 * @param string $to      Recipient.
	 * @param string $subject Subject.
	 * @param string $body    Body.
	 * @param array  $args    Optional send args (content_type, alt_body).
	 * @return true|WP_Error
	 */
	public function send( $to, $subject, $body, $args = array() ) {
		$settings = Settings::get( 'mail' );
		$args     = wp_parse_args(
			$args,
			array(
				'content_type' => 'text/plain',
				'alt_body'     => '',
			)
		);

		if ( 'smtp' === $settings['transport'] ) {
			return $this->send_via_smtp( $to, $subject, $body, $settings, $args );
		}

		if ( 'google' === $settings['transport'] && ! empty( $settings['google_connected'] ) ) {
			return $this->send_via_google( $to, $subject, $body, $settings, $args );
		}

		$headers = array( 'Content-Type: ' . $args['content_type'] . '; charset=UTF-8' );
		if ( ! empty( $settings['from_email'] ) ) {
			$from_name = $settings['from_name'] ?? get_bloginfo( 'name' );
			$headers[] = 'From: ' . $from_name . ' <' . $settings['from_email'] . '>';
		}

		$sent = wp_mail( $to, $subject, $body, $headers );

		if ( ! $sent ) {
			return new WP_Error( 'slr_mail_failed', __( 'Failed to send email.', 'smart-login-registration' ), array( 'status' => 500 ) );
		}

		return true;
	}

	/**
	 * Send test email.
	 *
	 * @param string $to Test recipient.
	 * @return true|WP_Error
	 */
	public function send_test( $to ) {
		return $this->send(
			$to,
			__( 'SLR SMTP Test', 'smart-login-registration' ),
			__( 'This is a test email from Smart Login Registration.', 'smart-login-registration' )
		);
	}

	/**
	 * Send via custom SMTP.
	 *
	 * @param string $to       Recipient.
	 * @param string $subject  Subject.
	 * @param string $body     Body.
	 * @param array  $settings Mail settings.
	 * @param array  $args     Send args.
	 * @return true|WP_Error
	 */
	private function send_via_smtp( $to, $subject, $body, $settings, $args = array() ) {
		if ( ! class_exists( PHPMailer::class ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		}

		$mail = new PHPMailer( true );

		try {
			$mail->isSMTP();
			$mail->Host       = $settings['smtp_host'];
			$mail->Port       = (int) $settings['smtp_port'];
			$mail->SMTPAuth   = true;
			$mail->Username   = $settings['smtp_user'];
			$mail->Password   = $this->decrypt_secret( $settings['smtp_pass'] );
			$mail->SMTPSecure = $settings['smtp_encryption'] ?: 'tls';
			$mail->CharSet    = 'UTF-8';

			$mail->setFrom( $settings['from_email'], $settings['from_name'] ?? '' );
			$mail->addAddress( $to );
			$mail->Subject = $subject;

			if ( 'text/html' === ( $args['content_type'] ?? 'text/plain' ) ) {
				$mail->isHTML( true );
				$mail->Body    = $body;
				$mail->AltBody = $args['alt_body'] ?? wp_strip_all_tags( $body );
			} else {
				$mail->isHTML( false );
				$mail->Body = $body;
			}

			$mail->send();
			return true;
		} catch ( \Exception $e ) {
			return new WP_Error( 'slr_smtp_failed', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Send via Google OAuth SMTP (Gmail).
	 *
	 * @param string $to       Recipient.
	 * @param string $subject  Subject.
	 * @param string $body     Body.
	 * @param array  $settings Mail settings.
	 * @param array  $args     Send args.
	 * @return true|WP_Error
	 */
	private function send_via_google( $to, $subject, $body, $settings, $args = array() ) {
		$refresh_token = $this->decrypt_secret( $settings['google_refresh_token'] ?? '' );

		if ( empty( $refresh_token ) ) {
			return new WP_Error( 'slr_google_not_connected', __( 'Google SMTP is not connected.', 'smart-login-registration' ), array( 'status' => 503 ) );
		}

		$access_token = $this->get_google_access_token( $refresh_token );
		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		if ( ! class_exists( PHPMailer::class ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		}

		$mail = new PHPMailer( true );

		try {
			$mail->isSMTP();
			$mail->Host       = 'smtp.gmail.com';
			$mail->Port       = 587;
			$mail->SMTPSecure = 'tls';
			$mail->SMTPAuth   = true;
			$mail->AuthType   = 'XOAUTH2';
			$mail->CharSet    = 'UTF-8';

			$mail->Username = $settings['from_email'];
			$mail->Password = $access_token;

			$mail->setFrom( $settings['from_email'], $settings['from_name'] ?? '' );
			$mail->addAddress( $to );
			$mail->Subject = $subject;

			if ( 'text/html' === ( $args['content_type'] ?? 'text/plain' ) ) {
				$mail->isHTML( true );
				$mail->Body    = $body;
				$mail->AltBody = $args['alt_body'] ?? wp_strip_all_tags( $body );
			} else {
				$mail->isHTML( false );
				$mail->Body = $body;
			}

			$mail->send();
			return true;
		} catch ( \Exception $e ) {
			return new WP_Error( 'slr_google_smtp_failed', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Exchange refresh token for access token.
	 *
	 * @param string $refresh_token Refresh token.
	 * @return string|WP_Error
	 */
	private function get_google_access_token( $refresh_token ) {
		$oauth         = new GoogleOAuthService();
		$client_id     = $oauth->get_client_id();
		$client_secret = $oauth->get_client_secret();

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return new WP_Error( 'slr_google_not_configured', __( 'Google OAuth credentials not configured.', 'smart-login-registration' ), array( 'status' => 503 ) );
		}

		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'body' => array(
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'refresh_token' => $refresh_token,
					'grant_type'    => 'refresh_token',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $data['access_token'] ) ) {
			return new WP_Error( 'slr_google_token_failed', __( 'Failed to refresh Google token.', 'smart-login-registration' ), array( 'status' => 500 ) );
		}

		return $data['access_token'];
	}

	/**
	 * Encrypt secret for storage.
	 *
	 * @param string $value Plain value.
	 * @return string
	 */
	public static function encrypt_secret( $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}

		$key = hash( 'sha256', wp_salt( 'auth' ), true );
		$iv  = random_bytes( 12 );
		$tag = '';

		$ciphertext = openssl_encrypt( (string) $value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
		if ( false === $ciphertext || '' === $tag ) {
			return '';
		}

		$payload = wp_json_encode(
			array(
				'iv'  => base64_encode( $iv ),
				'tag' => base64_encode( $tag ),
				'ct'  => base64_encode( $ciphertext ),
			)
		);

		return $payload ? 'v2:' . base64_encode( $payload ) : '';
	}

	/**
	 * Decrypt stored secret.
	 *
	 * @param string $value Encrypted value.
	 * @return string
	 */
	public function decrypt_secret( $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		if ( str_starts_with( $value, 'v2:' ) && function_exists( 'openssl_decrypt' ) ) {
			$payload = json_decode( base64_decode( substr( $value, 3 ) ), true );
			if ( is_array( $payload ) && ! empty( $payload['iv'] ) && ! empty( $payload['tag'] ) && ! empty( $payload['ct'] ) ) {
				$key       = hash( 'sha256', wp_salt( 'auth' ), true );
				$iv        = base64_decode( $payload['iv'], true );
				$tag       = base64_decode( $payload['tag'], true );
				$ciphertext = base64_decode( $payload['ct'], true );

				if ( false !== $iv && false !== $tag && false !== $ciphertext ) {
					$decrypted = openssl_decrypt( $ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
					return false !== $decrypted ? $decrypted : '';
				}
			}

			return '';
		}

		$key = wp_salt( 'auth' );
		$decrypted = openssl_decrypt( base64_decode( $value ), 'AES-256-CBC', substr( hash( 'sha256', $key ), 0, 32 ), 0, substr( hash( 'sha256', $key . 'iv' ), 0, 16 ) );
		return $decrypted ?: $value;
	}
}
