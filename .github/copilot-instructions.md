# NicheNest Copilot Instructions

## Architecture Overview

NicheNest is a micro-community platform built with vanilla PHP 8.4+, MySQL 8.0, and Bootstrap 5. The architecture follows a simple MVC-like pattern without a framework:

- **Router**: `router.php` handles all requests, routing to `/pages/`, `/ajax/`, or `/public/` based on URL path
- **Database**: Single PDO connection initialized in `includes/config.php` with prepared statements throughout
- **Session-based auth**: Helper functions in `includes/auth.php` (`isLoggedIn()`, `requireLogin()`, `requireAdmin()`)
- **No build step**: Direct PHP execution via `php -S localhost:8000 router.php`

### Key Directory Structure

```
/includes/     - Core utilities (config, auth, functions, notifications)
/pages/        - Full page views (posts.php, admin.php, groups.php)
/ajax/         - JSON API endpoints (like_post.php, join_group.php)
/public/       - Static assets (CSS, JS, images, uploads)
/data/         - SQL schema and migrations
```

## Development Workflow

### Local Setup (Critical Commands)

```bash
# Start MySQL with Docker (recommended)
docker build -f Dockerfile.mysql -t nichenest-mysql .
docker run -d --name nichenest-mysql -p 3306:3306 nichenest-mysql

# Start PHP dev server (MUST use router.php for correct routing)
php -S localhost:8000 router.php

# Database migrations
docker exec -i nichenest-mysql mysql -uroot -proot nichenest < data/schema.sql
```

**Important**: Always use `router.php` as the server entry point. Direct file access won't work due to routing logic.

### Database Credentials

Default config in `includes/config.php`:
- Host: `127.0.0.1`, Database: `nichenest`
- User: `nichenest`, Password: `nichenest123`
- Root password: `root`

## Project-Specific Patterns

### Routing & File Structure

- **Pages** (`/pages/*.php`): Start with session, require includes, call `requireLogin()` or `requireAdmin()`, include `header.php`/`footer.php`
- **AJAX endpoints** (`/ajax/*.php`): Return JSON, use `header('Content-Type: application/json')`, check `isLoggedIn()` first
- **Path references**: Always use relative paths from current directory context (e.g., `../includes/config.php` from pages)

Example page structure:
```php
<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireLogin();

$page_title = "Page Title";
include '../includes/header.php';
// Page content
include '../includes/footer.php';
```

### Authentication & Authorization

- User roles: `'user'` or `'admin'` enum in `users.role`
- User status: `'active'` or `'suspended'` enum in `users.status`
- Group roles: `'owner'` or `'member'` enum in `group_members.role`
- Auth helpers: `isLoggedIn()`, `isAdmin()`, `requireLogin()`, `requireAdmin()`, `requireGroupOwner($groupId)`
- Suspended users checked via `isCurrentUserSuspended()` before posting

### Database Access Pattern

```php
// Always use prepared statements (never string concatenation)
$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? AND group_id IS NULL");
$stmt->execute([$userId]);
$posts = $stmt->fetchAll(); // or ->fetch() for single row
```

Global `$pdo` variable available after including `config.php`. Always use PDO::FETCH_ASSOC (default in config).

### Group Privacy Model

- **Public groups**: Visible to all, anyone can view posts
- **Private groups**: Only members see group, must request to join or be invited
- Check access: `canAccessGroup($groupId, $userId)` (in `includes/functions.php`)
- Membership: `isGroupMember($groupId, $userId)`, `isGroupOwner($groupId, $userId)`

### Moderation System

Admin panel (`pages/admin.php`) requires `role = 'admin'`. All moderation actions logged to `moderation_logs` table:
- Actions: `flag_post`, `unflag_post`, `delete_post`, `suspend_user`, `activate_user`
- Logging function: `logModerationAction($pdo, $action, $target_type, $target_id, $reason = null)`

### Frontend JavaScript Patterns

- Vanilla JS in `public/js/script.js` (no frameworks)
- AJAX requests use Fetch API: `fetch('/ajax/endpoint.php', {method: 'POST', body: formData})`
- Response handling: `response.json().then(data => { if (data.success) {...} })`
- Notifications fetched periodically via `initializeNotifications()` calling `/ajax/get_notifications.php`

### Security Conventions

- **Input sanitization**: Always use `sanitizeInput($data)` (trim + stripslashes + htmlspecialchars)
- **Output escaping**: Use `htmlspecialchars()` when echoing user content
- **Passwords**: `hashPassword()` wraps `password_hash()`, `verifyPassword()` wraps `password_verify()`
- **File uploads**: Stored in `public/uploads/`, validate file types before saving

## Common Tasks

### Adding a New Page

1. Create file in `/pages/your_page.php`
2. Follow page structure pattern (session, includes, auth check, header/footer)
3. Reference from router via `/pages/your_page.php`

### Adding a New AJAX Endpoint

1. Create file in `/ajax/your_endpoint.php`
2. Start with error suppression, session, includes
3. Always output JSON: `header('Content-Type: application/json'); echo json_encode(['success' => true]);`
4. Check authentication first: `if (!isLoggedIn()) { echo json_encode(['success' => false, 'message' => 'Not logged in']); exit; }`

### Database Schema Changes

1. Add migration SQL file to `/data/migration_*.sql`
2. Run via Docker: `docker exec -i nichenest-mysql mysql -uroot -proot nichenest < data/migration_file.sql`
3. Update `schema.sql` with new structure for fresh installs

## Gotchas & Edge Cases

- **Router context**: When including files via router, `chdir()` changes working directory. Use `dirname(__DIR__)` for path resolution in AJAX files.
- **Session handling**: `ob_start()` called in `config.php` for session management. AJAX files may need `ob_clean()` before JSON output.
- **Group posts**: Posts can be global (`group_id IS NULL`) or group-specific (`group_id = ?`). Filter accordingly.
- **Default credentials**: Admin user is `admin/admin123` - remind users to change this!
- **Bootstrap 5**: Uses Bootstrap Icons (`bi-*` classes) and utility classes extensively

## Testing

No automated test suite currently exists. Manual testing workflow:
1. Create test data via UI or directly in MySQL
2. Test authentication boundaries (logged out, regular user, admin, suspended)
3. Verify group privacy (public vs. private, member vs. non-member)
4. Check AJAX responses in browser devtools Network tab
