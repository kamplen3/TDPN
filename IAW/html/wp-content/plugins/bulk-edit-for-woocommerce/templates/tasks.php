<?php
$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
if ( $paged < 0 ) {
	$paged = 1;
}
$per_page = isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 0;
if ( $per_page <= 0 ) {
	$per_page = 50;
}
$status        = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$query         = pbe()->task->get_tasks( $paged, $per_page, $status );
$current_url   = pbe()->get_current_url();
$current_url   = remove_query_arg( array( 'task_id', 'paged', 'task_id' ), $current_url );
$action_number = pbe()->conditions->get_number_actions();
$action_string = pbe()->conditions->get_string_actions();
$all_status    = pbe()->task->get_status();
$date_format   = get_option( 'date_format' );
$time_format   = get_option( 'time_format' );
$format        = $date_format . ' ' . $time_format;

?>
<h3 class="wp-heading-inline"><?php _e( 'Task History', 'pbe' ); ?></h3>
<?php foreach ( pbe()->task->get_status() as $key => $value ) { ?>
	<?php 

		$classes = array(
			'task-status-item',
			'status-'. $key,
		);

		if ( $key == $status ) {
			$classes[] = 'active';
		} elseif ( ( 'all' == $key || ! $key ) && ! $status ) {
			$classes[] = 'active';
		}
		
	?>
	<a href="<?php echo esc_url( add_query_arg( array( 'status' => $key ), $current_url ) ); ?>" class="<?php echo esc_attr( join( ' ', $classes ) ); ?>"><?php echo esc_html( $value ); ?></a>
<?php } ?>

<table class="pbe-task-table wp-list-table widefat fixed- striped">
	<thead>
		<tr>
			<th class="col-date"><?php _e( 'Date Created', 'pbe' ); ?></th>
			<th class="col-status"><?php _e( 'Status', 'pbe' ); ?></th>
			<th class="col-product"><?php _e( 'Products', 'pbe' ); ?></th>
			<th class="col-conditions"><?php _e( 'Conditions', 'pbe' ); ?></th>
			<th class="col-editing"><?php _e( 'Field', 'pbe' ); ?></th>
			<th class="col-action"><?php _e( 'Editing', 'pbe' ); ?></th>
			<th class="col-actions">&nbsp;</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $query->tasks as $key => $row ) { ?>
			<?php $row = pbe()->task->decode_task_fields( $row ); ?>
			<tr class="task-row" data-status="<?php echo esc_attr( $row->task_status ); ?>">
				<td>
					<a href="<?php echo esc_url( add_query_arg( array( 'view' => 'task-details', 'task_id' => $row->task_id ), $current_url ) ); ?>" title="<?php esc_attr_e( 'View details', 'pbe' ); ?>">
						<?php echo date( $format, strtotime( $row->task_created ) ); ?>
					</a>
				</td>
				<td class="col-status"><?php

				echo '<span data-id="'.esc_attr( $row->task_id ).'" class="task-status active" data-status="'.esc_attr( $row->task_status ).'">';
				if ( $row->task_status && isset( $all_status[ $row->task_status ] ) ) {
					echo esc_html( $all_status[ $row->task_status ] );
				} else {
					echo esc_html( $all_status['pending'] );
				}
				echo '</span>';

				if ( in_array( $row->task_status, array( 'scheduled', 'canceled' ) ) && $row->task_run_date != $row->task_created ) {
					echo '<time datetime="' . esc_attr( $row->task_run_date ) . '">' . date( $format, strtotime( $row->task_created ) ) . '</time>';
				}
				
				?></td>
				<td><?php echo $row->number_product; ?></td>
				<td class="col-conditions"><?php
				
				pbe()->conditions->readable_task_conditions( $row );
				
				?></td>
				<td><?php pbe()->conditions->readable_task_field( $row ); ?></td>
				<th class="col-action">
					<?php pbe()->conditions->readable_task_action( $row ); ?>
					<?php pbe()->conditions->readable_task_val( $row ); ?>
				</th>
			
				<td class="col-actions">
					<?php if ( 'delete' !== $row->task_edit_field['type'] ) { ?>
						<?php if ( 'completed' == $row->task_status ) { ?>
						<a data-id="<?php echo esc_attr( $row->task_id ); ?>"  class="task-action task-undo" href="#" title="<?php esc_attr_e( 'Revert', 'pbe' ); ?>"><span class="dashicons dashicons-backup"></span></a>
						<?php } ?>
						<?php if ( in_array( $row->task_status, array( 'scheduled', 'canceled' ) ) ) { ?>
						<a data-id="<?php echo esc_attr( $row->task_id ); ?>"  class="task-action task-cancel" href="#" title="<?php esc_attr_e( 'Cancel', 'pbe' ); ?>"><span class="dashicons dashicons-controls-pause"></span></a>
						<a data-id="<?php echo esc_attr( $row->task_id ); ?>"  class="task-action task-continue" href="#" title="<?php esc_attr_e( 'Continue', 'pbe' ); ?>"><span class="dashicons dashicons-controls-play"></span></a>
						<?php } ?>
					<?php } ?>
					<a data-id="<?php echo esc_attr( $row->task_id ); ?>"  class="task-action task-del" href="#" title="<?php esc_attr_e( 'Delete', 'pbe' ); ?>"><span class="dashicons dashicons-dismiss"></span></a>
				</td>
			</tr>
		<?php } ?>
	</tbody>
</table>

<div class="wp-list-table-wrap">
<div class="tablenav">
	<div class="alignleft">
		<label for="pbed-preview-number-show" class=""><?php _e( 'Number of tasks per page:', 'pbe' ); ?> </label>
		<select id="pbe-task-number-show">
			<?php for ( $i = 5; $i <= 100; $i += 5 ) { ?>
			<option <?php selected( $per_page, $i ); ?> value="<?php echo esc_attr( add_query_arg( array( 'per_page' => $i ), $current_url ) ); ?>"><?php echo $i; ?></option>
			<?php } ?>
		</select>
	</div>
	<?php
	$paging = pbe()->paging( $query->max_num_pages, $query->found_posts, $paged, add_query_arg( array( 'paged' => '#number#' ), $current_url ) );
	if ( $paging ) {
		echo $paging;
	}
	?>
</div>
</div>
