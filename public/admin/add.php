<?php
require_once '../includes/admin_header.php';

// Get all departments for dropdown
$departments = get_all_departments($conn);

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
        // Set default profile picture
        $profile_picture = 'default.jpg';

        // Handle profile picture upload if provided
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
            $upload_result = upload_profile_picture($_FILES['profile_picture']);

            if ($upload_result['success']) {
                $profile_picture = $upload_result['filename'];
            } else {
                $error_message = $upload_result['message'];
            }
        }

        // If no errors, insert new staff member
        if (!isset($error_message)) {
            $sql = "INSERT INTO staff_members (first_name, last_name, department_id, job_title, email, profile_picture)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sissss", $first_name, $last_name, $department_id, $job_title, $email, $profile_picture);

            if ($stmt->execute()) {
                // Redirect to admin dashboard with success message
                header("Location: index.php?success=1");
                exit;
            } else {
                $error_message = "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}
?>

<h1 class="page-title">Add New Staff Member</h1>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<div class="staff-form">
    <form action="add.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo isset($first_name) ? $first_name : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo isset($last_name) ? $last_name : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="department_id">Department/Service</label>
            <select id="department_id" name="department_id" required>
                <option value="">Select a Department</option>
                <?php foreach ($departments as $dept): ?>
                    <!-- Add data-color attribute to use with JavaScript -->
                    <option value="<?php echo $dept['id']; ?>" 
                           data-color="<?php echo $dept['color']; ?>" 
                           <?php echo (isset($department_id) && $department_id == $dept['id']) ? 'selected' : ''; ?>>
                        <?php echo $dept['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Department color preview -->
            <div id="department-color-preview" class="mt-2" style="display: none;"></div>
        </div>

        <!-- Add JavaScript to update department color preview on selection change -->
        <script>
            // Wait for DOM to be fully loaded
            document.addEventListener('DOMContentLoaded', function() {
                const departmentSelect = document.getElementById('department_id');
                const colorPreview = document.getElementById('department-color-preview');
                
                // Set initial state if a department is already selected
                if (departmentSelect.value) {
                    updateColorPreview();
                }
                
                // Update on change
                departmentSelect.addEventListener('change', updateColorPreview);
                
                // Function to update color preview
                function updateColorPreview() {
                    const selectedOption = departmentSelect.options[departmentSelect.selectedIndex];
                    const color = selectedOption.getAttribute('data-color');
                    const deptName = selectedOption.textContent.trim();
                    
                    if (color && deptName) {
                        // Calculate if text should be light or dark based on background color
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
                }
            });
        </script>

        <div class="form-group">
            <label for="job_title">Job Title</label>
            <input type="text" id="job_title" name="job_title" value="<?php echo isset($job_title) ? $job_title : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo isset($email) ? $email : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="profile_picture">Profile Picture</label>
            <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="dropzone-input">
            <div class="dropzone" id="profile-picture-dropzone">
                <div class="dropzone-icon">
                    <i class="lni lni-cloud-upload"></i>
                </div>
                <div class="dropzone-text">Drag & drop your image here</div>
                <div class="dropzone-subtext">or click to browse files (JPG, PNG only)</div>
                <div class="dropzone-file-info" style="display: none;"></div>
            </div>
            <div class="image-preview-container">
                <?php
                // For the add form, we need to create a dummy staff record
                // to use with get_staff_image_url function
                $dummy_staff = [
                    'profile_picture' => '',
                    'first_name' => isset($first_name) ? $first_name : '',
                    'last_name' => isset($last_name) ? $last_name : ''
                ];

                // If form was submitted but had errors, use the entered initials
                // Otherwise use 'NEW' as the placeholder text
                if (empty($dummy_staff['first_name']) && empty($dummy_staff['last_name'])) {
                    $placeholder_url = "https://placehold.co/200x200?text=NEW";
                    $img_src = $placeholder_url;
                } else {
                    $img_src = get_staff_image_url($dummy_staff, '200x200');
                }
                ?>
                <img id="image-preview" src="<?php echo $img_src; ?>" alt="Preview">
                <div class="remove-image" id="remove-image"><i class="lni lni-xmark"></i></div>
            </div>
        </div>

        <div class="form-actions">
            <a href="index.php" class="btn outline-secondary">Cancel</a>
            <button type="submit" class="btn">Add Staff Member</button>
        </div>
    </form>
</div>

<script src="../assets/js/main.js"></script>

<?php require_once '../includes/admin_footer.php'; ?>