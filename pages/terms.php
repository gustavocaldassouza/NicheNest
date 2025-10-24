<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Terms of Service - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<main class="container py-5" id="main-content" role="main">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="card-title h3">Terms of Service</h1>
                    <p class="text-muted">Welcome to <?php echo htmlspecialchars(APP_NAME); ?>. By using our platform, you agree to comply with and be bound by these terms.</p>

                    <section id="responsibilities" class="mt-4">
                        <h2 class="h5">User Responsibilities</h2>
                        <p>You agree to use the platform respectfully and lawfully, refraining from posting harmful or illegal content.</p>
                    </section>

                    <section id="security" class="mt-3">
                        <h2 class="h5">Account Security</h2>
                        <p>Maintain confidentiality of your login credentials. Notify us immediately of unauthorized access.</p>
                    </section>

                    <section id="moderation" class="mt-3">
                        <h2 class="h5">Content Moderation</h2>
                        <p>Admins have the right to flag, remove content, suspend accounts violating these terms.</p>
                    </section>

                    <section id="liability" class="mt-3">
                        <h2 class="h5">Limitation of Liability</h2>
                        <p>The platform is provided “as is” without warranties. We are not liable for user-generated content.</p>
                    </section>

                    <section id="changes" class="mt-3">
                        <h2 class="h5">Changes to Terms</h2>
                        <p>We reserve the right to update these terms. Continued use after updates constitutes acceptance.</p>
                    </section>

                    <div class="mt-4">
                        <a href="/pages/privacy.php" class="btn btn-outline-secondary btn-sm">View Privacy Policy</a>
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
                            <a class="nav-link" href="#responsibilities">User Responsibilities</a>
                            <a class="nav-link" href="#security">Account Security</a>
                            <a class="nav-link" href="#moderation">Content Moderation</a>
                            <a class="nav-link" href="#liability">Limitation of Liability</a>
                            <a class="nav-link" href="#changes">Changes to Terms</a>
                        </nav>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body small text-muted">
                        <strong>Contact</strong>
                        <p class="mb-0">Questions about terms? Email <a href="mailto:support@getnichenest.com">support@getnichenest.com</a></p>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>
