<?php
session_start();
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Clear any buffered output before sending JSON
if (ob_get_length()) ob_clean();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$attachmentId = isset($_POST['attachment_id']) ? (int)$_POST['attachment_id'] : 0;
$currentUserId = getCurrentUserId();

if (!$attachmentId) {
    echo json_encode(['success' => false, 'message' => 'Invalid attachment ID']);
    exit;
}

$result = deleteAttachment($attachmentId, $currentUserId);
echo json_encode($result);
