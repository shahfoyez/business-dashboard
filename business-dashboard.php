<?php

/**
 * Plugin Name: Business Dashboard
 * Description: Plugin for Business Dashboard functionality.
 * Version: 1.0
 * Author: Staff Asia
 */

// Define plugin constants
define('BUSINESS_DASHBOARD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BUSINESS_DASHBOARD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Activation hook: Create the page on plugin activation
//register_activation_hook(__FILE__, 'business_dashboard_create_page');

// Create the business dashboard page
//function business_dashboard_create_page()
//{
//	$page_title = 'Business Dashboard';
//	$page_template = 'business-dashboard-template.php';
//
//	// Check if the page doesn't exist already
//	$page_check = get_page_by_title($page_title);
//
//	if (empty($page_check)) {
//		$template_file = plugin_dir_path( __FILE__ ) . 'views/' . $page_template;
//
//		// Check if the file exists
//		if (file_exists($template_file)) {
//			$page_content = file_get_contents($template_file);
//
//			$page_id = wp_insert_post(array(
//				'post_title'    => $page_title,
//				'post_content'  => '',
//				'post_status'   => 'publish',
//				'post_type'     => 'page',
//			));
//
//			// Assign the custom template to the page
//			update_post_meta($page_id, '_wp_page_template', $page_template);
//		} else {
//			// Handle the case where the template file doesn't exist
//			echo 'Template file does not exist.';
//		}
//	}
//}




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
		$template = plugin_dir_path(__FILE__) . 'views/dashboard.php';
	} elseif (is_page_template('add-manager.php')) {
		$template = plugin_dir_path(__FILE__) . 'views/add-manager.php';
	}
	return $template;
}
add_filter('template_include', 'mcd_set_template', 99);