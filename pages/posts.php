<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/community_stats.php';
require_once '../includes/notifications.php';

requireLogin();

$errors = [];
$success = '';


if (isCurrentUserSuspended()) {
    $errors[] = 'Your account is suspended. You cannot create posts.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $title = sanitizeInput($_POST['title']);
    $content = sanitizeInput($_POST['content']);

    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    if (empty($content)) {
        $errors[] = 'Content is required';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([getCurrentUserId(), $title, $content]);
            $postId = $pdo->lastInsertId();

            if (!empty($_FILES['attachments'])) {
                $uploadResult = uploadPostAttachments((int)$postId, $_FILES['attachments']);
                if (!$uploadResult['success']) {
                    // Not fatal: keep post, show partial errors
                    foreach ($uploadResult['errors'] as $err) {
                        $errors[] = $err;
                    }
                }
            }
            $pdo->commit();
            $success = 'Post created successfully!';
            $_POST = [];
            header('Location: posts.php?success=1');
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Failed to create post. Please try again.';
        }
    }
}


// Get all posts with user info (excluding group posts)
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.display_name 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.group_id IS NULL
    ORDER BY p.created_at DESC
");
$stmt->execute();
$posts = $stmt->fetchAll();

$isAdminUser = isAdmin();
$page_title = "Community Posts - NicheNest";
include '../includes/header.php';
?>

<main id="main-content" role="main">
    <div class="container mt-4">
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

                <?php if (isCurrentUserSuspended()): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Your account is suspended. You cannot create posts.
                    </div>
                <?php else: ?>
                    <form method="POST" action="" enctype="multipart/form-data" id="createPostForm">
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
                            <div id="attachmentPreviews" class="mt-2 d-flex flex-wrap gap-2"></div>
                        </div>
                        <button type="submit" name="create_post" class="btn btn-primary">
                            <i class="bi bi-send"></i> Create Post
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <h4>Recent Posts</h4>

                <?php if (empty($posts)): ?>
                    <div class="card">
                        <div class="card-body text-center">
                            <h5>No posts yet</h5>
                            <p>Be the first to start a discussion!</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="card mb-4 <?php echo $post['flagged'] ? 'border-warning' : ''; ?>">
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
                                    <?php if (isPostOwner($post['id'], getCurrentUserId())): ?>
                                        <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit post">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-post-btn" data-post-id="<?php echo $post['id']; ?>" title="Delete post">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($isAdminUser && !isPostOwner($post['id'], getCurrentUserId())): ?>
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
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM likes WHERE post_id = ?");
                                $stmt->execute([$post['id']]);
                                $likeCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

                                $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
                                $stmt->execute([$post['id'], getCurrentUserId()]);
                                $userLiked = $stmt->rowCount() > 0;
                                ?>

                                <div class="post-actions">
                                    <button type="button"
                                        class="btn-action like-btn <?php echo $userLiked ? 'liked' : ''; ?>"
                                        data-post-id="<?php echo $post['id']; ?>"
                                        data-liked="<?php echo $userLiked ? 'true' : 'false'; ?>">
                                        <i class="bi bi-heart<?php echo $userLiked ? '-fill' : ''; ?>"></i>
                                        <span class="like-text"><?php echo $userLiked ? 'Liked' : 'Like'; ?></span>
                                        <?php if ($likeCount > 0): ?>
                                            <span class="badge rounded-pill bg-light text-dark border ms-1">
                                                <?php echo $likeCount; ?>
                                            </span>
                                        <?php endif; ?>
                                    </button>

                                    <button class="btn-action reply-btn reply-toggle"
                                        data-post-id="<?php echo $post['id']; ?>">
                                        <i class="bi bi-chat"></i>
                                        <span>Reply</span>
                                        <?php if ($post['replies_count'] > 0): ?>
                                            <span class="badge rounded-pill bg-light text-dark border ms-1">
                                                <?php echo $post['replies_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </button>
                                </div>

                                <form method="POST" class="reply-form mt-3 d-none" id="reply-form-<?php echo $post['id']; ?>">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <div class="mb-2">
                                        <textarea name="reply_content" class="form-control" rows="2" placeholder="Write your reply..." required></textarea>
                                    </div>
                                    <button type="submit" name="create_reply" class="btn btn-sm btn-success">Submit Reply</button>
                                    <button type="button" class="btn btn-sm btn-secondary ms-2 cancel-reply">Cancel</button>
                                </form>

                                <?php
                                $stmt = $pdo->prepare("
                                SELECT r.*, u.username, u.display_name 
                                FROM replies r 
                                JOIN users u ON r.user_id = u.id 
                                WHERE r.post_id = ? 
                                ORDER BY r.created_at ASC
                            ");
                                $stmt->execute([$post['id']]);
                                $replies = $stmt->fetchAll();
                                ?>

                                <?php if (!empty($replies)): ?>
                                    <div class="mt-3 replies-container">
                                        <h6>Replies:</h6>
                                        <?php foreach ($replies as $reply): ?>
                                            <div class="border-start border-3 border-light ps-3 mb-2 reply-item">
                                                <small class="text-muted">
                                                    <strong><?php echo htmlspecialchars($reply['display_name'] ?? $reply['username']); ?></strong>
                                                    <?php echo timeAgo($reply['created_at']); ?>
                                                </small>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <?php renderCommunityStatsCard(); ?>
                <div class="card">
                    <div class="card-header">
                        <h5>Community Guidelines</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle text-success"></i> Be respectful to all members</li>
                            <li><i class="bi bi-check-circle text-success"></i> Stay on topic</li>
                            <li><i class="bi bi-check-circle text-success"></i> No spam or self-promotion</li>
                            <li><i class="bi bi-check-circle text-success"></i> Use clear, descriptive titles</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>