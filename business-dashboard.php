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




function activate_plugin_name() {
	require_once WP_PLUGIN_DIR.'/business-dashboard/includes/Business_Dashboard_Activator.php';
	Business_Dashboard_Activator::activate();
}

//function deactivate_plugin_name() {
//	require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin-name-deactivator.php';
//	Plugin_Name_Deactivator::deactivate();
//}

register_activation_hook( __FILE__, 'activate_plugin_name' );
//register_deactivation_hook( __FILE__, 'deactivate_plugin_name' );



// Add custom page template to theme
function business_dashboard_template($templates)
{
	$templates['business-dashboard-template.php'] = __('Business Dashboard', 'business-dashboard');
	return $templates;
}
add_filter('theme_page_templates', 'business_dashboard_template');

add_action('template_include', 'mcd_set_template', 99);
function mcd_set_template()
{
	$page_template = 'business-dashboard-template.php';
	$template_file = plugin_dir_path( __FILE__ ) . 'views/' . $page_template;
	return  $template_file;
}

// Enqueue CSS and JS for the dashboard page
function business_dashboard_enqueue_scripts()
{
	if (is_page_template('business-dashboard-template.php')) {
		// Enqueue CSS
		wp_enqueue_style('business-dashboard-css', BUSINESS_DASHBOARD_PLUGIN_URL . 'assets/css/business-dashboard.css', array(), '1.0');

		// Enqueue JS
		wp_enqueue_script('business-dashboard-js', BUSINESS_DASHBOARD_PLUGIN_URL . 'assets/js/business-dashboard.js', array('jquery'), '1.0', true);
	}
}
add_action('wp_enqueue_scripts', 'business_dashboard_enqueue_scripts');