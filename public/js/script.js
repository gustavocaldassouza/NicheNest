document.addEventListener('DOMContentLoaded', function () {
    initializeAccessibility();
    initializeNotifications();

    const likeButtons = document.querySelectorAll('.like-btn');

    likeButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();

            const postId = this.getAttribute('data-post-id');
            const isCurrentlyLiked = this.getAttribute('data-liked') === 'true';

            this.disabled = true;
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i><span class="like-text">Loading...</span>';

            const formData = new FormData();
            formData.append('post_id', postId);

            fetch('/ajax/like_post.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const isLiked = data.liked;
                        this.innerHTML = `
                            <i class="bi bi-heart${isLiked ? '-fill' : ''} me-1"></i>
                            <span class="like-text">${isLiked ? 'Unlike' : 'Like'}</span>
                            <span class="badge bg-${isLiked ? 'danger' : 'success'} ms-1 rounded-pill" style="font-size: 0.7em;">
                                ${data.likeCount}
                            </span>
                        `;
                        this.className = `btn btn-sm btn-outline-${isLiked ? 'danger' : 'success'} like-btn position-relative`;
                        this.setAttribute('data-liked', isLiked.toString());

                        this.classList.add('liked');
                        setTimeout(() => {
                            this.classList.remove('liked');
                        }, 300);

                        showAlert(data.message, 'success');
                    } else {
                        showAlert(data.message, 'danger');
                        this.innerHTML = originalHTML;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred. Please try again.', 'danger');
                    this.innerHTML = originalHTML;
                })
                .finally(() => {
                    this.disabled = false;
                });
        });
    });

    const replyToggleButtons = document.querySelectorAll('.reply-toggle');
    replyToggleButtons.forEach(button => {
        button.addEventListener('click', function () {
            const postId = this.getAttribute('data-post-id');
            const replyForm = document.getElementById('reply-form-' + postId);

            if (replyForm) {
                replyForm.classList.toggle('d-none');
                if (!replyForm.classList.contains('d-none')) {
                    const textarea = replyForm.querySelector('textarea');
                    if (textarea) textarea.focus();
                }
            }
        });
    });

    const cancelReplyButtons = document.querySelectorAll('.cancel-reply');
    cancelReplyButtons.forEach(button => {
        button.addEventListener('click', function () {
            const replyForm = this.closest('.reply-form');
            if (replyForm) {
                replyForm.classList.add('d-none');
                const textarea = replyForm.querySelector('textarea');
                if (textarea) textarea.value = '';
            }
        });
    });

    // Handle reply form submission
    const replyForms = document.querySelectorAll('.reply-form');
    replyForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const postId = this.querySelector('input[name="post_id"]').value;
            const content = this.querySelector('textarea[name="reply_content"]').value.trim();

            if (!content) {
                showAlert('Reply content is required', 'danger');
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Submitting...';

            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('reply_content', content);

            fetch('/ajax/submit_reply.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text().then(text => {
                        if (!text.trim()) {
                            throw new Error('Empty response from server');
                        }
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Invalid JSON response:', text);
                            throw new Error('Invalid JSON response from server');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        addReplyToPost(postId, data.reply);
                        updateReplyCount(postId, data.replies_count);
                        this.querySelector('textarea').value = '';
                        this.classList.add('d-none');

                        showAlert(data.message, 'success');
                    } else {
                        showAlert(data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred. Please try again.', 'danger');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        });
    });

    const forms = document.querySelectorAll('form:not(.reply-form)');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const requiredFields = form.querySelectorAll('[required]');
            let hasErrors = false;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    hasErrors = true;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            const password = form.querySelector('input[name="password"]');
            const confirmPassword = form.querySelector('input[name="confirm_password"]');
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                confirmPassword.classList.add('is-invalid');
                hasErrors = true;
            }

            if (hasErrors) {
                e.preventDefault();
            }
        });
    });

    const alerts = document.querySelectorAll('.alert:not(.alert-danger)');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                alert.remove();
            }
        }, 5000);
    });

    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        if (textarea.hasAttribute('maxlength')) {
            const maxLength = textarea.getAttribute('maxlength');
            const counter = document.createElement('div');
            counter.className = 'form-text text-end';
            counter.innerHTML = `<span class="char-count">0</span>/${maxLength}`;
            textarea.parentNode.appendChild(counter);

            textarea.addEventListener('input', function () {
                const charCount = counter.querySelector('.char-count');
                charCount.textContent = this.value.length;

                if (this.value.length > maxLength * 0.9) {
                    charCount.className = 'char-count text-warning';
                } else {
                    charCount.className = 'char-count';
                }
            });
        }
    });

    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href').substring(1);
            
            // Skip empty anchors or just "#"
            if (!targetId || targetId === '') {
                return;
            }
            
            e.preventDefault();
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.classList.add('fade-in');
    });

    const destructiveButtons = document.querySelectorAll('[data-confirm]');
    destructiveButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            const confirmMessage = this.getAttribute('data-confirm');
            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
    });

    // Attachments preview for post forms
    function setupAttachmentPreview(inputId, previewContainerId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const preview = document.getElementById(previewContainerId);
        const MAX_SIZE = 10 * 1024 * 1024; // 10MB
        const allowedTypes = new Set([
            'image/jpeg','image/jpg','image/png','image/gif','image/webp',
            'application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain','application/zip','application/x-zip-compressed'
        ]);

        input.addEventListener('change', function () {
            if (!preview) return;
            preview.innerHTML = '';
            const files = Array.from(input.files || []);
            const validFiles = [];
            files.forEach(file => {
                if (file.size > MAX_SIZE) {
                    showAlert(`${file.name} is larger than 10MB and was skipped.`, 'warning');
                    return;
                }
                if (!allowedTypes.has(file.type)) {
                    showAlert(`${file.name} has an unsupported type and was skipped.`, 'warning');
                    return;
                }
                validFiles.push(file);

                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.className = 'img-thumbnail';
                    img.style.maxWidth = '120px';
                    img.style.maxHeight = '120px';
                    const reader = new FileReader();
                    reader.onload = e => { img.src = e.target.result; };
                    reader.readAsDataURL(file);
                    preview.appendChild(img);
                } else {
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-secondary';
                    badge.textContent = file.name;
                    preview.appendChild(badge);
                }
            });

            // If some files invalid, keep only valid ones by reconstructing DataTransfer (best effort)
            if (validFiles.length !== files.length) {
                try {
                    const dt = new DataTransfer();
                    validFiles.forEach(f => dt.items.add(f));
                    input.files = dt.files;
                } catch (e) {
                    // Ignore if not supported
                }
            }
        });
    }

    setupAttachmentPreview('attachments', 'attachmentPreviews');
    setupAttachmentPreview('attachments', 'attachmentPreviewsGroup');

    // Handle post deletion
    const deletePostButtons = document.querySelectorAll('.delete-post-btn');
    deletePostButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
                return;
            }

            const postId = this.getAttribute('data-post-id');
            const postCard = this.closest('.card');
            
            this.disabled = true;
            const originalHTML = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

            const formData = new FormData();
            formData.append('post_id', postId);

            fetch('/ajax/delete_post.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    if (!text.trim()) {
                        throw new Error('Empty response from server');
                    }
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    // Fade out and remove the post card
                    postCard.style.transition = 'opacity 0.3s ease';
                    postCard.style.opacity = '0';
                    setTimeout(() => {
                        postCard.remove();
                    }, 300);
                    showAlert(data.message, 'success');
                } else {
                    showAlert(data.message, 'danger');
                    this.disabled = false;
                    this.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while deleting the post.', 'danger');
                this.disabled = false;
                this.innerHTML = originalHTML;
            });
        });
    });

});

function showAlert(message, type) {
    if (typeof type === 'undefined') {
        type = 'info';
    }
    
    const alertContainer = document.createElement('div');
    alertContainer.className = 'alert alert-' + type + ' alert-dismissible fade show';
    alertContainer.setAttribute('role', 'alert');
    alertContainer.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';

    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertContainer, container.firstChild);

        setTimeout(function() {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alertContainer);
                bsAlert.close();
            } else {
                alertContainer.remove();
            }
        }, 5000);
    }
}

function formatTimeAgo(timestamp) {
    const now = new Date();
    const time = new Date(timestamp);
    const diff = Math.floor((now - time) / 1000);

    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
    if (diff < 2592000) return Math.floor(diff / 86400) + ' days ago';

    return time.toLocaleDateString();
}

// Avatar preview function
function previewAvatar(event) {
    const file = event.target.files[0];
    if (file) {
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File is too large. Maximum size is 5MB.');
            event.target.value = '';
            return;
        }

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
            event.target.value = '';
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatarPreview');
            if (preview) {
                preview.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);
    }
}
function initializeNotifications() {
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationsList = document.getElementById('notificationsList');
    const notificationBadge = document.getElementById('notificationBadge');
    const markAllReadBtn = document.getElementById('markAllReadBtn');

    if (!notificationDropdown) return;

    notificationDropdown.addEventListener('click', function (e) {
        e.preventDefault();
        loadNotifications();
    });

    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function (e) {
            e.preventDefault();
            markAllNotificationsAsRead();
        });
    }

    loadNotifications();

    setInterval(loadNotifications, 30000);
}

function loadNotifications() {
    fetch('/ajax/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.unread_count);
                updateNotificationsList(data.notifications);
            } else {
                console.error('Failed to load notifications:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
        });
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (!badge) return;

    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'inline-block';
    } else {
        badge.style.display = 'none';
    }
}

function updateNotificationsList(notifications) {
    const list = document.getElementById('notificationsList');
    const markAllBtn = document.getElementById('markAllReadBtn');

    if (!list) return;

    if (notifications.length === 0) {
        list.innerHTML = '<li class="dropdown-item text-center text-muted"><i class="bi bi-bell-slash me-2"></i>No notifications</li>';
        if (markAllBtn) markAllBtn.style.display = 'none';
        return;
    }

    const hasUnread = notifications.some(n => !n.is_read);
    if (markAllBtn) {
        markAllBtn.style.display = hasUnread ? 'inline-block' : 'none';
    }

    list.innerHTML = notifications.map(notification => {
        const iconClass = notification.type === 'like' ? 'bi-heart-fill text-danger' :
            notification.type === 'reply' ? 'bi-reply-fill text-primary' :
                'bi-bell-fill text-info';

        const unreadClass = !notification.is_read ? 'fw-bold bg-light' : '';

        return `
            <li class="dropdown-item notification-item ${unreadClass}" data-notification-id="${notification.id}" data-post-id="${notification.post_id}">
                <div class="d-flex align-items-start">
                    <i class="bi ${iconClass} me-2 mt-1"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${notification.title}</div>
                        <div class="text-muted small">${notification.message}</div>
                        <div class="text-muted small">${notification.time_ago}</div>
                    </div>
                    ${!notification.is_read ? '<div class="badge bg-primary rounded-pill ms-2">New</div>' : ''}
                </div>
            </li>
        `;
    }).join('');

    list.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function () {
            const notificationId = this.getAttribute('data-notification-id');
            const postId = this.getAttribute('data-post-id');

            markNotificationAsRead(notificationId);

            if (postId) {
                window.location.href = `/pages/posts.php#post-${postId}`;
            }
        });
    });
}

function markNotificationAsRead(notificationId) {
    const formData = new FormData();
    formData.append('notification_id', notificationId);

    fetch('/ajax/mark_notification_read.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.unread_count);
                loadNotifications();
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
}

function markAllNotificationsAsRead() {
    loadNotifications();
}

function addReplyToPost(postId, reply) {
    // Find the replies container for this post using the reply button specifically
    const replyButton = document.querySelector(`.reply-toggle[data-post-id="${postId}"]`);
    if (!replyButton) {
        console.error('Reply button not found for post ID:', postId);
        return;
    }
    const postCard = replyButton.closest('.card');
    if (!postCard) {
        console.error('Post card not found for post ID:', postId);
        return;
    }

    let repliesContainer = postCard.querySelector('.replies-container');

    // Create replies container if it doesn't exist
    if (!repliesContainer) {
        repliesContainer = document.createElement('div');
        repliesContainer.className = 'mt-3 replies-container';
        repliesContainer.innerHTML = '<h6>Replies:</h6>';

        // Insert after the reply form
        const replyForm = postCard.querySelector('.reply-form');
        if (replyForm) {
            replyForm.parentNode.insertBefore(repliesContainer, replyForm.nextSibling);
        }
    }

    // Create reply element
    const replyElement = document.createElement('div');
    replyElement.className = 'border-start border-3 border-light ps-3 mb-2 reply-item';
    replyElement.innerHTML = `
        <small class="text-muted">
            <strong>${reply.author}</strong>
            ${reply.time_ago}
        </small>
        <p class="mb-0">${reply.content.replace(/\n/g, '<br>')}</p>
    `;

    // Add to replies container
    repliesContainer.appendChild(replyElement);

    // Add fade-in animation
    replyElement.classList.add('fade-in');
}

function updateReplyCount(postId, count) {
    const replyBtn = document.querySelector(`.reply-toggle[data-post-id="${postId}"]`);
    if (replyBtn) {
        // Update button text to show count if there are replies
        if (count > 0) {
            replyBtn.innerHTML = `<i class="bi bi-reply me-1"></i> Reply (${count})`;
        } else {
            replyBtn.innerHTML = `<i class="bi bi-reply me-1"></i> Reply`;
        }
    } else {
        console.error('Reply button not found for updating count, post ID:', postId);
    }
}

function initializeAccessibility() {
    setupKeyboardNavigation();
    setupAriaLiveRegions();
    enhanceFormAccessibility();
    setupFocusManagement();
}

function setupKeyboardNavigation() {
    // Handle escape key for dropdowns and modals
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            // Close any open dropdowns
            const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(dropdown => {
                const toggle = dropdown.previousElementSibling;
                if (toggle && toggle.hasAttribute('data-bs-toggle')) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                        const instance = bootstrap.Dropdown.getInstance(toggle);
                        if (instance) instance.hide();
                    }
                }
            });
        }
    });

    // Improve tab navigation order
    const interactiveElements = document.querySelectorAll('a, button, input, textarea, select, [tabindex]');
    interactiveElements.forEach((element, index) => {
        if (!element.hasAttribute('tabindex') && !element.disabled) {
            element.setAttribute('tabindex', '0');
        }
    });

    // Skip to main content functionality
    const skipLink = document.querySelector('.visually-hidden-focusable');
    if (skipLink) {
        skipLink.addEventListener('click', function (e) {
            e.preventDefault();
            const mainContent = document.getElementById('main-content');
            if (mainContent) {
                mainContent.focus();
                mainContent.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
}

function setupAriaLiveRegions() {
    // Create or enhance existing live regions
    let alertContainer = document.getElementById('alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alert-container';
        alertContainer.setAttribute('aria-live', 'polite');
        alertContainer.setAttribute('aria-atomic', 'true');
        alertContainer.className = 'visually-hidden';
        document.body.appendChild(alertContainer);
    }

    // Override the existing showAlert function to use ARIA live regions
    window.showAlert = function (message, type = 'info') {
        // Visual alert (existing functionality)
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close alert"></button>
            </div>
        `;

        const container = document.querySelector('.container');
        if (container) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = alertHtml;
            container.insertBefore(tempDiv.firstElementChild, container.firstElementChild);
        }

        // Announce to screen readers
        alertContainer.textContent = message;

        // Clear the announcement after a delay
        setTimeout(() => {
            alertContainer.textContent = '';
        }, 5000);
    };
}

function enhanceFormAccessibility() {
    // Enhance form accessibility without visual indicators
    const requiredFields = document.querySelectorAll('input[required], textarea[required], select[required]');
    requiredFields.forEach(field => {
        // Add ARIA describedby if help text exists
        const helpText = field.nextElementSibling;
        if (helpText && helpText.classList.contains('form-text')) {
            const helpId = field.id + '-help';
            helpText.id = helpId;
            field.setAttribute('aria-describedby', helpId);
        }
    });

    // Enhance form validation messages
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const invalidFields = form.querySelectorAll(':invalid');
            if (invalidFields.length > 0) {
                e.preventDefault();

                // Focus on first invalid field
                invalidFields[0].focus();

                // Announce validation error
                const message = `Form has ${invalidFields.length} error${invalidFields.length > 1 ? 's' : ''}. Please review and correct.`;
                announceToScreenReader(message);
            }
        });
    });
}

function setupFocusManagement() {
    // Ensure focus is visible when navigating with keyboard
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Tab') {
            document.body.classList.add('keyboard-navigation');
        }
    });

    document.addEventListener('mousedown', function () {
        document.body.classList.remove('keyboard-navigation');
    });

    // Manage focus for dynamic content
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) { // Element node
                        const focusableElements = node.querySelectorAll('a, button, input, textarea, select, [tabindex]');
                        focusableElements.forEach(element => {
                            if (!element.hasAttribute('tabindex') && !element.disabled) {
                                element.setAttribute('tabindex', '0');
                            }
                        });
                    }
                });
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

function announceToScreenReader(message) {
    const alertContainer = document.getElementById('alert-container');
    if (alertContainer) {
        alertContainer.textContent = message;
        setTimeout(() => {
            alertContainer.textContent = '';
        }, 3000);
    }
}

// Override like button functionality to include accessibility improvements
function enhanceLikeButton(button) {
    const postId = button.getAttribute('data-post-id');
    const isLiked = button.getAttribute('data-liked') === 'true';

    // Add proper ARIA labels
    button.setAttribute('aria-label', `${isLiked ? 'Unlike' : 'Like'} this post`);
    button.setAttribute('aria-pressed', isLiked.toString());

    // Add keyboard support
    button.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            button.click();
        }
    });
}

// Apply enhancements to all like buttons
document.querySelectorAll('.like-btn').forEach(enhanceLikeButton);
