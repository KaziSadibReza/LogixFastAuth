<?php
/**
 * OTP generation and verification.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Database\OtpRepository;
use LogixFastAuth\Settings;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OtpService
 */
class OtpService {

	/**
	 * @var OtpRepository
	 */
	private $repository;

	/**
	 * @var MailService
	 */
	private $mail;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new OtpRepository();
		$this->mail       = MailService::instance();
	}

	/**
	 * Send OTP to email or phone.
	 *
	 * @param string $identifier Email or phone.
	 * @param string $channel    email|phone.
	 * @param string $purpose    register|login|verify.
	 * @return array|WP_Error
	 */
	public function send( $identifier, $channel = 'email', $purpose = 'verify' ) {
		$security = Settings::get( 'security' );
		$cooldown = (int) ( $security['otp_resend_cooldown'] ?? 60 );

		$identifier = is_string( $identifier ) ? trim( $identifier ) : '';
		$channel    = $this->normalize_channel( $channel );
		$purpose    = $this->normalize_purpose( $purpose );

		if ( is_wp_error( $channel ) ) {
			return $channel;
		}

		if ( is_wp_error( $purpose ) ) {
			return $purpose;
		}

		if ( '' === $identifier ) {
			return new WP_Error(
				'logixfast_auth_missing_identifier',
				__( 'Email or phone is required.', 'logixfast-auth' ),
				array( 'status' => 400 )
			);
		}

		$identifier_hash = $this->hash_identifier( $identifier, $channel );

		$last = $this->repository->get_latest( $identifier_hash, $channel, $purpose );
		if ( $last && ( time() - strtotime( $last->created_at . ' UTC' ) ) < $cooldown ) {
			return new WP_Error(
				'logixfast_auth_otp_cooldown',
				__( 'Please wait before requesting another code.', 'logixfast-auth' ),
				array( 'status' => 429 )
			);
		}

		// Always invalidate any previous codes for this identifier so only the
		// latest code is usable. This also prevents stale codes from blocking
		// verification when the user requests a resend.
		$this->repository->delete_by_identifier( $identifier_hash, $channel, $purpose );

		$code      = $this->generate_code();
		$code_hash = wp_hash_password( $code );
		$ttl       = (int) ( $security['otp_ttl'] ?? 600 );

		$insert_id = $this->repository->create( array(
			'identifier' => $identifier_hash,
			'channel'    => $channel,
			'purpose'    => $purpose,
			'code_hash'  => $code_hash,
			'expires_at' => gmdate( 'Y-m-d H:i:s', time() + $ttl ),
		) );

		if ( ! $insert_id ) {
			$db_error = method_exists( $this->repository, 'last_error' ) ? $this->repository->last_error() : '';
			$detail   = ( defined( 'WP_DEBUG' ) && WP_DEBUG && '' !== $db_error )
				? ' (' . $db_error . ')'
				: '';
			return new WP_Error(
				'logixfast_auth_otp_storage_failed',
				__( 'Could not generate verification code. Please try again.', 'logixfast-auth' ) . $detail,
				array( 'status' => 500 )
			);
		}

		if ( 'email' === $channel ) {
			$sent = $this->mail->send_otp( $identifier, $code, $purpose );
			if ( is_wp_error( $sent ) ) {
				return $sent;
			}
		} elseif ( 'phone' === $channel ) {
			$sent = $this->send_sms_otp( $identifier, $code, $purpose );
			if ( is_wp_error( $sent ) ) {
				return $sent;
			}
		}

		return array(
			'sent'     => true,
			'channel'  => $channel,
			'cooldown' => $cooldown,
		);
	}

	/**
	 * Verify OTP code.
	 *
	 * @param string $identifier Email or phone.
	 * @param string $code       OTP code.
	 * @param string $channel    email|phone.
	 * @return true|WP_Error
	 */
	public function verify( $identifier, $code, $channel = 'email', $purpose = 'verify' ) {
		$security        = Settings::get( 'security' );
		$max_attempts    = (int) ( $security['otp_max_attempts'] ?? 5 );
		$channel         = $this->normalize_channel( $channel );
		$purpose         = $this->normalize_purpose( $purpose );

		if ( is_wp_error( $channel ) ) {
			return $channel;
		}

		if ( is_wp_error( $purpose ) ) {
			return $purpose;
		}

		$identifier_hash = $this->hash_identifier( $identifier, $channel );

		$record = $this->repository->get_latest( $identifier_hash, $channel, $purpose );

		if ( ! $record ) {
			return new WP_Error( 'logixfast_auth_otp_invalid', __( 'Invalid or expired code.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		if ( strtotime( $record->expires_at . ' UTC' ) < time() ) {
			return new WP_Error( 'logixfast_auth_otp_expired', __( 'Code has expired. Please request a new one.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		if ( (int) $record->attempts >= $max_attempts ) {
			return new WP_Error( 'logixfast_auth_otp_max_attempts', __( 'Too many failed attempts.', 'logixfast-auth' ), array( 'status' => 429 ) );
		}

		if ( ! wp_check_password( $code, $record->code_hash ) ) {
			$this->repository->increment_attempts( $record->id );
			return new WP_Error( 'logixfast_auth_otp_invalid', __( 'Invalid code. Please try again.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		$this->repository->delete( $record->id );

		return true;
	}

	/**
	 * Send SMS via registered provider.
	 *
	 * @param string $phone   Phone number.
	 * @param string $code    OTP code.
	 * @param string $purpose Purpose.
	 * @return true|WP_Error
	 */
	private function send_sms_otp( $phone, $code, $purpose ) {
		$providers = apply_filters( 'logixfast_auth_sms_providers', array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LogixFastAuth plugin hook.

		if ( empty( $providers ) ) {
			return new WP_Error( 'logixfast_auth_no_sms_provider', __( 'No SMS provider configured.', 'logixfast-auth' ), array( 'status' => 503 ) );
		}

		$provider = reset( $providers );
		if ( ! $provider instanceof SmsProviderInterface ) {
			return new WP_Error( 'logixfast_auth_invalid_sms_provider', __( 'Invalid SMS provider.', 'logixfast-auth' ), array( 'status' => 500 ) );
		}

		$message = sprintf(
			/* translators: %s: OTP code */
			__( 'Your verification code is: %s', 'logixfast-auth' ),
			$code
		);

		return $provider->send( $phone, $message );
	}

	/**
	 * Generate 6-digit OTP.
	 *
	 * @return string
	 */
	private function generate_code() {
		return (string) random_int( 100000, 999999 );
	}

	/**
	 * Normalize and validate OTP channel.
	 *
	 * @param string $channel Channel.
	 * @return string|WP_Error
	 */
	private function normalize_channel( $channel ) {
		$channel = sanitize_key( $channel );
		if ( ! in_array( $channel, array( 'email', 'phone' ), true ) ) {
			return new WP_Error( 'logixfast_auth_invalid_channel', __( 'Invalid verification channel.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		return $channel;
	}

	/**
	 * Normalize and validate OTP purpose.
	 *
	 * @param string $purpose Purpose.
	 * @return string|WP_Error
	 */
	private function normalize_purpose( $purpose ) {
		$purpose = sanitize_key( $purpose );
		if ( ! in_array( $purpose, array( 'register', 'login', 'reset', 'verify' ), true ) ) {
			return new WP_Error( 'logixfast_auth_invalid_purpose', __( 'Invalid verification request.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		return $purpose;
	}

	/**
	 * Hash identifier for storage.
	 *
	 * @param string $identifier Identifier.
	 * @param string $channel    Channel.
	 * @return string
	 */
	private function hash_identifier( $identifier, $channel ) {
		return hash( 'sha256', strtolower( $channel ) . ':' . strtolower( trim( $identifier ) ) );
	}
}
