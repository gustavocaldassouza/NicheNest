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
    redirect('/pages/groups.php');
}

$groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$currentUserId = getCurrentUserId();

if (!$groupId) {
    setFlashMessage('Invalid group ID', 'danger');
    redirect('/pages/groups.php');
}

// Get group details
$stmt = $pdo->prepare("SELECT * FROM `groups` WHERE id = ?");
$stmt->execute([$groupId]);
$group = $stmt->fetch();

if (!$group) {
    setFlashMessage('Group not found', 'danger');
    redirect('/pages/groups.php');
}

// Check if user is already a member
if (isGroupMember($groupId, $currentUserId)) {
    setFlashMessage('You are already a member of this group', 'info');
    redirect('/pages/group_view.php?id=' . $groupId);
}

// Check if there's already a pending request
if (hasPendingGroupRequest($groupId, $currentUserId)) {
    setFlashMessage('You already have a pending join request for this group', 'info');
    redirect('/pages/group_view.php?id=' . $groupId);
}

try {
    if ($group['privacy'] === 'public') {
        // For public groups, add user directly as a member
        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
        $stmt->execute([$groupId, $currentUserId]);
        
        setFlashMessage('You have successfully joined the group!', 'success');
    } else {
        // For private groups, create a join request
        $stmt = $pdo->prepare("INSERT INTO group_member_requests (group_id, user_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$groupId, $currentUserId]);
        
        // Send notification to group owner
        $currentUser = getCurrentUser();
        createNotification(
            $group['owner_id'],
            'admin',
            'New Group Join Request',
            htmlspecialchars($currentUser['display_name'] ?? $currentUser['username']) . ' has requested to join ' . htmlspecialchars($group['name']),
            $groupId
        );
        
        setFlashMessage('Your join request has been sent to the group owner for approval', 'success');
    }
} catch (Exception $e) {
    setFlashMessage('An error occurred while processing your request. Please try again.', 'danger');
}

redirect('/pages/group_view.php?id=' . $groupId);
