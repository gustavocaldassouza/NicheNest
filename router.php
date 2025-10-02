<?php
// Simple router for PHP built-in server
$requestUri = $_SERVER['REQUEST_URI'];
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'];

// Remove query string and decode URL
$path = urldecode($path);

// Route requests
if ($path === '/' || $path === '/index.php') {
    // Serve the main index page
    chdir(__DIR__);
    require_once 'public/index.php';
} elseif (strpos($path, '/ajax/') === 0) {
    // Route AJAX requests
    $ajaxFile = __DIR__ . $path;
    if (file_exists($ajaxFile)) {
        chdir(__DIR__);
        require_once $ajaxFile;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'AJAX endpoint not found']);
    }
} elseif (strpos($path, '/pages/') === 0) {
    // Route pages requests
    $pageFile = __DIR__ . $path;
    if (file_exists($pageFile)) {
        // Change to the pages directory so relative includes work
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
        // Serve static files
        $mimeType = mime_content_type($filePath);
        header('Content-Type: ' . $mimeType);
        readfile($filePath);
    } else {
        http_response_code(404);
        echo "File not found: " . htmlspecialchars($path);
    }
} else {
    // Default to index
    require_once 'public/index.php';
}
