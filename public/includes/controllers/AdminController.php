<?php
/**
 * Admin Controller
 * 
 * Handles all admin-related requests.
 */

class AdminController {
    /**
     * Display the admin dashboard
     * 
     * @return void Outputs HTML
     */
    public function index() {
        // Include the original admin/index.php file
        require_once PUBLIC_PATH . '/admin/index.php';
    }

    /**
     * Display the add staff form
     * 
     * @return void Outputs HTML
     */
    public function add() {
        // Include the original admin/add.php file
        require_once PUBLIC_PATH . '/admin/add.php';
    }

    /**
     * Process the add staff form submission
     * 
     * @return void Outputs HTML
     */
    public function store() {
        // Include the original admin/add.php file
        require_once PUBLIC_PATH . '/admin/add.php';
    }

    /**
     * Display the edit staff form
     * 
     * @return void Outputs HTML
     */
    public function edit() {
        // Include the original admin/edit.php file
        require_once PUBLIC_PATH . '/admin/edit.php';
    }

    /**
     * Process the edit staff form submission
     * 
     * @return void Outputs HTML
     */
    public function update() {
        // Include the original admin/edit.php file
        require_once PUBLIC_PATH . '/admin/edit.php';
    }

    /**
     * Display the departments page
     * 
     * @return void Outputs HTML
     */
    public function departments() {
        // Include the original admin/departments.php file
        require_once PUBLIC_PATH . '/admin/departments.php';
    }

    /**
     * Display the companies page
     * 
     * @return void Outputs HTML
     */
    public function companies() {
        // Include the original admin/companies.php file
        require_once PUBLIC_PATH . '/admin/companies.php';
    }

    /**
     * Display the settings page
     * 
     * @return void Outputs HTML
     */
    public function settings() {
        // Include the original admin/settings.php file
        require_once PUBLIC_PATH . '/admin/settings.php';
    }

    /**
     * Process the settings form submission
     * 
     * @return void Outputs HTML
     */
    public function updateSettings() {
        // Include the original admin/settings.php file
        require_once PUBLIC_PATH . '/admin/settings.php';
    }
}
