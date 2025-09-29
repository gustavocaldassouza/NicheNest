<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$errors = [];
$success = '';

// Handle new post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
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
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([getCurrentUserId(), $title, $content]);
            $success = 'Post created successfully!';
            // Clear form data
            $_POST = [];
        } catch (PDOException $e) {
            $errors[] = 'Failed to create post. Please try again.';
        }
    }
}

// Handle new reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_reply'])) {
    $post_id = (int)$_POST['post_id'];
    $content = sanitizeInput($_POST['reply_content']);

    if (empty($content)) {
        $errors[] = 'Reply content is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO replies (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$post_id, getCurrentUserId(), $content]);
            $success = 'Reply added successfully!';
        } catch (PDOException $e) {
            $errors[] = 'Failed to add reply. Please try again.';
        }
    }
}

// Get all posts with user info
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.display_name 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC
");
$stmt->execute();
$posts = $stmt->fetchAll();

$page_title = "Community Posts - NicheNest";
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <!-- Create New Post -->
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

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="content" class="form-label">Content</label>
                    <textarea class="form-control" id="content" name="content" rows="4" required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="create_post" class="btn btn-primary">
                    <i class="bi bi-send"></i> Create Post
                </button>
            </form>
        </div>
    </div>

    <!-- Posts List -->
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
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($post['display_name'] ?? $post['username']); ?></strong>
                                <small class="text-muted"><?php echo timeAgo($post['created_at']); ?></small>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

                            <!-- Reply Button -->
                            <button class="btn btn-sm btn-outline-primary reply-toggle" data-post-id="<?php echo $post['id']; ?>">
                                <i class="bi bi-reply"></i> Reply
                            </button>

                            <!-- Reply Form (hidden by default) -->
                            <form method="POST" class="reply-form mt-3 d-none" id="reply-form-<?php echo $post['id']; ?>">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <div class="mb-2">
                                    <textarea name="reply_content" class="form-control" rows="2" placeholder="Write your reply..." required></textarea>
                                </div>
                                <button type="submit" name="create_reply" class="btn btn-sm btn-success">Submit Reply</button>
                                <button type="button" class="btn btn-sm btn-secondary ms-2 cancel-reply">Cancel</button>
                            </form>

                            <!-- Replies -->
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
                                <div class="mt-3">
                                    <h6>Replies:</h6>
                                    <?php foreach ($replies as $reply): ?>
                                        <div class="border-start border-3 border-light ps-3 mb-2">
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

<?php include __DIR__ . '/../includes/footer.php'; ?>