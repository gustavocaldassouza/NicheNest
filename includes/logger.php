<?php
/**
 * Logger Utility for NicheNest
 * 
 * Provides centralized logging functionality with multiple log levels,
 * file rotation, and configurable output destinations.
 */

class Logger
{
    // Log levels
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';

    private static $logDirectory = null;
    private static $logLevel = self::INFO;
    private static $maxFileSize = 5242880; // 5MB
    private static $maxFiles = 5;
    private static $enabled = true;

    /**
     * Initialize the logger with configuration
     */
    public static function init($config = [])
    {
        self::$logDirectory = $config['log_directory'] ?? __DIR__ . '/../logs';
        self::$logLevel = $config['log_level'] ?? self::INFO;
        self::$maxFileSize = $config['max_file_size'] ?? 5242880;
        self::$maxFiles = $config['max_files'] ?? 5;
        self::$enabled = $config['enabled'] ?? true;

        // Create log directory if it doesn't exist
        if (!is_dir(self::$logDirectory)) {
            mkdir(self::$logDirectory, 0755, true);
        }

        // Create .htaccess to protect log directory
        $htaccessPath = self::$logDirectory . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Deny from all\n");
        }
    }

    /**
     * Log a debug message
     */
    public static function debug($message, $context = [])
    {
        self::log(self::DEBUG, $message, $context);
    }

    /**
     * Log an info message
     */
    public static function info($message, $context = [])
    {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Log a warning message
     */
    public static function warning($message, $context = [])
    {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Log an error message
     */
    public static function error($message, $context = [])
    {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Log a critical message
     */
    public static function critical($message, $context = [])
    {
        self::log(self::CRITICAL, $message, $context);
    }

    /**
     * Main logging function
     */
    private static function log($level, $message, $context = [])
    {
        if (!self::$enabled) {
            return;
        }

        // Check if this log level should be recorded
        if (!self::shouldLog($level)) {
            return;
        }

        // Prepare log entry
        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logEntry = sprintf("[%s] [%s] %s%s\n", $timestamp, $level, $message, $contextString);

        // Determine log file
        $logFile = self::getLogFile($level);

        // Check file size and rotate if necessary
        self::rotateLogIfNeeded($logFile);

        // Write to log file
        try {
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // If logging fails, fall back to PHP error_log
            error_log("Logger failed to write to file: " . $e->getMessage());
            error_log($logEntry);
        }
    }

    /**
     * Log authentication events
     */
    public static function logAuth($action, $username, $success = true, $details = [])
    {
        $message = sprintf(
            "Authentication: %s | User: %s | Status: %s",
            $action,
            $username,
            $success ? 'SUCCESS' : 'FAILED'
        );
        
        $context = array_merge($details, [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        if ($success) {
            self::info($message, $context);
        } else {
            self::warning($message, $context);
        }
    }

    /**
     * Log moderation actions
     */
    public static function logModeration($action, $moderator, $target_type, $target_id, $reason = null)
    {
        $message = sprintf(
            "Moderation: %s | Moderator: %s | Target: %s #%d",
            $action,
            $moderator,
            $target_type,
            $target_id
        );

        $context = [
            'action' => $action,
            'moderator' => $moderator,
            'target_type' => $target_type,
            'target_id' => $target_id,
            'reason' => $reason
        ];

        self::info($message, $context);
    }

    /**
     * Log user actions
     */
    public static function logUserAction($action, $userId, $details = [])
    {
        $message = sprintf("User Action: %s | User ID: %d", $action, $userId);
        
        $context = array_merge($details, [
            'user_id' => $userId,
            'action' => $action
        ]);

        self::info($message, $context);
    }

    /**
     * Log database errors
     */
    public static function logDatabaseError($query, $error, $params = [])
    {
        $message = sprintf("Database Error: %s", $error);
        
        $context = [
            'query' => $query,
            'error' => $error,
            'params' => $params
        ];

        self::error($message, $context);
    }

    /**
     * Log security events
     */
    public static function logSecurity($event, $severity = self::WARNING, $details = [])
    {
        $message = sprintf("Security Event: %s", $event);
        
        $context = array_merge($details, [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);

        self::log($severity, $message, $context);
    }

    /**
     * Check if a log level should be recorded based on configuration
     */
    private static function shouldLog($level)
    {
        $levels = [
            self::DEBUG => 0,
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
            self::CRITICAL => 4
        ];

        $currentLevelValue = $levels[self::$logLevel] ?? 1;
        $messageLevelValue = $levels[$level] ?? 1;

        return $messageLevelValue >= $currentLevelValue;
    }

    /**
     * Get the log file path for a specific level
     */
    private static function getLogFile($level)
    {
        // Ensure log directory is set
        if (self::$logDirectory === null) {
            self::$logDirectory = __DIR__ . '/../logs';
        }
        
        $date = date('Y-m-d');
        
        switch ($level) {
            case self::ERROR:
            case self::CRITICAL:
                return self::$logDirectory . "/error-{$date}.log";
            case self::WARNING:
                return self::$logDirectory . "/warning-{$date}.log";
            case self::DEBUG:
                return self::$logDirectory . "/debug-{$date}.log";
            default:
                return self::$logDirectory . "/app-{$date}.log";
        }
    }

    /**
     * Rotate log file if it exceeds max size
     */
    private static function rotateLogIfNeeded($logFile)
    {
        if (!file_exists($logFile)) {
            return;
        }

        $fileSize = filesize($logFile);
        if ($fileSize < self::$maxFileSize) {
            return;
        }

        // Rotate existing backups (in reverse order)
        for ($i = self::$maxFiles - 2; $i >= 0; $i--) {
            $oldFile = $i === 0 ? $logFile : $logFile . '.' . $i;
            $newFile = $logFile . '.' . ($i + 1);
            
            if (file_exists($oldFile)) {
                if ($i === self::$maxFiles - 2) {
                    // Delete oldest backup if we're at max
                    if (file_exists($logFile . '.' . (self::$maxFiles - 1))) {
                        unlink($logFile . '.' . (self::$maxFiles - 1));
                    }
                }
                rename($oldFile, $newFile);
            }
        }
    }

    /**
     * Get log files list
     */
    public static function getLogFiles()
    {
        if (!is_dir(self::$logDirectory)) {
            return [];
        }

        $files = glob(self::$logDirectory . '/*.log*');
        return array_map('basename', $files);
    }

    /**
     * Read log file contents (with optional line limit)
     */
    public static function readLogFile($filename, $lines = 100)
    {
        $filepath = self::$logDirectory . '/' . basename($filename);
        
        if (!file_exists($filepath)) {
            return false;
        }

        // Security check - ensure file is in log directory
        $realpath = realpath($filepath);
        $logDirRealpath = realpath(self::$logDirectory);
        
        if (!$realpath || !$logDirRealpath || substr($realpath, 0, strlen($logDirRealpath)) !== $logDirRealpath) {
            return false; // Path traversal attempt
        }

        // Read last N lines
        try {
            $file = new SplFileObject($filepath);
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();
            
            $startLine = max(0, $totalLines - $lines);
            $file->seek($startLine);
            
            $content = [];
            while (!$file->eof()) {
                $line = $file->current();
                if (!empty(trim($line))) {
                    $content[] = $line;
                }
                $file->next();
            }
            
            return implode('', $content);
        } catch (Exception $e) {
            // Log file became inaccessible or other error
            return false;
        }
    }

    /**
     * Clear old log files (older than X days)
     */
    public static function clearOldLogs($daysOld = 30)
    {
        if (!is_dir(self::$logDirectory)) {
            return 0;
        }

        $files = glob(self::$logDirectory . '/*.log*');
        $count = 0;
        $threshold = time() - ($daysOld * 86400);

        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get logger statistics
     */
    public static function getStats()
    {
        if (!is_dir(self::$logDirectory)) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'files' => []
            ];
        }

        $files = glob(self::$logDirectory . '/*.log*');
        $totalSize = 0;
        $fileStats = [];

        foreach ($files as $file) {
            $size = filesize($file);
            $totalSize += $size;
            $fileStats[] = [
                'name' => basename($file),
                'size' => $size,
                'modified' => filemtime($file)
            ];
        }

        return [
            'total_files' => count($files),
            'total_size' => $totalSize,
            'files' => $fileStats
        ];
    }
}

// Note: Logger will be initialized in config.php with proper configuration
