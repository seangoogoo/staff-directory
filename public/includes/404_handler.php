<?php
/**
 * 404 Handler
 *
 * This file handles 404 (Not Found) responses and redirects to the index page.
 * It ensures proper HTTP status codes are set and logs the 404 event.
 */

// Make sure we have access to all required constants and functions
require_once __DIR__ . '/bootstrap.php';

// Set proper 404 status code
http_response_code(404);

// Log the 404 if logger is available
global $logger;
if (isset($logger)) {
    $logger->info('404 Not Found', [
        'uri' => $_SERVER['REQUEST_URI'],
        'referer' => $_SERVER['HTTP_REFERER'] ?? 'none'
    ]);
}

// Redirect to index.php with a 404 indicator
// This allows index.php to optionally display a "Page not found" message if desired
header('Location: ' . APP_BASE_URI . '/index.php?not_found=1');
exit;
