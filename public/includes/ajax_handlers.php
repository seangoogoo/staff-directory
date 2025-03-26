<?php
/**
 * AJAX Handlers for Staff Directory
 * 
 * This file contains handlers for AJAX requests from the frontend
 */

// Prevent errors from appearing in output
error_reporting(0);
ini_set('display_errors', 0);

// Set content type to JSON before any output
header('Content-Type: application/json');

try {
    // Include required files
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/../config/database.php';
    
    // Check if we have a valid database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    // Handle different AJAX actions
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'get_departments_by_company':
                // Get company name from the request
                $company_name = isset($_GET['company']) ? sanitize_input($_GET['company']) : '';
                
                // Get departments for this company
                $departments = get_departments_by_company($conn, $company_name);
                
                // Return as JSON
                echo json_encode(['success' => true, 'departments' => $departments]);
                break;
                
            case 'get_companies_by_department':
                // Get department name from the request
                $department_name = isset($_GET['department']) ? sanitize_input($_GET['department']) : '';
                
                // Get companies for this department
                $companies = get_companies_by_department($conn, $department_name);
                
                // Return as JSON
                echo json_encode(['success' => true, 'companies' => $companies]);
                break;
                
            default:
                // Invalid action
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
                break;
        }
    } else {
        // No action specified
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No action specified']);
    }
} catch (Throwable $e) {
    // Something went wrong, return error message
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}

// Make sure we exit to prevent any additional output
exit;
