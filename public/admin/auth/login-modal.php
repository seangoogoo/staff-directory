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
<div id="loginModal" class="modal<?php echo $show_login_modal ? ' show' : ''; ?>">
    <div class="modal-container">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Staff Directory Admin Login</h2>
                <button type="button" class="close-btn" id="closeLoginModal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="loginAlert" class="alert alert-danger" style="display: none;"></div>
                <form id="loginForm">
                    <div class="form-group">
                        <label for="username"><i class="lni lni-user"></i> Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter username" required autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="password"><i class="lni lni-lock"></i> Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter password" required autocomplete="current-password">
                    </div>
                    <!-- Hidden field to pass return URL -->
                    <input type="hidden" id="returnUrl" name="returnUrl" value="<?php echo $return_url; ?>">
                    <div class="form-actions">
                        <button type="submit" class="btn submit-btn"><i class="lni lni-enter"></i> Login</button>
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
    
    // Function to open the modal
    function openLoginModal() {
        loginModal.classList.add('show')
        document.body.classList.add('modal-open')
    }
    
    // Function to close the modal
    function closeModal() {
        loginModal.classList.remove('show')
        document.body.classList.remove('modal-open')
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
    if (loginModal.classList.contains('show')) {
        document.body.classList.add('modal-open')
    }
})
</script>
