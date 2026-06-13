<?php
/**
 * Minimal dedicated login page template.
 *
 * @package SLR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> — <?php esc_html_e( 'Sign In & Register', 'smart-login-registration' ); ?></title>
	<?php wp_head(); ?>
	<style>
		html,
		body.slr-dedicated-page {
			margin: 0;
			padding: 0;
			width: 100%;
			min-height: 100dvh;
			overflow: hidden;
			-webkit-text-size-adjust: 100%;
		}

		body.slr-dedicated-page {
			background:
				radial-gradient(circle at 20% 20%, rgba(214, 51, 108, 0.06) 0%, transparent 45%),
				radial-gradient(circle at 80% 80%, rgba(15, 23, 42, 0.04) 0%, transparent 40%),
				linear-gradient(160deg, #f8f9fb 0%, #eef1f5 50%, #e8ecf1 100%);
		}

		#slr-root {
			min-height: 100dvh;
			width: 100%;
		}
	</style>
</head>
<body <?php body_class( 'slr-dedicated-page' ); ?>>
	<div id="slr-root" data-mode="page"></div>
	<?php wp_footer(); ?>
</body>
</html>
