<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Logout user
logoutUser();
setFlashMessage('You have been logged out successfully.', 'info');
redirect('/');
