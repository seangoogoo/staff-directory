<?php
// Start session first to avoid header warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include centralized path configuration
require_once __DIR__ . '/paths.php';

// Require autoloader - now PRIVATE_PATH is defined
require_once PRIVATE_PATH . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Formatter\LineFormatter;

// Include auth system first to setup authentication constants
require_once PUBLIC_PATH . '/admin/auth/auth.php';

// Create global logger instance
$logger = new Logger('staff-directory');

// Add processors for extra information
$logger->pushProcessor(new IntrospectionProcessor());

if ($_ENV['DEV_MODE'] === 'true') {
    // Development logging setup
    $debugHandler = new StreamHandler(
        PRIVATE_PATH . '/logs/debug.log',
        Logger::DEBUG
    );

    // Custom format to match old debug_log style
    $debugFormatter = new LineFormatter(
        "[%datetime%] %channel%.%level_name%: %message% %context%\n",
        "Y-m-d H:i:s" // Date format
    );
    $debugHandler->setFormatter($debugFormatter);
    $logger->pushHandler($debugHandler);
} else {
    // Production logging setup
    $productionHandler = new RotatingFileHandler(
        PRIVATE_PATH . '/logs/app.log',
        30, // Keep 30 days of logs
        Logger::ERROR
    );
    $logger->pushHandler($productionHandler);
}

// Load core dependencies
require_once PRIVATE_PATH . '/config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/AssetManager.php';

// Load language manager
require_once __DIR__ . '/LanguageManager.php';

// Initialize language manager (this will detect and set the current language)
$languageManager = LanguageManager::getInstance();

// Initialize AssetManager
$assetManager = new AssetManager(PUBLIC_PATH);

// Make logger and language manager available globally
global $logger, $languageManager;
