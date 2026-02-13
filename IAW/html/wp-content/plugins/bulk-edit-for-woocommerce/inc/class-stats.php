<?php

class PBE_Stats {
	public function __construct() {

	}

	function get_limit_task() {
		return pbe()->task->get_limit();
	}

	public function track_task( $task_id ) {
		$option_key = 'pbe_track_task_' . $task_id;
		// $now = current_time( 'timestamp' );
		$now = time();
		add_option( $option_key, $now );
	}

	function count_task_in_this_month() {
		global $wpdb;
		$start_this_month = strtotime( 'midnight first day of this month' );
		$end_this_moth = strtotime( 'midnight first day of next month' );
		$sql = "SELECT count(option_id) as `total` FROM {$wpdb->options} WHERE `option_name` LIKE 'pbe_track_task_%' AND CAST(`option_value` AS UNSIGNED) >= %d and CAST(`option_value` AS UNSIGNED) <= %d";
		return $wpdb->get_var( $wpdb->prepare( $sql, $start_this_month, $end_this_moth ) ); // WPCS: unprepared SQL OK.
	}

	public function clean_up() {
		$start_this_month = strtotime( 'midnight first day of this month' );
		global $wpdb;
		$sql = "DELETE FROM {$wpdb->options} WHERE `option_name` LIKE 'pbe_track_task_%' AND CAST(`option_value` AS UNSIGNED) < %d";
		return $wpdb->get_var( $wpdb->prepare( $sql, $start_this_month ) ); // WPCS: unprepared SQL OK.
	}

	public function track_log( $log_id ) {

	}

	function count_task_finished() {
		$n = pbe()->task->count_tasks( "WHERE task_status IN ( 'completed', 'reverted') " );
		return intval( $n );
	}

	function count_task_pending() {
		$n = pbe()->task->count_tasks( "WHERE task_status IN ( 'pending', 'scheduled') " );
		return intval( $n );
	}

	function count_product() {
		global $wpdb;
		$table = $wpdb->prefix . 'pbe_logs';
		$sql = "SELECT count(*) as `total` FROM {$table} WHERE `object_type` <> 'variation' ";
		return $wpdb->get_var( $sql ); // WPCS: unprepared SQL OK.
	}

	function count_variation_product() {
		global $wpdb;
		$table = $wpdb->prefix . 'pbe_logs';
		$sql = "SELECT count(*) as `total` FROM {$table} WHERE `object_type` = 'variation' ";
		return $wpdb->get_var( $sql ); // WPCS: unprepared SQL OK.
	}


}
