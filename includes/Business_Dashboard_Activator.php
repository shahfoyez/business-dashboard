<?php
class Business_Dashboard_Activator {
	public $page_templates_store;
	public function __construct(){

	}

	public static function activate() {
		$views_dir = WP_PLUGIN_DIR . '/business-dashboard/templates/';
		$template_files = scandir($views_dir);
		// Remove . and .. from the list
		$template_files = array_diff($template_files, array('..', '.'));

		foreach ($template_files as $template_file) {
			// Check if it's a PHP file
			if (pathinfo($template_file, PATHINFO_EXTENSION) === 'php') {
				$page_title = ucwords(str_replace(array('-', '_'), ' ', pathinfo($template_file, PATHINFO_FILENAME)));
				$page_template = $template_file;

				// Check if the page doesn't exist already
				$page_check = get_page_by_title($page_title);

				if (empty($page_check)) {
					// Check if the file exists
					if (file_exists($views_dir . $page_template)) {
						$page_content = file_get_contents($views_dir . $page_template);

						// Insert the page
						$page_id = wp_insert_post(array(
							'post_title'    => $page_title,
							'post_content'  => '',
							'post_status'   => 'publish',
							'post_type'     => 'page',
						));

						// Assign the custom template to the page
						update_post_meta($page_id, '_wp_page_template', $page_template);



						$file_content = file_get_contents($views_dir . $template_file);

						// Use regular expression to find the Template Name declaration
						preg_match('/Template Name:(.*?)(\r|\n)/', $file_content, $matches);

						if (!empty($matches[1])) {
							$page_template_name = trim($matches[1]);

							// Convert the template name to lowercase and replace spaces with hyphens
							$template_name_slug = strtolower(str_replace(' ', '-', $page_template_name));
						}

					} else {
						// Handle the case where the template file doesn't exist
						echo 'Template file does not exist: ' . $page_template . '<br>';
					}
				}
			}
		}
	}

}
