<?php
/**
 * Template Name: Add Manager
 * Template Post Type: page
 */

// Custom page content goes here

echo '<div class="page-content">';
echo '<h1>This is a custom page template</h1>';
echo '<p>This content is defined in the template file.</p>';
echo '</div>';

global $wpdb;
$user_id = 1;

// Select specific columns
$query = $wpdb->prepare("
    SELECT *
    FROM {$wpdb->prefix}bp_activity
    WHERE user_id = %d
    ORDER BY date_recorded DESC
    LIMIT 50
", $user_id);

$activities = $wpdb->get_results($query);
echo "<pre>";
var_dump($activities);
echo "</pre>";
