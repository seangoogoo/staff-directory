<?php
require_once '../includes/admin_header.php';

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
    $department = sanitize_input($_POST['department']);
    $job_title = sanitize_input($_POST['job_title']);
    $email = sanitize_input($_POST['email']);

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($department) || empty($job_title) || empty($email)) {
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
                    department = ?,
                    job_title = ?,
                    email = ?,
                    profile_picture = ?
                    WHERE id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $first_name, $last_name, $department, $job_title, $email, $profile_picture, $id);

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
            <label for="department">Department/Service</label>
            <input type="text" id="department" name="department" value="<?php echo $staff['department']; ?>" required>
        </div>

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
                <div class="dropzone-subtext">or click to browse files (JPG, PNG only)</div>
                <div class="dropzone-file-info" style="display: none;"></div>
            </div>
            <div class="image-preview-container">
                <img id="image-preview" src="<?php echo get_staff_image_url($staff, '200x200'); ?>" alt="Current Profile Picture">
                <div class="remove-image" id="remove-image"><i class="lni lni-xmark"></i></div>
            </div>
        </div>

        <div class="form-actions">
            <a href="index.php" class="btn outline-secondary">Cancel</a>
            <button type="submit" class="btn">Update Staff Member</button>
        </div>
    </form>
</div>

<script src="../assets/js/main.js"></script>

<?php require_once '../includes/admin_footer.php'; ?>
