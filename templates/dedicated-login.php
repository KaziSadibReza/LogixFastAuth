<?php
/**
 * Minimal dedicated login page template.
 *
 * @package LogixFastAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> — <?php esc_html_e( 'Sign In & Register', 'logixfast-auth' ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'logixfast-auth-dedicated-page' ); ?>>
	<div id="logixfast-auth-root" data-mode="page"></div>
	<?php wp_footer(); ?>
</body>
</html>
