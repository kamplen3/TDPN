<?php
class PBE_Plugin
{
	/**
	 * The single instance of the class
	 *
	 * @var PBE_Plugin
	 * @since 0.0.1
	 */
	protected static $_instance = null;
	/**
	 * The plugin dir
	 *
	 * @var string
	 * @since 0.0.1
	 */
	protected $plugin_dir;
	/**
	 * The plugin url
	 *
	 * @var string
	 * @since 0.0.1
	 */
	protected $plugin_url;
	/**
	 * The plugin version
	 *
	 * @var string
	 * @since 0.0.1
	 */
	public $plugin_version;
	/**
	 * Admin var
	 *
	 * @var PBE_Admin
	 * @since 0.0.1
	 */
	protected $admin = null;

	/**
	 * Admin var
	 *
	 * @var PBE_Search
	 * @since 0.0.1
	 */
	public $search = null;

	/**
	 * Admin var
	 *
	 * @var PBE_Task
	 * @since 0.0.1
	 */
	public $task = null;

	/**
	 * Admin var
	 *
	 * @var PBE_Conditions
	 * @since 0.0.1
	 */
	public $conditions = null;

	/**
	 * Admin var
	 *
	 * @var PBE_Schedule
	 * @since 0.0.1
	 */
	public $schedule = null;

	/**
	 * Admin var
	 *
	 * @var PBE_Stats
	 * @since 0.0.1
	 */
	public $stats = null;

	public static function instance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * PBE_Plugin Constructor.
	 */
	public function __construct()
	{

		$this->plugin_dir = PBE_DIR;
		$this->plugin_url = PBE_URL;
		$this->plugin_version = $this->get_version();

		if (!class_exists('WooCommerce')) { // Check if WooCommerce install and active.
			add_action('admin_notices', array($this, 'admin_notice_woocommerce'));
		}

		$this->includes();
		$this->setup();
	}

	function get_version()
	{
		if (!function_exists('get_plugin_data')) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}
		$plugin_data = get_plugin_data(PBE_PLUGIN_FILE);
		return $plugin_data['Version'];
	}

	function admin_notice_woocommerce()
	{
?>
		<div class="notice notice-warning is-dismissible">
			<p><?php

			
					_e('<strong>Bulk Edit for WooCommerce</strong> requires WooCommerce to be activated', 'pbe');
				

				?></p>
		</div>
<?php
	}

	/**
	 * Load text domain
	 *
	 * @return void
	 */
	public function load_textdomain()
	{
		load_plugin_textdomain('pbe', false, $this->plugin_dir . '/languages');
	}

	public function includes()
	{
		require_once $this->plugin_dir . 'inc/class-upgrade.php';
		require_once $this->plugin_dir . 'inc/class-conditions.php';
		require_once $this->plugin_dir . 'inc/class-stats.php';
		require_once $this->plugin_dir . 'inc/class-task.php';
		require_once $this->plugin_dir . 'inc/class-tax-query.php';
		require_once $this->plugin_dir . 'inc/class-meta-query.php';
		require_once $this->plugin_dir . 'inc/class-search.php';
		require_once $this->plugin_dir . 'inc/class-schedule.php';
		require_once $this->plugin_dir . 'inc/class-setting.php';
	
	}

	function strtotime($value, $task)
	{
		return strtotime($value);
	}

	function maybe_json_decode($original)
	{
		if (!is_string($original)) {
			return (array) $original;
		}
		$r = json_decode($original, true);
		if (json_last_error() === JSON_ERROR_NONE) {
			return $r;
		}
		return $original;
	}


	/**
	 * Method setup.
	 */
	public function setup()
	{
		$this->search     = new PBE_Search();
		$this->stats      = new PBE_Stats();
		$this->task       = new PBE_Task();
		$this->conditions = new PBE_Conditions();
		$this->schedule   = new PBE_Schedule();

		add_action('init', array($this, 'load_textdomain'));
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('wp_ajax_pbe_search_term', array($this, 'ajax_terms'));
		add_action('wp_ajax_pbe_select_products', array($this, 'ajax_products'));
		// add_action( 'wp_ajax_nopriv_pbe_search_term', array( $this, 'ajax' ) );
		add_action('wp_ajax_pbe_search_products', array($this, 'ajax_search_products'));

		add_action('wp_ajax_pbe_heart_beat', array($this, 'ajax_heart_beat'));
		add_action('wp_ajax_pbe_task_cancel', array($this, 'ajax_task_cancel'));
		add_action('wp_ajax_pbe_task_continue', array($this, 'ajax_task_continue'));
		add_action('wp_ajax_pbe_task_revert', array($this, 'ajax_task_revert'));
		add_action('wp_ajax_pbe_task_del', array($this, 'ajax_task_del'));

		add_action('wp_ajax_pbe_new_task', array($this, 'ajax_new_task'));
		add_action('wp_ajax_pbe_do_task', array($this, 'ajax_do_task'));
		add_action('pbe_before_page_setting_content', array($this->task, 'maybe_handle_post'));

		do_action('pbe_loaded');
	}

	function verify_nonce()
	{
		$nonce = isset($_REQUEST['pbe_nonce']) ? $_REQUEST['pbe_nonce'] : false;
		if (!wp_verify_nonce($nonce, 'pbe_action')) {
			die('Security check');
		}
	}

	function ajax_task_del()
	{
		$this->verify_nonce();
		$task_id = isset($_REQUEST['task_id']) ? absint($_REQUEST['task_id']) : 0;
		$this->task->del($task_id);
		wp_send_json_success();
		die();
	}

	function ajax_task_revert()
	{
		$this->verify_nonce();
		$task_id = isset($_REQUEST['task_id']) ? absint($_REQUEST['task_id']) : 0;
		$this->task->update_task($task_id, array('task_status' => 'reverted'));
		$r = $this->task->do_revert($task_id);
		$r['label'] = __('Reverted', 'pbe');
		$r['status'] = 'reverted';
		wp_send_json_success($r);
		die();
	}

	function ajax_task_continue()
	{
		$this->verify_nonce();
		$task_id = isset($_REQUEST['task_id']) ? absint($_REQUEST['task_id']) : 0;
		$this->task->update_task($task_id, array('task_status' => 'scheduled'));
		wp_send_json_success(
			array(
				'label' => __('Scheduled', 'pbe'),
				'status' => 'scheduled',
			)
		);
		die();
	}

	function ajax_task_cancel()
	{
		$this->verify_nonce();
		$task_id = isset($_REQUEST['task_id']) ? absint($_REQUEST['task_id']) : 0;
		$this->task->update_task($task_id, array('task_status' => 'canceled'));
		wp_send_json_success(
			array(
				'label' => __('Cancled', 'pbe'),
				'status' => 'canceled',
			)
		);
		die();
	}

	function ajax_heart_beat()
	{
		$this->verify_nonce();
		pbe()->task->do_task();

		$r = $this->task->check_tasks();
		if (!$r) {
			wp_send_json_error($r);
		} else {
			wp_send_json_success($r);
		}

		die();
	}

	public function is_date_time($date)
	{
		if (class_exists('DateTime')) {
			$format = 'Y-m-d H:i:s';
			$d = DateTime::createFromFormat($format, $date);
			return $d && $d->format($format) == $date;
		}
		return true;
	}

	function ajax_new_task()
	{
		$this->verify_nonce();
		$task_id = $this->task->handle_post();
		$url = false;
		if ($task_id > 0) {
			$title = __('Bulk Editing Task Created!', 'pbe');
			$url = admin_url('admin.php?page=pbe-page&tab=tasks&view=task-details&task_id=' . $task_id);
		} else {
			$title = __('Opp! Can not create task', 'pbe');
		}

		$data = array(
			'task_id' => $task_id,
			'title' => $title,
			'running_title' => __('Running task...', 'pbe'),
			'url' => $url,
		);
		if ($task_id > 0) {
			wp_send_json_success($data);
		} else {
			wp_send_json_error($data);
		}

		die();
	}

	function ajax_do_task()
	{
		$this->verify_nonce();
		$task_id = isset($_REQUEST['task_id']) ? absint($_REQUEST['task_id']) : 0;
		$task = $this->task->get_task($task_id);
		$url = false;

		$url = admin_url('admin.php?page=pbe-page&tab=tasks&view=task-details&task_id=' . $task_id);

		$data = array(
			'task_id' => $task_id,
			'title' => __('Couldn\'t run this task.', 'pbe'),
			'url' => $url,
			'status' => 'done',
		);

		if (!$task) {
			wp_send_json_success($data);
		}
		$do_task = $this->task->do_task($task_id);

		if ('done' == $do_task) {
			$data['title'] = __('Task completed!', 'pbe');
		} elseif ('next' == $do_task) {
			$data['title'] = __('Running task...', 'pbe');
			$data['status'] = 'next';
		}

		if ('scheduled' == $task->task_status) {
			$data['title'] = __('Task scheduled! ', 'pbe');
		}

		wp_send_json_success($data);

		die();
	}

	public function ajax_search_products()
	{
		$this->verify_nonce();
		wp_send_json_success($this->search->the_ajax());
		die();
	}

	function ajax_terms()
	{
		$this->verify_nonce();
		$tax = isset($_REQUEST['tax']) ? sanitize_text_field($_REQUEST['tax']) : '';
		$q = isset($_REQUEST['q']) ? sanitize_text_field($_REQUEST['q']) : null;

		$result_data = array();
		if ($tax) {
			$terms = get_terms(
				$tax,
				array(
					'hide_empty' => false,
					'search' => $q,
				)
			);

			if ($terms && !is_wp_error($terms)) {
				foreach ($terms as $key => $term) {
					$result_data[$key] = array(
						'id' => $term->term_id,
						'text' => $term->name,
						'number' => 100,
					);
				}
			}
		}

		$data = array(
			'results' => $result_data,
			'pagination' => array(
				'more' => false,
			),
		);
		wp_send_json($data);
		die();
	}

	function ajax_products()
	{
		$this->verify_nonce();
		$q = isset($_REQUEST['q']) ? sanitize_text_field($_REQUEST['q']) : null;

		$result_data = array();

		$products = wc_get_products(array('s' => $q));

		if ($products && !is_wp_error($products)) {
			foreach ($products as $key => $product) {
				$sku = $product->get_sku();
				$result_data[$key] = array(
					'id' => $product->get_id(),
					'text' => ($sku) ? sprintf('%1$s (%2$s)', $product->get_name(), $sku) : $product->get_name(),
				);
			}
		}

		$data = array(
			'results' => $result_data,
			'pagination' => array(
				'more' => false,
			),
		);
		wp_send_json($data);
		die();
	}

	/**
	 * Method enqueue_scripts
	 */
	public function admin_enqueue_scripts()
	{
		if (!class_exists('WooCommerce')) { // Check if WooCommerce install and active.
			return;
		}

		wp_register_style('jquery-ui-style', WC()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css', array(), WC_VERSION);
		wp_enqueue_style('jquery-ui-style');
		wp_enqueue_script('select2', $this->plugin_url . 'assets/js/select2.full.min.js', array('jquery'), $this->plugin_version, true);
		wp_enqueue_script('jquery-ui-timepicker', $this->plugin_url . 'assets/js/jquery-ui-timepicker-addon.js', array('jquery', 'jquery-ui-datepicker'), $this->plugin_version, true);
		wp_enqueue_script('pbe-admin', $this->plugin_url . 'assets/js/admin.js', array('jquery', 'underscore', 'jquery-ui-datepicker', 'jquery-ui-timepicker', 'select2'), $this->plugin_version, true);
		wp_enqueue_style('pbe-admin', $this->plugin_url . 'assets/css/admin.css', array(), $this->plugin_version);
		wp_enqueue_style('select2', $this->plugin_url . 'assets/css/select2.css', array(), $this->plugin_version);

		$config = array(
			'ajax_url'                => admin_url('admin-ajax.php'),
			'nonce'                   => wp_create_nonce('pbe_action'),
			'filter_fields'           => $this->conditions->get_filter_fields(),
			'filter_variable_fields'  => $this->conditions->get_variable_filter_fields(),
			'number_conditions'       => $this->conditions->get_filter_number_conditions(),
			'string_conditions'       => $this->conditions->get_filter_string_conditions(),
			'tax_conditions'          => $this->conditions->get_filter_tax_conditions(),
			'custom_fields'           => $this->conditions->get_custom_fields(),
			'edit_string_placeholder' => __('Your text..', 'pbe'),
			'edit_number_placeholder' => __('Your number...', 'pbe'),
			'comfirm_delete'          => __('Delete this task ?', 'pbe'),
			'comfirm_revert'          => __('Revert this task ?', 'pbe'),
			'reverting_text'          => __('Revert...', 'pbe'),
			'loading_text'            => __('Loading...', 'pbe'),
			'creating_task_text'      => __('Creating task...', 'pbe'),
			'editing_field'           => isset($_GET['edit_field']) ? sanitize_text_field(wp_unslash($_GET['edit_field'])) : '',
			'X-WP-Nonce'    	=> wp_create_nonce('wp_rest'),
		);

	

		wp_localize_script(
			'pbe-admin',
			'PBE',
			$config
		);
	}


	public static function install()
	{
		require_once PBE_DIR . 'inc/class-install.php';
		PBE_Install::init();
		if (pbe()->schedule) {
			pbe()->schedule->add_cron_job();
		}
	}

	public static function uninstall()
	{
		if (pbe()->schedule) {
			pbe()->schedule->remove_all_cron_jobs();
		}
	}

	function get_current_url()
	{
		return wp_unslash($_SERVER['REQUEST_URI']);
	}


	public function paging($max_num_pages, $found_posts, $paged = 1, $url = '#')
	{

		$which = 'top';

		$current = $paged;
		$last       = $max_num_pages;

		$total_items     = $found_posts;
		$total_pages     = $max_num_pages;
		$infinite_scroll = false;

		$output = '<span class="displaying-num">' . sprintf(_n('%s item', '%s items', $total_items, 'pbe'), number_format_i18n($total_items)) . '</span>';

		$removable_query_args = wp_removable_query_args();

		$page_links = array();

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		$disable_first = $disable_last = $disable_prev = $disable_next = false;

		if ($current == 1) {
			$disable_first = true;
			$disable_prev  = true;
		}
		if ($current == 2) {
			$disable_first = true;
		}
		if ($current == $total_pages) {
			$disable_last = true;
			$disable_next = true;
		}
		if ($current == $total_pages - 1) {
			$disable_last = true;
		}

		if ($disable_first) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$firs_url = str_replace('#number#', 1, $url);
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s' data-page='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				$firs_url,
				1,
				__('First page', 'pbe'),
				'&laquo;'
			);
		}

		if ($disable_prev) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$pp = max(1, $current - 1);
			$pp_url = str_replace('#number#', $pp, $url);
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s' data-page='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				$pp_url,
				max(1, $current - 1),
				__('Previous page', 'pbe'),
				'&lsaquo;'
			);
		}

		if ('bottom' === $which) {
			$html_current_page  = $current;
			$total_pages_before = '<span class="screen-reader-text">' . __('Current Page', 'pbe') . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
		} else {
			$input_url = $url;
			if ('#' == $url) {
				$input_url = '';
			}
			$html_current_page = sprintf(
				"%s<input class='current-page' id='current-page-selector' type='text' data-url='%s' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
				'<label for="current-page-selector" class="screen-reader-text">' . __('Current Page', 'pbe') . '</label>',
				esc_url($input_url),
				$current,
				strlen($total_pages)
			);
		}
		$html_total_pages = sprintf("<span class='total-pages'>%s</span>", number_format_i18n($total_pages));
		$page_links[]     = $total_pages_before . sprintf(_x('%1$s of %2$s', 'paging', 'pbe'), $html_current_page, $html_total_pages) . $total_pages_after;

		if ($disable_next) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$np = min($total_pages, $current + 1);
			$np_url = str_replace('#number#', $np, $url);
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s' data-page='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				$np_url,
				$np,
				__('Next page', 'pbe'),
				'&rsaquo;'
			);
		}

		if ($disable_last) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		} else {
			$tp_url = str_replace('%number%', $total_pages, $url);
			$page_links[] = sprintf(
				"<a class='last-page button' href='%s' data-page='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				$tp_url,
				$total_pages,
				__('Last page', 'pbe'),
				'&raquo;'
			);
		}

		$pagination_links_class = 'pagination-links';
		if (!empty($infinite_scroll)) {
			$pagination_links_class .= ' hide-if-js';
		}
		$output .= "\n<span class='$pagination_links_class'>" . join("\n", $page_links) . '</span>';

		if ($total_pages) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}

		return "<div class='tablenav-pages{$page_class}'>$output</div>";
	}
}
