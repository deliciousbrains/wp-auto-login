<?php


namespace DeliciousBrains\WPMigrations\Database\Migrations;

use DeliciousBrains\WPMigrations\Database\AbstractMigration;

class AddSingleUseToKeysTable extends AbstractMigration {

	public function run() {
		global $wpdb;

		$wpdb->query( "
			ALTER TABLE `{$wpdb->prefix}dbrns_auto_login_keys`
			ADD COLUMN `one_time` tinyint NOT NULL DEFAULT 0;
		" );
	}
}
