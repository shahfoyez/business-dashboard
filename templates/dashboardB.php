<?php
/**
 * Template Name: Dashboard
 * Template Post Type: page
 */
?>

<h1>Dashboard</h1>
<?php
// Define the path to your views folder
$views_dir = WP_PLUGIN_DIR . '/business-dashboard/views/';


// Get list of template files
$template_files = scandir($views_dir);


// Remove . and .. from the list
$template_files = array_diff($template_files, array('..', '.'));

foreach ($template_files as $template_file) {
	// Check if it's a PHP file
	if (pathinfo($template_file, PATHINFO_EXTENSION) === 'php') {
		$page_title = ucwords(str_replace(array('-', '_'), ' ', pathinfo($template_file, PATHINFO_FILENAME)));
		$page_template = $template_file;
echo "page_template";
echo "<pre>";
var_dump($page_template);
echo "</pre>";


		$file_content = file_get_contents($views_dir . $template_file);

		// Use regular expression to find the Template Name declaration
		preg_match('/Template Name:(.*?)(\r|\n)/', $file_content, $matches);

		if (!empty($matches[1])) {
			$template_name = trim($matches[1]);

			// Convert the template name to lowercase and replace spaces with hyphens
			$template_name_slug = strtolower(str_replace(' ', '-', $template_name));
            echo "template_name:";
			 echo "<pre>";
			 var_dump($template_name);
			 echo "</pre>";
             echo "<br>";
			echo "template_name_slug:";

             echo "<pre>";
             var_dump($template_name_slug);
             echo "</pre>";
		}



        
		// Check if the page doesn't exist already
		$page_check = get_page_by_title($page_title);

		if (empty($page_check)) {
			// Check if the file exists
			if (file_exists($views_dir . $page_template)) {
				$page_content = file_get_contents($views_dir . $page_template);



				 

			} else {
				// Handle the case where the template file doesn't exist
				echo 'Template file does not exist: ' . $page_template . '<br>';
			}
		}
	}
}

