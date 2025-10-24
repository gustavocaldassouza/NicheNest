<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Privacy Policy - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<main class="container py-5" id="main-content" role="main">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="card-title h3">Privacy Policy</h1>
                    <p class="text-muted">At <?php echo htmlspecialchars(APP_NAME); ?>, we prioritize your privacy and are committed to protecting your personal information.</p>

                    <section id="collection" class="mt-4">
                        <h2 class="h5">Information Collection</h2>
                        <p>We collect personal data including username, email, and profile details when you register. We also record usage data such as login/logout events, posts, and interactions for moderation and audit purposes.</p>
                    </section>

                    <section id="use" class="mt-3">
                        <h2 class="h5">Use of Information</h2>
                        <p>Your information is used to provide community features, secure your account, monitor activity for abuse, and communicate important updates.</p>
                    </section>

                    <section id="security" class="mt-3">
                        <h2 class="h5">Data Security</h2>
                        <p>We use encryption, hashed passwords (bcrypt), prepared statements to prevent SQL injections, and secure session handling to safeguard your data.</p>
                    </section>

                    <section id="third-party" class="mt-3">
                        <h2 class="h5">Third-Party Services</h2>
                        <p>We do not share your data with third parties except for hosting services or legal obligations.</p>
                    </section>

                    <section id="rights" class="mt-3">
                        <h2 class="h5">Your Rights</h2>
                        <p>You may access, update, or request deletion of your personal data by contacting <a href="mailto:support@getnichenest.com">support@getnichenest.com</a> (replace with your real contact).</p>
                    </section>

                    <section id="cookies" class="mt-3">
                        <h2 class="h5">Cookies</h2>
                        <p>We use cookies for session management and analytics. You can disable cookies in your browser settings but some features may be affected.</p>
                    </section>

                    <section id="changes" class="mt-3">
                        <h2 class="h5">Changes to Policy</h2>
                        <p>This policy may be updated occasionally. The revision date is shown here: <strong><?php echo date('F j, Y'); ?></strong>.</p>
                    </section>

                    <div class="mt-4">
                        <a href="/pages/terms.php" class="btn btn-outline-secondary btn-sm">Read Terms of Service</a>
                        <a href="#" class="btn btn-link btn-sm ms-2">Back to top</a>
                    </div>
                </div>
            </div>
        </div>

        <aside class="col-lg-4">
            <div class="position-sticky" style="top: 90px;">
                <div class="card border-muted mb-3">
                    <div class="card-body">
                        <h6 class="mb-2">On this page</h6>
                        <nav class="nav flex-column small">
                            <a class="nav-link" href="#collection">Information Collection</a>
                            <a class="nav-link" href="#use">Use of Information</a>
                            <a class="nav-link" href="#security">Data Security</a>
                            <a class="nav-link" href="#third-party">Third-Party Services</a>
                            <a class="nav-link" href="#rights">Your Rights</a>
                            <a class="nav-link" href="#cookies">Cookies</a>
                            <a class="nav-link" href="#changes">Changes</a>
                        </nav>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body small text-muted">
                        <strong>Contact</strong>
                        <p class="mb-0">Questions about privacy? Email <a href="mailto:support@getnichenest.com">support@getnichenest.com</a></p>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>
