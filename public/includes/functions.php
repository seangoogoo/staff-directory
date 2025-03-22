<?php
// Common functions for the application

/**
 * Get placeholder settings from database
 * 
 * @return array Associative array of placeholder settings
 */
function get_placeholder_settings_from_db() {
    global $conn;
    $placeholder_settings = [];
    
    if (isset($conn) && !$conn->connect_error) {
        // Using mysqli instead of PDO
        $stmt = $conn->prepare("SELECT setting_key, setting_value FROM placeholder_settings");
        
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Fetch all rows
            while ($row = $result->fetch_assoc()) {
                // Convert font_size_factor to float for calculations
                if ($row['setting_key'] === 'font_size_factor') {
                    $placeholder_settings[$row['setting_key']] = (float)$row['setting_value'];
                } else {
                    $placeholder_settings[$row['setting_key']] = $row['setting_value'];
                }
            }
            
            $stmt->close();
        }
    }
    
    return $placeholder_settings;
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

    // Load custom settings from database if available
    $placeholder_settings = get_placeholder_settings_from_db();
    if (!empty($placeholder_settings)) {
        $default_settings = array_merge($default_settings, $placeholder_settings);
    }
    
    // Fallback to file-based settings if database settings couldn't be loaded
    // This provides backward compatibility during migration
    if (empty($placeholder_settings)) {
        $settings_file = __DIR__ . '/placeholder_settings.php';
        if (file_exists($settings_file)) {
            include $settings_file;
            // $placeholder_settings will be loaded from the file
            if (isset($placeholder_settings) && is_array($placeholder_settings)) {
                $default_settings = array_merge($default_settings, $placeholder_settings);
            }
        }
    }

    // Use provided parameters if set, otherwise use defaults
    $font_weight = $font_weight ?: $default_settings['font_weight'];
    $bg_color = $bg_color ?: $default_settings['bg_color'];
    $text_color = $text_color ?: $default_settings['text_color'];
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
    if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg") {
        return ["success" => false, "message" => "Sorry, only JPG, JPEG & PNG files are allowed."];
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
function get_all_staff_members($conn, $sort_by = 'last_name', $sort_order = 'ASC', $search = '', $department = '') {
    // Validate sort_by and sort_order parameters
    $allowed_sort_fields = ['first_name', 'last_name', 'department', 'job_title', 'email', 'id'];
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

    // Start building the prepared statement - JOIN with departments table
    $sql = "SELECT s.*, d.name as department, d.color as department_color FROM staff_members s "
         . "JOIN departments d ON s.department_id = d.id "
         . "WHERE 1=1";

    // Add search condition if provided
    if (!empty($search)) {
        $search_param = "%{$search}%";
        $sql .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.job_title LIKE ?)";
        $types .= 'sss';
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

    // Adjust sort field if it's department
    if ($sort_by === 'department') {
        $sort_by = 'd.name';
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
    $sql = "SELECT s.*, d.name as department, d.id as department_id "
         . "FROM staff_members s "
         . "JOIN departments d ON s.department_id = d.id "
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
 * @return string The CSS class name ('dark-text' or 'light-text')
 */
function get_text_contrast_class($hex_color) {
    // Remove # if present
    $hex = ltrim($hex_color, '#');
    
    // Convert hex to RGB
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Calculate luminance - standard formula for brightness perception
    $luminance = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    
    // Return appropriate class name based on luminance
    return ($luminance > 150) ? 'dark-text' : 'light-text';
}
?>
