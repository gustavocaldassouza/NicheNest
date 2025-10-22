<?php

function createNotification($user_id, $type, $title, $message, $related_id = null)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, related_id, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $type, $title, $message, $related_id]);
        
        Logger::debug("Notification created", [
            'user_id' => $user_id,
            'type' => $type,
            'notification_id' => $pdo->lastInsertId()
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        Logger::error("Failed to create notification", [
            'error' => $e->getMessage(),
            'user_id' => $user_id,
            'type' => $type
        ]);
        return false;
    }
}

function getUnreadNotificationCount($user_id)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return (int)$result['count'];
    } catch (PDOException $e) {
        Logger::error("Failed to get unread notification count", [
            'error' => $e->getMessage(),
            'user_id' => $user_id
        ]);
        return 0;
    }
}

function getLatestNotifications($user_id, $limit = 10)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT n.*, p.title as post_title, p.id as post_id
            FROM notifications n
            LEFT JOIN posts p ON n.related_id = p.id AND n.type IN ('reply', 'like')
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        Logger::error("Failed to get latest notifications", [
            'error' => $e->getMessage(),
            'user_id' => $user_id
        ]);
        return [];
    }
}

function markNotificationAsRead($notification_id, $user_id)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notification_id, $user_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        Logger::error("Failed to mark notification as read", [
            'error' => $e->getMessage(),
            'notification_id' => $notification_id,
            'user_id' => $user_id
        ]);
        return false;
    }
}

function markAllNotificationsAsRead($user_id)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE user_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$user_id]);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        Logger::error("Failed to mark all notifications as read", [
            'error' => $e->getMessage(),
            'user_id' => $user_id
        ]);
        return false;
    }
}

function createLikeNotification($post_id, $liker_user_id)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.title, p.user_id as post_author_id, u.username as liker_username
            FROM posts p
            JOIN users u ON u.id = ?
            WHERE p.id = ?
        ");
        $stmt->execute([$liker_user_id, $post_id]);
        $data = $stmt->fetch();

        if (!$data || $data['post_author_id'] == $liker_user_id) {
            return false;
        }

        $title = "New Like";
        $message = $data['liker_username'] . " liked your post: " . $data['title'];

        return createNotification(
            $data['post_author_id'],
            'like',
            $title,
            $message,
            $post_id
        );
    } catch (PDOException $e) {
        Logger::error("Failed to create like notification", [
            'error' => $e->getMessage(),
            'post_id' => $post_id,
            'liker_user_id' => $liker_user_id
        ]);
        return false;
    }
}

function createReplyNotification($post_id, $reply_id, $replier_user_id)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.title, p.user_id as post_author_id, u.username as replier_username
            FROM posts p
            JOIN users u ON u.id = ?
            WHERE p.id = ?
        ");
        $stmt->execute([$replier_user_id, $post_id]);
        $data = $stmt->fetch();

        if (!$data || $data['post_author_id'] == $replier_user_id) {
            return false;
        }

        $title = "New Reply";
        $message = $data['replier_username'] . " replied to your post: " . $data['title'];

        return createNotification(
            $data['post_author_id'],
            'reply',
            $title,
            $message,
            $post_id
        );
    } catch (PDOException $e) {
        Logger::error("Failed to create reply notification", [
            'error' => $e->getMessage(),
            'post_id' => $post_id,
            'reply_id' => $reply_id,
            'replier_user_id' => $replier_user_id
        ]);
        return false;
    }
}

function getNotificationIconHTML($user_id)
{
    $unreadCount = getUnreadNotificationCount($user_id);
    $badgeHTML = $unreadCount > 0 ?
        '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">' . $unreadCount . '</span>' : '';

    return '
        <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-bell"></i>
            ' . $badgeHTML . '
        </a>
    ';
}
