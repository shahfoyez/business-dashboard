<?php
add_action('wp_ajax_ct_remove', 'ct_remove');
add_action('wp_ajax_nopriv_ct_remove', 'ct_remove');
function ct_remove() {
    //$data = json_encode($_POST);
	require plugin_dir_path(__FILE__) . '/PluginController.php';
	$ct_plugin = new CTPluginController();
	$output = $ct_plugin->RemoveCT($_POST);
	wp_send_json($output);
}
add_action('wp_ajax_ct_assign', 'ct_assign');
add_action('wp_ajax_nopriv_ct_assign', 'ct_assign');
function ct_assign() {
	// $data = json_encode($_POST);
    // wp_send_json($_POST);
	require plugin_dir_path(__FILE__) . '/AssignController.php';
	$ct_plugin = new CTAssignController();
	$output = $ct_plugin->AssignCT($_POST);
	wp_send_json($output);
}

