# Staff Directory Application - Development Documentation

## Version History

### Version 1.2 (March 2025)

#### April 13, 2025
*JavaScript Conditional Logging and Image Upload Fixes*

- Implemented conditional console logging in JavaScript
  - Added window.DEVMODE flag that's only injected in development mode
  - Used environment variable $_ENV['DEV_MODE'] to control JavaScript logging
  - Ensured no debug flags are exposed in production DOM
  - Created consistent debug logging pattern across all JavaScript files
  - Improved code maintainability by centralizing debug configuration

- Fixed image upload and removal issues
  - Resolved bug where remove button wouldn't appear when uploading an image to a profile with no existing image
  - Modified edit.php to always render the remove button in the DOM but control visibility with CSS
  - Ensured consistent behavior across all admin pages (edit.php, add.php, settings.php, companies.php)
  - Improved user experience by making image management more intuitive
  - Fixed edge cases in image handling for better reliability

- Cleaned up orphan and duplicate code in settings.php
  - Created a centralized image validation function for consistent file validation
  - Removed redundant database connection code in favor of centralized functions
  - Fixed error handling in process_logo_upload with specific error messages
  - Eliminated duplicate logo removal button logic
  - Simplified form submission handling for better maintainability

#### April 13, 2025
*Subdirectory Deployment and Routing Implementation*

- Implemented comprehensive subdirectory deployment support
  - Created a flexible routing system that works in both root and subdirectory deployments
  - Added centralized path handling with APP_BASE_URI constant
  - Implemented proper URL and asset path generation with helper functions
  - Created a dedicated 404 handler with proper redirection
  - Fixed all hardcoded paths throughout the application

- Enhanced application architecture with modern routing patterns
  - Implemented Front Controller pattern for centralized request handling
  - Created a Router class using FastRoute for efficient URL routing
  - Added middleware support for request/response processing
  - Implemented proper separation between URL paths and filesystem paths
  - Fixed path construction in image generation and file uploads

- Improved path handling across the application
  - Updated JavaScript to use window.APP_BASE_URI for all dynamic URLs
  - Modified database storage to use relative paths without the subdirectory prefix
  - Updated all file operations to use PUBLIC_PATH constant
  - Fixed placeholder image generation to work correctly in subdirectory deployments
  - Ensured proper URL construction for all links and forms

- Created comprehensive documentation for subdirectory deployment
  - Added detailed Subdirectory_Deployment_Configuration_Checklist.md
  - Documented all required configuration steps for Apache and Nginx
  - Provided troubleshooting guidance for common deployment issues
  - Included examples of correct and incorrect path handling approaches
  - Created step-by-step instructions for server setup and configuration


#### April 5, 2025
*Config Directory Relocation for Enhanced Security*

- Relocated the config directory from within public folder to project root level
  - Moved all configuration files out of web-accessible location
  - Updated file paths in `env_loader.php` to properly reference `.env` file
  - Modified all include/require statements across the application
  - Ensured proper relative path references in all files
- Relocated authentication configuration for additional security
  - Moved `auth_config.php` from `public/admin/auth/` to the config folder
  - Updated include paths in all authentication-related files
  - Centralized all sensitive configuration in a single secured location
  - Simplified path references by keeping related config files together
- Security benefits:
  - Configuration files are now completely outside web root
  - Protected sensitive database connection details from potential exposure
  - Authentication settings secured from direct web access
  - Followed security best practices for PHP applications
  - Prevents direct access to configuration files even if server misconfiguration occurs
- Implementation details:
  - Updated `env_loader.php` to use correct `dirname(__DIR__)` path reference
  - Modified all files that include config files to use updated paths
  - Adjusted relative path references in authentication system
  - Maintained backward compatibility with existing functionality
  - No changes to actual logic or behavior of the application

#### March 30, 2025
*Admin Interface Structure Improvement and Session Message Standardization*

- Restructured admin interface files to improve organization and separation of concerns
  - Created new `admin_head.php` for PHP initialization and security checks
  - Modified `admin_header.php` to focus solely on HTML output
  - Implemented consistent admin file pattern with processing before HTML output
  - Added security constant `INCLUDED_FROM_ADMIN_PAGE` to prevent direct access
- Standardized session message handling across all admin files
  - Implemented consistent use of `set_session_message()` and `get_session_message()` functions
  - Replaced direct `$_SESSION` variable assignments with function calls
  - Ensured all form processing follows the Post/Redirect/Get pattern
  - Fixed message display with proper `!empty()` checks to prevent empty alert boxes
- Enhanced error handling and user experience
  - Standardized redirects after form submissions to prevent resubmission
  - Improved header handling to prevent "Headers already sent" errors
  - Added consistent border styling to alert messages
  - Ensured form data persistence on validation errors
- Technical improvements
  - Reduced code duplication by centralizing common initialization code
  - Enhanced security by implementing proper redirect patterns
  - Improved maintainability with standardized session message handling
  - Optimized code organization for better separation of concerns

#### March 28, 2025
*Company Statistics Dashboard Implementation*

- Added comprehensive company statistics to the admin dashboard
  - Implemented staff count per company metrics
  - Created visual representation of company size distribution
  - Added percentage calculations to show relative company sizes
  - Displayed total staff count for quick organization overview
- Enhanced admin user interface with modern statistics cards
  - Designed responsive grid layout for company statistics
  - Added progress bars to visually represent staff distribution
  - Ensured consistent styling with the rest of the admin interface
- Backend implementation details
  - Created new `get_all_company_statistics()` function in functions.php
  - Optimized SQL queries to calculate staff counts efficiently
  - Added percentage calculations for visual representation
  - Ensured proper handling of edge cases (empty companies, etc.)
- Improved admin index page organization
  - Added Companies column to staff listing table
  - Displayed company logos alongside company names
  - Updated styling for better visibility and recognition
  - Ensured consistent information hierarchy in admin views

#### March 27, 2025
*Company Management Implementation*

- Added comprehensive company management functionality to the admin interface
  - Created CRUD operations (Create, Read, Update, Delete) for companies
  - Implemented logo upload and management with dropzone interface
  - Added descriptive text fields for company information
  - Created proper validation for all company operations
- Enhanced user experience with consistent UI patterns
  - Used the same image preview and upload pattern as staff management
  - Ensured proper fallback to placeholder images
  - Added visual cues for non-deletable companies (companies with staff members)
- Backend implementation details
  - Added necessary company management functions in functions.php
  - Created secure file upload handling for company logos
  - Implemented clean-up of old logos when replaced or deleted
  - Added proper error checking for all database operations
- Visual enhancements
  - Added styling for company logos in tables and forms
  - Created a specialized image preview for company logos
  - Ensured consistent button styles across the admin interface

#### March 26, 2025
*Advanced Bidirectional Cascading Filters for Company-Department Selection*

- Implemented comprehensive bidirectional cascading filters system
  - Added dynamic department filtering based on selected company
  - Added dynamic company filtering based on selected department
  - Preserved filter selections when switching between filters
  - Fixed issues with filter state consistency and synchronization
- Created new backend functionality
  - Developed `get_departments_by_company()` function in functions.php
  - Added new `get_companies_by_department()` function for reverse filtering
  - Restructured AJAX handler in ajax_handlers.php to handle multiple filter requests
  - Implemented proper JSON responses with success/error messaging
- Updated frontend components with advanced state management
  - Added state tracking to prevent circular update issues between filters
  - Converted filter update functions to Promise-based architecture
  - Created helper functions for code reusability and maintainability
  - Improved error handling for failed AJAX requests
- Technical enhancements
  - Added optimized event handling to prevent infinite update loops
  - Fixed timing issues with asynchronous AJAX calls
  - Implemented proper selection maintenance across filter changes
  - Ensured compatible integration with existing filter/sort functionality

#### March 25, 2025
*Database Restructuring for Improved Company-Department Hierarchy*

- Restructured database to establish logical company-department hierarchy
  - Modified the order of columns in the `staff_members` table to place `company_id` before `department_id`
  - Updated all SQL INSERT statements in both main SQL file and migration file
  - Created comprehensive documentation of required code changes in `database_company_changes.md`
  - Generated prioritized TODO list in `database_todo.md` to ensure smooth transition
- Identified critical code updates needed:
  - Staff creation and editing forms need company selection capability
  - Database query functions require updates to handle the new structure
  - Frontend display must be enhanced to show company information
- Provided interim solutions to maintain functionality during transition
  - Temporary fixes for immediate application stability
  - Detailed testing plan for verifying changes

#### March 24, 2025
*SCSS Variables to CSS Custom Properties Migration*

- Converted all SCSS variables to CSS custom properties for improved maintainability
  - Moved variable definitions to `:root` in `_common.scss`
  - Replaced all SCSS variable references with CSS custom property syntax
  - Maintained consistent styling while enabling dynamic style changes
  - Added CSS custom properties for colors, spacing, fonts, and effects
- Improved code organization and reduced redundancy
  - Removed redundant variable definitions from `_variables.scss`
  - Updated box-shadow and transition properties across all files
  - Ensured consistent variable naming conventions
  - Enhanced footer styling with custom font size and weight
- Enhanced theming capabilities
  - Enabled dynamic style changes via JavaScript
  - Improved maintainability by centralizing style definitions
  - Provided better separation of concerns between structure and presentation

#### March 23, 2025
*Application Branding, Database Updates, and Code Improvements*

- Implemented comprehensive application branding system
  - Added customizable application titles for both frontend and admin areas
  - Created settings to manage default and custom logos
  - Added logo upload functionality with proper validation
  - Implemented option to revert to default logo
  - Added visual preview for logo changes
- Enhanced database with software development team structure
  - Updated departments list with 12 software development-focused departments
  - Added color-coding for each department for better visual organization
  - Created comprehensive sample data with 23 staff members across all departments
  - Included international names to represent a diverse global team
  - Standardized email format with staffdirectory.com domain
  - Aligned job titles with contemporary software industry roles
- Enhanced error handling for file uploads
  - Improved user feedback for file upload errors
  - Implemented specific error messages based on PHP error codes
  - Fixed logical structure of conditional statements for error handling
- Cleaned up debug logging and code structure
  - Removed all debug logging from logo upload and removal processes
  - Corrected duplicate conditional statements
  - Enhanced maintainability by removing unnecessary code
  - Improved code organization and readability

#### March 22, 2025
*Department Color Visualization & Code Improvements*

- Added visual department color indicators throughout the application
  - Created a reusable `.pill` class in `_common.scss` for consistent styling
  - Implemented department color pills in staff management table
  - Added color pills to staff cards in the frontend directory
  - Enhanced department selection in add/edit forms with color preview
- Created a centralized `get_text_contrast_class()` function in `functions.php`
  - Determines if text should be light or dark based on background color
  - Uses standardized luminance calculation formula
  - Ensures consistent text contrast across the application
  - Replaced duplicate code with function calls for better maintainability

#### March 22, 2025
*Placeholder Image System Enhancements*

- Implemented configurable placeholder image system for staff without profile pictures
- Added admin settings page for customizing placeholder appearance
- Created settings for font weight, background color, text color, and font size
- Added font size factor control with range slider (1-6 scale)
- Converted placeholder images from PNG to WebP format for better performance
- Implemented real-time preview with JavaScript for settings adjustments
- Added automatic image regeneration when settings change
- Used settings hash to track changes and avoid unnecessary regeneration
- Migrated placeholder settings from PHP file to database storage
- Added backward compatibility for file-based settings during migration

#### March 19, 2025
*Department Management System Implementation*

#### Database Schema Improvements
- Created a proper relational database structure for departments
  - Added a new `departments` table with columns:
    - `id` (Primary Key, auto-incrementing)
    - `name` (Unique, VARCHAR)
    - `description` (TEXT)
    - `created_at` (TIMESTAMP)
    - `updated_at` (TIMESTAMP)
  - Modified the `staff_members` table to use foreign keys
    - Changed `department` text field to `department_id` integer
    - Added proper foreign key constraint with ON DELETE RESTRICT
    - Ensured data integrity between staff and departments
- Provided a comprehensive migration path
  - Created migration script (`migrate_departments.sql`)
  - Backup of existing staff data
  - Automatic extraction of departments from existing records
  - Default departments population
  - Foreign key relationship setup

#### Department Management Interface
- Implemented a full CRUD interface for department management
  - Added "Departments" navigation item to admin area
  - Created new departments.php admin page with two-panel layout:
    - Department list with actions (view, edit, delete)
    - Add/Edit form for department management
  - Implemented validation to prevent deletion of departments in use
  - Added staff count indicator to show departments in use

#### Database Architecture Changes

The database schema has been updated to support proper relational data management:

```sql
-- Departments table
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Modified staff_members table
ALTER TABLE `staff_members`
  ADD COLUMN `department_id` int(11) NOT NULL AFTER `last_name`,
  ADD KEY `department_id` (`department_id`),
  ADD CONSTRAINT `staff_members_ibfk_1`
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
  ON DELETE RESTRICT ON UPDATE CASCADE;
```

##### Default Departments

The system is pre-configured with the following departments:
- IT (Information Technology)
- Marketing
- HR (Human Resources)
- Finance
- Operations

#### Application Code Updates

- Updated admin interface to use department dropdowns
  - Modified add.php and edit.php forms to use select elements
  - Changed SQL queries to reference department_id instead of department name
  - Updated parameter binding from string (`s`) to integer (`i`) for department_id
- Added new helper functions in functions.php:
  - `get_all_departments()` - Returns complete department records
  - `get_all_department_names()` - Returns only department names for dropdowns
  - `get_department_by_id()` - Retrieves a department by ID
  - `get_department_by_name()` - Retrieves a department by name
- Updated existing functions to work with the new structure
  - Modified `get_all_staff_members()` to JOIN with the departments table
  - Updated `get_staff_member_by_id()` to include department information

#### March 29, 2025
*Enhanced Search and Filter Architecture*

- Implemented a shared filtering core architecture
  - Extracted reusable filter functionality into a dedicated `filter-core.js` module
  - Created specialized frontend and admin filter implementations
  - Ensured consistent filtering behavior across interfaces
  - Improved code maintainability by centralizing common functionality
- Improved frontend filters
  - Renamed `main.js` to `frontend-filters.js` for better code organization
  - Adapted frontend code to use the shared filter core
  - Maintained full backwards compatibility with existing functionality
  - Added better error handling for filter operations
- Added admin dashboard filtering capabilities
  - Implemented consistent search, department, and company filters in admin view
  - Created `admin-filters.js` that leverages the shared filter core
  - Added dynamic table filtering for staff management
  - Ensured proper path handling for AJAX requests in admin context
- Technical implementation details
  - Used JavaScript module pattern for better code organization
  - Implemented Promise-based AJAX request handling
  - Added state tracking to prevent circular updates between filters
  - Created helper functions for consistent option handling
  - Fixed path issues with AJAX requests in different contexts
- UX improvements for filters (March 30, 2025)
  - Standardized filter input styles across frontend and admin interfaces
  - Added intelligent filtering to only show departments and companies with staff members
  - Created new `get_active_department_names()` and `get_active_company_names()` functions
  - Eliminated empty search results by removing unused filter options

### Version 1.1 - March 18, 2025
*Authentication System Optimization & Environment Configuration*

#### Authentication System Improvements
- Centralized authentication configuration
  - Created `auth_config.php` to consolidate all auth-related settings
  - Standardized session and cookie parameters across the application
  - Improved maintainability and security with centralized constants
  - Protected configuration file from direct access
- Added environment variable control for secure cookies
  - Created `USE_SECURE_COOKIES` environment variable
  - Implemented fallback to auto-detect HTTPS when not explicitly set
  - Added proper documentation for configuration options
- Enhanced authentication architecture
  - Created comprehensive authentication flow diagram
  - Documented file structure and responsibilities
  - Improved code organization and readability

#### Authentication System Architecture

The authentication system has been optimized with a centralized configuration approach. For complete details, please refer to the [dedicated authentication system documentation](authentication-system.md).

**Key Features:**
- Centralized configuration in `auth_config.php`
- Environment variable-based credentials
- Comprehensive flow with login modal and AJAX endpoints
- Secure session management with proper cookie handling
- Detailed security measures including protection against common vulnerabilities

### Version 1.0 - March 16-17, 2025
*Initial Release*

#### Directory Structure Reorganization
- Restructured application to follow security best practices
- Created a separate `public` directory to serve as the web root
- Moved all web-accessible files to the public directory
- Kept sensitive files and code outside the publicly accessible area
- Moved the `env` directory to the project root for easier access while maintaining security
- Updated all file paths and includes to reflect the new structure
- Modified the README with comprehensive directory structure documentation

#### Authentication System Initial Implementation
- Fixed authentication system to work with the new directory structure
  - Improved error handling in JSON API endpoints
  - Enhanced session management and persistence
  - Used output buffering to prevent unwanted output
  - Ensured consistent session initialization across the application
- Implemented robust login state persistence
  - Added proper cache control headers to prevent stale login information
  - Improved cookie handling for better session persistence
  - Fixed session cookie parameters for domain-wide availability
  - Enhanced error handling for network requests
- Fixed .htaccess configuration to avoid server errors
  - Removed invalid directives that were causing 500 errors
  - Updated security settings for proper directory access

#### Security Improvements
- Removed hardcoded credentials from codebase using environment variables
- Created external `.env` file located outside the web root for better security
- Implemented a custom environment loader in `config/env_loader.php`
- Updated database configuration and authentication system to use environment variables
- Added proper documentation in README for environment setup
- Created `.gitignore` file to prevent accidental commit of sensitive files
- Fixed SQL injection vulnerabilities by replacing string concatenation with prepared statements
  - Added parameter binding for all database queries
  - Implemented input validation for sort fields and sort order
  - Properly parameterized LIKE clauses for search functionality

#### Login Modal Enhancements
- Fixed login modal functionality to properly handle user authentication
- Resolved issues with modal positioning by updating SCSS for perfect centering
- Added a login status check endpoint (`check_login.php`) to determine if user is already authenticated
- Updated form submission to use AJAX instead of traditional form submission
- Improved error handling with clear error messages for failed login attempts
- Enhanced user experience by redirecting directly to admin dashboard when already logged in
- Updated modal structure to properly follow the SCSS component architecture
- Added proper error display within the modal body

#### Image Removal Feature
- Added a remove button (cross icon) to the image preview on both add.php and edit.php forms
- Implemented JavaScript functionality to handle image removal:
  - When clicked on add.php: Resets to placeholder image
  - When clicked on edit.php: Marks the image for deletion upon form submission
- Added hidden form field (`delete_image`) to track image deletion requests
- Updated PHP to handle image deletion from both filesystem and database
- Enhanced styling with improved positioning and visual feedback
- Ensured proper cleanup of file inputs and image preview elements
- Fixed edge cases for various image states (new uploads, existing images)

#### Profile Picture Implementation
- Replaced static default avatar with dynamic placeholder images
- Implemented [placehold.co](https://placehold.co/) service for generating placeholder images
- Used staff member initials (first letter of first name + first letter of last name) for personalized placeholders
- Different placeholder image sizes based on context:
  - Frontend cards: 600x400 with initials
  - Admin list: 50x50 with initials
  - Edit/Add forms: 200x200 with initials or "NEW" for add form
- Added robust file existence verification to prevent broken image links
- Created centralized `get_staff_image_url()` function to handle all image URL generation
- Added admin console warnings for missing profile images with detailed information

#### SCSS Implementation
- Restructured CSS into SCSS format for better maintainability
- Created modular SCSS structure with separate files:
  - `_variables.scss`: Contains color variables, breakpoints, and reusable mixins
  - `_common.scss`: Shared styles used in both frontend and admin interfaces
  - `frontend.scss`: Frontend-specific styles for staff directory display
  - `admin.scss`: Admin-specific styles for the dashboard and management interface
  - `main.scss`: Main import file showing dependency structure
- Implemented SCSS best practices:
  - Used variables for consistent colors and spacing
  - Created mixins for repeated patterns like flexbox layouts
  - Used nesting for cleaner, more readable code
  - Separated concerns into logical partials
  - Added responsive breakpoint mixins
- Updated header files to reference the appropriate compiled CSS files

#### LineIcons Integration
- Added the LineIcons icon library via npm
- Copied icon files to assets/vendor/lineicons directory
- Added CSS include to both frontend and admin header files
- LineIcons usage example: `<i class="lni lni-search"></i>` for a search icon

#### Drag & Drop Image Uploads
- Implemented native JavaScript drag and drop functionality for profile pictures
- Created a modern dropzone UI with visual feedback for drag events
- Enhanced the image preview functionality to work with both drag & drop and file input
- Improved the user experience when uploading staff profile images
- Used LineIcons for the upload icon interface

#### Additional Code Updates
- Updated `functions.php` to handle empty profile picture strings and added new functionality
- Enhanced admin dashboard with console warnings for missing images
- Added additional security headers and best practices through `.htaccess`
- Standardized code styling and formatting throughout the application

#### Placeholder Image System
- Implemented a configurable placeholder image system for staff without profile pictures
- Created an admin settings page to customize placeholder appearance
- Added settings for font weight, background color, text color, and font size
- Converted placeholder images from PNG to WebP format for better performance
- Implemented automatic image regeneration when settings change
- Used settings hash to track changes and avoid unnecessary regeneration
- Optimized font sizing algorithm based on image dimensions

### Version 1.2.5 (April 2025)

#### April 6, 2025
*Staff Form JavaScript Refactoring*

- Consolidated shared JavaScript functions from add.php and edit.php into a utility file
  - Created new `staff-form-utils.js` in assets/js directory
  - Extracted common form utilities into reusable functions
  - Added JSDoc comments to document function parameters and return values
  - Ensured backwards compatibility with existing functionality
- Implemented properly parameterized utility functions:
  - `updateDepartmentColorPreview()` - For displaying the department color
  - `getPlaceholderImageUrl()` - For generating placeholder image URLs
  - `handleFileSelection()` - For handling file uploads
  - `setupDragAndDrop()` - For file drag and drop functionality
  - `preventDefaults()` - Helper for drag and drop events
  - `debounce()` - Utility for limiting repeated function calls
- Enhanced code maintainability and organization:
  - Reduced code duplication between forms
  - Made future changes easier to implement
  - Improved code readability
  - Standardized parameter passing conventions
  - Enhanced separation of concerns between shared and page-specific code
- Preserved form-specific behaviors:
  - Kept duplicate checking logic in add.php
  - Maintained edit.php-specific image handling for existing staff members
  - Ensured smooth integration with PHP-generated data

#### April 2, 2025
*Staff Member Duplicate Checking System*

- Implemented comprehensive duplicate checking for staff member creation
  - Added case-insensitive duplicate name detection (first name + last name combination)
  - Added case-insensitive duplicate email detection
  - Created both client-side and server-side validation for maximum security
- Enhanced user experience with real-time feedback
  - Added AJAX-based validation to check duplicates without page reload
  - Implemented visual indicators (red borders) for duplicate fields
  - Added clear error messages displayed immediately below relevant fields
  - Created form submission prevention when duplicates are detected
- Backend implementation
  - Created a reusable `check_staff_duplicate()` function in functions.php
  - Implemented API endpoint `/includes/check_duplicate.php` for AJAX requests
  - Added server-side validation in form processing as a fallback security measure
  - Built robust error handling with descriptive user feedback
- Technical enhancements
  - Used debouncing technique to limit API calls during typing
  - Added proper error handling for AJAX requests
  - Implemented event delegation for efficient event handling
  - Ensured cross-browser compatibility for all features

## Environment Configuration
- PHP 7.4
- MySQL database (Connection name: `staff_dir`)
- Frontend UI using Tailwind CSS and Vanilla JavaScript
- Remixicon for icon library
- Local image storage in `uploads` directory
- Custom authentication system for admin area (previously was `.htaccess`/`.htpasswd`)
- Development URL: https://staffdirectory.local (configured via local hosts file)

## Project Roadmap

### Completed Features
- ✅ LineIcons library integration (March 16, 2025)
- ✅ Admin dashboard UI improvements with icons and buttons (March 16, 2025)
- ✅ Drag-and-drop image upload functionality (March 16, 2025)
- ✅ Centralized authentication configuration (March 18, 2025)
- ✅ Environment variable support for secure cookies (March 18, 2025)
- ✅ Authentication flow documentation and visualization (March 18, 2025)
- ✅ Enhanced placeholder image generation (March 22, 2025)
  - Added configurable font size factor setting
  - Converted placeholder images from PNG to WebP format
  - Improved image quality and reduced file sizes
- ✅ Fixed image handling for user profiles (March 22, 2025)
  - Fixed remove button visibility for placeholder images
  - Added real-time placeholder image updates when selecting departments
  - Improved name initial detection for placeholder images
  - Fixed user creation issues with empty profile pictures
- ✅ Enhanced placeholder image settings UI (March 22, 2025)
  - Added real-time preview for font weight and font size changes
  - Improved browser cache handling for placeholder image previews
  - Fixed font size slider update issues
  - Streamlined settings interface by removing redundant information
- ✅ Application branding system implementation (March 23, 2025)
  - Added customizable application titles for frontend and admin areas
  - Implemented custom logo upload and management
  - Added logo preview functionality with real-time updates
  - Created option to revert to default logo when needed
  - Improved error handling for file uploads
- ✅ Database enhancement for software development teams (March 23, 2025)
  - Updated departments list with 12 software development-focused departments
  - Added color-coding for each department for better visual categorization
  - Created comprehensive sample data with 23 staff members across all departments
  - Included international names to represent a diverse global team
  - Standardized email format with staffdirectory.com domain
  - Aligned job titles with contemporary software industry roles
- ✅ Company management system implementation (March 27, 2025)
  - Added CRUD interface for managing companies in the admin area
  - Implemented drag-and-drop logo upload with preview functionality
  - Created responsive company listings with proper styling for logos
- ✅ Company statistics dashboard implementation (March 28, 2025)
  - Added staff count metrics per company with visual progress bars
  - Implemented percentage calculations to show relative company sizes
  - Added total staff count card for quick organization overview
  - Enhanced admin index page with company column in staff listing
  - Added validation to prevent deleting companies with assigned staff
  - Implemented secure file handling for company logo uploads
  - Ensured consistent UI patterns matching staff management screens
- ✅ Enhanced Search and Filter Architecture (March 29, 2025)
  - Implemented shared filtering core architecture with `filter-core.js`
  - Created specialized frontend and admin filter implementations
  - Added dynamic table filtering for staff management
  - Standardized filter input styles across frontend and admin interfaces
  - Implemented intelligent filtering to only show departments and companies with staff
  - Added proper path handling for AJAX requests in different contexts
  - Used state tracking to prevent circular updates between filters
  - Improved code maintainability by centralizing common filtering functionality
- ✅ Staff Member Duplicate Checking System (April 2, 2025)
  - Added case-insensitive duplicate name and email detection
  - Implemented real-time AJAX-based validation with visual indicators
  - Created form submission prevention when duplicates are detected
  - Added server-side validation as a fallback security measure
  - Used debouncing technique to optimize AJAX request frequency
  - Implemented comprehensive error handling with descriptive feedback
- ✅ Tailwind CSS Integration (April 5, 2025)
  - Replaced all custom CSS with Tailwind utility classes
  - Converted all pages to modern responsive design
  - Improved UI consistency across the application
  - Enhanced responsive behavior for mobile and tablet views
  - Replaced LineIcons with Remixicon for better icon support
  - Added custom breakpoints for specific UI elements
  - Implemented Tailwind forms plugin for improved form styling
  - Streamlined build process with optimized CSS output
- ✅ Merge shared Javascript of edit.php and add.php in a dedicated js file
- ✅ Subdirectory Deployment and Routing Implementation (April 13, 2025)
  - Implemented comprehensive subdirectory deployment support
  - Enhanced application architecture with modern routing patterns
  - Improved path handling across the application
  - Created a dedicated 404 handler with proper redirection
  - Fixed all hardcoded paths throughout the application
  - Created comprehensive documentation for subdirectory deployment

### Planned Improvements

#### Version 1.2 (Planned)
- ~~Create a Git repository for version control~~
- ~~Department management section (Database table and admin interface)~~
- ~~Create a default logo for the App~~
- ~~Create a setting panel in the admin to set a custom logo instead of the default logo and modify associated titles for front-end and admin~~
- ~~Improve Placeholder Image Settings to set a real live preview of the update (Javascript)~~
- ~~Updated database with comprehensive software development departments and staff members~~
- ~~Create default user images to match the default application setup and alternate between users that have and don't have profile pictures to display examples of placeholder images~~
- ~~Convert all SCSS variables to CSS custom properties~~
- ~~Enable company groups management in case a company includes holdings~~
- ~~Replace LineIcons with Remixicon~~
- ~~Improve UX/UI design for admin and Front-end using Tailwind CSS~~
- ~~Add search and filters to the staff member management list~~
- ~~In homepage and admin filter, don't list companies and departments that do not have staff members~~
- ~~Check for existing user before submitting the form in add.php form~~
- ~~List every Php and remove unused functions~~
- ~~Merge shared Javascript of edit.php and add.php in a dedicated js file~~
- ~~Set a customizable path and folder name to set up the application access to a subdirectory like /public/staffdirectory and could configured on any kind of Apache server with locked configuration~~
- ~~Create a favicon~~
- Add internationalization support (FR/EN translation files)
- Improve unused placeholder images management to remove them when not needed either by programming a folder cleanup once a day and/or by using temporary images when editing settings or creating/editing users
- Install example app. on staffdirectory.jensen-siu.net

#### Version 1.3 (Planned)
- Add staff counts by departments in company statistics
- Set auto hide behavior to context messages
- Add sorting options in admin homepage staff member list
- Set pagination to admin staff member list
- Improve and centralize javascript functions for image preview
- Refactor image handling architecture to address the following issues:
  - Separate image handling concerns across different page types (settings, add, edit)
  - Implement consistent interface for image actions (upload, remove, preview)
  - Remove temporary fixes in settings.php for logo handling
  - Create modular, page-specific image handlers that share common functionality
- Enable login possibility to access the front-end
- Add an optional date of entrance in the company in the user info (update DB, add.php, edit.php, index.php and admin/index.php)
- Add a birthday field for staff members as optional
- Include company logo in the staff member image (as a watermark)
- Add advanced search features for Staff Members Management
- CSV import/export functionality
- Enhanced authentication security:
  - CSRF protection for login form
  - Login rate limiting
  - Remember-me functionality
  - Additional session security parameters
  - Class-based authentication architecture
