<?php

namespace DeliciousBrains\WPAutoLogin\Model;

class AutoLoginKey {

	/**
	 * Name for table without prefix
	 *
	 * @var string
	 */
	protected static $table = 'dbrns_auto_login_keys';

	/**
	 * Constant for number of seconds legacy keys expire after - was 4 months(ish).
	 */
	const LEGACY_EXPIRY_SECONDS = 4 * 30 * DAY_IN_SECONDS;

	/**
	 * The key
	 *
	 * @var string
	 */
	public $login_key;

	/**
	 * User ID that the key is for
	 *
	 * @var int
	 */
	public $user_id;

	/**
	 * Key created date/time in MySQL format (UTC/GMT)
	 *
	 * @var string
	 */
	public $created;

	/**
	 * Key expiry date/time in MySQL format (UTC/GMT)
	 *
	 * @var string
	 */
	public $expires;

	public function __construct( $attributes = array() ) {
		foreach ( $attributes as $key => $value ) {
			$this->$key = $value;
		}
		if ( ! isset( $attributes['created'] ) ) {
			$this->created = gmdate( 'Y-m-d H:i:s', time() );
		}
	}

	public static function get_by_key( $key ) {
		global $wpdb;

		$table = self::$table;

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $wpdb->prefix{$table} WHERE login_key = %s ORDER BY CREATED DESC LIMIT 1", $key ),
			ARRAY_A
		);

		if ( empty( $row ) ) {
			return null;
		}

		return new self( $row );
	}

	/**
	 * Static method to delete legacy keys from the database.
	 * These keys will have an expiry of '0000-00-00 00:00:00', and will need to be
	 * expired based on their creation date.
	 *
	 * @return void
	 */
	public static function delete_legacy_keys() {
		global $wpdb;

		$table = self::$table;

		$expired_keys_created_before_time = gmdate( 'Y-m-d H:i:s', time() - self::LEGACY_EXPIRY_SECONDS );

		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->prefix{$table} WHERE created < %s AND expires = '0000-00-00 00:00:00'", $expired_keys_created_before_time ) );
	}

	/**
	 * Static method to delete expired keys from the database.
	 *
	 * @return void
	 */
	public static function delete_expired_keys() {
		global $wpdb;

		$table = self::$table;

		$sql_now = gmdate( 'Y-m-d H:i:s', time() );

		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->prefix{$table} WHERE expires < %s AND expires != '0000-00-00 00:00:00'", $sql_now ) );
	}

	public function save() {
		global $wpdb;

		$data = array(
			'login_key' => $this->login_key,
			'user_id'   => $this->user_id,
			'expires'   => $this->expires,
			'created'   => $this->created,
		);

		return $wpdb->insert( $wpdb->prefix . self::$table, $data );
	}

	public function is_expired() {
		// Handle legacy key for backwards compatibility.
		if ( $this->is_legacy_key() && $this->has_legacy_key_expired() ) {
			return false;
		}

		// Handle regular key
		if ( mysql2date( 'G', $this->expires ) > time() ) {
			return false;
		}

		return true;
	}

	public function is_legacy_key() {
		return '0000-00-00 00:00:00' === $this->expires;
	}

	public function has_legacy_key_expired() {
		// The old version always used 4 months expiry.
		return ( strtotime( $this->created ) + self::LEGACY_EXPIRY_SECONDS ) < time();
	}
}
