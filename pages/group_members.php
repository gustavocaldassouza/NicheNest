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

requireGroupOwner($groupId);

// Get group details
$stmt = $pdo->prepare("SELECT * FROM `groups` WHERE id = ?");
$stmt->execute([$groupId]);
$group = $stmt->fetch();

if (!$group) {
    setFlashMessage('Group not found', 'danger');
    redirect('groups.php');
}

// Get pending member requests
$pendingRequests = getPendingMemberRequests($groupId);

// Get current members
$stmt = $pdo->prepare("
    SELECT gm.*, u.username, u.display_name, u.avatar, u.email
    FROM group_members gm
    JOIN users u ON gm.user_id = u.id
    WHERE gm.group_id = ?
    ORDER BY gm.role DESC, gm.joined_at ASC
");
$stmt->execute([$groupId]);
$members = $stmt->fetchAll();

$page_title = "Manage Members - " . htmlspecialchars($group['name']);
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-people"></i> Manage Members</h2>
        <a href="group_view.php?id=<?php echo $groupId; ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Group
        </a>
    </div>

    <div class="row">
        <div class="col-md-12">
            <?php if ($group['privacy'] === 'private' && !empty($pendingRequests)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="bi bi-hourglass-split"></i> Pending Member Requests
                            <span class="badge bg-dark"><?php echo count($pendingRequests); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($pendingRequests as $request): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 p-3 border rounded">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($request['display_name'] ?? $request['username']); ?></h6>
                                    <small class="text-muted">@<?php echo htmlspecialchars($request['username']); ?></small>
                                    <div class="small text-muted mt-1">
                                        Requested: <?php echo timeAgo($request['created_at']); ?>
                                    </div>
                                </div>
                                <div>
                                    <form method="POST" action="../ajax/manage_member_request.php" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-check-circle"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="../ajax/manage_member_request.php" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="deny">
                                        <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-x-circle"></i> Deny
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($group['privacy'] === 'private'): ?>
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle"></i> No pending member requests at this time.
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-people-fill"></i> Current Members
                        <span class="badge bg-primary"><?php echo count($members); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member['display_name'] ?? $member['username']); ?></td>
                                        <td>@<?php echo htmlspecialchars($member['username']); ?></td>
                                        <td>
                                            <?php if ($member['role'] === 'owner'): ?>
                                                <span class="badge bg-primary">Owner</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Member</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo timeAgo($member['joined_at']); ?></td>
                                        <td>
                                            <?php if ($member['role'] !== 'owner'): ?>
                                                <form method="POST" action="../ajax/remove_member.php" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to remove this member?');">
                                                    <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i> Remove
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
