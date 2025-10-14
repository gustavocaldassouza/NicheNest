<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'notifications.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'NicheNest'; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/public/css/style.css">
    <meta name="theme-color" content="#0d6efd">
    <meta name="description" content="NicheNest - Your micro-community platform for focused groups">
</head>

<body>
    <a href="#main-content" class="visually-hidden-focusable btn btn-primary position-absolute" style="top: 10px; left: 10px; z-index: 9999;">Skip to main content</a>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark" role="navigation" aria-label="Main navigation">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/" aria-label="NicheNest home page">
                <i class="bi bi-people-fill" aria-hidden="true"></i> NicheNest
            </a>

            <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav"
                aria-controls="navbarNav"
                aria-expanded="false"
                aria-label="Toggle navigation menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto" role="menubar">
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="/" role="menuitem">Home</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item" role="none">
                            <a class="nav-link" href="/pages/posts.php" role="menuitem">Posts</a>
                        </li>
                        <li class="nav-item" role="none">
                            <a class="nav-link" href="/pages/groups.php" role="menuitem">Groups</a>
                        </li>
                        <li class="nav-item" role="none">
                            <a class="nav-link" href="/pages/profile.php" role="menuitem">Profile</a>
                        </li>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item" role="none">
                                <a class="nav-link" href="/pages/admin.php" role="menuitem">Admin</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <?php $user = getCurrentUser(); ?>
                        <li class="nav-item dropdown me-3" role="none">
                            <a class="nav-link position-relative" href="#"
                                id="notificationDropdown"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                aria-label="Notifications menu"
                                aria-describedby="notificationBadge">
                                <i class="bi bi-bell" aria-hidden="true"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                    id="notificationBadge"
                                    style="display: none;"
                                    aria-label="unread notifications">
                                    0
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end notification-dropdown"
                                style="min-width: 300px; max-height: 400px; overflow-y: auto;"
                                aria-labelledby="notificationDropdown"
                                role="menu">
                                <li class="dropdown-header d-flex justify-content-between align-items-center" role="none">
                                    <span>Notifications</span>
                                    <button class="btn btn-sm btn-outline-secondary"
                                        id="markAllReadBtn"
                                        style="display: none;"
                                        aria-label="Mark all notifications as read">Mark all read</button>
                                </li>
                                <li role="none">
                                    <hr class="dropdown-divider">
                                </li>
                                <div id="notificationsList" role="group" aria-label="Notifications list">
                                    <li class="dropdown-item text-center text-muted" role="none">
                                        <i class="bi bi-hourglass-split me-2" aria-hidden="true"></i>Loading notifications...
                                    </li>
                                </div>
                                <li role="none">
                                    <hr class="dropdown-divider">
                                </li>
                                <li role="none"><a class="dropdown-item text-center" href="#" id="viewAllNotifications" role="menuitem">View all notifications</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown" role="none">
                            <a class="nav-link dropdown-toggle" href="#"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                aria-label="User menu for <?php echo htmlspecialchars($user['display_name'] ?? $user['username']); ?>">
                                <?php echo htmlspecialchars($user['display_name'] ?? $user['username']); ?>
                            </a>
                            <ul class="dropdown-menu" role="menu">
                                <li role="none"><a class="dropdown-item" href="/pages/profile.php" role="menuitem">My Profile</a></li>
                                <li role="none">
                                    <hr class="dropdown-divider">
                                </li>
                                <li role="none"><a class="dropdown-item" href="/pages/logout.php" role="menuitem">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item" role="none">
                            <a class="nav-link" href="/pages/login.php" role="menuitem">Login</a>
                        </li>
                        <li class="nav-item" role="none">
                            <a class="nav-link" href="/pages/register.php" role="menuitem">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php
    $flash = getFlashMessage();
    if ($flash):
    ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>