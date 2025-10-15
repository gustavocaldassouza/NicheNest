<?php

function isCurrentUserSuspended()
{
    if (!isLoggedIn()) return false;
    global $pdo;
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([getCurrentUserId()]);
    $user = $stmt->fetch();
    return $user && $user['status'] === 'suspended';
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function getCurrentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUser()
{
    if (!isLoggedIn()) {
        return null;
    }

    global $pdo;
    $stmt = $pdo->prepare("SELECT id, username, email, display_name, avatar, created_at FROM users WHERE id = ?");
    $stmt->execute([getCurrentUserId()]);
    return $stmt->fetch();
}

function loginUser($userId)
{
    $_SESSION['user_id'] = $userId;
    $_SESSION['login_time'] = time();
}

function logoutUser()
{
    session_destroy();
    session_start();
}

function isAdmin()
{
    if (!isLoggedIn()) {
        return false;
    }

    global $pdo;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([getCurrentUserId()]);
    $user = $stmt->fetch();

    return $user && $user['role'] === 'admin';
}

function requireLogin()
{
    if (!isLoggedIn()) {
        setFlashMessage('Please log in to access this page.', 'warning');
        redirect('../pages/login.php');
    }
}

function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        setFlashMessage('Access denied. Admin privileges required.', 'danger');
        redirect('../public/index.php');
    }
}

/**
 * Require group owner access
 */
function requireGroupOwner($groupId)
{
    requireLogin();
    if (!isGroupOwner($groupId, getCurrentUserId())) {
        setFlashMessage('Access denied. Group owner privileges required.', 'danger');
        redirect('../pages/groups.php');
    }
}

/**
 * Require group member access
 */
function requireGroupMember($groupId)
{
    requireLogin();
    if (!canAccessGroup($groupId, getCurrentUserId())) {
        setFlashMessage('Access denied. You do not have permission to view this group.', 'danger');
        redirect('../pages/groups.php');
    }
}
