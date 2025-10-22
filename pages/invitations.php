<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$currentUserId = getCurrentUserId();

// Get pending invitations for the user
$pendingInvitations = getPendingInvitationsForUser($currentUserId);

$page_title = "My Invitations - NicheNest";
include '../includes/header.php';
?>

<div class="container mt-4">
    <h2><i class="bi bi-envelope"></i> My Group Invitations</h2>
    
    <?php if (empty($pendingInvitations)): ?>
        <div class="alert alert-info mt-4">
            <i class="bi bi-info-circle"></i> You don't have any pending group invitations at this time.
        </div>
        <a href="groups.php" class="btn btn-primary mt-3">
            <i class="bi bi-people-fill"></i> Browse Groups
        </a>
    <?php else: ?>
        <div class="row mt-4">
            <?php foreach ($pendingInvitations as $invitation): ?>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title">
                                    <?php echo htmlspecialchars($invitation['group_name']); ?>
                                </h5>
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-envelope"></i> Pending
                                </span>
                            </div>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars(substr($invitation['group_description'], 0, 150)); ?>
                                <?php if (strlen($invitation['group_description']) > 150): ?>...<?php endif; ?>
                            </p>
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="bi bi-person"></i> Invited by: 
                                    <strong><?php echo htmlspecialchars($invitation['inviter_name'] ?? $invitation['inviter_username']); ?></strong>
                                </small><br>
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> <?php echo timeAgo($invitation['created_at']); ?>
                                </small>
                            </div>
                            <div class="d-flex gap-2">
                                <form method="POST" action="../ajax/manage_invitation.php" class="flex-grow-1">
                                    <input type="hidden" name="invitation_id" value="<?php echo $invitation['id']; ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bi bi-check-circle"></i> Accept
                                    </button>
                                </form>
                                <form method="POST" action="../ajax/manage_invitation.php" class="flex-grow-1"
                                      onsubmit="return confirm('Are you sure you want to decline this invitation?');">
                                    <input type="hidden" name="invitation_id" value="<?php echo $invitation['id']; ?>">
                                    <input type="hidden" name="action" value="decline">
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i class="bi bi-x-circle"></i> Decline
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
