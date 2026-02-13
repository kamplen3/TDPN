<div class="pbe-box pbe-find postbox">
	<h3><?php _e( '1. Find products you want to edit', 'pbe' ); ?></h3>
	<form class="pbe-find-form" action="<?php echo esc_url( admin_url( 'admin-ajax.php?action=pbe_search_products' ) ); ?>" method="post">
	
		<div class="pbe-find-group pbe-form-box">
			<div id="pbe-find-conditions" class="pbe-find-conditions"></div>
			<p>
				<a class="button has-icon button-secondary pbe-add-search-cond" href="#"><span class="dashicons dashicons-plus"></span> <?php _e( 'Add more condition', 'pbe' ); ?></a>
			</p>
		</div>
		
		<div class="pbe-find-group pbe-g-variations pbe-form-box">
			<div class="pbe-find-vartiations">
				<div class="action-row action-variation-condtions">
					<div id="pbe-variation-filters" class="pbe-find-conditions"></div>
					<p>
						<a class="button has-icon button-secondary pbe-add-search-cond" href="#"><span class="dashicons dashicons-plus"></span> <?php _e( 'Add variable condition', 'pbe' ); ?></a>
					</p>
				</div>
			</div>
		</div>

		<div class="pbe-actions">
			<button class="button has-icon button-primary" type="submit" name="" value="find_products"><span class="dashicons dashicons-search"></span> <?php echo esc_attr_e( 'Find and preview products', 'pbe' ); ?></button>
		</div>
		<input type="hidden" name="paged" id="pbe-find-paged" value="1"/>
		<input type="hidden" name="has_variations" class="has_variations" value=""/>
		<input type="hidden" name="posts_page_page" id="pbe-find-posts_page_page" value=""/>
		<?php wp_nonce_field( 'pbe_action', 'pbe_nonce' ); ?>
	</form>
</div>

<div class="pbe-box pbe-previews postbox">
	<h3><?php _e( '2. Choose products you want to edit', 'pbe' ); ?></h3>
	<div id="pbe-previews" class="-">
		<p class="description"><?php _e( 'Click to button <strong>Find and preview products</strong> to show preview products.', 'pbe' ); ?></p>
	</div>
</div>

<div class="pbe-box pbe-action postbox">
	<?php

		$limit = pbe()->stats->get_limit_task();
		$n_task = pbe()->stats->count_task_in_this_month();
		if ( $n_task < $limit ) {
			pbe_settings()->load_template( 'form-action-edit' );
		} else {
			?>
			<h3><?php _e( 'Tasks Limit', 'pbe' ); ?></h3>
			<div><?php printf( __( 'You\'ve created %1$d/%2$d tasks in this month. Please upgrade to premium versiion add more tasks.', 'pbe' ), $n_task, $limit ); ?></div>
			<?php
		}
	
	?>
</div>
<?php
pbe_settings()->load_template( 'modal' );
pbe_settings()->load_template( 'tpl' );

