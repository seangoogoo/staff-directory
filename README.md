# Staff Directory Application -- Version 1.2.5

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

### Frontend Display
- Grid view of staff members
- Search and filter functionality
- Sorting options
- Responsive design for desktop and mobile

## Tech Stack
- Frontend: HTML, Tailwind, Vanilla JavaScript
- Backend: PHP 7.4, MySQL
- Image Processing: Intervention Image 2.7 (WebP support)
- Image Storage: Local upload folder

## Installation
1. Clone the repository
2. Import the database schema from `database/staff_dir.sql`
3. Configure database connection in `config/database.php`
4. Set up .env file for the authentication of the admin area
5. Ensure the uploads directory is writable by the web server

## Folder tree
The application follows a secure directory structure:

```
├── `public/`                       # Web accessible files (the web root)
│   ├── `admin/`                    # Admin interface files
│   │   ├── `auth/`                 # Authentication system
│   │   │   ├── `auth_config.php`   # Centralized auth configuration
│   │   │   ├── `auth.php`          # Authentication utility functions
│   │   │   ├── `check_login.php`   # Login validation
│   │   │   ├── `login-modal.php`   # Login modal template
│   │   │   ├── `login.php`         # Login form page
│   │   │   └── `logout.php`        # Logout functionality
│   │   ├── `add.php`               # Add new staff member
│   │   ├── `companies.php`         # Company management interface
│   │   ├── `departments.php`       # Department management interface
│   │   ├── `edit.php`              # Edit existing staff member
│   │   ├── `index.php`             # Admin dashboard page
│   │   └── `settings.php`          # Application settings & placeholder image configuration
│   ├── `api/`                      # API endpoints for the application
│   ├── `assets/`                   # CSS, JavaScript, images and other assets
│   │   ├── `css/`                  # Compiled CSS files
│   │   ├── `fonts/`                # Font files
│   │   │   ├── `Outfit/`           # Outfit font files
│   │   │   └── `remixicon/`        # Remix icon font files
│   │   ├── `images/`               # Image assets
│   │   ├── `js/`                   # JavaScript files
│   │   │   ├── `admin-filters.js`  # Admin-specific filtering functionality
│   │   │   ├── `filter-core.js`    # Core filtering logic shared between admin/frontend
│   │   │   ├── `frontend-filters.js` # Frontend-specific filtering functionality
│   │   │   └── `main.js`           # Main application JavaScript
│   │   └── `vendor/`               # Third-party libraries
│   ├── `config/`                   # Configuration files accessed by public pages
│   │   ├── `database.php`          # Database connection configuration
│   │   └── `env_loader.php`        # Environment variables loader
│   ├── `includes/`                 # PHP includes for the public pages
│   │   ├── `admin_footer.php`      # Admin page footer
│   │   ├── `admin_header.php`      # Admin page header
│   │   ├── `ajax_handlers.php`     # AJAX request handlers
│   │   ├── `check_duplicate.php`   # AJAX endpoint for staff duplicate detection
│   │   ├── `footer.php`            # Frontend page footer
│   │   ├── `functions.php`         # Utility functions
│   │   ├── `generate_placeholder.php` # Placeholder image generator
│   │   └── `header.php`            # Frontend page header
│   ├── `uploads/`                  # User uploaded content
│   │   ├── `companies/`            # Company logo images
│   │   ├── `logos/`                # Application logo images
│   │   └── `placeholders/`         # Generated placeholder images (WebP format)
│   └── `index.php`                 # Main entry point
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
│   └── `devbook.md`                # Developer documentation
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
- Access the frontend at: `/index.php`
- Access the admin dashboard at: `/admin/index.php`

## Environment Setup

### Security Considerations
This application uses environment variables to secure sensitive data like database credentials and admin passwords. The environment file is stored outside the web root for enhanced security.

### Setting Up Environment Variables
1. Create an `.env` file in the directory `../env/`
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
```
3. Make sure the `.env` file has appropriate permissions (readable by the web server but not by other users)

