# Staff Directory Application -- Version 1.1

A web-based staff directory application that allows administrators to manage staff members and provides a user-friendly interface for employees to browse the directory.

## Features

### Admin Dashboard
- Add new staff members with details like name, department, job title, email, and profile picture
- Edit existing staff members
- Delete staff members
- Protected by .htaccess authentication

### Frontend Display
- Grid view of staff members
- Search and filter functionality
- Sorting options
- Responsive design for desktop and mobile

## Tech Stack
- Frontend: HTML, SCSS, Vanilla JavaScript
- Backend: PHP 7.4, MySQL
- Image Storage: Local upload folder

## Installation
1. Clone the repository
2. Import the database schema from `database/staff_dir.sql`
3. Configure database connection in `config/database.php`
4. Set up .htaccess authentication for the admin area
5. Ensure the uploads directory is writable by the web server

## Directory Structure
The application follows a secure directory structure:

```
├── `public/`                       # Web accessible files (the web root)
│   ├── `admin/`                    # Admin interface files
│   │   ├── `auth/`                 # Authentication system
│   │   │   ├── `auth.php`          # Authentication utility functions
│   │   │   ├── `check_login.php`   # Login validation
│   │   │   ├── `login-modal.php`   # Login modal template
│   │   │   ├── `login.php`         # Login form page
│   │   │   └── `logout.php`        # Logout functionality
│   │   ├── `add.php`               # Add new staff member
│   │   ├── `edit.php`              # Edit existing staff member
│   │   └── `index.php`             # Admin dashboard page
│   ├── `assets/`                   # CSS, JavaScript, images and other assets
│   │   ├── `css/`                  # Compiled CSS files
│   │   ├── `images/`               # Image assets
│   │   ├── `js/`                   # JavaScript files
│   │   ├── `scss/`                 # SCSS source files (edit these, not CSS directly)
│   │   │   ├── `_common.scss`      # Common styles
│   │   │   ├── `_login-modal.scss` # Login modal styles
│   │   │   ├── `_variables.scss`   # SCSS variables
│   │   │   ├── `admin.scss`        # Admin-specific styles
│   │   │   └── `frontend.scss`     # Frontend styles
│   │   └── `vendor/`               # Third-party libraries
│   ├── `config/`                   # Configuration files accessed by public pages
│   │   ├── `database.php`          # Database connection configuration
│   │   └── `env_loader.php`        # Environment variables loader
│   ├── `includes/`                 # PHP includes for the public pages
│   │   ├── `admin_footer.php`      # Admin page footer
│   │   ├── `admin_header.php`      # Admin page header
│   │   ├── `footer.php`            # Frontend page footer
│   │   ├── `functions.php`         # Utility functions
│   │   └── `header.php`            # Frontend page header
│   ├── `uploads/`                  # User uploaded content
│   └── `index.php`                 # Main entry point
├── `database/`                     # Database schema
│   └── `staff_dir.sql`             # SQL database schema and initial data
├── `documentation/`                # Project documentation
│   └── `devbook.md`                # Developer documentation
└── `env/`                          # Environment variables (outside web root for security)
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

