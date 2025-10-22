<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
$basePath = dirname(__DIR__);
require_once $basePath . '/includes/config.php';
require_once $basePath . '/includes/functions.php';
require_once $basePath . '/includes/auth.php';
require_once $basePath . '/includes/notifications.php';

ob_clean();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to view notifications']);
    exit;
}

$user_id = getCurrentUserId();

try {
    $notifications = getLatestNotifications($user_id, 10);
    $unreadCount = getUnreadNotificationCount($user_id);

    $formattedNotifications = [];
    foreach ($notifications as $notification) {
        $formattedNotifications[] = [
            'id' => $notification['id'],
            'type' => $notification['type'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'is_read' => (bool)$notification['is_read'],
            'created_at' => $notification['created_at'],
            'time_ago' => timeAgo($notification['created_at']),
            'post_id' => $notification['post_id'],
            'post_title' => $notification['post_title']
        ];
    }

    echo json_encode([
        'success' => true,
        'notifications' => $formattedNotifications,
        'unread_count' => $unreadCount
    ]);
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to load notifications']);
}

ob_end_flush();
