<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$page_title = "NicheNest - Micro Community Platform";
include 'includes/header.php';
?>
Like
<div class="hero-section bg-primary text-white text-center py-5 mb-4">
    <div class="container">
        <h1 class="display-4">Welcome to NicheNest</h1>
        <p class="lead">Your micro-community platform for focused groups</p>
        <?php if (!isLoggedIn()): ?>
            <a href="/pages/register.php" class="btn btn-light btn-lg me-2">Join Community</a>
            <a href="/pages/login.php" class="btn btn-outline-light btn-lg">Login</a>
        <?php else: ?>
            <a href="/pages/posts.php" class="btn btn-light btn-lg">View Posts</a>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <h2>Recent Activity</h2>
            <?php if (isLoggedIn()): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5>Quick Post</h5>
                        <form action="/pages/posts.php" method="POST">
                            <div class="mb-3">
                                <textarea name="content" class="form-control" rows="3" placeholder="What's on your mind?"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Post</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Welcome to Your Community</h5>
                    <p class="card-text">Connect with like-minded individuals, share ideas, and build meaningful relationships in focused interest groups.</p>
                    <a href="/pages/posts.php" class="btn btn-primary">Explore Posts</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5>Community Stats</h5>
                </div>
                <div class="card-body">
                    <p><strong>Active Members:</strong> Coming Soon</p>
                    <p><strong>Total Posts:</strong> Coming Soon</p>
                    <p><strong>Groups:</strong> Coming Soon</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>