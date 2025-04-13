# Path Helpers Guide for Subdirectory Deployment

## Overview

This document provides guidelines for handling file paths and URLs in the Staff Directory application to ensure compatibility with subdirectory deployments. Following these practices will allow the application to be easily moved to different subdirectories without breaking links or asset references.

## Why Path Helpers Matter

When an application is deployed in a subdirectory (e.g., `https://example.com/staff-directory/` instead of `https://example.com/`), all internal links and asset references must include the subdirectory prefix. Hardcoding paths can lead to broken links and missing assets when the application is moved to a different subdirectory.

### Common Issues Without Path Helpers

1. **Broken Links**: Links to pages may lead to 404 errors
2. **Missing Assets**: Images, CSS, and JavaScript files may fail to load
3. **Incorrect Form Submissions**: Forms may submit to incorrect URLs
4. **API Endpoint Failures**: AJAX requests may target incorrect endpoints

## Available Path Helpers

The application provides several helper functions to handle paths correctly:

### 1. `url($path)`

Use this function for generating URLs to pages or endpoints within the application.

```php
// Instead of:
<a href="/admin/index.php">Admin</a>

// Use:
<a href="<?php echo url('admin/index.php'); ?>">Admin</a>
```

### 2. `asset($path)`

Use this function for referencing static assets like images, CSS, and JavaScript files.

```php
// Instead of:
<img src="/assets/images/logo.svg">

// Use:
<img src="<?php echo asset('images/logo.svg'); ?>">
```

### 3. JavaScript Access to Base URI

For JavaScript files, use the global `APP_BASE_URI` variable:

```javascript
// Instead of:
fetch('/api/endpoint')

// Use:
fetch(window.APP_BASE_URI + '/api/endpoint')
```

## Best Practices

### 1. Database Storage

When storing paths in the database:

- Store only relative paths without the subdirectory prefix
- Use helper functions when retrieving and displaying these paths

```php
// When storing:
$relativePath = 'uploads/companies/' . $filename;
// Store $relativePath in the database

// When displaying:
<img src="<?php echo url($dbPath); ?>">
```

### 2. Form Actions

Always use the `url()` function for form actions:

```php
<form action="<?php echo url('admin/process.php'); ?>" method="POST">
```

### 3. AJAX Requests

For AJAX requests, use the `APP_BASE_URI` JavaScript variable:

```javascript
fetch(window.APP_BASE_URI + '/admin/api/endpoint', {
    method: 'POST',
    // ...
})
```

### 4. Image Sources

For image sources, use the `asset()` or `url()` function depending on the context:

```php
// For static assets:
<img src="<?php echo asset('images/icon.png'); ?>">

// For user-uploaded content:
<img src="<?php echo url($staff['profile_image']); ?>">
```

## Debugging Path Issues

When debugging path-related issues:

1. **Check Apache Logs**: Look for 404 errors in the Apache logs
2. **Inspect Network Requests**: Use browser developer tools to inspect network requests
3. **Verify Path Construction**: Ensure paths are being constructed correctly with helper functions
4. **Check Database Values**: Verify that paths stored in the database are relative

### Common Error Patterns

- Requests to `/path/to/resource` instead of `/staff-directory/path/to/resource`
- Apache errors like `script '/path/to/public/index.php' not found`
- Missing images or assets in the browser

## Migration Strategy for Existing Code

When updating existing code:

1. Identify hardcoded paths using grep: `grep -r "src=\"/" public/staff-directory/`
2. Replace direct concatenation with helper functions
3. Update database values to store only relative paths
4. Test thoroughly in both root and subdirectory deployments

## Conclusion

Consistently using path helpers throughout the application ensures flexibility for subdirectory deployments. This approach centralizes path configuration and makes the application more maintainable and portable.

Remember: **Never hardcode paths** - always use the provided helper functions.
