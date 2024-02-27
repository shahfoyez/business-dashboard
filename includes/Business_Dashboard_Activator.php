<?php
class Business_Dashboard_Activator {
	public static function activate() {
		$page_title = 'Business Dashboard';
		$page_template = 'business-dashboard-template.php';

		// Check if the page doesn't exist already
		$page_check = get_page_by_title($page_title);

		if (empty($page_check)) {
			$template_file = WP_PLUGIN_DIR .'/business-dashboard/views/' . $page_template;

			// Check if the file exists
			if (file_exists($template_file)) {
				$page_content = file_get_contents($template_file);

				$page_id = wp_insert_post(array(
					'post_title'    => $page_title,
					'post_content'  => '',
					'post_status'   => 'publish',
					'post_type'     => 'page',
				));

				// Assign the custom template to the page
				update_post_meta($page_id, '_wp_page_template', $page_template);
			} else {
				// Handle the case where the template file doesn't exist
				echo 'Template file does not exist.';
			}
		}
	}
}
