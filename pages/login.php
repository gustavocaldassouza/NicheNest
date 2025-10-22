<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (isLoggedIn()) {
    redirect('/');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitizeInput($_POST['identifier']);
    $password = $_POST['password'];

    if (empty($identifier)) {
        $errors[] = 'Username or email is required';
    }
    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && verifyPassword($password, $user['password'])) {
            loginUser($user['id']);
            setFlashMessage('Welcome back, ' . htmlspecialchars($user['username']) . '!', 'success');
            redirect('/');
        } else {
            // Log failed login attempt
            Logger::logAuth('login', $identifier, false, [
                'reason' => 'invalid_credentials'
            ]);
            $errors[] = 'Invalid username/email or password';
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