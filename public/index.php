<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = "NicheNest - Micro Community Platform";
include __DIR__ . '/../includes/header.php';
$stats = getCommunityStats();
?>
<main id="main-content" role="main">
    <header class="hero-section bg-primary text-white text-center py-5 mb-4" role="banner">
        <div class="container">
            <h1 class="display-4">Welcome to NicheNest</h1>
            <p class="lead">Your micro-community platform for focused groups</p>
            <?php if (!isLoggedIn()): ?>
                <div class="hero-actions" role="group" aria-label="Get started actions">
                    <a href="/pages/register.php"
                        class="btn btn-light btn-lg me-2"
                        aria-describedby="join-description">Join Community</a>
                    <a href="/pages/login.php"
                        class="btn btn-outline-light btn-lg"
                        aria-describedby="login-description">Login</a>
                    <div class="visually-hidden">
                        <span id="join-description">Create a new account to start participating in the community</span>
                        <span id="login-description">Sign in to your existing account</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="hero-actions">
                    <a href="/pages/posts.php"
                        class="btn btn-light btn-lg"
                        aria-label="Go to posts page to view community content">View Posts</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="container">
        <div class="row">
            <!-- Main content area -->
            <div class="col-lg-8">
                <section aria-labelledby="recent-activity-heading">
                    <h2 id="recent-activity-heading">Recent Activity</h2>

                    <?php if (isLoggedIn()): ?>
                        <article class="card mb-4" role="complementary" aria-labelledby="quick-post-heading">
                            <div class="card-body">
                                <h3 id="quick-post-heading" class="h5">Quick Post</h3>
                                <form action="/pages/posts.php" method="POST" aria-label="Create a new post">
                                    <div class="mb-3">
                                        <label for="post-content" class="visually-hidden">Post content</label>
                                        <textarea name="content"
                                            id="post-content"
                                            class="form-control"
                                            rows="3"
                                            placeholder="What's on your mind?"
                                            aria-describedby="post-help"></textarea>
                                        <div id="post-help" class="form-text visually-hidden">
                                            Share your thoughts with the community
                                        </div>
                                    </div>
                                    <button type="submit"
                                        class="btn btn-primary"
                                        aria-label="Submit your post to the community">Post</button>
                                </form>
                            </div>
                        </article>
                    <?php endif; ?>

                    <article class="card" role="complementary">
                        <div class="card-body">
                            <h3 class="card-title h5">Welcome to Your Community</h3>
                            <p class="card-text">Connect with like-minded individuals, share ideas, and build meaningful relationships in focused interest groups.</p>
                            <a href="/pages/posts.php"
                                class="btn btn-primary"
                                aria-label="Browse all community posts and discussions">Explore Posts</a>
                        </div>
                    </article>
                </section>
            </div>

            <!-- Sidebar -->
            <aside class="col-lg-4" role="complementary" aria-labelledby="sidebar-heading">
                <div class="visually-hidden">
                    <h2 id="sidebar-heading">Community Information</h2>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h5>Community Stats</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><i class="bi bi-people me-2 text-primary"></i><strong>Active Members:</strong> <?php echo number_format($stats['active_members']); ?></p>
                        <p class="mb-2"><i class="bi bi-chat-left-text me-2 text-success"></i><strong>Total Posts:</strong> <?php echo number_format($stats['total_posts']); ?></p>
                        <p class="mb-0"><i class="bi bi-collection me-2 text-info"></i><strong>Groups:</strong> <?php echo number_format($stats['total_groups']); ?></p>
                        <div class="mt-3">
                            <a href="/pages/discover_groups.php" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-compass"></i> Discover Groups
                            </a>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>