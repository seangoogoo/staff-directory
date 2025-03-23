<?php
require_once '../includes/admin_header.php';

// Get all departments for dropdown
$departments = get_all_departments($conn);

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = sanitize_input($_GET['id']);
$staff = get_staff_member_by_id($conn, $id);

// If staff member not found, redirect to dashboard
if (!$staff) {
    header("Location: index.php");
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $department_id = sanitize_input($_POST['department_id']);
    $job_title = sanitize_input($_POST['job_title']);
    $email = sanitize_input($_POST['email']);

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($department_id) || empty($job_title) || empty($email)) {
        $error_message = "All fields are required.";
    } else {
        // Keep existing profile picture by default
        $profile_picture = $staff['profile_picture'];

        // Check if image should be deleted (cross button was clicked)
        if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
            // Delete the image file if it exists and is not the default
            if ($profile_picture && $profile_picture != 'default.jpg') {
                $old_picture_path = __DIR__ . "/../uploads/" . $profile_picture;
                if (file_exists($old_picture_path)) {
                    unlink($old_picture_path);
                }
            }
            // Set profile picture to NULL in the database
            $profile_picture = NULL;
        }
        // Handle profile picture upload if provided
        else if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
            $upload_result = upload_profile_picture($_FILES['profile_picture']);

            if ($upload_result['success']) {
                // Delete old profile picture if not default
                if ($profile_picture && $profile_picture != 'default.jpg') {
                    $old_picture_path = __DIR__ . "/../uploads/" . $profile_picture;
                    if (file_exists($old_picture_path)) {
                        unlink($old_picture_path);
                    }
                }

                $profile_picture = $upload_result['filename'];
            } else {
                $error_message = $upload_result['message'];
            }
        }

        // If no errors, update staff member
        if (!isset($error_message)) {
            $sql = "UPDATE staff_members SET
                    first_name = ?,
                    last_name = ?,
                    department_id = ?,
                    job_title = ?,
                    email = ?,
                    profile_picture = ?
                    WHERE id = ?";

            $stmt = $conn->prepare($sql);
            // Bind parameters to the prepared statement: string, string, integer, string, string, string, integer
            $stmt->bind_param("ssisssi", $first_name, $last_name, $department_id, $job_title, $email, $profile_picture, $id);

            if ($stmt->execute()) {
                // Redirect to admin dashboard with success message
                header("Location: index.php?updated=1");
                exit;
            } else {
                $error_message = "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}
?>

<h1 class="page-title">Edit Staff Member</h1>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<div class="staff-form">
    <form action="edit.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo $staff['first_name']; ?>" required>
        </div>

        <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo $staff['last_name']; ?>" required>
        </div>

        <div class="form-group">
            <label for="department_id">Department/Service</label>
            <select id="department_id" name="department_id" required>
                <option value="">Select a Department</option>
                <?php foreach ($departments as $dept): ?>
                    <!-- Add data-color attribute to use with JavaScript -->
                    <option value="<?php echo $dept['id']; ?>"
                            data-color="<?php echo $dept['color']; ?>"
                            <?php echo ($staff['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                        <?php echo $dept['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Department color preview -->
            <div id="department-color-preview" class="mt-2" style="display: <?php echo $staff['department_id'] ? 'block' : 'none'; ?>">
                <?php if ($staff['department_id']):
                    // Get the selected department's color
                    $selected_dept = null;
                    foreach ($departments as $dept) {
                        if ($dept['id'] == $staff['department_id']) {
                            $selected_dept = $dept;
                            break;
                        }
                    }

                    if ($selected_dept):
                        // Get proper text color contrast class
                        $text_class = get_text_contrast_class($selected_dept['color']);
                    ?>
                    <div class="pill <?php echo $text_class; ?>" style="background-color: <?php echo $selected_dept['color']; ?>">
                        Selected: <?php echo $selected_dept['name']; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add JavaScript to update department color preview on selection change -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const departmentSelect = document.getElementById('department_id');
                const colorPreview = document.getElementById('department-color-preview');

                departmentSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const color = selectedOption.getAttribute('data-color');
                    const deptName = selectedOption.textContent.trim();

                    if (color && deptName) {
                        // Calculate if text should be light or dark
                        // Using same logic as get_text_contrast_class() PHP function
                        const hex = color.replace('#', '');
                        const r = parseInt(hex.substr(0, 2), 16);
                        const g = parseInt(hex.substr(2, 2), 16);
                        const b = parseInt(hex.substr(4, 2), 16);
                        const luminance = ((r * 299) + (g * 587) + (b * 114)) / 1000;
                        const textClass = (luminance > 150) ? 'dark-text' : 'light-text';

                        // Update preview
                        colorPreview.innerHTML = `
                            <div class="pill ${textClass}" style="background-color: ${color}">
                                Selected: ${deptName}
                            </div>
                        `;
                        colorPreview.style.display = 'block';
                    } else {
                        colorPreview.style.display = 'none';
                    }
                });
            });
        </script>

        <div class="form-group">
            <label for="job_title">Job Title</label>
            <input type="text" id="job_title" name="job_title" value="<?php echo $staff['job_title']; ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo $staff['email']; ?>" required>
        </div>

        <div class="form-group">
            <label for="profile_picture">Profile Picture</label>
            <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="dropzone-input">
            <div class="dropzone" id="profile-picture-dropzone">
                <div class="dropzone-icon">
                    <i class="lni lni-cloud-upload"></i>
                </div>
                <div class="dropzone-text">Drag & drop your image here</div>
                <div class="dropzone-subtext">or click to browse files (JPG, PNG, WebP)</div>
                <div class="dropzone-file-info" style="display: none;"></div>
            </div>
            <div class="image-preview-container">
                <?php
                // Get department color for placeholder background
                $dept_color = '#cccccc'; // Default gray
                foreach ($departments as $dept) {
                    if ($dept['id'] == $staff['department_id']) {
                        $dept_color = $dept['color'];
                        break;
                    }
                }
                
                // Determine if we're showing a real profile picture or placeholder
                // Need to check if profile_picture field actually contains a valid uploaded image file
                $has_profile_picture = !empty($staff['profile_picture']) && file_exists(__DIR__ . '/../uploads/' . $staff['profile_picture']);
                ?>
                <img id="image-preview" 
                     src="<?php echo get_staff_image_url($staff, '200x200', null, $dept_color); ?>" 
                     alt="Current Profile Picture"
                     data-is-placeholder="<?php echo $has_profile_picture ? 'false' : 'true'; ?>"
                     data-dept-color="<?php echo $dept_color; ?>">
                <!-- Remove button (hidden for placeholders, visible for custom images) -->
                <div class="remove-image" id="remove-image" style="display: <?php echo $has_profile_picture ? 'flex' : 'none'; ?>">
                    <i class="lni lni-xmark"></i>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="index.php" class="btn outline-secondary">Cancel</a>
            <button type="submit" class="btn">Update Staff Member</button>
        </div>
    </form>
</div>

<script src="../assets/js/main.js"></script>

<script>
// Update placeholder image when department changes
document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.getElementById('department_id');
    const imagePreview = document.getElementById('image-preview');
    const removeButton = document.getElementById('remove-image');
    const profilePicture = '<?php echo $staff["profile_picture"]; ?>';
    const firstName = '<?php echo $staff["first_name"]; ?>';
    const lastName = '<?php echo $staff["last_name"]; ?>';
    
    // Store department colors in a JavaScript object
    const departmentColors = {};
    <?php foreach ($departments as $dept): ?>
    departmentColors['<?php echo $dept["id"]; ?>'] = '<?php echo $dept["color"]; ?>';
    <?php endforeach; ?>
    
    // Enforce correct placeholder status and remove button visibility on page load
    const hasRealImage = '<?php echo $has_profile_picture ? "true" : "false"; ?>' === 'true';
    console.log('Page load - Has real image:', hasRealImage);
    
    if (imagePreview) {
        // Set the correct placeholder status
        imagePreview.dataset.isPlaceholder = hasRealImage ? 'false' : 'true';
        
        // Make absolutely sure the remove button visibility is correct
        if (removeButton) {
            removeButton.style.display = hasRealImage ? 'flex' : 'none';
            console.log('Setting initial remove button visibility:', hasRealImage ? 'visible' : 'hidden');
        }
    }
    
    // Function to update the image preview
    function updateImagePreview() {
        // Only update if no profile picture is set
        if (!profilePicture) {
            const departmentId = departmentSelect.value;
            const departmentColor = departmentColors[departmentId] || '#cccccc';
            
            // Generate new image URL with updated department color
            const timestamp = new Date().getTime(); // Add timestamp to prevent caching
            imagePreview.src = `../includes/generate_placeholder.php?name=${firstName}+${lastName}&size=200x200&bg_color=${encodeURIComponent(departmentColor)}&t=${timestamp}`;
            
            // When changing department colors, ensure this is still treated as a placeholder
            // with no remove button
            imagePreview.dataset.isPlaceholder = 'true';
            if (removeButton) {
                removeButton.style.display = 'none';
            }
        }
    }
    
    // Listen for changes to the department select
    departmentSelect.addEventListener('change', updateImagePreview);
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>
