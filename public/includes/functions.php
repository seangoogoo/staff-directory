<?php
// Common functions for the application

/**
 * Get the appropriate image URL for a staff member
 *
 * @param array $staff The staff member data
 * @param string $size The size of the placeholder image (default: '600x400')
 * @return string The image URL
 */
function get_staff_image_url($staff, $size = '600x600') {
    // Generate initials for the placeholder image
    $initials = '';
    if (!empty($staff['first_name'])) $initials .= strtoupper(substr($staff['first_name'], 0, 1));
    if (!empty($staff['last_name'])) $initials .= strtoupper(substr($staff['last_name'], 0, 1));

    // Generate placeholder URL
    $placeholder_url = "https://placehold.co/{$size}?text=" . urlencode($initials) . "&font=poppins";

    // If no profile picture is set, return the placeholder
    if (empty($staff['profile_picture'])) {
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
    $sql = "SELECT s.*, d.name as department FROM staff_members s "
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
 */
function get_all_departments($conn) {
    $sql = "SELECT id, name, description FROM departments ORDER BY name";
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
?>
