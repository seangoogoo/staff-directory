# Subdirectory Deployment & Routing Refactor Plan

---

## Phased Implementation Checklist

### Phase 1: Preparation
- [ ] Backup current project and database
- [ ] Define desired **base URL** (e.g., `/staff-app/`)
- [ ] Set up a development environment with the app in a subdirectory for testing
- [ ] Install `nikic/fast-route` via Composer

### Phase 2: Routing Foundation
- [ ] Create a **private routing bootstrap file** (e.g., `config/router.php`)
- [ ] In `router.php`, initialize FastRoute and define all routes
- [ ] Refactor `public/index.php` to:
  - [ ] Only load environment/config
  - [ ] Delegate all request dispatching to `config/router.php`
- [ ] Configure `.htaccess` or Nginx to redirect all app requests to `public/index.php`

### Phase 3: URL Management
- [ ] Implement a `baseUrl()` helper function (in a shared config or utility file)
- [ ] Replace all hardcoded URLs in PHP templates with `baseUrl()`
- [ ] Inject `window.APP_BASE_URL` into HTML templates
- [ ] Refactor JavaScript to use `window.APP_BASE_URL` for AJAX and dynamic URLs

### Phase 4: Refactor Includes & Handlers
- [ ] Change all `require`/`include` paths to use absolute filesystem paths
- [ ] Convert existing page scripts (`admin/index.php`, `admin/add.php`, etc.) into **handler functions** or **controller classes**
- [ ] Register these handlers with FastRoute in `config/router.php`
- [ ] Remove direct access to these scripts; all access goes through routing

### Phase 5: Asset & CSS Verification
- [ ] Verify `<link>` and `<script>` tags use `baseUrl()`
- [ ] Confirm CSS font paths resolve correctly in browser
- [ ] Test all images, fonts, and uploads load properly

### Phase 6: Testing & Deployment
- [ ] Test all routes, admin functions, and frontend in subdirectory
- [ ] Check redirects, AJAX, and form submissions
- [ ] Review security (no sensitive files exposed)
- [ ] Deploy to production

---

## Request Handling Architecture

### Centralized Router Location

- **Router logic will be centralized in:**
  `config/router.php` (private, **outside** the public web root)

- **Why?**
  Keeping routing logic private improves security and maintainability.

### How it works:

- **`public/index.php`** (web root, front controller):
  - Loads environment and config
  - Includes `config/router.php`
  - Passes the current HTTP request to the router
  - Outputs the response

- **`config/router.php`** (private):
  - Initializes FastRoute
  - Defines all URL routes and maps them to handler functions or controller classes
  - Dispatches the request
  - Calls the appropriate handler
  - Handles 404, 405, or errors

---

## Projected Directory Structure

```
project-root/
│
├── public/                     # Web root, accessible by browser
│   ├── .htaccess               # Rewrite rules to front controller
│   ├── index.php               # Front controller, minimal logic
│   ├── assets/                 # CSS, JS, images, fonts
│   ├── uploads/                # User uploads
│   └── admin/                  # (Optional) legacy files, to be phased out
│
├── config/                     # Private configuration and routing
│   ├── database.php            # DB config
│   ├── auth_config.php         # Auth config
│   ├── env_loader.php          # Loads .env variables
│   ├── router.php              # **Centralized routing logic**
│   └── helpers.php             # URL helper, utility functions
│
├── src/                        # Source SCSS, fonts, etc.
│
├── vendor/                     # Composer dependencies
│
├── staff_dir_env/              # .env files (private)
│
├── database/                   # SQL schema
│
├── documentation/              # Project docs
│
├── composer.json
├── package.json
└── README.md
```

---

# Deployment in Subdirectory with PHP Routing and Asset Path Management

## Overview

This document outlines a comprehensive plan to refactor the Staff Directory application to support flexible deployment **within any subdirectory** of a web server (e.g., `https://yourdomain.com/staff-app/` or `/subdir1/subdir2/`). The goal is to make this configurable with minimal changes, improving maintainability and portability.

---

## Why Use a Router?

Currently, the application:

- Relies on direct access to individual `.php` files (`/index.php`, `/admin/index.php`, etc.)
- Uses a mix of relative and absolute URLs, assuming deployment at the web root
- Has no centralized URL management

This makes subdirectory deployment difficult and error-prone.

**Solution:** Implement a **front controller** pattern with a routing library (`nikic/FastRoute`) to:

- Centralize request handling
- Abstract URL generation
- Easily adapt to different base paths
- Enable clean URLs and better maintainability

---

## Step-by-Step Migration Plan

### 1. Install FastRoute

Add FastRoute via Composer:

```bash
composer require nikic/fast-route
```

---

### 2. Configure Web Server for Front Controller

**Apache (.htaccess):**

Redirect all requests **within your app's subdirectory** to `public/index.php`, except for existing files:

```apache
RewriteEngine On
RewriteBase /your/subdirectory/   # Adjust to your deployment path

# Don't rewrite existing files or directories
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Rewrite everything else to index.php
RewriteRule ^ index.php [QSA,L]
```

**Nginx:**

```nginx
location /your/subdirectory/ {
    try_files $uri $uri/ /your/subdirectory/index.php?$query_string;
}
```

---

### 3. Implement Front Controller (`public/index.php`)

- Detect or define the **base URL** (e.g., `/staff-app/`)
- Initialize FastRoute dispatcher
- Define all routes, e.g.:

```php
$r->addRoute('GET', '/', 'home_handler');
$r->addRoute('GET', '/admin', 'admin_dashboard_handler');
$r->addRoute('GET', '/admin/add', 'admin_add_handler');
$r->addRoute('POST', '/api/check-duplicate', 'api_check_duplicate_handler');
```

- Dispatch incoming requests and call the appropriate handler
- Handlers can include existing PHP files or be refactored into functions/classes

---

### 4. Create a PHP URL Helper

Implement a function:

```php
function baseUrl(string $path = ''): string {
    $base = rtrim($_ENV['APP_BASE_URL'] ?? '/your/subdirectory', '/');
    return $base . '/' . ltrim($path, '/');
}
```

- Use this helper **everywhere** in PHP/HTML templates:
  - `<a href="<?= baseUrl('/admin') ?>">`
  - `<form action="<?= baseUrl('/admin/add') ?>">`
  - `<link rel="stylesheet" href="<?= baseUrl('/assets/css/styles.css') ?>">`
  - `<script src="<?= baseUrl('/assets/js/main.js') ?>"></script>`
  - `header('Location: ' . baseUrl('/admin'))`

---

### 5. Inject Base URL into JavaScript

In your main layout (e.g., `includes/header.php`), add:

```php
<script>
window.APP_BASE_URL = '<?= rtrim(baseUrl(), '/') ?>';
</script>
```

This exposes the base URL to all JavaScript code.

---

### 6. Refactor JavaScript URL Construction

Update all JavaScript that builds URLs dynamically:

- **Before:**

```js
fetch('/includes/ajax_handlers.php?action=check')
```

- **After:**

```js
fetch(window.APP_BASE_URL + '/api/check-duplicate?action=check')
```

- Same applies to dynamically setting image sources, redirects, etc.

---

### 7. Refactor PHP Includes and URL Generation

- Change all `require`/`include` statements to use **absolute filesystem paths**:

```php
require __DIR__ . '/../config/database.php';
```

- Replace all **hardcoded URLs** in PHP/HTML with the `baseUrl()` helper
- Adapt existing `.php` files to be **handlers** invoked by the router, rather than directly accessed scripts

---

### 8. CSS and Font Paths

- Your SCSS (`src/input.scss`) uses:

```scss
@font-face {
  src: url('../fonts/Outfit/Outfit-VariableFont_wght.ttf');
}
```

- This is **relative to the final CSS file location** (`public/assets/css/`)
- As long as the `<link rel="stylesheet">` uses the correct URL (via `baseUrl()`), **browsers will resolve font paths correctly**
- **No changes needed** inside CSS/SCSS files

---

## Summary

- **Centralizes** all routing and URL management
- **Supports any subdirectory deployment** via configuration
- **Ensures all PHP, JS, and CSS paths work correctly**
- **Improves maintainability** and prepares for future enhancements (e.g., clean URLs, API endpoints)

---

## Benefits

- **Flexible Deployment:** Easily move the app to any subdirectory or domain
- **Cleaner URLs:** Potential for user-friendly URLs without `.php`
- **Security:** Hide internal script structure
- **Maintainability:** Easier to update links and assets in one place
- **Modern Architecture:** Aligns with best practices in PHP development

---

## Next Steps

- Implement the above plan incrementally
- Test thoroughly in a subdirectory environment
- Update documentation as needed
