<?php
ob_start(); // Start output buffering

// Define constant to indicate this is an admin page (required by admin_head.php)
define('INCLUDED_FROM_ADMIN_PAGE', true);

// Include admin head for initialization, security checks and database connection
require_once '../includes/admin_head.php';

// Get all departments & companies for dropdowns (needed for form display AND processing potentially)
$departments = get_all_departments($conn);
$companies = get_all_companies($conn);

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = sanitize_input($_GET['id']);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // $conn and functions are available from admin_head.php

    // Sanitize and validate input
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $company_id = sanitize_input($_POST['company_id']);
    $department_id = sanitize_input($_POST['department_id']);
    $job_title = sanitize_input($_POST['job_title']);
    $email = sanitize_input($_POST['email']);

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($company_id) || empty($department_id) || empty($job_title) || empty($email)) {
        set_session_message('error_message', "All fields are required.");
        set_form_data($_POST);
        header("Location: edit.php?id=" . $id);
        exit;
    } else {
        // Fetch current staff data to get existing picture filename
        $current_staff = get_staff_member_by_id($conn, $id);
        if (!$current_staff) {
            set_session_message('error_message', "Staff member not found.");
            header("Location: index.php");
            exit;
        }
        $profile_picture = $current_staff['profile_picture']; // Keep existing by default

        // First check if new profile picture is uploaded - this takes precedence over delete flag
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
            // Handle new picture upload
            $upload_result = upload_profile_picture($_FILES['profile_picture']);

            if ($upload_result['success']) {
                // Delete old picture if it exists before assigning new one
                if ($profile_picture) {
                     // --- Revert to inline unlink logic from backup ---
                    $old_picture_path = PUBLIC_PATH . "/uploads/" . basename($profile_picture); // Use basename for security
                    if (file_exists($old_picture_path)) {
                        @unlink($old_picture_path);
                    }
                    // --- End revert ---
                }
                $profile_picture = $upload_result['filename']; // Assign new filename
            } else {
                set_session_message('error_message', $upload_result['message']);
                set_form_data($_POST);
                header("Location: edit.php?id=" . $id);
                exit;
            }
        }
        // Then check if image should be deleted (only if no new upload)
        else if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
            if ($profile_picture) { // Only delete if there is a picture
                // --- Revert to inline unlink logic from backup ---
                $old_picture_path = PUBLIC_PATH . "/uploads/" . basename($profile_picture); // Use basename for security
                if (file_exists($old_picture_path)) {
                    @unlink($old_picture_path); // Use @ to suppress errors if unlink fails, though logging might be better
                }
                // --- End revert ---
            }
            $profile_picture = NULL; // Set to NULL in DB
        }
        // If no new file and delete not checked, $profile_picture remains the current one

        // Update staff member in DB
        $sql = "UPDATE staff_members SET
                first_name = ?, last_name = ?, company_id = ?, department_id = ?,
                job_title = ?, email = ?, profile_picture = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            set_session_message('error_message', "Database error (prepare): " . $conn->error);
            set_form_data($_POST);
            header("Location: edit.php?id=" . $id);
            exit;
        }

        $stmt->bind_param("ssiisssi", $first_name, $last_name, $company_id, $department_id, $job_title, $email, $profile_picture, $id);

        if ($stmt->execute()) {
            // Clear form data on success before redirect
            if (isset($_SESSION['form_data'])) {
                 unset($_SESSION['form_data']);
            }
            set_session_message('success_message', "Staff member updated successfully.");
            header("Location: index.php?updated=1"); // Keep param for now, but session msg is better
            exit;
        } else {
            set_session_message('error_message', "Error updating staff member: " . $stmt->error);
            set_form_data($_POST); // Keep form data on DB error
            header("Location: edit.php?id=" . $id);
            exit;
        }
        $stmt->close();
    }
}

// --- Fetch data for displaying the form ---

// Fetch the staff member data for display
$staff = get_staff_member_by_id($conn, $id);

// If staff member not found now, redirect (could happen if deleted)
if (!$staff) {
    set_session_message('error_message', "Staff member not found.");
    header("Location: index.php");
    exit;
}

// Retrieve session error/success messages and form data
$error_message = get_session_message('error_message'); // Use helper function
$success_message = get_session_message('success_message'); // Check for success too
$form_data_from_session = get_form_data(); // Use new function

// Use form data from session if available (due to error), otherwise use fetched staff data
$form_data = !empty($form_data_from_session) ? $form_data_from_session : $staff;

// Determine if the originally loaded staff member had a real picture
$has_real_picture = !empty($staff['profile_picture']);

// Include the HTML header after all processing is done
require_once '../includes/admin_header.php';
?>

<!-- Centered form container -->
<div class="max-w-2xl mx-auto bg-white p-6 rounded shadow-md">

    <h1 class="text-2xl font-semibold mb-5 text-gray-700">Edit Staff Member: <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></h1>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php endif; ?>
    <?php if ($success_message): // Display success message if redirected back (though usually redirect to index) ?>
        <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    <?php endif; ?>

    <form action="edit.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="form-group">
                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($form_data['first_name']); ?>" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>

            <div class="form-group">
                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($form_data['last_name']); ?>" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
        </div>

        <div class="form-group mb-4">
            <label for="company_id" class="block text-sm font-medium text-gray-700 mb-1">Company</label>
            <select id="company_id" name="company_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="">Select a Company</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?php echo $company['id']; ?>"
                            <?php echo (isset($form_data['company_id']) && $form_data['company_id'] == $company['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($company['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group mb-4">
            <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">Department/Service</label>
            <select id="department_id" name="department_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="">Select a Department</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>"
                            data-color="<?php echo htmlspecialchars($dept['color']); ?>"
                            <?php echo (isset($form_data['department_id']) && $form_data['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <!-- Department color preview -->
            <div id="department-color-preview" class="mt-2" style="display: none;"></div>
        </div>

         <div class="form-group mb-4">
            <label for="job_title" class="block text-sm font-medium text-gray-700 mb-1">Job Title</label>
            <input type="text" id="job_title" name="job_title" value="<?php echo htmlspecialchars($form_data['job_title']); ?>" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>

        <div class="form-group mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email']); ?>" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>

        <div class="form-group mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>

            <!-- Visually hidden file input -->
            <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="sr-only dropzone-input">

            <!-- Hidden input to signal deletion -->
            <input type="hidden" name="delete_image" id="delete_image_flag" value="0">

            <!-- Styled Drop Zone -->
            <label for="profile_picture" id="dropzone" class="dropzone mb-4 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md cursor-pointer hover:border-indigo-300">
                <div class="space-y-1 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="flex text-sm text-gray-600">
                        <span class="relative bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                            Upload a new file
                        </span>
                        <p class="pl-1">or drag and drop to replace</p>
                    </div>
                    <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                </div>
            </label>

             <!-- Display current picture & delete option -->
             <?php
             // Get current image URL using the potentially modified form_data for correct color
             $dept_color_for_placeholder = '#cccccc'; // Default
             if (isset($form_data['department_id'])) {
                 foreach ($departments as $dept) {
                     if ($dept['id'] == $form_data['department_id']) {
                         $dept_color_for_placeholder = $dept['color'];
                         break;
                     }
                 }
             }
             $current_image_url = get_staff_image_url($form_data, '150x150', null, $dept_color_for_placeholder);
             ?>
             <div class="flex justify-center">
                 <div class="relative">
                     <img id="image-preview"
                          src="<?php echo $current_image_url; ?>"
                          data-default-image="<?php echo asset('images/add-picture.svg'); ?>"
                          alt="Current Picture"
                          class="w-[150px] h-[150px] rounded-lg bg-gray-100 object-cover">

                     <button type="button"
                             id="remove-image"
                             class="absolute -top-2 -right-2 bg-gray-800 text-white rounded-full p-1.5 shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                             data-update-field="delete_image"
                             data-update-value="1"
                             style="display: <?php echo $has_real_picture ? 'flex' : 'none'; ?>"
                             title="Remove current picture">
                         <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                         </svg>
                     </button>
                 </div>
             </div>

        </div> <!-- End of form-group mb-4 for Profile Picture -->

        <div class="form-actions flex justify-end gap-3 mt-6 border-t border-gray-200 pt-4">
            <a href="index.php"
               class="inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-300">Cancel</a>
            <button type="submit"
                    class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Update Staff Member</button>
        </div>
    </form>

</div>

<!-- Include shared JavaScript utilities -->
<script src="../assets/js/staff-form-utils.js"></script>

<!-- JavaScript for department color preview -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const departmentSelect = document.getElementById('department_id')
    const colorPreview = document.getElementById('department-color-preview')

    // Set initial department color preview if a department is selected
    const initialDeptId = "<?php echo isset($form_data['department_id']) ? $form_data['department_id'] : ''; ?>"
    if (initialDeptId) {
        // Ensure the dropdown reflects the potentially repopulated value
        departmentSelect.value = initialDeptId
        // Update the color preview based on this value
        updateDepartmentColorPreview(departmentSelect, colorPreview)
    }

    // Update color preview when department changes
    departmentSelect.addEventListener('change', function() {
        updateDepartmentColorPreview(departmentSelect, colorPreview)
    })

    // Note: All image preview, dropzone, and remove button functionality is now handled by main.js
    // The remove button has been updated to use the standard ID and data attributes
})
</script>

<?php
require_once '../includes/admin_footer.php';
ob_end_flush(); // Send the buffered output to the browser
?>
