// NicheNest JavaScript functionality

document.addEventListener('DOMContentLoaded', function() {

    // Reply form toggle functionality
    const replyToggleButtons = document.querySelectorAll('.reply-toggle');
    replyToggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const replyForm = document.getElementById('reply-form-' + postId);

            if (replyForm) {
                replyForm.classList.toggle('d-none');
                if (!replyForm.classList.contains('d-none')) {
                    // Focus on textarea when form opens
                    const textarea = replyForm.querySelector('textarea');
                    if (textarea) textarea.focus();
                }
            }
        });
    });

    // Cancel reply functionality
    const cancelReplyButtons = document.querySelectorAll('.cancel-reply');
    cancelReplyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const replyForm = this.closest('.reply-form');
            if (replyForm) {
                replyForm.classList.add('d-none');
                // Clear the textarea
                const textarea = replyForm.querySelector('textarea');
                if (textarea) textarea.value = '';
            }
        });
    });

    // Form validation enhancement
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
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

            // Password confirmation check
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

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-danger)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Character counter for textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        if (textarea.hasAttribute('maxlength')) {
            const maxLength = textarea.getAttribute('maxlength');
            const counter = document.createElement('div');
            counter.className = 'form-text text-end';
            counter.innerHTML = `<span class="char-count">0</span>/${maxLength}`;
            textarea.parentNode.appendChild(counter);

            textarea.addEventListener('input', function() {
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

    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
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

    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.classList.add('fade-in');
    });

    // Confirmation dialogs for destructive actions
    const destructiveButtons = document.querySelectorAll('[data-confirm]');
    destructiveButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const confirmMessage = this.getAttribute('data-confirm');
            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
    });

});

// Utility functions
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

        // Auto-hide after 5 seconds
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