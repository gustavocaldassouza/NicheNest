<?php
session_start();
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Clear any buffered output before sending JSON
if (ob_get_length()) ob_clean();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$currentUserId = getCurrentUserId();
$isAdminUser = isAdmin();
$reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : null;

if (!$postId) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

// Verify ownership OR admin privileges
if (!isPostOwner($postId, $currentUserId) && !$isAdminUser) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this post']);
    exit;
}

try {
    // Get group_id before deletion for redirect info
    $stmt = $pdo->prepare("SELECT group_id FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }

    $pdo->beginTransaction();

    // Delete attachments (files and DB records) - CASCADE will handle DB, but we need file cleanup
    deletePostAttachments($postId);

    // Delete post (will cascade to replies, likes, notifications, etc.)
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$postId]);

    $pdo->commit();

    // Log moderation action if admin deleted someone else's post
    if ($isAdminUser && !isPostOwner($postId, $currentUserId)) {
        $stmt = $pdo->prepare("INSERT INTO moderation_logs (moderator_id, action, target_type, target_id, reason) VALUES (?, 'delete_post', 'post', ?, ?)");
        $stmt->execute([$currentUserId, $postId, $reason]);
        
        // Also log to file
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$currentUserId]);
        $moderator = $stmt->fetch();
        
        if ($moderator) {
            Logger::logModeration('delete_post', $moderator['username'], 'post', $postId, $reason);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Post deleted successfully',
        'group_id' => $post['group_id']
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Failed to delete post']);
}
