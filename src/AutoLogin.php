<?php

namespace DeliciousBrains\WPAutoLogin;

use DeliciousBrains\WPAutoLogin\Model\AutoLoginKey;
use DeliciousBrains\WPAutoLogin\CLI\Command;
use DeliciousBrains\WPMigrations\Database\Migrator;

class AutoLogin {

	/**
	 * @var AutoLogin
	 */
	private static $instance;

	/**
	 * @var int
	 */
	protected $expires;

	/**
	 * Instantiate singleton instance
	 *
	 * @param string $command_name  Name to used for WP-CLI command.
	 * @param int    $expires       Key expiry in seconds - default 4 months.
	 *
	 * @return AutoLogin Instance
	 */
	public static function instance( $command_name = 'dbi', $expires = 10_368_000 ) {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof AutoLogin ) ) {
			self::$instance = new AutoLogin();
			self::$instance->init( $command_name, $expires );
		}

		return self::$instance;
	}


	/**
	 * Initialise the singleton instance
	 *
	 * @param string $command_name  Name to used for WP-CLI command.
	 * @param int    $expires       Key expiry in seconds.
	 */
	public function init( $command_name, $expires ) {
		Migrator::instance();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( $command_name . ' auto-login-url', Command::class );
		}

		$this->expires = $expires;

		add_filter( 'dbi_wp_migrations_paths', array( $this, 'add_migration_path' ) );
		add_action( 'init', array( $this, 'handle_auto_login' ) );
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
		$row = AutoLoginKey::get_by_key( $key );

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
			$already_exists = AutoLoginKey::get_by_key( $key );
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
		AutoLoginKey::delete_created( $expired_date );

		$now_date = gmdate( 'Y-m-d H:i:s', time() );
		AutoLoginKey::delete_expires( $now_date );
	}

	/**
	 * @param string   $url
	 * @param int      $user_id
	 * @param array    $args
	 * @param null|int $expires_in Seconds
	 *
	 * @return string
	 */
	public function create_url( $url, $user_id, $args = array(), $expires_in = null ) {
		$login_key = $this->create_key( $user_id, $expires_in );

		$args = array_merge( array(
			'login_key' => $login_key,
			'user_id'   => $user_id,
		), $args );

		return add_query_arg( $args, $url );
	}

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * class via the `new` operator from outside of this class.
	 */
	protected function __construct() {
	}

	/**
	 * As this class is a singleton it should not be clone-able
	 */
	protected function __clone() {
	}

	/**
	 * As this class is a singleton it should not be able to be unserialized
	 */
	protected function __wakeup() {
	}
}
