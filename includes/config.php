<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'nichenest');
define('DB_USER', 'nichenest');
define('DB_PASS', 'nichenest123');

define('APP_NAME', 'NicheNest');
define('APP_URL', 'http://localhost/nichenest/public');
define('UPLOAD_PATH', 'uploads/');

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
    die("Database connection failed: " . $e->getMessage());
}

ob_start();
