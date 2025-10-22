<?php
// Main entry point for Apache/Heroku
// Mimics the behavior of router.php for production environments
// Note: This file maintains the same routing logic and directory changes
// as router.php to ensure compatibility with the existing application structure

$requestUri = $_SERVER['REQUEST_URI'];
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'];

$path = urldecode($path);

if ($path === '/' || $path === '/index.php') {
    chdir(__DIR__);
    require_once 'public/index.php';
} elseif (strpos($path, '/ajax/') === 0) {
    $ajaxFile = __DIR__ . $path;
    if (file_exists($ajaxFile)) {
        chdir(__DIR__);
        require_once $ajaxFile;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'AJAX endpoint not found']);
    }
} elseif (strpos($path, '/pages/') === 0) {
    $pageFile = __DIR__ . $path;
    if (file_exists($pageFile)) {
        chdir(__DIR__ . '/pages');
        require_once $pageFile;
    } else {
        http_response_code(404);
        echo "Page not found: " . htmlspecialchars($path);
    }
} elseif (strpos($path, '/public/') === 0) {
    // Route public directory requests
    $filePath = __DIR__ . $path;
    if (file_exists($filePath)) {
        // Serve static files with correct MIME types
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'htm' => 'text/html',
        ];

        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        header('Content-Type: ' . $mimeType);
        readfile($filePath);
    } else {
        http_response_code(404);
        echo "File not found: " . htmlspecialchars($path);
    }
} elseif (strpos($path, '/uploads/') === 0 || strpos($path, '/images/') === 0 || strpos($path, '/css/') === 0 || strpos($path, '/js/') === 0) {
    // Route requests that should be in public directory
    $filePath = __DIR__ . '/public' . $path;
    if (file_exists($filePath)) {
        // Serve static files with correct MIME types
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'htm' => 'text/html',
        ];

        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        header('Content-Type: ' . $mimeType);
        readfile($filePath);
    } else {
        http_response_code(404);
        echo "File not found: " . htmlspecialchars($path);
    }
} else {
    require_once 'public/index.php';
}
