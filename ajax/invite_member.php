<?php
session_start();
$basePath = dirname(__DIR__);
require_once $basePath . '/includes/config.php';
require_once $basePath . '/includes/functions.php';
require_once $basePath . '/includes/auth.php';
require_once $basePath . '/includes/notifications.php';

header('Content-Type: application/json');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$inviteeId = isset($_POST['invitee_id']) ? (int)$_POST['invitee_id'] : 0;
$currentUserId = getCurrentUserId();

// Validate input
if (!$groupId || !$inviteeId) {
    echo json_encode(['success' => false, 'message' => 'Invalid group or user']);
    exit;
}

// Check if user is group owner
if (!isGroupOwner($groupId, $currentUserId)) {
    echo json_encode(['success' => false, 'message' => 'Only group owners can invite members']);
    exit;
}

// Get group details
$stmt = $pdo->prepare("SELECT * FROM `groups` WHERE id = ?");
$stmt->execute([$groupId]);
$group = $stmt->fetch();

if (!$group) {
    echo json_encode(['success' => false, 'message' => 'Group not found']);
    exit;
}

// Check if invitee exists
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
$stmt->execute([$inviteeId]);
$invitee = $stmt->fetch();

if (!$invitee) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Check if user is already a member
if (isGroupMember($groupId, $inviteeId)) {
    echo json_encode(['success' => false, 'message' => 'User is already a member of this group']);
    exit;
}

// Check if invitation already exists
if (hasPendingGroupInvitation($groupId, $inviteeId)) {
    echo json_encode(['success' => false, 'message' => 'Invitation already sent to this user']);
    exit;
}

try {
    // Create invitation
    $stmt = $pdo->prepare("
        INSERT INTO group_invitations (group_id, inviter_id, invitee_id, status, created_at)
        VALUES (?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$groupId, $currentUserId, $inviteeId]);
    
    // Create notification
    $currentUser = getCurrentUser();
    $notificationTitle = "Group Invitation";
    $notificationMessage = $currentUser['username'] . " invited you to join the group: " . $group['name'];
    createNotification($inviteeId, 'admin', $notificationTitle, $notificationMessage, $groupId);
    
    Logger::logUserAction('group_invite_sent', $currentUserId, [
        'group_id' => $groupId,
        'group_name' => $group['name'],
        'invitee_id' => $inviteeId,
        'invitee_username' => $invitee['username']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Invitation sent successfully']);
} catch (PDOException $e) {
    Logger::error("Failed to create invitation", [
        'error' => $e->getMessage(),
        'group_id' => $groupId,
        'inviter_id' => $currentUserId,
        'invitee_id' => $inviteeId
    ]);
    echo json_encode(['success' => false, 'message' => 'Failed to send invitation']);
}
