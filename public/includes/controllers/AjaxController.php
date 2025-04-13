<?php
/**
 * Ajax Controller
 * 
 * Handles all AJAX requests for the application.
 */

class AjaxController {
    /**
     * Handle general AJAX requests
     * 
     * @return void Outputs JSON response
     */
    public function handle() {
        // Include the original ajax_handlers.php file
        require_once PUBLIC_PATH . '/includes/ajax_handlers.php';
    }

    /**
     * Check for duplicate entries
     * 
     * @return void Outputs JSON response
     */
    public function checkDuplicate() {
        // Include the original check_duplicate.php file
        require_once PUBLIC_PATH . '/includes/check_duplicate.php';
    }
}
