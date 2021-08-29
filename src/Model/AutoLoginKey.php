<?php

namespace DeliciousBrains\WPAutoLogin\Model;

class AutoLoginKey {

	/**
	 * Name for table without prefix
	 *
	 * @var string
	 */
	protected static $table = 'dbrns_auto_login_keys';

	public $login_key;
	public $user_id;
	public $expires;

	public static function get_by_key( $key ) {
		global $wpdb;

		$table = self::$table;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->prefix{$table} WHERE login_key = %s ORDER BY CREATED DESC LIMIT 1", $key ) );
	}

	public static function delete_created( $date ) {
		global $wpdb;

		$table = self::$table;

		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->prefix{$table} WHERE created < %s AND expires = '0000-00-00 00:00:00'", $date ) );
	}

	public static function delete_expires( $date ) {
		global $wpdb;

		$table = self::$table;

		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->prefix{$table} WHERE expires < %s AND expires != '0000-00-00 00:00:00'", $date ) );
	}

	public function save() {
		global $wpdb;

		$data = array( 'login_key' => $this->login_key, 'user_id' => $this->user_id );

		if ( $this->expires ) {
			$data['expires'] = $this->expires;
		}

		$data['created'] = gmdate( 'Y-m-d H:i:s', time() );

		return $wpdb->insert( $wpdb->prefix . self::$table, $data );
	}
}