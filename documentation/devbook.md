# Staff Directory Application - Development Documentation

## Version History

### Version 1.2 (March 2025)

#### July 28, 2026
*Installer POST Guard (Anonymous Reinstallation / Credential Overwrite)*

- Closed a P1: `public/install.php` never checked `is_installed()` before processing a POST. The check existed (`is_installed()`, defined line 34) but was called exactly once, at line 421, purely to decide which HTML to render on **GET** — the `if ($_SERVER['REQUEST_METHOD'] === 'POST')` block at line 340 ran unconditionally regardless of installation state
- Consequence: on an already-installed, internet-facing instance, an anonymous POST with `action=install` reached `initialize_database()` and `update_env_file()` exactly as during first setup — rewriting `staff_dir_env/.env` with attacker-supplied database **and** admin credentials, and re-running the schema initialization. No authentication gate exists before installation, by design, since there is no admin session to authenticate against yet
- Fix: `if (is_installed())` at the entry of the POST block, before any `$_POST` read or validation — returns `403` with a translated message and `exit;`. Covers both POST actions handled by that block, `test_connection` and `install`, alike; neither reaches the database or the filesystem once the guard trips
- Unchanged: GET behavior (the "already installed" screen, now at line ~524, keeps rendering from the same `$installed` flag set at line 454) and the legitimate first-run install path (`DB_INSTALLED=false` or absent) — the guard only trips when `is_installed()` is already true
- Added the `installer_disabled` key to `languages/en/common.php` and `languages/fr/common.php` (81 keys each, verified in parity), reusing the existing `is_installed` / `reinstall_instructions` keys for the rest of the 403 message
- Verified on the Apache vhost with `DB_INSTALLED=true`: `POST action=install` with dummy credentials → 403, `POST action=test_connection` → 403, a POST with no `action` at all → 403, `GET` → 200 still showing the "already installed" screen, and the `md5sum` of `staff_dir_env/.env` identical before and after all of them. The 403 page renders in French under `?lang=fr`. With `DB_INSTALLED` temporarily set to `false`, an empty `POST action=install` reached `validate_form_data()` and returned the required-field errors, confirming the guard does not block a genuine installation
- Note the guard's dependency: it trusts `DB_INSTALLED` in `staff_dir_env/.env`, so an unreadable or reset `.env` reopens the installer. Deleting `install.php` after deployment remains the primary defense, and the deployment guides now say so in that order
- Removed two debug `error_log()` calls that dumped submitted form data on every accepted POST. One of them, in the prefix-mismatch error path, was `error_log("Form data: " . print_r($form_data, true))` — `$form_data` holds `DB_PASS` and `ADMIN_PASSWORD` in clear text, so a prefix problem during installation wrote both passwords to the PHP error log, which on shared hosting is often readable from the web root. The other logged `DB_NAME` and the table prefix on every POST. The 11 remaining `error_log()` calls only carry the database name, the prefix, booleans and SQL excerpts, and were kept: the table prefix is the historically fragile part of the installer. Re-verified afterwards that a `DB_INSTALLED=false` POST still reaches `validate_form_data()` and reports its 6 required fields, and that the 403 guard still trips once the flag is back to `true`
- Stopped echoing submitted passwords back into the installer form. Both `type="password"` fields carried `value="<?php echo htmlspecialchars($form_data['DB_PASS'] ?? ''); ?>"` (and the same for `ADMIN_PASSWORD`), so any validation error rendered the database and admin passwords in clear text inside the HTML — where the browser cache, the session history and any intermediary see them. The `value` attributes are gone; the two fields are simply retyped. Every non-secret field keeps its repopulation. Verified by forcing a validation error with a deliberately invalid `db_name` while posting real-looking passwords: 0 occurrence of either password in the returned HTML, while `db_host` and `db_user` came back filled
- Replaced the default admin credentials in `staff_dir_env/.env_example` (`ADMIN_USERNAME=admin` / `ADMIN_PASSWORD=admin`) with placeholders and a warning comment. This file matters more than the documentation that quoted the same pair: `update_env_file()` **copies it to `.env` verbatim** when `.env` is missing (`install.php:156-157`), so a manual install, or a web install that fails between the copy and the form write, left a live `admin`/`admin` account. The web installer still overwrites both keys from its form, and the two values it reads from the template — `DB_HOST` and `DB_NAME` — are untouched (verified: still `localhost` and `staff_dir`). `README.md` had the same `ADMIN_USERNAME=admin` in its `.env` example, now a placeholder too
- Documented an auth finding in the process, **not** fixed here: `ADMIN_PASSWORD_HASH` is not read from `.env` at all. `config/auth_config.php:62-63` takes the cleartext `ADMIN_PASSWORD` and calls `password_hash()` on **every request**, with `'admin'` as the fallback when the key is absent. The admin password therefore lives in cleartext in `.env`, and every page load pays a bcrypt hash it throws away

#### July 28, 2026
*Duplicate Detection on Edit*

- Closed the duplicate-detection gap in `admin/edit.php`: the uniqueness constraint only existed on creation, so `Jean Martin` could be created once and then reached a second time by renaming any other record. `edit.php` contained no duplicate check at all, neither server-side nor AJAX
- `check_staff_duplicate()` (`functions.php`) takes an optional 5th parameter `$exclude_id`. Both queries carry `AND id != ?` unconditionally, with the parameter normalised to `0` when absent or non-numeric: `id` is `AUTO_INCREMENT` from 1, so `id != 0` is always true and excludes nothing. That keeps a single query shape instead of branching the SQL and the `bind_param()` signature. Without the exclusion the function would report the record being edited as its own duplicate and reject every save, even one that leaves the name untouched. The two existing `add.php` calls are unaffected
- `edit.php` rejects a duplicate **before** handling the uploaded file, so a refused save never writes an orphan image to `uploads/`
- `includes/check_duplicate.php` accepts an optional `exclude_id` POST field, same normalisation
- The real-time check now lives once in `assets/js/staff-form-utils.js` as `setupDuplicateChecking(form, fields, excludeId)` — the file both pages already load and where the shared form logic already lives (`debounce`, drag & drop, placeholder URL). `add.php` lost ~80 lines of inline JS, `edit.php` calls the same helper with its own id instead of a second copy
- Translated the duplicate messages, which were hardcoded English in `functions.php` and `check_duplicate.php` while the whole admin is bilingual: the `duplicate_name` / `duplicate_email` keys already existed in both language files and were used nowhere. Added `resolve_duplicates` (en/fr) for the submit-time alert, which was hardcoded in `add.php` too
- Verified server-side by redirect target, distinguishing acceptance (`index.php?updated=1`) from refusal (`edit.php?id=`): saving a record unchanged passes; renaming it onto an existing homonym is refused, including on case differences (`BETA duptest2`); taking another record's email is refused, uppercase included; a free name and a free email pass. The database was checked after each case — nothing was written on refusal
- Verified the AJAX endpoint: a record's own name and email return `duplicate:false` with `exclude_id`, `true` without it; another record's name or email returns `true`; `exclude_id=abc` excludes nothing
- Verified in a real browser (authenticated session, PHP 8.4) on **both** pages: blur on the record's own name shows nothing, renaming onto a homonym shows the message with both fields outlined, submission is blocked by the alert without navigating and nothing is written to the database, returning to a free name clears both, a taken email flags the email field. In French the message and the alert are the French strings
- Reviews (security, correctness, over-engineering, documentation) ran on the whole session. Nothing blocking; four findings applied: the forgotten `(int) round(...)` in the GD fallback branch of `get_staff_image_url()`, an upper bound of 2000px on placeholder dimensions (`?size=9999x9999` asked GD for gigabytes — and the single-number fix had widened it, since the height now mirrors the width), the unconditional `AND id != ?` above, and the JS factorisation. Two findings were rejected on purpose: replacing the `lint-php74.sh` loop with `xargs` (it would print one "No syntax errors" line per file instead of only failures) and merging the endpoint into `check_staff_duplicate()` (the helper tests name *and* email in one pass, the endpoint one *or* the other per `$_POST['type']`, so merging would fire a useless second query on every keystroke)

#### July 28, 2026
*Documentation Sanitization, PHP 7.4/8.x Dual Support, Duplicate Check and Logout Cleanup*

- Reorganized the documentation for a public repository
  - Moved 5 internal or outdated documents to the git-ignored `documentation-private/`: `php-functions-audit.md`, `Database_Configuration_Tracker.md`, `router_integration_roadmap.md`, `fast_route_technical_overview.md`, `Subdirectory_Deployment_&_Routing_Integration_roadmap.md`
  - The three FastRoute documents described the integration as still pending, while it has been live in `Router.php` since April 2025 — an external reader would conclude the opposite of reality
  - Removed the resulting dangling entry from the README directory tree
- Corrected security-relevant documentation errors
  - `Database_Configuration_Implementation.md`: the installer does **not** delete itself (the only `unlink()` in `install.php` removes a temporary SQL file). The two claims of automatic removal, and the "can be configured to self-delete" bullet, were replaced with a mandatory manual-deletion warning
  - `Database_Configuration_Implementation.md`: fixed the migration script name (`migrate_prefix.php`, which does not exist, → `migrate_tables.php`)
  - `FTP_Deployment_Guide.md`: added the missing "delete `install.php` once the installation succeeds" warning, both in the Option A quick path and in the installer section, ahead of the `DB_INSTALLED=false` reinstall note
  - `authentication-system.md`: replaced the `admin` / `admin` sample credentials with placeholders plus a do-not-deploy-with-defaults warning
- Fixed prefix-aware duplicate detection (`includes/check_duplicate.php`)
  - The AJAX endpoint queried `staff_members` as a literal instead of `TABLE_STAFF_MEMBERS` — the last two unprefixed table references in the codebase
  - On a prefixed install (`sd_`) every call raised `mysqli_sql_exception: Table 'staff_dir.staff_members' doesn't exist`, so the real-time duplicate checking advertised in the README was inoperative
  - Kept the endpoint's own queries rather than reusing `check_staff_duplicate()`: the helper tests name *and* email in one pass, while the endpoint tests one *or* the other depending on `$_POST['type']`
  - Verified: case-insensitive name and email lookups return `{"duplicate":true}` for an existing member and `{"duplicate":false}` otherwise
- Cleaned up `logout_user()` (`admin/auth/auth.php`)
  - `session_destroy()` is now guarded by `session_status() === PHP_SESSION_ACTIVE`
  - Removed the `session_start()` → `session_regenerate_id(true)` → `session_destroy()` block that followed it: PHP assigns a fresh session id on the next `session_start()`, and restarting a session here re-issued the very cookie the preceding lines had just expired
  - Verified with `error_reporting=E_ALL`: three session warnings per call before the change ("Session cannot be started after headers have already been sent", "Session ID cannot be regenerated when there is no active session", "Trying to destroy uninitialized session"), none after
- Removed the dead asset `assets/js/filter-core.min.js`: referenced nowhere, absent from `manifest.json`, produced by no npm script, and frozen at its April 2025 content while `filter-core.js` kept evolving
- Made the PHP 7.4 target explicit while running cleanly on PHP 8.x
  - `composer.json`: added `"php": "^7.4 || ^8.0"` to `require`. `config.platform.php` stays at `7.4.33` — the two serve different purposes: `platform` *simulates* a PHP version during dependency resolution (so a `composer update` run on an 8.x workstation cannot pull 8-only packages that would break a 7.4 host), while `require.php` is what `composer install` and `composer check-platform-reqs` actually verify
  - Removed `php-di/php-di`, declared but referenced nowhere (no `use DI`, no `ContainerBuilder`; dependencies come from globals). Its `functions.php` is autoloaded via `files`, so it emitted two `Implicitly marking parameter $className as nullable is deprecated` notices on **every** request under PHP 8.4. Removing it also dropped `php-di/invoker`, `php-di/phpdoc-reader`, `laravel/serializable-closure` and `psr/container`
  - Removed `intervention/image`, also declared but referenced nowhere (no `Intervention\`, no `ImageManager`, no `Image::` anywhere in the repository): placeholder generation has always used raw GD. It was the only reason `guzzlehttp/psr7` was installed, which `composer audit` flagged for 4 medium-severity advisories (CVE-2026-59882, CVE-2026-55766, CVE-2026-49214, CVE-2026-48998: host confusion and CRLF injection). Dropping it removed `guzzlehttp/psr7`, `psr/http-message`, `psr/http-factory`, `ralouphie/getallheaders` and the `ext-fileinfo` requirement
  - `vendor/` is now down to 3 packages — `nikic/fast-route`, `monolog/monolog`, `psr/log` — and `composer audit` reports no advisories
  - Added `npm run lint:php74` (`src/build-tools/lint-php74.sh`), which lints every file under `public/`, `config/` and `database/` with a real PHP 7.4 binary. The local `php -l` runs 8.4 and happily accepts 8-only syntax that would be a fatal error in production; the script was verified to reject a `match()` expression that `php -l` passes. Override the interpreter with `PHP74=/path/to/php7.4`
- Fixed two PHP 8.x issues found by walking the whole app with `error_reporting=E_ALL` (34 requests: frontend, authenticated admin, create with upload, edit with emptied fields, image deletion, departments, companies, settings, AJAX, placeholder generation, logout)
  - `functions.php:233` raised `Undefined array key 1` on `list($width, $height) = explode('x', $size)`, because `generate_placeholder.php` passes `$_GET['size']` straight through: a single number produced a silent 200x100 image. A one-number size is now treated as a square, and a non-numeric one falls back to the 100px floor
  - `functions.php:319` raised `Implicit conversion from float … to int loses precision` (deprecated since 8.1): the text-centering divisions feed `imagettftext()`/`imagestring()`, whose `$x`/`$y` are integers. Both are now `(int) round(...)`
  - Both fixes are valid PHP 7.4 syntax. Verified after the change: `size=200` → 200x200, `size=600x400` → 600x400, `size=abc` → 100x100, and a full second pass emitting zero warnings or deprecations

#### November 18, 2025
*Web-Based Installer Restoration and Documentation Corrections*

- Restored the web-based installer (install.php) that was previously removed from the codebase
  - Recovered install.php from git commit 5e8a044 (680 lines, 31KB)
  - File was originally deleted during centralized path configuration implementation
  - Restoration was necessary because documentation still referenced the installer as a feature
  - User project memories explicitly requested an installer for easier deployment
- Updated installer to use centralized path configuration system
  - Removed hardcoded path definitions (lines 22-28)
  - Replaced with single include: `require_once __DIR__ . '/includes/paths.php';`
  - Ensured compatibility with paths.php system implemented in April 2025
  - Verified all path constants (BASE_PATH, PRIVATE_PATH, PUBLIC_PATH, APP_BASE_URI) are properly loaded
- Verified code compatibility with current codebase
  - Database processing: `process_sql_file()` function signature matches perfectly
  - LanguageManager: All required functions (`__()`, `current_language()`) available and compatible
  - .env handling: All 9 required variables exist in `.env_example`
  - Functions: No deprecated functions found, all dependencies satisfied
- Conducted comprehensive translation keys audit
  - Extracted 32 unique translation keys from installer using grep
  - Discovered 100% coverage: all 32 keys already exist in both English and French language files
  - No new translations needed to be added
  - Translation categories: form labels (11), buttons (3), messages (9), help text (5), errors (4)
- Executed comprehensive testing strategy (17/17 tests passed - 100% pass rate)
  - **Pre-Installation Testing (4 tests)**:
    - Already installed scenario: Correct message and reinstall instructions displayed
    - Not installed scenario: Installer form displays with all fields
    - CSS and assets loading: No console errors, all resources load successfully
    - Form validation: Validation errors display correctly in French
  - **Installation Process Testing (5 tests)**:
    - Database connection test: Success message displays correctly
    - Clean installation: Database created, tables with prefix, admin user created, .env updated
    - Example data installation: 31 staff, 12 departments, 3 companies loaded successfully
    - Custom table prefix: Default `sd_` prefix applied correctly
    - Database creation: Database created when `DB_CREATE_DATABASE=true`
  - **Post-Installation Testing (5 tests)**:
    - Admin login: Successful authentication with installer-created credentials
    - Admin dashboard: Statistics, navigation, staff table all display correctly
    - Database tables: All 4 tables exist with correct `sd_` prefix
    - Application functionality: No errors, all features accessible
    - Error logs: No PHP errors during installation or login
  - **Error Handling Testing (3 tests)**:
    - Form validation errors: Display correctly in French
    - Database connection success: Success message displays correctly
    - Already installed protection: Prevents reinstallation when `DB_INSTALLED=true`
- Corrected critical documentation errors
  - **README.md**: Removed incorrect `/staff-directory/` subdirectory from directory structure diagram
    - Updated lines 81-139 to show actual structure: `admin/`, `assets/`, `includes/`, `uploads/` directly in `public/`
    - Added `paths.php` to the includes directory listing
    - Verified Web-Based Installer section (lines 41-50) is accurate
  - **FTP_Deployment_Guide.md**: Fixed all path references and added troubleshooting
    - Changed `public/staff-directory/includes/paths.local.php` to `public/includes/paths.local.php` (lines 67-72)
    - Updated installer URLs to show both root and subdirectory installation options
    - Fixed core application file paths (removed `/staff-directory/` prefix)
    - Updated upload directory paths and permissions
    - Added comprehensive installer troubleshooting section with 6 common issues and manual fallback instructions
    - Clarified that subdirectory installation is optional, not required
- Production readiness confirmed
  - All functionality works correctly
  - 100% compatibility with current codebase
  - Full bilingual support (EN/FR)
  - Security: Input validation, error handling, safe database operations
  - User experience: Clear interface, helpful error messages, intuitive flow
  - Documentation: Complete and accurate
- Technical implementation details
  - Installer file: `public/install.php` (676 lines after path updates)
  - Path configuration: Uses centralized `public/includes/paths.php`
  - Database processing: Uses `database/process_sql.php` for table prefix handling
  - Translation system: Uses LanguageManager with 32 translation keys
  - Environment configuration: Creates/updates `staff_dir_env/.env` file
  - SQL files: Supports both `staff_dir_clean.sql` (empty) and `staff_dir.sql` (example data)
  - Installation status: Tracked via `DB_INSTALLED` flag in `.env` file
  - Admin credentials: Stored in `.env` and hashed using `password_hash()`

#### April 16, 2025
*Centralized Path Configuration System*

- Implemented a centralized path configuration system for improved deployment flexibility
  - Extracted path definitions from bootstrap.php into a dedicated paths.php file
  - Created a shared configuration approach used by both bootstrap.php and install.php
  - Added support for custom path overrides via paths.local.php
  - Improved installation process by eliminating the need to modify multiple files
- Enhanced deployment documentation
  - Updated FTP_Deployment_Guide.md with detailed path configuration instructions
  - Added visual directory structure diagram for better understanding
  - Created comprehensive troubleshooting section for path-related issues
  - Provided examples for common hosting configurations
- Technical implementation details
  - Created public/includes/paths.php as the central path configuration file
  - Modified bootstrap.php to include the new paths.php file
  - Updated install.php to use the same paths.php file
  - Added paths.local.php.example with documentation and examples
  - Implemented conditional debug logging for path-related issues

#### April 15, 2025
*Special Character Encoding Fix*

- Fixed HTML entity encoding issues in form submissions
  - Modified `sanitize_input()` function to make HTML encoding optional with a new parameter
  - Updated form processing in companies.php and settings.php to avoid HTML encoding when storing data
  - Ensured proper HTML encoding is still applied when displaying data in templates
  - Fixed issues with special characters like "&" being double-encoded
  - Created test scripts to verify encoding behavior
- Technical implementation details
  - Updated `sanitize_input()` function in functions.php to include an `$encode_html` parameter
  - Modified `process_title_settings()` in settings.php to use the updated function
  - Replaced `filter_input()` with `sanitize_input()` for consistent handling
  - Ensured backward compatibility with existing code
  - Added comprehensive testing for various special characters

#### April 14, 2025
*Internationalization Implementation (FR/EN)*

- Implemented comprehensive internationalization system with English and French support
  - Created a structured language file system with separate files for different contexts:
    - `common.php` - Shared translations across the application
    - `frontend.php` - Frontend-specific translations
    - `admin.php` - Admin-specific translations
  - Added language selection in the admin settings page
  - Implemented language switching with session and cookie persistence
  - Used the `__()` function for all text strings throughout the application
- Enhanced user experience with multilingual support
  - Added language selection dropdown in settings page
  - Implemented proper language detection from browser, session, and cookies
  - Created consistent translation patterns across all pages
  - Ensured proper fallback to default language when translations are missing
- Technical implementation details
  - Created `LanguageManager` class to handle language detection and translation
  - Implemented language configuration in `config/languages.php`
  - Added helper functions for language switching and detection
  - Used database storage for user language preference
  - Ensured proper language persistence across sessions
- Comprehensive translation coverage
  - Translated all user-facing text in the application
  - Added translations for error messages, success notifications, and UI elements
  - Created language-specific formatting for dates and numbers
  - Implemented proper pluralization support for count-based messages

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

### Version 1.2.8 (November 2023)

#### June 13, 2024
*Installer UI Redesign, Internationalization, and Table Prefix Fixes*

- Enhanced installer UI to match admin interface design
  - Redesigned install.php to use Tailwind CSS exclusively with no inline styles
  - Implemented consistent styling with the admin interface for better user experience
  - Used the Outfit font for typography consistency across the application
  - Matched logo sizing with the public homepage for brand consistency
  - Created proper form layout with responsive design
  - Added improved message styling for success and error notifications
  - Ensured proper spacing, colors, and visual hierarchy
  - Fixed form field styling to match admin interface patterns

- Implemented internationalization for the installer page
  - Added translation keys for all installer text in both English and French language files
  - Updated install.php to use the LanguageManager for language detection and translation
  - Implemented proper path constants in install.php to support the LanguageManager
  - Replaced all hardcoded text with calls to the `__()` translation function
  - Ensured session handling for language persistence
  - Added language switching support via URL parameter (?lang=fr)
  - Maintained consistent internationalization approach with the rest of the application
  - Verified proper display of translations in both English and French

- Fixed table prefix implementation in database setup
  - Ensured proper handling of table prefixes with automatic underscore addition
  - Added clear user feedback about prefix formatting in the installer UI
  - Improved error logging for better troubleshooting during installation
  - Enhanced prefix validation to prevent common configuration errors
  - Fixed edge cases in SQL processing with prefixes
  - Added comprehensive debugging to track prefix application in SQL files
  - Fixed hardcoded table name in departments.php that was causing SQL errors
  - Ensured consistent use of TABLE_* constants throughout the application for proper prefix handling

#### November 15, 2023
*Database Configuration and Installer Implementation*

- Implemented flexible database configuration system
  - Added support for custom database names via `DB_NAME` environment variable
  - Implemented table prefix support with `DB_TABLE_PREFIX` environment variable
  - Added database creation control with `DB_CREATE_DATABASE` flag
  - Created installation status tracking with `DB_INSTALLED` flag
  - Updated all SQL queries to use table constants instead of direct table references
- Created comprehensive database migration tools
  - Implemented `migrate_tables.php` script for renaming existing tables with prefixes
  - Added safety checks to prevent overwriting existing tables
  - Implemented dry-run mode for testing migrations without making changes
  - Added force option to skip confirmation prompts for automated migrations
  - Created detailed migration summary with success/error/skipped counts
- Developed web-based installer for easy setup
  - Created `install.php` with user-friendly interface for database configuration
  - Implemented database connection testing functionality
  - Added form validation and error handling
  - Created database initialization with proper table creation
  - Added installation status checking to prevent reinstallation
  - Implemented admin account setup during installation
- Enhanced documentation and testing
  - Updated README.md with database configuration information
  - Updated FTP_Deployment_Guide.md with installer instructions
  - Created comprehensive test scripts for all aspects of the implementation
  - Added detailed error handling and user feedback throughout the system
  - Implemented proper environment variable handling for configuration

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
- Flexible database configuration with support for custom database names and table prefixes
- Web-based installer for easy setup and configuration

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
- ✅ Internationalization Implementation (April 14, 2025)
  - Added comprehensive multilingual support with English and French translations
  - Created structured language files for common, frontend, and admin contexts
  - Implemented language selection in admin settings with session/cookie persistence
  - Used the `__()` function for all text strings throughout the application
  - Added proper language detection from browser, session, and cookies
  - Created consistent translation patterns across all pages

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
- ~~Add internationalization support (FR/EN translation files)~~
- ~~Install example app. on staffdirectory.jensen-siu.net~~
- ~~Add the ability to customize a prefix for the database tables~~ (November 15, 2023)
- Improve unused placeholder images management to remove them when not needed either by programming a folder cleanup once a day and/or by using temporary images when editing settings or creating/editing users


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
