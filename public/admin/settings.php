<?php
// Start with auth checks before any output
require_once 'auth/auth.php';

// Include common functions
require_once '../includes/functions.php';

// Check if user is logged in (will redirect if not logged in)
require_login();


// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Now include admin header which outputs HTML with navigation
require_once '../includes/admin_header.php';



//* New functions start

/**
 * Process form submissions based on form type
 *
 * @param string $form_type Type of form being processed (e.g., 'font', 'logo', 'title')
 * @param array $post_data POST data from form submission
 * @param array $files_data FILES data from form submission
 * @return array Result with consistent structure containing success status, messages, and settings
 */
function process_form_submission($form_type, $post_data, $files_data = []) {
    $result = [
        'success' => false,
        'message' => '',
        'settings' => [],
        'success_key' => 'success_message',
        'error_key' => 'error_message'
    ];

    // Process based on form type
    switch ($form_type) {
        case 'font':
            // Process font settings
            $font_result = process_font_settings($post_data);

            // Normalize result format
            $result['success'] = !empty($font_result['messages']['success']);
            $result['message'] = $result['success'] ? $font_result['messages']['success'] : $font_result['messages']['error'];
            $result['settings'] = $font_result['settings'];
            break;

        case 'logo':
            // Set session keys for logo operations
            $result['success_key'] = 'logo_success_message';
            $result['error_key'] = 'logo_error_message';

            // Process logo visibility first (always happens)
            $visibility_result = process_logo_visibility($post_data);
            $result['settings']['show_logo'] = $visibility_result['show_logo'];

            // Process logo removal
            if (isset($post_data['remove_logo']) && ($post_data['remove_logo'] === '1' || $post_data['remove_logo'] === 1)) {
                $removal_result = process_logo_removal();
                $result['success'] = $removal_result['success'];
                $result['message'] = $removal_result['message'];

                if ($removal_result['success']) {
                    $result['settings']['custom_logo_path'] = '';
                }
            }
            // Process logo upload
            else if (isset($files_data['custom_logo']) && $files_data['custom_logo']['error'] === UPLOAD_ERR_OK) {
                $upload_result = process_logo_upload($files_data);
                $result['success'] = $upload_result['success'];
                $result['message'] = $upload_result['message'];

                if ($upload_result['success']) {
                    $result['settings']['custom_logo_path'] = $upload_result['logo_path'];
                }
            }
            // Handle logo upload errors
            else if (!isset($post_data['remove_logo'])) {
                $error_message = handle_logo_upload_error($files_data);
                $result['success'] = false;
                $result['message'] = $error_message;
            }
            break;

        case 'title':
            // Set session keys for title operations
            $result['success_key'] = 'logo_success_message';
            $result['error_key'] = 'logo_error_message';

            // Process title settings
            $title_result = process_title_settings($post_data);
            $result['success'] = $title_result['success'];
            $result['message'] = $title_result['message'];

            if ($title_result['success']) {
                $result['settings']['frontend_title'] = $title_result['titles']['frontend_title'];
                $result['settings']['admin_title'] = $title_result['titles']['admin_title'];
            }
            break;

        case 'title_reset':
            // Set session keys for title operations
            $result['success_key'] = 'logo_success_message';
            $result['error_key'] = 'logo_error_message';

            // Reset title settings
            $reset_result = reset_title_settings();
            $result['success'] = $reset_result['success'];
            $result['message'] = $reset_result['message'];

            if ($reset_result['success']) {
                $result['settings']['frontend_title'] = $reset_result['titles']['frontend_title'];
                $result['settings']['admin_title'] = $reset_result['titles']['admin_title'];
            }
            break;

        default:
            $result['message'] = 'Unknown form type: ' . $form_type;
            break;
    }

    return $result;
}

// Update settings in database function has been moved to functions.php

// Settings are already loaded in admin_header.php
// No need to load settings again

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_result = null;

    // Determine which form was submitted and process it
    if (isset($_POST['save_settings'])) {
        // Process font settings form
        $form_result = process_form_submission('font', $_POST);
    }
    else if (isset($_POST['save_logo_only'])) {
        // Process logo form
        $form_result = process_form_submission('logo', $_POST, $_FILES);
    }
    else if (isset($_POST['save_title_settings'])) {
        // Process title settings form
        $form_result = process_form_submission('title', $_POST);
    }
    else if (isset($_POST['reset_title_settings'])) {
        // Process title reset form
        $form_result = process_form_submission('title_reset', $_POST);
    }

    // Update app settings with form results if processing was successful
    if ($form_result && !empty($form_result['settings'])) {
        $app_settings = array_merge($app_settings, $form_result['settings']);
    }

    // Handle redirect with appropriate messages if form was processed
    if ($form_result) {
        handle_redirect(
            $form_result['success_key'],
            $form_result['success'] ? $form_result['message'] : '',
            $form_result['error_key'],
            !$form_result['success'] ? $form_result['message'] : ''
        );
    }
}

// Generate a sample placeholder image with current settings
$sample_image_url = generate_sample_placeholder_image($app_settings);

// Sample placeholder image generation function has been moved to functions.php

//* New functions end

// Session message and redirect functions have been moved to functions.php

// Default settings function has been moved to functions.php


// Clear placeholder images function has been moved to functions.php

/**
 * Process and save font settings
 *
 * @param array $post_data POST data from form submission
 * @return array Updated settings and status messages
 */
function process_font_settings($post_data) {
    global $conn;
    $messages = ['success' => '', 'error' => ''];
    $settings = [];

    // Validate font weight
    $valid_weights = ['Thin', 'ExtraLight', 'Light', 'Regular', 'Medium', 'SemiBold', 'Bold', 'ExtraBold', 'Black'];
    $font_weight = isset($post_data['font_weight']) && in_array($post_data['font_weight'], $valid_weights)
        ? $post_data['font_weight']
        : 'Regular';

    // Validate font size factor (between 1 and 6)
    $font_size_factor = isset($post_data['font_size_factor']) && is_numeric($post_data['font_size_factor'])
        && $post_data['font_size_factor'] >= 1 && $post_data['font_size_factor'] <= 6
        ? (float)$post_data['font_size_factor']
        : 3;

    // Save settings to database
    try {
        // Update each setting in the database
        $settings = [
            'font_weight' => $font_weight,
            'font_size_factor' => $font_size_factor
        ];

        update_settings_in_db($settings);
        clear_placeholder_images();

        $messages['success'] = "Settings saved successfully!";
    } catch (Exception $e) {
        $messages['error'] = "Database error: " . $e->getMessage();
    }

    return [
        'settings' => $settings,
        'messages' => $messages
    ];
}

/**
 * Process logo visibility setting
 *
 * @param array $post_data POST data from form submission
 * @return array Result with status and messages
 */
function process_logo_visibility($post_data) {
    global $conn;
    $result = ['success' => false, 'message' => ''];

    // Process show_logo option
    $show_logo = isset($post_data['show_logo']) ? '1' : '0';

    try {
        // Update show_logo setting
        $stmt = $conn->prepare("UPDATE app_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'show_logo'");
        $stmt->bind_param("s", $show_logo);
        $stmt->execute();
        $stmt->close();

        $result['success'] = true;
        $result['show_logo'] = $show_logo;
    } catch (Exception $e) {
        $result['message'] = "Database error: " . $e->getMessage();
    }

    return $result;
}

/**
 * Delete logo file
 *
 * @param string $logo_path Path to the logo file
 */
function delete_logo_file($logo_path) {
    // Get absolute paths
    $app_root = realpath(__DIR__ . '/..');
    $old_logo = $app_root . $logo_path;

    // Try to delete the file if it exists
    if (file_exists($old_logo)) {
        unlink($old_logo);
    } else {
        // Fallback: search for logo files in the uploads directory
        $logos_dir = $app_root . '/uploads/logos/';

        if (is_dir($logos_dir)) {
            $files = scandir($logos_dir);
            $custom_logos = [];

            // Find all custom logo files
            foreach ($files as $file) {
                if (strpos($file, 'custom_logo_') === 0) {
                    $custom_logos[] = $file;
                }
            }

            if (!empty($custom_logos)) {
                // Sort files by name (which includes timestamp) to get the most recent
                rsort($custom_logos);
                $latest_logo = $logos_dir . $custom_logos[0];

                // Attempt to delete the file
                unlink($latest_logo);
            }
        }
    }
}

/**
 * Process logo removal
 *
 * @return array Result with status and messages
 */
function process_logo_removal() {
    global $conn, $app_settings;
    $result = ['success' => false, 'message' => ''];

    try {
        // Re-query the database to ensure we have the most up-to-date logo path
        $logo_path_query = $conn->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'custom_logo_path'");
        $logo_path_query->execute();
        $logo_path_result = $logo_path_query->get_result();
        $logo_path_row = $logo_path_result->fetch_assoc();
        $logo_path_query->close();

        // Store the logo path before clearing it from the database
        $old_logo_path = null;
        if ($logo_path_row && !empty($logo_path_row['setting_value'])) {
            $old_logo_path = $logo_path_row['setting_value'];
        } else if (!empty($app_settings['custom_logo_path'])) {
            // Fallback to app_settings if database query fails
            $old_logo_path = $app_settings['custom_logo_path'];
        }

        // Remove logo by setting empty path in database
        $empty_path = '';
        $stmt = $conn->prepare("UPDATE app_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'custom_logo_path'");
        $stmt->bind_param("s", $empty_path);
        $stmt->execute();
        $stmt->close();

        $result['success'] = true;
        $result['message'] = "Logo has been reset to default!";

        // Delete old logo file if it exists
        if ($old_logo_path) {
            delete_logo_file($old_logo_path);
        }
    } catch (Exception $e) {
        $result['message'] = "Database error: " . $e->getMessage();
    }

    return $result;
}

/**
 * Process logo upload
 *
 * @param array $files Files from form submission
 * @return array Result with status and messages
 */
function process_logo_upload($files) {
    global $conn, $app_settings;
    $result = ['success' => false, 'message' => ''];

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
    $max_size = 2 * 1024 * 1024; // 2MB

    // Validate file type and size
    if (in_array($files['custom_logo']['type'], $allowed_types) && $files['custom_logo']['size'] <= $max_size) {
        // Create uploads/logos directory if it doesn't exist
        $upload_dir = __DIR__ . '/../uploads/logos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $file_ext = pathinfo($files['custom_logo']['name'], PATHINFO_EXTENSION);
        $filename = 'custom_logo_' . time() . '.' . $file_ext;
        $file_path = $upload_dir . $filename;

        // Move uploaded file
        if (move_uploaded_file($files['custom_logo']['tmp_name'], $file_path)) {
            // Save path to database (relative path for portability)
            $logo_path = '/uploads/logos/' . $filename;

            try {
                $stmt = $conn->prepare("UPDATE app_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'custom_logo_path'");
                $stmt->bind_param("s", $logo_path);
                $stmt->execute();
                $stmt->close();

                $result['success'] = true;
                $result['message'] = "Logo updated successfully!";
                $result['logo_path'] = $logo_path;

                // Delete old logo if there was one
                if (!empty($app_settings['custom_logo_path']) && $app_settings['custom_logo_path'] !== $logo_path) {
                    delete_logo_file($app_settings['custom_logo_path']);
                }
            } catch (Exception $e) {
                $result['message'] = "Database error: " . $e->getMessage();
            }
        } else {
            $result['message'] = "Error uploading logo. Please try again.";
        }
    } else {
        // Validation failed, store appropriate error message
        if (!in_array($files['custom_logo']['type'], $allowed_types)) {
            $result['message'] = "Invalid file type. Please upload an image file (JPG, PNG, GIF, SVG or WEBP).";
        } else if ($files['custom_logo']['size'] > $max_size) {
            $result['message'] = "File too large. Please upload an image under 2MB.";
        } else {
            $result['message'] = "Invalid file. Please upload an image file (JPG, PNG, GIF, SVG or WEBP) under 2MB.";
        }
    }

    return $result;
}

/**
 * Handle logo upload error
 *
 * @param array $files Files from form submission
 * @return string Error message
 */
function handle_logo_upload_error($files) {
    $error_message = "Please select a logo file to upload.";

    if (isset($files['custom_logo'])) {
        // Check if there was an error code
        $error_code = $files['custom_logo']['error'];

        // Provide human-readable error based on the error code
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];

        // Set more specific error message if available
        if (isset($error_messages[$error_code])) {
            $error_message = $error_messages[$error_code];
        }
    }

    return $error_message;
}

/**
 * Process title settings
 *
 * @param array $post_data POST data from form submission
 * @return array Result with status and messages
 */
function process_title_settings($post_data) {
    global $conn;
    $result = ['success' => false, 'message' => ''];

    // Update titles - allow empty values
    $frontend_title = filter_input(INPUT_POST, 'frontend_title', FILTER_SANITIZE_SPECIAL_CHARS);
    $admin_title = filter_input(INPUT_POST, 'admin_title', FILTER_SANITIZE_SPECIAL_CHARS);

    $titles = [
        'frontend_title' => $frontend_title,
        'admin_title' => $admin_title
    ];

    try {
        update_settings_in_db($titles);

        $result['success'] = true;
        $result['message'] = "Title settings saved successfully!";
        $result['titles'] = $titles;
    } catch (Exception $e) {
        $result['message'] = "Database error: " . $e->getMessage();
    }

    return $result;
}

/**
 * Reset title settings to defaults
 *
 * @return array Result with status and messages
 */
function reset_title_settings() {
    $original_app_settings= get_default_app_settings();
    $result = ['success' => false, 'message' => ''];

    try {
        // Get default values from .env file or fallback to default app settings stored in functions.php
        $default_frontend_title = isset($_ENV['DEFAULT_FRONTEND_TITLE']) && $_ENV['DEFAULT_FRONTEND_TITLE'] !== '' ? $_ENV['DEFAULT_FRONTEND_TITLE'] : $original_app_settings['frontend_title'];
        $default_admin_title = isset($_ENV['DEFAULT_ADMIN_TITLE']) && $_ENV['DEFAULT_ADMIN_TITLE'] !== '' ? $_ENV['DEFAULT_ADMIN_TITLE'] : $original_app_settings['admin_title'];

        // Set default titles in database
        $titles = [
            'frontend_title' => $default_frontend_title,
            'admin_title' => $default_admin_title
        ];

        update_settings_in_db($titles);

        $result['success'] = true;
        $result['message'] = "Title settings reset to defaults successfully!";
        $result['titles'] = $titles;
    } catch (Exception $e) {
        $result['message'] = "Database error: " . $e->getMessage();
    }

    return $result;
}

// Load application settings function has been moved to functions.php

// Settings are already loaded in admin_header.php
// Just get session messages

// Get session messages
$success_message = get_session_message('success_message');
$error_message = get_session_message('error_message');
$logo_success_message = get_session_message('logo_success_message');
$logo_error_message = get_session_message('logo_error_message');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    // Validate font weight
    $valid_weights = ['Thin', 'ExtraLight', 'Light', 'Regular', 'Medium', 'SemiBold', 'Bold', 'ExtraBold', 'Black'];
    $font_weight = isset($_POST['font_weight']) && in_array($_POST['font_weight'], $valid_weights)
        ? $_POST['font_weight']
        : 'Regular';

    // Validate font size factor (between 1 and 6)
    $font_size_factor = isset($_POST['font_size_factor']) && is_numeric($_POST['font_size_factor'])
        && $_POST['font_size_factor'] >= 1 && $_POST['font_size_factor'] <= 6
        ? (float)$_POST['font_size_factor']
        : 3;

    // Save settings to database
    try {
        // Update each setting in the database
        $settings = [
            'font_weight' => $font_weight,
            'font_size_factor' => $font_size_factor
        ];

        foreach ($settings as $key => $value) {
            // Check if setting exists
            $check = $conn->prepare("SELECT id FROM app_settings WHERE setting_key = ?");
            $check->bind_param("s", $key);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                // Update existing setting
                $stmt = $conn->prepare("UPDATE app_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                $stmt->bind_param("ss", $value, $key);
                $stmt->execute();
            } else {
                // Insert new setting
                $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->bind_param("ss", $value, $key);
                $stmt->execute();
            }

            // Close statements
            $check->close();
            $stmt->close();
        }

        // Store success message in session
        $_SESSION['success_message'] = "Settings saved successfully!";

        // Update our current settings for this page
        $app_settings['font_weight'] = $font_weight;
        $app_settings['bg_color'] = $bg_color;
        $app_settings['text_color'] = $text_color;
        $app_settings['font_size_factor'] = $font_size_factor;

        // Clear placeholder images to force regeneration
        $placeholder_dir = __DIR__ . '/../uploads/placeholders';
        if (is_dir($placeholder_dir)) {
            // Clear both PNG and WebP placeholder images
            $png_files = glob($placeholder_dir . '/*.png');
            $webp_files = glob($placeholder_dir . '/*.webp');
            $all_files = array_merge($png_files, $webp_files);

            foreach ($all_files as $file) {
                @unlink($file);
            }
        }

        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        // Store error message in session
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();

        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle logo settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_logo_only'])) {
    // Process show_logo option
    $show_logo = isset($_POST['show_logo']) ? '1' : '0';

    try {
        // Update show_logo setting
        $stmt = $conn->prepare("UPDATE app_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'show_logo'");
        $stmt->bind_param("s", $show_logo);
        $stmt->execute();
        $stmt->close();

        // Update current settings
        $app_settings['show_logo'] = $show_logo;
    } catch (Exception $e) {
        // Store error message in session
        $_SESSION['logo_error_message'] = "Database error: " . $e->getMessage();

        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Process logo removal or upload
    // Handle logo removal
    if (isset($_POST['remove_logo']) && ($_POST['remove_logo'] === '1' || $_POST['remove_logo'] === 1)) {

        // Re-query the database to ensure we have the most up-to-date logo path
        // This is critical for proper logo deletion
        $logo_path_query = $conn->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'custom_logo_path'");
        $logo_path_query->execute();
        $logo_path_result = $logo_path_query->get_result();
        $logo_path_row = $logo_path_result->fetch_assoc();
        $logo_path_query->close();

        // Store the logo path before clearing it from the database
        $old_logo_path = null;
        if ($logo_path_row && !empty($logo_path_row['setting_value'])) {
            $old_logo_path = $logo_path_row['setting_value'];
        } else if (!empty($app_settings['custom_logo_path'])) {
            // Fallback to app_settings if database query fails
            $old_logo_path = $app_settings['custom_logo_path'];
        }

        // Remove logo by setting empty path in database
        try {
            $empty_path = '';
            $stmt = $conn->prepare("UPDATE app_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'custom_logo_path'");
            $stmt->bind_param("s", $empty_path);
            $stmt->execute();
            $stmt->close();

            // Store success message in session
            $_SESSION['logo_success_message'] = "Logo has been reset to default!";

            // Delete old logo file if it exists
            if ($old_logo_path) {
                // Get absolute paths
                $app_root = realpath(__DIR__ . '/..');
                $old_logo = $app_root . $old_logo_path;

                // Try to delete the file if it exists
                if (file_exists($old_logo)) {
                    unlink($old_logo);
                } else {
                    // Fallback: search for logo files in the uploads directory
                    $logos_dir = $app_root . '/uploads/logos/';

                    if (is_dir($logos_dir)) {
                        $files = scandir($logos_dir);
                        $custom_logos = [];

                        // Find all custom logo files
                        foreach ($files as $file) {
                            if (strpos($file, 'custom_logo_') === 0) {
                                $custom_logos[] = $file;
                            }
                        }

                        if (!empty($custom_logos)) {
                            // Sort files by name (which includes timestamp) to get the most recent
                            rsort($custom_logos);
                            $latest_logo = $logos_dir . $custom_logos[0];

                            // Attempt to delete the file
                            unlink($latest_logo);
                        }
                    }
                }
            }

            // Update current settings
            $app_settings['custom_logo_path'] = '';

            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } catch (Exception $e) {
            // Store error message in session
            $_SESSION['logo_error_message'] = "Database error: " . $e->getMessage();

            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
    // Process custom logo upload
    else if (isset($_FILES['custom_logo']) && $_FILES['custom_logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
        $max_size = 2 * 1024 * 1024; // 2MB

        // Log upload attempt details


        // Validate file type and size
        if (in_array($_FILES['custom_logo']['type'], $allowed_types) && $_FILES['custom_logo']['size'] <= $max_size) {
            // Create uploads/logos directory if it doesn't exist
            $upload_dir = __DIR__ . '/../uploads/logos/';
            if (!is_dir($upload_dir)) {

                mkdir($upload_dir, 0755, true);
            }

            // Generate unique filename
            $file_ext = pathinfo($_FILES['custom_logo']['name'], PATHINFO_EXTENSION);
            $filename = 'custom_logo_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $filename;
            // Move uploaded file
            if (move_uploaded_file($_FILES['custom_logo']['tmp_name'], $file_path)) {
                // Save path to database (relative path for portability)
                $logo_path = '/uploads/logos/' . $filename;

                // Update settings in database
                try {
                    $stmt = $conn->prepare("UPDATE app_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'custom_logo_path'");
                    $stmt->bind_param("s", $logo_path);
                    $stmt->execute();
                    $stmt->close();

                    // Store success message in session
                    $_SESSION['logo_success_message'] = "Logo updated successfully!";

                    // Delete old logo if there was one
                    if (!empty($app_settings['custom_logo_path']) && $app_settings['custom_logo_path'] !== $logo_path) {
                        // Get absolute path to old logo
                        $app_root = realpath(__DIR__ . '/..');
                        $old_logo_relative = $app_settings['custom_logo_path'];
                        $old_logo = $app_root . $old_logo_relative;

                        // Try to delete the old logo file if it exists
                        if (file_exists($old_logo)) {
                            unlink($old_logo);
                        }
                    }

                    // Update current settings
                    $app_settings['custom_logo_path'] = $logo_path;

                    // Redirect to prevent form resubmission
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;

                } catch (Exception $e) {
                    // Store error message in session
                    $_SESSION['logo_error_message'] = "Database error: " . $e->getMessage();

                    // Redirect to prevent form resubmission
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                }
            } else {
                // Store error message in session
                $_SESSION['logo_error_message'] = "Error uploading logo. Please try again.";

                // Redirect to prevent form resubmission
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            // Validation failed, store appropriate error message
            if (!in_array($_FILES['custom_logo']['type'], $allowed_types)) {
                $_SESSION['logo_error_message'] = "Invalid file type. Please upload an image file (JPG, PNG, GIF, SVG or WEBP).";
            } else if ($_FILES['custom_logo']['size'] > $max_size) {
                $_SESSION['logo_error_message'] = "File too large. Please upload an image under 2MB.";
            } else {
                $_SESSION['logo_error_message'] = "Invalid file. Please upload an image file (JPG, PNG, GIF, SVG or WEBP) under 2MB.";
            }

            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    } else if (!isset($_POST['remove_logo'])) {
        // Only show this error if we're not trying to remove the logo
        if (isset($_FILES['custom_logo'])) {
            // Check if there was an error code
            $error_code = $_FILES['custom_logo']['error'];

            // Provide human-readable error based on the error code
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ];

            // Set more specific error message if available
            if (isset($error_messages[$error_code])) {
                $_SESSION['logo_error_message'] = $error_messages[$error_code];
            } else {
                // Default message if error code isn't recognized
                $_SESSION['logo_error_message'] = "Please select a logo file to upload.";
            }
        } else {
            // No file information at all
            $_SESSION['logo_error_message'] = "Please select a logo file to upload.";
        }

        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle title settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_title_settings'])) {
    // Update titles - allow empty values
    $frontend_title = filter_input(INPUT_POST, 'frontend_title', FILTER_SANITIZE_SPECIAL_CHARS);
    $admin_title = filter_input(INPUT_POST, 'admin_title', FILTER_SANITIZE_SPECIAL_CHARS);

    $titles = [
        'frontend_title' => $frontend_title,
        'admin_title' => $admin_title
    ];

    try {
        foreach ($titles as $key => $value) {
            $stmt = $conn->prepare("UPDATE app_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
            $stmt->bind_param("ss", $value, $key);
            $stmt->execute();
            $stmt->close();
        }

        // Update current settings
        $app_settings['frontend_title'] = $frontend_title;
        $app_settings['admin_title'] = $admin_title;

        // Store success message in session
        $_SESSION['logo_success_message'] = "Title settings saved successfully!";

        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        // Store error message in session
        $_SESSION['logo_error_message'] = "Database error: " . $e->getMessage();

        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle reset title settings to defaults from .env file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_title_settings'])) {
    // Use the reset_title_settings() function for consistency
    $reset_result = reset_title_settings();

    if ($reset_result['success']) {
        // Update current settings
        $app_settings['frontend_title'] = $reset_result['titles']['frontend_title'];
        $app_settings['admin_title'] = $reset_result['titles']['admin_title'];

        // Store success message in session
        $_SESSION['logo_success_message'] = $reset_result['message'];
    } else {
        // Store error message in session
        $_SESSION['logo_error_message'] = $reset_result['message'];
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// App settings are already loaded as a global variable in admin_header.php
// No need to reload them here

// Generate a sample placeholder image with current settings
$sample_initials = 'AB';
$sample_size = '200x200';
$sample_image_url = get_staff_image_url([
    'first_name' => 'Admin',
    'last_name' => 'Buddy',
    'profile_picture' => ''
], $sample_size);

?>

    <h1 class="page-title">Application Settings</h1>

    <!-- Logo Settings Section -->
    <div class="settings-section">
        <h2 class="page-title">Logo Settings</h2>

        <?php if (!empty($logo_success_message) && isset($_POST['save_logo_only'])): ?>
            <div class="alert alert-success"><?php echo $logo_success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($logo_error_message) && isset($_POST['save_logo_only'])): ?>
            <div class="alert alert-danger"><?php echo $logo_error_message; ?></div>
        <?php endif; ?>



        <div class="settings-container">
            <div class="row">
                <div class="col-md-6">
                    <form method="post" action="" enctype="multipart/form-data" id="logo-form">
                        <div class="form-group">
                            <label for="custom_logo">Custom Logo:</label>
                            <input type="file" name="custom_logo" id="custom_logo" class="dropzone-input" accept="image/*">
                            <div class="dropzone" id="logo-dropzone">
                                <div class="dropzone-icon">
                                    <i class="lni lni-cloud-upload"></i>
                                </div>
                                <div class="dropzone-text">Drag & drop your logo here</div>
                                <div class="dropzone-subtext">or click to browse files (JPG, PNG, GIF, SVG, WEBP only)</div>
                                <div class="dropzone-file-info" style="display: none;"></div>
                            </div>
                            <small class="form-text text-muted">Recommended size: 50x50px. Max size: 2MB.</small>
                        </div>

                        <!-- Hide logo option -->
                        <div class="form-group form-check mt-3">
                            <input type="checkbox" class="form-check-input" id="show_logo" name="show_logo" value="1" <?php echo (isset($app_settings['show_logo']) && $app_settings['show_logo'] === '1') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="show_logo">Show logo in header</label>
                            <small class="form-text text-muted">Uncheck to hide both custom and default logo</small>
                        </div>

                        <!-- Hidden input for removing logo, initially disabled -->
                        <input type="hidden" id="remove_logo" name="remove_logo" value="0">

                        <div class="form-group">
                            <button type="submit" name="save_logo_only" class="btn btn-primary">Save Logo Settings</button>
                        </div>
                    </form>
                </div>

                <div class="col-md-6">
                    <div class="preview-container">
                        <h3>Logo Preview</h3>
                        <div class="logo-preview">
                            <?php if (!empty($app_settings['custom_logo_path'])): ?>
                                <img id="image-preview" src="<?php echo htmlspecialchars($app_settings['custom_logo_path']); ?>" alt="Custom Logo" class="img-fluid">
                            <?php else: ?>
                                <img id="image-preview" src="/assets/images/staff-directory-logo.svg" alt="Default Logo" class="img-fluid">
                                <p class="text-muted mt-2"><small>Default logo is currently in use</small></p>
                            <?php endif; ?>
                            <div class="remove-image" id="remove-image" style="display: <?php echo !empty($app_settings['custom_logo_path']) ? 'block' : 'none'; ?>">
                                <i class="lni lni-xmark"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Title Settings Section -->
    <div class="settings-section">
        <h2 class="page-title">Title Settings</h2>

        <?php if (!empty($logo_success_message) && isset($_POST['save_title_settings'])): ?>
            <div class="alert alert-success"><?php echo $logo_success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($logo_error_message) && isset($_POST['save_title_settings'])): ?>
            <div class="alert alert-danger"><?php echo $logo_error_message; ?></div>
        <?php endif; ?>

        <div class="settings-container">
            <form method="post" action="">
                <div class="form-group">
                    <label for="frontend_title">Frontend Title:</label>
                    <input type="text" name="frontend_title" id="frontend_title" class="form-control"
                        value="<?php echo htmlspecialchars($app_settings['frontend_title']); ?>">
                </div>

                <div class="form-group">
                    <label for="admin_title">Admin Area Title:</label>
                    <input type="text" name="admin_title" id="admin_title" class="form-control"
                        value="<?php echo htmlspecialchars($app_settings['admin_title']); ?>">
                </div>

                <div class="form-group">
                    <button type="submit" name="reset_title_settings" class="btn btn-secondary ml-2">Reset to Defaults</button>
                    <button type="submit" name="save_title_settings" class="btn btn-primary">Save Title Settings</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Placeholder Image Settings Section -->
    <div class="settings-section">
        <h2 class="page-title">Placeholder Image Settings</h2>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

    <div class="settings-container">
        <div class="row">
            <div class="col-md-6">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="font_weight">Font Weight:</label>
                        <select name="font_weight" id="font_weight" class="form-control">
                            <?php
                            $weights = ['Thin', 'ExtraLight', 'Light', 'Regular', 'Medium', 'SemiBold', 'Bold', 'ExtraBold', 'Black'];
                            foreach ($weights as $weight) {
                                $selected = ($weight === $app_settings['font_weight']) ? 'selected' : '';
                                echo "<option value=\"{$weight}\" {$selected}>{$weight}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <p class="text-info"><i class="fa fa-info-circle"></i> Placeholder backgrounds now use department colors automatically.</p>
                    </div>

                    <div class="form-group">
                        <label for="font_size_factor">Font Size:</label>
                        <input type="range" name="font_size_factor" id="font_size_factor" class="form-control"
                               min="1" max="6" step="0.5"
                               value="<?php echo isset($app_settings['font_size_factor']) ? $app_settings['font_size_factor'] : 3; ?>">
                        <div class="range-labels">
                            <span class="small">Small</span>
                            <span class="current-value"><?php echo isset($app_settings['font_size_factor']) ? $app_settings['font_size_factor'] : 3; ?></span>
                            <span class="large">Large</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="save_settings" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>

            <div class="col-md-6">
                <div class="preview-container">
                    <h3>Preview</h3>
                    <div class="sample-image">
                        <img id="preview-image" src="<?php echo $sample_image_url; ?>" alt="Sample Placeholder" class="img-fluid">
                    </div>
                    <p class="text-muted mt-3">
                       <small><i>Note: Changes are shown in the preview but only applied to all placeholder images when you click Save.</i></small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Update preview when settings change
    document.addEventListener('DOMContentLoaded', function() {
        // Get form elements
        const fontSizeSlider = document.getElementById('font_size_factor')
        const fontWeightSelect = document.getElementById('font_weight')
        const fontSizeDisplay = document.querySelector('.range-labels .current-value')
        const previewImage = document.getElementById('preview-image')

        // Generate placeholder URL with specific parameters
        function generatePlaceholderUrl(fontWeight, fontSizeFactor) {
            // Use Admin Buddy as our demo initials
            const timestamp = new Date().getTime() // Prevent caching
            console.log(`Generating preview with font size factor: ${fontSizeFactor}`)
            // Force clearing out the browser's cache by adding timestamp and random value
            return `../includes/generate_placeholder.php?name=${encodeURIComponent('Admin Buddy')}&size=200x200&font_weight=${encodeURIComponent(fontWeight)}&font_size_factor=${fontSizeFactor}&nocache=${timestamp}-${Math.random()}`
        }

        // Update preview image with current settings
        function updatePreview() {
            if (!fontWeightSelect || !fontSizeSlider || !previewImage) return

            const fontWeight = fontWeightSelect.value
            const fontSizeFactor = fontSizeSlider.value

            // Update font size display
            if (fontSizeDisplay) {
                const value = parseFloat(fontSizeFactor).toFixed(1)
                // Remove trailing zero if value is whole number
                const displayValue = value.endsWith('.0') ? value.slice(0, -2) : value
                fontSizeDisplay.textContent = displayValue
            }

            // Force image update by creating a new Image object
            const newImage = new Image()
            newImage.onload = function() {
                previewImage.src = this.src
            }
            newImage.src = generatePlaceholderUrl(fontWeight, fontSizeFactor)

            // For debugging
            console.log('Preview update requested for font size factor:', fontSizeFactor)
        }

        // Add event listeners
        if (fontSizeSlider) {
            // Listen to both input and change events to ensure it works across all browsers
            fontSizeSlider.addEventListener('input', updatePreview)
            fontSizeSlider.addEventListener('change', updatePreview)
            // Manually trigger the update once to ensure initial state is correct
            updatePreview()
        }

        if (fontWeightSelect) {
            fontWeightSelect.addEventListener('change', updatePreview)
        }
    })
</script>

<!-- Logo Dropzone Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get elements
        const logoDropzone = document.getElementById('logo-dropzone')
        const fileInput = document.getElementById('custom_logo')
        const imagePreview = document.getElementById('image-preview')
        const removeButton = document.getElementById('remove-image')
        const defaultLogo = '/assets/images/staff-directory-logo.svg'
        const fileInfoElement = document.querySelector('.dropzone-file-info')

        // Initialize dropzone functionality
        if (logoDropzone && fileInput) {
            // Handle clicking on the dropzone
            logoDropzone.addEventListener('click', function() {
                fileInput.click()
            })

            // Handle drag and drop events
            logoDropzone.addEventListener('dragover', function(e) {
                e.preventDefault()
                logoDropzone.classList.add('dragover')
            })

            logoDropzone.addEventListener('dragleave', function() {
                logoDropzone.classList.remove('dragover')
            })

            logoDropzone.addEventListener('drop', function(e) {
                e.preventDefault()
                logoDropzone.classList.remove('dragover')

                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files
                    handleFileSelection()
                }
            })

            // Handle file input change
            fileInput.addEventListener('change', handleFileSelection)
        }

        // Function to handle file selection
        function handleFileSelection() {
            if (fileInput.files && fileInput.files[0]) {
                const file = fileInput.files[0]

                // Check if file is an image
                if (!file.type.match('image.*')) {
                    alert('Please select an image file (JPG, PNG, GIF, SVG, WEBP)')
                    fileInput.value = ''
                    return
                }

                // Check file size (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size exceeds 2MB limit')
                    fileInput.value = ''
                    return
                }

                // Show file info
                if (fileInfoElement) {
                    fileInfoElement.textContent = file.name + ' (' + formatFileSize(file.size) + ')'
                    fileInfoElement.style.display = 'block'
                }

                // Update preview
                const reader = new FileReader()
                reader.onload = function(e) {
                    if (imagePreview) {
                        imagePreview.src = e.target.result
                    }

                    // Show remove button
                    if (removeButton) {
                        removeButton.style.display = 'block'
                    }

                    // Hide default logo message if exists
                    const defaultMessage = document.querySelector('.logo-preview .text-muted')
                    if (defaultMessage) {
                        defaultMessage.style.display = 'none'
                    }

                    // Reset the remove_logo value to 0
                    const removeInput = document.getElementById('remove_logo')
                    if (removeInput) {
                        removeInput.value = '0'
                    }
                }
                reader.readAsDataURL(file)
            }
        }

        // Format file size to human-readable format
        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' bytes'
            else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
            else return (bytes / 1048576).toFixed(1) + ' MB'
        }

        // Handle remove button
        if (removeButton) {
            removeButton.addEventListener('click', function(e) {
                e.preventDefault()

                // Reset the preview to default logo
                if (imagePreview) {
                    imagePreview.src = defaultLogo
                }

                // Clear the file input
                if (fileInput) {
                    fileInput.value = ''
                }

                // Hide the file info text
                if (fileInfoElement) {
                    fileInfoElement.style.display = 'none'
                    fileInfoElement.textContent = ''
                }

                // Hide the remove button
                removeButton.style.display = 'none'

                // Show 'default logo' message
                const logoPreview = document.querySelector('.logo-preview')
                if (logoPreview) {
                    // Check if the message exists, if not create it
                    let defaultMessage = logoPreview.querySelector('.text-muted')
                    if (!defaultMessage) {
                        defaultMessage = document.createElement('p')
                        defaultMessage.className = 'text-muted mt-2'
                        const small = document.createElement('small')
                        small.textContent = 'Default logo is currently in use'
                        defaultMessage.appendChild(small)
                        logoPreview.appendChild(defaultMessage)
                    } else {
                        defaultMessage.style.display = 'block'
                    }
                }

                // Set the remove_logo value to 1 to signal logo removal
                let removeInput = document.getElementById('remove_logo')
                if (removeInput) {
                    removeInput.value = '1'
                }
            })
        }
    })
</script>

<?php require_once '../includes/footer.php'; ?>