<?php

class PBE_Task
{
	private $table_tasks = '';
	private $table_logs = '';
	private $all_filter_fields = array();
	private $limit_task = 30;

	function __construct()
	{
		global $wpdb;
		$this->table_tasks = $wpdb->prefix . 'pbe_tasks';
		$this->table_logs = $wpdb->prefix . 'pbe_logs';
	}

	public function get_limit()
	{
	
			return 5;
		
	}

	/**
	 * Get all available status.
	 *
	 * @return array
	 */
	function get_status()
	{
		$status = array(
			''          => __('All', 'pbe'),
			'pending'   => __('Running', 'pbe'),
			'completed' => __('Completed', 'pbe'),
			'scheduled' => __('Scheduled', 'pbe'),
			'reverted'  => __('Reverted', 'pbe'),
			'canceled'  => __('Canceled', 'pbe'),
		);
		return $status;
	}

	/**
	 * Get all available tags.
	 *
	 * @return array
	 */
	public function get_available_tags()
	{
		$tags = array(
			'{{title_to_slug}}' => array(
				'label' => __('Convert product title to slug', 'pbe'),
				'method' => 'get_name',
				'edit_callback' => 'sanitize_title',
			),
			'{{slug}}'          => array(
				'label' => __('Product slug', 'pbe'),
				'method' => 'get_slug',
			),
			'{{title}}'         => array(
				'label' => __('Product title', 'pbe'),
				'method' => 'get_name',
			),
			'{{sku}}'           => array(
				'label' => __('Product SKU', 'pbe'),
				'method' => 'get_sku',
			),
			'{{price}}'         => array(
				'label' => __('Price', 'pbe'),
				'method' => 'get_price',
			),
			'{{sale_price}}'    => array(
				'label' => __('Sale Price', 'pbe'),
				'method' => 'get_sale_price',
			),
		);

		return apply_filters('pbe_get_available_tags', $tags);
	}

	public function maybe_use_tags($string, $product_id)
	{
		$product = wc_get_product($product_id);
		if (!$product || $product->get_id() != $product_id) {
			$product = new stdClass();
		}

		foreach ($this->get_available_tags() as $tag => $args) {
			$value = '';
			if (method_exists($product, $args['method'])) {
				$value = call_user_func_array(array($product, $args['method']), array());
			}

			if (isset($args['edit_callback'])) {
				$value = call_user_func_array($args['edit_callback'], array($value));
			}

			$string = str_replace($tag, $value, $string);
		}

		return $string;
	}

	/**
	 * Check task status.
	 *
	 * @param boolean|string|array $ids Task ids to check.
	 * @return boolean|array
	 */
	function check_tasks($ids = false)
	{
		if (!$ids) {
			$ids = isset($_REQUEST['task_ids']) ? wp_unslash($_REQUEST['task_ids']) : '';
		}
		if (!is_array($ids)) {
			$ids = explode(',', $ids);
		}
		$ids = array_filter($ids);
		$ids = array_map('absint', $ids);

		set_transient('pbe_heart_beat', 'doing', 3000);
		if (empty($ids)) {
			return false;
		}

		global $wpdb;
		$sql = "SELECT task_id, task_status  FROM {$this->table_tasks} WHERE `task_id` IN (" . join(',', $ids) . ')';
		$r = $wpdb->get_results($sql); // WPCS: unprepared SQL OK.
		if (!$r) {
			return false;
		}

		$all_status = pbe()->task->get_status();
		foreach ($r as $k => $row) {
			if ($row->task_status && isset($all_status[$row->task_status])) {
				$r[$k]->label = esc_html($all_status[$row->task_status]);
			} else {
				$r[$k]->label = esc_html($all_status['pending']);
			}
		}
		return $r;
	}

	function maybe_handle_post()
	{
		$task_id = isset($_GET['task_id']) ? absint($_GET['task_id']) : false;
		if ($task_id) {
			$this->do_task($task_id);
		}
	}

	/**
	 * Handle task data to create new task.
	 *
	 * @param array|boolean $post_data If false data will get from $_POST.
	 * @return int
	 */
	public function handle_post($post_data = false)
	{
		if (!is_array($post_data) || empty($post_data)) {
			$post_data = $_POST;
		}

		$post_data = wp_parse_args(
			$post_data,
			array(
				'edit_field_name'            => '',
				'edit_field_action'          => '',
				'edit_field_value'           => null,
				'edit_field_editor'          => '',
				'edit_field_value_old'       => '',
				'edit_field_extra'           => '',
				'edit_find_variation_fields' => '',
				'edit_find_fields'           => '',
				'edit_schedule_datetime'     => '',
				'found_posts'                => '',
			)
		);

		// Check if use editor content.
		if (is_null($post_data['edit_field_value']) && $post_data['edit_field_editor']) {
			$post_data['edit_field_value'] = $post_data['edit_field_editor'];
		}

		$post_data = array_map('wp_unslash', $post_data);
		$edit_fields = pbe()->conditions->get_filter_fields();
		$edit_field = isset($edit_fields[$post_data['edit_field_name']]) ? $edit_fields[$post_data['edit_field_name']] : false;
		if (!$edit_field) {
			return 0; // the field do not exists.
		}
		if (isset($edit_field['edit']) && false === $edit_field['edit']) {
			return 0; // the field not editable.
		}

		if (is_array($post_data['edit_find_fields'])) {
			$post_data['edit_find_fields'] = json_encode($post_data['edit_find_fields']);
		}
		if (is_array($post_data['edit_find_variation_fields'])) {
			$post_data['edit_find_variation_fields'] = json_encode($post_data['edit_find_variation_fields']);
		}

		if (is_array($post_data['edit_field_extra'])) {
			$post_data['edit_field_extra'] = json_encode($post_data['edit_field_extra']);
		}

		$date_sql = current_time('mysql');

		$status = 'pending';

		if (!pbe()->is_date_time($post_data['edit_schedule_datetime'])) {
			$post_data['edit_schedule_datetime'] = $date_sql;
		} else {
			$status = 'scheduled';
		}

		$new_value = '';
		$old_value = '';

		if ('files' == $edit_field['type']) {
			$files = array();
			$post_data['edit_field_value'] = wp_parse_args(
				$post_data['edit_field_value'],
				array(
					'urls' => array(),
					'names' => array(),
				)
			);
			foreach ((array) $post_data['edit_field_value']['urls'] as $index => $val) {
				$hash = wp_generate_uuid4();
				$name = isset($post_data['edit_field_value']['names'][$index]) ? $post_data['edit_field_value']['names'][$index] : '';
				$files[$hash] = array(
					'id' => $hash,
					'name' => $name,
					'file' => $val,
				);
			}

			$post_data['edit_field_value'] = $files;
		}

		if (is_array($post_data['edit_field_value'])) {
			$new_value = json_encode($post_data['edit_field_value']);
		} else {
			$new_value = $post_data['edit_field_value'];
		}

		$edit_field = wp_parse_args(
			$edit_field,
			array(
				'title' => '',
				'type' => '',
				'_id' => '',
				'source' => '',
			)
		);

		$row_data = array(
			'task_status'          => $status,
			'task_new_val'         => $new_value,
			'task_old_val'         => $post_data['edit_field_value_old'],
			'task_edit_field'      => json_encode($edit_field),
			'task_val_type'        => $edit_field['type'],
			'task_action'          => $post_data['edit_field_action'],
			'task_find_fields'     => $post_data['edit_find_fields'],
			'task_variable_fields' => $post_data['edit_find_variation_fields'],
			'task_extra'           => $post_data['edit_field_extra'],
			'task_created'         => $date_sql,
			'task_run_date'        => $post_data['edit_schedule_datetime'],
		);

		$id = $this->insert($row_data);
		if ($id) {
			$this->add_task_posts($id);
			$count = $this->count_pending_product($id);
			$this->update_task($id, array('number_product' => $count));
		}

		return $id;
	}

	function is_skip_vartiations($task)
	{
		$task = $this->get_task($task);
		$edit_field = $this->get_task_edit_field($task);
		if (isset($edit_field['skip_variations']) && $edit_field['skip_variations']) {
			return true;
		} else {
			if (is_array($task->task_variable_fields)) {
				foreach ($task->task_variable_fields as $field) {
					if (is_array($field) && isset($field['field']) && 'none' == $field['field']) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Add posts to task.
	 *
	 * @since 0.0.1
	 * @since 0.0.3
	 *
	 * @param object $task
	 * @return string|bool|void
	 */
	function add_task_posts($task)
	{
		$have_posts = true;
		$per_page = $this->limit_task;
		$paged = 1;
		$variable_ids = array();
		$post_ids = array();

		if (!$task) {
			return false;
		}
		if (is_numeric($task)) {
			$task = $this->get_task($task);
		} else {
			$task = (object) $task;
		}

		if (!$task) {
			return;
		}

		$task_id = $task->task_id;

		$do_parent = true;
		if ('yes' == $task->task_extra['skip_parent']) {
			$do_parent = false;
		}

		do {
			$post_query = pbe()->search->search(
				$task->task_find_fields,
				array(
					'paged' => $paged,
					'posts_page_page' => $per_page,
				)
			);

			if ($post_query) {
				$found_posts = $post_query->found_posts;
				$max_num_pages = $post_query->max_num_pages;

				if ($found_posts <= $per_page) {
					$have_posts = false;
				}
				if ($paged <= $max_num_pages) {
					$paged++;
				}

				if ($post_query->have_posts()) {

					// var_dump( $post_query->found_posts ); die();


					while ($post_query->have_posts()) {
						$post_query->the_post();
						global $product;
						$product_id = $product->get_id();
						if ($do_parent) {
							$this->insert_log($task_id, $product_id, 'product', $product->get_name());
						}

						// ------------------.
						$child_query = false;

						if ($product->get_type() == 'variable') {
							$new_child_search = new PBE_Search();
							$child_query = $new_child_search->search_variations($product_id, $task->task_variable_fields);
						} else {

							$children_products = $product->get_children();
							if ($children_products) {
								$new_child_search = new PBE_Search();
								$child_query = $new_child_search->search_children(
									$task->task_variable_fields,
									array(
										'post__in' => $children_products,
									)
								);
							}
						}

						if ($child_query && $child_query->have_posts()) {
							while ($child_query->have_posts()) {
								$child_query->the_post();
								global $product;
								$this->insert_log($task_id, $product->get_id(), 'variation', $product->get_name());
							}
						}

						// ------------------.
					}
				} else {
					$have_posts = false;
				}
			} else {
				$have_posts = false;
			}
		} while ($have_posts);
	}

	function add_task_variable_posts($task, $variable_ids)
	{
		$have_posts = true;
		$per_page = $this->limit_task;
		$paged = 1;
		if (!$task) {
			return false;
		}
		if (is_numeric($task)) {
			$task = $this->get_task($task);
		} else {
			$task = (object) $task;
		}

		if (!$task) {
			return false;
		}

		$task_id = $task->task_id;

		if ($this->is_skip_vartiations($task)) {
			return false;
		}

		do {
			$post_query = pbe()->search->search(
				$task->task_variable_fields,
				array(
					'paged' => $paged,
					'posts_page_page' => $per_page,
					'post_type' => 'product_variation',
					'post_parent__in' => $variable_ids,
					'post_status' => 'any',
				),
				true
			);

			if ($post_query && $post_query->have_posts()) {
				$found_posts = $post_query->found_posts;
				$max_num_pages = $post_query->max_num_pages;

				if ($found_posts <= $per_page) {
					$have_posts = false;
				}
				if ($paged <= $max_num_pages) {
					$paged++;
				}

				while ($post_query->have_posts()) {
				}
			} else {
				$have_posts = false;
			}
		} while ($have_posts);
	}

	function get_log($task_id, $object_id)
	{
		global $wpdb;
		$sql = "SELECT * FROM {$this->table_logs} WHERE `task_id` = %d  AND `object_id` = %d";
		return $wpdb->get_row($wpdb->prepare($sql, $task_id, $object_id)); // WPCS: unprepared SQL OK.
	}

	function get_pending_logs($task_id, $paged = 1)
	{
		return $this->get_logs($task_id, $paged, $this->limit_task, 'pending');
	}

	function get_logs($task_id, $paged = 1, $per_page = 100, $status = '', $status_condition = '=')
	{
		global $wpdb;

		global $wpdb;
		if (!$per_page) {
			$per_page = 100;
		}

		$found_posts = $this->count_logs($task_id, $status, $status_condition);
		$max_num_pages = ceil($found_posts / $per_page);

		$where = '';
		if ($status && 'all' != $status) {
			$where .= $wpdb->prepare(" AND `status` {$status_condition} %s", $status); // WPCS: unprepared SQL OK.
		}

		$offset = ($paged - 1) * $per_page;
		$limit = "$offset, $per_page";

		$sql = "SELECT * FROM {$this->table_logs} WHERE `task_id` = %d {$where} ORDER BY date_added ASC  LIMIT {$limit}"; // WPCS: unprepared SQL OK.

		$r = $wpdb->get_results($wpdb->prepare($sql, $task_id)); // WPCS: unprepared SQL OK.
		return (object) array(
			'logs'         => ($r) ? $r : array(),
			'max_num_pages' => $max_num_pages,
			'found_posts'   => $found_posts,
		);
	}

	function count_logs($task_id, $status = '', $status_condition = '=')
	{
		global $wpdb;
		$where = '';
		if ($status && 'all' != $status) {
			$where .= $wpdb->prepare(" AND `status` {$status_condition} %s", $status); // WPCS: unprepared SQL OK.
		}

		$sql = "SELECT count( * ) as `num_product` FROM {$this->table_logs} WHERE `task_id` = %d {$where}";

		return intval($wpdb->get_var($wpdb->prepare($sql, absint($task_id)))); // WPCS: unprepared SQL OK.
	}


	function count_pending_product($task_id)
	{
		return $this->count_logs($task_id, 'pending');
	}

	function count_tasks($where = '')
	{
		global $wpdb;
		$sql = "SELECT count( * ) as `num_product` FROM {$this->table_tasks} {$where}";
		return $wpdb->get_var($sql); // WPCS: unprepared SQL OK.
	}


	function insert($data = array())
	{
		global $wpdb;
		$wpdb->insert($this->table_tasks, $data);
		$id = $wpdb->insert_id;
		if ($id && !is_wp_error($id)) {
			pbe()->stats->track_task($id);
		}
		return $id;
	}

	function update_task($task_id, $data = array())
	{
		global $wpdb;
		return $wpdb->update(
			$this->table_tasks,
			$data,
			array('task_id' => $task_id)
		);
	}

	function get_task_edit_field($task)
	{
		$field_id = false;
		$org_field = false;
		if (is_numeric($task)) {
			$task = $this->get_task($task);
			$field_id = $task->task_edit_field['_id'];
			$org_field = $task->task_edit_field;
		} elseif (is_object($task)) {
			$field_id = $task->task_edit_field['_id'];
			$org_field = $task->task_edit_field;
		}
		if (is_array($task)) {
			$field_id = $task['task_edit_field']['_id'];
			$org_field = $task['task_edit_field'];
		}
		if ($field_id) {
			$all_filter_fields = pbe()->conditions->get_filter_fields();
			if (isset($all_filter_fields[$field_id])) {
				return $all_filter_fields[$field_id];
			} else {
				return $org_field;
			}
		}
		return $org_field;
	}

	function decode_task_fields($task)
	{
		if ($task) {
			$task->task_edit_field = pbe()->maybe_json_decode($task->task_edit_field);
			$task->task_find_fields = pbe()->maybe_json_decode($task->task_find_fields);
			$task->task_variable_fields = pbe()->maybe_json_decode($task->task_variable_fields);
			$task->task_extra = pbe()->maybe_json_decode($task->task_extra);

			$task->task_edit_field = wp_parse_args(
				$task->task_edit_field,
				array(
					'title' => '',
					'type' => '',
					'_id' => '',
					'source' => '',
				)
			);

		

			$task->task_extra = wp_parse_args(
				$task->task_extra,
				array(
					'skip_parent'     => '',
					'number_round'    => '',
					'number_nearest'  => false,
					'set_sale_price'  => '',
					'pa_is_visible'   => '',
					'pa_is_variation' => '',
				)
			);
		}

		return $task;
	}

	public function get_task($task_id)
	{
		if (!$task_id) {
			return false;
		}

		if (is_object($task_id) || is_array($task_id)) {
			return (object) $task_id;
		}

		global $wpdb;
		$sql = "SELECT * FROM {$this->table_tasks} WHERE task_id = %d";
		$r = $wpdb->get_row($wpdb->prepare($sql, $task_id)); // WPCS: unprepared SQL OK.
		return $this->decode_task_fields($r);
	}

	function get_pending_task()
	{
		global $wpdb;
		$date = current_time('mysql');
		$sql = "SELECT * FROM {$this->table_tasks} WHERE task_status IN ('pending', 'scheduled' ) AND task_run_date <= '{$date}' ORDER BY task_run_date ASC LIMIT 1";
		$r = $wpdb->get_row($sql); // WPCS: unprepared SQL OK.
		return $this->decode_task_fields($r);
	}

	public function get_tasks($paged = 1, $per_page = 100, $status = 'all')
	{
		global $wpdb;
		if (!$per_page) {
			$per_page = 100;
		}
		$where = ' WHERE 1 ';
		if ($status && 'all' != $status) {
			$where .= $wpdb->prepare(' AND `task_status` = %s ', $status);
		}

		$found_posts = $this->count_tasks($where);
		$max_num_pages = ceil($found_posts / $per_page);

		$offset = ($paged - 1) * $per_page;
		$limit = "$offset, $per_page";

		$sql = "SELECT *  FROM {$this->table_tasks} {$where} ORDER BY task_created DESC LIMIT $limit";

		$r = $wpdb->get_results($sql); // WPCS: unprepared SQL OK.
		return (object) array(
			'tasks'         => $r,
			'max_num_pages' => $max_num_pages,
			'found_posts'   => $found_posts,
		);
	}

	/**
	 * Insert task log.
	 *
	 * @since 0.0.1
	 * @since 0.0.3
	 *
	 * @param int    $task_id
	 * @param int    $object_id
	 * @param string $object_type
	 * @param string $title
	 * @return bool
	 */
	function insert_log($task_id, $object_id, $object_type = 'product', $title = '')
	{
		$r = $this->get_log($task_id, $object_id);
		if ($r) {
			return true;
		}
		global $wpdb;
		$data = array(
			'task_id' => $task_id,
			'object_id' => $object_id,
			'object_type' => $object_type,
			'object_title' => $title,
			'status' => 'pending',
		);
		$wpdb->insert($this->table_logs, $data);
		return true;
	}

	function update_task_logs_status($task_id, $status = '')
	{
		global $wpdb;
		return $wpdb->update(
			$this->table_logs,
			array(
				'status' => $status,
			),
			array(
				'task_id' => $task_id,
			)
		);
	}

	function update_log($task_id, $object_id, $data = array())
	{
		global $wpdb;

		foreach ($data as $k => $v) {
			if (is_array($v)) {
				$data[$k] = json_encode($v);
			}
		}

		return $wpdb->update(
			$this->table_logs,
			$data,
			array(
				'task_id' => $task_id,
				'object_id' => $object_id,
			)
		);
	}

	function del($task_id)
	{
		global $wpdb;
		$wpdb->delete(
			$this->table_logs,
			array(
				'task_id' => $task_id,
			)
		);
		$wpdb->delete(
			$this->table_tasks,
			array(
				'task_id' => $task_id,
			)
		);
	}

	function delete_log($task_id, $object_id)
	{
		global $wpdb;
		return $wpdb->delete(
			$this->table_logs,
			array(
				'task_id' => $task_id,
				'object_id' => $object_id,
			)
		);
	}

	public function do_task($task_id = false)
	{
		if ($task_id) {
			$task = $this->get_task($task_id);
		} else {
			$task = $this->get_pending_task();
		}

		if (!$task) {
			return false;
		}

		// Skip reverted task.
		if ('reverted' == $task->task_status) {
			return false;
		}

		// Skip completed task.
		if ('completed' == $task->task_status) {
			return 'done';
		}

		update_option('_pbe_last_run_task', $task->task_id);
		set_transient('_pbe_last_run_task', $task->task_id, 3000);

		$now = current_time('timestamp');
		if (strtotime($task->task_run_date) > $now) { // Do not run it right now.
			return false;
		}

		$task->task_edit_field = wp_parse_args(
			$task->task_edit_field,
			array(
				'_id' => '',
				'type' => '',
				'source' => array(),
			)
		);

		$fields = pbe()->conditions->get_filter_fields();
		$field_id = $task->task_edit_field['_id'];
		if (isset($fields[$field_id])) {
			$task->task_edit_field = $fields[$field_id];
			$task->task_edit_field['_id'] = $field_id;
		}

		$edit_action = isset($this->task_edit_field['edit_action']) ? $this->task_ed['edit_action'] : true;

		$source = isset($task->task_edit_field['source']) ? $task->task_edit_field['source'] : array();

		if (!is_array($source) || empty($source)) {
			// No fields to edit, just update status.
			$this->update_task($task->task_id, array('task_status' => 'completed'));
			return 'done';
		}

		$source = wp_parse_args(
			$source,
			array(
				'type'              => '',
				'meta_key'          => '',
				'meta_compare'      => '',
				'meta_type'         => '',
				'field'             => '', // Apply for type as post.
				'field_type'        => '',
				'taxonomy'          => '',
				'edit_val_callback' => '',
			)
		);

		$source['_id'] = $field_id;

		if (!$source['type']) {
			// No field type edit.
			$this->update_task($task->task_id, array('task_status' => 'completed'));
			return 'done';
		}

		$logs_query = $this->get_pending_logs($task->task_id);
		if (!$logs_query) {
			$this->update_task($task->task_id, array('task_status' => 'completed'));
			return 'done';
		} else {

		
				$this->do_edit($logs_query, $task, $source);
			
		}

		$r = $logs_query->max_num_pages > 1 ? 'next' : 'done';
		if ('done' == $r) {
			$this->update_task($task->task_id, array('task_status' => 'completed'));
		}
		return $r;
	}

	function do_edit($logs_query, $task, $source)
	{
		if ($source['edit_val_callback'] && is_callable($source['edit_val_callback'])) {
			$task->task_new_val = call_user_func_array($source['edit_val_callback'], array($task->task_new_val, $task));
		}

		// 

		foreach ($logs_query->logs as $log) {
			$this->edit_product($log, $source, $task);
			// switch ($source['type']) {
			// 	case 'post':
			// 		$this->edit_post($log, $source, $task);
			// 		break;
			// 	case 'post_meta':
			// 		$this->edit_post_meta($log, $source, $task);
			// 		break;
			// 	case 'product_type':
			// 	case 'taxonomy':
			// 	case 'tax':
			// 		$this->edit_tax($log, $source, $task);
			// 		break;
			// }
		}
	}

	function do_revert($task_id)
	{
		// Revert completed tasks only.
		$query = $this->get_logs($task_id, 1, $this->limit_task, 'completed', '=');
		$n = 0;
		if ($query) {
			$n = count($query->logs);
			foreach ((array) $query->logs as $log) {
				$method = $log->edit_field;
				$product_id = $log->object_id;
				$product = wc_get_product($product_id);
				if (!$product) {
					$this->update_log($task_id, $log->object_id, array('status' => 'reverted', 'message' => __('Skip because product deleted', 'pbe')));
					return;
				}

				$value = maybe_unserialize($log->old_value);

				$log_status = 'reverted';

				if (is_callable([$product, 'set_' . $method])) {
					call_user_func_array([$product, 'set_' . $method], [$value]);
					try {
						$product->save();
					} catch (Exception $e) {
						$log_status = 'error';
						$message = $e->getMessage();
					}
				}

				$this->update_log($task_id, $log->object_id, array('status' => $log_status, 'message' => $message));
			} // End loop logs.
		}

		$r = array(
			'did_posts' => $n,
			'next_paged' => -1,
			'found_posts' => -1,
		);

		if ($query) {
			$r['max_pages'] = $query->max_num_pages;
			$r['found_posts'] = $query->found_posts;
			if (1 < $query->max_num_pages) {
				$r['next_paged'] = 2;
			}
		}

		return $r;
	}


	function mybe_round_number($value, $task)
	{
		if ('number' != $task->task_val_type) {
			return $value;
		}

		if (!is_numeric($value)) {
			return $value;
		}

		if (empty($task->task_extra) || !is_array($task->task_extra)) {
			return $value;
		}

		if (!isset($task->task_extra['number_round']) || 'yes' != $task->task_extra['number_round']) {
			return $value;
		}

		$number_nearest = isset($task->task_extra['number_nearest']) ? $task->task_extra['number_nearest'] : false;
		if (!$number_nearest || !is_numeric($number_nearest)) {
			return $value;
		}

		$round = floor($value);
		$round += $number_nearest;
		return $round;
	}

	protected function append_data($data_1, $data_2, $data_type = 'string')
	{
		if ('array' !=  $data_type) {
			return $data_1 . $data_2;
		}
		if (!is_array($data_1)) {
			$data_1 = [];
		}
		if (!is_array($data_2)) {
			$data_2 = [];
		}

		return array_merge($data_1, $data_2);
	}

	protected function prepend_data($data_1, $data_2, $data_type = 'string')
	{
		if ('array' !=  $data_type) {
			return $data_2 . $data_1;
		}
		if (!is_array($data_1)) {
			$data_1 = [];
		}
		if (!is_array($data_2)) {
			$data_2 = [];
		}

		return array_merge($data_2, $data_1);
	}

	function edit_product($log, $source, $task)
	{
		global $wpdb;

		$product = wc_get_product($log->object_id);
		$now = current_time('mysql');
		if (!$product) {
			$this->update_log(
				$log->task_id,
				$log->object_id,
				array(
					'status' => 'completed',
					'date_completed' => $now,
				)
			);
		}
		$method = $source['_id'];

		$old_value = '';
		$task_value = $this->maybe_use_tags(pbe()->maybe_json_decode($task->task_new_val), $log->object_id);
		$data_type  = 'string';
		if (in_array($task->task_val_type, ['products', 'tax', 'product_type', 'gallery', 'files'])) {
			$data_type  = 'array';
		}

	

		if (is_callable([$product, 'get_' . $method])) {
			$old_value  = call_user_func_array([$product, 'get_' . $method], ['edit']);
		}

		if ('sale_price' == $method) {
			if ($old_value <= 0) {
				$old_value = $product->get_regular_price();
			}
		}

		if ('sku' == $method) {
			if (!$old_value) {
				$old_value = $product->get_id();
			}
		}

		$save_value = $old_value;

		switch ($task->task_action) {
			case 'append':
			case 'add':
				$save_value = $this->append_data($old_value, $task_value, $data_type);
				break;
			case 'prepend':
				$save_value = $this->prepend_data($old_value, $task_value, $data_type);
				break;
			case 'replace':
				$task_old_val = $this->maybe_use_tags($task->task_old_val, $log->object_id);
				$save_value = str_replace($task_old_val, $task_value, $old_value);
				break;
			case 'increase_val':
				$save_value = floatval($save_value);
				$old_value = floatval($old_value);
				$task_value = floatval($task_value);
				$new_val = floatval($old_value);
				$save_value += floatval($task_value);
				break;

			case 'increase_percent':
				$save_value = floatval($save_value);
				$old_value = floatval($old_value);
				$task_value = floatval($task_value);
				$new_val = floatval($old_value);
				$save_value -= ($new_val * $task_value) / 100;
				break;

			case 'decrease_val':
				$save_value = floatval($save_value);
				$old_value = floatval($old_value);
				$task_value = floatval($task_value);
				$new_val = floatval($old_value);
				$save_value -= floatval($task_value);
				break;

			case 'decrease_percent':
				$save_value = floatval($save_value);
				$old_value = floatval($old_value);
				$task_value = floatval($task_value);
				$new_val = floatval($old_value);
				$save_value -= ($new_val * $task_value) / 100;
				break;

			case 'null':
			case 'set_null':
			case 'empty':
			case 'set_empty':
				$save_value = '';
				if ('array' == $data_type) {
					$save_value = [];
				}
				break;

			case 'set':
			case 'set_new':
				$save_value = $task_value;
				break;

			default:
				// code...
				break;
		}

		$save_value = $this->mybe_round_number($save_value, $task);
		$log_status = 'completed';
		$message = '';

		if (is_callable([$product, 'set_' . $method])) {
			call_user_func_array([$product, 'set_' . $method], [$save_value]);

			if (in_array($method, ['regular_price', 'price'])) {
				if (is_array($task->task_extra) && isset($task->task_extra['set_sale_price']) && 'yes' == $task->task_extra['set_sale_price']) {
					if ($save_value > $old_value) {
						$product->set_sale_price($old_value);
					}
				}
			}
			try {
				$product->save();
			} catch (Exception $e) {
				$log_status = 'error';
				$message = $e->getMessage();
			}
		} elseif ('product_type' == $method && 'variation' != $product->get_type()) {
			$terms = pbe()->maybe_json_decode($task->task_new_val);
			$taxonomy = $source['taxonomy'];
			$args = array('fields' => 'ids');
			if (is_array($terms)) {
				$terms = array_map('intval', $terms);
			} else {
				$terms = intval($terms);
			}
			$old_terms = wp_get_object_terms($product->get_id(), $taxonomy, $args);
			wp_remove_object_terms($product->get_id(), $old_terms, $taxonomy);
			wp_set_object_terms($product->get_id(), $terms, $taxonomy);
		}


		$this->update_log(
			$log->task_id,
			$log->object_id,
			array(
				'new_value'      => $save_value,
				'old_value'      => $old_value,
				'status'         => $log_status,
				'edit_field'     => $method,
				'date_completed' => $now,
				'message' => $message,
			)
		);

		do_action('pbe_task_after_edit_post', $log, $source['field'], $save_value, $old_value);
	}
}
