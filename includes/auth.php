<?php
// Authentication functions for NicheNest

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    global $pdo;
    $stmt = $pdo->prepare("SELECT id, username, email, display_name, avatar, created_at FROM users WHERE id = ?");
    $stmt->execute([getCurrentUserId()]);
    return $stmt->fetch();
}

/**
 * Login user
 */
function loginUser($userId) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['login_time'] = time();
}

/**
 * Logout user
 */
function logoutUser() {
    session_destroy();
    session_start();
}

/**
 * Check if user has admin role
 */
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }

    global $pdo;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([getCurrentUserId()]);
    $user = $stmt->fetch();

    return $user && $user['role'] === 'admin';
}

/**
 * Require login for page access
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('Please log in to access this page.', 'warning');
        redirect('../pages/login.php');
    }
}

/**
 * Require admin access for page
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlashMessage('Access denied. Admin privileges required.', 'danger');
        redirect('../public/index.php');
    }
}
?>