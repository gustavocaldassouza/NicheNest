<?php
// Simple health check endpoint
header('Content-Type: application/json');

$status = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'php_version' => PHP_VERSION,
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
];

// Check database connection
try {
    $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
    $dbName = getenv('DB_NAME') ?: 'nichenest';
    $dbUser = getenv('DB_USER') ?: 'nichenest';
    $dbPass = getenv('DB_PASS') ?: 'nichenest123';
    
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_TIMEOUT => 5]
    );
    $status['database'] = 'connected';
    $status['db_host'] = $dbHost;
} catch (PDOException $e) {
    $status['database'] = 'error';
    $status['db_error'] = $e->getMessage();
    $status['db_host'] = $dbHost ?? 'not set';
}

http_response_code($status['database'] === 'connected' ? 200 : 503);
echo json_encode($status, JSON_PRETTY_PRINT);
