<?php
// Common functions for the application


/**
 * Get placeholder settings from database (legacy function for backward compatibility)
 *
 * @return array Associative array of placeholder settings with defaults
 */
function get_placeholder_settings_from_db() {
    // Call load_app_settings to ensure we get defaults as well
    return load_app_settings();
}

/**
 * Mix a color with black or white, similar to CSS color-mix function
 *
 * This function simulates the CSS color-mix functionality by mixing a color
 * with either black or white at a specified percentage.
 *
 * Examples:
 * - For dark variant (like color-mix(in srgb, color, black 50%)):
 *   color_mix($hex_color, 'dark', 50)
 * - For light variant (like color-mix(in srgb, color, white 90%)):
 *   color_mix($hex_color, 'light', 90)
 *
 * @param string $hex_color The base color in hex format (with or without #)
 * @param string $variant Either 'dark' (mix with black) or 'light' (mix with white)
 * @param int $percentage Percentage of black or white to mix (0-100)
 * @return string The resulting hex color code (with #)
 */
function color_mix($hex_color, $variant = 'dark', $percentage = 50) {
    // Remove # if present
    $hex = ltrim($hex_color, '#');

    // Handle shorthand hex (e.g., #abc)
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    // Convert hex to RGB
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // Ensure percentage is between 0 and 100
    $percentage = max(0, min(100, $percentage));

    // Calculate the mix factor (0.0 - 1.0)
    $factor = $percentage / 100;

    if ($variant === 'dark') {
        // Mix with black (decrease RGB values)
        $r = round($r * (1 - $factor));
        $g = round($g * (1 - $factor));
        $b = round($b * (1 - $factor));
    } else {
        // Mix with white (increase RGB values)
        $r = round($r + (255 - $r) * $factor);
        $g = round($g + (255 - $g) * $factor);
        $b = round($b + (255 - $b) * $factor);
    }

    // Convert back to hex
    $hex_result = sprintf('#%02x%02x%02x', $r, $g, $b);

    return $hex_result;
}

/**
 * Get the URL for a staff member's profile picture or generate a placeholder with initials
 *
 * @param array $staff Staff member data
 * @param string $size Size of the image in format 'widthxheight'
 * @param string $font_weight Font weight to use for the placeholder image
 * @param string $bg_color Background color for the placeholder image (hex format)
 * @param string $text_color Text color for the placeholder image (hex format)
 * @param float $font_size_factor Font size factor (1-6, higher means larger font)
 * @return string The image URL
 */
function get_staff_image_url($staff, $size = '600x600', $font_weight = null, $bg_color = null, $text_color = null, $font_size_factor = null) {
    // Default settings
    $default_settings = [
        'font_weight' => 'Regular',
        'bg_color' => '#cccccc',
        'text_color' => '#ffffff',
        'font_size_factor' => 3 // Default font size factor (higher = larger font)
    ];

    // Load settings with defaults from the database
    // get_placeholder_settings_from_db() now calls load_app_settings() which provides all defaults
    $placeholder_settings = get_placeholder_settings_from_db();
    $default_settings = array_merge($default_settings, $placeholder_settings);

    // Use provided parameters if set, otherwise use defaults
    $font_weight = $font_weight ?: $default_settings['font_weight'];
    $bg_color = $bg_color ?: $default_settings['bg_color'];
    $contrast = get_text_contrast_class($bg_color, false);
    // Returns: 'dark' or 'light'
    // $text_color = $text_color ?: $default_settings['text_color'];
    // $text_color = color_mix($bg_color, 'dark', 20);
    $text_color = $contrast == 'dark' ? color_mix($bg_color, 'dark', 20) : color_mix($bg_color, 'light', 35);
    $font_size_factor = $font_size_factor ?: $default_settings['font_size_factor'];

    // Make sure we have the hex2rgb function
    if (!function_exists('hex2rgb')) {
        /**
         * Convert hex color code to RGB array
         *
         * @param string $hex_color Hex color code (e.g. #cccccc or cccccc)
         * @return array Array with r, g, b values
         */
        function hex2rgb($hex_color) {
            // Remove # if present
            $hex_color = ltrim($hex_color, '#');

            // Parse the hex color
            if (strlen($hex_color) == 3) {
                // Convert short hex (e.g. #abc) to full hex (e.g. #aabbcc)
                $r = hexdec(substr($hex_color, 0, 1) . substr($hex_color, 0, 1));
                $g = hexdec(substr($hex_color, 1, 1) . substr($hex_color, 1, 1));
                $b = hexdec(substr($hex_color, 2, 1) . substr($hex_color, 2, 1));
            } else {
                // Standard hex color (e.g. #aabbcc)
                $r = hexdec(substr($hex_color, 0, 2));
                $g = hexdec(substr($hex_color, 2, 2));
                $b = hexdec(substr($hex_color, 4, 2));
            }

            // Return RGB array
            return array('r' => $r, 'g' => $g, 'b' => $b);
        }
    }
    // Generate initials for the placeholder image
    $initials = '';
    if (!empty($staff['first_name'])) $initials .= strtoupper(substr($staff['first_name'], 0, 1));
    if (!empty($staff['last_name'])) $initials .= strtoupper(substr($staff['last_name'], 0, 1));

    // If initials are empty, use a default
    if (empty($initials)) {
        $initials = 'NA';
    }

    // Parse size dimensions
    list($width, $height) = explode('x', $size);

    // Ensure minimum dimensions for readable text
    $width = max(100, (int)$width);
    $height = max(100, (int)$height);

    // Generate a settings hash to automatically detect changes
    $settings_hash = md5($font_weight . $bg_color . $text_color . $font_size_factor);
    $placeholder_filename = 'placeholder_' . $initials . '_' . $width . 'x' . $height . '_' . $settings_hash . '.webp';
    $placeholder_path = __DIR__ . '/../uploads/placeholders/' . $placeholder_filename;
    $placeholder_url = '/uploads/placeholders/' . $placeholder_filename;

    // If no profile picture is set, generate or return the placeholder
    if (empty($staff['profile_picture'])) {
        // Check if placeholder already exists
        if (!file_exists($placeholder_path)) {
            // Clean up old placeholder images with the same initials and dimensions but different settings
            $placeholder_dir = __DIR__ . '/../uploads/placeholders/';
            if (is_dir($placeholder_dir)) {
                $pattern = 'placeholder_' . $initials . '_' . $width . 'x' . $height . '_*.png';
                $old_files = glob($placeholder_dir . $pattern);
                foreach ($old_files as $old_file) {
                    // Skip the current file
                    if (basename($old_file) !== $placeholder_filename) {
                        @unlink($old_file);
                    }
                }
            }
            // Create placeholder image using native GD
            try {
                // Create a blank image with the specified dimensions
                $image = imagecreatetruecolor($width, $height);

                // Convert hex colors to RGB
                $bg_rgb = hex2rgb($bg_color);
                $text_rgb = hex2rgb($text_color);

                // Set background color
                $bg_color_resource = imagecolorallocate($image, $bg_rgb['r'], $bg_rgb['g'], $bg_rgb['b']);
                imagefill($image, 0, 0, $bg_color_resource);

                // Set text color
                $text_color_resource = imagecolorallocate($image, $text_rgb['r'], $text_rgb['g'], $text_rgb['b']);

                // Validate font weight (ensure it's one of the available options)
                $valid_weights = ['Thin', 'ExtraLight', 'Light', 'Regular', 'Medium', 'SemiBold', 'Bold', 'ExtraBold', 'Black'];
                if (!in_array($font_weight, $valid_weights)) {
                    $font_weight = 'Regular'; // Default to Regular if invalid weight specified
                }

                // Store the font weight in a global variable for future reference
                global $staff_placeholder_font_weight;
                $staff_placeholder_font_weight = $font_weight;

                // Path to the Outfit font with specified weight
                $font_path = __DIR__ . '/../assets/fonts/Outfit/static/Outfit-' . $font_weight . '.ttf';

                // Fallback to variable font if the specified weight is not available
                if (!file_exists($font_path)) {
                    $font_path = __DIR__ . '/../assets/fonts/Outfit/Outfit-VariableFont_wght.ttf';
                }

                // Check if the font file exists
                if (file_exists($font_path)) {
                    // Calculate font size based on image dimensions and font size factor
                    // For the staff cards (600x400), we want a larger font
                    if ($width == 600 && $height == 400) {
                        // Base size of 100 multiplied by the font size factor (1-6)
                        $font_size = 100 * ($font_size_factor / 3); // Scale based on factor
                    } else {
                        // Dynamic size based on the font size factor
                        $font_size = min($width, $height) / (9 / $font_size_factor); // Dynamic size for other dimensions
                    }

                    // Get text dimensions for centering
                    $bbox = imagettfbbox($font_size, 0, $font_path, $initials);
                    $text_width = $bbox[2] - $bbox[0];
                    $text_height = $bbox[1] - $bbox[7];

                    // Calculate position to center the text
                    $x = ($width - $text_width) / 2 - $bbox[0];
                    $y = ($height - $text_height) / 2 - $bbox[7];

                    // We're using the static Regular weight (400) version of the font
                    // This gives us the exact weight we want without needing variable font support
                    // Add text to the image (initials) with TrueType font
                    imagettftext($image, $font_size, 0, $x, $y, $text_color_resource, $font_path, $initials);
                } else {
                    // Fallback to built-in font if the TrueType font is not available
                    $font_size = 5; // Maximum GD built-in font size

                    // Get text dimensions
                    $text_width = imagefontwidth($font_size) * strlen($initials);
                    $text_height = imagefontheight($font_size);

                    // Calculate position to center the text
                    $x = ($width - $text_width) / 2;
                    $y = ($height - $text_height) / 2;

                    // Add text to the image (initials) with built-in font
                    imagestring($image, $font_size, $x, $y, $initials, $text_color_resource);
                }

                // Save the image as WebP (better compression than PNG)
                // Quality ranges from 0 (worst quality, smaller file) to 100 (best quality, larger file)
                imagewebp($image, $placeholder_path, 85); // 85% quality offers good balance
                imagedestroy($image);
            } catch (\Exception $e) {
                // Log error
                error_log('Failed to generate placeholder image: ' . $e->getMessage());

                // Create a fallback image with larger text
                $fallback_image = imagecreatetruecolor($width, $height);
                // Use the same colors as the main image attempt
                $bg_color = imagecolorallocate($fallback_image, $bg_rgb['r'], $bg_rgb['g'], $bg_rgb['b']);
                $text_color = imagecolorallocate($fallback_image, $text_rgb['r'], $text_rgb['g'], $text_rgb['b']);
                imagefill($fallback_image, 0, 0, $bg_color);

                // Use the largest built-in font and center it
                $font_size = 5; // Maximum GD built-in font size
                $text_width = imagefontwidth($font_size) * strlen($initials);
                $text_height = imagefontheight($font_size);
                $x = ($width - $text_width) / 2;
                $y = ($height - $text_height) / 2;
                imagestring($fallback_image, $font_size, $x, $y, $initials, $text_color);
                // Save fallback image as WebP with 85% quality
                imagewebp($fallback_image, $placeholder_path, 85);
                imagedestroy($fallback_image);
            }
        }

        return $placeholder_url;
    }

    // Check if the file exists in the uploads directory
    $file_path = __DIR__ . "/../uploads/" . $staff['profile_picture'];
    if (file_exists($file_path)) {
        return '/uploads/' . $staff['profile_picture'];
    } else {
        // If the file doesn't exist, log the missing image
        // Add to a global array that will be output at the end of the page
        global $missing_images;
        if (!isset($missing_images)) {
            $missing_images = [];
        }
        $full_name = $staff['first_name'] . ' ' . $staff['last_name'];
        $missing_images[] = "Profile image missing for {$full_name}: {$staff['profile_picture']}";

        return $placeholder_url;
    }
}

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Upload profile picture
 */
function upload_profile_picture($file) {
    $target_dir = __DIR__ . "/../uploads/";
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Check if image file is a actual image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return ["success" => false, "message" => "File is not an image."];
    }

    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        return ["success" => false, "message" => "Sorry, your file is too large."];
    }

    // Allow certain file formats
    if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "webp") {
        return ["success" => false, "message" => "Sorry, only JPG, JPEG, PNG & WebP files are allowed."];
    }

    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["success" => true, "filename" => $new_filename];
    } else {
        return ["success" => false, "message" => "Sorry, there was an error uploading your file."];
    }
}

/**
 * Get all staff members
 */
function get_all_staff_members($conn, $sort_by = 'last_name', $sort_order = 'ASC', $search = '', $department = '', $company = '') {
    // Validate sort_by and sort_order parameters
    $allowed_sort_fields = ['first_name', 'last_name', 'department', 'job_title', 'email', 'id', 'company'];
    if (!in_array($sort_by, $allowed_sort_fields)) {
        $sort_by = 'last_name';
    }

    $allowed_sort_orders = ['ASC', 'DESC'];
    if (!in_array(strtoupper($sort_order), $allowed_sort_orders)) {
        $sort_order = 'ASC';
    }

    // Base query without conditions
    $params = [];
    $types = '';

    // Start building the prepared statement - JOIN with departments and companies tables
    $sql = "SELECT s.*, d.name as department, d.color as department_color, "
         . "c.name as company, c.id as company_id, c.logo as company_logo "
         . "FROM staff_members s "
         . "JOIN departments d ON s.department_id = d.id "
         . "JOIN companies c ON s.company_id = c.id "
         . "WHERE 1=1";

    // Add search condition if provided
    if (!empty($search)) {
        $search_param = "%{$search}%";
        $sql .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.job_title LIKE ? OR c.name LIKE ?)";
        $types .= 'ssss';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    // Add department filter if provided
    if (!empty($department)) {
        $sql .= " AND d.name = ?";
        $types .= 's';
        $params[] = $department;
    }

    // Add company filter if provided
    if (!empty($company)) {
        $sql .= " AND c.name = ?";
        $types .= 's';
        $params[] = $company;
    }

    // Adjust sort field if it's department or company
    if ($sort_by === 'department') {
        $sort_by = 'd.name';
    } elseif ($sort_by === 'company') {
        $sort_by = 'c.name';
    } else {
        $sort_by = 's.' . $sort_by;
    }

    // Add sorting - these values are already validated above
    $sql .= " ORDER BY {$sort_by} {$sort_order}";

    // Prepare and execute statement
    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $staff_members = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $staff_members[] = $row;
        }
    }

    $stmt->close();
    return $staff_members;
}

/**
 * Get a staff member by ID
 */
function get_staff_member_by_id($conn, $id) {
    $sql = "SELECT s.*, d.name as department, d.id as department_id, c.id as company_id, c.name as company "
         . "FROM staff_members s "
         . "JOIN departments d ON s.department_id = d.id "
         . "JOIN companies c ON s.company_id = c.id "
         . "WHERE s.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $staff = $result->fetch_assoc();
        $stmt->close();
        return $staff;
    }

    $stmt->close();
    return null;
}

/**
 * Delete a staff member
 */
function delete_staff_member($conn, $id) {
    // Get the profile picture filename
    $staff = get_staff_member_by_id($conn, $id);

    $sql = "DELETE FROM staff_members WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $result = $stmt->execute();
    $stmt->close();

    if ($result === TRUE) {
        // Delete the profile picture if it exists
        if ($staff && !empty($staff['profile_picture'])) {
            $file_path = __DIR__ . "/../uploads/" . $staff['profile_picture'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        return true;
    }

    return false;
}

/**
 * Get all companies
 *
 * @param mysqli $conn Database connection
 * @return array Array of companies with id, name, description, and logo
 */
function get_all_companies($conn) {
    $sql = "SELECT id, name, description, logo FROM companies ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $companies = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $companies[] = $row;
        }
    }

    $stmt->close();
    return $companies;
}

/**
 * Get all company names
 *
 * @param mysqli $conn Database connection
 * @return array Array of company names
 */
function get_all_company_names($conn) {
    $sql = "SELECT name FROM companies ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $companies = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $companies[] = $row['name'];
        }
    }

    $stmt->close();
    return $companies;
}

/**
 * Get department by ID
 */
function get_department_by_id($conn, $id) {
    $sql = "SELECT * FROM departments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $department = $result->fetch_assoc();
        $stmt->close();
        return $department;
    }

    $stmt->close();
    return null;
}

/**
 * Get all departments
 *
 * @param mysqli $conn Database connection
 * @return array Array of departments with id, name, description, and color
 */
function get_all_departments($conn) {
    $sql = "SELECT id, name, description, color FROM departments ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $departments = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $departments[] = $row;
        }
    }

    $stmt->close();
    return $departments;
}

/**
 * Get all department names
 *
 * @param mysqli $conn Database connection
 * @return array Array of department names
 */
function get_all_department_names($conn) {
    $sql = "SELECT name FROM departments ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $departments = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $departments[] = $row['name'];
        }
    }

    $stmt->close();
    return $departments;
}

/**
 * Get departments by company name
 *
 * @param mysqli $conn Database connection
 * @param string $company_name Company name to filter departments by
 * @return array Array of department names that belong to the specified company
 */
function get_departments_by_company($conn, $company_name) {
    // If no company specified, return all departments
    if (empty($company_name)) {
        return get_all_department_names($conn);
    }

    // Query to find departments that have staff members in the specified company
    $sql = "SELECT DISTINCT d.name
            FROM departments d
            JOIN staff_members s ON d.id = s.department_id
            JOIN companies c ON s.company_id = c.id
            WHERE c.name = ?
            ORDER BY d.name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $company_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $departments = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $departments[] = $row['name'];
        }
    }

    $stmt->close();

    return $departments;
}

/**
 * Get companies that have staff members in a specific department
 *
 * @param mysqli $conn Database connection
 * @param string $department_name Department name to filter by
 * @return array Array of company names
 */
function get_companies_by_department($conn, $department_name) {
    // If no department specified, return all companies
    if (empty($department_name)) {
        return get_all_company_names($conn);
    }

    // Query to find companies that have staff members in the specified department
    $sql = "SELECT DISTINCT c.name
            FROM companies c
            JOIN staff_members s ON c.id = s.company_id
            JOIN departments d ON s.department_id = d.id
            WHERE d.name = ?
            ORDER BY c.name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $department_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $companies = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $companies[] = $row['name'];
        }
    }

    $stmt->close();

    return $companies;
}

/**
 * Get department by name
 */
function get_department_by_name($conn, $name) {
    $sql = "SELECT * FROM departments WHERE name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $department = $result->fetch_assoc();
        $stmt->close();
        return $department;
    }

    $stmt->close();
    return null;
}
/**
 * Determine if text should be light or dark based on background color
 *
 * @param string $hex_color The hex color code (with or without #)
 * @param bool $return_class Whether to return the CSS class name or just 'dark'/'light'
 * @return string Either the CSS class name ('dark-text'/'light-text') or just 'dark'/'light'
 */
function get_text_contrast_class($hex_color, $return_class = true) {
    // Remove # if present
    $hex = ltrim($hex_color, '#');

    // Handle shorthand hex (e.g., #abc)
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    // Convert hex to RGB
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // Calculate luminance - standard formula for brightness perception
    $luminance = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    // debug_log("Luminance: " . $luminance);

    // Determine if dark or light based on luminance
    $is_dark = $luminance > 190;

    if ($return_class) {
        // Return appropriate class name based on luminance (original behavior)
        return $is_dark ? 'dark-text' : 'light-text';
    } else {
        // Return just 'dark' or 'light' value
        return $is_dark ? 'dark' : 'light';
    }
}

/**
 * Set a session message
 *
 * @param string $key Session key for message
 * @param string $message Message to store
 * @return bool True if message was set, false otherwise
 */
function set_session_message($key, $message) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Only set message if it's not empty
    if (!empty($message)) {
        $_SESSION[$key] = $message;
        error_log("Session message set: $key = $message");
        return true;
    }
    return false;
}

/**
 * Get and clear a session message
 *
 * @param string $key Session key for message
 * @return string Message from session or empty string if none
 */
function get_session_message($key) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $message = '';

    // Get message if it exists
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        error_log("Retrieved session message: $key = $message");

        // Clear message
        unset($_SESSION[$key]);
    }

    return $message;
}

/**
 * Handle redirect after form processing
 *
 * Used in: admin/settings.php, admin/staff_edit.php, admin/departments.php
 * This function sets session messages and performs a redirect
 *
 * @param string $success_key Session key for success message
 * @param string $success_message Success message to store
 * @param string $error_key Session key for error message
 * @param string $error_message Error message to store
 * @param string $redirect_url URL to redirect to (defaults to current page)
 */
function handle_redirect($success_key, $success_message, $error_key, $error_message, $redirect_url = '') {
    error_log('handle_redirect function called');

    // Make sure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Debug output buffer status
    error_log('Output buffering status: ' . (ob_get_level() ? 'ON (level: ' . ob_get_level() . ')' : 'OFF'));
    error_log('Headers sent: ' . (headers_sent($file, $line) ? 'YES at ' . $file . ':' . $line : 'NO'));

    // Check if headers have already been sent
    if (headers_sent($file, $line)) {
        error_log("Cannot redirect - headers already sent in $file on line $line");
    }

    // Set messages if they exist
    if (!empty($success_message)) {
        $_SESSION[$success_key] = $success_message;
        error_log("Set success message directly in session: $success_key = $success_message");
    }

    if (!empty($error_message)) {
        $_SESSION[$error_key] = $error_message;
        error_log("Set error message directly in session: $error_key = $error_message");
    }

    // If no redirect URL provided, use current page
    if (empty($redirect_url)) {
        $redirect_url = $_SERVER['PHP_SELF'];
    }

    error_log('Attempting to redirect to: ' . $redirect_url);

    // Commit session data before redirecting
    session_write_close();

    // End output buffering if active
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Redirect to prevent form resubmission
    if (!headers_sent()) {
        error_log('Performing redirect with header()');
        header("Location: " . $redirect_url);
        exit;
    } else {
        error_log('Using JavaScript fallback for redirect');
        echo '<script>window.location.href="' . $redirect_url . '";</script>';
        exit;
    }
}

/**
 * Get default application settings
 *
 * Used in: admin/settings.php, includes/header.php, includes/admin_header.php
 * Provides default values for application settings when none are stored
 *
 * @return array Default application settings
 */
function get_default_app_settings()
{
    return [
        'font_weight' => 'Regular',
        'font_size_factor' => 3, // Default font size factor (higher = larger font)
        'custom_logo_path' => '',
        'frontend_title' => 'Staff Directory',
        'admin_title' => 'Staff Directory Admin'
    ];
}

/**
 * Clear placeholder images to force regeneration
 *
 * Used in: admin/settings.php (when settings are changed), admin/staff_edit.php (when staff info is updated)
 * Removes all placeholder images to ensure they're regenerated with current settings
 */
function clear_placeholder_images()
{
    $placeholder_dir = dirname(__DIR__) . '/uploads/placeholders';
    if (is_dir($placeholder_dir)) {
        // Clear both PNG and WebP placeholder images
        $png_files = glob($placeholder_dir . '/*.png');
        $webp_files = glob($placeholder_dir . '/*.webp');
        $all_files = array_merge($png_files, $webp_files);

        foreach ($all_files as $file) {
            @unlink($file);
        }
    }
}

/**
 * Update settings in the database
 *
 * Used in: admin/settings.php, admin/setup.php
 * Updates or inserts settings into the app_settings table
 *
 * @param array $settings Associative array of settings to update
 */
function update_settings_in_db($settings)
{
    global $conn;

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
            $stmt->bind_param("ss", $key, $value);
            $stmt->execute();
        }

        // Close statements
        $check->close();
        $stmt->close();
    }
}



/**
 * Load application settings from database and merge with defaults
 *
 * Used in: admin/settings.php, includes/header.php, includes/admin_header.php, and throughout the application
 * Loads settings from database and merges with defaults
 *
 * @return array Complete application settings
 */
function load_app_settings()
{
    // Start with default settings
    $app_settings = get_default_app_settings();

    // Load settings from database
    global $conn;

    if (isset($conn) && !$conn->connect_error) {
        // Using mysqli instead of PDO
        $stmt = $conn->prepare("SELECT setting_key, setting_value FROM app_settings");

        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();

            // Fetch all rows
            while ($row = $result->fetch_assoc()) {
                // Convert font_size_factor to float for calculations
                if ($row['setting_key'] === 'font_size_factor') {
                    $app_settings[$row['setting_key']] = (float)$row['setting_value'];
                } else {
                    $app_settings[$row['setting_key']] = $row['setting_value'];
                }
            }

            $stmt->close();
        }
    }
    // If database query fails, we'll just use the defaults from get_default_app_settings()

    return $app_settings;
}

/**
 * Log debug information to a file
 *
 * @param mixed $data The data to log
 * @param string $label Optional label for the log entry
 * @param bool $print_r Whether to use print_r (true) or var_export (false)
 * @return void
 */
function debug_log($data, $label = '', $print_r = true) {
    $log_file = __DIR__ . '/../../logs/debug.log';

    // Create logs directory if it doesn't exist
    $log_dir = dirname($log_file);
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    // Format the log entry
    $log_entry = "[" . date('Y-m-d H:i:s') . "]";
    if (!empty($label)) {
        $log_entry .= " [{$label}]";
    }

    // Format the data
    if (is_array($data) || is_object($data)) {
        $log_entry .= "\n" . ($print_r ? print_r($data, true) : var_export($data, true));
    } else {
        $log_entry .= " {$data}";
    }

    // Add a line break at the end
    $log_entry .= "\n\n";

    // Write to log file
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Get company by ID
 *
 * @param mysqli $conn Database connection
 * @param int $id Company ID
 * @return array|null Company data or null if not found
 */
function get_company_by_id($conn, $id) {
    $sql = "SELECT * FROM companies WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * Upload company logo
 *
 * @param array $file The uploaded file from $_FILES
 * @return string|false The path to the uploaded logo or false on failure
 */
function upload_company_logo($file) {
    // Define allowed file types and maximum size
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }

    // Validate file size
    if ($file['size'] > $max_size) {
        return false;
    }

    // Create the upload directory if it doesn't exist
    $upload_dir = __DIR__ . '/../uploads/companies/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate a unique filename
    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('company_') . '.' . $file_ext;
    $upload_path = $upload_dir . $filename;

    // Move the uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Return the relative path
        return '/uploads/companies/' . $filename;
    }

    return false;
}

/**
 * Check if company has staff members
 *
 * @param mysqli $conn Database connection
 * @param int $company_id Company ID
 * @return int Number of staff members associated with the company
 */
function get_company_staff_count($conn, $company_id) {
    $sql = "SELECT COUNT(*) as count FROM staff_members WHERE company_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row['count'];
}

/**
 * Get statistics for all companies
 *
 * @param mysqli $conn Database connection
 * @return array Array of company statistics including company name, logo, staff count, and percentage
 */
function get_all_company_statistics($conn) {
    // Get all companies with their staff counts
    $sql = "SELECT c.id, c.name, c.logo, COUNT(s.id) as staff_count
            FROM companies c
            LEFT JOIN staff_members s ON c.id = s.company_id
            GROUP BY c.id
            ORDER BY staff_count DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $companies = [];
    $total_staff = 0;

    // First pass to get all companies and the total staff count
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
        $total_staff += $row['staff_count'];
    }

    $stmt->close();

    // Second pass to calculate percentages
    foreach ($companies as &$company) {
        $company['percentage'] = $total_staff > 0 ? round(($company['staff_count'] / $total_staff) * 100, 1) : 0;
    }

    return [
        'companies' => $companies,
        'total_staff' => $total_staff
    ];
}

/**
 * Get all department names that have at least one staff member
 *
 * @param mysqli $conn Database connection
 * @return array Array of department names with at least one staff member
 */
function get_active_department_names($conn) {
    $sql = "SELECT DISTINCT d.name
            FROM departments d
            INNER JOIN staff_members s ON d.id = s.department_id
            ORDER BY d.name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row['name'];
    }

    $stmt->close();
    return $departments;
}

/**
 * Get all company names that have at least one staff member
 *
 * @param mysqli $conn Database connection
 * @return array Array of company names with at least one staff member
 */
function get_active_company_names($conn) {
    $sql = "SELECT DISTINCT c.name
            FROM companies c
            INNER JOIN staff_members s ON c.id = s.company_id
            ORDER BY c.name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $companies = [];
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row['name'];
    }

    $stmt->close();
    return $companies;
}

/**
 * Check if a staff member already exists with the same name or email
 *
 * @param mysqli $conn Database connection
 * @param string $first_name First name to check
 * @param string $last_name Last name to check
 * @param string $email Email to check
 * @return array Result with status and message
 */
function check_staff_duplicate($conn, $first_name, $last_name, $email) {
    $result = ['duplicate' => false, 'message' => ''];

    // Check for duplicate name (case insensitive)
    $sql_name = "SELECT id FROM staff_members WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?)";
    $stmt_name = $conn->prepare($sql_name);
    $stmt_name->bind_param("ss", $first_name, $last_name);
    $stmt_name->execute();
    $stmt_name->store_result();

    if ($stmt_name->num_rows > 0) {
        $result['duplicate'] = true;
        $result['message'] = "A staff member with the same name already exists.";
        $stmt_name->close();
        return $result;
    }
    $stmt_name->close();

    // Check for duplicate email (case insensitive)
    $sql_email = "SELECT id FROM staff_members WHERE LOWER(email) = LOWER(?)";
    $stmt_email = $conn->prepare($sql_email);
    $stmt_email->bind_param("s", $email);
    $stmt_email->execute();
    $stmt_email->store_result();

    if ($stmt_email->num_rows > 0) {
        $result['duplicate'] = true;
        $result['message'] = "A staff member with this email address already exists.";
    }
    $stmt_email->close();

    return $result;
}
