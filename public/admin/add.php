<?php
// Define constant to indicate this is an admin page (required by admin_head.php)
define('INCLUDED_FROM_ADMIN_PAGE', true);

// Include admin head for initialization, security checks and database connection
require_once '../includes/admin_head.php';

// Process form submission first, before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dependencies ($conn, functions) are already included in admin_head.php

    // Sanitize and validate input
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $company_id = sanitize_input($_POST['company_id']);
    $department_id = sanitize_input($_POST['department_id']);
    $job_title = sanitize_input($_POST['job_title']);
    $email = sanitize_input($_POST['email']);

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($company_id) || empty($department_id) || empty($job_title) || empty($email)) {
        set_session_message('error_message', __('all_fields_required'));
        set_form_data($_POST);
        header("Location: add.php");
        exit;
    } else {
        // Check for duplicates using the new function
        $duplicate_check = check_staff_duplicate($conn, $first_name, $last_name, $email);

        if ($duplicate_check['duplicate']) {
            set_session_message('error_message', $duplicate_check['message']);
            set_form_data($_POST);
            header("Location: add.php");
            exit;
        } else {
            $profile_picture = '';
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
                $upload_result = upload_profile_picture($_FILES['profile_picture']);
                if ($upload_result['success']) {
                    $profile_picture = $upload_result['filename'];
                } else {
                    set_session_message('error_message', $upload_result['message']);
                    set_form_data($_POST);
                    header("Location: add.php");
                    exit;
                }
            }

            $sql = "INSERT INTO " . TABLE_STAFF_MEMBERS . " (first_name, last_name, company_id, department_id, job_title, email, profile_picture)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiisss", $first_name, $last_name, $company_id, $department_id, $job_title, $email, $profile_picture);

            if ($stmt->execute()) {
                set_session_message('success_message', __('staff_added'));
                header("Location: index.php");
                exit;
            } else {
                set_session_message('error_message', __('error_adding_staff') . ": " . $stmt->error);
                set_form_data($_POST);
                header("Location: add.php");
                exit;
            }

            $stmt->close();
        }
    }
}

// Get data needed for the form dropdowns
$departments = get_all_departments($conn);
$companies = get_all_companies($conn);

// Retrieve session error message and form data
$error_message = get_session_message('error_message');
$form_data = get_form_data();

// Include the HTML header after all processing is done
require_once '../includes/admin_header.php';

// --- The rest of the file remains the same, starting from the HTML part ---
?>

<!-- Centered form container -->
<div class="max-w-2xl mx-auto bg-white p-6 rounded shadow-md">
    <h1 class="text-2xl font-semibold mb-5 text-gray-700"><?php echo __('add_new_staff_member'); ?></h1>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php endif; ?>

    <form action="add.php" method="POST" enctype="multipart/form-data">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="form-group">
                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('first_name'); ?></label>
                <input type="text"
                       id="first_name"
                       name="first_name"
                       value="<?php echo isset($form_data['first_name']) ? htmlspecialchars($form_data['first_name']) : ''; ?>"
                       required
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>

            <div class="form-group">
                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('last_name'); ?></label>
                <input type="text"
                       id="last_name"
                       name="last_name"
                       value="<?php echo isset($form_data['last_name']) ? htmlspecialchars($form_data['last_name']) : ''; ?>"
                       required
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
        </div>

        <div class="form-group mb-4">
            <label for="company_id" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('company'); ?></label>
            <select id="company_id"
                    name="company_id"
                    required
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value=""><?php echo __('select_company'); ?></option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?php echo $company['id']; ?>"
                            <?php echo (isset($form_data['company_id']) && $form_data['company_id'] == $company['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($company['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group mb-4">
            <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('department'); ?></label>
            <select id="department_id"
                    name="department_id"
                    required
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value=""><?php echo __('select_department'); ?></option>
                <?php foreach ($departments as $dept): ?>
                    <!-- Add data-color attribute to use with JavaScript -->
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
            <label for="job_title" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('job_title'); ?></label>
            <input type="text"
                   id="job_title"
                   name="job_title"
                   value="<?php echo isset($form_data['job_title']) ? htmlspecialchars($form_data['job_title']) : ''; ?>"
                   required
                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>

        <div class="form-group mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('email'); ?></label>
            <input type="email"
                   id="email"
                   name="email"
                   value="<?php echo isset($form_data['email']) ? htmlspecialchars($form_data['email']) : ''; ?>"
                   required
                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>

        <div class="form-group mb-4">
            <label for="profile_picture" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('profile_picture'); ?></label>
            <!-- Visually hidden file input -->
            <input type="file"
                   id="profile_picture"
                   name="profile_picture"
                   accept="image/*"
                   class="sr-only">

            <!-- Styled Drop Zone -->
            <label for="profile_picture" id="dropzone" class="dropzone mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md cursor-pointer hover:border-indigo-300">
                <div class="space-y-1 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="flex text-sm text-gray-600">
                        <span class="relative bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                            <?php echo __('upload_image'); ?>
                        </span>
                        <p class="pl-1"><?php echo __('drag_drop_image'); ?></p>
                    </div>
                    <p class="text-xs text-gray-500"><?php echo __('image_formats'); ?></p>
                </div>
            </label>

            <!-- Image Preview -->
            <div class="mt-4 flex justify-center">
                <div class="relative">
                    <img id="image-preview"
                         src="<?php echo asset('images/add-picture.svg'); ?>"
                         data-default-image="<?php echo asset('images/add-picture.svg'); ?>"
                         alt="<?php echo __('preview'); ?>"
                         class="w-[200px] h-[200px] rounded-lg bg-gray-100 object-cover">
                    <button type="button"
                            id="remove-image"
                            class="absolute -top-2 -right-2 bg-gray-800 text-white rounded-full p-1.5 shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                            style="display: none;">
                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="form-actions flex justify-end gap-3 mt-6 border-t border-gray-200 pt-4">
            <a href="index.php"
               class="inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition duration-150 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-300">
                <?php echo __('cancel'); ?>
            </a>
            <button type="submit"
                    class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <?php echo __('add_staff_member'); ?>
            </button>
        </div>
    </form>
</div>

<!-- Include shared JavaScript utilities -->
<script src="../assets/js/staff-form-utils.js"></script>

<!-- Add page-specific JavaScript -->
<script>
// Add translations for JavaScript
window.translations = {
    selected: "<?php echo __('selected'); ?>",
    uploadImageFile: "<?php echo __('upload_image_file'); ?>",
    fileTooLarge: "<?php echo __('file_too_large'); ?>"
};

document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const departmentSelect = document.getElementById('department_id')
    const colorPreview = document.getElementById('department-color-preview')
    const imagePreview = document.getElementById('image-preview')
    const firstName = document.getElementById('first_name')
    const lastName = document.getElementById('last_name')
    const fileInput = document.getElementById('profile_picture')
    const dropzone = document.getElementById('dropzone')
    const removeButton = document.getElementById('remove-image')
    const form = document.querySelector('form')
    const emailInput = document.getElementById('email')

    // Default image when no user image is available
    const defaultImagePath = '../assets/images/add-picture.svg'

    // Create validation message elements
    const nameValidationMsg = document.createElement('div')
    nameValidationMsg.className = 'text-red-500 text-sm mt-1 hidden'
    lastName.parentNode.appendChild(nameValidationMsg)

    const emailValidationMsg = document.createElement('div')
    emailValidationMsg.className = 'text-red-500 text-sm mt-1 hidden'
    emailInput.parentNode.appendChild(emailValidationMsg)

    // Set initial state if a department is already selected
    // Use the repopulated form_data if available
    const initialDeptId = "<?php echo isset($form_data['department_id']) ? $form_data['department_id'] : ''; ?>"
    if (initialDeptId) {
        departmentSelect.value = initialDeptId
        // Need to manually trigger change to update color preview and potentially placeholder
        // if name fields were also repopulated
        departmentSelect.dispatchEvent(new Event('change'))
    } else if (departmentSelect.value) {
        // Fallback if no form data but a department is selected by default (unlikely but safe)
        updateDepartmentColorPreview(departmentSelect, colorPreview)
        updatePlaceholderImage()
    }

    // Set up event listeners
    departmentSelect.addEventListener('change', function() {
        updateDepartmentColorPreview(departmentSelect, colorPreview)
        updatePlaceholderImage()
    })

    // Update placeholder when name fields change
    firstName.addEventListener('input', updatePlaceholderImage)
    lastName.addEventListener('input', updatePlaceholderImage)

    // Set up file input change handler
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0]
        handleFileSelection(file, imagePreview, removeButton)
    })

    // Remove image button handler
    removeButton.addEventListener('click', function(e) {
        e.preventDefault()
        imagePreview.src = getPlaceholderImageUrl(firstName, lastName, departmentSelect, defaultImagePath, '200x200')
        imagePreview.dataset.isPlaceholder = 'true'
        fileInput.value = '' // Clear the file input
        removeButton.style.display = 'none'
        dropzone.classList.remove('border-indigo-500')
        dropzone.classList.add('border-gray-300')
    })

    // Set up drag and drop
    setupDragAndDrop(dropzone, fileInput, imagePreview, removeButton)

    // Function to update the placeholder image
    function updatePlaceholderImage() {
        if (fileInput.files && fileInput.files.length > 0) {
            return
        }

        imagePreview.src = getPlaceholderImageUrl(firstName, lastName, departmentSelect, defaultImagePath, '200x200')
        imagePreview.dataset.isPlaceholder = 'true'
        removeButton.style.display = 'none'
    }

    // Check for name duplicates when both first and last name have values
    const checkNameDuplicate = debounce(function() {
        const fName = firstName.value.trim()
        const lName = lastName.value.trim()

        if (fName && lName) {
            fetch('../includes/check_duplicate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `type=name&first_name=${encodeURIComponent(fName)}&last_name=${encodeURIComponent(lName)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.duplicate) {
                    nameValidationMsg.textContent = data.message
                    nameValidationMsg.classList.remove('hidden')
                    firstName.classList.add('border-red-500')
                    lastName.classList.add('border-red-500')
                } else {
                    nameValidationMsg.classList.add('hidden')
                    firstName.classList.remove('border-red-500')
                    lastName.classList.remove('border-red-500')
                }
            })
            .catch(error => {
                console.error('Error checking name duplicate:', error)
            })
        }
    }, 500)

    // Check for email duplicates
    const checkEmailDuplicate = debounce(function() {
        const email = emailInput.value.trim()

        if (email && email.includes('@')) {
            fetch('../includes/check_duplicate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `type=email&value=${encodeURIComponent(email)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.duplicate) {
                    emailValidationMsg.textContent = data.message
                    emailValidationMsg.classList.remove('hidden')
                    emailInput.classList.add('border-red-500')
                } else {
                    emailValidationMsg.classList.add('hidden')
                    emailInput.classList.remove('border-red-500')
                }
            })
            .catch(error => {
                console.error('Error checking email duplicate:', error)
            })
        }
    }, 500)

    // Add event listeners for duplicate checking
    firstName.addEventListener('blur', checkNameDuplicate)
    lastName.addEventListener('blur', checkNameDuplicate)
    emailInput.addEventListener('blur', checkEmailDuplicate)

    // Prevent form submission if duplicates are found
    form.addEventListener('submit', function(e) {
        if (!nameValidationMsg.classList.contains('hidden') ||
            !emailValidationMsg.classList.contains('hidden')) {
            e.preventDefault()
            alert('Please resolve duplicate entries before submitting.')
        }
    })
})
</script>

<?php require_once '../includes/admin_footer.php'; ?>