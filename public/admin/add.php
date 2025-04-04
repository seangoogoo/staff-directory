<?php
// Include essential files ONCE at the top.
// admin_header.php also includes them, but require_once prevents double inclusion.
require_once __DIR__ . '/../config/database.php'; // Establishes $conn
require_once __DIR__ . '/../includes/functions.php'; // Provides necessary functions

// Start session if not already started (needed for storing error messages/form data)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Process form submission first, before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dependencies ($conn, functions) are already included above

    // Sanitize and validate input
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $company_id = sanitize_input($_POST['company_id']);
    $department_id = sanitize_input($_POST['department_id']);
    $job_title = sanitize_input($_POST['job_title']);
    $email = sanitize_input($_POST['email']);

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($company_id) || empty($department_id) || empty($job_title) || empty($email)) {
        // Store error message in session to display after redirect or on page load
        $_SESSION['error_message'] = "All fields are required.";
        $_SESSION['form_data'] = $_POST;
    } else {
        $profile_picture = '';
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
            $upload_result = upload_profile_picture($_FILES['profile_picture']);
            if ($upload_result['success']) {
                $profile_picture = $upload_result['filename'];
            } else {
                // Store error message in session
                $_SESSION['error_message'] = $upload_result['message'];
                $_SESSION['form_data'] = $_POST;
            }
        }

        if (!isset($_SESSION['error_message'])) {
            $sql = "INSERT INTO staff_members (first_name, last_name, company_id, department_id, job_title, email, profile_picture)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiisss", $first_name, $last_name, $company_id, $department_id, $job_title, $email, $profile_picture);

            if ($stmt->execute()) {
                header("Location: index.php?success=1");
                exit;
            } else {
                // Store database error message in session
                $_SESSION['error_message'] = "Error adding staff member: " . $stmt->error;
                $_SESSION['form_data'] = $_POST;
            }

            $stmt->close();
        }
    }
}

// Now include the header (it will skip re-including db and functions due to require_once)
require_once '../includes/admin_header.php';

// Retrieve session error message and form data if they exist (already started session)
$error_message = null;
$form_data = [];
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Clear the message after retrieving
}
if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']); // Clear the data after retrieving
}

// Get data needed for the form dropdowns (moved after header include)
$departments = get_all_departments($conn);
$companies = get_all_companies($conn);

// --- The rest of the file remains the same, starting from the HTML part ---
?>

<!-- Centered form container -->
<div class="max-w-2xl mx-auto bg-white p-6 rounded shadow-md">
    <h1 class="text-2xl font-semibold mb-5 text-gray-700">Add New Staff Member</h1>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php endif; ?>

    <form action="add.php" method="POST" enctype="multipart/form-data">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="form-group">
                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                <input type="text"
                       id="first_name"
                       name="first_name"
                       value="<?php echo isset($form_data['first_name']) ? htmlspecialchars($form_data['first_name']) : ''; ?>"
                       required
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>

            <div class="form-group">
                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                <input type="text"
                       id="last_name"
                       name="last_name"
                       value="<?php echo isset($form_data['last_name']) ? htmlspecialchars($form_data['last_name']) : ''; ?>"
                       required
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
        </div>

        <div class="form-group mb-4">
            <label for="company_id" class="block text-sm font-medium text-gray-700 mb-1">Company</label>
            <select id="company_id"
                    name="company_id"
                    required
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
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
            <select id="department_id"
                    name="department_id"
                    required
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="">Select a Department</option>
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
            <label for="job_title" class="block text-sm font-medium text-gray-700 mb-1">Job Title</label>
            <input type="text"
                   id="job_title"
                   name="job_title"
                   value="<?php echo isset($form_data['job_title']) ? htmlspecialchars($form_data['job_title']) : ''; ?>"
                   required
                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>

        <div class="form-group mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email"
                   id="email"
                   name="email"
                   value="<?php echo isset($form_data['email']) ? htmlspecialchars($form_data['email']) : ''; ?>"
                   required
                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>

        <div class="form-group mb-4">
            <label for="profile_picture" class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
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
                            Upload a file
                        </span>
                        <p class="pl-1">or drag and drop</p>
                    </div>
                    <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                </div>
            </label>

            <!-- Image Preview -->
            <div class="mt-4 flex justify-center">
                <div class="relative">
                    <img id="image-preview"
                         src="../assets/images/add-picture.svg"
                         alt="Preview"
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
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Add Staff Member
            </button>
        </div>
    </form>
</div>

<!-- Add JavaScript to update department color preview and placeholder image -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.getElementById('department_id')
    const colorPreview = document.getElementById('department-color-preview')
    const imagePreview = document.getElementById('image-preview')
    const firstName = document.getElementById('first_name')
    const lastName = document.getElementById('last_name')
    const fileInput = document.getElementById('profile_picture')
    const dropzone = document.getElementById('dropzone')
    const removeButton = document.getElementById('remove-image')

    // Set initial state if a department is already selected
    // Use the repopulated form_data if available
    const initialDeptId = "<?php echo isset($form_data['department_id']) ? $form_data['department_id'] : ''; ?>";
    if (initialDeptId) {
        departmentSelect.value = initialDeptId;
        // Need to manually trigger change to update color preview and potentially placeholder
        // if name fields were also repopulated
        departmentSelect.dispatchEvent(new Event('change'));
    } else if (departmentSelect.value) {
        // Fallback if no form data but a department is selected by default (unlikely but safe)
        updateColorPreview()
        updatePlaceholderImage() // This might show initials if name fields have values
    }

    // Update on change
    departmentSelect.addEventListener('change', function() {
        updateColorPreview()
        updatePlaceholderImage()
    })

    // Update placeholder when name fields change
    firstName.addEventListener('input', updatePlaceholderImage)
    lastName.addEventListener('input', updatePlaceholderImage)

    // Handle file input change
    fileInput.addEventListener('change', handleFileSelect)

    // Remove image button handler
    removeButton.addEventListener('click', function(e) {
        e.preventDefault()
        imagePreview.src = getPlaceholderUrl()
        imagePreview.dataset.isPlaceholder = 'true'
        fileInput.value = '' // Clear the file input
        removeButton.style.display = 'none'
        dropzone.classList.remove('border-indigo-500')
        dropzone.classList.add('border-gray-300')
    })

    // Drag and drop handlers
    ;['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false)
        document.body.addEventListener(eventName, preventDefaults, false)
    })

    function preventDefaults(e) {
        e.preventDefault()
        e.stopPropagation()
    }

    dropzone.addEventListener('dragenter', function(e) {
        preventDefaults(e)
        dropzone.classList.remove('border-gray-300')
        dropzone.classList.add('border-indigo-500')
    })

    dropzone.addEventListener('dragleave', function(e) {
        preventDefaults(e)
        if (!dropzone.contains(e.relatedTarget)) {
            dropzone.classList.remove('border-indigo-500')
            dropzone.classList.add('border-gray-300')
        }
    })

    dropzone.addEventListener('drop', function(e) {
        preventDefaults(e)
        dropzone.classList.remove('border-indigo-500')
        dropzone.classList.add('border-gray-300')
        const dt = e.dataTransfer
        const files = dt.files
        handleFiles(files)
    })

    function handleFileSelect(e) {
        const files = e.target.files
        handleFiles(files)
    }

    function handleFiles(files) {
        if (files && files[0]) {
            const file = files[0]

            // Check file type
            if (!file.type.match('image.*')) {
                alert('Please upload an image file')
                return
            }

            // Check file size (10MB max)
            if (file.size > 10 * 1024 * 1024) {
                alert('File is too large. Maximum size is 10MB')
                return
            }

            const reader = new FileReader()
            reader.onload = function(e) {
                imagePreview.src = e.target.result
                imagePreview.dataset.isPlaceholder = 'false'
                removeButton.style.display = 'block'
            }
            reader.readAsDataURL(file)

            // Update the file input
            const dataTransfer = new DataTransfer()
            dataTransfer.items.add(file)
            fileInput.files = dataTransfer.files
        }
    }

    // Function to update color preview
    function updateColorPreview() {
        const selectedOption = departmentSelect.options[departmentSelect.selectedIndex]
        const color = selectedOption.getAttribute('data-color')
        const deptName = selectedOption.textContent.trim()

        if (color && deptName) {
            const hex = color.replace('#', '')
            const r = parseInt(hex.substr(0, 2), 16)
            const g = parseInt(hex.substr(2, 2), 16)
            const b = parseInt(hex.substr(4, 2), 16)
            const luminance = ((r * 299) + (g * 587) + (b * 114)) / 1000
            const textColor = (luminance > 150) ? 'text-gray-800' : 'text-white'

            colorPreview.innerHTML = `
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium ${textColor}" style="background-color: ${color}">
                    Selected: ${deptName}
                </span>
            `
            colorPreview.style.display = 'block'
        } else {
            colorPreview.style.display = 'none'
        }
    }

    function getPlaceholderUrl() {
        const selectedOption = departmentSelect.options[departmentSelect.selectedIndex]
        const color = selectedOption?.getAttribute('data-color') || '#cccccc'
        const fName = firstName?.value || ''
        const lName = lastName?.value || ''

        // Only generate a placeholder with initials if we have a name
        if (!fName && !lName) {
            return '../assets/images/add-picture.svg'
        }

        const nameParam = `${fName.trim()} ${lName.trim()}`
        const timestamp = new Date().getTime()
        const url = `../includes/generate_placeholder.php?name=${encodeURIComponent(nameParam)}&size=200x200&bg_color=${encodeURIComponent(color)}&t=${timestamp}`
        return url
    }

    // Function to update the placeholder image
    function updatePlaceholderImage() {
        if (fileInput.files && fileInput.files.length > 0) {
            return
        }

        imagePreview.src = getPlaceholderUrl()
        imagePreview.dataset.isPlaceholder = 'true'
        removeButton.style.display = 'none'
    }
})
</script>

<?php require_once '../includes/admin_footer.php'; ?>