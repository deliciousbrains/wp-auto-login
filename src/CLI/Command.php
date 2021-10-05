<?php

namespace DeliciousBrains\WPAutoLogin\CLI;

use DeliciousBrains\WPAutoLogin\AutoLogin;
use DeliciousBrains\WPAutoLogin\Model\AutoLoginKey;

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
	 * [--expiry=<seconds>]
	 * : Number of seconds until key expires - defaults to 2 days
	 * ---
	 * default: 172800
	 * ---

	 * [--one-time]
	 * : Make the login key work only once
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @return null
	 */
	public function auto_login_url( $args, $assoc_args ) {
		if ( empty( $args[0] ) ) {
			return \WP_CLI::warning( 'User ID or email address not supplied' );
		}

		// Validate and fetch user
		$field = is_numeric( $args[0] ) ? 'ID' : 'email';
		$user  = get_user_by( $field, $args[0] );
		if ( ! $user ) {
			return \WP_CLI::warning( 'User not found' );
		}

		// Validate expiry
		if ( ! is_numeric( $assoc_args['expiry'] ) ) {
			\WP_CLI::error( 'Please specify a numeric value for the expiry.' );
		}

		$url = empty( $args[1] ) ? home_url() :  $args[1];
		$key_url = AutoLogin::instance()->create_url( $url, $user->ID, array(), $assoc_args['expiry'], $assoc_args['one-time'] );

		return \WP_CLI::success( 'Auto-login URL generated: ' . $key_url );
	}

	/**
	 * Purge expired auto-login keys from the database.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @return null
	 */
	public function purge_autologin_keys( $args, $assoc_args ) {
		$legacy_keys_deleted  = AutoLoginKey::delete_legacy_keys();
		$regular_keys_deleted = AutoLoginKey::delete_expired_keys();
		$total_keys_deleted   = $legacy_keys_deleted + $regular_keys_deleted;

		if ( false === $legacy_keys_deleted || false === $regular_keys_deleted ) {
			return \WP_CLI::error( 'An error occurred while deleting expired keys. ' . $total_keys_deleted . ' keys were deleted.' );
		}

		return \WP_CLI::success( $total_keys_deleted . ' keys were deleted.' );
	}
}
