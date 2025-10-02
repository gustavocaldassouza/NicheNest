<?php
// Disable error reporting to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Clear any output buffer content
ob_clean();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to like posts']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get post ID from request
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$user_id = getCurrentUserId();

if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

try {
    // Check if user already liked this post
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);

    if ($stmt->rowCount() === 0) {
        // Insert new like
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$post_id, $user_id]);
        $liked = true;
    } else {
        // Remove like (toggle behavior)
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        $liked = false;
    }

    // Get updated like count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $likeCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'likeCount' => $likeCount,
        'message' => $liked ? 'Post liked!' : 'Post unliked!'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to update like. Please try again.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
}

// Ensure we end with clean output
ob_end_flush();
