<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $privacy = $_POST['privacy'] === 'private' ? 'private' : 'public';
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Group name is required';
    } elseif (strlen($name) < 3) {
        $errors[] = 'Group name must be at least 3 characters';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Group name must be less than 100 characters';
    }
    
    if (empty($description)) {
        $errors[] = 'Group description is required';
    }
    
    // Check if group name already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'A group with this name already exists';
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Create the group
            $stmt = $pdo->prepare("INSERT INTO `groups` (name, description, owner_id, privacy) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, getCurrentUserId(), $privacy]);
            $groupId = $pdo->lastInsertId();
            
            // Add the owner as a member with owner role
            $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'owner')");
            $stmt->execute([$groupId, getCurrentUserId()]);
            
            $pdo->commit();
            
            setFlashMessage('Group created successfully!', 'success');
            redirect('group_view.php?id=' . $groupId);
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'An error occurred while creating the group. Please try again.';
        }
    }
}

$page_title = "Create Group - NicheNest";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-plus-circle"></i> Create New Group</h3>
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

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Group Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                                   required maxlength="100">
                            <div class="form-text">Choose a unique name for your group (3-100 characters)</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="4" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            <div class="form-text">Describe what your group is about</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Privacy Setting *</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="privacy" id="privacy_public" 
                                       value="public" <?php echo (!isset($_POST['privacy']) || $_POST['privacy'] === 'public') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="privacy_public">
                                    <i class="bi bi-globe"></i> <strong>Public</strong>
                                    <div class="text-muted small">Anyone can see and join this group</div>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="radio" name="privacy" id="privacy_private" 
                                       value="private" <?php echo (isset($_POST['privacy']) && $_POST['privacy'] === 'private') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="privacy_private">
                                    <i class="bi bi-lock"></i> <strong>Private</strong>
                                    <div class="text-muted small">Only members can see this group. Join requests require approval.</div>
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="groups.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Group
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
