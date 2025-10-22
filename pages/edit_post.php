<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUserId = getCurrentUserId();
$errors = [];
$success = '';

if (!$postId) {
    setFlashMessage('Invalid post ID', 'danger');
    redirect('posts.php');
}

// Get post details
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    setFlashMessage('Post not found', 'danger');
    redirect('posts.php');
}

// Check ownership
if (!isPostOwner($postId, $currentUserId)) {
    setFlashMessage('You do not have permission to edit this post', 'danger');
    redirect('posts.php');
}

// Handle post update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_post'])) {
    $title = sanitizeInput($_POST['title']);
    $content = sanitizeInput($_POST['content']);

    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    if (empty($content)) {
        $errors[] = 'Content is required';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Update post
            $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$title, $content, $postId]);

            // Handle new attachments
            if (!empty($_FILES['attachments']['name'][0])) {
                $uploadResult = uploadPostAttachments($postId, $_FILES['attachments']);
                if (!$uploadResult['success']) {
                    foreach ($uploadResult['errors'] as $err) {
                        $errors[] = $err;
                    }
                }
            }

            $pdo->commit();

            setFlashMessage('Post updated successfully!', 'success');

            // Redirect based on context
            if ($post['group_id']) {
                redirect('group_view.php?id=' . $post['group_id']);
            } else {
                redirect('posts.php');
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Failed to update post. Please try again.';
        }
    }
}

// Get existing attachments
$attachments = getPostAttachments($postId);

$page_title = "Edit Post - NicheNest";
include '../includes/header.php';
?>

<main id="main-content" role="main">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-pencil"></i> Edit Post</h5>
                        <a href="<?php echo $post['group_id'] ? 'group_view.php?id=' . $post['group_id'] : 'posts.php'; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
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

                        <form method="POST" action="" enctype="multipart/form-data" id="editPostForm">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title"
                                    value="<?php echo htmlspecialchars($post['title']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="content" class="form-label">Content</label>
                                <textarea class="form-control" id="content" name="content" rows="6" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                            </div>

                            <?php if (!empty($attachments)): ?>
                                <div class="mb-3">
                                    <label class="form-label">Current Attachments</label>
                                    <div id="currentAttachments">
                                        <?php foreach ($attachments as $att): ?>
                                            <div class="attachment-item border rounded p-2 mb-2 d-flex justify-content-between align-items-center" data-attachment-id="<?php echo $att['id']; ?>">
                                                <div class="d-flex align-items-center flex-grow-1">
                                                    <?php if ($att['type'] === 'image'): ?>
                                                        <img src="<?php echo htmlspecialchars($att['file_path']); ?>" alt="Attachment" class="img-thumbnail me-2" style="max-width: 80px; max-height: 80px;">
                                                    <?php else: ?>
                                                        <i class="bi bi-paperclip me-2"></i>
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($att['original_name']); ?></span>
                                                    <small class="text-muted ms-2">(<?php echo round($att['size'] / 1024); ?> KB)</small>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-attachment-btn" data-attachment-id="<?php echo $att['id']; ?>">
                                                    <i class="bi bi-trash"></i> Remove
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="attachments" class="form-label">Add New Attachments</label>
                                <input class="form-control" type="file" id="attachments" name="attachments[]" multiple
                                    accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain,application/zip" />
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> Select multiple files (up to 10MB each). Images and documents (PDF, Word, Excel, TXT, ZIP) are allowed.
                                </div>
                                <div id="attachmentPreviewsEdit" class="mt-2 d-flex flex-wrap gap-2"></div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" name="update_post" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Update Post
                                </button>
                                <a href="<?php echo $post['group_id'] ? 'group_view.php?id=' . $post['group_id'] : 'posts.php'; ?>" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Setup attachment preview for new files
        const attachmentInput = document.getElementById('attachments');
        const previewContainer = document.getElementById('attachmentPreviewsEdit');

        if (attachmentInput && previewContainer) {
            const MAX_SIZE = 10 * 1024 * 1024; // 10MB
            const allowedTypes = new Set([
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
                'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain', 'application/zip', 'application/x-zip-compressed'
            ]);

            attachmentInput.addEventListener('change', function() {
                previewContainer.innerHTML = '';
                const files = Array.from(this.files || []);

                files.forEach(file => {
                    if (file.size > MAX_SIZE) {
                        showAlert(`${file.name} is larger than 10MB and will be skipped.`, 'warning');
                        return;
                    }
                    if (!allowedTypes.has(file.type)) {
                        showAlert(`${file.name} has an unsupported type and will be skipped.`, 'warning');
                        return;
                    }

                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.className = 'img-thumbnail';
                        img.style.maxWidth = '120px';
                        img.style.maxHeight = '120px';
                        const reader = new FileReader();
                        reader.onload = e => {
                            img.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                        previewContainer.appendChild(img);
                    } else {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-secondary me-1';
                        badge.textContent = file.name;
                        previewContainer.appendChild(badge);
                    }
                });
            });
        }

        // Handle attachment deletion
        const deleteButtons = document.querySelectorAll('.delete-attachment-btn');
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Are you sure you want to remove this attachment?')) {
                    return;
                }

                const attachmentId = this.getAttribute('data-attachment-id');
                const attachmentItem = this.closest('.attachment-item');

                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

                fetch('/ajax/delete_attachment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'attachment_id=' + encodeURIComponent(attachmentId)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            attachmentItem.remove();
                            showAlert(data.message, 'success');
                        } else {
                            showAlert(data.message, 'danger');
                            this.disabled = false;
                            this.innerHTML = '<i class="bi bi-trash"></i> Remove';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('An error occurred. Please try again.', 'danger');
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-trash"></i> Remove';
                    });
            });
        });

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            document.querySelector('.card-body').insertBefore(alertDiv, document.querySelector('form'));
            setTimeout(() => alertDiv.remove(), 5000);
        }
    });
</script>

<?php include '../includes/footer.php'; ?>