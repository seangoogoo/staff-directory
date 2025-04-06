<?php
// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Database Configuration using environment variables
define('DB_HOST', isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost');
define('DB_USER', isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : '');
define('DB_PASS', isset($_ENV['DB_PASS']) ? $_ENV['DB_PASS'] : '');
define('DB_NAME', isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : '');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
