<?php

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! function_exists( 'dbi_get_auto_login_url' ) ) {
	/**
	 * @param string $url
	 * @param int $user_id
	 * @param array<mixed> $args
	 * @param null|int $expires_in
	 *
	 * @return string
	 */
	function dbi_get_auto_login_url( $url, $user_id, $args = array(), $expires_in = null ) {
		$autologin = \DeliciousBrains\WPAutoLogin\AutoLogin::instance();

		return $autologin->create_url( $url, $user_id, $args, $expires_in );
	}
}