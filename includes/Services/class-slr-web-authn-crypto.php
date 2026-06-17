<?php
/**
 * Server-side WebAuthn ceremony verification.
 *
 * @package SLR
 */

namespace SLR\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

use SLR\Database\WebAuthnRepository;
use Throwable;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\RSA\RS256;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AppleAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WebAuthnCrypto
 */
class WebAuthnCrypto {

	const LOGIN_SESSION_TTL = 900;

	/**
	 * Whether the WebAuthn PHP library is available.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return class_exists( PublicKeyCredentialLoader::class );
	}

	/**
	 * Build registration options for a user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $rp_id   Relying party ID.
	 * @return array|WP_Error
	 */
	public function build_register_options( $user_id, $rp_id ) {
		if ( ! self::is_available() ) {
			return new WP_Error( 'slr_webauthn_unavailable', __( 'Passkey support is not available on this server.', 'smart-login-registration' ), array( 'status' => 503 ) );
		}

		$user = get_user_by( 'id', (int) $user_id );
		if ( ! $user ) {
			return new WP_Error( 'slr_user_not_found', __( 'User not found.', 'smart-login-registration' ), array( 'status' => 404 ) );
		}

		$challenge = random_bytes( 32 );
		$options   = PublicKeyCredentialCreationOptions::create(
			PublicKeyCredentialRpEntity::create( get_bloginfo( 'name' ), $rp_id ),
			PublicKeyCredentialUserEntity::create(
				$user->user_email,
				(string) $user_id,
				$user->display_name ? $user->display_name : $user->user_login
			),
			$challenge,
			array(
				PublicKeyCredentialParameters::create( 'public-key', -7 ),
				PublicKeyCredentialParameters::create( 'public-key', -257 ),
			),
			null,
			PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
			array(),
			300000
		);

		$payload = $options->jsonSerialize();
		set_transient( 'slr_webauthn_reg_' . (int) $user_id, wp_json_encode( $payload ), 300 );

		return $payload;
	}

	/**
	 * Verify a registration response and return the credential source.
	 *
	 * @param int    $user_id User ID.
	 * @param array  $response Browser credential response.
	 * @param string $rp_id   Relying party ID.
	 * @return PublicKeyCredentialSource|WP_Error
	 */
	public function verify_register( $user_id, $response, $rp_id ) {
		if ( ! self::is_available() ) {
			return new WP_Error( 'slr_webauthn_unavailable', __( 'Passkey support is not available on this server.', 'smart-login-registration' ), array( 'status' => 503 ) );
		}

		$stored = get_transient( 'slr_webauthn_reg_' . (int) $user_id );
		if ( ! $stored ) {
			return new WP_Error( 'slr_webauthn_expired', __( 'Registration session expired.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		delete_transient( 'slr_webauthn_reg_' . (int) $user_id );

		try {
			$creation_options = PublicKeyCredentialCreationOptions::createFromArray( json_decode( $stored, true ) );
			$credential       = $this->load_credential( $response );
			if ( is_wp_error( $credential ) ) {
				return $credential;
			}

			if ( ! $credential->response instanceof AuthenticatorAttestationResponse ) {
				return new WP_Error( 'slr_webauthn_invalid', __( 'Invalid passkey response.', 'smart-login-registration' ), array( 'status' => 400 ) );
			}

			$factory   = $this->create_ceremony_factory( $rp_id );
			$validator = AuthenticatorAttestationResponseValidator::create( null, null, null, null, null, $factory->creationCeremony( $this->get_secured_rp_ids( $rp_id ) ) );

			return $validator->check( $credential->response, $creation_options, $rp_id );
		} catch ( Throwable $exception ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only.
				error_log( 'SLR WebAuthn register verify failed: ' . $exception->getMessage() );
			}
			return new WP_Error( 'slr_webauthn_invalid', __( 'Passkey registration could not be verified.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}
	}

	/**
	 * Build login options.
	 *
	 * @param string $email Optional email.
	 * @param string $rp_id Relying party ID.
	 * @return array|WP_Error
	 */
	public function build_login_options( $email, $rp_id ) {
		if ( ! self::is_available() ) {
			return new WP_Error( 'slr_webauthn_unavailable', __( 'Passkey support is not available on this server.', 'smart-login-registration' ), array( 'status' => 503 ) );
		}

		$challenge   = random_bytes( 32 );
		$allow_creds = array();

		if ( '' !== $email ) {
			$user = get_user_by( 'email', sanitize_email( $email ) );
			if ( $user ) {
				$creds = ( new WebAuthnRepository() )->get_by_user( (int) $user->ID );
				foreach ( $creds as $cred ) {
					$source = $this->load_credential_source( $cred );
					if ( $source instanceof PublicKeyCredentialSource ) {
						$allow_creds[] = $source->getPublicKeyCredentialDescriptor();
					}
				}
			}
		}

		$options = PublicKeyCredentialRequestOptions::create(
			$challenge,
			$rp_id,
			$allow_creds,
			PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
			600000
		);

		$session_key = 'slr_webauthn_login_' . wp_generate_password( 16, false );
		$payload     = $this->normalize_request_options_payload( $options->jsonSerialize() );
		$options_json = wp_json_encode( $payload );

		if ( ! is_string( $options_json ) || '' === $options_json ) {
			return new WP_Error( 'slr_webauthn_invalid', __( 'Passkey sign-in could not be started.', 'smart-login-registration' ), array( 'status' => 500 ) );
		}

		set_transient(
			$session_key,
			array(
				'email'   => $email,
				'options' => $options_json,
			),
			self::LOGIN_SESSION_TTL
		);

		return array(
			'options'     => $payload,
			'sessionKey'  => $session_key,
			'session_key' => $session_key,
		);
	}

	/**
	 * Verify a login response.
	 *
	 * @param string $session_key Session key.
	 * @param array  $response    Browser credential response.
	 * @param string $rp_id       Relying party ID.
	 * @return int|WP_Error User ID.
	 */
	public function verify_login( $session_key, $response, $rp_id ) {
		if ( ! self::is_available() ) {
			return new WP_Error( 'slr_webauthn_unavailable', __( 'Passkey support is not available on this server.', 'smart-login-registration' ), array( 'status' => 503 ) );
		}

		$session = get_transient( $session_key );
		$options_array = $this->decode_stored_request_options( $session );
		if ( null === $options_array ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only.
				error_log( 'SLR WebAuthn login session missing or expired for key prefix: ' . substr( $session_key, 0, 24 ) );
			}
			return new WP_Error( 'slr_webauthn_expired', __( 'Login session expired. Please try passkey sign-in again.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		$credential_id = $this->sanitize_credential_id( $response['id'] ?? '' );
		if ( '' === $credential_id ) {
			return new WP_Error( 'slr_webauthn_invalid', __( 'Invalid passkey response.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		$repo       = new WebAuthnRepository();
		$credential = $repo->get_by_credential_id( $credential_id );
		if ( ! $credential ) {
			return new WP_Error( 'slr_webauthn_invalid', __( 'Passkey not recognized.', 'smart-login-registration' ), array( 'status' => 401 ) );
		}

		$source = $this->load_credential_source( $credential );
		if ( ! $source instanceof PublicKeyCredentialSource ) {
			return new WP_Error(
				'slr_webauthn_invalid',
				__( 'This passkey must be registered again before it can be used.', 'smart-login-registration' ),
				array( 'status' => 401 )
			);
		}

		try {
			$request_options = PublicKeyCredentialRequestOptions::createFromArray( $options_array );
			$public_key_cred   = $this->load_credential( $response );
			if ( is_wp_error( $public_key_cred ) ) {
				return $public_key_cred;
			}

			if ( ! $public_key_cred->response instanceof AuthenticatorAssertionResponse ) {
				return new WP_Error( 'slr_webauthn_invalid', __( 'Invalid passkey response.', 'smart-login-registration' ), array( 'status' => 400 ) );
			}

			$factory   = $this->create_ceremony_factory( $rp_id );
			$validator = AuthenticatorAssertionResponseValidator::create( null, null, null, null, null, $factory->requestCeremony( $this->get_secured_rp_ids( $rp_id ) ) );
			$updated   = $validator->check( $source, $public_key_cred->response, $request_options, $rp_id, $source->userHandle );

			$repo->update_counter( (int) $credential->id, (int) $updated->counter );
			$repo->update_public_key( (int) $credential->id, wp_json_encode( $updated->jsonSerialize() ) );

			delete_transient( $session_key );

			return (int) $credential->user_id;
		} catch ( Throwable $exception ) {
			return new WP_Error( 'slr_webauthn_invalid', __( 'Passkey sign-in could not be verified.', 'smart-login-registration' ), array( 'status' => 401 ) );
		}
	}

	/**
	 * Persist a verified credential source.
	 *
	 * @param int                       $user_id User ID.
	 * @param PublicKeyCredentialSource $source  Verified source.
	 * @param array                     $response Original browser response.
	 * @return true|WP_Error
	 */
	public function store_credential_source( $user_id, PublicKeyCredentialSource $source, $response ) {
		$credential_id = $this->sanitize_credential_id( $response['id'] ?? '' );
		if ( '' === $credential_id ) {
			return new WP_Error( 'slr_webauthn_invalid', __( 'Invalid passkey response.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		( new WebAuthnRepository() )->create(
			array(
				'user_id'       => (int) $user_id,
				'credential_id' => $credential_id,
				'public_key'    => wp_json_encode( $source->jsonSerialize() ),
				'counter'       => (int) $source->counter,
				'transports'    => implode( ',', $response['transports'] ?? array() ),
			)
		);

		return true;
	}

	/**
	 * Normalize WebAuthn request options for JSON transient storage.
	 *
	 * @param array<string, mixed> $payload Serialized request options.
	 * @return array<string, mixed>
	 */
	private function normalize_request_options_payload( array $payload ) {
		if ( empty( $payload['allowCredentials'] ) || ! is_array( $payload['allowCredentials'] ) ) {
			return $payload;
		}

		$normalized = array();
		foreach ( $payload['allowCredentials'] as $descriptor ) {
			if ( $descriptor instanceof \Webauthn\PublicKeyCredentialDescriptor ) {
				$normalized[] = $descriptor->jsonSerialize();
				continue;
			}
			if ( is_array( $descriptor ) ) {
				$normalized[] = $descriptor;
			}
		}

		$payload['allowCredentials'] = $normalized;

		return $payload;
	}

	/**
	 * Decode stored login options from a transient payload.
	 *
	 * @param mixed $session Stored transient value.
	 * @return array<string, mixed>|null
	 */
	private function decode_stored_request_options( $session ) {
		if ( ! is_array( $session ) || ! isset( $session['options'] ) ) {
			return null;
		}

		$stored_options = $session['options'];
		if ( is_string( $stored_options ) ) {
			$decoded = json_decode( $stored_options, true );
			return is_array( $decoded ) && ! empty( $decoded['challenge'] ) ? $decoded : null;
		}

		if ( is_array( $stored_options ) && ! empty( $stored_options['challenge'] ) ) {
			return $this->normalize_request_options_payload( $stored_options );
		}

		return null;
	}

	/**
	 * @param object|null $row DB row.
	 * @return PublicKeyCredentialSource|null
	 */
	private function load_credential_source( $row ) {
		if ( ! $row || empty( $row->public_key ) ) {
			return null;
		}

		$data = json_decode( (string) $row->public_key, true );
		if ( ! is_array( $data ) || empty( $data['publicKeyCredentialId'] ) ) {
			return null;
		}

		try {
			return PublicKeyCredentialSource::createFromArray( $data );
		} catch ( Throwable $exception ) {
			return null;
		}
	}

	/**
	 * @param array $response Browser response.
	 * @return PublicKeyCredential|WP_Error
	 */
	private function load_credential( $response ) {
		if ( ! is_array( $response ) ) {
			return new WP_Error( 'slr_webauthn_invalid', __( 'Invalid passkey response.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}

		try {
			$manager = $this->get_attestation_statement_manager();
			$loader  = PublicKeyCredentialLoader::create( AttestationObjectLoader::create( $manager ) );

			return $loader->loadArray( $response );
		} catch ( Throwable $exception ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only.
				error_log( 'SLR WebAuthn credential load failed: ' . $exception->getMessage() );
			}
			return new WP_Error( 'slr_webauthn_invalid', __( 'Invalid passkey response.', 'smart-login-registration' ), array( 'status' => 400 ) );
		}
	}

	/**
	 * Attestation formats used by platform passkeys (Windows Hello, iCloud, etc.).
	 *
	 * @return AttestationStatementSupportManager
	 */
	private function get_attestation_statement_manager() {
		$algorithm_manager = Manager::create()->add( ES256::create(), RS256::create() );

		return AttestationStatementSupportManager::create(
			array(
				PackedAttestationStatementSupport::create( $algorithm_manager ),
				AppleAttestationStatementSupport::create(),
				TPMAttestationStatementSupport::create(),
				FidoU2FAttestationStatementSupport::create(),
				AndroidKeyAttestationStatementSupport::create(),
			)
		);
	}

	/**
	 * Ceremony factory with attestation + local HTTP exceptions for dev hosts.
	 *
	 * @param string $rp_id Relying party ID.
	 * @return CeremonyStepManagerFactory
	 */
	private function create_ceremony_factory( $rp_id ) {
		$factory = new CeremonyStepManagerFactory();
		$factory->setAttestationStatementSupportManager( $this->get_attestation_statement_manager() );
		$factory->setSecuredRelyingPartyId( $this->get_secured_rp_ids( $rp_id ) );

		return $factory;
	}

	/**
	 * RP IDs allowed to use HTTP (localhost / local dev) during WebAuthn verification.
	 *
	 * @param string $rp_id Primary relying party ID.
	 * @return string[]
	 */
	private function get_secured_rp_ids( $rp_id ) {
		$ids = array_filter(
			array(
				$rp_id,
				wp_parse_url( home_url(), PHP_URL_HOST ),
				'localhost',
				'127.0.0.1',
			)
		);

		return array_values( array_unique( array_map( 'strval', $ids ) ) );
	}

	/**
	 * Preserve base64url credential IDs from the browser.
	 *
	 * @param mixed $credential_id Credential ID.
	 * @return string
	 */
	public function sanitize_credential_id( $credential_id ) {
		$credential_id = is_string( $credential_id ) ? trim( $credential_id ) : '';
		if ( '' === $credential_id ) {
			return '';
		}

		return preg_replace( '/[^A-Za-z0-9\-_]/', '', $credential_id );
	}
}
