<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars(NicheNest) ?> - Terms of Service</title>
    <link href="/public/css/style.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container py-5">
        <h1>Terms of Service</h1>
        <p>Welcome to <?= htmlspecialchars(NicheNest) ?>. By using our platform, you agree to comply with and be bound by these terms.</p>

        <h2>User Responsibilities</h2>
        <p>You agree to use the platform respectfully and lawfully, refraining from posting harmful or illegal content.</p>

        <h2>Account Security</h2>
        <p>Maintain confidentiality of your login credentials. Notify us immediately of unauthorized access.</p>

        <h2>Content Moderation</h2>
        <p>Admins have the right to flag, remove content, or suspend accounts violating these terms.</p>

        <h2>Limitation of Liability</h2>
        <p>The platform is provided “as is” without warranties. We are not liable for user-generated content.</p>

        <h2>Changes to Terms</h2>
        <p>We reserve the right to update these terms. Continued use after updates constitutes acceptance.</p>

        <h2>Contact</h2>
        <p>If you have questions, contact support@getnichenest.com (replace with your real contact).</p>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
