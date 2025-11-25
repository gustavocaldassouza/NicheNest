<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$user = getCurrentUser();
$errors = [];
$success = '';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $display_name = sanitizeInput($_POST['display_name'] ?? '');
    $bio = sanitizeInput($_POST['bio'] ?? '');

    // Handle avatar upload using the helper function
    $avatarResult = null;
    if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatarResult = uploadAvatar($_FILES['avatar'], getCurrentUserId());
        if (!$avatarResult['success']) {
            $errors[] = $avatarResult['message'];
        }
    }

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate username
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    } elseif (strlen($username) > 50) {
        $errors[] = 'Username must be 50 characters or less';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores';
    } elseif ($username !== $user['username']) {
        // Check if username is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, getCurrentUserId()]);
        if ($stmt->fetch()) {
            $errors[] = 'Username already taken. Please choose another one.';
        }
    }

    if (empty($display_name)) {
        $errors[] = 'Display name is required';
    }

    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = 'Current password is required to change password';
        } else {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([getCurrentUserId()]);
            $user_data = $stmt->fetch();

            if (!verifyPassword($current_password, $user_data['password'])) {
                $errors[] = 'Current password is incorrect';
            } elseif (strlen($new_password) < 6) {
                $errors[] = 'New password must be at least 6 characters';
            } elseif ($new_password !== $confirm_password) {
                $errors[] = 'New passwords do not match';
            }
        }
    }

    if (empty($errors)) {
        try {
            // Log para debug
            Logger::debug("Updating profile", [
                'user_id' => getCurrentUserId(),
                'username' => $username,
                'display_name' => $display_name,
                'bio' => $bio,
                'bio_length' => strlen($bio)
            ]);
            
            if (!empty($new_password)) {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, display_name = ?, bio = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $display_name, $bio, hashPassword($new_password), getCurrentUserId()]);
                $success = 'Profile and password updated successfully!';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, display_name = ?, bio = ? WHERE id = ?");
                $stmt->execute([$username, $display_name, $bio, getCurrentUserId()]);
                $success = 'Profile updated successfully!';
            }

            // Log success
            Logger::info("Profile updated successfully", [
                'user_id' => getCurrentUserId(),
                'username' => $username
            ]);

            // Add avatar upload success message
            if ($avatarResult && $avatarResult['success']) {
                $success .= ' Avatar uploaded successfully!';
            }

            // Refresh user data
            $user = getCurrentUser();
        } catch (PDOException $e) {
            Logger::error("Profile update failed", [
                'error' => $e->getMessage(),
                'user_id' => getCurrentUserId()
            ]);
            $errors[] = 'Failed to update profile. Please try again.';
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([getCurrentUserId()]);
$user_posts = $stmt->fetchAll();

$page_title = "My Profile - NicheNest";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="bi bi-person-circle"></i> Edit Profile</h4>
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
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username"
                                        value="<?php echo htmlspecialchars($_POST['username'] ?? $user['username']); ?>" 
                                        pattern="[a-zA-Z0-9_]{3,}" 
                                        title="Username can only contain letters, numbers, and underscores, and must be at least 3 characters"
                                        required>
                                    <div class="form-text">Must be unique, at least 3 characters</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email"
                                        value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                    <div class="form-text">Email cannot be changed</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="display_name" class="form-label">Display Name</label>
                            <input type="text" class="form-control" id="display_name" name="display_name"
                                value="<?php echo htmlspecialchars($_POST['display_name'] ?? $user['display_name'] ?? $user['username']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($_POST['bio'] ?? $user['bio'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="avatar" class="form-label">Avatar</label>
                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" onchange="previewAvatar(event)">
                            <div class="form-text">Max size: 5MB. Allowed formats: JPG, PNG, GIF, WebP</div>
                            <div class="mt-2">
                                <img id="avatarPreview" src="<?php echo htmlspecialchars(getUserAvatar(getCurrentUserId())); ?>"
                                    alt="Avatar" class="img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;">
                            </div>
                        </div>

                        <hr>
                        <h5>Change Password (Optional)</h5>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5>Profile Info</h5>
                </div>
                <div class="card-body">
                    <p><strong>Member since:</strong><br><?php echo formatTimestamp($user['created_at']); ?></p>
                    <p><strong>Total posts:</strong> <?php echo count($user_posts); ?></p>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5>Recent Posts</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($user_posts)): ?>
                        <p class="text-muted">No posts yet.</p>
                    <?php else: ?>
                        <?php foreach ($user_posts as $post): ?>
                            <div class="mb-2 pb-2 border-bottom">
                                <h6 class="mb-1"><?php echo htmlspecialchars($post['title']); ?></h6>
                                <small class="text-muted"><?php echo timeAgo($post['created_at']); ?></small>
                            </div>
                        <?php endforeach; ?>
                        <a href="posts.php" class="btn btn-sm btn-outline-primary">View All Posts</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>