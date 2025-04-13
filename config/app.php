<?php
/**
 * Application Configuration
 *
 * This file contains centralized configuration for the Staff Directory application.
 */

return [
    'paths' => [
        'base' => BASE_PATH,
        'private' => PRIVATE_PATH,
        'public' => PUBLIC_PATH,
        'uploads' => PUBLIC_PATH . '/uploads',
    ],
    'urls' => [
        'base' => APP_BASE_URI,
        'assets' => APP_BASE_URI . '/assets',
    ],
    'routing' => [
        'cache' => false, // Caching disabled as it's not needed for this simple app
        'redirectUnmatchedToIndex' => true, // Preserve behavior of redirecting unexisting requests to index.php
    ]
];
