# Debugging Guide

This guide explains how to use the `debug_log` function for debugging purposes in the Staff Directory project.

## Overview

The `debug_log` function is a utility function that allows you to log debug information to a file. It's located in `public/includes/functions.php` and provides a flexible way to track variables, objects, and other data during development.

## Usage

### Basic Usage

```php
debug_log($data);
```

### With Label

```php
debug_log($data, 'My Label');
```

### With Custom Format

```php
debug_log($data, 'My Label', false); // Uses var_export instead of print_r
```

## Parameters

1. `$data` (mixed): The data you want to log. Can be any type (string, array, object, etc.)
2. `$label` (string, optional): A label to identify the log entry. Defaults to empty string.
3. `$print_r` (bool, optional): Whether to use `print_r` (true) or `var_export` (false) for formatting. Defaults to true.

## Log File Location

Debug logs are stored in the `logs/debug.log` file in your project root directory. The directory will be created automatically if it doesn't exist.

## Example Usage

### Logging a Simple Variable

```php
$user_id = 123;
debug_log($user_id, 'User ID');
```

### Logging an Array

```php
$staff_data = [
    'name' => 'John Doe',
    'department' => 'IT',
    'role' => 'Developer'
];
debug_log($staff_data, 'Staff Data');
```

### Logging an Object

```php
$staff = new StaffMember();
$staff->setName('Jane Smith');
debug_log($staff, 'Staff Object');
```

### Logging with var_export Format

```php
$config = [
    'debug' => true,
    'environment' => 'development'
];
debug_log($config, 'Configuration', false);
```

## Log Output Format

The log entries will look like this:

```
[2024-03-31 18:00:00] [User ID] 123

[2024-03-31 18:00:01] [Staff Data]
Array
(
    [name] => John Doe
    [department] => IT
    [role] => Developer
)

```

## Best Practices

1. Use descriptive labels to easily identify different log entries
2. Use `print_r` (default) for better readability of arrays and objects
3. Use `var_export` when you need PHP code format output
4. Clean up debug logs before deploying to production
5. Consider adding log rotation for production environments

## Security Considerations

- Debug logs may contain sensitive information
- Ensure the `logs` directory is not publicly accessible
- Consider implementing log rotation to prevent large log files
- Remove or disable debug logging in production environments

## Troubleshooting

If you're not seeing log entries:

1. Check if the `logs` directory exists and has proper write permissions
2. Verify that the web server user has write access to the directory
3. Check if there are any PHP errors preventing the function from executing
4. Ensure the path to the log file is correct for your environment