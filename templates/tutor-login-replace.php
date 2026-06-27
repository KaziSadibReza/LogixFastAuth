<?php
/**
 * Replaces Tutor dashboard login with LogixFastAuth popup triggers.
 *
 * @package LogixFastAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LogixFastAuth\Integrations\Tutor_Login;

Tutor_Login::render_login_popup_shell();
