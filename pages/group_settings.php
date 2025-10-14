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
    
    // Check if group name already exists (excluding current group)
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE name = ? AND id != ?");
        $stmt->execute([$name, $groupId]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'A group with this name already exists';
        }
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE `groups` SET name = ?, description = ?, privacy = ? WHERE id = ?");
            $stmt->execute([$name, $description, $privacy, $groupId]);
            
            setFlashMessage('Group settings updated successfully!', 'success');
            $group['name'] = $name;
            $group['description'] = $description;
            $group['privacy'] = $privacy;
            $success = true;
        } catch (Exception $e) {
            $errors[] = 'An error occurred while updating the group. Please try again.';
        }
    }
}

$page_title = "Group Settings - " . htmlspecialchars($group['name']);
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-gear"></i> Group Settings</h3>
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
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> Group settings updated successfully!
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Group Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($group['name']); ?>" 
                                   required maxlength="100">
                            <div class="form-text">Choose a unique name for your group (3-100 characters)</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="4" required><?php echo htmlspecialchars($group['description']); ?></textarea>
                            <div class="form-text">Describe what your group is about</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Privacy Setting *</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="privacy" id="privacy_public" 
                                       value="public" <?php echo $group['privacy'] === 'public' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="privacy_public">
                                    <i class="bi bi-globe"></i> <strong>Public</strong>
                                    <div class="text-muted small">Anyone can see and join this group</div>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="radio" name="privacy" id="privacy_private" 
                                       value="private" <?php echo $group['privacy'] === 'private' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="privacy_private">
                                    <i class="bi bi-lock"></i> <strong>Private</strong>
                                    <div class="text-muted small">Only members can see this group. Join requests require approval.</div>
                                </label>
                            </div>
                            <?php if ($group['privacy'] === 'public'): ?>
                                <div class="alert alert-warning mt-2">
                                    <i class="bi bi-exclamation-triangle"></i> 
                                    <strong>Note:</strong> Changing to private will hide this group from non-members.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="group_view.php?id=<?php echo $groupId; ?>" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Group
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
