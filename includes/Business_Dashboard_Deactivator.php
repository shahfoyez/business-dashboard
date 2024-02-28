<?php
class Business_Dashboard_Deactivator {
	public static function deactivate() {
		$views_dir = WP_PLUGIN_DIR . '/business-dashboard/templates/';
		$template_files = scandir($views_dir);
		// Remove . and .. from the list
		$template_files = array_diff($template_files, array('..', '.'));

		foreach ($template_files as $template_file) {
			// Check if it's a PHP file
			if (pathinfo($template_file, PATHINFO_EXTENSION) === 'php') {
				$page_title = ucwords(str_replace(array('-', '_'), ' ', pathinfo($template_file, PATHINFO_FILENAME)));

				// Find the page by title
				$page = get_page_by_title($page_title);

				// If page exists, delete it
				if ($page) {
					wp_delete_post($page->ID, true); // Set second parameter to true to force delete
				}
			}
		}
	}
}
