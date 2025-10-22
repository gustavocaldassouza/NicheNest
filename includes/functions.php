<?php
// General utility functions for NicheNest

/**
 * Sanitize input data
 */
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email address
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate secure password hash
 */
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Redirect to a specific page
 */
function redirect($url)
{
    header("Location: " . $url);
    exit();
}

/**
 * Display flash messages
 */
function setFlashMessage($message, $type = 'info')
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Format timestamp for display
 */
function formatTimestamp($timestamp)
{
    return date('M j, Y 	 g:i A', strtotime($timestamp));
}

/**
 * Get time ago format
 */
function timeAgo($timestamp)
{
    $time = time() - strtotime($timestamp);

    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time / 60) . ' minutes ago';
    if ($time < 86400) return floor($time / 3600) . ' hours ago';
    if ($time < 2592000) return floor($time / 86400) . ' days ago';

    return date('M j, Y', strtotime($timestamp));
}

// Group-related functions

function isGroupOwner($groupId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ? AND user_id = ? AND role = 'owner'");
    $stmt->execute([$groupId, $userId]);
    return $stmt->fetchColumn() > 0;
}

function isGroupMember($groupId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$groupId, $userId]);
    return $stmt->fetchColumn() > 0;
}

function canAccessGroup($groupId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT privacy FROM `groups` WHERE id = ?");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch();

    if (!$group) {
        return false;
    }

    if ($group['privacy'] === 'public') {
        return true;
    }

    return isGroupMember($groupId, $userId);
}

function hasPendingGroupRequest($groupId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_member_requests WHERE group_id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$groupId, $userId]);
    return $stmt->fetchColumn() > 0;
}

function getPendingMemberRequests($groupId)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT gmr.*, u.username, u.display_name, u.avatar 
        FROM group_member_requests gmr
        JOIN users u ON gmr.user_id = u.id
        WHERE gmr.group_id = ? AND gmr.status = 'pending'
        ORDER BY gmr.created_at DESC
    ");
    $stmt->execute([$groupId]);
    return $stmt->fetchAll();
}

function hasPendingGroupInvitation($groupId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_invitations WHERE group_id = ? AND invitee_id = ? AND status = 'pending'");
    $stmt->execute([$groupId, $userId]);
    return $stmt->fetchColumn() > 0;
}

function getPendingInvitationsForUser($userId)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT gi.*, g.name as group_name, g.description as group_description, 
               u.username as inviter_username, u.display_name as inviter_name
        FROM group_invitations gi
        JOIN `groups` g ON gi.group_id = g.id
        JOIN users u ON gi.inviter_id = u.id
        WHERE gi.invitee_id = ? AND gi.status = 'pending'
        ORDER BY gi.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function searchUsersNotInGroup($groupId, $searchTerm = '', $limit = 10)
{
    global $pdo;

    $sql = "
        SELECT u.id, u.username, u.display_name, u.avatar, u.email
        FROM users u
        WHERE u.id NOT IN (SELECT user_id FROM group_members WHERE group_id = ?)
        AND u.id NOT IN (SELECT invitee_id FROM group_invitations WHERE group_id = ? AND status = 'pending')
    ";

    $params = [$groupId, $groupId];

    if (!empty($searchTerm)) {
        $sql .= " AND (u.username LIKE ? OR u.display_name LIKE ? OR u.email LIKE ?)";
        $searchPattern = '%' . $searchTerm . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    }

    $sql .= " ORDER BY u.username ASC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}


/**
 * Upload and save profile avatar
 * 
 * @param array $file The uploaded file from $_FILES
 * @param int $userId The user ID
 * @return array Result with success status and message
 */
function uploadAvatar($file, $userId)
{
    global $pdo;

    // Validate file exists
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error occurred.'];
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($file['tmp_name']);

    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.'];
    }

    // Validate file size (5MB max)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
    $uploadDir = __DIR__ . '/../public/uploads/avatars/';
    $uploadPath = $uploadDir . $filename;
    $dbPath = '/uploads/avatars/' . $filename;

    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Delete old avatar if exists
    try {
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $oldAvatar = $stmt->fetchColumn();

        if ($oldAvatar && $oldAvatar !== '/images/default-avatar.svg') {
            $oldPath = __DIR__ . '/../public' . $oldAvatar;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
    } catch (Exception $e) {
        // Continue even if old file deletion fails
    }

    // Upload new image
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Update database
        try {
            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$dbPath, $userId]);

            return ['success' => true, 'message' => 'Avatar updated successfully.', 'path' => $dbPath];
        } catch (PDOException $e) {
            // Delete uploaded file if database update fails
            if (file_exists($uploadPath)) {
                unlink($uploadPath);
            }
            return ['success' => false, 'message' => 'Failed to update database.'];
        }
    }

    return ['success' => false, 'message' => 'Failed to upload image.'];
}

/**
 * Get user avatar URL
 * 
 * @param int $userId The user ID (optional, uses current user if not provided)
 * @return string The avatar URL
 */
function getUserAvatar($userId = null)
{
    global $pdo;

    if ($userId === null) {
        $userId = getCurrentUserId();
    }

    if (!$userId) {
        return '/images/default-avatar.svg';
    }

    try {
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $avatar = $stmt->fetchColumn();

        if ($avatar) {
            $fullPath = __DIR__ . '/../public' . $avatar;
            if (file_exists($fullPath)) {
                return $avatar;
            }
        }
    } catch (Exception $e) {
        // Return default on error
    }

    return '/images/default-avatar.svg';
}

/**
 * Delete user avatar
 * 
 * @param int $userId The user ID
 * @return bool Success status
 */
function deleteAvatar($userId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $avatar = $stmt->fetchColumn();

        if ($avatar && $avatar !== '/images/default-avatar.svg') {
            $filePath = __DIR__ . '/../public' . $avatar;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Reset to default avatar
        $stmt = $pdo->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
        $stmt->execute([$userId]);

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Upload multiple attachments for a post
 *
 * @param int $postId
 * @param array $files The $_FILES['attachments'] array
 * @return array Result with success and errors
 */
function uploadPostAttachments($postId, $files)
{
    global $pdo;

    if (!isset($files) || !isset($files['name']) || empty($files['name'])) {
        return ['success' => true, 'uploaded' => 0]; // No files uploaded is not an error
    }

    $allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $allowedFileTypes = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
        'application/msword',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
        'application/vnd.ms-excel',
        'text/plain',
        'application/zip',
        'application/x-zip-compressed'
    ];
    $maxSize = 10 * 1024 * 1024; // 10MB per file

    $uploadBase = __DIR__ . '/../public/uploads/posts/';
    if (!is_dir($uploadBase)) {
        mkdir($uploadBase, 0755, true);
    }

    $uploadedCount = 0;
    $errors = [];

    $fileCount = is_array($files['name']) ? count($files['name']) : 0;
    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            if ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = 'Upload error for file ' . htmlspecialchars($files['name'][$i]);
            }
            continue;
        }

        $tmpPath = $files['tmp_name'][$i];
        $origName = $files['name'][$i];
        $size = (int)$files['size'][$i];
        $mime = mime_content_type($tmpPath);

        if ($size > $maxSize) {
            $errors[] = htmlspecialchars($origName) . ' exceeds maximum size of 10MB.';
            continue;
        }

        $type = in_array($mime, $allowedImageTypes) ? 'image' : (in_array($mime, $allowedFileTypes) ? 'file' : null);
        if ($type === null) {
            $errors[] = htmlspecialchars($origName) . ' has an unsupported file type (' . htmlspecialchars($mime) . ').';
            continue;
        }

        $ext = pathinfo($origName, PATHINFO_EXTENSION);
        $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        $rand = bin2hex(random_bytes(8));
        $subdir = $type === 'image' ? 'images' : 'files';
        $filename = 'post_' . $postId . '_' . time() . '_' . $rand . ($safeExt ? ('.' . strtolower($safeExt)) : '');
        $destDir = $uploadBase . $subdir . '/';
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        $destPath = $destDir . $filename;
        $dbPath = '/uploads/posts/' . $subdir . '/' . $filename;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            $errors[] = 'Failed to save file ' . htmlspecialchars($origName);
            continue;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO post_attachments (post_id, type, file_path, original_name, mime_type, size, created_at) VALUES (?,?,?,?,?,?,NOW())");
            $stmt->execute([$postId, $type, $dbPath, $origName, $mime, $size]);
            $uploadedCount++;
        } catch (Exception $e) {
            // Clean up file if DB insert fails
            if (file_exists($destPath)) {
                unlink($destPath);
            }
            $errors[] = 'Database error saving attachment ' . htmlspecialchars($origName);
        }
    }

    return ['success' => empty($errors), 'uploaded' => $uploadedCount, 'errors' => $errors];
}

/**
 * Get attachments for a post
 */
function getPostAttachments($postId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, type, file_path, original_name, mime_type, size FROM post_attachments WHERE post_id = ? ORDER BY id ASC");
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

/**
 * Render attachments block for a post
 */
function renderAttachmentsHtml($attachments)
{
    if (!$attachments || count($attachments) === 0) return '';

    $images = array_filter($attachments, function ($a) {
        return $a['type'] === 'image';
    });
    $files = array_filter($attachments, function ($a) {
        return $a['type'] === 'file';
    });

    ob_start();
?>
    <div class="mt-3">
        <?php if (!empty($images)): ?>
            <div class="row g-2">
                <?php foreach ($images as $img): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?php echo htmlspecialchars($img['file_path']); ?>" target="_blank" rel="noopener">
                            <img src="<?php echo htmlspecialchars($img['file_path']); ?>" alt="Image attachment" class="img-fluid rounded border">
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($files)): ?>
            <ul class="list-unstyled mt-2">
                <?php foreach ($files as $file): ?>
                    <li class="mb-1">
                        <i class="bi bi-paperclip me-1"></i>
                        <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" rel="noopener">
                            <?php echo htmlspecialchars($file['original_name']); ?>
                        </a>
                        <small class="text-muted">(<?php echo round(((int)$file['size']) / 1024); ?> KB)</small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Check if a user owns a post
 */
function isPostOwner($postId, $userId)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        return $post && (int)$post['user_id'] === (int)$userId;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Delete a single attachment and its file
 */
function deleteAttachment($attachmentId, $userId)
{
    global $pdo;
    try {
        // Get attachment info and verify ownership via post
        $stmt = $pdo->prepare("
            SELECT pa.*, p.user_id 
            FROM post_attachments pa
            JOIN posts p ON pa.post_id = p.id
            WHERE pa.id = ?
        ");
        $stmt->execute([$attachmentId]);
        $attachment = $stmt->fetch();

        if (!$attachment) {
            return ['success' => false, 'message' => 'Attachment not found'];
        }

        if ((int)$attachment['user_id'] !== (int)$userId) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Delete file from filesystem
        $filePath = __DIR__ . '/../public' . $attachment['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM post_attachments WHERE id = ?");
        $stmt->execute([$attachmentId]);

        return ['success' => true, 'message' => 'Attachment deleted'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error deleting attachment'];
    }
}

/**
 * Delete all attachments for a post
 */
function deletePostAttachments($postId)
{
    global $pdo;
    try {
        $attachments = getPostAttachments($postId);
        foreach ($attachments as $att) {
            $filePath = __DIR__ . '/../public' . $att['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        $stmt = $pdo->prepare("DELETE FROM post_attachments WHERE post_id = ?");
        $stmt->execute([$postId]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get community stats: active members, total posts, total groups
 * Returns array: ['active_members' => int, 'total_posts' => int, 'total_groups' => int]
 */
function getCommunityStats()
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    global $pdo;

    try {
        // Use individual COUNTs for clarity and indexes
        $activeMembers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
        $totalPosts = (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        $totalGroups = (int)$pdo->query("SELECT COUNT(*) FROM `groups`")->fetchColumn();

        $cached = [
            'active_members' => $activeMembers,
            'total_posts' => $totalPosts,
            'total_groups' => $totalGroups,
        ];
        return $cached;
    } catch (Exception $e) {
        // On error, return zeros to avoid breaking UI
        return [
            'active_members' => 0,
            'total_posts' => 0,
            'total_groups' => 0,
        ];
    }
}
