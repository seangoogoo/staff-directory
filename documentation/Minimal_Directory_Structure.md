# Minimal Directory Structure for Deployment

This document outlines the minimal directory structure required for deploying the Staff Directory application. Use this as a checklist when preparing your files for FTP upload.

## Directory Structure Overview

```
├── private/                      # Private files (outside web root)
│   ├── config/                   # Configuration files
│   │   ├── auth_config.php       # Authentication configuration
│   │   ├── database.php          # Database connection configuration
│   │   └── env_loader.php        # Environment variables loader
│   ├── staff_dir_env/            # Environment variables
│   │   └── .env                  # Environment file (create this manually)
│   ├── logs/                     # Application logs (create this directory)
│   └── vendor/                   # Composer dependencies
│
└── public_html/                  # Web accessible files (web root)
    └── staff-directory/          # Application files
        ├── admin/                # Admin interface files
        │   ├── auth/             # Authentication system
        │   ├── add.php           # Add new staff member
        │   ├── companies.php     # Company management
        │   ├── departments.php   # Department management
        │   ├── edit.php          # Edit staff member
        │   ├── index.php         # Admin dashboard
        │   └── settings.php      # Application settings
        ├── assets/               # CSS, JavaScript, images
        │   ├── css/              # CSS files
        │   ├── fonts/            # Font files
        │   ├── images/           # Image assets
        │   ├── js/               # JavaScript files
        │   └── vendor/           # Third-party libraries
        ├── includes/             # PHP includes
        │   ├── controllers/      # MVC controllers
        │   ├── admin_footer.php  # Admin footer
        │   ├── admin_header.php  # Admin header
        │   ├── bootstrap.php     # Application bootstrap
        │   ├── footer.php        # Frontend footer
        │   ├── functions.php     # Utility functions
        │   ├── header.php        # Frontend header
        │   ├── MiddlewareStack.php # Middleware implementation
        │   └── Router.php        # URL routing system
        ├── uploads/              # User uploaded content (must be writable)
        │   ├── companies/        # Company logos
        │   ├── logos/            # Application logos
        │   └── placeholders/     # Generated placeholders
        ├── front-controller.php  # Front controller
        ├── .htaccess             # Apache configuration
        └── index.php             # Main entry point
```

## Required Files Checklist

### Configuration Files (Private)

- [ ] `config/auth_config.php`
- [ ] `config/database.php`
- [ ] `config/env_loader.php`
- [ ] `staff_dir_env/.env` (create this file manually)

### Core Application Files (Public)

- [ ] `staff-directory/front-controller.php`
- [ ] `staff-directory/index.php`
- [ ] `staff-directory/.htaccess`
- [ ] `staff-directory/includes/bootstrap.php`
- [ ] `staff-directory/includes/Router.php`
- [ ] `staff-directory/includes/MiddlewareStack.php`
- [ ] `staff-directory/includes/functions.php`

### Admin Interface Files

- [ ] `staff-directory/admin/index.php`
- [ ] `staff-directory/admin/add.php`
- [ ] `staff-directory/admin/edit.php`
- [ ] `staff-directory/admin/companies.php`
- [ ] `staff-directory/admin/departments.php`
- [ ] `staff-directory/admin/settings.php`
- [ ] `staff-directory/admin/auth/` (entire directory)

### Controller Files

- [ ] `staff-directory/includes/controllers/AdminController.php`
- [ ] `staff-directory/includes/controllers/AjaxController.php`
- [ ] `staff-directory/includes/controllers/AuthController.php`
- [ ] `staff-directory/includes/controllers/HomeController.php`
- [ ] `staff-directory/includes/controllers/ImageController.php`

### Template Files

- [ ] `staff-directory/includes/header.php`
- [ ] `staff-directory/includes/footer.php`
- [ ] `staff-directory/includes/admin_header.php`
- [ ] `staff-directory/includes/admin_footer.php`

### Asset Files

- [ ] `staff-directory/assets/css/` (entire directory)
- [ ] `staff-directory/assets/js/` (entire directory)
- [ ] `staff-directory/assets/images/` (entire directory)
- [ ] `staff-directory/assets/fonts/` (entire directory)

### Upload Directories (Must be Writable)

- [ ] `staff-directory/uploads/companies/`
- [ ] `staff-directory/uploads/logos/`
- [ ] `staff-directory/uploads/placeholders/`

## Minimum Vendor Dependencies

If you want to minimize the size of the vendor directory, these are the essential packages:

- [ ] `vendor/autoload.php`
- [ ] `vendor/composer/`
- [ ] `vendor/nikic/fast-route/`
- [ ] `vendor/monolog/monolog/`
- [ ] `vendor/psr/log/`
- [ ] `vendor/intervention/image/`

## Important Notes

1. The `uploads` directory and its subdirectories must be writable by the web server
2. The `logs` directory must be writable by the web server
3. The `.env` file must be created manually with your specific configuration
4. The `bootstrap.php` file may need to be modified to match your server's directory structure
