<?php
/**
 * Generate a placeholder image with initials
 * This file handles direct requests to generate placeholder images dynamically
 *
 * @author Jensen Siu
 * @version 1.0
 */

// Include bootstrap to ensure constants are defined
require_once __DIR__ . '/bootstrap.php';

// Allow access only within the application
if (!defined('APP_PATH')) {
    define('APP_PATH', dirname(__DIR__));
}

// functions.php is already included by bootstrap.php
// require_once 'functions.php';

// Get parameters
$name = isset($_GET['name']) ? $_GET['name'] : 'Unknown User';
$size = isset($_GET['size']) ? $_GET['size'] : '200x200';
$bg_color = isset($_GET['bg_color']) ? $_GET['bg_color'] : '#9CA3AF';
$text_color = isset($_GET['text_color']) ? '#ffffff' : '#ffffff'; // Default to white text
$font_weight = isset($_GET['font_weight']) ? $_GET['font_weight'] : 'Regular';
$font_size_factor = isset($_GET['font_size_factor']) ? floatval($_GET['font_size_factor']) : 3; // Default to 3

// Create a mock staff array with the provided name
$name_parts = explode(' ', $name, 2);
$staff = [
    'first_name' => isset($name_parts[0]) ? $name_parts[0] : 'Unknown',
    'last_name' => isset($name_parts[1]) ? $name_parts[1] : 'User'
];

// Generate the placeholder image and get both URL and path
$image_data = get_staff_image_url($staff, $size, $font_weight, $bg_color, $text_color, $font_size_factor, true);
$image_url = $image_data['url'];
$image_path = $image_data['path'];

// $logger->info('Image url', ['path' => $image_url]);
// $logger->info('Image path', ['path' => $image_path]);


// Send the image with appropriate headers
if (file_exists($image_path)) {
    // Set the content type
    header('Content-Type: image/webp');

    // Set cache control headers
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');

    // Output the image
    readfile($image_path);
} else {
    // If file doesn't exist, return an error
    header('HTTP/1.0 404 Not Found');
    echo 'Image not found';
}
?>
