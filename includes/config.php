<?php
// Prevent double-include
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);

// Parse ClearDB URL if present (Heroku)
if (getenv('JAWSDB_URL')) {
    $url = parse_url(getenv('JAWSDB_URL'));
    define('DB_HOST', $url['host']);
    define('DB_NAME', ltrim($url['path'], '/'));
    define('DB_USER', $url['user']);
    define('DB_PASS', $url['pass']);
} else {
    // Local development defaults
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
    define('DB_NAME', getenv('DB_NAME') ?: 'nichenest');
    define('DB_USER', getenv('DB_USER') ?: 'nichenest');
    define('DB_PASS', getenv('DB_PASS') ?: 'nichenest123');
}

define('APP_NAME', getenv('APP_NAME') ?: 'NicheNest');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/nichenest/public');
define('UPLOAD_PATH', getenv('UPLOAD_PATH') ?: 'uploads/');

// Logging configuration
define('LOG_ENABLED', getenv('LOG_ENABLED') !== 'false'); // Enabled by default
define('LOG_LEVEL', getenv('LOG_LEVEL') ?: 'INFO'); // DEBUG, INFO, WARNING, ERROR, CRITICAL
define('LOG_DIRECTORY', getenv('LOG_DIRECTORY') ?: __DIR__ . '/../logs');
define('LOG_MAX_FILE_SIZE', getenv('LOG_MAX_FILE_SIZE') ?: 5242880); // 5MB
define('LOG_MAX_FILES', getenv('LOG_MAX_FILES') ?: 5);

// Initialize Logger
require_once __DIR__ . '/logger.php';
Logger::init([
    'enabled' => LOG_ENABLED,
    'log_level' => LOG_LEVEL,
    'log_directory' => LOG_DIRECTORY,
    'max_file_size' => LOG_MAX_FILE_SIZE,
    'max_files' => LOG_MAX_FILES
]);

// Database connection with retry logic for container startup
$pdo = null;
$maxRetries = 10;
$retryDelay = 3; // seconds

for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        Logger::info("Database connection established successfully", [
            'host' => DB_HOST,
            'database' => DB_NAME,
            'attempt' => $attempt
        ]);
        break;
    } catch (PDOException $e) {
        if ($attempt === $maxRetries) {
            Logger::critical("Database connection failed after $maxRetries attempts", [
                'error' => $e->getMessage(),
                'host' => DB_HOST,
                'database' => DB_NAME
            ]);
            die("Database connection failed: " . $e->getMessage());
        }
        Logger::warning("Database connection attempt $attempt failed, retrying in {$retryDelay}s...", [
            'error' => $e->getMessage()
        ]);
        sleep($retryDelay);
    }
}

ob_start();
