<?php
// Start output buffering to prevent "headers already sent" issues
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle AJAX requests for logo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['js_form_submit'])) {
    // Process form
    require_once '../includes/functions.php';

    // Response will be JSON
    header('Content-Type: application/json');

    // Process the logo form
    $form_result = process_form_submission('logo', $_POST, $_FILES);

    // Update app settings with form results if processing was successful
    if ($form_result && !empty($form_result['settings'])) {
        global $app_settings;
        // Load app settings if not already loaded
        if (!isset($app_settings)) {
            $app_settings = load_app_settings();
        }
        $app_settings = array_merge($app_settings, $form_result['settings']);
    }

    // Return JSON response
    $response = [
        'success' => $form_result['success'],
        'message' => $form_result['message']
    ];

    // Add logo path if available
    if ($form_result['success'] && isset($form_result['settings']['custom_logo_path'])) {
        $response['logoPath'] = $form_result['settings']['custom_logo_path'];
    }

    echo json_encode($response);
    exit;
}

// Start with auth checks before any output
require_once 'auth/auth.php';

// Include common functions
require_once '../includes/functions.php';

// Check if user is logged in (will redirect if not logged in)
require_login();

// Now include admin header which outputs HTML with navigation
require_once '../includes/admin_header.php';

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
    else if (isset($_POST['remove_logo_submit'])) {
        // Special case for logo removal via separate button
        // Create temporary POST data with remove_logo=1
        $_POST['remove_logo'] = '1';

        // Process logo removal
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

    // Set session message directly
    if ($form_result) {
        if ($form_result['success']) {
            // Set success message in session
            $_SESSION[$form_result['success_key']] = $form_result['message'];

            // For logo uploads, ensure the success message is written to session before redirect
            if (isset($_POST['save_logo_only']) || isset($_POST['remove_logo_submit'])) {
                session_write_close();
                session_start();
            }
        } else {
            // Set error message in session
            $_SESSION[$form_result['error_key']] = $form_result['message'];
        }

        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get session messages
$success_message = get_session_message('success_message');
$error_message = get_session_message('error_message');
$logo_success_message = get_session_message('logo_success_message');
$logo_error_message = get_session_message('logo_error_message');
$title_success_message = get_session_message('title_success_message');
$title_error_message = get_session_message('title_error_message');

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

            // Check if there's a file upload or removal - these take precedence over visibility
            $has_upload = isset($files_data['custom_logo']) && $files_data['custom_logo']['error'] === UPLOAD_ERR_OK;
            $has_removal = isset($post_data['remove_logo']) && ($post_data['remove_logo'] === '1' || $post_data['remove_logo'] === 1);

            // Only set the visibility message if there's no upload or removal
            if (!$has_upload && !$has_removal) {
                // Set initial success for visibility change if there's no other operation
                $result['success'] = $visibility_result['success'];
                $result['message'] = "Logo visibility settings saved successfully!";
            }

            // Process logo removal
            if ($has_removal) {
                $removal_result = process_logo_removal();
                $result['success'] = $removal_result['success'];
                $result['message'] = $removal_result['message'];

                if ($removal_result['success']) {
                    $result['settings']['custom_logo_path'] = '';
                }
            }
            // Process logo upload
            else if ($has_upload) {
                $upload_result = process_logo_upload($files_data);
                $result['success'] = $upload_result['success'];
                $result['message'] = $upload_result['message'];

                if ($upload_result['success']) {
                    $result['settings']['custom_logo_path'] = $upload_result['logo_path'];
                }
            }
            // Handle logo upload errors
            else if (!$has_removal && isset($files_data['custom_logo']) && $files_data['custom_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $error_message = handle_logo_upload_error($files_data);
                $result['success'] = false;
                $result['message'] = $error_message;
            }

            break;

        case 'title':
            // Set session keys for title operations (use separate keys for title)
            $result['success_key'] = 'title_success_message';
            $result['error_key'] = 'title_error_message';

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
            // Set session keys for title operations (use separate keys for title)
            $result['success_key'] = 'title_success_message';
            $result['error_key'] = 'title_error_message';

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

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
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

                // Set a success message that we'll display on page refresh
                $upload_success_message = "Logo uploaded successfully: " . $files['custom_logo']['name'];

                // FORCE the session variable to be set and saved immediately
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['logo_success_message'] = $upload_success_message;

                // Immediately write the session to disk
                session_write_close();

                // Start it again so we can continue using it
                session_start();

                $result['success'] = true;
                $result['message'] = $upload_success_message;
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
            $result['message'] = "Invalid file type. Please upload an image file (JPG, PNG, GIF, SVG, WEBP).";
        } else if ($files['custom_logo']['size'] > $max_size) {
            $result['message'] = "File too large. Please upload an image under 2MB.";
        } else {
            $result['message'] = "Invalid file. Please upload an image file (JPG, PNG, GIF, SVG, WEBP) under 2MB.";
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
    $original_app_settings = get_default_app_settings();
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

?>

<!-- Logo Settings Section -->
<div class="mb-10 logo-section">

    <h1 class="text-2xl font-semibold text-gray-900 mb-4">Settings</h1>
    <?php if (!empty($logo_success_message)): ?>
        <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
            <?php echo htmlspecialchars($logo_success_message); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($logo_error_message)): ?>
        <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
            <?php echo htmlspecialchars($logo_error_message); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Logo Settings</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Logo Preview</h3>
                <div class="relative logo-preview border border-gray-200 p-4 rounded-md flex items-center justify-center bg-gray-50 h-48 w-full">
                        <?php if (!empty($app_settings['custom_logo_path'])): ?>
                        <img id="image-preview" src="<?php echo htmlspecialchars($app_settings['custom_logo_path']); ?>" alt="Custom Logo" class="max-h-full max-w-full">
                        <?php else: ?>
                        <img id="image-preview" src="/assets/images/staff-directory-logo.svg" alt="Default Logo" class="max-h-full max-w-full">
                        <?php endif; ?>
                    <button type="button" id="remove-image" style="display: <?php echo !empty($app_settings['custom_logo_path']) ? 'flex' : 'none'; ?>"
                            class="absolute -top-2 -right-2 bg-gray-600 text-white rounded-full h-6 w-6 flex items-center justify-center hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        <span class="sr-only">Remove logo</span>
                        <i class="ri-close-line text-sm"></i>
                    </button>
                </div>
            </div>

            <div>
                <form method="post" action="" enctype="multipart/form-data" id="logo-form">
                    <div class="mb-3">
                        <label for="custom_logo" class="block text-sm font-medium text-gray-700 mt-4 mb-2">Custom Logo:</label>
                        <input type="file" name="custom_logo" id="custom_logo" class="hidden" accept="image/*">
                        <div id="logo-dropzone" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md cursor-pointer hover:border-indigo-500 hover:bg-gray-50 transition-colors duration-150">
                            <div class="space-y-1 text-center w-full">
                                <!-- Icon -->
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <span class="relative rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                        Drag & drop your logo here
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500">or click to browse files (JPG, PNG, GIF, SVG, WEBP only)</p>
                                <div class="dropzone-file-info text-xs text-gray-500 font-medium mt-1" style="display: none;"></div>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Recommended size: 50x50px. Max size: 2MB.</p>
                    </div>

                    <!-- Hide logo option -->
                    <div>
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                       id="show_logo" name="show_logo" value="1"
                                       <?php echo (isset($app_settings['show_logo']) && $app_settings['show_logo'] === '1') ? 'checked' : ''; ?>>
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="show_logo" class="font-medium text-gray-700">Show logo in header</label>
                                <p class="text-gray-500">Uncheck to hide both custom and default logo</p>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden input for removing logo, initially disabled -->
                    <input type="hidden" id="remove_logo" name="remove_logo" value="0">
                </form>
            </div>
        </div>

        <!-- Buttons section at the bottom right -->
        <div class="flex justify-end pt-4">
            <div class="flex space-x-4">
                <button type="submit" form="logo-form" name="save_logo_only" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Save Logo Settings
                </button>

                <?php if (!empty($app_settings['custom_logo_path'])): ?>
                <button type="submit" form="logo-form" name="remove_logo_submit" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Remove Custom Logo
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Title Settings Section -->
<div class="mb-10">

    <?php if (!empty($title_success_message)): ?>
        <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
            <?php echo $title_success_message; ?>
        </div>
        <?php endif; ?>

    <?php if (!empty($title_error_message)): ?>
        <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
            <?php echo $title_error_message; ?>
        </div>
        <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Title Settings</h2>
        <form method="post" action="" class="space-y-6" id="title-form">
            <div>
                <label for="frontend_title" class="block text-sm font-medium text-gray-700">Frontend Title:</label>
                <input type="text" name="frontend_title" id="frontend_title"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        value="<?php echo htmlspecialchars($app_settings['frontend_title']); ?>">
                </div>

            <div>
                <label for="admin_title" class="block text-sm font-medium text-gray-700">Admin Area Title:</label>
                <input type="text" name="admin_title" id="admin_title"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        value="<?php echo htmlspecialchars($app_settings['admin_title']); ?>">
            </div>
        </form>

        <!-- Buttons section at the bottom right -->
        <div class="flex justify-end pt-6">
            <div class="flex space-x-4">
                <button type="submit" form="title-form" name="reset_title_settings"
                        class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Reset to Defaults
                </button>
                <button type="submit" form="title-form" name="save_title_settings"
                        class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Save Title Settings
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Placeholder Image Settings Section -->
<div class="mb-10">

    <?php if (!empty($success_message)): ?>
        <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Placeholder Image Settings</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Preview</h3>
                <div class="border border-gray-200 p-4 rounded-md flex items-center justify-center bg-gray-50">
                    <img id="preview-image" src="" alt="Sample Placeholder" class="h-48 w-48 rounded-lg object-cover">
                </div>
                <p class="mt-3 text-xs text-gray-500 italic">
                    Note: Changes are shown in the preview but only applied to all placeholder images when you click Save.
                </p>
            </div>

            <div>
                <form method="post" action="" class="space-y-6" id="placeholder-form">
                    <div>
                        <label for="font_weight" class="block text-sm font-medium text-gray-700 mt-4 mb-2">Font Weight:</label>
                        <select name="font_weight" id="font_weight"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <?php
                            $weights = ['Thin', 'ExtraLight', 'Light', 'Regular', 'Medium', 'SemiBold', 'Bold', 'ExtraBold', 'Black'];
                            foreach ($weights as $weight) {
                                $selected = ($weight === $app_settings['font_weight']) ? 'selected' : '';
                                echo "<option value=\"{$weight}\" {$selected}>{$weight}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="rounded-md bg-blue-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="ri-information-line text-blue-400"></i>
                            </div>
                            <div class="flex items-center ml-3">
                                <p class="text-sm text-blue-700">
                                    Placeholder backgrounds now use department colors automatically.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="font_size_factor" class="block text-sm font-medium text-gray-700">Font Size:</label>
                        <input type="range" name="font_size_factor" id="font_size_factor"
                            class="mt-1 w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                            min="1" max="6" step="0.5"
                            value="<?php echo isset($app_settings['font_size_factor']) ? $app_settings['font_size_factor'] : 3; ?>">
                        <div class="flex justify-between px-2 text-xs text-gray-600">
                            <span>Small</span>
                            <span class="current-value font-medium"><?php echo isset($app_settings['font_size_factor']) ? $app_settings['font_size_factor'] : 3; ?></span>
                            <span>Large</span>
                        </div>
                    </div>
                </form>
            </div>

        </div>

        <!-- Buttons section at the bottom right -->
        <div class="flex justify-end pt-4">
            <button type="submit" form="placeholder-form" name="save_settings"
                    class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save Settings
            </button>
        </div>
    </div>
</div>

<script>
    // Update preview when settings change
    document.addEventListener('DOMContentLoaded', function() {
        // Get form elements
        const fontSizeSlider = document.getElementById('font_size_factor')
        const fontWeightSelect = document.getElementById('font_weight')
        const fontSizeDisplay = document.querySelector('.current-value')
        const previewImage = document.getElementById('preview-image')

        // Generate placeholder URL with specific parameters
        function generatePlaceholderUrl(fontWeight, fontSizeFactor) {
            // Use Admin Buddy as our demo initials
            const timestamp = new Date().getTime() // Prevent caching
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
                logoDropzone.classList.add('border-indigo-500', 'bg-gray-50')
            })

            logoDropzone.addEventListener('dragleave', function() {
                logoDropzone.classList.remove('border-indigo-500', 'bg-gray-50')
            })

            logoDropzone.addEventListener('drop', function(e) {
                e.preventDefault()
                logoDropzone.classList.remove('border-indigo-500', 'bg-gray-50')

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
                        removeButton.style.display = 'flex'
                    }

                    // Hide default logo message if exists
                    const defaultMessage = document.querySelector('.logo-preview .text-xs')
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
                    let defaultMessage = logoPreview.querySelector('.text-xs')
                    if (!defaultMessage) {
                        defaultMessage = document.createElement('p')
                        defaultMessage.className = 'absolute bottom-2 left-0 right-0 text-center text-xs text-gray-500'
                        defaultMessage.textContent = 'Default logo is currently in use'
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

<!-- Add special JavaScript for direct form handling -->
<script>
function handleLogoFormSubmit(form) {
    // Create a formData object
    const formData = new FormData(form)

    // Add a special flag for JavaScript handling
    formData.append('js_form_submit', '1')

    // Create an AJAX request
    const xhr = new XMLHttpRequest()
    xhr.open('POST', window.location.href)

    // Track upload progress if desired
    xhr.upload.onprogress = function(e) {
        // Optional: Add progress indicator
    }

    // When completed
    xhr.onload = function() {
        // Check if the response was JSON
        try {
            const response = JSON.parse(xhr.responseText)
            if(response.success) {
                // Display success message directly
                showMessage('success', response.message)

                // Update image preview if needed
                if (response.logoPath) {
                    document.getElementById('image-preview').src = response.logoPath
                    document.getElementById('remove-image').style.display = 'flex'
                }
            } else {
                // Display error message
                showMessage('error', response.message || 'An error occurred')
            }
        } catch(e) {
            // If response wasn't JSON, reload the page
            window.location.reload()
        }
    }

    // Handle errors
    xhr.onerror = function() {
        showMessage('error', 'Failed to upload logo')
    }

    // Send the form data
    xhr.send(formData)

    // Prevent regular form submission
    return false
}

function showMessage(type, message) {
    // Remove any existing messages
    const existingMessages = document.querySelectorAll('.logo-section .alert-message')
    existingMessages.forEach(el => el.remove())

    // Create a new message element
    const messageEl = document.createElement('div')
    messageEl.className = type === 'success'
        ? 'p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg alert-message'
        : 'p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg alert-message'
    messageEl.setAttribute('role', 'alert')
    messageEl.textContent = message

    // Insert it at the beginning of the logo section
    const logoSection = document.querySelector('.logo-section')
    const heading = logoSection.querySelector('h2')
    logoSection.insertBefore(messageEl, heading.nextSibling)

    // Scroll to the message
    messageEl.scrollIntoView({behavior: 'smooth', block: 'center'})
}
</script>

<?php require_once '../includes/footer.php';

// Make sure all session data is written
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Flush the output buffer to ensure all content is sent
if (ob_get_level()) {
    ob_end_flush();
}
?>