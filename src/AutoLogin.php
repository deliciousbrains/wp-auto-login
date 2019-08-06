<?php

namespace DeliciousBrains\WPAutoLogin;

use DeliciousBrains\WPAutoLogin\Model\AutoLoginKey;
use DeliciousBrains\WPMigrations\Database\Migrator;

class AutoLogin {

	/**
	 * Expiration time interval.
	 *
	 * @var int
	 */
	private $expires = 0;

	function __construct() {
		$this->expires = DAY_IN_SECONDS * 30 * 4;
	}

	public function init() {
		Migrator::instance();
		add_filter( 'dbi_wp_migrations_paths', array( $this, 'add_migration_path' ) );
		add_action( 'init', array( $this, 'handle_auto_login' ), 10 );
	}

	public function add_migration_path( $paths ) {
		$paths[] = dirname( __DIR__ ) . '/migrations';

		return $paths;
	}

	public function handle_auto_login() {
		$login_key = filter_input( INPUT_GET, 'login_key', FILTER_SANITIZE_STRING );
		$user_id   = filter_input( INPUT_GET, 'user_id', FILTER_VALIDATE_INT );

		if ( empty( $login_key ) || empty( $user_id ) ) {
			return;
		}

		// Limit Login Attempts plugin
		if ( function_exists( 'is_limit_login_ok' ) && ! is_limit_login_ok() ) {
			return;
		}

		$user = new \WP_User( $user_id );

		if ( ! $user->ID ) {
			return;
		}

		$user_id = $this->get_user_id_for_key( $login_key );

		if ( ! $user_id || $user_id != $user->ID ) {
			do_action( 'wp_login_failed', $user->user_login );

			return;
		}

		wp_set_auth_cookie( $user->ID );
		do_action( 'wp_login', $user->user_login, $user );

		$redirect = remove_query_arg( array( 'login_key', 'user_id' ) );
		wp_redirect( $redirect );
		exit;
	}

	/**
	 * @param string $key
	 *
	 * @return bool|int
	 */
	public function get_user_id_for_key( $key ) {
		$row = AutoLoginKey::where( 'login_key', $key )->first();

		if ( ! $row ) {
			return false;
		}

		if ( mysql2date( 'G', $row->created ) < time() - $this->expires ) {
			return false;
		}

		return $row->user_id;
	}

	/**
	 * @param int      $user_id
	 * @param null|int $expires_in Seconds
	 *
	 * @return bool|string
	 */
	public function create_key( $user_id, $expires_in = null ) {
		$this->remove_expired_keys();

		do {
			$key            = wp_generate_password( 40, false );
			$already_exists = AutoLoginKey::where( 'login_key', $key )->first();
		} while ( $already_exists );


		$loginkey            = new AutoLoginKey();
		$loginkey->login_key = $key;
		$loginkey->user_id   = $user_id;
		if ( $expires_in ) {
			$loginkey->expires = gmdate( 'Y-m-d H:i:s', time() + $expires_in );
		}

		$result = $loginkey->save();

		if ( ! $result ) {
			return false;
		}

		return $key;
	}

	protected function remove_expired_keys() {
		$expired_date = gmdate( 'Y-m-d H:i:s', ( time() - $this->expires ) );
		AutoLoginKey::where( 'created', '<', $expired_date )->where( 'expires', '0000-00-00 00:00:00' )->delete();

		$now_date = gmdate( 'Y-m-d H:i:s', time() );
		AutoLoginKey::where( 'expires', '<', $now_date )->delete();
	}

	/**
	 * @param string   $url
	 * @param int      $user_id
	 * @param array    $args
	 * @param null|int $expires_in Seconds
	 *
	 * @return string
	 */
	public function create_url( $url, $user_id, $args, $expires_in = null ) {
		$login_key = $this->create_key( $user_id, $expires_in );

		$args = array_merge( $args, array(
			'login_key' => $login_key,
			'user_id'   => $user_id,
		) );

		return add_query_arg( $args, $url );
	}

}
