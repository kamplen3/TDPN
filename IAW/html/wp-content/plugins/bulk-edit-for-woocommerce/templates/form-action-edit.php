<h3><?php _e('3. Choose how you want to edit', 'pbe'); ?></h3>
<form id="pbe-action-form" class="disabled" method="post">
	<div class="form-inner">
		<div class="action-row">
			<div class="action-field field-name">
				<select id="pbe-action-edit-field" name="edit_field_name">
					<?php
					$edit_field = isset($_GET['edit_field']) ? sanitize_text_field($_GET['edit_field']) : '';
					foreach (pbe()->conditions->get_filter_fields() as $key => $value) {
						if (isset($value['action_show_only']) && $value['action_show_only']) {
					?>
							<option disabled="disabled" value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value['title'] . ' (' . $value['action_show_only'] . ' )'); ?></option>
						<?php
						} elseif (!isset($value['edit']) || $value['edit']) {
						?>
							<option <?php selected($edit_field, $key); ?> value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value['title']); ?></option>
					<?php
						}
					}
					?>
				</select>
			</div>
			<div class="action-field field-action"></div>
			<div class="action-field field-action-val"> </div>
		</div>

		<div class="action-row extra_editor">
			<?php
			wp_editor(
				'',
				'edit_field_editor',
				array(
					'textarea_rows' => 20,
					'editor_class' => 'pbe_extra_editor',
				)
			);
			?>
		</div>

		<div class="action-row action-help-placeholders" style="display: none;">
			<?php _e('Available placeholders:', 'pbe'); ?>
			<ul>
				<?php
				foreach (pbe()->task->get_available_tags() as $key => $args) {
					echo '<li>' . sprintf('<strong>%1$s</strong>: %2$s', $key, $args['label']) . '</li>';
				}
				?>
			</ul>
		</div>


		<div id="pbe_skip_parent" class="action-row action-variation-condtions">
			<p>
				<label><input type="checkbox" class="pbe_skip_parent" value="yes" name="edit_field_extra[skip_parent]" /><?php _e('Apply for product variations only.', 'pbe'); ?></label>
			</p>
		</div>
		<!-- if_is_premium -->
		<div class="action-confirm-delete">
			<p class="agree-deletion-p"><label><input id="agree-deletion-products" type="checkbox"><?php _e('Agree to deletion products.', 'pbe'); ?></label></p>
			<p class="pbe-danger-notice"><?php _e('CAUTION! This task non-revertible.', 'pbe'); ?></p>
		</div>
		<!-- /if_is_premium -->

		<p class="action-buttons-row">
			<button type="submit" id="start-editing-button" name="start_editing" class="button has-icon button-primary"><span class="dashicons dashicons-controls-play"></span><span class="txt"><?php _e('Start Bulk Editing Now', 'pbe'); ?></span></button>
			<button type="button" id="schedule_editing" name="schedule_editing" class="schedule-editing-button has-icon button button-secondary"><span class="dashicons dashicons-calendar-alt"></span><span class="txt"><?php _e('Schedule Bulk Editing', 'pbe'); ?></span></button>
			<span class="action-schedule-extra pbe-hide">
				<input type="text" id="edit-schedule-datetime" name="edit_schedule_datetime" placeholder="<?php esc_attr_e('Enter your start date', 'pbe'); ?>" class="datetime-input" autocompleted="off" />
				<button type="button" name="schedule_cancel" class="schedule-cancel-button button button-secondary"><?php _e('Cancel', 'pbe'); ?></button>
				<button type="submit" id="edit-schedule-confirm" name="edit_schedule_confirm" value="schedule" class="button button-primary"><?php _e('Schedule', 'pbe'); ?></button>
			</span>
		</p>

		<input id="action_hidden_find_fields" name="edit_find_fields" type="hidden" value="" />
		<input id="action_hidden_find_variation_fields" name="edit_find_variation_fields" type="hidden" value="" />
		<input id="action_hidden_run_date" name="edit_run_date" type="hidden" value="" />
		<input id="action_found_posts" name="found_posts" type="hidden" value="" />
		<input name="action" type="hidden" value="pbe_new_task" />
		<?php wp_nonce_field('pbe_action', 'pbe_nonce'); ?>
	</div>
</form>