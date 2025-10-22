<?php
require_once __DIR__ . '/functions.php';

/**
 * Render a Community Stats card (Active Members, Total Posts, Groups)
 */
function renderCommunityStatsCard()
{
    $stats = getCommunityStats();
?>
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="bi bi-graph-up"></i> Community Stats</h5>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-people me-2 text-primary"></i>
                <div><strong>Active Members:</strong> <?php echo number_format($stats['active_members']); ?></div>
            </div>
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-chat-left-text me-2 text-success"></i>
                <div><strong>Total Posts:</strong> <?php echo number_format($stats['total_posts']); ?></div>
            </div>
            <div class="d-flex align-items-center">
                <i class="bi bi-collection me-2 text-info"></i>
                <div><strong>Groups:</strong> <?php echo number_format($stats['total_groups']); ?></div>
            </div>
        </div>
    </div>
<?php
}
