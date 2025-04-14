# Internationalization (i18n) Implementation

This document describes the internationalization (i18n) implementation in the Staff Directory application.

## Overview

The Staff Directory application now supports multiple languages through a simple and flexible internationalization system. The current implementation supports English and French, but can be easily extended to support additional languages.

**Implementation Date:** April 14, 2025

## Features

- Language detection based on URL parameter, session, cookie, or browser preference
- Language selection via a dropdown menu in the admin settings page
- Translation of all user-facing text in PHP templates using the `__()` function
- Translation support in JavaScript for dynamic content
- Automatic detection of user's preferred language
- Persistent language selection across sessions via cookies and database storage
- Comprehensive translation coverage for all user-facing text

## Directory Structure

```
/languages/
  /en/
    common.php       # Common translations used across the application
    frontend.php     # Frontend-specific translations
    admin.php        # Admin-specific translations
  /fr/
    common.php       # French common translations
    frontend.php     # French frontend translations
    admin.php        # French admin translations
```

## Configuration

Language settings are defined in `config/languages.php`:

```php
return [
    // Supported languages with their details
    'supported' => [
        'en' => [
            'code' => 'en',
            'name' => 'English',
            'locale' => 'en_US',
            'flag' => 'us',
            'dir' => 'ltr',
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

    // Translation file path relative to the application root
    'translations_path' => 'languages',
];
```

## Usage

### PHP Templates

In PHP templates, use the `__()` function to translate text:

```php
// Basic translation
echo __('staff_directory');

// Translation with parameters
echo __('welcome_message', ['name' => $user_name]);
```

### JavaScript

In JavaScript, use the `__()` function to translate text:

```javascript
// Basic translation
console.log(__('staff_directory'));

// Translation with parameters
console.log(__('welcome_message', {name: userName}));
```

## Adding a New Language

To add a new language:

1. Create a new directory in the `languages` directory with the language code (e.g., `languages/es/` for Spanish)
2. Create the following files in the new directory:
   - `common.php`
   - `frontend.php`
   - `admin.php`
3. Copy the translation keys from the English files and translate the values
4. Add the language to the `supported` array in `config/languages.php`

Example for adding Spanish:

```php
'es' => [
    'code' => 'es',
    'name' => 'Español',
    'locale' => 'es_ES',
    'flag' => 'es',
    'dir' => 'ltr',
    'default' => false
]
```

## Language Detection

The language detection system works in the following order:

1. URL parameter: `?lang=fr`
2. Session: Stored in `$_SESSION['app_language']`
3. Cookie: Stored in `$_COOKIE['app_language']`
4. Browser preference: Detected from `Accept-Language` header
5. Default language: Defined in `config/languages.php`

## Language Switching

The language switcher is implemented as a dropdown menu in both the frontend and admin interfaces. When a user selects a language, the application:

1. Sets the language in the session
2. Sets a cookie with the language code
3. Reloads the current page with the new language

## Implementation Details

### LanguageManager Class

The `LanguageManager` class in `public/includes/LanguageManager.php` handles all language-related functionality:

- Language detection
- Translation loading
- Translation functions
- Language switching

### Bootstrap Integration

The language system is initialized in `public/includes/bootstrap.php`:

```php
// Load language manager
require_once __DIR__ . '/LanguageManager.php';

// Initialize language manager (this will detect and set the current language)
$languageManager = LanguageManager::getInstance();
```

### JavaScript Integration

Translations for JavaScript are passed via a global `window.translations` object in the header:

```php
// Add translations for JavaScript
window.translations = {
    // Common translations
    'all_departments': "<?php echo __('all_departments'); ?>",
    'all_companies': "<?php echo __('all_companies'); ?>",
    'no_staff_found': "<?php echo __('no_staff_found'); ?>",
    // ...
};
```

The `i18n.js` file provides the `__()` function for JavaScript:

```javascript
function __(key, params = {}) {
    // Get the translation or fallback to the key
    let translation = window.translations[key] || key;

    // Replace parameters
    if (params) {
        for (const param in params) {
            translation = translation.replace(`:${param}`, params[param]);
        }
    }

    return translation;
}
```

## Best Practices

1. Always use translation keys instead of hardcoded text
2. Use descriptive translation keys that reflect the content
3. Group related translations in the appropriate file (common, frontend, admin)
4. Use parameters for dynamic content instead of concatenating strings
5. Keep translations organized and consistent across languages
6. Test the application in all supported languages

## Admin Settings Page Implementation

The language selection has been implemented in the admin settings page (`public/admin/settings.php`). The implementation includes:

1. A dedicated "Language Settings" section with a dropdown menu for language selection
2. Server-side processing to save the selected language in the database
3. Session and cookie storage for persistent language selection
4. Immediate application of the selected language upon saving

```php
// Language settings form in settings.php
<form method="post" action="" id="language-form">
    <div class="mb-4">
        <label for="app_language" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('language'); ?>:</label>
        <div class="relative">
            <select name="app_language" id="app_language" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                <?php foreach (supported_languages() as $code => $lang): ?>
                    <option value="<?php echo $code; ?>" <?php echo current_language() === $code ? 'selected' : ''; ?>>
                        <?php echo $lang['name']; ?> <?php echo $lang['default'] ? '(' . __('default') . ')' : ''; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="mt-2 text-sm text-gray-500"><?php echo __('language_selection_help'); ?></p>
        </div>
    </div>
    <button type="submit" name="save_language_settings" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        <?php echo __('save_language_settings'); ?>
    </button>
</form>
```

## Future Improvements

- Add support for additional languages (Spanish, German, etc.)
- Implement a translation management interface in the admin area
- Add support for RTL (right-to-left) languages
- Enhance JavaScript translation support for dynamic content
- Implement language-specific formatting for dates, numbers, and currencies
- Add translation caching for better performance
