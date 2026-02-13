<?php

/**
 * Plugin Name: Bulk Edit for WooCommerce 
 * Plugin URI: https://github.com/PressMaximum/bulk-edit-for-woocommerce
 * Description: A Bulk Edit plugin for WooCommerce
 * Version: 0.0.7
 * Author: PressMaximum
 * Author URI: https://github.com/PressMaximum
 * Text Domain: pbe
 * Domain Path: /languages
 * License:     GPL-2.0+
 */

if (!defined('PBE_URL')) {
	define('PBE_URL', plugin_dir_url(__FILE__));
}
if (!defined('PBE_DIR')) {
	define('PBE_DIR', plugin_dir_path(__FILE__));
}
if (!defined('PBE_PLUGIN_FILE')) {
	define('PBE_PLUGIN_FILE', __FILE__);
}
if (!defined('PBE_PLUGIN_PRO_URL')) {
	define('PBE_PLUGIN_PRO_URL', 'https://pressmaximum.com/pressmerce/bulk-edit-for-woocommerce-pro-version/');
}




if (!class_exists('PBE_Plugin')) {



	if (is_admin() || wp_doing_cron()) { // Run in admin an cron only.

		require_once PBE_DIR . 'inc/class-plugin.php';
		/**
		 * Main instance of PBE_Plugin.
		 *
		 * Returns the main instance of PBE_Plugin to prevent the need to use globals.
		 *
		 * @since  0.0.1
		 * @return PBE_Plugin
		 */
		function pbe()
		{
			return PBE_Plugin::instance();
		}

		add_action('plugins_loaded', 'pbe');
	}
}



function pbe_activation_()
{
	PBE_Plugin::install();

}
register_activation_hook(__FILE__, 'pbe_activation_');
register_deactivation_hook(__FILE__, array('PBE_Plugin', 'uninstall'));


if (!function_exists('pbe_activation_redirect')) {
	function pbe_activation_redirect($plugin)
	{
		if (is_plugin_active('woocommerce/woocommerce.php')) {
			if (plugin_basename(__FILE__) == $plugin) {
				exit(wp_redirect(admin_url('admin.php?page=pbe-page')));
			}
		}
	}
}

add_action('activated_plugin', 'pbe_activation_redirect');
