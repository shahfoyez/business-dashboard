<?php

/**
 * Plugin Name: Business Dashboard
 * Description: Plugin for Business Dashboard functionality.
 * Version: 1.0
 * Author: Staff Asia
 */

function activate_business_dashboard() {
	require_once WP_PLUGIN_DIR.'/business-dashboard/includes/Business_Dashboard_Activator.php';
	Business_Dashboard_Activator::activate();
}
function deactivate_business_dashboard() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/Business_Dashboard_Deactivator.php';
	Business_Dashboard_Deactivator::deactivate();
}
register_activation_hook( __FILE__, 'activate_business_dashboard' );
register_deactivation_hook( __FILE__, 'deactivate_business_dashboard' );

function business_dashboard_templates($templates) {
	$templates['dashboard.php'] = __('Dashboard', 'dashboard');
	$templates['add-manager.php'] = __('Add Manager', 'add-manager');
	return $templates;
}

add_filter('theme_page_templates', 'business_dashboard_templates');
function mcd_set_template($template) {
	if (is_page_template('dashboard.php')) {
		$template = plugin_dir_path(__FILE__) . 'templates/dashboard.php';
	} elseif (is_page_template('add-manager.php')) {
		$template = plugin_dir_path(__FILE__) . 'templates/add-manager.php';
	}
	return $template;
}
add_filter('template_include', 'mcd_set_template', 99);
