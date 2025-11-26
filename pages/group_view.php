<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$groupId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$groupId) {
    setFlashMessage('Invalid group ID', 'danger');
    redirect('groups.php');
}

$currentUserId = getCurrentUserId();
$isAdminUser = isAdmin();
$errors = [];
$success = '';

// Handle post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $title = sanitizeInput($_POST['title']);
    $content = sanitizeInput($_POST['content']);

    if (empty($title)) {
        $errors[] = 'Post title is required';
    }

    if (empty($content)) {
        $errors[] = 'Post content is required';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, group_id, title, content, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$currentUserId, $groupId, $title, $content]);
            $postId = (int)$pdo->lastInsertId();
            if (!empty($_FILES['attachments'])) {
                $uploadResult = uploadPostAttachments($postId, $_FILES['attachments']);
                if (!$uploadResult['success']) {
                    foreach ($uploadResult['errors'] as $err) {
                        $errors[] = $err;
                    }
                }
            }
            $pdo->commit();
            $success = 'Post created successfully!';
            // Clear form data
            $_POST = [];
            header('Location: group_view.php?id=' . $groupId . '&success=1');
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Failed to create post. Please try again.';
        }
    }
}

// Handle reply creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_reply'])) {
    $post_id = (int)$_POST['post_id'];
    $content = sanitizeInput($_POST['reply_content']);

    if (empty($content)) {
        $errors[] = 'Reply content is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO replies (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$post_id, $currentUserId, $content]);
            $success = 'Reply added successfully!';
        } catch (PDOException $e) {
            $errors[] = 'Failed to add reply. Please try again.';
        }
    }
}

// Get group details
$stmt = $pdo->prepare("
    SELECT g.*, u.username as owner_username, u.display_name as owner_name,
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
    FROM `groups` g
    JOIN users u ON g.owner_id = u.id
    WHERE g.id = ?
");
$stmt->execute([$groupId]);
$group = $stmt->fetch();

if (!$group) {
    setFlashMessage('Group not found', 'danger');
    redirect('groups.php');
}

// Check if user can access this group
$isMember = isGroupMember($groupId, $currentUserId);
$isOwner = isGroupOwner($groupId, $currentUserId);
$hasPendingRequest = hasPendingGroupRequest($groupId, $currentUserId);

if ($group['privacy'] === 'private' && !$isMember) {
    // Private group and user is not a member
    $page_title = htmlspecialchars($group['name']) . " - NicheNest";
    include '../includes/header.php';
?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-lock display-1 text-warning"></i>
                        <h3 class="mt-3">Private Group</h3>
                        <p class="text-muted">This is a private group. You need to request membership to view its content.</p>

                        <?php if ($hasPendingRequest): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-hourglass-split"></i> Your join request is pending approval
                            </div>
                        <?php else: ?>
                            <form method="POST" action="/ajax/join_group.php" class="mt-3">
                                <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-person-plus"></i> Request to Join
                                </button>
                            </form>
                        <?php endif; ?>

                        <a href="groups.php" class="btn btn-outline-secondary mt-2">
                            <i class="bi bi-arrow-left"></i> Back to Groups
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
    include '../includes/footer.php';
    exit;
}

// Get group members
$stmt = $pdo->prepare("
    SELECT gm.*, u.username, u.display_name, u.avatar
    FROM group_members gm
    JOIN users u ON gm.user_id = u.id
    WHERE gm.group_id = ?
    ORDER BY gm.role DESC, gm.joined_at ASC
");
$stmt->execute([$groupId]);
$members = $stmt->fetchAll();

// Get group posts
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.display_name 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.group_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$groupId]);
$posts = $stmt->fetchAll();

// Get replies for each post
foreach ($posts as &$post) {
    $stmt = $pdo->prepare("
        SELECT r.*, u.username, u.display_name 
        FROM replies r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.post_id = ? 
        ORDER BY r.created_at ASC
    ");
    $stmt->execute([$post['id']]);
    $post['replies'] = $stmt->fetchAll();
}

$page_title = htmlspecialchars($group['name']) . " - NicheNest";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="mb-2"><?php echo htmlspecialchars($group['name']); ?></h2>
                    <p class="text-muted mb-2"><?php echo htmlspecialchars($group['description']); ?></p>
                    <div class="mb-2">
                        <span class="badge bg-<?php echo $group['privacy'] === 'private' ? 'warning' : 'success'; ?>">
                            <i class="bi bi-<?php echo $group['privacy'] === 'private' ? 'lock' : 'globe'; ?>"></i>
                            <?php echo ucfirst($group['privacy']); ?>
                        </span>
                        <span class="badge bg-secondary">
                            <i class="bi bi-people"></i> <?php echo $group['member_count']; ?> member<?php echo $group['member_count'] != 1 ? 's' : ''; ?>
                        </span>
                    </div>
                    <small class="text-muted">
                        Owner: <?php echo htmlspecialchars($group['owner_name'] ?? $group['owner_username']); ?>
                        | Created: <?php echo timeAgo($group['created_at']); ?>
                    </small>
                </div>
                <div>
                    <?php if ($isOwner): ?>
                        <a href="group_settings.php?id=<?php echo $groupId; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                        <a href="group_members.php?id=<?php echo $groupId; ?>" class="btn btn-outline-info">
                            <i class="bi bi-people"></i> Manage Members
                        </a>
                    <?php elseif ($isMember): ?>
                        <form method="POST" action="../ajax/leave_group.php" class="d-inline"
                            onsubmit="return confirm('Are you sure you want to leave this group?');">
                            <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="bi bi-box-arrow-right"></i> Leave Group
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Create New Post -->
            <?php if ($isMember): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-plus-circle"></i> Create New Post</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data" id="groupCreatePostForm">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title"
                                    value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="content" class="form-label">Content</label>
                                <textarea class="form-control" id="content" name="content" rows="4" required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="attachments" class="form-label">Attachments</label>
                                <input class="form-control" type="file" id="attachments" name="attachments[]" multiple accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain,application/zip" />
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> Select multiple files (up to 10MB each). Images and documents (PDF, Word, Excel, TXT, ZIP) are allowed.
                                </div>
                                <div id="attachmentPreviewsGroup" class="mt-2 d-flex flex-wrap gap-2"></div>
                            </div>
                            <button type="submit" name="create_post" class="btn btn-primary">
                                <i class="bi bi-send"></i> Create Post
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Group Posts -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-chat-left-text"></i> Group Feed</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($posts)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No posts yet.
                            <?php if ($isMember): ?>
                                Be the first to share something with the group!
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="card mb-3 <?php echo $post['flagged'] ? 'border-warning' : ''; ?>">
                                <div class="card-header d-flex justify-content-between align-items-center <?php echo $post['flagged'] ? 'bg-warning bg-opacity-25' : ''; ?>">
                                    <div>
                                        <?php if ($post['flagged']): ?>
                                            <span class="badge bg-warning text-dark me-2" title="This post has been flagged for review">
                                                <i class="bi bi-flag-fill"></i> Flagged
                                            </span>
                                        <?php endif; ?>
                                        <strong><?php echo htmlspecialchars($post['display_name'] ?? $post['username']); ?></strong>
                                        <small class="text-muted"><?php echo timeAgo($post['created_at']); ?></small>
                                    </div>
                                    <div class="btn-group" role="group">
                                        <?php if (isPostOwner($post['id'], $currentUserId)): ?>
                                            <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit post">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-post-btn" data-post-id="<?php echo $post['id']; ?>" title="Delete post">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($isAdminUser && !isPostOwner($post['id'], $currentUserId)): ?>
                                            <button type="button" 
                                                class="btn btn-sm btn-<?php echo $post['flagged'] ? 'secondary' : 'warning'; ?> admin-flag-btn" 
                                                data-post-id="<?php echo $post['id']; ?>" 
                                                data-flagged="<?php echo $post['flagged'] ? 'true' : 'false'; ?>"
                                                title="<?php echo $post['flagged'] ? 'Unflag post' : 'Flag post for review'; ?>">
                                                <i class="bi bi-flag<?php echo $post['flagged'] ? '-fill' : ''; ?>"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger admin-delete-btn" data-post-id="<?php echo $post['id']; ?>" title="Delete post (Admin)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body" id="post-<?php echo $post['id']; ?>">
                                    <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                    <?php
                                    $attachments = getPostAttachments($post['id']);
                                    echo renderAttachmentsHtml($attachments);
                                    ?>

                                    <?php if ($isMember): ?>
                                        <!-- Reply Button -->
                                        <button class="btn btn-sm btn-outline-primary reply-toggle" data-post-id="<?php echo $post['id']; ?>">
                                            <i class="bi bi-reply"></i> Reply
                                        </button>

                                        <!-- Reply Form (hidden by default) -->
                                        <form method="POST" class="reply-form mt-3 d-none" id="reply-form-<?php echo $post['id']; ?>">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <div class="mb-2">
                                                <textarea name="reply_content" class="form-control" rows="2" placeholder="Write a reply..." required></textarea>
                                            </div>
                                            <button type="submit" name="create_reply" class="btn btn-sm btn-primary">
                                                <i class="bi bi-send"></i> Post Reply
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary cancel-reply">Cancel</button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Replies Section -->
                                    <?php if (!empty($post['replies'])): ?>
                                        <div class="mt-3 pt-3 border-top">
                                            <h6 class="text-muted">Replies (<?php echo count($post['replies']); ?>)</h6>
                                            <div class="replies">
                                                <?php foreach ($post['replies'] as $reply): ?>
                                                    <div class="border-start border-3 ps-3 mb-2">
                                                        <small class="text-muted">
                                                            <strong><?php echo htmlspecialchars($reply['display_name'] ?? $reply['username']); ?></strong>
                                                            <?php echo timeAgo($reply['created_at']); ?>
                                                        </small>
                                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-people"></i> Members</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($members as $member): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                <strong><?php echo htmlspecialchars($member['display_name'] ?? $member['username']); ?></strong>
                                <?php if ($member['role'] === 'owner'): ?>
                                    <span class="badge bg-primary">Owner</span>
                                <?php endif; ?>
                                <div class="small text-muted">@<?php echo htmlspecialchars($member['username']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>