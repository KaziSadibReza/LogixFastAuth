<?php
/**
 * Email transport for OTP, notifications, and site-wide wp_mail when configured.
 *
 * @package SLR
 */

namespace SLR\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

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
	 * Singleton for mail hooks.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Optional HTML alt body for the current SLR send() call.
	 *
	 * @var array<string, mixed>|null
	 */
	private static $mail_context = null;

	/**
	 * Last wp_mail failure in this request.
	 *
	 * @var \WP_Error|null
	 */
	private $last_mail_error = null;

	/**
	 * Register site-wide mail transport hooks.
	 *
	 * @return self
	 */
	public static function register() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the shared MailService instance.
	 *
	 * @return self
	 */
	public static function instance() {
		return self::register();
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'phpmailer_init', array( $this, 'configure_phpmailer' ) );
		add_action( 'wp_mail_failed', array( $this, 'capture_mail_failed' ), 10, 1 );
	}

	/**
	 * Whether SLR should route wp_mail through custom SMTP / Google.
	 *
	 * @param array|null $settings Optional mail settings.
	 * @return bool
	 */
	public static function uses_custom_transport( $settings = null ) {
		$settings  = is_array( $settings ) ? $settings : Settings::get( 'mail' );
		$transport = $settings['transport'] ?? 'wp_mail';

		if ( 'smtp' === $transport ) {
			return ! empty( $settings['smtp_host'] );
		}

		if ( 'google' === $transport ) {
			return ! empty( $settings['google_connected'] ) && ! empty( $settings['google_refresh_token'] );
		}

		return false;
	}

	/**
	 * Active third-party SMTP plugins that may conflict with SLR mail routing.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function get_smtp_plugin_conflicts() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$candidates = array(
			'wp-mail-smtp/wp_mail_smtp.php'           => 'WP Mail SMTP',
			'easy-wp-smtp/easy-wp-smtp.php'           => 'Easy WP SMTP',
			'post-smtp/postman-smtp.php'              => 'Post SMTP',
			'fluent-smtp/fluent-smtp.php'             => 'FluentSMTP',
			'wp-smtp/wp-smtp.php'                     => 'WP SMTP',
			'smtp-mailer/main.php'                    => 'SMTP Mailer',
			'gmail-smtp/main.php'                     => 'Gmail SMTP',
			'wp-email-smtp/wp-email-smtp.php'         => 'WP Email SMTP',
			'mailin/mailin.php'                       => 'Brevo (Sendinblue)',
			'sendgrid-email-delivery-simplified/wpsendgrid.php' => 'SendGrid',
		);

		$conflicts = array();

		foreach ( $candidates as $plugin_file => $label ) {
			if ( is_plugin_active( $plugin_file ) ) {
				$conflicts[] = array(
					'slug' => $plugin_file,
					'name' => $label,
				);
			}
		}

		return $conflicts;
	}

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

		if ( 'google' === ( $settings['transport'] ?? 'wp_mail' ) && empty( $settings['google_connected'] ) ) {
			return new WP_Error( 'slr_google_not_connected', __( 'Google SMTP is not connected.', 'smart-login-registration' ), array( 'status' => 503 ) );
		}

		$headers = array( 'Content-Type: ' . $args['content_type'] . '; charset=UTF-8' );

		if ( ! empty( $settings['from_email'] ) ) {
			$from_name = $settings['from_name'] ?? get_bloginfo( 'name' );
			$headers[] = 'From: ' . $from_name . ' <' . $settings['from_email'] . '>';
		}

		self::$mail_context  = $args;
		$this->last_mail_error = null;

		$sent = wp_mail( $to, $subject, $body, $headers );

		self::$mail_context = null;

		if ( ! $sent ) {
			$message = __( 'Failed to send email.', 'smart-login-registration' );
			if ( $this->last_mail_error instanceof \WP_Error ) {
				$message = $this->last_mail_error->get_error_message();
			}

			return new WP_Error( 'slr_mail_failed', $message, array( 'status' => 500 ) );
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
	 * Configure PHPMailer for all wp_mail() calls when SLR transport is active.
	 *
	 * @param PHPMailer $phpmailer Mailer instance.
	 * @return void
	 */
	public function configure_phpmailer( $phpmailer ) {
		$settings = Settings::get( 'mail' );
		$transport = $settings['transport'] ?? 'wp_mail';

		if ( 'smtp' === $transport ) {
			$this->apply_smtp_transport( $phpmailer, $settings );
		} elseif ( 'google' === $transport && ! empty( $settings['google_connected'] ) ) {
			$this->apply_google_transport( $phpmailer, $settings );
		} else {
			return;
		}

		$from_email = $this->resolve_from_email( $settings );
		$from_name  = $settings['from_name'] ?? get_bloginfo( 'name' );

		if ( $from_email ) {
			try {
				$phpmailer->setFrom( $from_email, $from_name, false );
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- From may already be set.
				unset( $e );
			}
		}

		if ( is_array( self::$mail_context ) && ! empty( self::$mail_context['alt_body'] ) ) {
			$phpmailer->AltBody = (string) self::$mail_context['alt_body'];
		}
	}

	/**
	 * Store wp_mail failure details for SLR send() callers.
	 *
	 * @param \WP_Error $error Mail error.
	 * @return void
	 */
	public function capture_mail_failed( $error ) {
		if ( $error instanceof \WP_Error ) {
			$this->last_mail_error = $error;
		}
	}

	/**
	 * Apply custom SMTP settings to PHPMailer.
	 *
	 * @param PHPMailer $phpmailer Mailer instance.
	 * @param array     $settings  Mail settings.
	 * @return void
	 */
	private function apply_smtp_transport( $phpmailer, $settings ) {
		if ( empty( $settings['smtp_host'] ) ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = (string) $settings['smtp_host'];
		$phpmailer->Port       = (int) ( $settings['smtp_port'] ?? 587 );
		$phpmailer->SMTPAuth   = true;
		$phpmailer->Username   = (string) ( $settings['smtp_user'] ?? '' );
		$phpmailer->Password   = $this->decrypt_secret( $settings['smtp_pass'] ?? '' );
		$phpmailer->SMTPSecure = $settings['smtp_encryption'] ?: 'tls';
		$phpmailer->CharSet    = 'UTF-8';
	}

	/**
	 * Apply Gmail OAuth SMTP settings to PHPMailer.
	 *
	 * @param PHPMailer $phpmailer Mailer instance.
	 * @param array     $settings  Mail settings.
	 * @return void
	 */
	private function apply_google_transport( $phpmailer, $settings ) {
		$refresh_token = $this->decrypt_secret( $settings['google_refresh_token'] ?? '' );
		if ( empty( $refresh_token ) ) {
			return;
		}

		$access_token = $this->get_google_access_token( $refresh_token );
		if ( is_wp_error( $access_token ) ) {
			return;
		}

		if ( ! interface_exists( 'PHPMailer\PHPMailer\OAuthTokenProvider' ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/OAuthTokenProvider.php';
		}

		$from_email = $this->resolve_from_email( $settings );

		$phpmailer->isSMTP();
		$phpmailer->Host       = 'smtp.gmail.com';
		$phpmailer->Port       = 587;
		$phpmailer->SMTPSecure = 'tls';
		$phpmailer->SMTPAuth   = true;
		$phpmailer->AuthType   = 'XOAUTH2';
		$phpmailer->CharSet    = 'UTF-8';
		$phpmailer->Username   = $from_email;
		$phpmailer->setOAuth( new Gmail_OAuth_Token_Provider( $from_email, $access_token ) );
	}

	/**
	 * Resolve the From address for SMTP transports.
	 *
	 * @param array $settings Mail settings.
	 * @return string
	 */
	private function resolve_from_email( $settings ) {
		if ( 'google' === ( $settings['transport'] ?? '' ) && ! empty( $settings['google_account_email'] ) ) {
			return (string) $settings['google_account_email'];
		}

		return ! empty( $settings['from_email'] ) ? (string) $settings['from_email'] : '';
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
				$key        = hash( 'sha256', wp_salt( 'auth' ), true );
				$iv         = base64_decode( $payload['iv'], true );
				$tag        = base64_decode( $payload['tag'], true );
				$ciphertext = base64_decode( $payload['ct'], true );

				if ( false !== $iv && false !== $tag && false !== $ciphertext ) {
					$decrypted = openssl_decrypt( $ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
					return false !== $decrypted ? $decrypted : '';
				}
			}

			return '';
		}

		$key       = wp_salt( 'auth' );
		$decrypted = openssl_decrypt( base64_decode( $value ), 'AES-256-CBC', substr( hash( 'sha256', $key ), 0, 32 ), 0, substr( hash( 'sha256', $key . 'iv' ), 0, 16 ) );
		return $decrypted ?: $value;
	}
}
