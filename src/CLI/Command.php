<?php

namespace DeliciousBrains\WPAutoLogin\CLI;

use DeliciousBrains\WPAutoLogin\AutoLogin;

class Command extends \WP_CLI_Command {

	/**
	 * Generate auto-login URL for a user ID or user email
	 *
	 * ## OPTIONS
	 *
	 * [<user>]
	 * : User ID or email address
	 *
	 * [<url>]
	 * : URL to add the login key to
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @return null
	 */
	public function __invoke( $args, $assoc_args ) {
		if ( empty( $args[0] ) ) {
			return \WP_CLI::warning( 'User ID or email address not supplied' );
		}

		$field = is_numeric( $args[0] ) ? 'ID' : 'email';
		$user  = get_user_by( $field, $args[0] );
		if ( ! $user ) {
			return \WP_CLI::warning( 'User not found' );
		}


		$url = empty( $args[1] ) ? home_url() :  $args[1];
		$key_url = AutoLogin::instance()->create_url( $url, $user->ID );

		\WP_CLI::success( 'Auto-login URL generated: ' . $key_url );
	}
}
