<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';


// Get all groups (public and private)
if (isLoggedIn()) {
    $currentUserId = getCurrentUserId();
    $stmt = $pdo->prepare("
        SELECT g.*, u.username as owner_username, u.display_name as owner_name,
               (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
               (SELECT role FROM group_members WHERE group_id = g.id AND user_id = ?) as user_role,
               (SELECT COUNT(*) FROM group_member_requests WHERE group_id = g.id AND user_id = ? AND status = 'pending') as has_pending_request
        FROM `groups` g
        JOIN users u ON g.owner_id = u.id
        ORDER BY g.created_at DESC
    ");
    $stmt->execute([$currentUserId, $currentUserId]);
    $groups = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT g.*, u.username as owner_username, u.display_name as owner_name,
               (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
        FROM `groups` g
        JOIN users u ON g.owner_id = u.id
        WHERE g.privacy = 'public'
        ORDER BY g.created_at DESC
    ");
    $stmt->execute();
    $groups = $stmt->fetchAll();
}

$page_title = "Discover Groups - NicheNest";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="bi bi-compass"></i> Discover Interest Groups</h2>
            <p class="text-muted">Browse all groups and find communities matching your interests</p>
        </div>
    </div>

    <?php if (!isLoggedIn()): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <a href="login.php" class="alert-link">Log in</a> or
            <a href="register.php" class="alert-link">create an account</a> to join groups and participate in discussions.
        </div>
    <?php endif; ?>

    <?php if (empty($groups)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-info-circle"></i> No groups available yet. Check back soon!
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($groups as $group): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title">
                                    <?php if (isLoggedIn()): ?>
                                        <a href="group_view.php?id=<?php echo $group['id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($group['name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($group['name']); ?>
                                    <?php endif; ?>
                                </h5>
                                <span class="badge bg-<?php echo $group['privacy'] === 'private' ? 'warning' : 'success'; ?>">
                                    <i class="bi bi-<?php echo $group['privacy'] === 'private' ? 'lock' : 'globe'; ?>"></i> <?php echo ucfirst($group['privacy']); ?>
                                </span>
                            </div>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars(substr($group['description'], 0, 150)); ?>
                                <?php if (strlen($group['description']) > 150): ?>...<?php endif; ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($group['owner_name'] ?? $group['owner_username']); ?>
                                </small>
                                <small class="text-muted">
                                    <i class="bi bi-people"></i> <?php echo $group['member_count']; ?> member<?php echo $group['member_count'] != 1 ? 's' : ''; ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <?php if (isLoggedIn()): ?>
                                <a href="group_view.php?id=<?php echo $group['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View Group
                                </a>
                                <?php if ($group['user_role']): ?>
                                    <span class="badge bg-info"><i class="bi bi-check-circle"></i> <?php echo $group['user_role'] === 'owner' ? 'Owner' : 'Member'; ?></span>
                                <?php elseif ($group['has_pending_request'] > 0): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Request Pending</span>
                                <?php elseif ($group['privacy'] === 'public'): ?>
                                    <form method="POST" action="/ajax/join_group.php" class="d-inline">
                                        <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-person-plus"></i> Join
                                        </button>
                                    </form>
                                <?php elseif ($group['privacy'] === 'private'): ?>
                                    <form method="POST" action="/ajax/join_group.php" class="d-inline">
                                        <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            <i class="bi bi-person-plus"></i> Request to Join
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-box-arrow-in-right"></i> Login to Join
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>