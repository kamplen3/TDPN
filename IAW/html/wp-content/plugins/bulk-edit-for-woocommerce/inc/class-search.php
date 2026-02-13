<?php

class PBE_Search
{
	private $query_args = array();
	private $post_status = array();
	private $taxonomies = array();
	private $custom_fields = array();

	public $post_query = array();
	public $meta_query = array();
	public $tax_query = array();
	/**
	 * WP Query
	 *
	 * @var WP_Query
	 */
	public $query = null;

	public $table_columns = array();
	public $paged = 1;
	public $fields = array();
	public $field_variations = array();
	public $has_none_field = false;

	public function __construct()
	{
		/**
		 * @see https://wordpress.stackexchange.com/questions/11822/how-to-limit-search-to-post-titles
		 */
	}

	function text_cond_to_sql_compare($cond, $value, $return = 'string')
	{
		if (!$value) {
			return false;
		}
		global $wpdb;
		$value = $wpdb->esc_like($value);
		$c = '';
		$condtions = array(
			'containts' => __('Containts', 'pbe'),
			'not_containts' => __('Does not containts', 'pbe'),
			'start_with' => __('Start with', 'pbe'),
			'end_with' => __('End with', 'pbe'),
			'empty' => __('Empty', 'pbe'),
		);

		$compare = '';
		switch ($cond) {
			case 'containts':
				$compare = 'LIKE';
				$c = "%{$value}%";
				break;

			case 'not_containts':
				$compare = 'NOT LIKE';
				$c = "%{$value}%";
				break;

			case 'start_with':
				$compare = 'LIKE';
				$c = "{$value}%";
				break;

			case 'end_with':
				$compare = 'LIKE';
				$c = "%{$value}";
				break;

			case 'empty':
				$compare = '=';
				$c = '';
				break;

			default:
				$compare = '=';
				$c = "{$value}";
				break;
		}

		if ('string' == $return) {
			$c = "'" . $c . "'";
			return $compare . ' ' . $c;
		}

		return array(
			'compare' => $compare,
			'value' => $c,
		);
	}

	/**
	 * Filter Search
	 *
	 * @see https://wordpress.stackexchange.com/questions/11822/how-to-limit-search-to-post-titles
	 *
	 * @param array|string $search
	 * @param WP_Query     $wp_query
	 * @return array|string
	 */
	function posts_bulk_edit_search($search, $wp_query)
	{
		global $wpdb;
		$search = array();

		foreach ($this->post_query as $args) {
			$key = $args['field'];
			$where_sql = false;
			$compare_sql = '';
			if (isset($args['type']) && 'date' == $args['type']) {
				$cond = $this->to_number_compare($args['cond']);
				if (!is_array($args['val'])) { // is single value.
					if ($this->is_date($args['val'])) {
						$compare_sql = $wpdb->prepare("{$cond} %s", $args['val']);
					}

					if ($compare_sql) {
						$where_sql = "DATE({$wpdb->posts}.{$key}) {$compare_sql}";
					}
				} else {
					$val = $args['val'];
					if (isset($val['from']) && isset($val['to']) && $this->is_date($val['from']) && $this->is_date($val['to'])) {
						$compare_sql = $wpdb->prepare("$cond %s AND %s", $val['from'] . ' 00:00:00', $val['to'] . ' 23:59:59');
					}

					if ($compare_sql) {
						$where_sql = "{$wpdb->posts}.{$key} {$compare_sql}";
					}
				}
			} else {
				$compare_sql = $this->text_cond_to_sql_compare($args['cond'], $args['val']);
				if ($compare_sql) {
					$where_sql = "{$wpdb->posts}.{$key} {$compare_sql}";
				}
			}

			if ($where_sql) {
				$search[] = $where_sql;
			}
		}

		$search = join(' AND ', $search);
		if ($search) {
			$search  = ' AND ' . $search;
		}
		return $search;
	}

	function reset()
	{
		$this->query_args       = array();
		$this->post_status      = array('publish');
		$this->taxonomies       = array();
		$this->custom_fields    = array();

		$this->post_query       = array();
		$this->meta_query       = array();
		$this->tax_query        = array();
		$this->query            = null;

		$this->table_columns    = array();
		$this->paged            = 1;
		$this->fields           = array();
		$this->query_args       = array();
	}

	public function search($fields, $args = array(), $force_status = false)
	{
		$this->reset();

		$default = array(
			'field' => '',
			'cond' => '',
			'val' => '',
		);

		$this->query_args = array_merge(wp_unslash($_REQUEST), $args);
		$paged = isset($this->query_args['paged']) ? absint($this->query_args['paged']) : 1;
		$this->query_args = wp_parse_args(
			$args,
			array(
				'paged' => $paged,
				'posts_page_page' => 0,
				'post_type' => 'product',
				'post_parent__in' => false,
				'post_status' => '',
			)
		);

		if (!$this->query_args['post_type']) {
			$this->query_args['post_type'] = 'product';
		}

		$this->paged = absint($this->query_args['paged']);
		$posts_page_page = isset($this->query_args['posts_page_page']) ? $this->query_args['posts_page_page'] : 0;
		if ($posts_page_page > 100) {
			$posts_page_page = 100;
		}

		$available_fields = pbe()->conditions->get_filter_fields();

		foreach ($fields as $fied_id =>  $field) {
			$field = wp_parse_args($field, $default);
			if ('not_set' == $field['cond']) {
				if (!$field['val']) {
					$field['val'] = -1;
				}
			}
			if ($field['field']) {
				if (isset($available_fields[$field['field']])) {
					$field_settings = $available_fields[$field['field']];
					$this->fields[] = $field;
					if ('custom_field' != $field['field']) {
						if (!$field_settings['skip_column']) {
							$this->set_dynamic_column($fied_id, $field_settings);
						}
					}
					$this->build_field($field, $field_settings);
				}
			}
		}

		if ($this->has_none_field) {
			return false;
		}

		$post_status = empty($this->post_status) ? 'publish' : $this->post_status;

		if ($force_status) {
			if ($this->query_args['post_status']) {
				$post_status = $this->query_args['post_status'];
			}
		}

		$this->query_args['posts_per_page']   = $posts_page_page;
		$this->query_args['paged']            = $this->paged;
		$this->query_args['post_status']      = $post_status;
		$this->query_args['no_found_rows']    = false;
		$this->query_args['suppress_filters'] = false;

		if (!empty($this->meta_query)) {
			$this->query_args['meta_query'] = $this->meta_query;
		}

		if (!empty($this->tax_query)) {
			$this->query_args['tax_query'] = $this->tax_query;
		}

		add_filter('posts_search', array($this, 'posts_bulk_edit_search'), 30, 2);
		$this->query = new WP_Query($this->query_args);
		remove_filter('posts_search', array($this, 'posts_bulk_edit_search'), 30);
		
		return $this->query;
	}

	function is_skip_vartiations($field_variations)
	{

		if (is_array($field_variations)) {
			foreach ($field_variations as $field) {
				if (is_array($field) && isset($field['field']) && 'none' == $field['field']) {
					return true;
				}
			}
		}

		return false;
	}

	function is_seach_all_vartiations($field_variations)
	{

		if (is_array($field_variations)) {
			foreach ($field_variations as $field) {
				if (is_array($field) && isset($field['field']) && 'all' == $field['field']) {
					return true;
				}
			}
		}

		return false;
	}


	function search_variations($parent_id, $field_variations, $args = array())
	{
		if (!$parent_id) {
			return false;
		}

		$args = wp_parse_args(
			$args,
			array(
				'paged' => null,
				'posts_per_page' => -1,
			)
		);
		if (!is_array($parent_id)) {
			$parent_id = array($parent_id);
		}

		$seach_fields = array();
		if (!$this->is_skip_vartiations($field_variations)) {
			$seach_fields = $field_variations;
		} else {
			return false; // Skip all variations.
		}

		if ($this->is_seach_all_vartiations($field_variations)) {
			$seach_fields = array();
		}

		return $this->search(
			$seach_fields,
			array(
				'paged' => $args['paged'],
				'posts_per_page' => $args['posts_per_page'],
				'post_type' => 'product_variation',
				'post_parent__in' => $parent_id,
				'post_status' => 'any',
			)
		);
	}


	function search_children($field_variations, $args = array())
	{

		$args = wp_parse_args(
			$args,
			array(
				'paged' => null,
				'posts_per_page' => -1,
				'post_type' => 'any',
				'post_status' => 'publish',
				'post_parent__in' => '',
				'post__in' => '',
			)
		);

		$seach_fields = array();
		if (!$this->is_skip_vartiations($field_variations)) {
			$seach_fields = $field_variations;
		} else {
			return false; // Skip all variations.
		}

		if ($this->is_seach_all_vartiations($field_variations)) {
			$seach_fields = array();
		}

		return $this->search(
			$seach_fields,
			$args
		);
	}

	public function set_meta_query($meta_key, $value, $type = 'CHAR', $compare = '=')
	{
		if (!isset($this->meta_query['relation'])) {
			$this->meta_query['relation'] = 'AND';
		}

		if ('_regular_price' != $meta_key) {
			$this->meta_query[] = array(
				'key' => $meta_key,
				'value' => $value,
				'type' => $type,
				'compare' => $compare,
			);
		} else {

			$meta_query = array(
				'relation' => 'OR',
			);

			foreach (array('_regular_price', '_price') as $index => $key) {
				$meta_query[$index] = array(
					'key' => $key,
					'value' => $value,
					'type' => $type,
					'compare' => $compare,
				);
			}

			$this->meta_query[] = $meta_query;
		}
	}


	public function set_tax_query($terms, $taxonomy, $field = 'term_id', $operator = 'IN')
	{
		if (empty($terms) || !$terms) {
			return;
		}
		if (!isset($this->tax_query['relation'])) {
			$this->tax_query['relation'] = 'AND';
		}

		$this->tax_query[] = array(
			'taxonomy' => $taxonomy,
			'field'    => $field,
			'terms'    => $terms,
			'operator' => $operator,
		);
	}

	public function to_number_compare($cond)
	{
		switch ($cond) {
			case '=':
			case '>':
			case '<':
			case '>=':
			case '<=':
				return $cond;
				break;
			case 'between':
				return 'BETWEEN';
				break;
			case 'not_between':
				return 'NOT BETWEEN';
				break;
			default:
				return false;
		}
	}

	public function validate_input_number($val, $cond)
	{

		$number = 0;
		$c = '';
		switch ($cond) {
			case '=':
			case '>':
			case '<':
			case '>=':
			case '<=':
				$val = trim($val);
				if (!is_numeric($val)) {
					return false;
				}

				$number = floatval($val);
				$c = $cond;

				break;
			case 'between':
			case 'not_between':
				if (!is_array($val) || count($val) < 2) {
					return false;
				}
				$number = array();
				if (isset($val['from'])) {
					$number[0] = floatval($val['from']);
				} else {
					$number[0] = reset($val);
				}

				if (isset($val['to'])) {
					$number[1] = floatval($val['to']);
				} else {
					$number[1] = end($val);
				}

				if ('not_between' == $cond) {
					$c = 'NOT BETWEEN';
				} else {
					$c = 'BETWEEN';
				}
				break;
			default:
				return false;
		}

		return array(
			'cond' => $c,
			'number' => $number,
		);
	}

	function validate_meta_string_compare($cond)
	{
		$compare = 'LIKE';
		switch ($cond) {
			case 'containts':
				$compare = 'LIKE';
				break;
			case 'not_containts':
				$compare = 'NOT LIKE';
				break;
			case 'empty':
				$compare = 'NOT EXISTS';
				break;
		}

		return $compare;
	}

	public function is_date($date)
	{
		if (preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $date)) {
			return true;
		} else {
			return false;
		}
	}

	public function value_callback($value, $value_callback = '')
	{
		$new_value = false;
		switch ($value_callback) {
			case 'date_to_timestamp':
				if (!$this->is_date($value)) {
					$new_value = false;
				} else {
					$new_value = strtotime($value);
				}
				break;
			case 'date_to_timestamp_start':
				if (!$this->is_date($value)) {
					$new_value = false;
				} else {
					$new_value = strtotime($value . ' 00:00:00');
				}
				break;
			case 'date_to_timestamp_end':
				if (!$this->is_date($value)) {
					$new_value = false;
				} else {
					$new_value = strtotime($value . ' 23:59:59');
				}

				break;
		}

		return apply_filters('pbe_search_value_callback', $new_value, $value, $value_callback);
	}

	public function build_field($field_settings, $settings)
	{
		$settings = wp_parse_args(
			$settings,
			array(
				'title' => '',
				'type' => '',
				'taxonomy' => '',
				'source' => array(),
			)
		);

		$field = $field_settings['field'];
		$cond = $field_settings['cond'];
		$type = $settings['type'];
		$val = $field_settings['val'];

		if ('none' == $field) {
			$this->has_none_field = true;
		}

		$field_id = $settings['_id'];
		$source = $settings['source'];

		if (!empty($source)) {

			if (isset($source['value_callback'])) {
				if ('date' == $type) {
					if (is_array($val)) {
						$val['from'] = $this->value_callback($val['from'], 'date_to_timestamp_start');
						$val['to'] = $this->value_callback($val['to'], 'date_to_timestamp_end');
					} else {
						$val = $this->value_callback($val, $source['value_callback']);
					}
				} else {
					$val = $this->value_callback($val, $source['value_callback']);
				}
			}

			switch ($source['type']) {
				case 'post': // All fields in table posts.
					switch ($source['field']) {
						case 'post_status':
							if (is_string($val)) {
								$this->post_status[$val] = $val;
							} else {
								foreach ((array) $val as $stt) {
									$stt = (string) $stt;
									$this->post_status[$stt] = $stt;
								}
							}
							break;

						default:
							$this->post_query[] = array(
								'field' => $source['field'],
								'cond' => $cond,
								'val' => $val,
								'type' => isset($source['field_type']) ? $source['field_type'] : '',
							);
					}

					break;
				case 'tax': // All post taxonomy.
					$type = isset($source['field']) ? $source['field'] : 'term_id';
					switch ($cond) {
						case 'not_set':
							$category_id   = get_option('default_product_cat', 0);
							if ('product_cat' === $source['taxonomy']) {
								$val = $category_id;
								$operator = 'IN';
							} else {
								$operator = 'NOT EXISTS';
							}
							break;
						case 'not_in':
							$operator = 'NOT IN';
							break;
						default:
							$operator = 'IN';
					}

					$this->set_tax_query($val, $source['taxonomy'], $type, $operator);
					break;
				case 'post_meta':  // All post meta.
					if ($val) {
						$source = wp_parse_args(
							$source,
							array(
								'type'         => '',
								'meta_key'     => '',
								'meta_type'    => '',
								'meta_compare' => '',
							)
						);
						if (in_array($type, ['number', 'date'])) {
							$validate = $this->validate_input_number($val, $cond);
							if ($validate) {
								$this->set_meta_query($source['meta_key'], $validate['number'], $source['meta_type'], $validate['cond']);
							}
						} else {
							if ($source['meta_compare']) {
								$compare = $source['meta_compare'];
							} else {
								$compare = $this->validate_meta_string_compare($cond);
							}
							$this->set_meta_query($source['meta_key'], $val, $source['meta_type'], $compare);
						}
					}
					break;
				case 'custom_field': // All dynamic custom field.
					$t = 'CHAR';
					$validate = null;
					if ('number' == $field_settings['meta_type'] || 'meta_number' == $field_settings['meta_type']) {
						$t = 'DECIMAL(15,5)';
						$validate = $this->validate_input_number($val, $cond);
						if ($validate) {
							$this->set_meta_query($field_settings['meta_key'], $validate['number'], $t, $validate['cond']);
						}
					} else {
						$compare = $this->validate_meta_string_compare($cond);
						$this->set_meta_query($field_settings['meta_key'], $val, $t, $compare);
					}

					if (!isset($field['skip_column']) || !$field['skip_column']) {
						$this->set_dynamic_column(
							'cf_' . $field_id,
							array(
								'title' => $field_settings['meta_key'],
								'_id' => isset($field_settings['_id']) ? $field_settings['_id'] : '____',
								'source' => array(
									'type' => 'post_meta',
									'meta_key' => $field_settings['meta_key'],
									'meta_type' => '',
								),
							)
						);
					}

					break;

				default:
					// code...
					break;
			}
		}
	}

	function set_dynamic_column($field_name, $args)
	{
		$list = array(
			'thumbnail' => '',
			'post_title' => '',
			'sku' => '',
			'regular_price' => '',
			'sale_price' => '',
			'price' => '',
		);
		if (!isset($list[$field_name])) {
			$this->table_columns[$field_name] = $args;
		}
	}

	/**
	 * Limit length of an arg.
	 *
	 * @param  string  $string Argument to limit.
	 * @param  integer $limit Limit size in characters.
	 * @return string
	 */
	protected function limit_length($string, $limit = 127)
	{
		$str_limit = $limit - 3;
		if (function_exists('mb_strimwidth')) {
			if (mb_strlen($string) > $limit) {
				$string = mb_strimwidth($string, 0, $str_limit) . '...';
			}
		} else {
			if (strlen($string) > $limit) {
				$string = substr($string, 0, $str_limit) . '...';
			}
		}
		return $string;
	}

	function dynamic_columns()
	{
		global $product,  $post;
		foreach ($this->table_columns as $key => $args) {

			$source = $args['source'];
?>
			<td scope="col" class="manage-column column-name column-meta-<?php echo esc_attr($key); ?>">
				<?php
				$method = 'get_' . $args['_id'];
				if (!in_array($source['type'], ['tax']) && is_callable([$product, $method])) {
					echo call_user_func_array([$product, $method], ['view']);
				} else {
					switch ($source['type']) {
						case 'post':
							echo esc_html($this->limit_length($post->{$source['field']}));
							break;
						case 'tax':
							the_terms($post->ID, $source['taxonomy'], '', ',', '');
							break;
						case 'post_meta':
							$meta_value = get_post_meta($post->ID, $source['meta_key'], true);
							if (!is_string($meta_value) && !is_numeric($meta_value)) {
								$meta_value = json_encode($meta_value, JSON_PRETTY_PRINT);
								echo '<pre>' . esc_textarea($meta_value) . '</pre>';
							} else {
								echo esc_html($this->limit_length($meta_value));
							}
							break;
						default:
							// code...
							break;
					}
				}



				?></td>
		<?php
		}
	}

	function the_product_row($child = false)
	{
		global $product,  $post;
		$product_name = $product->get_name();

		$sub = array();
		$sub[] = __('ID: ', 'pbe') . $product->get_id();

		$stock_html = '';
		if ($product->is_on_backorder()) {
			$stock_html = '<mark class="onbackorder">' . __('On backorder', 'pbe') . '</mark>';
		} elseif ($product->is_in_stock()) {
			$stock_html = '<mark class="instock">' . __('In stock', 'pbe') . '</mark>';
		} else {
			$stock_html = '<mark class="outofstock">' . __('Out of stock', 'pbe') . '</mark>';
		}

		if ($product->managing_stock()) {
			$stock_html .= ' (' . wc_stock_amount($product->get_stock_quantity()) . ')';
		}

		if ($stock_html) {
			$sub[] = $stock_html;
		}

		?>
		<tr class="<?php echo $child ? 'child-row' : 'top-row'; ?>">
			<td scope="col" class="manage-column column-name column-thumbnail">
				<?php

				if (has_post_thumbnail()) {
					the_post_thumbnail('thumbnail');
				} else {
					echo wc_placeholder_img('thumbnail');
				}

				?></td>
			<td scope="col" class="manage-column column-name column-primary"><span class="product-name">
					<?php
					if ($child) {
						echo $product_name;
					} else {
						echo edit_post_link($product_name);
						_post_states($post);
					}
					?><br />
					<?php
					echo join(' | ', $sub);
					?>
				</span>
			</td>
			<td scope="col" class="manage-column column-name column-sku"><?php echo $product->get_sku(); ?></td>
			<td scope="col" class="manage-column column-name column-price"><?php echo $product->get_price_html(); ?></td>
			<?php if (empty($this->table_columns)) { ?>
				<td scope="col" class="manage-column column-name column-category"><?php the_terms($post->ID, 'product_cat', '', ',', ''); ?></td>
				<td scope="col" class="manage-column column-name column-tag"><?php the_terms($post->ID, 'product_tag', '', ',', ''); ?></td>
			<?php } else { ?>
				<?php $this->dynamic_columns(); ?>
			<?php } ?>
		</tr>
		<?php
	}

	public function paging($which = 'top')
	{
		return pbe()->paging($this->query->max_num_pages, $this->query->found_posts, $this->paged);
	}

	public function the_ajax()
	{
		$has_variations = isset($_POST['has_variations']) && $_POST['has_variations'] ? 'yes' : false;
		$find_variations_type = isset($_POST['find_variations_type']) ? sanitize_text_field(wp_unslash($_POST['find_variations_type'])) : false;
		$posts_page_page = isset($_POST['posts_page_page']) ? absint($_POST['posts_page_page']) : 25;

		if ($posts_page_page <= 0) {
			$posts_page_page = get_option('pbed_preview_number_show');
		}

		if ($posts_page_page <= 0) {
			$posts_page_page = 20;
		}

		update_option('pbed_preview_number_show', $posts_page_page);

		$args = array(
			'posts_page_page' => $posts_page_page,
		);

		if ($has_variations) {
			$args['_find_variations_type'] = $find_variations_type;
		}

		$fields = isset($_POST['fields']) ? wp_unslash($_POST['fields']) : false;
		$this->field_variations = isset($_POST['field_variations']) ? wp_unslash($_POST['field_variations']) : false;

		$r = $this->search(
			$fields,
			$args
		);

		if (!$this->query) {
			$results = array(
				'found_posts' => 0,
				'max_num_pages' => 0,
				'html' => '',
				'fields' => $fields,
				'field_variations' => $this->field_variations,
			);
			return $results;
		}
		$results = array(
			'found_posts' => $this->query->found_posts,
			'max_num_pages' => $this->query->max_num_pages,
			'html' => '',
			'fields' => $fields,
			'field_variations' => $this->field_variations,
		);

		ob_start();

		$this->the_table();

		$results['html'] = ob_get_clean();
		return $results;
	}

	public function the_table()
	{

		
		if (!$this->query->have_posts()) {
		?>
			<div class="pbe-not-found">
				<p class="description"><?php esc_html_e('Nothing was found with your conditions. Try finding by change your filter condtions.', 'pbe'); ?></p>
			</div>
		<?php
			return;
		}

		$found_posts = $this->query->found_posts;
		$max_num_pages = $this->query->max_num_pages;
		$num_show = get_option('pbed_preview_number_show', 20);

		?>
		<div class="wp-list-table-wrap">
			<div class="tablenav">
				<div class="alignleft">
					<label for="pbed-preview-number-show" class=""><?php _e('Number of products per page:', 'pbe'); ?> </label>
					<select name="pbed_preview_show" id="pbed-preview-number-show">
						<?php for ($i = 5; $i <= 100; $i += 5) { ?>
							<option <?php selected($num_show, $i); ?> value="<?php echo $i; ?>"><?php echo $i; ?></option>
						<?php } ?>
					</select>
				</div>
				<?php
				$paging = $this->paging();
				if ($paging) {
					echo $paging;
				}
				?>
			</div>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-name column-thumbnail"><?php _e('Image', 'pbe'); ?></th>
						<th scope="col" class="manage-column column-name column-primary"><?php _e('Name', 'pbe'); ?></th>
						<th scope="col" class="manage-column column-name column-sku"><?php _e('Sku', 'pbe'); ?></th>
						<th scope="col" class="manage-column column-name column-price"><?php _e('Price', 'pbe'); ?></th>
						<?php if (empty($this->table_columns)) { ?>
							<th scope="col" class="manage-column column-name column-category"><?php _e('Categories', 'pbe'); ?></th>
							<th scope="col" class="manage-column column-name column-tag"><?php _e('Tags', 'pbe'); ?></th>
						<?php } else { ?>
							<?php
							foreach ($this->table_columns as $key => $args) {
							?>
								<th scope="col" class="manage-column column-name column-<?php echo esc_attr($key); ?>"><?php echo esc_attr($args['title']); ?></th>
							<?php
							}
							?>
						<?php } ?>
					</tr>
				</thead>

				<tbody>
					<?php while ($this->query->have_posts()) { ?>
						<?php

						$this->query->the_post();
						global $product;
						global $post;
						setup_postdata($post);
						$product = wc_get_product($post);
						$parent_id = $product->get_id();
						$this->the_product_row();
						$child_query = false;

						if ($product->get_type() == 'variable') {
							$new_child_search = new self();
							$child_query = $new_child_search->search_variations($parent_id, $this->field_variations);
						} else {

							$children_products = $product->get_children();
							if ($children_products) {
								$new_child_search = new self();
								$child_query = $new_child_search->search_children(
									$this->field_variations,
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
								global $post;
								setup_postdata($post);
								$product = wc_get_product($post->ID);
								$this->the_product_row(true);
							}
						}

						?>
					<?php } ?>

				</tbody>
			</table>
		</div>
<?php

	}
}
