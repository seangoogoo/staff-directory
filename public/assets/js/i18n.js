/**
 * Internationalization (i18n) support for JavaScript
 * 
 * This file provides translation functions for JavaScript code.
 */

// Global translations object
window.translations = window.translations || {};

/**
 * Translate a key
 * 
 * @param {string} key - The translation key
 * @param {object} params - Parameters to replace in the translation
 * @returns {string} The translated string
 */
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

// Export the translation function
window.__ = __;

// Log initialization if in development mode
if (window.DEV_MODE) {
    console.log('i18n initialized with language:', window.currentLanguage);
}
