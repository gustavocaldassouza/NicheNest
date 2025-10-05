<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

logoutUser();
setFlashMessage('You have been logged out successfully.', 'info');
redirect('/');
