<?php

$n_task        = pbe()->stats->count_task_in_this_month();
$task_finished = pbe()->stats->count_task_finished();
$task_pending  = pbe()->stats->count_task_pending();
$n_product     = pbe()->stats->count_product();
$n_variation   = pbe()->stats->count_variation_product();
$limit         = pbe()->stats->get_limit_task();
$edit_link     = admin_url( 'admin.php?page=pbe-page&tab=bulk_edit' );

?>
<div class="pbed-box">
	<h3><?php _e( 'Account Status', 'pbe' ); ?></h3>
	<div class="pbed-box-inner">
		<div class="pbed-box-item">
			<div class="pebd-item-heading"><?php _e( 'Monthly Limit', 'pbe' ); ?></div>
			<div class="pebd-item-desc"><?php echo $n_task; ?>/<?php
		
				echo $limit;
			
			?></div>
		</div>
		<div class="pbed-box-item">
			<div class="pebd-item-heading"><?php _e( 'Total Tasks', 'pbe' ); ?></div>
			<div class="pebd-item-desc"><?php printf( _n( '%d Task finished', '%d Tasks finished', $task_finished, 'pbe' ), $task_finished ); ?></div>
			<div class="pebd-item-desc"><?php printf( _n( '%d Pending task', '%d Pending tasks', $task_pending, 'pbe' ), $task_pending ); ?></div>
		</div>
		<div class="pbed-box-item">
			<div class="pebd-item-heading"><?php _e( 'Total Edits', 'pbe' ); ?></div>
			<div class="pebd-item-desc"><?php printf( _n( '%d Product edited', '%d Products edited', $n_product, 'pbe' ), $n_product ); ?></div>
			<div class="pebd-item-desc"><?php printf( _n( '%d Variation edited', '%d Variations edited', $n_variation, 'pbe' ), $n_variation ); ?></div>
		</div>
	</div>
</div>


<div class="pbed-box box-actions">
	<h3 class="wp-heading-inline"><?php _e( 'Quick Start', 'pbe' ); ?></h3>
	<a class="task-status-item" href="<?php echo esc_url( admin_url( 'admin.php?page=pbe-page&tab=bulk_edit' ) ); ?>"><?php _e( 'Start Bulk edit now', 'pbe' ); ?></a>

	<div class="pbed-box-inner">
		<?php foreach ( pbe()->conditions->get_filter_fields() as $key => $field ) { ?>
			<?php if ( isset( $field['action_show_only'] ) && $field['action_show_only'] ) { ?>
				<div class="pbed-box-item show-only">
					<div class="pebd-item-heading"><?php echo esc_html( $field['title'] ); ?></div>
					<div class="pebd-item-desc"><?php echo $field['action_show_only']; // WPCS: XSS ok. ?></div>
				</div>
			<?php } elseif ( ! isset( $field['edit'] ) || $field['edit'] ) { ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'edit_field' => $key ), $edit_link ) ); ?>" class="pbed-box-item">
					<div class="pebd-item-heading"><?php echo esc_html( $field['title'] ); ?></div>
				</a>
			<?php } ?>
		<?php } ?>
	</div>
</div>
