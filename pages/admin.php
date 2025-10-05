<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_user_status'])) {
        $user_id = (int)$_POST['user_id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status === 'active' ? 'suspended' : 'active';

        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $user_id]);
        $message = "User status updated successfully.";
    }

    if (isset($_POST['delete_post'])) {
        $post_id = (int)$_POST['post_id'];

        $stmt = $pdo->prepare("DELETE FROM replies WHERE post_id = ?");
        $stmt->execute([$post_id]);

        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $message = "Post and its replies deleted successfully.";
    }
}

$stmt = $pdo->query("SELECT id, username, email, display_name, role, status, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT p.id, p.title, p.content, p.created_at, u.username, u.display_name 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC
");
$posts = $stmt->fetchAll();

$user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$post_count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$reply_count = $pdo->query("SELECT COUNT(*) FROM replies")->fetchColumn();

$page_title = "Admin Panel - NicheNest";
include '../includes/header.php';
?>

<div class="container mt-4">
    <h2><i class="bi bi-gear-fill"></i> Admin Panel</h2>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-4">
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
        <div class="col-md-4">
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
        <div class="col-md-4">
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
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?php echo $post['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars(substr($post['title'], 0, 50)); ?>
                                    <?php if (strlen($post['title']) > 50) echo '...'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($post['display_name'] ?? $post['username']); ?></td>
                                <td><?php echo timeAgo($post['created_at']); ?></td>
                                <td>
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
</div>

<?php include '../includes/footer.php'; ?>