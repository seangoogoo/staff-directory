# Debugging Guide

This guide explains logging in the Staff Directory project, which now uses Monolog for enhanced logging capabilities while maintaining backward compatibility with existing code.

## Overview

While the project still supports the legacy `debug_log()` and `error_log()` functions, they now use Monolog internally for improved logging features:

- Log rotation (prevents large log files)
- Different log levels (DEBUG, INFO, WARNING, ERROR, etc.)
- Structured logging with context
- Different handlers for development and production
- Stack trace information

## Basic Usage (Legacy Style)

The old debug_log function still works as before:

```php
debug_log($data);
debug_log($data, 'My Label');
debug_log($data, 'My Label', false); // Uses var_export
```

## Enhanced Usage (Recommended)

For new code, use Monolog directly for better logging:

```php
global $logger;

// Different log levels
$logger->debug('Detailed information for debugging');
$logger->info('Interesting events');
$logger->warning('Exceptional occurrences that are not errors');
$logger->error('Runtime errors that need to be monitored');
$logger->critical('Critical conditions');

// Logging with context
$logger->info('User logged in', [
    'user_id' => $user->id,
    'ip' => $_SERVER['REMOTE_ADDR']
]);
```

## Log File Locations

- Development: `logs/debug.log` (all levels)
- Production: `logs/app.log` (errors only, rotated every 30 days)

## Migration Notes

1. Existing `debug_log()` calls will continue to work
2. Existing `error_log()` calls will continue to work
3. New code should use the Monolog `$logger` directly
4. All logs now include:
   - Timestamp
   - Log level
   - File and line number where log was called
   - Context information

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
