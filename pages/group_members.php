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

// Get pending invitations
$stmt = $pdo->prepare("
    SELECT gi.*, u.username, u.display_name, u.avatar
    FROM group_invitations gi
    JOIN users u ON gi.invitee_id = u.id
    WHERE gi.group_id = ? AND gi.status = 'pending'
    ORDER BY gi.created_at DESC
");
$stmt->execute([$groupId]);
$pendingInvitations = $stmt->fetchAll();

$page_title = "Manage Members - " . htmlspecialchars($group['name']);
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-people"></i> Manage Members</h2>
        <div>
            <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#inviteModal">
                <i class="bi bi-envelope-plus"></i> Invite Members
            </button>
            <a href="group_view.php?id=<?php echo $groupId; ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Group
            </a>
        </div>
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

            <?php if (!empty($pendingInvitations)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-envelope"></i> Pending Invitations
                            <span class="badge bg-light text-dark"><?php echo count($pendingInvitations); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($pendingInvitations as $invitation): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 p-3 border rounded">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($invitation['display_name'] ?? $invitation['username']); ?></h6>
                                    <small class="text-muted">@<?php echo htmlspecialchars($invitation['username']); ?></small>
                                    <div class="small text-muted mt-1">
                                        Invited: <?php echo timeAgo($invitation['created_at']); ?>
                                    </div>
                                </div>
                                <div>
                                    <span class="badge bg-info">Invitation Sent</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
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

<!-- Invite Members Modal -->
<div class="modal fade" id="inviteModal" tabindex="-1" aria-labelledby="inviteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inviteModalLabel">
                    <i class="bi bi-envelope-plus"></i> Invite Members to Group
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="searchUser" class="form-label">Search for users to invite:</label>
                    <input type="text" class="form-control" id="searchUser" placeholder="Enter username, display name, or email">
                    <small class="text-muted">Start typing to search for users</small>
                </div>
                <div id="searchResults">
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-search"></i> Search for users to invite
                    </div>
                </div>
                <div id="inviteStatus" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
let searchTimeout;

document.getElementById('searchUser').addEventListener('input', function() {
    const searchTerm = this.value.trim();
    
    clearTimeout(searchTimeout);
    
    if (searchTerm.length < 2) {
        document.getElementById('searchResults').innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-search"></i> Search for users to invite</div>';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        searchUsers(searchTerm);
    }, 300);
});

function searchUsers(searchTerm) {
    const searchResults = document.getElementById('searchResults');
    searchResults.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm" role="status"></div> Searching...</div>';
    
    fetch('../ajax/search_users.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'group_id=<?php echo $groupId; ?>&search=' + encodeURIComponent(searchTerm)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.users.length === 0) {
                searchResults.innerHTML = '<div class="alert alert-info">No users found matching your search.</div>';
            } else {
                let html = '<div class="list-group">';
                data.users.forEach(user => {
                    html += `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${escapeHtml(user.display_name || user.username)}</strong>
                                <div class="small text-muted">@${escapeHtml(user.username)}</div>
                            </div>
                            <button class="btn btn-sm btn-primary" onclick="inviteUser(${user.id}, '${escapeHtml(user.username)}')">
                                <i class="bi bi-envelope-plus"></i> Invite
                            </button>
                        </div>
                    `;
                });
                html += '</div>';
                searchResults.innerHTML = html;
            }
        } else {
            searchResults.innerHTML = '<div class="alert alert-danger">' + escapeHtml(data.message) + '</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        searchResults.innerHTML = '<div class="alert alert-danger">An error occurred while searching.</div>';
    });
}

function inviteUser(userId, username) {
    const inviteStatus = document.getElementById('inviteStatus');
    inviteStatus.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Sending invitation...</div>';
    
    fetch('../ajax/invite_member.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'group_id=<?php echo $groupId; ?>&invitee_id=' + userId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            inviteStatus.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' + escapeHtml(data.message) + '</div>';
            // Refresh the search to remove the invited user
            const searchTerm = document.getElementById('searchUser').value.trim();
            if (searchTerm.length >= 2) {
                setTimeout(() => searchUsers(searchTerm), 1000);
            }
            // Reload page after 2 seconds to show updated invitations list
            setTimeout(() => location.reload(), 2000);
        } else {
            inviteStatus.innerHTML = '<div class="alert alert-danger"><i class="bi bi-x-circle"></i> ' + escapeHtml(data.message) + '</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        inviteStatus.innerHTML = '<div class="alert alert-danger">An error occurred while sending the invitation.</div>';
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include '../includes/footer.php'; ?>
