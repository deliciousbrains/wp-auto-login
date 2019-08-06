<?php


namespace DeliciousBrains\WPMigrations\Database\Migrations;

use DeliciousBrains\WPMigrations\Database\AbstractMigration;

class CreateAutoLoginKeysTable extends AbstractMigration {

	public function run() {
		global $wpdb;

		$sql = "
			CREATE TABLE `{$wpdb->prefix}dbrns_auto_login_keys` (
			  `login_key` varchar(60) NOT NULL,
			  `user_id` bigint(20) NOT NULL,
			  `created` datetime NOT NULL,
			  `expires` datetime NOT NULL,
			  PRIMARY KEY (`login_key`)
			) {$this->get_collation()};
		";

		dbDelta( $sql );
	}
}