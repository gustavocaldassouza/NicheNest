<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$currentUserId = getCurrentUserId();

// Get all groups that the user can see (public groups + private groups they're a member of)
// Note: user_id parameter is used three times - for checking membership role, EXISTS clause, and pending requests
$stmt = $pdo->prepare("
    SELECT g.*, u.username as owner_username, u.display_name as owner_name,
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
           (SELECT role FROM group_members WHERE group_id = g.id AND user_id = ?) as user_role,
           (SELECT COUNT(*) FROM group_member_requests WHERE group_id = g.id AND user_id = ? AND status = 'pending') as has_pending_request
    FROM `groups` g
    JOIN users u ON g.owner_id = u.id
    WHERE g.privacy = 'public' 
       OR EXISTS (SELECT 1 FROM group_members WHERE group_id = g.id AND user_id = ?)
    ORDER BY g.created_at DESC
");
$stmt->execute([$currentUserId, $currentUserId, $currentUserId]);
$groups = $stmt->fetchAll();

$page_title = "Groups - NicheNest";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-people-fill"></i> Groups</h2>
        <a href="group_create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Group
        </a>
    </div>

    <?php if (empty($groups)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No groups found. Be the first to create one!
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($groups as $group): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title">
                                    <a href="group_view.php?id=<?php echo $group['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($group['name']); ?>
                                    </a>
                                </h5>
                                <span class="badge bg-<?php echo $group['privacy'] === 'private' ? 'warning' : 'success'; ?>">
                                    <i class="bi bi-<?php echo $group['privacy'] === 'private' ? 'lock' : 'globe'; ?>"></i>
                                    <?php echo ucfirst($group['privacy']); ?>
                                </span>
                            </div>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars(substr($group['description'], 0, 150)); ?>
                                <?php if (strlen($group['description']) > 150): ?>...<?php endif; ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bi bi-person"></i> Owner: <?php echo htmlspecialchars($group['owner_name'] ?? $group['owner_username']); ?>
                                </small>
                                <small class="text-muted">
                                    <i class="bi bi-people"></i> <?php echo $group['member_count']; ?> member<?php echo $group['member_count'] != 1 ? 's' : ''; ?>
                                </small>
                            </div>
                            <?php if ($group['user_role']): ?>
                                <div class="mt-2">
                                    <span class="badge bg-info">
                                        <i class="bi bi-check-circle"></i> 
                                        <?php echo $group['user_role'] === 'owner' ? 'Owner' : 'Member'; ?>
                                    </span>
                                </div>
                            <?php elseif ($group['has_pending_request'] > 0): ?>
                                <div class="mt-2">
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-hourglass-split"></i> Request Pending
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="group_view.php?id=<?php echo $group['id']; ?>" class="btn btn-sm btn-outline-primary">
                                View Group
                            </a>
                            <?php if ($group['user_role'] === 'owner'): ?>
                                <a href="group_settings.php?id=<?php echo $group['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-gear"></i> Settings
                                </a>
                                <a href="group_members.php?id=<?php echo $group['id']; ?>" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-people"></i> Members
                                </a>
                            <?php elseif (!$group['user_role'] && $group['privacy'] === 'public'): ?>
                                <form method="POST" action="/ajax/join_group.php" class="d-inline">
                                    <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-person-plus"></i> Join Group
                                    </button>
                                </form>
                            <?php elseif (!$group['user_role'] && $group['privacy'] === 'private' && $group['has_pending_request'] == 0): ?>
                                <form method="POST" action="/ajax/join_group.php" class="d-inline">
                                    <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-warning">
                                        <i class="bi bi-person-plus"></i> Request to Join
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
