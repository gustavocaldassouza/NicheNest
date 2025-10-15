<?php
session_start();
$basePath = dirname(__DIR__);
require_once $basePath . '/includes/config.php';
require_once $basePath . '/includes/functions.php';
require_once $basePath . '/includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('Invalid request method', 'danger');
    redirect('../pages/groups.php');
}

$groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$currentUserId = getCurrentUserId();

if (!$groupId) {
    setFlashMessage('Invalid group ID', 'danger');
    redirect('../pages/groups.php');
}

// Check if user is the owner
if (isGroupOwner($groupId, $currentUserId)) {
    setFlashMessage('Group owners cannot leave their own group. Transfer ownership or delete the group instead.', 'danger');
    redirect('../pages/group_view.php?id=' . $groupId);
}

// Check if user is a member
if (!isGroupMember($groupId, $currentUserId)) {
    setFlashMessage('You are not a member of this group', 'danger');
    redirect('../pages/groups.php');
}

try {
    // Remove user from group
    $stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$groupId, $currentUserId]);
    
    setFlashMessage('You have successfully left the group', 'success');
    redirect('../pages/groups.php');
} catch (Exception $e) {
    setFlashMessage('An error occurred while leaving the group. Please try again.', 'danger');
    redirect('../pages/group_view.php?id=' . $groupId);
}
