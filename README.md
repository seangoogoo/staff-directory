# Staff Directory Application -- Version 1.2.8

A web-based staff directory application that allows administrators to manage staff members and provides a user-friendly interface for employees to browse the directory.

Live demo available at: [https://staff-directory.jensen-siu.net/](https://staff-directory.jensen-siu.net/)

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
- Multilingual support with English and French translations
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
- Internationalization: Custom i18n system with language files
- Development Tools: BrowserSync for live reloading, Concurrently for parallel task execution

## Installation

### Local Development
1. Clone the repository
2. Import the database schema from `database/staff_dir.sql`
3. Configure database connection in `config/database.php`
4. Set up .env file for the authentication of the admin area
5. Ensure the uploads directory is writable by the web server
6. For subdirectory deployment, follow the detailed instructions in `documentation/Subdirectory_Deployment_Configuration_Checklist.md`

### Remote Server Deployment (FTP)
For deploying to a remote server with only FTP and phpMyAdmin access:
1. Use the clean database schema in `database/staff_dir_clean.sql` (no example data)
2. Follow the detailed instructions in `documentation/FTP_Deployment_Guide.md`
3. Use the checklist in `documentation/Minimal_Directory_Structure.md` to ensure all required files are uploaded

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
│   │   │   │   ├── `breakpoints.js` # Utility for accessing Tailwind breakpoints in JavaScript
│   │   │   │   ├── `filter-core.js` # Core filtering logic shared between admin/frontend
│   │   │   │   ├── `frontend-filters.js` # Frontend-specific filtering functionality
│   │   │   │   ├── `i18n.js`       # Internationalization utilities
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
│   │   │   ├── `LanguageManager.php` # Internationalization system
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
│   ├── `env_loader.php`            # Environment variables loader
│   └── `languages.php`             # Language configuration
├── `src/`                          # Source files for development
│   ├── `build-tools/`              # Build process utilities
│   │   └── `postcss-tailwind-to-css-vars.js` # PostCSS plugin to convert Tailwind breakpoints to CSS variables
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
│   ├── `debugging.md`              # Debugging guide
│   └── `internationalization.md`   # Internationalization documentation
├── `languages/`                    # Translation files
│   ├── `en/`                       # English translations
│   │   ├── `common.php`            # Common translations
│   │   ├── `frontend.php`          # Frontend translations
│   │   └── `admin.php`             # Admin translations
│   └── `fr/`                       # French translations
│       ├── `common.php`            # Common translations
│       ├── `frontend.php`          # Frontend translations
│       └── `admin.php`             # Admin translations
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
├── `postcss.config.js`             # PostCSS configuration
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

# Language Configuration
DEFAULT_LANGUAGE=en

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

## Internationalization System

The application supports multiple languages through a comprehensive internationalization system:

- **Language Files**: Organized by language and context in the `languages` directory
- **Translation Function**: The `__()` function is used throughout the application for text translation
- **Language Selection**: Users can select their preferred language in the admin settings page
- **Language Detection**: The system automatically detects the user's language based on browser settings, cookies, and session
- **Supported Languages**: Currently supports English (en) and French (fr), with the ability to add more languages

For more details about the internationalization system, see the documentation in `documentation/internationalization.md`.

## Tailwind CSS Integration

This application uses Tailwind CSS for styling, integrated with SCSS for additional custom styles.

### Setup and Installation

1. Install dependencies:
   ```bash
   npm install
   ```

2. The project uses the following Tailwind-related packages:
   - `tailwindcss`: Core Tailwind CSS framework
   - `@tailwindcss/forms`: Plugin for form element styling
   - `autoprefixer`: For adding vendor prefixes to CSS

### Build Process

The build process combines SCSS and Tailwind CSS:

1. SCSS files are compiled from `src/input.scss` to an intermediate CSS file
2. Tailwind processes the intermediate file to generate the final CSS
3. Font files are copied from source to the public directory

```bash
# Development mode with hot reloading
npm run dev

# Production build
npm run build
```

### Development Tools

The project uses several tools to enhance the development experience:

- **browser-sync**: Automatically refreshes the browser when files change, providing real-time feedback during development. It's configured to proxy the PHP development server and watch for changes in PHP files and CSS.

- **concurrently**: Allows running multiple commands simultaneously in a single terminal window. In this project, it's used to run the PHP server, SCSS compiler, Tailwind watcher, and browser-sync in parallel during development.

These tools are configured in the `dev` script in `package.json` and work together to create a seamless development experience:

```json
"dev": "concurrently \"mkdir -p public/assets/fonts/Outfit && cp src/fonts/Outfit/Outfit-VariableFont_wght.ttf public/assets/fonts/Outfit/ && mkdir -p public/assets/fonts/remixicon && cp -r src/fonts/remixicon/* public/assets/fonts/remixicon/\" \"php -S localhost:8000 -t public\" \"sass --watch src/input.scss:src/intermediate.css\" \"npx tailwindcss -i ./src/intermediate.css -o ./public/assets/css/styles.css --watch\" \"browser-sync start --proxy localhost:8000 --files 'public/**/*.php, public/assets/css/styles.css' --no-notify\""
```

This script performs the following tasks in parallel:
1. Copies font files to the public directory
2. Starts a PHP development server on port 8000
3. Watches for changes in SCSS files and compiles them to the intermediate CSS
4. Watches for changes in the intermediate CSS and processes it with Tailwind
5. Starts browser-sync to automatically refresh the browser when files change

### Configuration

- **Tailwind Config**: `tailwind.config.js` contains custom configuration:
  - Content paths: Scans PHP files for class usage
  - Custom fonts: Outfit (sans-serif) and Remixicon
  - Custom breakpoints: Including a special 'nav' breakpoint
  - Plugins: @tailwindcss/forms for enhanced form styling

- **SCSS Integration**: `src/input.scss` includes:
  - Tailwind directives (@tailwind base, components, utilities)
  - Custom font declarations
  - Additional custom styles that extend Tailwind

### Usage in Templates

Tailwind utility classes are used directly in HTML/PHP templates:

```html
<div class="flex items-center justify-between p-4 bg-white rounded-lg shadow">
  <!-- Content here -->
</div>
```

### Customization

To customize the Tailwind configuration:

1. Edit `tailwind.config.js` to add custom colors, fonts, or extend existing themes
2. Add custom styles in `src/input.scss` for styles that can't be achieved with utility classes
3. Run the build process to apply changes

### Best Practices

- Use Tailwind utility classes for most styling needs
- Create custom components using @apply in SCSS for reusable patterns
- Keep the build process running during development for immediate feedback
- Use the browser inspector to explore available Tailwind classes

### Troubleshooting

#### Common Issues

1. **CSS changes not appearing:**
   - Ensure the development server is running (`npm run dev`)
   - Check browser console for errors
   - Verify that browser-sync is connected and watching the correct files
   - Try clearing your browser cache

2. **Tailwind classes not being generated:**
   - Make sure the class is used in one of the PHP files scanned by Tailwind
   - Check the `content` path in `tailwind.config.js` to ensure it includes all necessary files
   - Run a production build (`npm run build`) to see if the issue persists

3. **Development server conflicts:**
   - If port 8000 is already in use, modify the port in the `dev` script in `package.json`
   - If browser-sync doesn't connect, check if another instance is already running

4. **Font issues:**
   - Ensure the font files are correctly copied to the public directory
   - Check the font paths in `src/input.scss` to make sure they match the actual file locations

