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
                            <form method="POST" action="../ajax/join_group.php" class="mt-3">
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
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-chat-left-text"></i> Group Feed</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Group feed and posts functionality coming soon! 
                        This is where members will share content and discussions.
                    </div>
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
