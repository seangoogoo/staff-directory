<?php
/**
 * Authentication Controller
 *
 * Handles all authentication-related requests including login, logout, and session checks.
 * Follows modern PHP practices with proper separation of concerns.
 */

class AuthController {
    /**
     * Check if a user is logged in
     *
     * @return void Outputs JSON response
     */
    public function checkLogin() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Define constant to prevent direct access to configuration
        if (!defined('AUTH_SYSTEM')) {
            define('AUTH_SYSTEM', true);
        }

        // Include auth functions and configuration
        require_once PUBLIC_PATH . '/admin/auth/auth.php';

        // Set JSON content type
        header('Content-Type: application/json');

        // Add cache control headers to prevent caching
        header('Cache-Control: ' . CACHE_CONTROL_HEADER);
        header('Pragma: ' . PRAGMA_HEADER);

        // Check login status and return as JSON
        $logged_in = is_logged_in();

        // Simplified response - only what's needed
        echo json_encode([
            'logged_in' => $logged_in,
            'timestamp' => time()
        ]);
    }

    /**
     * Process login request
     *
     * @return void Outputs JSON response
     */
    public function login() {
        // Logging is handled by the Monolog logger

        global $logger;

        // Log the start of the login process
        $logger->debug("Login method called");
        $logger->debug("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        $logger->debug("POST data", $_POST);

        // Prevent any output before our JSON
        ob_start();

        // Start session first to ensure consistency
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Define constant to prevent direct access to configuration
        if (!defined('AUTH_SYSTEM')) {
            define('AUTH_SYSTEM', true);
        }

        try {
            // Include auth functions and configuration
            require_once PUBLIC_PATH . '/admin/auth/auth.php';

            // Clear any previous output
            ob_end_clean();

            // Set JSON content type
            header('Content-Type: application/json');

            // Process only POST requests
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $logger->warning("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                return;
            }
            $logger->debug("Request method is POST");

            // Check if data is provided
            if (!isset($_POST['username']) || !isset($_POST['password'])) {
                $logger->warning("Missing username or password in POST data");
                echo json_encode(['success' => false, 'message' => 'Username and password are required']);
                return;
            }
            $logger->debug("Username and password are provided");

            $username = $_POST['username'];
            $password = $_POST['password'];
            $returnUrl = isset($_POST['returnUrl']) ? $_POST['returnUrl'] : DEFAULT_RETURN_URL;

            // Verify credentials
            $logger->debug("Verifying credentials for username: $username");
            $logger->debug("ADMIN_USERNAME constant: " . (defined('ADMIN_USERNAME') ? ADMIN_USERNAME : 'Not defined'));

            if (verify_login($username, $password)) {
                // Login successful
                $logger->info("Login successful for user: $username");
                login_user($username);

                // Return success response with the return URL
                echo json_encode(['success' => true, 'returnUrl' => $returnUrl]);
            } else {
                // Login failed
                $logger->warning("Login failed for user: $username");
                echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
            }
        } catch (Exception $e) {
            // If any error occurs, still return valid JSON
            $logger->error("Exception caught in login method: " . $e->getMessage(), ['exception' => $e]);
            ob_end_clean();
            header('Content-Type: application/json');
            header('Cache-Control: ' . CACHE_CONTROL_HEADER);
            header('Pragma: ' . PRAGMA_HEADER);
            echo json_encode([
                'success' => false,
                'message' => 'Server error occurred'
            ]);
        }
    }

    /**
     * Process logout request
     *
     * @return void Redirects to home page
     */
    public function logout() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Define constant to prevent direct access to configuration
        if (!defined('AUTH_SYSTEM')) {
            define('AUTH_SYSTEM', true);
        }

        // Include auth functions and configuration
        require_once PUBLIC_PATH . '/admin/auth/auth.php';

        // Perform logout
        logout_user();

        // Redirect to home page
        header('Location: ' . APP_BASE_URI);
        exit;
    }
}
