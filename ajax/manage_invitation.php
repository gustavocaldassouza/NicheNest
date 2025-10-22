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
    redirect('../pages/invitations.php');
}

$invitationId = isset($_POST['invitation_id']) ? (int)$_POST['invitation_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$currentUserId = getCurrentUserId();

// Validate input
if (!$invitationId || !in_array($action, ['accept', 'decline'])) {
    setFlashMessage('Invalid request', 'danger');
    redirect('../pages/invitations.php');
}

// Get invitation details
$stmt = $pdo->prepare("
    SELECT gi.*, g.name as group_name
    FROM group_invitations gi
    JOIN `groups` g ON gi.group_id = g.id
    WHERE gi.id = ? AND gi.invitee_id = ? AND gi.status = 'pending'
");
$stmt->execute([$invitationId, $currentUserId]);
$invitation = $stmt->fetch();

if (!$invitation) {
    setFlashMessage('Invitation not found or already processed', 'danger');
    redirect('../pages/invitations.php');
}

try {
    if ($action === 'accept') {
        // Update invitation status
        $stmt = $pdo->prepare("UPDATE group_invitations SET status = 'accepted', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$invitationId]);
        
        // Add user to group as member
        $stmt = $pdo->prepare("
            INSERT INTO group_members (group_id, user_id, role, joined_at)
            VALUES (?, ?, 'member', NOW())
            ON DUPLICATE KEY UPDATE role = 'member'
        ");
        $stmt->execute([$invitation['group_id'], $currentUserId]);
        
        // Create notification for inviter
        $currentUser = getCurrentUser();
        $notificationTitle = "Invitation Accepted";
        $notificationMessage = $currentUser['username'] . " accepted your invitation to join " . $invitation['group_name'];
        createNotification($invitation['inviter_id'], 'admin', $notificationTitle, $notificationMessage, $invitation['group_id']);
        
        setFlashMessage('You have joined the group successfully!', 'success');
        redirect('../pages/group_view.php?id=' . $invitation['group_id']);
    } else {
        // Decline invitation
        $stmt = $pdo->prepare("UPDATE group_invitations SET status = 'declined', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$invitationId]);
        
        setFlashMessage('Invitation declined', 'info');
        redirect('../pages/invitations.php');
    }
} catch (PDOException $e) {
    error_log("Failed to manage invitation: " . $e->getMessage());
    setFlashMessage('Failed to process invitation', 'danger');
    redirect('../pages/invitations.php');
}
