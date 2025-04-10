<?php
// Start session first to avoid header warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Formatter\LineFormatter;

// Define base paths if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2)); // Project root
    define('PRIVATE_PATH', BASE_PATH); // Currently same as BASE_PATH
    define('PUBLIC_PATH', __DIR__ . '/../'); // Current public directory
    define('APP_BASE_URI', ''); // Empty for now, will be '/staff-directory' later
}

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

// Make logger available globally
global $logger;
