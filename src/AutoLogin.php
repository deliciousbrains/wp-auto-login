<?php

namespace DeliciousBrains\WPAutoLogin;

use DeliciousBrains\WPAutoLogin\CLI\Command;
use DeliciousBrains\WPAutoLogin\Model\AutoLoginKey;
use DeliciousBrains\WPMigrations\Database\Migrator;

/**
 * Main auto-login functionality
 */
class AutoLogin {

	/**
	 * Singleton instance
	 *
	 * @var AutoLogin
	 */
	private static $instance;

	/**
	 * Key expiry duration in seconds
	 *
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
	public static function instance( $command_name = 'dbi', $expires = 10368000 ) {
		if ( ! isset( self::$instance ) || ! ( self::$instance instanceof AutoLogin ) ) {
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

		$this->register_wpcli_commands( $command_name );

		$this->register_cron_actions();

		$this->expires = $expires;

		add_filter( 'dbi_wp_migrations_paths', [ $this, 'add_migration_path' ] );
		add_action( 'init', [ $this, 'handle_auto_login' ] );
	}

	/**
	 * Registers WP-CLI commands
	 *
	 * @param string $command_name The name of the first-level command to register sub-commands under.
	 *
	 * @return void
	 */
	public function register_wpcli_commands( $command_name ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( $command_name, Command::class );
		}
	}

	/**
	 * Adds cron action hooks
	 *
	 * @return void
	 */
	public function register_cron_actions() {
		// We'll make use of the built-in wp_scheduled_delete action.
		add_action( 'wp_scheduled_delete', [ $this, 'remove_expired_keys' ] );
	}

	/**
	 * Filter to modify paths for migration files for the wp-migrations package
	 *
	 * @param string[] $paths  List of paths passed in.
	 *
	 * @return string[]
	 */
	public function add_migration_path( $paths ) {
		$paths[] = dirname( __DIR__ ) . '/migrations';

		return $paths;
	}

	/**
	 * Action to (possibly) handle login on init hook
	 *
	 * @return void
	 */
	public function handle_auto_login() {
		$login_key = filter_input( INPUT_GET, 'login_key', FILTER_DEFAULT );
		$user_id   = filter_input( INPUT_GET, 'user_id', FILTER_VALIDATE_INT );

		if ( $login_key === false || $login_key === null || $user_id === false || $user_id === null ) {
			return;
		}

		if ( $login_key !== null && $login_key !== false ) {
			$login_key = sanitize_text_field( $login_key );
		}

		// Limit Login Attempts plugin.
		if ( function_exists( 'is_limit_login_ok' ) && ! is_limit_login_ok() ) {
			return;
		}

		$user = get_user_by( 'ID', $user_id );

		if ( $user === false ) {
			return;
		}

		$key = AutoLoginKey::get_by_key( $login_key );

		$user_id_for_key = $this->get_user_id_for_key( $key );

		if ( $user_id_for_key === false || $user_id_for_key != $user->ID ) {
			do_action( 'wp_login_failed', $user->user_login );

			return;
		}

		wp_set_auth_cookie( $user->ID );
		do_action( 'wp_login', $user->user_login, $user );

		$key->maybe_delete_one_time_key();

		$redirect = remove_query_arg( [ 'login_key', 'user_id' ] );
		wp_redirect( $redirect );
		exit;
	}

	/**
	 * @param string $key_to_find
	 *
	 * @return bool|int
	 */
	public function get_user_id_for_key( $key ) {
		if ( ! $key ) {
			return false;
		}

		if ( $key->is_expired() ) {
			return false;
		}

		return $key->user_id;
	}

	/**
	 * @param int          $user_id
	 * @param null|int     $expires_in Seconds
	 * @param null|boolean $one_time
	 *
	 * @return bool|string
	 */
	public function create_key( $user_id, $expires_in = null, $one_time = false ) {
		do {
			$key            = wp_generate_password( 40, false );
			$already_exists = AutoLoginKey::get_by_key( $key );
		} while ( $already_exists );

		$loginkey            = new AutoLoginKey();
		$loginkey->login_key = $key;
		$loginkey->user_id   = $user_id;
		$loginkey->one_time  = $one_time;
		if ( $expires_in ) {
			$loginkey->expires = gmdate( 'Y-m-d H:i:s', time() + $expires_in );
		} else {
			$loginkey->expires = gmdate( 'Y-m-d H:i:s', time() + $this->expires );
		}

		$result = $loginkey->save();

		if ( ! $result ) {
			return false;
		}

		return $key;
	}

	/**
	 * Purge expired keys from the database.
	 *
	 * @return void
	 */
	public function remove_expired_keys() {
		AutoLoginKey::delete_legacy_keys();
		AutoLoginKey::delete_expired_keys();
	}

	/**
	 * @param string       $url
	 * @param int          $user_id
	 * @param array        $args
	 * @param null|int     $expires_in Seconds
	 * @param null|boolean $one_time
	 *
	 * @return string
	 */
	public function create_url( $url, $user_id, $args = [], $expires_in = null, $one_time = false ) {
		$login_key = $this->create_key( $user_id, $expires_in, $one_time );

		$args = array_merge( [
			'login_key' => $login_key,
			'user_id'   => $user_id,
		], $args );

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
	public function __wakeup() {
	}
}
