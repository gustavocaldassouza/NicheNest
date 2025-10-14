<?php
session_start();
$basePath = dirname(__DIR__);
require_once $basePath . '/includes/config.php';
require_once $basePath . '/includes/functions.php';
require_once $basePath . '/includes/auth.php';
require_once $basePath . '/includes/notifications.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('Invalid request method', 'danger');
    redirect('../pages/groups.php');
}

$groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$currentUserId = getCurrentUserId();

if (!$groupId || !$userId) {
    setFlashMessage('Invalid parameters', 'danger');
    redirect('../pages/groups.php');
}

// Verify user is group owner
requireGroupOwner($groupId);

// Check if target user is the owner
if (isGroupOwner($groupId, $userId)) {
    setFlashMessage('Cannot remove the group owner', 'danger');
    redirect('../pages/group_members.php?id=' . $groupId);
}

// Check if target user is a member
if (!isGroupMember($groupId, $userId)) {
    setFlashMessage('User is not a member of this group', 'danger');
    redirect('../pages/group_members.php?id=' . $groupId);
}

try {
    // Get group name for notification
    $stmt = $pdo->prepare("SELECT name FROM `groups` WHERE id = ?");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch();
    
    // Remove user from group
    $stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$groupId, $userId]);
    
    // Send notification to the removed user
    if ($group) {
        createNotification(
            $userId,
            'admin',
            'Removed from Group',
            'You have been removed from ' . htmlspecialchars($group['name']),
            $groupId
        );
    }
    
    setFlashMessage('Member removed successfully', 'success');
} catch (Exception $e) {
    setFlashMessage('An error occurred while removing the member. Please try again.', 'danger');
}

redirect('../pages/group_members.php?id=' . $groupId);
