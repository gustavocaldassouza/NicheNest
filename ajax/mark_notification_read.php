<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';

ob_clean();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to mark notifications as read']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = getCurrentUserId();
$notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;

if ($notification_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

try {
    $success = markNotificationAsRead($notification_id, $user_id);

    if ($success) {
        $unreadCount = getUnreadNotificationCount($user_id);
        echo json_encode([
            'success' => true,
            'unread_count' => $unreadCount,
            'message' => 'Notification marked as read'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
    }
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'An error occurred while marking notification as read']);
}

ob_end_flush();
