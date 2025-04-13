# Staff Directory Application -- Version 1.2.7

A web-based staff directory application that allows administrators to manage staff members and provides a user-friendly interface for employees to browse the directory.

## Features

### Admin Dashboard
- Add new staff members with details like name, company, department, job title, email, and profile picture
  - Real-time duplicate checking prevents adding staff with identical names or email addresses
- Edit existing staff members
- Delete staff members
- Manage companies with logo upload functionality
- Manage departments with custom color coding
- View company statistics and staff distribution
- Filter and search functionality for staff list
- Customizable application settings (logo, default placeholder image for staff members, and title)
- Protected by authentication system
- Flexible deployment options (root or subdirectory installation)

### Frontend Display
- Grid view of staff members
- Search and filter functionality
- Sorting options
- Responsive design for desktop and mobile

## Tech Stack
- Frontend: HTML, Tailwind, Vanilla JavaScript
- Backend: PHP 7.4, MySQL
- Routing: FastRoute for URL routing, Front Controller pattern
- Image Processing: Intervention Image 2.7 (WebP support)
- Image Storage: Local upload folder
- Logging: Monolog for structured logging

## Installation
1. Clone the repository
2. Import the database schema from `database/staff_dir.sql`
3. Configure database connection in `config/database.php`
4. Set up .env file for the authentication of the admin area
5. Ensure the uploads directory is writable by the web server
6. For subdirectory deployment, follow the detailed instructions in `documentation/Subdirectory_Deployment_Configuration_Checklist.md`

## Directory structure
The application follows a secure directory structure:

```
├── `public/`                       # Web accessible files (the web root)
│   ├── `staff-directory/`          # Application files (can be deployed in a subdirectory)
│   │   ├── `admin/`                # Admin interface files
│   │   │   ├── `auth/`             # Authentication system
│   │   │   │   ├── `auth.php`      # Authentication utility functions
│   │   │   │   ├── `check_login.php` # Login validation
│   │   │   │   ├── `login-modal.php` # Login modal template
│   │   │   │   ├── `login.php`     # Login form page
│   │   │   │   └── `logout.php`    # Logout functionality
│   │   │   ├── `add.php`           # Add new staff member
│   │   │   ├── `companies.php`     # Company management interface
│   │   │   ├── `departments.php`   # Department management interface
│   │   │   ├── `edit.php`          # Edit existing staff member
│   │   │   ├── `index.php`         # Admin dashboard page
│   │   │   └── `settings.php`      # Application settings & placeholder image configuration
│   │   ├── `assets/`               # CSS, JavaScript, images and other assets
│   │   │   ├── `css/`              # Compiled CSS files
│   │   │   ├── `fonts/`            # Font files
│   │   │   │   ├── `Outfit/`       # Outfit font files
│   │   │   │   └── `remixicon/`    # Remix icon font files
│   │   │   ├── `images/`           # Image assets
│   │   │   ├── `js/`               # JavaScript files
│   │   │   │   ├── `admin-filters.js` # Admin-specific filtering functionality
│   │   │   │   ├── `filter-core.js` # Core filtering logic shared between admin/frontend
│   │   │   │   ├── `frontend-filters.js` # Frontend-specific filtering functionality
│   │   │   │   ├── `staff-form-utils.js` # Shared utilities for staff add/edit forms
│   │   │   │   └── `main.js`       # Main application JavaScript
│   │   │   └── `vendor/`           # Third-party libraries
│   │   ├── `includes/`             # PHP includes for the public pages
│   │   │   ├── `404_handler.php`   # 404 error handler
│   │   │   ├── `admin_footer.php`  # Admin page footer
│   │   │   ├── `admin_header.php`  # Admin page header
│   │   │   ├── `ajax_handlers.php` # AJAX request handlers
│   │   │   ├── `AssetManager.php`  # Asset path management
│   │   │   ├── `bootstrap.php`     # Application bootstrap and configuration
│   │   │   ├── `check_duplicate.php` # AJAX endpoint for staff duplicate detection
│   │   │   ├── `controllers/`      # MVC controllers
│   │   │   │   ├── `AdminController.php` # Admin area controller
│   │   │   │   ├── `AjaxController.php` # AJAX request controller
│   │   │   │   ├── `AuthController.php` # Authentication controller
│   │   │   │   ├── `HomeController.php` # Frontend controller
│   │   │   │   └── `ImageController.php` # Image handling controller
│   │   │   ├── `footer.php`        # Frontend page footer
│   │   │   ├── `functions.php`     # Utility functions
│   │   │   ├── `generate_placeholder.php` # Placeholder image generator
│   │   │   ├── `header.php`        # Frontend page header
│   │   │   ├── `MiddlewareStack.php` # Middleware implementation
│   │   │   └── `Router.php`        # URL routing system
│   │   ├── `uploads/`              # User uploaded content
│   │   │   ├── `companies/`        # Company logo images
│   │   │   ├── `logos/`            # Application logo images
│   │   │   └── `placeholders/`     # Generated placeholder images (WebP format)
│   │   ├── `front-controller.php`  # Front controller entry point
│   │   ├── `.htaccess`             # Apache configuration for routing
│   │   └── `index.php`             # Main entry point
├── `config/`                       # Configuration files (moved outside web root for security)
│   ├── `auth_config.php`           # Centralized authentication configuration
│   ├── `database.php`              # Database connection configuration
│   └── `env_loader.php`            # Environment variables loader
├── `src/`                          # Source files for development
│   ├── `fonts/`                    # Original font files
│   │   ├── `Outfit/`               # Outfit variable font
│   │   └── `remixicon/`            # Remix icon font files
│   ├── `input.scss`                # Main SCSS input file
│   ├── `intermediate.css`          # Intermediate CSS (generated)
│   └── `intermediate.css.map`      # Source mapping for CSS
├── `database/`                     # Database schema and migration scripts
│   ├── `migrate_departments.sql`   # Migration script for department management
│   └── `staff_dir.sql`             # Initial database schema and data
├── `documentation/`                # Project documentation
│   ├── `devbook.md`                # Developer documentation
│   ├── `Subdirectory_Deployment_Configuration_Checklist.md` # Subdirectory deployment guide
│   └── `debugging.md`              # Debugging guide
├── `logs/`                         # Application logs
├── `vendor/`                       # Composer dependencies
├── `node_modules/`                 # NPM dependencies
├── `staff_dir_env/`                # Environment variables (outside web root for security)
│   ├── `.env`                      # Environment variables file
│   └── `.env_example`              # Example environment file template
├── `.vscode/`                      # VS Code configuration
│   └── `settings.json`             # Editor settings
├── `package.json`                  # NPM package configuration
├── `package-lock.json`             # NPM package lock file
├── `composer.json`                 # Composer configuration
├── `composer.lock`                 # Composer lock file
├── `tailwind.config.js`            # Tailwind CSS configuration
└── `.gitignore`                    # Git ignore rules
```

## Usage

### Root Deployment
- Access the frontend at: `/index.php`
- Access the admin dashboard at: `/admin/index.php`

### Subdirectory Deployment
- Access the frontend at: `/your-subdirectory/index.php`
- Access the admin dashboard at: `/your-subdirectory/admin/index.php`

All URLs are automatically handled by the routing system based on the `APP_BASE_URI` configuration in `includes/bootstrap.php`.

## Environment Setup

### Security Considerations
This application uses environment variables to secure sensitive data like database credentials and admin passwords. The environment file is stored outside the web root for enhanced security.

### Setting Up Environment Variables
1. Create an `.env` file in the directory `staff_dir_env/`
2. Add the following variables to the file:
```
# Database Configuration
DB_HOST=localhost
DB_USER=your_database_user
DB_PASS=your_database_password
DB_NAME=staff_dir

# Admin Credentials
ADMIN_USERNAME=admin
ADMIN_PASSWORD=your_secure_password

# Application Settings
DEV_MODE=false

# Session and Cookie Configuration
USE_SECURE_COOKIES=true
SESSION_LIFETIME=86400
SESSION_UPDATE_INTERVAL=3600
COOKIE_PATH=/
COOKIE_LIFETIME=2592000
```
3. Make sure the `.env` file has appropriate permissions (readable by the web server but not by other users)

## Routing System

The application uses a modern routing system based on the Front Controller pattern:

- **Front Controller**: All requests are routed through `front-controller.php`, which serves as the central entry point
- **Router**: The `Router` class uses FastRoute to map URLs to controller actions
- **Controllers**: Organized in the `includes/controllers/` directory, handle specific application functionality
- **Middleware**: The `MiddlewareStack` class provides a pipeline for request processing

### Path Handling

The application uses several path-related constants and helper functions:

- `APP_BASE_URI`: Defined in `includes/bootstrap.php`, sets the base URL path for the application
- `BASE_PATH`: Points to the project root directory
- `PUBLIC_PATH`: Points to the public web directory
- `url()`: Helper function to generate URLs with the correct base URI
- `asset()`: Helper function to generate asset URLs

For subdirectory deployment configuration, see the detailed guide in `documentation/Subdirectory_Deployment_Configuration_Checklist.md`.

