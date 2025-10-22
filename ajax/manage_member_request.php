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

$requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;

if (!$requestId || !in_array($action, ['approve', 'deny']) || !$groupId) {
    setFlashMessage('Invalid request parameters', 'danger');
    redirect('../pages/groups.php');
}

// Verify user is group owner
requireGroupOwner($groupId);

// Get the request details
$stmt = $pdo->prepare("
    SELECT gmr.*, g.name as group_name, u.username, u.display_name
    FROM group_member_requests gmr
    JOIN `groups` g ON gmr.group_id = g.id
    JOIN users u ON gmr.user_id = u.id
    WHERE gmr.id = ? AND gmr.group_id = ? AND gmr.status = 'pending'
");
$stmt->execute([$requestId, $groupId]);
$request = $stmt->fetch();

if (!$request) {
    setFlashMessage('Request not found or already processed', 'danger');
    redirect('../pages/group_members.php?id=' . $groupId);
}

try {
    $pdo->beginTransaction();
    
    if ($action === 'approve') {
        // Add user to group members
        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
        $stmt->execute([$groupId, $request['user_id']]);
        
        // Update request status
        $stmt = $pdo->prepare("UPDATE group_member_requests SET status = 'approved' WHERE id = ?");
        $stmt->execute([$requestId]);
        
        // Send notification to the user
        createNotification(
            $request['user_id'],
            'admin',
            'Group Request Approved',
            'Your request to join ' . htmlspecialchars($request['group_name']) . ' has been approved!',
            $groupId
        );
        
        setFlashMessage('Member request approved successfully!', 'success');
    } else {
        // Update request status to denied
        $stmt = $pdo->prepare("UPDATE group_member_requests SET status = 'denied' WHERE id = ?");
        $stmt->execute([$requestId]);
        
        // Send notification to the user
        createNotification(
            $request['user_id'],
            'admin',
            'Group Request Denied',
            'Your request to join ' . htmlspecialchars($request['group_name']) . ' has been denied.',
            $groupId
        );
        
        setFlashMessage('Member request denied', 'info');
    }
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    setFlashMessage('An error occurred while processing the request. Please try again.', 'danger');
}

redirect('../pages/group_members.php?id=' . $groupId);
