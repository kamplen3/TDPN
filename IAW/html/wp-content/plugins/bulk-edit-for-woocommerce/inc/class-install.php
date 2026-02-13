<?php

class PBE_Install {
	static $version = '0.0.2';
	public static function init() {
		self::create_tables();
	}

	public static function create_tables() {
		global $wpdb;
		$table_tasks = $wpdb->prefix . 'pbe_tasks';
		$table_logs = $wpdb->prefix . 'pbe_logs';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "
			CREATE TABLE `{$table_tasks}` (
				`task_id` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`task_status` varchar(30) NOT NULL,
				`number_product` int(15) NOT NULL,
				`task_edit_field` text NULL,
				`task_val_type` varchar(100) NOT NULL,
				`task_action` varchar(100) NOT NULL,
				`task_new_val` text NULL,
				`task_old_val` text NULL,
				`task_find_fields` text NULL,
				`task_variable_fields` text NULL,
				`task_extra` text NULL,
				`task_created` datetime default CURRENT_TIMESTAMP,
				`task_run_date` datetime default CURRENT_TIMESTAMP
			) {$charset_collate};
		";

		$sql2 = "
		CREATE TABLE `{$table_logs}` (
			`task_id` bigint(20) NOT NULL,
			`object_id` bigint(20) NOT NULL,
			`object_title` varchar(255) NULL,
			`object_type` varchar(20) NOT NULL,
			`edit_field` varchar(255) NOT NULL,
			`new_value` text NOT NULL,
			`old_value` text NOT NULL,
			`status` varchar(30) NOT NULL,
			`date_added` datetime default CURRENT_TIMESTAMP,
			`date_completed` datetime default NULL,
			`message` text default NULL
		  );
		";

		$sql3  = "ALTER TABLE `{$table_logs}` ADD CONSTRAINT `pbe_task_id` FOREIGN KEY (`task_id`) REFERENCES `{$table_tasks}` (`task_id`)";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		dbDelta( $sql2 );
		//$wpdb->hide_errors();
		$wpdb->query( $sql3 );
		update_option( 'pbe_db_version', self::$version );
	}

	static function upgrade() {
		self::update_db_0_0_2();
	}

	static function update_db_0_0_2() {
		global $wpdb;
		self::create_tables();
	}
}
