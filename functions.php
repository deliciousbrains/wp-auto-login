<?php

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! function_exists( 'dbi_get_auto_login_url' ) ) {
	function dbi_get_auto_login_url( $url, $user_id, $args = array(), $expires_in = null, $one_time = false ) {
		$autologin = \DeliciousBrains\WPAutoLogin\AutoLogin::instance();

		return $autologin->create_url( $url, $user_id, $args, $expires_in, $one_time );
	}
}
