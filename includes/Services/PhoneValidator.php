<?php
/**
 * International phone validation.
 *
 * @package LogixFastAuth
 */

namespace LogixFastAuth\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- LogixFastAuth is the plugin prefix.

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PhoneValidator
 */
class PhoneValidator {

	/**
	 * libphonenumber class names (optional Composer dependency).
	 */
	const LIB_UTIL   = '\\libphonenumber\\PhoneNumberUtil';
	const LIB_FORMAT = '\\libphonenumber\\PhoneNumberFormat';

	/**
	 * Validate and normalize phone number to E.164.
	 *
	 * @param string $phone   Phone number.
	 * @param string $region Default region ISO code.
	 * @return string|WP_Error
	 */
	public function validate( $phone, $region = 'BD' ) {
		if ( ! class_exists( self::LIB_UTIL ) ) {
			return $this->validate_basic( $phone );
		}

		$util = call_user_func( array( self::LIB_UTIL, 'getInstance' ) );

		try {
			$number = $util->parse( $phone, $region );
		} catch ( \Exception $e ) {
			return new WP_Error( 'logixfast_auth_invalid_phone', __( 'Please enter a valid phone number.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		if ( ! $util->isValidNumber( $number ) ) {
			return new WP_Error( 'logixfast_auth_invalid_phone', __( 'Please enter a valid phone number.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		return $util->format( $number, constant( self::LIB_FORMAT . '::E164' ) );
	}

	/**
	 * Basic validation when libphonenumber is not installed.
	 *
	 * @param string $phone Phone number.
	 * @return string|WP_Error
	 */
	private function validate_basic( $phone ) {
		$phone = preg_replace( '/[^\d+]/', '', $phone );

		if ( strlen( $phone ) < 8 ) {
			return new WP_Error( 'logixfast_auth_invalid_phone', __( 'Please enter a valid phone number.', 'logixfast-auth' ), array( 'status' => 400 ) );
		}

		return $phone;
	}
}
