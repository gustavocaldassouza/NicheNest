<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$page_title = "Privacy Policy - NicheNest";
include '../includes/header.php';
?>

<main class="container py-5">
    <h1>Privacy Policy</h1>
    <p>At <?= htmlspecialchars('NicheNest') ?>, we prioritize your privacy and are committed to protecting your personal information in accordance with applicable laws such as GDPR and CCPA.</p>

    <h2>Information Collection</h2>
    <p>We collect personal data including username, email, and profile details when you register. We also record usage data such as login/logout events, posts, and interactions for moderation and audit purposes.</p>

    <h2>Use of Information</h2>
    <p>Your information is used to provide community features, secure your account, monitor activity for abuse, and communicate important updates.</p>

    <h2>Data Security</h2>
    <p>We use encryption, hashed passwords (bcrypt), prepared statements to prevent SQL injections, and secure session handling to safeguard your data.</p>

    <h2>Third-Party Services</h2>
    <p>We do not share your data with third parties except for hosting services or legal obligations.</p>

    <h2>Your Rights</h2>
    <p>You may access, update, or request deletion of your personal data by contacting support@getnichenest.com (replace with your real contact).</p>

    <h2>Cookies</h2>
    <p>We use cookies for session management and analytics. You can disable cookies in your browser settings but some features may be affected.</p>

    <h2>Changes to Policy</h2>
    <p>This policy may be updated occasionally. The revision date is shown here: <?= date('F j, Y') ?>.</p>

</main>

<?php include '../includes/footer.php'; ?>