# NicheNest Logging System

This document describes the comprehensive logging system implemented in NicheNest.

## Overview

The logging system provides centralized, structured logging for all application events, including:
- Authentication events (login, logout, failed attempts)
- User actions (registration, post creation, group operations)
- Moderation actions (user suspension, post flagging/deletion)
- Database errors
- Security events
- System errors and warnings

## Architecture

### Core Components

1. **Logger Class** (`includes/logger.php`)
   - Main logging utility with multiple log levels
   - File-based logging with automatic rotation
   - Structured context data support
   - Security protections for log directory

2. **Log Files** (`logs/` directory)
   - Organized by log level and date
   - Automatic rotation when size limit reached
   - Protected from web access via `.htaccess`

3. **Admin Interface** (`pages/logs.php`)
   - View and manage log files
   - Real-time log viewing
   - Cleanup old logs
   - Log statistics dashboard

## Log Levels

The system supports five log levels (ordered by severity):

- **DEBUG**: Detailed diagnostic information for debugging
- **INFO**: General informational messages (default)
- **WARNING**: Warning messages for potentially problematic situations
- **ERROR**: Error conditions that don't stop execution
- **CRITICAL**: Critical conditions that may cause system failure

## Configuration

Configure logging via environment variables or in `includes/config.php`:

```php
LOG_ENABLED=true              # Enable/disable logging
LOG_LEVEL=INFO                # Minimum log level to record
LOG_DIRECTORY=logs            # Directory for log files
LOG_MAX_FILE_SIZE=5242880     # Max file size before rotation (bytes)
LOG_MAX_FILES=5               # Number of rotated files to keep
```

## Usage

### Basic Logging

```php
// Log at different levels
Logger::debug("Detailed debug info", ['user_id' => 123]);
Logger::info("Something happened", ['action' => 'create_post']);
Logger::warning("Potential issue", ['reason' => 'rate_limit']);
Logger::error("An error occurred", ['error' => $e->getMessage()]);
Logger::critical("Critical failure", ['system' => 'database']);
```

### Specialized Logging Functions

```php
// Authentication events
Logger::logAuth('login', 'username', true);  // Successful login
Logger::logAuth('login', 'username', false, ['reason' => 'invalid_password']);

// Moderation actions
Logger::logModeration('suspend_user', 'admin_username', 'user', 123, 'spam');

// User actions
Logger::logUserAction('create_post', $userId, ['post_id' => 456]);

// Database errors
Logger::logDatabaseError($query, $error, $params);

// Security events
Logger::logSecurity('suspicious_activity', Logger::WARNING, ['details' => 'info']);
```

## Log File Structure

### Naming Convention

- `app-YYYY-MM-DD.log` - General application logs (INFO level)
- `error-YYYY-MM-DD.log` - Error and critical logs
- `warning-YYYY-MM-DD.log` - Warning logs
- `debug-YYYY-MM-DD.log` - Debug logs

### Log Entry Format

```
[YYYY-MM-DD HH:MM:SS] [LEVEL] Message | Context: {"key":"value"}
```

Example:
```
[2025-10-20 16:30:45] [INFO] Authentication: login | User: john_doe | Status: SUCCESS | Context: {"ip":"192.168.1.1","user_agent":"Mozilla/5.0..."}
```

## Log Rotation

Logs are automatically rotated when they reach the configured size limit:

1. Current log renamed to `filename.log.1`
2. Previous backups shifted up (`.1` → `.2`, `.2` → `.3`, etc.)
3. Oldest backup deleted if max files limit reached
4. New empty log file created

## Admin Interface

Access the log viewer at `/pages/logs.php` (admin only).

Features:
- **View Logs**: Browse and view log files with configurable line count (50, 100, 500 lines)
- **Statistics**: See total files, combined size, and logging status
- **Cleanup**: Delete logs older than specified days (1-365)
- **Security**: Logs are only accessible to admin users

## Security Features

1. **Access Control**: `.htaccess` denies direct web access to log directory
2. **Path Traversal Protection**: Log viewer validates file paths
3. **Admin Only**: Log management restricted to admin role
4. **Sensitive Data**: Passwords and sensitive data are never logged
5. **IP Tracking**: Security events include IP address and user agent

## Integration Points

The logging system is integrated into:

1. **Authentication** (`includes/auth.php`)
   - Login/logout events
   - Failed authentication attempts
   - Unauthorized access attempts

2. **User Actions** (`pages/register.php`, `pages/login.php`)
   - User registration
   - Profile updates
   - Password changes

3. **Moderation** (`pages/admin.php`)
   - User suspensions/activations
   - Post flagging/unflagging
   - Content deletion

4. **Notifications** (`includes/notifications.php`)
   - Notification creation failures
   - Database errors

5. **AJAX Endpoints** (`ajax/*.php`)
   - Group operations
   - Post/reply submissions
   - Like actions

6. **Database** (`includes/config.php`)
   - Connection success/failure
   - Query errors (via exception handlers)

## Best Practices

### What to Log

✅ **DO Log:**
- Authentication events (success and failure)
- User actions (registration, posts, groups)
- Moderation actions
- Security events
- Database errors
- API errors
- System errors

❌ **DON'T Log:**
- Passwords or password hashes
- Session tokens
- Credit card numbers
- Private messages content
- Personal identification numbers

### Log Level Guidelines

- **DEBUG**: Use for development debugging, disabled in production
- **INFO**: Normal operations, user actions, successful transactions
- **WARNING**: Recoverable errors, unusual but handled situations
- **ERROR**: Errors that prevent specific operations but not system-wide
- **CRITICAL**: System failures, data corruption, security breaches

### Context Data

Always include relevant context:
```php
Logger::info("Post created", [
    'user_id' => $userId,
    'post_id' => $postId,
    'group_id' => $groupId,
    'title' => $title
]);
```

## Maintenance

### Regular Tasks

1. **Monitor Log Size**: Check log directory size regularly
2. **Review Errors**: Periodically review ERROR and CRITICAL logs
3. **Clean Old Logs**: Use admin interface to delete logs older than 30-90 days
4. **Adjust Log Level**: Set to INFO in production, DEBUG only for troubleshooting

### Troubleshooting

**Logs not being created:**
- Check `LOG_ENABLED` is true
- Verify `logs/` directory exists and is writable
- Check file permissions (should be 755 for directory, 644 for files)

**Log files too large:**
- Reduce `LOG_MAX_FILE_SIZE`
- Lower `LOG_LEVEL` (e.g., WARNING instead of INFO)
- Clean up old logs more frequently

**Performance issues:**
- Disable DEBUG logging in production
- Increase rotation size to reduce file operations
- Consider external logging service for high-traffic sites

## Future Enhancements

Potential improvements for the logging system:

1. Log to database for searchable history
2. Email alerts for CRITICAL level logs
3. Integration with external services (Sentry, Papertrail, etc.)
4. Log filtering and search in admin interface
5. Real-time log streaming
6. Log aggregation for multiple servers
7. Performance metrics and analytics

## API Reference

### Logger Methods

```php
// Basic logging
Logger::debug($message, $context = [])
Logger::info($message, $context = [])
Logger::warning($message, $context = [])
Logger::error($message, $context = [])
Logger::critical($message, $context = [])

// Specialized logging
Logger::logAuth($action, $username, $success, $details = [])
Logger::logModeration($action, $moderator, $target_type, $target_id, $reason = null)
Logger::logUserAction($action, $userId, $details = [])
Logger::logDatabaseError($query, $error, $params = [])
Logger::logSecurity($event, $severity, $details = [])

// Log management
Logger::getLogFiles()
Logger::readLogFile($filename, $lines = 100)
Logger::clearOldLogs($daysOld = 30)
Logger::getStats()

// Configuration
Logger::init($config = [])
```

---

For questions or issues with the logging system, please open an issue on GitHub or contact the development team.
