<?php
/**
 * Language Configuration
 *
 * This file contains configuration for the internationalization system.
 */

return [
    // Supported languages with their details
    'supported' => [
        'en' => [
            'code' => 'en',
            'name' => 'English',
            'locale' => 'en_US',
            'flag' => 'us',
            'dir' => 'ltr', // text direction: ltr or rtl
            'default' => true
        ],
        'fr' => [
            'code' => 'fr',
            'name' => 'Français',
            'locale' => 'fr_FR',
            'flag' => 'fr',
            'dir' => 'ltr',
            'default' => false
        ]
    ],
    
    // Default language code
    'default' => 'en',
    
    // Language detection methods in order of priority
    'detection' => [
        'url',      // Detect from URL parameter (e.g., ?lang=fr)
        'session',  // Use language stored in session
        'cookie',   // Use language stored in cookie
        'browser',  // Detect from browser Accept-Language header
        'default'   // Fall back to default language
    ],
    
    // Session and cookie settings
    'session_key' => 'app_language',
    'cookie_key' => 'app_language',
    'cookie_lifetime' => 30 * 24 * 60 * 60, // 30 days
    
    // URL parameter name for language switching
    'url_parameter' => 'lang',
    
    // Whether to include language code in URLs
    'url_include_lang' => false,
    
    // Translation file path relative to the application root
    'translations_path' => 'languages',
];
