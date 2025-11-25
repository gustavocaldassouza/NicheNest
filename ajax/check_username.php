<?php
error_reporting(0);
session_start();
chdir(dirname(__DIR__));
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$username = sanitizeInput($_POST['username'] ?? '');

if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Username is required']);
    exit;
}

// Basic validation
if (strlen($username) < 3) {
    echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores']);
    exit;
}

try {
    // Check if username exists
    // If user is logged in, exclude their own ID from the check
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, getCurrentUserId()]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
    }
    
    $exists = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'available' => !$exists,
        'username' => $username
    ]);
    
} catch (PDOException $e) {
    Logger::error("Error checking username availability", [
        'error' => $e->getMessage(),
        'username' => $username
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error checking username availability'
    ]);
}
