<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (isLoggedIn()) {
    redirect('/');
}

$errors = [];

// Rate limiting for login attempts (brute force protection)
$maxAttempts = 5;
$lockoutTime = 15 * 60; // 15 minutes
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Initialize login attempts tracking in session
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}

// Clean up old attempts
$_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function ($attempt) use ($lockoutTime) {
    return (time() - $attempt) < $lockoutTime;
});

// Check if locked out
$isLockedOut = count($_SESSION['login_attempts']) >= $maxAttempts;
if ($isLockedOut) {
    $remainingTime = ceil(($lockoutTime - (time() - $_SESSION['login_attempts'][0])) / 60);
    Logger::logSecurity('brute_force_lockout', Logger::WARNING, [
        'ip' => $clientIP,
        'attempts' => count($_SESSION['login_attempts'])
    ]);
    $errors[] = "Too many failed login attempts. Please try again in {$remainingTime} minutes.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLockedOut) {
    $identifier = sanitizeInput($_POST['identifier']);
    $password = $_POST['password'];

    if (empty($identifier)) {
        $errors[] = 'Username or email is required';
    }
    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, username, password, status FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && verifyPassword($password, $user['password'])) {
            // Check if user is suspended
            if ($user['status'] === 'suspended') {
                Logger::logSecurity('suspended_user_login_attempt', Logger::WARNING, [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'ip' => $clientIP
                ]);
                $errors[] = 'Your account has been suspended. Please contact support.';
            } else {
                // Clear login attempts on successful login
                $_SESSION['login_attempts'] = [];

                loginUser($user['id']);
                // if the user has just registered, show a welcome message instead of "Welcome back"
                if (!empty($_SESSION['just_registered'])) {
                    setFlashMessage('Welcome, ' . htmlspecialchars($user['username']) . '! Your account has been created.', 'success');
                    unset($_SESSION['just_registered']);
                } else {
                    setFlashMessage('Welcome back, ' . htmlspecialchars($user['username']) . '!', 'success');
                }
                redirect('/');
            }
        } else {
            // Track failed attempt
            $_SESSION['login_attempts'][] = time();
            $attemptCount = count($_SESSION['login_attempts']);

            // Log failed login attempt with security context
            Logger::logAuth('login', $identifier, false, [
                'reason' => 'invalid_credentials',
                'ip' => $clientIP,
                'attempt_count' => $attemptCount
            ]);

            // Log security event if nearing lockout
            if ($attemptCount >= 3) {
                Logger::logSecurity('multiple_failed_logins', Logger::WARNING, [
                    'identifier' => $identifier,
                    'ip' => $clientIP,
                    'attempts' => $attemptCount
                ]);
            }

            $errors[] = 'Invalid username/email or password';
            if ($attemptCount >= 3) {
                $remaining = $maxAttempts - $attemptCount;
                if ($remaining > 0) {
                    $errors[] = "Warning: {$remaining} attempt(s) remaining before lockout.";
                }
            }
        }
    }
}

$page_title = "Login - NicheNest";
include '../includes/header.php';
?>

<main id="main-content" role="main">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h1 class="text-center h3" id="login-heading">Login to NicheNest</h1>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger" role="alert" aria-labelledby="error-heading">
                                <h2 id="error-heading" class="visually-hidden">Login Errors</h2>
                                <ul class="mb-0" role="list">
                                    <?php foreach ($errors as $error): ?>
                                        <li role="listitem"><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST"
                            action=""
                            aria-labelledby="login-heading"
                            novalidate>
                            <div class="mb-3">
                                <label for="identifier" class="form-label">
                                    Username or Email
                                </label>
                                <input type="text"
                                    class="form-control <?php echo !empty($errors) && in_array('Username or email is required', $errors) ? 'is-invalid' : ''; ?>"
                                    id="identifier"
                                    name="identifier"
                                    value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                                    required
                                    aria-describedby="identifier-help <?php echo !empty($errors) ? 'identifier-error' : ''; ?>"
                                    autocomplete="username">
                                <div id="identifier-help" class="form-text">
                                    Enter your username or email address
                                </div>
                                <?php if (!empty($errors) && in_array('Username or email is required', $errors)): ?>
                                    <div id="identifier-error" class="invalid-feedback" role="alert">
                                        Username or email is required
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Password
                                </label>
                                <input type="password"
                                    class="form-control <?php echo !empty($errors) && in_array('Password is required', $errors) ? 'is-invalid' : ''; ?>"
                                    id="password"
                                    name="password"
                                    required
                                    aria-describedby="password-help <?php echo !empty($errors) ? 'password-error' : ''; ?>"
                                    autocomplete="current-password">
                                <div id="password-help" class="form-text">
                                    Enter your account password
                                </div>
                                <?php if (!empty($errors) && in_array('Password is required', $errors)): ?>
                                    <div id="password-error" class="invalid-feedback" role="alert">
                                        Password is required
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid">
                                <button type="submit"
                                    class="btn btn-primary"
                                    aria-describedby="login-button-help">
                                    Login
                                </button>
                                <div id="login-button-help" class="visually-hidden">
                                    Submit the form to login to your account
                                </div>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <p>Don't have an account?
                                <a href="register.php"
                                    aria-label="Go to registration page to create a new account">Register here</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>