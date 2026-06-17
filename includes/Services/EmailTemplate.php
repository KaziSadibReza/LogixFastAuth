<?php
/**
 * HTML email templates for SLR.
 *
 * @package SLR
 */

namespace SLR\Services; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- SLR is the plugin prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EmailTemplate
 */
class EmailTemplate {

	/**
	 * Brand accent color.
	 */
	private const ACCENT = '#d6336c';

	/**
	 * Render OTP verification email (HTML).
	 *
	 * @param string $code        OTP code.
	 * @param string $purpose     register|login|verify.
	 * @param int    $ttl_minutes Expiry in minutes.
	 * @return string
	 */
	public static function otp_verification_html( $code, $purpose = 'verify', $ttl_minutes = 10 ) {
		$site_name = get_bloginfo( 'name' );
		$site_url  = home_url( '/' );
		$year      = gmdate( 'Y' );
		$code      = preg_replace( '/\D/', '', (string) $code );

		$headings = array(
			'register' => __( 'Verify your email', 'smart-login-registration' ),
			'login'    => __( 'Sign-in verification', 'smart-login-registration' ),
			'reset'    => __( 'Reset your password', 'smart-login-registration' ),
			'verify'   => __( 'Verification code', 'smart-login-registration' ),
		);

		$messages = array(
			'register' => __( 'Enter this code to finish creating your account. Your welcome email will arrive after verification.', 'smart-login-registration' ),
			'login'    => __( 'Enter this code to sign in to your account.', 'smart-login-registration' ),
			'reset'    => __( 'Enter this code to reset your password.', 'smart-login-registration' ),
			'verify'   => __( 'Enter this code to verify your identity.', 'smart-login-registration' ),
		);

		$heading = $headings[ $purpose ] ?? $headings['verify'];
		$message = $messages[ $purpose ] ?? $messages['verify'];

		$expiry_text = sprintf(
			/* translators: %d: number of minutes */
			_n( 'This code expires in %d minute.', 'This code expires in %d minutes.', $ttl_minutes, 'smart-login-registration' ),
			$ttl_minutes
		);

		$security_note = __( 'If you did not request this code, you can safely ignore this email.', 'smart-login-registration' );
		$digit_row     = self::build_digit_boxes_row( $code );
		$accent        = esc_attr( self::ACCENT );

		ob_start();
		?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title><?php echo esc_html( $heading ); ?></title>
	<!--[if mso]>
	<noscript>
		<xml>
			<o:OfficeDocumentSettings>
				<o:PixelsPerInch>96</o:PixelsPerInch>
			</o:OfficeDocumentSettings>
		</xml>
	</noscript>
	<![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#f3f0ee;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1a1a2e;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
	<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#f3f0ee" style="background-color:#f3f0ee;width:100%;">
		<tr>
			<td align="center" style="padding:40px 16px;">
				<table role="presentation" width="520" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff" style="width:100%;max-width:520px;background-color:#ffffff;border-radius:20px;border:1px solid #ece8e6;">
					<!-- Header -->
					<tr>
						<td align="center" bgcolor="<?php echo esc_attr( $accent ); ?>" style="background-color:<?php echo esc_attr( $accent ); ?>;padding:32px 40px;text-align:center;">
							<p style="margin:0 0 8px;font-size:13px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#ffffff;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;"><?php echo esc_html( $site_name ); ?></p>
							<h1 style="margin:0;font-size:26px;font-weight:700;color:#ffffff;line-height:1.3;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;"><?php echo esc_html( $heading ); ?></h1>
						</td>
					</tr>
					<!-- Body -->
					<tr>
						<td style="padding:36px 40px 28px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
							<p style="margin:0 0 28px;font-size:15px;line-height:1.6;color:#4a4a68;"><?php echo esc_html( $message ); ?></p>

							<!-- OTP digit boxes (nested tables — email-client safe) -->
							<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto 28px;">
								<tr>
									<?php echo $digit_row; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</tr>
							</table>

							<p style="margin:0 0 8px;font-size:14px;line-height:1.5;color:#6b6b80;text-align:center;"><?php echo esc_html( $expiry_text ); ?></p>
							<p style="margin:0;font-size:13px;line-height:1.5;color:#9a9ab0;text-align:center;"><?php echo esc_html( $security_note ); ?></p>
						</td>
					</tr>
					<!-- Footer -->
					<tr>
						<td align="center" style="padding:20px 40px 32px;border-top:1px solid #f0ecea;text-align:center;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
							<p style="margin:0 0 4px;font-size:12px;color:#9a9ab0;">
								<a href="<?php echo esc_url( $site_url ); ?>" style="color:<?php echo esc_attr( $accent ); ?>;text-decoration:none;font-weight:600;"><?php echo esc_html( $site_name ); ?></a>
							</p>
							<p style="margin:0;font-size:11px;color:#b8b8c8;">&copy; <?php echo esc_html( $year ); ?> <?php echo esc_html( $site_name ); ?></p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
		<?php
		return trim( (string) ob_get_clean() );
	}

	/**
	 * Build a row of fixed-size digit boxes using nested tables (email-safe).
	 *
	 * @param string $code OTP code.
	 * @return string
	 */
	private static function build_digit_boxes_row( $code ) {
		$digits = str_split( $code );
		if ( empty( $digits ) ) {
			return '';
		}

		$html = '';
		foreach ( $digits as $digit ) {
			$html .= '<td valign="top" width="1%" style="width:1%;padding:0;margin:0;white-space:nowrap;">';
			$html .= '<table role="presentation" cellspacing="0" cellpadding="0" border="0">';
			$html .= '<tr>';
			$html .= '<td align="center" valign="middle" width="48" height="56" bgcolor="#f8f4f6" style="width:48px;min-width:48px;max-width:48px;height:56px;background-color:#f8f4f6;border:1px solid #ecd5df;border-radius:10px;font-size:24px;font-weight:700;color:#1a1a2e;font-family:\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;text-align:center;line-height:56px;mso-line-height-rule:exactly;">';
			$html .= esc_html( $digit );
			$html .= '</td>';
			$html .= '</tr>';
			$html .= '</table>';
			$html .= '</td>';
		}

		return $html;
	}

	/**
	 * Plain-text fallback for OTP email.
	 *
	 * @param string $code        OTP code.
	 * @param string $purpose     Purpose.
	 * @param int    $ttl_minutes Expiry in minutes.
	 * @return string
	 */
	public static function otp_verification_plain( $code, $purpose = 'verify', $ttl_minutes = 10 ) {
		$site_name = get_bloginfo( 'name' );

		$expiry_text = sprintf(
			/* translators: %d: number of minutes */
			_n( 'This code expires in %d minute.', 'This code expires in %d minutes.', $ttl_minutes, 'smart-login-registration' ),
			$ttl_minutes
		);

		return sprintf(
			"%s\n\n%s: %s\n\n%s\n\n— %s",
			__( 'Your verification code', 'smart-login-registration' ),
			__( 'Code', 'smart-login-registration' ),
			$code,
			$expiry_text,
			$site_name
		);
	}
}
