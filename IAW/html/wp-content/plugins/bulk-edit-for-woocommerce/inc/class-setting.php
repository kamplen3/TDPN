<?php

class PBE_Plugin_Setting {
	/**
	 * The option key in database.
	 *
	 * @var PBE_Plugin_Setting_Option_Key
	 * @since 0.0.1
	 */
	protected $option_key = 'pbe_settings';

	/**
	 * The metabox prefix.
	 *
	 * @var string
	 * @since 0.0.1
	 */
	protected $metabox_prefix = '_pbe_';

	/**
	 * The option configs.
	 *
	 * @var array
	 * @since 0.0.1
	 */
	protected $option_configs = array();

	/**
	 * The metabox configs.
	 *
	 * @var array
	 * @since 0.0.1
	 */
	protected $metabox_configs = array();

	/**
	 * The setting tabs.
	 *
	 * @var array
	 * @since 0.0.1
	 */
	protected $tabs = array();

	/**
	 * The setting subtabs.
	 *
	 * @var array
	 * @since 0.0.1
	 */
	protected $sub_tabs = array();

	/**
	 * The current tab.
	 *
	 * @var array
	 * @since 0.0.1
	 */
	protected $current_tab = array();

	protected $current_page_id = '';
	protected $current_tab_id = '';


	/**
	 * The current section.
	 *
	 * @var array
	 * @since 0.0.1
	 */
	protected $current_section = array();

	/**
	 * The current page slug.
	 *
	 * @var string
	 * @since 0.0.1
	 */
	protected $current_page_slug = array();

	/**
	 * The menu pages.
	 *
	 * @var array
	 * @since 0.0.1
	 */
	protected $menu_pages = array();


	/**
	 * The setting fields.
	 *
	 * @var array
	 * @since 0.0.1
	 */
	protected $setting_fields = array();

	/**
	 * The single instance of the class.
	 *
	 * @var PBE_Plugin_Setting
	 * @since 0.0.1
	 */
	protected static $_instance = null;
	/**
	 * Method __construct
	 */
	public function __construct() {
		if ( ! class_exists( 'WooCommerce' ) ) { // Check if WooCommerce install and active.
			return;
		}
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ), 90 );
		add_action( 'cmb2_admin_init', array( $this, 'register_db_settings' ), 10 );
		add_action( 'admin_init', array( $this, 'admin_init' ), 1 );

		add_action(
			'init',
			function() {
				$this->get_post_meta();
			}
		);
	}
	/**
	 * Hook to cmb2_admin_init
	 */
	public function register_db_settings() {
		register_setting( $this->option_key, $this->option_key );
		$this->init_meta_box();
	}

	/**
	 * Init meta box
	 *
	 * @return void
	 */
	public function init_meta_box() {
		if ( is_array( $this->metabox_configs ) && ! empty( $this->metabox_configs ) ) {
			$metabox_configs = apply_filters( 'pbe_register_metabox', $this->metabox_configs );
			foreach ( $metabox_configs as $config ) {
				$default_args = array(
					'id'            => $this->metabox_prefix . 'metabox',
					'title'         => esc_html__( 'Metabox', 'pbe' ),
					'object_types'  => array( 'post', 'page' ),
				);
				$args = wp_parse_args( $config['args'], $default_args );
				$meta_box = new_cmb2_box( $args );
				foreach ( $config['fields'] as $field ) {
					$field['id'] = $this->metabox_prefix . $field['id'];
					$meta_box->add_field( $field );
				}
			}
		}
	}

	/**
	 * Admin init
	 *
	 * @return void
	 */
	public function admin_init() {

		$this->current_page_id = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$this->current_tab_id = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';
		$has_tab = false;
		if ( isset( $this->tabs[ $this->current_page_id ] ) ) {
			if ( isset( $this->tabs[ $this->current_page_id ][ $this->current_tab_id ] ) ) {
				$has_tab = true;
			} else {
				$tab_key = key( $this->tabs[ $this->current_page_id ] );
				$this->current_tab_id = $tab_key;
			}
		} else {
			return;
		}

		$tab_key = 'tab|' . $this->current_tab_id;
		if ( isset( $this->set_setting_fields[ $tab_key ] ) ) {
			$this->current_section = $this->set_setting_fields[ $tab_key ];
		}

	}
	/**
	 * Get all registered menu slugs
	 *
	 * @return array
	 */
	public function get_available_menu_slugs() {
		$menu_slug = array();
		foreach ( $this->menu_pages as $menu ) {
			if ( isset( $menu['menu_slug'] ) && '' !== $menu['menu_slug'] ) {
				$menu_slug[] = $menu['menu_slug'];
			}
		}
		return $menu_slug;
	}

	/**
	 * Instance
	 *
	 * @return PBE_Plugin_Setting
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Add menu setting page
	 */
	public function add_menu_pages() {
		foreach ( $this->menu_pages as $menu_page ) {
			$default = array(
				'page_title' => '',
				'menu_title' => '',
				'capability' => 'manage_options',
				'menu_slug'  => '',
				'parent_slug' => '',
				'icon_url'   => '',
				'position'   => null,
			);
			$args = wp_parse_args( $menu_page, $default );
			if ( is_null( $args['parent_slug'] ) || '' == $args['parent_slug'] ) {
				add_menu_page( $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], array( $this, 'page_content' ), $args['icon_url'], $args['position'] );
			} else {
				add_submenu_page( $args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], array( $this, 'page_content' ) );
			}
		}
	}

	/**
	 * Add setting page
	 *
	 * @param [array] $args
	 * @return void
	 */
	public function add_settings_page( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'menu_slug' => '',
				'parent_slug' => '',
				'page_title' => '',
				'menu_title' => '',
			)
		);
		$this->menu_pages[] = $args;
	}

	/**
	 * Render tabs
	 *
	 * @return void
	 */
	public function render_tabs() {
		if ( isset( $this->tabs[ $this->current_page_id ] ) && ! empty( $this->tabs ) ) {
			?>
			<nav class="nav-tab-wrapper">
				<?php
				foreach ( (array) $this->tabs[ $this->current_page_id ] as $tab_id => $tab_config ) {

					$tab_url = add_query_arg( array( 'tab' => $tab_id ), menu_page_url( $this->current_page_id, false ) );
					$extra_class = '';
					if ( $tab_id == $this->current_tab_id ) {
						$extra_class = ' nav-tab-active';
					}
					?>
					<a href="<?php echo esc_url( $tab_url ); ?>" id="<?php echo esc_attr( $tab_id ); ?>" class="nav-tab<?php echo esc_attr( $extra_class ); ?>"><?php echo esc_html( $tab_config['tab_title'] ); ?></a>
					<?php
				}
				?>
			</nav>
			<?php
		}
	}

	/**
	 * Check target page
	 *
	 * @return boolean
	 */
	public function check_target_page() {
		return true;
	}

	/**
	 * Render form content
	 *
	 * @return void
	 */
	public function render_form_content() {
		$target_page = $this->check_target_page();
		$target_page = true;
		if ( ! $target_page ) {
			return;
		}
		$this->render_tabs();
		$tab = $this->tabs[ $this->current_page_id ][ $this->current_tab_id ];
		$method_template = 'template_' . $this->current_tab_id;

		?>
		<div class="form-content">
			<?php
			if ( is_callable( $tab['render_callback'] ) ) {
				call_user_func_array(
					$tab['render_callback'],
					array(
						'page'     => $this->current_page_id,
						'tab'      => $this->current_tab_id,
						'settings' => $this,
					)
				);
			} else {
				$this->template();
			}
			?>
		</div>
		<?php
	}

	function validate_file_name( $name ) {
		$file_name = sanitize_title( $name );
		$file_name = str_replace( '_', '-', $file_name );
		return $file_name;
	}

	function load_template( $file_name ) {
		$template_file      = PBE_DIR . 'templates/' . $file_name . '.php';
		if ( file_exists( $template_file ) ) {
			include $template_file;
		}
	}


	function template() {
		$file_name          = $this->validate_file_name( $this->current_tab_id );
		$template_file      = PBE_DIR . 'templates/' . $file_name . '.php';
		$view               = isset( $_GET['view'] ) ? $this->validate_file_name( $_GET['view'] ) : '';
		$template_view_file = PBE_DIR . 'templates/' . $view . '.php';
		$template_view_file = apply_filters( 'pbe_template_view', $template_view_file, $this );
		$template_file      = apply_filters( 'pbe_template', $template_file, $this );

		if ( $view && file_exists( $template_view_file ) ) {
			include $template_view_file;
			return;
		}

		if ( file_exists( $template_file ) ) {
			include $template_file;
		} else {
			include PBE_DIR . 'templates/dashboard.php';
		}
	}

	/**
	 * Render setting page content
	 */
	public function page_content() {

		/**
		 * Hook pbe_before_page_setting_content
		 *
		 * @since 0.0.1
		 */
		do_action( 'pbe_before_page_setting_content' );
		?>
		<div class="wrap pbe-wrap">
			<?php
				/**
				 * Hook pbe_page_setting_before_title
				 *
				 * @since 0.0.1
				 */
				do_action( 'pbe_page_setting_before_title' );
			?>
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php
				/**
				 * Hook pbe_page_setting_after_title
				 *
				 * @since 0.0.1
				 */
				do_action( 'pbe_page_setting_after_title' );
			?>

			<?php
				/**
				 * Hook pbe_page_setting_before_form_content
				 *
				 * @since 0.0.1
				 */
				do_action( 'pbe_page_setting_before_form_content' );
			?>
			<?php

			$this->render_form_content();

			?>
			<?php
				/**
				 * Hook pbe_page_setting_after_form_content
				 *
				 * @since 0.0.1
				 */
				do_action( 'pbe_page_setting_after_form_content' );
			?>
			<div class="clear"></div>
		</div>
		<?php
		/**
		 * Hook pbe_after_page_setting_content
		 *
		 * @since 0.0.1
		 */
		do_action( 'pbe_after_page_setting_content' );
	}

	/**
	 * Set option config for cmb2 form
	 *
	 * @return array
	 */
	public function option_metabox() {
		$current_tab = $this->current_tab;
		$current_section = $this->current_section;
		$current_section_id = $current_section['id'];
		$fields = array();
		if ( isset( $current_section_id ) && '' !== $current_section_id && in_array( $current_section_id, array_keys( $current_tab['sub_tabs'] ) ) ) {
			$fields = $current_section['fields'];
		} elseif ( isset( $current_tab['fields'] ) && ! empty( $current_tab['fields'] ) ) {
			$fields = $current_tab['fields'];
		}
		return array(
			'id'         => 'form_settings',
			'show_on'    => array(
				'key' => 'options-page',
				'value' => array( $this->option_key ),
			),
			'show_names' => true,
			'fields'     => $fields,
		);
	}

	/**
	 * Register tab
	 *
	 * @param [string] $tab_id
	 * @param [string] $tab_title
	 * @param [string] $menu_slug
	 * @return void
	 */
	public function register_tab( $menu_slug = '', $tab_id = '', $tab_title = '', $render_callback = '' ) {
		if ( ! isset( $this->tabs[ $menu_slug ] ) ) {
			$this->tabs[ $menu_slug ]  = array();

		}
		$this->tabs[ $menu_slug ][ $tab_id ] = array(
			'tab_id'    => sanitize_text_field( $tab_id ),
			'tab_title' => $tab_title,
			'menu_slug' => $menu_slug,
			'render_callback' => $render_callback,
			'subtabs' => array(),
		);

	}

	public function register_sub_tab( $menu_slug, $parent_tab_id, $tab_id, $tab_title ) {
		if ( isset( $this->tabs[ $parent_tab_id ] ) ) {
			$this->tabs[ $parent_tab_id ]['sub_tabs'][ $tab_id ] = array(
				'tab_id' => sanitize_text_field( $tab_id ),
				'tab_title' => $tab_title,
			);
		}
	}

	/**
	 * Set tab fields configs
	 *
	 * @param string $tab_id
	 * @param array  $fields
	 * @return void
	 */
	public function set_tab_fields( $tab_id, $fields ) {
		$this->set_setting_fields[ 'tab|' . $tab_id ] = $fields;
	}
	/**
	 * Add setting to page without tab
	 *
	 * @param [string] $menu_slug
	 * @param [array]  $fields
	 * @return void
	 */
	public function set_setting_fields( $menu_slug, $fields ) {
		if ( '' !== $menu_slug && is_array( $fields ) && ! empty( $fields ) ) {
			$fields = apply_filters( 'pbe_set_setting_fields', $fields, $menu_slug );
			if ( isset( $this->setting_fields[ $menu_slug ] ) && isset( $this->setting_fields[ $menu_slug ]['fields'] ) ) {
				$this->setting_fields[ $menu_slug ]['fields'] = array_merge( $this->setting_fields[ $menu_slug ]['fields'], $fields );
			} else {
				$this->setting_fields[ $menu_slug ] = array(
					'menu_slug' => $menu_slug,
					'fields'    => $fields,
				);
			}
		}
	}
	/**
	 * Add setting to page without tab via file
	 *
	 * @param [string] $menu_slug
	 * @param [array]  $file_configs
	 * @return void
	 */
	public function set_setting_file_configs( $menu_slug, $file_configs ) {
		$file_configs = apply_filters( 'pbe_set_setting_file_configs', $file_configs, $menu_slug );
		if ( file_exists( $file_configs ) || file_exists( PBE_DIR . 'inc/admin/setting-configs/' . $file_configs ) ) {
			$file_config_dir = $file_configs;
			if ( ! file_exists( $file_config_dir ) ) {
				$file_config_dir = PBE_DIR . 'inc/admin/setting-configs/' . $file_configs;

			}
			$configs = include $file_config_dir;
			if ( '' !== $menu_slug && is_array( $configs ) && ! empty( $configs ) ) {
				$this->set_setting_fields( $menu_slug, $configs );
			}
		}
	}
	/**
	 * Add setting to page with one file each call
	 *
	 * @param [string] $menu_slug
	 * @param [array]  $field
	 * @return void
	 */
	public function set_setting_field( $menu_slug, $field ) {
		if ( '' !== $menu_slug && is_array( $field ) && ! empty( $field ) ) {
			$fields = apply_filters( 'pbe_set_setting_field', $field, $menu_slug );
			if ( isset( $this->setting_fields[ $menu_slug ] ) && isset( $this->setting_fields[ $menu_slug ]['fields'] ) ) {
				$exists_fields = $this->setting_fields[ $menu_slug ]['fields'];
				$exists_fields[] = $field;
				$this->setting_fields[ $menu_slug ]['fields'] = $exists_fields;
			} else {
				$this->setting_fields[ $menu_slug ] = array(
					'menu_slug' => $menu_slug,
					'fields' => array(
						$field,
					),
				);
			}
		}
	}
	/**
	 * Set tab field
	 *
	 * @param [string] $tab_id
	 * @param [array]  $field
	 * @return void
	 */
	public function set_tab_field( $tab_id, $field ) {
		if ( '' !== $tab_id && is_array( $field ) && ! empty( $field ) ) {
			$fields = apply_filters( 'pbe_set_tab_field', $field, $tab_id );
			if ( isset( $this->option_configs[ $tab_id ] ) && isset( $this->option_configs[ $tab_id ]['fields'] ) ) {
				$exists_fields = $this->option_configs[ $tab_id ]['fields'];
				$exists_fields[] = $field;
				$this->option_configs[ $tab_id ]['fields'] = $exists_fields;
			} else {
				$this->option_configs[ $tab_id ] = array(
					'id' => $tab_id,
					'fields' => array(
						$field,
					),
				);
			}
		}
	}
	/**
	 * Set tab fields configs via file
	 *
	 * @param [string] $tab_id
	 * @param [string] $file_configs
	 * @return void
	 */
	public function set_tab_file_configs( $tab_id, $file_configs ) {
		$file_configs = apply_filters( 'pbe_set_tab_file_configs', $file_configs, $tab_id );
		if ( file_exists( $file_configs ) || file_exists( PBE_DIR . 'inc/admin/setting-configs/' . $file_configs ) ) {
			$file_config_dir = $file_configs;
			if ( ! file_exists( $file_config_dir ) ) {
				$file_config_dir = PBE_DIR . 'inc/admin/setting-configs/' . $file_configs;

			}
			$configs = include $file_config_dir;
			if ( '' !== $tab_id && is_array( $configs ) && ! empty( $configs ) ) {
				$this->set_tab_fields( $tab_id, $configs );
			}
		}
	}


	/**
	 * Public getter method for retrieving protected/private variables
	 *
	 * @since  0.0.1
	 * @param  string $field Field to retrieve.
	 * @return mixed Field value or null.
	 */
	public function __get( $field ) {
		if ( in_array( $field, array( 'menu_slugs', 'option_key', 'option_configs', 'tabs' ), true ) ) {
			return $this->{$field};
		}
		return null;
	}

	/**
	 * Get setting
	 *
	 * @param string $setting_key
	 * @param string $default_value
	 * @return mixed Field value or default value
	 */
	public function get_setting( $setting_key = '', $default_value = '' ) {
		if ( function_exists( 'cmb2_get_option' ) ) {
			return cmb2_get_option( $this->option_key, $setting_key, $default_value );
		} else {
			$options = get_option( $this->option_key );
			return isset( $options[ $setting_key ] ) ? $options[ $setting_key ] : $default_value;
		}
	}

	/**
	 * Add metabox configs
	 *
	 * @param [array] $args
	 * @param [array] $fields
	 * @return void
	 */
	public function add_meta_box( $args, $fields ) {
		$this->metabox_configs[] = array(
			'args' => $args,
			'fields' => $fields,
		);
	}

	/**
	 * Add metabox config with fields defined in a file
	 *
	 * @param [array]  $args
	 * @param [string] $file_dir
	 * @return void
	 */
	public function add_meta_box_file( $args, $file_dir ) {
		$file_configs = apply_filters( 'pbe_add_meta_box_file', $file_dir, $args );
		if ( file_exists( $file_configs ) || file_exists( PBE_DIR . 'inc/admin/setting-configs/' . $file_configs ) ) {
			$file_config_dir = $file_configs;
			if ( ! file_exists( $file_config_dir ) ) {
				$file_config_dir = PBE_DIR . 'inc/admin/setting-configs/' . $file_configs;
			}
			$configs = include $file_config_dir;
			if ( is_array( $args ) && ! empty( $args ) && is_array( $configs ) && ! empty( $configs ) ) {
				$this->add_meta_box( $args, $configs );
			}
		}
	}

	/**
	 * Get metabox value
	 *
	 * @param string  $meta_key
	 * @param integer $post_id
	 * @param string  $default_value
	 * @return mixed  Meta value or default value
	 */
	public function get_post_meta( $meta_key = '', $post_id = 0, $default_value = '' ) {
		$post_meta = get_post_meta( $this->metabox_prefix . $meta_key, $post_id, true );
		if ( ! empty( $post_meta ) ) {
			return $post_meta;
		}
		return $default_value;
	}


}

/**
 * Init plugin settings.
 *
 * @return PBE_Plugin_Setting
 */
function pbe_settings() {
	return PBE_Plugin_Setting::instance();
}

if ( file_exists( PBE_DIR . 'inc/configs.php' ) ) {
	require_once PBE_DIR . 'inc/configs.php';
}

