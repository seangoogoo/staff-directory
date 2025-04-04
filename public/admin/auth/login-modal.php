<?php
/**
 * Login Modal Component
 * This single file handles the login modal display for the entire application
 * Uses centralized configuration from auth_config.php
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load auth configuration (define AUTH_SYSTEM to prevent direct access)
if (!defined('AUTH_SYSTEM')) {
    define('AUTH_SYSTEM', true);
}
require_once __DIR__ . '/auth_config.php';

// Check if login is required via URL parameter or session flag
$login_param = isset($_GET['login']) ? $_GET['login'] : '';
$session_flag = isset($_SESSION[LOGIN_MODAL_FLAG]) ? $_SESSION[LOGIN_MODAL_FLAG] : false;
$show_login_modal = ($login_param === 'required') || ($session_flag === true);

// Get any return URL from query parameter
$return_url = isset($_GET['return']) ? htmlspecialchars($_GET['return']) : DEFAULT_RETURN_URL;

// Clear the session flag after checking
if (isset($_SESSION[LOGIN_MODAL_FLAG])) {
    $_SESSION[LOGIN_MODAL_FLAG] = false;
}
?>

<!-- Consolidated Login Modal -->
<div id="loginModal" class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 transition-opacity duration-300 <?php echo $show_login_modal ? 'opacity-100' : 'opacity-0 pointer-events-none hidden'; ?>">
    <div class="modal-container min-h-screen px-4 text-center flex items-center justify-center">
        <!-- Modal panel -->
        <div class="modal-content w-full max-w-md bg-white rounded-2xl shadow-xl transform transition-all">
            <div class="modal-header px-6 py-2 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800">Staff Directory Admin Login</h2>
                    <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" id="closeLoginModal">
                        <span class="text-2xl">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body p-6">
                <div id="loginAlert" class="mb-4 px-4 py-3 rounded-lg text-red-800 bg-red-50 border border-red-200" style="display: none;"></div>
                <form id="loginForm" class="space-y-4">
                    <div class="form-group">
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="ri-user-line mr-1"></i> Username
                        </label>
                        <input type="text"
                               id="username"
                               name="username"
                               placeholder="Enter username"
                               required
                               autocomplete="username"
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
                    </div>
                    <div class="form-group">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="ri-lock-line mr-1"></i> Password
                        </label>
                        <input type="password"
                               id="password"
                               name="password"
                               placeholder="Enter password"
                               required
                               autocomplete="current-password"
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
                    </div>
                    <!-- Hidden field to pass return URL -->
                    <input type="hidden" id="returnUrl" name="returnUrl" value="<?php echo $return_url; ?>">
                    <div class="form-actions mt-6">
                        <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                            <i class="ri-login-circle-line mr-1"></i> Login
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Consolidated Login JS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginModal = document.getElementById('loginModal')
    const loginForm = document.getElementById('loginForm')
    const loginAlert = document.getElementById('loginAlert')
    const closeLoginModal = document.getElementById('closeLoginModal')
    const passwordField = document.getElementById('password')

    // Function to open the modal
    function openLoginModal() {
        loginModal.classList.remove('opacity-0', 'pointer-events-none', 'hidden')
        loginModal.classList.add('opacity-100')
        document.body.style.overflow = 'hidden'

        // Add a slight delay to enable the password field when modal is visible
        setTimeout(() => {
            passwordField.disabled = false
        }, 10)
    }

    // Function to close the modal
    function closeModal() {
        loginModal.classList.remove('opacity-100')
        loginModal.classList.add('opacity-0', 'pointer-events-none', 'hidden')
        document.body.style.overflow = ''

        // Disable the password field when modal is hidden to prevent password manager icons
        passwordField.disabled = true
    }

    // Initially disable password field if modal is hidden
    if (!<?php echo $show_login_modal ? 'true' : 'false'; ?>) {
        passwordField.disabled = true
    }

    // Handle admin area link clicks
    const adminLink = document.getElementById('adminLink')
    if (adminLink) {
        adminLink.addEventListener('click', function(e) {
            e.preventDefault()

            // First check if the user is already logged in
            fetch('/admin/auth/check_login.php', {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.logged_in) {
                    // User is already logged in, redirect to admin area
                    window.location.href = '/admin/index.php'
                } else {
                    // User is not logged in, show the login modal
                    openLoginModal()
                }
            })
            .catch(() => {
                // Default to showing login modal if there's an error
                openLoginModal()
            })
        })
    }

    // Close modal when clicking the close button
    if (closeLoginModal) {
        closeLoginModal.addEventListener('click', closeModal)
    }

    // Close modal when clicking background (outside modal content)
    loginModal.addEventListener('click', function(e) {
        if (e.target === loginModal) {
            closeModal()
        }
    })

    // Handle login form submission
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault()

            const username = document.getElementById('username').value
            const password = document.getElementById('password').value
            const returnUrl = document.getElementById('returnUrl').value

            // Hide any previous alerts
            loginAlert.style.display = 'none'

            // Create form data for the request
            const formData = new FormData()
            formData.append('username', username)
            formData.append('password', password)
            formData.append('returnUrl', returnUrl)

            // Send login request
            fetch('/admin/auth/login.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                cache: 'no-store'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect directly to admin area or return URL
                    window.location.href = returnUrl || '/admin/index.php'
                } else {
                    // Show error message
                    loginAlert.textContent = data.message || 'Login failed. Please try again.'
                    loginAlert.style.display = 'block'
                }
            })
            .catch(() => {
                loginAlert.textContent = 'An error occurred. Please try again.'
                loginAlert.style.display = 'block'
            })
        })
    }

    // Auto-open modal if needed (based on PHP flag)
    if (<?php echo $show_login_modal ? 'true' : 'false'; ?>) {
        document.body.style.overflow = 'hidden'
        // Enable password field when modal is visible
        passwordField.disabled = false
    } else {
        // Make sure the modal is properly hidden with CSS classes
        loginModal.classList.add('hidden')
    }
})
</script>
