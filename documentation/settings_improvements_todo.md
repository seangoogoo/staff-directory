# Settings Page Improvements TODO

## Settings.php Code Improvements

### 1. Code Structure and Organization
- [ ] Refactor settings.php to improve readability and maintainability
  - [+] Break down large code blocks into smaller, focused functions
  - [+] Extract repeated code patterns into reusable functions
  - [+] Move utility functions to functions.php where appropriate
- [ ] Centralize settings management
  - [ ] Create unified getter/setter functions: `update_app_setting($key, $value, $conn)` and `get_app_setting($key, $default, $conn)`
  - [ ] Implement these in functions.php for application-wide use
  - [ ] Replace all direct settings access with function calls
- [ ] Consolidate duplicate settings retrieval
  - [ ] Remove redundant settings loading in various sections of settings.php
  - [ ] Fix the repeated settings retrieval logic in header.php and admin_header.php
- [ ] Standardize session messages
  - [ ] Use a single success/error message key in the session
  - [ ] Add message "type" parameter to differentiate contexts (logo, title, etc.)
- [ ] Define constants for default values
  - [ ] Create constants for default logo path, titles, etc.
  - [ ] Use these constants throughout the application

### 2. Error Handling Enhancements
- [ ] Continue improving error handling throughout the settings code
  - [ ] Standardize error message format and presentation
  - [ ] Add more specific error checking for file operations
  - [ ] Implement proper transaction handling for database operations

### 3. Security Improvements
- [ ] Enhance security measures for file uploads
  - [ ] Implement more robust file type validation
  - [ ] Add file content analysis for potential security threats
  - [ ] Implement strict file size limits with clear user feedback
- [ ] Improve input validation and sanitization
  - [ ] Validate all user inputs before processing
  - [ ] Apply consistent sanitization to all inputs
  - [ ] Prevent potential SQL injection points

### 4. Form Processing Improvements
- [ ] Refactor form processing
  - [ ] Consolidate title and logo forms into a single form to simplify UX
  - [ ] Implement a more object-oriented approach for settings management
  - [ ] Group related settings together in the code
- [ ] Improve form validation
  - [ ] Add validation for logo uploads (dimensions, aspect ratio)
  - [ ] Provide more specific error messages for validation failures
  - [ ] Preserve form data on validation errors

### 5. Performance Optimization
- [ ] Optimize database operations
  - [ ] Reduce number of database calls where possible
  - [ ] Implement proper indexing for frequently accessed data
  - [ ] Use prepared statements consistently
- [ ] Improve file handling performance
  - [ ] Optimize file operations to reduce disk I/O
  - [ ] Implement proper caching mechanisms
  - [ ] Add file operation timeouts to prevent hanging
- [ ] Add settings caching
  - [ ] Implement a caching layer for app settings
  - [ ] Add cache invalidation when settings are changed


## Notes for Implementation
- Remember to use the `functions.php` file for new utility functions
- Keep all styling updates in the SCSS files (public/assets/scss)
- Document all changes in the devbook.md file
- Consider adding unit tests for critical functionality

