<?php
$pbe_setting = pbe_settings();
$pbe_setting->add_settings_page(
	array(
		'menu_slug' => 'pbe-page',
		'parent_slug' => null,
		'page_title' => esc_html__( 'Bulk Edit', 'pbe' ),
		'menu_title' => esc_html__( 'Bulk Edit', 'pbe' ),
		'icon_url' => 'dashicons-edit',
		'position' => 58,
	)
);

$pbe_setting->register_tab( 'pbe-page', 'dashboard', esc_html__( 'Dashboard', 'pbe' ) );
$pbe_setting->register_tab( 'pbe-page', 'bulk_edit', esc_html__( 'Bulk Edit', 'pbe' ) );
$pbe_setting->register_tab( 'pbe-page', 'tasks', esc_html__( 'Tasks', 'pbe' ) );
// $pbe_setting->register_tab( 'pbe-page', 'pricing', esc_html__( 'Pricing', 'pbe' ) );
// $pbe_setting->register_tab( 'pbe-page', 'helps', esc_html__( 'Helps', 'pbe' ) );
