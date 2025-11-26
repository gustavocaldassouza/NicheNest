<?php

/**
 * AJAX endpoint for admin to flag/unflag posts
 */
error_reporting(0);
ini_set('display_errors', 0);

session_start();
$basePath = dirname(__DIR__);
require_once $basePath . '/includes/config.php';
require_once $basePath . '/includes/functions.php';
require_once $basePath . '/includes/auth.php';

ob_clean();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in']);
    exit;
}

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Admin privileges required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : null;

if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

try {
    // Get current flag status
    $stmt = $pdo->prepare("SELECT id, flagged, title FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }

    $new_flag = $post['flagged'] ? 0 : 1;

    // Update flag status
    $stmt = $pdo->prepare("UPDATE posts SET flagged = ? WHERE id = ?");
    $stmt->execute([$new_flag, $post_id]);

    // Log the moderation action
    $action = $new_flag === 1 ? 'flag_post' : 'unflag_post';
    $moderator_id = getCurrentUserId();

    $stmt = $pdo->prepare("INSERT INTO moderation_logs (moderator_id, action, target_type, target_id, reason) VALUES (?, ?, 'post', ?, ?)");
    $stmt->execute([$moderator_id, $action, $post_id, $reason]);

    // Also log to file
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$moderator_id]);
    $moderator = $stmt->fetch();

    if ($moderator) {
        Logger::logModeration($action, $moderator['username'], 'post', $post_id, $reason);
    }

    $message = $new_flag === 1 ? 'Post flagged successfully' : 'Post unflagged successfully';

    echo json_encode([
        'success' => true,
        'flagged' => (bool)$new_flag,
        'message' => $message
    ]);
} catch (PDOException $e) {
    ob_clean();
    header('Content-Type: application/json');
    Logger::error("Failed to flag/unflag post", ['error' => $e->getMessage(), 'post_id' => $post_id]);
    echo json_encode(['success' => false, 'message' => 'Failed to update post flag status']);
}

ob_end_flush();
