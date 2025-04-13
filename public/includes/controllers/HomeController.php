<?php
/**
 * Home Controller
 * 
 * Handles requests to the home page.
 */

class HomeController {
    /**
     * Display the home page
     * 
     * @return void Outputs HTML
     */
    public function index() {
        // Include the original index.php file
        require_once PUBLIC_PATH . '/index.php';
    }
}
