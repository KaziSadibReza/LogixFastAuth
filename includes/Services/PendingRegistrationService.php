<?php
/**
 * Stage registration data until OTP is verified.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use LogixFastAuth\Activator;
use LogixFastAuth\Database\PendingRegistrationRepository;
use LogixFastAuth\Settings;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PendingRegistrationService
 */
class PendingRegistrationService {

	/**
	 * @var PendingRegistrationRepository
	 */
	private $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new PendingRegistrationRepository();
	}

	/**
	 * Registration staging TTL (longer than a single OTP code).
	 *
	 * @return int Seconds.
	 */
	public static function get_session_ttl() {
		$security = Settings::get( 'security' );
		$otp_ttl  = (int) ( $security['otp_ttl'] ?? 600 );

		return max( $otp_ttl * 6, 3600 );
	}

	/**
	 * Store registration payload until OTP verification completes.
	 *
	 * @param array $data Validated registration data.
	 * @return array|WP_Error
	 */
	public function stage( $data ) {
		if ( class_exists( Activator::class ) && ! Activator::ensure_tables() ) {
			return new WP_Error(
				'logixfast_auth_pending_storage_failed',
				__( 'Registration storage is unavailable. Please contact the site administrator.', 'logixfast-auth' ),
				array( 'status' => 500 )
			);
		}

		$this->repository->purge_expired();

		$email      = sanitize_email( $data['email'] ?? '' );
		$phone      = sanitize_text_field( $data['phone'] ?? '' );
		$channel    = ! empty( $data['otp_channel'] ) ? $data['otp_channel'] : 'email';
		$identifier = 'phone' === $channel ? $phone : $email;

		if ( empty( $identifier ) ) {
			return new WP_Error( 'logixfast_auth_missing_identifier', __( 'Email or phone is required.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		$identifier_hash = $this->hash_identifier( $identifier, $channel );
		$token           = bin2hex( random_bytes( 32 ) );
		$ttl             = self::get_session_ttl();

		$payload = array(
			'full_name' => sanitize_text_field( $data['full_name'] ?? '' ),
			'email'     => $email,
			'phone'     => $phone,
			'password'  => (string) ( $data['password'] ?? '' ),
			'username'  => sanitize_user( (string) ( $data['username'] ?? '' ), true ),
		);

		$json = wp_json_encode( $payload );
		if ( false === $json ) {
			return new WP_Error(
				'logixfast_auth_pending_encode_failed',
				__( 'Could not encode registration data.', 'logixfast-auth' ),
				array( 'status' => 500 )
			);
		}

		$encrypted = MailService::encrypt_secret( $json );
		if ( empty( $encrypted ) ) {
			return new WP_Error(
				'logixfast_auth_pending_storage_failed',
				__( 'Could not secure registration data. Please contact the site administrator.', 'logixfast-auth' ),
				array( 'status' => 500 )
			);
		}

		$mail            = MailService::instance();
		$round_trip_json = $mail->decrypt_secret( $encrypted );
		if ( $round_trip_json !== $json ) {
			return new WP_Error(
				'logixfast_auth_pending_storage_failed',
				__( 'Encryption check failed. Please contact the site administrator.', 'logixfast-auth' ),
				array( 'status' => 500 )
			);
		}

		$this->repository->delete_by_identifier( $identifier_hash );

		$insert_id = $this->repository->create(
			array(
				'token'           => $token,
				'identifier_hash' => $identifier_hash,
				'channel'         => $channel,
				'data_encrypted'  => $encrypted,
				'expires_at'      => self::expires_at_gmt( $ttl ),
			)
		);

		if ( ! $insert_id ) {
			$db_error = $this->repository->last_error();
			$detail   = ( defined( 'WP_DEBUG' ) && WP_DEBUG && '' !== $db_error )
				? ' (' . $db_error . ')'
				: '';
			return new WP_Error(
				'logixfast_auth_pending_storage_failed',
				__( 'Could not save registration session. Please try again.', 'logixfast-auth' ) . $detail,
				array( 'status' => 500 )
			);
		}

		return array(
			'pending_token'      => $token,
			'email'              => $email,
			'session_expires_at' => time() + $ttl,
		);
	}

	/**
	 * Extend an active registration session (e.g. after OTP resend).
	 *
	 * @param string $token       Pending token.
	 * @param string $identifier  Email or phone.
	 * @param string $channel     email|phone.
	 * @return array|WP_Error
	 */
	public function extend( $token, $identifier, $channel = 'email' ) {
		$record = $this->resolve_record( $token, $identifier, $channel );
		if ( is_wp_error( $record ) ) {
			return $record;
		}

		$ttl        = self::get_session_ttl();
		$expires_at = self::expires_at_gmt( $ttl );

		if ( ! $this->repository->update_expires_at( $record->token, $expires_at ) ) {
			return new WP_Error( 'logixfast_auth_pending_extend_failed', __( 'Could not extend registration session.', 'logixfast-auth' ), array( 'status' => 500 ) );
		}

		return array(
			'pending_token'      => $record->token,
			'session_expires_at' => time() + $ttl,
		);
	}

	/**
	 * Check that a pending registration exists for the given identifier
	 * without consuming or modifying it. Returns the matched record or a
	 * WP_Error explaining why it cannot be used.
	 *
	 * @param string $token       Pending token.
	 * @param string $identifier  Email or phone.
	 * @param string $channel     email|phone.
	 * @return object|WP_Error
	 */
	public function peek( $token, $identifier, $channel = 'email' ) {
		return $this->resolve_record( $token, $identifier, $channel );
	}

	/**
	 * Load staged registration after OTP verification.
	 *
	 * @param string $token       Pending token.
	 * @param string $identifier  Email or phone used for OTP.
	 * @param string $channel     email|phone.
	 * @return array|WP_Error
	 */
	public function consume( $token, $identifier, $channel = 'email' ) {
		$record = $this->resolve_record( $token, $identifier, $channel );
		if ( is_wp_error( $record ) ) {
			return $record;
		}

		$mail    = MailService::instance();
		$decoded = json_decode( $mail->decrypt_secret( $record->data_encrypted ), true );
		if ( ! is_array( $decoded ) || empty( $decoded['email'] ) || ! array_key_exists( 'password', $decoded ) || '' === $decoded['password'] ) {
			$this->repository->delete_by_token( $record->token );
			return new WP_Error( 'logixfast_auth_pending_invalid', __( 'Registration data is invalid. Please sign up again.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		$this->repository->delete_by_token( $record->token );

		return $decoded;
	}

	/**
	 * Find a valid pending record by token and/or identifier.
	 *
	 * @param string $token       Pending token.
	 * @param string $identifier  Email or phone.
	 * @param string $channel     email|phone.
	 * @return object|WP_Error
	 */
	private function resolve_record( $token, $identifier, $channel = 'email' ) {
		$record = null;

		if ( ! empty( $token ) ) {
			$record = $this->repository->get_by_token( $token );
		}

		if ( ! $record && ! empty( $identifier ) ) {
			$record = $this->repository->get_by_identifier(
				$this->hash_identifier( $identifier, $channel ),
				$channel
			);
		}

		if ( ! $record ) {
			return new WP_Error( 'logixfast_auth_pending_expired', __( 'Registration session expired. Please sign up again.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		if ( self::is_expired( $record->expires_at ) ) {
			$this->repository->delete_by_token( $record->token );
			return new WP_Error( 'logixfast_auth_pending_expired', __( 'Registration session expired. Please sign up again.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		if ( ! empty( $identifier ) ) {
			$expected_hash = $this->hash_identifier( $identifier, $channel );
			if ( $record->identifier_hash !== $expected_hash || $record->channel !== $channel ) {
				return new WP_Error( 'logixfast_auth_pending_mismatch', __( 'Verification does not match this registration.', 'logixfast-auth' ), array( 'status' => 400 ) );
			}
		}

		return $record;
	}

	/**
	 * @param int $ttl Seconds from now.
	 * @return string
	 */
	public static function expires_at_gmt( $ttl ) {
		return gmdate( 'Y-m-d H:i:s', time() + $ttl );
	}

	/**
	 * @param string $expires_at_gmt UTC datetime string.
	 * @return bool
	 */
	public static function is_expired( $expires_at_gmt ) {
		return strtotime( $expires_at_gmt . ' UTC' ) < time();
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
