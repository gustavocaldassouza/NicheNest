<?php
session_start();

// Determine the correct path based on how the script is being called
$basePath = dirname(__DIR__);
require_once $basePath . '/includes/config.php';
require_once $basePath . '/includes/functions.php';
require_once $basePath . '/includes/auth.php';
require_once $basePath . '/includes/notifications.php';

header('Content-Type: application/json');


if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to reply']);
    exit;
}
if (function_exists('isCurrentUserSuspended') ? isCurrentUserSuspended() : false) {
    echo json_encode(['success' => false, 'message' => 'Your account is suspended. You cannot reply.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$post_id = (int)($_POST['post_id'] ?? 0);
$content = sanitizeInput($_POST['reply_content'] ?? '');

if (empty($post_id)) {
    echo json_encode(['success' => false, 'message' => 'Post ID is required']);
    exit;
}

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Reply content is required']);
    exit;
}

try {
    // Check if post exists
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }

    // Insert reply
    $stmt = $pdo->prepare("INSERT INTO replies (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$post_id, getCurrentUserId(), $content]);
    $reply_id = $pdo->lastInsertId();

    // Update reply count
    $stmt = $pdo->prepare("UPDATE posts SET replies_count = replies_count + 1 WHERE id = ?");
    $stmt->execute([$post_id]);

    // Create notification
    createReplyNotification($post_id, $reply_id, getCurrentUserId());

    // Get the new reply data for response
    $stmt = $pdo->prepare("
        SELECT r.*, u.username, u.display_name 
        FROM replies r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$reply_id]);
    $reply = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get updated reply count
    $stmt = $pdo->prepare("SELECT replies_count FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $replies_count = $stmt->fetch(PDO::FETCH_ASSOC)['replies_count'];

    echo json_encode([
        'success' => true,
        'message' => 'Reply added successfully!',
        'reply' => [
            'id' => $reply['id'],
            'content' => $reply['content'],
            'author' => $reply['display_name'] ?? $reply['username'],
            'created_at' => $reply['created_at'],
            'time_ago' => timeAgo($reply['created_at'])
        ],
        'replies_count' => $replies_count
    ]);
} catch (PDOException $e) {
    error_log("Database error in submit_reply.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to add reply. Please try again.']);
}
