<?php

class PBE_Schedule {
	function __construct() {
		add_filter( 'cron_schedules', array( $this, 'custom_cron_job_recurrence' ) );
		add_action( 'pbe_cron_do_task', array( $this, 'do_task' ) );
		add_action( 'pbe_cron_cleanup_db', array( $this, 'cleanup_db' ) );
	}

	/**
	 * Custom Cron Recurrences.
	 *
	 * @param array $schedules
	 * @return array
	 */
	function custom_cron_job_recurrence( $schedules ) {
		$schedules['every_minutes'] = array(
			'display' => __( 'Every minute', 'pbe' ),
			'interval' => 60,
		);
		return $schedules;
	}


	/**
	 * Schedule Cron Job Event.
	 *
	 * @return void
	 */
	function add_cron_job() {
		wp_clear_scheduled_hook( 'pbe_cron_do_task' );
		wp_clear_scheduled_hook( 'pbe_cron_cleanup_db' );
		wp_schedule_event( time() + 60, 'every_minutes', 'pbe_cron_do_task' );
		wp_schedule_event( time() + ( 6 * HOUR_IN_SECONDS ), 'twicedaily', 'pbe_cron_cleanup_db' );
	}

	function remove_all_cron_jobs() {
		wp_clear_scheduled_hook( 'pbe_cron_do_task' );
	}

	function do_task() {
		$doing = get_transient( '_pbe_last_run_task' );
		if ( ! $doing ) {
			pbe()->task->do_task();
		}

		update_option( '_pbe_last_cron', current_time( 'mysql' ) );

	}

	function cleanup_db() {
		pbe()->stats->clean_up();
	}

}


