document.addEventListener('DOMContentLoaded', function () {
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

    const forms = document.querySelectorAll('form');
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
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
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
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
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

});

function showAlert(message, type = 'info') {
    const alertContainer = document.createElement('div');
    alertContainer.className = `alert alert-${type} alert-dismissible fade show`;
    alertContainer.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertContainer, container.firstChild);

        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertContainer);
            bsAlert.close();
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