<?php

$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
if ( $paged < 0 ) {
	$paged = 1;
}
$current_url = pbe()->get_current_url();


$task_id = isset( $_GET['task_id'] ) ? absint( $_GET['task_id'] ) : 0;
$current_url = pbe()->get_current_url();
$current_url = remove_query_arg( array( 'paged' ), $current_url );
$task = pbe()->task->get_task( $task_id );

if ( ! $task ) {
	?>
	<div class="pbe-task-not-found">
		<h3><?php _e( 'Task not found!', 'pbe' ); ?></h3>
		<a class="task-status-item" href="<?php echo esc_url( admin_url( 'admin.php?page=pbe-page&tab=tasks' ) ); ?>"><?php _e( 'Back to Tasks', 'pbe' ); ?></a>
	</div>
	<?php
	return;
}

$date_format   = get_option( 'date_format' );
$time_format   = get_option( 'time_format' );
$format        = $date_format . ' ' . $time_format;
$query         = pbe()->task->get_logs( $task_id, $paged, 50 );
$all_status    = pbe()->task->get_status();
$action_number = pbe()->conditions->get_number_actions();
$action_string = pbe()->conditions->get_string_actions();

$is_delete = 'delete' == $task->task_edit_field['type'];

?>
<div class="pbe-task-details">
	<h3 class="wp-heading-inline"><?php _e( 'Task Details', 'pbe' ); ?></h3>
	<a class="task-status-item" href="<?php echo esc_url( admin_url( 'admin.php?page=pbe-page&tab=tasks' ) ); ?>"><?php _e( 'Back to Tasks', 'pbe' ); ?></a>
	<span class="task-status active" data-id="<?php echo esc_attr( $task->task_id ); ?>" data-status="<?php echo esc_attr( $task->task_status ); ?>"><?php echo isset( $all_status[ $task->task_status ] ) ? $all_status[ $task->task_status ] : $task->task_status; ?></span>

	<?php if ( ! $is_delete ) { ?>
		<?php if ( 'completed' == $task->task_status ) { ?>
		<a class="task-status-item task-action task-undo" data-id="<?php echo esc_attr( $task->task_id ); ?>" href="#"><?php _e( 'Revert', 'pbe' ); ?></a>
		<?php } ?>
		<?php if ( 'scheduled' == $task->task_status ) { ?>
			<a class="task-status-item task-action task-cancel" data-id="<?php echo esc_attr( $task->task_id ); ?>" href="#"><?php _e( 'Cancel', 'pbe' ); ?></a>
		<?php } ?>
	<?php } ?>

	<a data-id="<?php echo esc_attr( $task->task_id ); ?>" class="task-status-item task-action task-del" href="#" title="<?php esc_attr_e( 'Delete', 'pbe' ); ?>"><?php _e( 'Delete', 'pbe' ); ?></a>

	<div class="clear">
		<div class="pbe-letf">
			<p><?php

			echo '<strong>';
			_e( 'Field:', 'pbe' );
			echo '</strong>';

			echo ' <span>';

			$field = $task->task_edit_field;
			if ( is_array( $field ) && ! empty( $field ) ) {
				echo esc_html( $field ['title'] );
			} else {
				_e( 'Nothing', 'pbe' );
			}

			echo '</span>';

			?></p>
			<?php if ( ! $is_delete ) { ?>
			<p><?php

			echo '<strong>';
			_e( 'Action:', 'pbe' );
			echo '</strong>';

			echo ' <span>';

			if ( 'number' == $task->task_val_type ) {
				echo esc_html( isset( $action_number[ $task->task_action ] ) ? $action_number[ $task->task_action ] : $task->task_action );
			} else {
				echo esc_html( isset( $action_string[ $task->task_action ] ) ? $action_string[ $task->task_action ] : $task->task_action );
			}

			echo '</span>';

			?></p>
			<?php } ?>
			<?php if ( 'replace' == $task->task_action ) { ?>
			<p>
				<?php
				echo '<strong>';
				_e( 'Find Text:', 'pbe' );
				echo '</strong>';
				echo ' <span>';
				echo esc_html( $task->task_old_val );
				echo '</span>';
				?>
			</p>
			<?php } ?>
			<?php if ( ! $is_delete ) { ?>
			<p>
				<?php
				echo '<strong>';
				_e( 'Value:', 'pbe' );
				echo '</strong>';
				echo ' <span>';

				if ( ! is_array( $task->task_new_val ) && ! empty( $task->task_new_val ) ) {
					if ( mb_strlen( $task->task_new_val ) > 100 ) {
						echo esc_html( mb_substr( $task->task_new_val, 0, 100 ) ) . '...';
					} else {
						echo esc_html( $task->task_new_val );
					}
				} else {
					echo '<span class="val-empty">';
					_e( 'Empty', 'pbe' );
					echo '</span>';
				}

				echo '</span>';

				?>
			</p>
			<?php } ?>
		</div><!-- /.pbe-letf -->

		<div class="pbe-right">
			<p>
				<?php
				echo '<strong>' . __( 'Date created:', 'pbe' ) . '</strong> ';
				echo date_i18n( $format, strtotime( $task->task_created ) );
				?>
			</p>
			<p>
				<?php
				echo '<strong>' . __( 'ID:', 'pbe' ) . '</strong> ';
				echo esc_html( $task->task_id );
				?>
			</p>
			<p>
				<strong><?php _e( 'Items:', 'pbe' ); ?></strong>
				<span><?php echo esc_html( $query->found_posts ); ?></span>
			</p>
		</div><!-- /.pbe-right -->

	</div><!-- /.clear -->


</div>

<table class="pbe-task-table wp-list-table widefat fixed striped tags">
	<thead>
		<tr>
			<th class="col-product-id" style="width: 60px;"><?php _e( '#', 'pbe' ); ?></th>
			<th class="col-product-name"><?php _e( 'Product', 'pbe' ); ?></th>
			<th class="col-new_value"><?php _e( 'New value', 'pbe' ); ?></th>
			<th class="col-old_value"><?php _e( 'Old value', 'pbe' ); ?></th>
			<th class="col-status"><?php _e( 'Status', 'pbe' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( (array) $query->logs as $key => $log ) { ?>
			<tr>
				<th><?php echo esc_html( $log->object_id ); ?></th>
				<td><?php
				if ( $log->object_title ) {
					echo esc_html( $log->object_title );
				} else {
					$product = wc_get_product( $log->object_id );
					if ( $product ) {
						echo esc_html( $product->get_name );
					} else {
						_e( 'Product removed', 'pbe' );
					}
				}
				?></td>
				<td>
					<?php echo esc_html( $log->new_value ); ?>
				</td>
				<td>
					<?php echo esc_html( $log->old_value ); ?>
				</td>
				<td>
					<?php echo esc_html( $log->status ); ?>
				</td>
			</tr>
		<?php } ?>
	</tbody>
</table>


<div class="wp-list-table-wrap">
	<div class="tablenav">
		<?php
		$paging = pbe()->paging( $query->max_num_pages, $query->found_posts, $paged, add_query_arg( array( 'paged' => '#number#' ), $current_url ) );
		if ( $paging ) {
			echo $paging;
		}
		?>
	</div>
</div>



