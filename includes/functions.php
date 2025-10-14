<?php

function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

function redirect($url)
{
    header("Location: " . $url);
    exit();
}

function setFlashMessage($message, $type = 'info')
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

function formatTimestamp($timestamp)
{
    return date('M j, Y 	g:i A', strtotime($timestamp));
}

function timeAgo($timestamp)
{
    $time = time() - strtotime($timestamp);

    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time / 60) . ' minutes ago';
    if ($time < 86400) return floor($time / 3600) . ' hours ago';
    if ($time < 2592000) return floor($time / 86400) . ' days ago';

    return date('M j, Y', strtotime($timestamp));
}

// Group-related functions

function isGroupOwner($groupId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ? AND user_id = ? AND role = 'owner'");
    $stmt->execute([$groupId, $userId]);
    return $stmt->fetchColumn() > 0;
}

function isGroupMember($groupId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$groupId, $userId]);
    return $stmt->fetchColumn() > 0;
}

function canAccessGroup($groupId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT privacy FROM `groups` WHERE id = ?");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch();
    
    if (!$group) {
        return false;
    }
    
    if ($group['privacy'] === 'public') {
        return true;
    }
    
    return isGroupMember($groupId, $userId);
}

function hasPendingGroupRequest($groupId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_member_requests WHERE group_id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$groupId, $userId]);
    return $stmt->fetchColumn() > 0;
}

function getPendingMemberRequests($groupId)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT gmr.*, u.username, u.display_name, u.avatar 
        FROM group_member_requests gmr
        JOIN users u ON gmr.user_id = u.id
        WHERE gmr.group_id = ? AND gmr.status = 'pending'
        ORDER BY gmr.created_at DESC
    ");
    $stmt->execute([$groupId]);
    return $stmt->fetchAll();
}
