<?php
// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'nichenest');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Application configuration
define('APP_NAME', 'NicheNest');
define('APP_URL', 'http://localhost/nichenest/public');
define('UPLOAD_PATH', 'uploads/');

// Database connection using PDO
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
} catch (PDOException $e) {
    // In production, log error instead of displaying
    die("Database connection failed: " . $e->getMessage());
}

// Start output buffering
ob_start();
