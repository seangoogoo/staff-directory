<?php
/**
 * Language Manager
 *
 * Handles language detection, selection, and translation.
 */

class LanguageManager {
    /**
     * @var string The current language code
     */
    private $currentLanguage;

    /**
     * @var array The language configuration
     */
    public $config;

    /**
     * @var array The loaded translations
     */
    private $translations = [];

    /**
     * @var LanguageManager The singleton instance
     */
    private static $instance;

    /**
     * Get the singleton instance
     *
     * @return LanguageManager
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Load language configuration
        $this->config = require PRIVATE_PATH . '/config/languages.php';

        // Detect the current language
        $this->currentLanguage = $this->detectLanguage();

        // Load translations for the current language
        $this->loadTranslations();
    }

    /**
     * Detect the current language based on the configured detection methods
     *
     * @return string The detected language code
     */
    private function detectLanguage() {
        $detectedLang = $this->config['default'];

        foreach ($this->config['detection'] as $method) {
            switch ($method) {
                case 'url':
                    $urlParam = $this->config['url_parameter'];
                    if (isset($_GET[$urlParam]) && $this->isValidLanguage($_GET[$urlParam])) {
                        $detectedLang = $_GET[$urlParam];
                        // Store in session for future requests
                        $_SESSION[$this->config['session_key']] = $detectedLang;
                        // Store in cookie for persistence
                        setcookie(
                            $this->config['cookie_key'],
                            $detectedLang,
                            time() + $this->config['cookie_lifetime'],
                            '/'
                        );
                        break 2; // Exit the loop
                    }
                    break;

                case 'session':
                    if (isset($_SESSION[$this->config['session_key']]) &&
                        $this->isValidLanguage($_SESSION[$this->config['session_key']])) {
                        $detectedLang = $_SESSION[$this->config['session_key']];
                        break 2; // Exit the loop
                    }
                    break;

                case 'cookie':
                    if (isset($_COOKIE[$this->config['cookie_key']]) &&
                        $this->isValidLanguage($_COOKIE[$this->config['cookie_key']])) {
                        $detectedLang = $_COOKIE[$this->config['cookie_key']];
                        // Store in session for future requests
                        $_SESSION[$this->config['session_key']] = $detectedLang;
                        break 2; // Exit the loop
                    }
                    break;

                case 'browser':
                    $browserLang = $this->detectBrowserLanguage();
                    if ($browserLang !== null) {
                        $detectedLang = $browserLang;
                        // Store in session for future requests
                        $_SESSION[$this->config['session_key']] = $detectedLang;
                        break 2; // Exit the loop
                    }
                    break;

                case 'default':
                default:
                    // Use the default language
                    $detectedLang = $this->config['default'];
                    break 2; // Exit the loop
            }
        }

        return $detectedLang;
    }

    /**
     * Detect the browser language
     *
     * @return string|null The detected language code or null if not detected
     */
    private function detectBrowserLanguage() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        // Parse the Accept-Language header
        $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

        // Extract the language codes
        $browserLangs = [];
        foreach ($langs as $lang) {
            $parts = explode(';', $lang);
            $code = trim($parts[0]);
            $code = substr($code, 0, 2); // Get the first 2 characters (language code)
            $browserLangs[] = $code;
        }

        // Find the first supported language
        foreach ($browserLangs as $lang) {
            if ($this->isValidLanguage($lang)) {
                return $lang;
            }
        }

        return null;
    }

    /**
     * Check if a language code is valid
     *
     * @param string $lang The language code to check
     * @return bool Whether the language is valid
     */
    private function isValidLanguage($lang) {
        return isset($this->config['supported'][$lang]);
    }

    /**
     * Load translations for the current language
     *
     * @return void
     */
    private function loadTranslations() {
        $langPath = PRIVATE_PATH . '/' . $this->config['translations_path'] . '/' . $this->currentLanguage;

        // Load common translations
        $commonFile = $langPath . '/common.php';
        if (file_exists($commonFile)) {
            $this->translations = array_merge($this->translations, require $commonFile);
        }

        // Load frontend translations
        $frontendFile = $langPath . '/frontend.php';
        if (file_exists($frontendFile)) {
            $this->translations = array_merge($this->translations, require $frontendFile);
        }

        // Load admin translations
        $adminFile = $langPath . '/admin.php';
        if (file_exists($adminFile)) {
            $this->translations = array_merge($this->translations, require $adminFile);
        }
    }

    /**
     * Get the current language code
     *
     * @return string The current language code
     */
    public function getCurrentLanguage() {
        return $this->currentLanguage;
    }

    /**
     * Get the current language details
     *
     * @return array The current language details
     */
    public function getCurrentLanguageDetails() {
        return $this->config['supported'][$this->currentLanguage] ?? null;
    }

    /**
     * Get all supported languages
     *
     * @return array The supported languages
     */
    public function getSupportedLanguages() {
        return $this->config['supported'];
    }

    /**
     * Set the current language
     *
     * @param string $lang The language code to set
     * @return bool Whether the language was set successfully
     */
    public function setLanguage($lang) {
        if (!$this->isValidLanguage($lang)) {
            return false;
        }

        $this->currentLanguage = $lang;

        // Store in session
        $_SESSION[$this->config['session_key']] = $lang;

        // Store in cookie
        setcookie(
            $this->config['cookie_key'],
            $lang,
            time() + $this->config['cookie_lifetime'],
            '/'
        );

        // Reload translations
        $this->translations = [];
        $this->loadTranslations();

        return true;
    }

    /**
     * Translate a key
     *
     * @param string $key The translation key
     * @param array $params Parameters to replace in the translation
     * @return string The translated string
     */
    public function translate($key, $params = []) {
        // Get the translation
        $translation = $this->translations[$key] ?? $key;

        // Replace parameters
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $translation = str_replace(':' . $param, $value, $translation);
            }
        }

        return $translation;
    }

    /**
     * Translate a key with plural forms
     *
     * @param string $key The translation key
     * @param int $count The count for pluralization
     * @param array $params Parameters to replace in the translation
     * @return string The translated string
     */
    public function translatePlural($key, $count, $params = []) {
        // Add count to params
        $params['count'] = $count;

        // Get the singular or plural key based on count
        $translationKey = $key . ($count === 1 ? '_singular' : '_plural');

        // Get the translation
        $translation = $this->translations[$translationKey] ?? $key;

        // Replace parameters
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $translation = str_replace(':' . $param, $value, $translation);
            }
        }

        return $translation;
    }

    /**
     * Get the URL for switching to a language
     *
     * @param string $lang The language code to switch to
     * @return string The URL for switching to the language
     */
    public function getLanguageSwitchUrl($lang) {
        $urlParam = $this->config['url_parameter'];
        $currentUrl = $_SERVER['REQUEST_URI'];

        // Remove existing language parameter if present
        $currentUrl = preg_replace('/([?&])' . $urlParam . '=[^&]+(&|$)/', '$1', $currentUrl);

        // Add the new language parameter
        if (strpos($currentUrl, '?') !== false) {
            $currentUrl .= '&' . $urlParam . '=' . $lang;
        } else {
            $currentUrl .= '?' . $urlParam . '=' . $lang;
        }

        // Clean up the URL (remove trailing & or ?)
        $currentUrl = rtrim($currentUrl, '&?');

        return $currentUrl;
    }
}

/**
 * Global translation function
 *
 * @param string $key The translation key
 * @param array $params Parameters to replace in the translation
 * @return string The translated string
 */
function __($key, $params = []) {
    return LanguageManager::getInstance()->translate($key, $params);
}

/**
 * Global plural translation function
 *
 * @param string $key The translation key
 * @param int $count The count for pluralization
 * @param array $params Parameters to replace in the translation
 * @return string The translated string
 */
function __n($key, $count, $params = []) {
    return LanguageManager::getInstance()->translatePlural($key, $count, $params);
}

/**
 * Get the current language code
 *
 * @return string The current language code
 */
function current_language() {
    return LanguageManager::getInstance()->getCurrentLanguage();
}

/**
 * Get the current language details
 *
 * @return array The current language details
 */
function current_language_details() {
    return LanguageManager::getInstance()->getCurrentLanguageDetails();
}

/**
 * Get all supported languages
 *
 * @return array The supported languages
 */
function supported_languages() {
    return LanguageManager::getInstance()->getSupportedLanguages();
}

/**
 * Get the URL for switching to a language
 *
 * @param string $lang The language code to switch to
 * @return string The URL for switching to the language
 */
function language_switch_url($lang) {
    return LanguageManager::getInstance()->getLanguageSwitchUrl($lang);
}
