<?php
/**
 * Replaces Tutor dashboard login with SLR popup triggers.
 *
 * @package SLR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SLR\Integrations\Tutor_Login;

Tutor_Login::render_login_popup_shell();
