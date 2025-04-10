# PHP Functions Audit (April 5, 2025)

This document lists all PHP functions in the project.
The purpose of this list is:
- to track for unused functions and determine if they need to be removed or kept for future usage,
- cleanup and reorganize the code.


## How to use this list

- [ ] Check the box when you've audited a function
- Mark functions for:
  - 🟢 Keep (function is used and necessary)
  - 🟡 Evaluate (function may be useful in the future)
  - 🔴 Remove (function is unused or redundant)
  - 🔷 Improve (function works but needs refactoring or enhancement)

## Functions List

### public/includes/functions.php

- 🔴 `get_placeholder_settings_from_db()` - Legacy function for backward compatibility
- 🟢 `color_mix($hex_color, $variant, $percentage)` - Mix a color with black or white - used in get_staff_image_url()
- 🟢 `get_staff_image_url($staff, $size, $font_weight, $bg_color, $text_color, $font_size_factor)` - Get image URL for staff member
- 🟢 `hex2rgb($hex_color)` - Convert hex color code to RGB array (defined within get_staff_image_url)
- 🟢 `sanitize_input($data)` - Sanitize user input data
- 🔷 `upload_profile_picture($file)` - Handle profile picture upload - merge it with upload_company_logo() to create a shared functionality
- 🟢 `get_all_staff_members($conn, $sort_by, $sort_order, $search, $department, $company)` - Get all staff members
- 🟢 `get_staff_member_by_id($conn, $id)` - Get staff member by ID
- 🟢 `delete_staff_member($conn, $id)` - Delete staff member by ID
- 🟢 `get_all_companies($conn)` - Get all companies
- 🟢 `get_all_company_names($conn)` - Get all company names - used by get_companies_by_department() as fallback
- 🟢 `get_department_by_id($conn, $id)` - Get department by ID
- 🟢 `get_all_departments($conn)` - Get all departments
- 🟢 `get_all_department_names($conn)` - Get all department names
- 🟢 `get_departments_by_company($conn, $company_name)` - Get departments by company
- 🟢 `get_companies_by_department($conn, $department_name)` - Get companies by department
- 🟡 `get_department_by_name($conn, $name)` - Get department by name - currently unused but could be useful for validation or future features
- 🟢 `get_text_contrast_class($hex_color, $return_class)` - Get text contrast class based on background color
- 🟢 `set_session_message($key, $message)` - Set session message - exists but bypassed by handle_redirect(); could be refactored for consistency
- 🟢 `get_session_message($key)` - Get session message - used in admin/edit.php and admin/settings.php
- 🔴 `handle_redirect($success_key, $success_message, $error_key, $error_message, $redirect_url)` - Handle redirect with messages - function exists but is never called; all pages use direct $_SESSION assignments and header() calls instead
- 🟢 `get_default_app_settings()` - Get default application settings
- [ ] `clear_placeholder_images()` - Clear placeholder images
- [ ] `update_settings_in_db($settings)` - Update settings in database
- 🟢 `load_app_settings()` - Load application settings
- 🟢 `debug_log($data, $label, $print_r)` - Log debug information
- 🟢 `get_company_by_id($conn, $id)` - Get company by ID
- 🟢 `upload_company_logo($file)` - Handle company logo upload
- 🟢 `get_company_staff_count($conn, $company_id)` - Get staff count for a company
- 🟢 `get_all_company_statistics($conn)` - Get statistics for all companies
- 🟢 `get_active_department_names($conn)` - Get names of departments with staff
- 🟢 `get_active_company_names($conn)` - Get names of companies with staff
- 🟢 `check_staff_duplicate($conn, $first_name, $last_name, $email)` - Check for duplicate staff members

### config/env_loader.php

- 🟢 `load_env($path)` - Load environment variables from file

### public/admin/auth/auth.php

- 🟢 `is_logged_in()` - Check if user is logged in
- 🟢 `verify_login($username, $password)` - Verify login credentials
- 🟢 `login_user($username)` - Log in a user
- 🟢 `logout_user()` - Log out current user
- 🟢 `require_login($ajax)` - Require user to be logged in
- 🔴 `check_admin_login()` - Check admin login status

### public/admin/settings.php

- 🟢 `process_form_submission($form_type, $post_data, $files_data)` - Process settings form submission
- 🟢 `process_font_settings($post_data)` - Process font settings
- 🟢 `process_logo_visibility($post_data)` - Process logo visibility settings
- 🟢 `delete_logo_file($logo_path)` - Delete logo file
- 🟢 `process_logo_removal()` - Process logo removal
- 🟢 `process_logo_upload($files)` - Process logo upload
- 🟢 `handle_logo_upload_error($files)` - Handle logo upload errors
- 🟢 `process_title_settings($post_data)` - Process title settings
- 🟢 `reset_title_settings()` - Reset title settings to defaults

### public/includes/ajax_handlers.php

- [ ] Functions in this file need to be checked

### public/includes/check_duplicate.php

- [ ] Functions in this file need to be checked

### public/includes/generate_placeholder.php

- [ ] Functions in this file need to be checked

## JavaScript Functions

These JavaScript functions are included for reference but are not part of the PHP audit:

### public/admin/companies.php
- `preventDefaults(e)`
- `highlight()`
- `unhighlight()`
- `handleDrop(e)`

### public/includes/admin_header.php
- `checkScreenSize()`

### public/admin/edit.php
- `getPlaceholderUrl()`
- `updateImagePreviewDisplay(imageUrl)`
- `updateColorPreviewDisplay()`
- `handleFileSelection(file)`
- `updatePlaceholderOnNameChange()`
- `preventDefaults(e)`

### public/admin/auth/login-modal.php
- `openLoginModal()`
- `closeModal()`

### public/admin/add.php
- `preventDefaults(e)`
- `handleFileSelect(e)`
- `handleFiles(files)`
- `updateColorPreview()`
- `getPlaceholderUrl()`
- `updatePlaceholderImage()`
- `debounce(func, wait)`

### public/admin/settings.php
- `generatePlaceholderUrl(fontWeight, fontSizeFactor)`
- `updatePreview()`
- `handleFileSelection()`
- `formatFileSize(bytes)`
- `handleLogoFormSubmit(form)`
- `showMessage(type, message)`