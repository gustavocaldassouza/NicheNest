<?php
session_start();
$basePath = dirname(__DIR__);
require_once $basePath . '/includes/config.php';
require_once $basePath . '/includes/functions.php';
require_once $basePath . '/includes/auth.php';

header('Content-Type: application/json');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';
$currentUserId = getCurrentUserId();

// Validate input
if (!$groupId) {
    echo json_encode(['success' => false, 'message' => 'Invalid group']);
    exit;
}

// Check if user is group owner
if (!isGroupOwner($groupId, $currentUserId)) {
    echo json_encode(['success' => false, 'message' => 'Only group owners can search for users to invite']);
    exit;
}

try {
    $users = searchUsersNotInGroup($groupId, $searchTerm, 20);
    
    // Remove current user from results
    $users = array_filter($users, function($user) use ($currentUserId) {
        return $user['id'] != $currentUserId;
    });
    
    // Return only necessary fields for privacy
    $users = array_map(function($user) {
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'display_name' => $user['display_name']
        ];
    }, array_values($users));
    
    echo json_encode(['success' => true, 'users' => $users]);
} catch (PDOException $e) {
    error_log("Failed to search users: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to search users']);
}
