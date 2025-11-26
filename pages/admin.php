<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$message = '';

// Function to log moderation actions
function logModerationAction($pdo, $action, $target_type, $target_id, $reason = null) {
    $moderator_id = getCurrentUserId();
    if (!$moderator_id) {
        return false; // Cannot log without valid moderator ID
    }
    
    // Get moderator username for logging
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$moderator_id]);
    $moderator = $stmt->fetch();
    
    // Log to database and file - both should succeed or we note the failure
    $dbSuccess = false;
    $fileSuccess = false;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO moderation_logs (moderator_id, action, target_type, target_id, reason) VALUES (?, ?, ?, ?, ?)");
        $dbSuccess = $stmt->execute([$moderator_id, $action, $target_type, $target_id, $reason]);
    } catch (PDOException $e) {
        Logger::error("Failed to log moderation action to database", [
            'error' => $e->getMessage(),
            'action' => $action,
            'target_type' => $target_type,
            'target_id' => $target_id
        ]);
    }
    
    // Always attempt file logging
    if ($moderator) {
        try {
            Logger::logModeration($action, $moderator['username'], $target_type, $target_id, $reason);
            $fileSuccess = true;
        } catch (Exception $e) {
            // File logging failed - at least we have database log
        }
    }
    
    return $dbSuccess || $fileSuccess; // Success if either method worked
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_user_status'])) {
        $user_id = (int)$_POST['user_id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status === 'active' ? 'suspended' : 'active';

        // Validate user exists
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            $message = "Error: User not found.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            
            // Log the moderation action
            $action = $new_status === 'suspended' ? 'suspend_user' : 'activate_user';
            logModerationAction($pdo, $action, 'user', $user_id);
            
            $message = "User status updated successfully.";
        }
    }

    if (isset($_POST['delete_post'])) {
        $post_id = (int)$_POST['post_id'];

        // Validate post exists
        $stmt = $pdo->prepare("SELECT 1 FROM posts WHERE id = ? LIMIT 1");
        $stmt->execute([$post_id]);
        if (!$stmt->fetch()) {
            $message = "Error: Post not found.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM replies WHERE post_id = ?");
            $stmt->execute([$post_id]);

            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            
            // Log the moderation action
            logModerationAction($pdo, 'delete_post', 'post', $post_id);
            
            $message = "Post and its replies deleted successfully.";
        }
    }

    if (isset($_POST['toggle_post_flag'])) {
        $post_id = (int)$_POST['post_id'];
        $current_flag = (int)$_POST['current_flag'];
        $new_flag = $current_flag === 1 ? 0 : 1;

        // Validate post exists
        $stmt = $pdo->prepare("SELECT 1 FROM posts WHERE id = ? LIMIT 1");
        $stmt->execute([$post_id]);
        if (!$stmt->fetch()) {
            $message = "Error: Post not found.";
        } else {
            $stmt = $pdo->prepare("UPDATE posts SET flagged = ? WHERE id = ?");
            $stmt->execute([$new_flag, $post_id]);
            
            // Log the moderation action
            $action = $new_flag === 1 ? 'flag_post' : 'unflag_post';
            logModerationAction($pdo, $action, 'post', $post_id);
            
            $message = $new_flag === 1 ? "Post flagged successfully." : "Post unflagged successfully.";
        }
    }
}

$stmt = $pdo->query("SELECT id, username, email, display_name, role, status, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT p.id, p.title, p.content, p.flagged, p.created_at, u.username, u.display_name 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.flagged DESC, p.created_at DESC
");
$posts = $stmt->fetchAll();

$user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$post_count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$reply_count = $pdo->query("SELECT COUNT(*) FROM replies")->fetchColumn();
$flagged_count = $pdo->query("SELECT COUNT(*) FROM posts WHERE flagged = 1")->fetchColumn();

// Get recent moderation logs
$stmt = $pdo->query("
    SELECT ml.*, u.username as moderator_username, u.display_name as moderator_name 
    FROM moderation_logs ml
    JOIN users u ON ml.moderator_id = u.id
    ORDER BY ml.created_at DESC
    LIMIT 20
");
$moderation_logs = $stmt->fetchAll();

$page_title = "Admin Panel - NicheNest";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-gear-fill"></i> Admin Panel</h2>
        <a href="logs.php" class="btn btn-outline-primary">
            <i class="bi bi-file-text"></i> View System Logs
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5>Total Users</h5>
                            <h2><?php echo $user_count; ?></h2>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-people-fill fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5>Total Posts</h5>
                            <h2><?php echo $post_count; ?></h2>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-file-post-fill fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5>Total Replies</h5>
                            <h2><?php echo $reply_count; ?></h2>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-chat-fill fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5>Flagged Posts</h5>
                            <h2><?php echo $flagged_count; ?></h2>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-flag-fill fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h4>User Management</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Display Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['display_name'] ?? ''); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo ($user['status'] ?? 'active') === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo htmlspecialchars($user['status'] ?? 'active'); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] !== getCurrentUserId()): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $user['status'] ?? 'active'; ?>">
                                            <button type="submit" name="toggle_user_status"
                                                class="btn btn-sm btn-<?php echo ($user['status'] ?? 'active') === 'active' ? 'warning' : 'success'; ?>">
                                                <?php echo ($user['status'] ?? 'active') === 'active' ? 'Suspend' : 'Activate'; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>Post Management</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr class="<?php echo $post['flagged'] ? 'table-warning' : ''; ?>">
                                <td><?php echo $post['id']; ?></td>
                                <td>
                                    <?php if ($post['flagged']): ?>
                                        <i class="bi bi-flag-fill text-warning" title="Flagged"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars(substr($post['title'], 0, 50)); ?>
                                    <?php if (strlen($post['title']) > 50) echo '...'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($post['display_name'] ?? $post['username']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $post['flagged'] ? 'warning' : 'success'; ?>">
                                        <?php echo $post['flagged'] ? 'Flagged' : 'Normal'; ?>
                                    </span>
                                </td>
                                <td><?php echo timeAgo($post['created_at']); ?></td>
                                <td>
                                    <a href="posts.php#post-<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-info" title="View post">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <input type="hidden" name="current_flag" value="<?php echo $post['flagged'] ? 1 : 0; ?>">
                                        <button type="submit" name="toggle_post_flag" class="btn btn-sm btn-<?php echo $post['flagged'] ? 'secondary' : 'warning'; ?>">
                                            <i class="bi bi-flag"></i> <?php echo $post['flagged'] ? 'Unflag' : 'Flag'; ?>
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this post and all its replies?');">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <button type="submit" name="delete_post" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Moderation Logs -->
    <div class="card mt-4">
        <div class="card-header">
            <h4>Recent Moderation Actions</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Moderator</th>
                            <th>Action</th>
                            <th>Target</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($moderation_logs)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No moderation actions yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($moderation_logs as $log): ?>
                                <tr>
                                    <td><?php echo timeAgo($log['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($log['moderator_name'] ?? $log['moderator_username']); ?></td>
                                    <td>
                                        <?php
                                        $action_labels = [
                                            'flag_post' => '<i class="bi bi-flag"></i> Flagged Post',
                                            'unflag_post' => '<i class="bi bi-flag"></i> Unflagged Post',
                                            'delete_post' => '<i class="bi bi-trash"></i> Deleted Post',
                                            'suspend_user' => '<i class="bi bi-person-x"></i> Suspended User',
                                            'activate_user' => '<i class="bi bi-person-check"></i> Activated User'
                                        ];
                                        echo $action_labels[$log['action']] ?? htmlspecialchars($log['action']);
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo ucfirst($log['target_type']); ?> #<?php echo $log['target_id']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>