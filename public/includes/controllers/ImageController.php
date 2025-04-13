<?php
/**
 * Image Controller
 * 
 * Handles image generation and processing.
 */

class ImageController {
    /**
     * Generate placeholder image
     * 
     * @return void Outputs image data
     */
    public function generatePlaceholder() {
        // Include the original generate_placeholder.php file
        require_once PUBLIC_PATH . '/includes/generate_placeholder.php';
    }
}
