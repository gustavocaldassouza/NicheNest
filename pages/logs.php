<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$message = '';
$logContent = '';
$selectedFile = '';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$filterLevel = isset($_GET['level']) ? $_GET['level'] : '';

// Handle log file export
if (isset($_GET['export']) && isset($_GET['file'])) {
    $exportFile = basename($_GET['file']);
    $content = Logger::readLogFile($exportFile, 10000);
    if ($content !== false) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $exportFile . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }
}

// Handle log file cleanup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_old_logs'])) {
    $days = isset($_POST['days']) ? (int)$_POST['days'] : 30;
    if ($days > 0 && $days <= 365) {
        $userId = getCurrentUserId();
        if ($userId) {
            $count = Logger::clearOldLogs($days);
            $message = "Successfully deleted {$count} old log file(s).";
            Logger::logUserAction('clear_old_logs', $userId, [
                'days_old' => $days,
                'files_deleted' => $count
            ]);
        }
    } else {
        $message = "Invalid number of days. Must be between 1 and 365.";
    }
}

// Get list of log files
$logFiles = Logger::getLogFiles();

// Handle log file viewing
if (isset($_GET['file']) && !empty($_GET['file'])) {
    $selectedFile = basename($_GET['file']);
    $lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 100;
    $lines = max(10, min(1000, $lines)); // Clamp between 10 and 1000

    $logContent = Logger::readLogFile($selectedFile, $lines);

    if ($logContent === false) {
        $message = "Unable to read log file or file not found.";
        $logContent = '';
    } else {
        // Apply search filter
        if (!empty($searchTerm)) {
            $filteredLines = [];
            foreach (explode("\n", $logContent) as $line) {
                if (stripos($line, $searchTerm) !== false) {
                    $filteredLines[] = $line;
                }
            }
            $logContent = implode("\n", $filteredLines);
        }

        // Apply level filter
        if (!empty($filterLevel)) {
            $filteredLines = [];
            foreach (explode("\n", $logContent) as $line) {
                if (stripos($line, "[$filterLevel]") !== false) {
                    $filteredLines[] = $line;
                }
            }
            $logContent = implode("\n", $filteredLines);
        }
    }
}

// Get log statistics
$stats = Logger::getStats();

// Calculate security stats from recent logs
$securityEvents = 0;
$errorEvents = 0;
$todayFile = 'app_' . date('Y-m-d') . '.log';
if (in_array($todayFile, $logFiles)) {
    $todayContent = Logger::readLogFile($todayFile, 1000);
    if ($todayContent) {
        $securityEvents = substr_count($todayContent, '[SECURITY]') + substr_count($todayContent, 'SECURITY');
        $errorEvents = substr_count($todayContent, '[ERROR]') + substr_count($todayContent, '[CRITICAL]');
    }
}

$page_title = "System Logs - Admin Panel";
include '../includes/header.php';
?>

<style>
    .log-line {
        font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
        font-size: 0.8em;
    }

    .log-line-debug {
        color: #6c757d;
    }

    .log-line-info {
        color: #0d6efd;
    }

    .log-line-warning {
        color: #856404;
        background-color: #fff3cd;
    }

    .log-line-error {
        color: #721c24;
        background-color: #f8d7da;
    }

    .log-line-critical {
        color: #fff;
        background-color: #dc3545;
        font-weight: bold;
    }

    .log-line-security {
        color: #856404;
        background-color: #ffc107;
    }

    .log-viewer {
        max-height: 600px;
        overflow-y: auto;
        background-color: #1e1e1e;
    }

    .log-viewer pre {
        margin: 0;
        padding: 1rem;
        color: #d4d4d4;
    }

    .log-search {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .stat-card-security {
        border-left: 4px solid #ffc107;
    }

    .stat-card-errors {
        border-left: 4px solid #dc3545;
    }

    .stat-card-files {
        border-left: 4px solid #0d6efd;
    }

    .stat-card-size {
        border-left: 4px solid #198754;
    }

    .highlight {
        background-color: #ffc107;
        color: #000;
        padding: 0 2px;
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-file-text"></i> System Logs</h2>
        <div>
            <button class="btn btn-outline-primary me-2" onclick="refreshLogs()" title="Refresh logs">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <a href="admin.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Admin Panel
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Enhanced Log Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card-files">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle text-muted mb-1">Total Log Files</h6>
                            <h3 class="card-title mb-0"><?php echo $stats['total_files']; ?></h3>
                        </div>
                        <i class="bi bi-file-earmark-text text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card-size">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle text-muted mb-1">Total Size</h6>
                            <h3 class="card-title mb-0"><?php echo number_format($stats['total_size'] / 1024, 2); ?> KB</h3>
                        </div>
                        <i class="bi bi-hdd text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card-security">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle text-muted mb-1">Security Events Today</h6>
                            <h3 class="card-title mb-0"><?php echo $securityEvents; ?></h3>
                        </div>
                        <i class="bi bi-shield-exclamation text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card-errors">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle text-muted mb-1">Errors Today</h6>
                            <h3 class="card-title mb-0"><?php echo $errorEvents; ?></h3>
                        </div>
                        <i class="bi bi-exclamation-triangle text-danger" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Log Files List -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Log Files</h5>
                    <span class="badge bg-primary"><?php echo count($logFiles); ?></span>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($logFiles)): ?>
                        <p class="p-3 text-muted mb-0">No log files found.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($logFiles as $file): ?>
                                <?php
                                $isSelected = $selectedFile === $file;
                                $fileInfo = null;
                                foreach ($stats['files'] as $f) {
                                    if ($f['name'] === $file) {
                                        $fileInfo = $f;
                                        break;
                                    }
                                }
                                // Determine file type icon
                                $icon = 'bi-file-text';
                                if (strpos($file, 'error') !== false) $icon = 'bi-exclamation-circle text-danger';
                                elseif (strpos($file, 'debug') !== false) $icon = 'bi-bug text-secondary';
                                elseif (strpos($file, 'warning') !== false) $icon = 'bi-exclamation-triangle text-warning';
                                ?>
                                <a href="?file=<?php echo urlencode($file); ?>"
                                    class="list-group-item list-group-item-action <?php echo $isSelected ? 'active' : ''; ?>">
                                    <div class="d-flex align-items-center">
                                        <i class="bi <?php echo $icon; ?> me-2"></i>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="text-truncate small"><?php echo htmlspecialchars($file); ?></div>
                                            <?php if ($fileInfo): ?>
                                                <small class="<?php echo $isSelected ? 'text-light' : 'text-muted'; ?>">
                                                    <?php echo number_format($fileInfo['size'] / 1024, 1); ?> KB
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Log Cleanup -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-trash"></i> Log Cleanup</h5>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete old log files?');">
                        <div class="mb-3">
                            <label for="days" class="form-label">Delete logs older than:</label>
                            <div class="input-group">
                                <input type="number"
                                    class="form-control"
                                    id="days"
                                    name="days"
                                    value="30"
                                    min="1"
                                    max="365"
                                    required>
                                <span class="input-group-text">days</span>
                            </div>
                        </div>
                        <button type="submit" name="clear_old_logs" class="btn btn-warning w-100">
                            <i class="bi bi-trash"></i> Clear Old Logs
                        </button>
                    </form>
                </div>
            </div>

            <!-- Log Status -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Logging:</span>
                        <span class="badge <?php echo LOG_ENABLED ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo LOG_ENABLED ? 'Enabled' : 'Disabled'; ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Level:</span>
                        <span class="badge bg-secondary"><?php echo LOG_LEVEL; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Log Viewer -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="mb-0">
                                <i class="bi bi-terminal"></i>
                                <?php echo $selectedFile ? htmlspecialchars($selectedFile) : 'Select a log file'; ?>
                            </h5>
                        </div>
                        <?php if ($selectedFile): ?>
                            <div class="col-md-8">
                                <div class="d-flex gap-2 justify-content-end">
                                    <div class="btn-group">
                                        <a href="?file=<?php echo urlencode($selectedFile); ?>&lines=50<?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo $filterLevel ? '&level=' . urlencode($filterLevel) : ''; ?>"
                                            class="btn btn-sm btn-outline-secondary <?php echo (!isset($_GET['lines']) || $_GET['lines'] == 50) ? 'active' : ''; ?>">
                                            50
                                        </a>
                                        <a href="?file=<?php echo urlencode($selectedFile); ?>&lines=100<?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo $filterLevel ? '&level=' . urlencode($filterLevel) : ''; ?>"
                                            class="btn btn-sm btn-outline-secondary <?php echo (isset($_GET['lines']) && $_GET['lines'] == 100) ? 'active' : ''; ?>">
                                            100
                                        </a>
                                        <a href="?file=<?php echo urlencode($selectedFile); ?>&lines=500<?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo $filterLevel ? '&level=' . urlencode($filterLevel) : ''; ?>"
                                            class="btn btn-sm btn-outline-secondary <?php echo (isset($_GET['lines']) && $_GET['lines'] == 500) ? 'active' : ''; ?>">
                                            500
                                        </a>
                                    </div>
                                    <a href="?file=<?php echo urlencode($selectedFile); ?>&export=1" class="btn btn-sm btn-outline-success" title="Export log file">
                                        <i class="bi bi-download"></i> Export
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($selectedFile): ?>
                    <!-- Search and Filter Bar -->
                    <div class="card-body py-2 bg-light log-search border-bottom">
                        <form method="GET" class="row g-2 align-items-center">
                            <input type="hidden" name="file" value="<?php echo htmlspecialchars($selectedFile); ?>">
                            <input type="hidden" name="lines" value="<?php echo isset($_GET['lines']) ? (int)$_GET['lines'] : 100; ?>">
                            <div class="col-md-5">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" name="search" placeholder="Search logs..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select form-select-sm" name="level" onchange="this.form.submit()">
                                    <option value="">All Levels</option>
                                    <option value="DEBUG" <?php echo $filterLevel === 'DEBUG' ? 'selected' : ''; ?>>DEBUG</option>
                                    <option value="INFO" <?php echo $filterLevel === 'INFO' ? 'selected' : ''; ?>>INFO</option>
                                    <option value="WARNING" <?php echo $filterLevel === 'WARNING' ? 'selected' : ''; ?>>WARNING</option>
                                    <option value="ERROR" <?php echo $filterLevel === 'ERROR' ? 'selected' : ''; ?>>ERROR</option>
                                    <option value="CRITICAL" <?php echo $filterLevel === 'CRITICAL' ? 'selected' : ''; ?>>CRITICAL</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-sm btn-primary w-100">
                                    <i class="bi bi-filter"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="log-viewer">
                    <?php if ($selectedFile && $logContent): ?>
                        <pre id="log-content"><?php
                                                // Color-code log levels
                                                $coloredContent = htmlspecialchars($logContent);

                                                // Highlight search term if present
                                                if (!empty($searchTerm)) {
                                                    $coloredContent = preg_replace('/(' . preg_quote(htmlspecialchars($searchTerm), '/') . ')/i', '<span class="highlight">$1</span>', $coloredContent);
                                                }

                                                echo $coloredContent;
                                                ?></pre>
                    <?php elseif ($selectedFile): ?>
                        <p class="p-3 text-muted mb-0">Log file is empty or no matching entries found.</p>
                    <?php else: ?>
                        <div class="text-center p-5">
                            <i class="bi bi-file-earmark-text text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">Select a log file from the list to view its contents.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Log Legend -->
            <?php if ($selectedFile): ?>
                <div class="card mt-3">
                    <div class="card-body py-2">
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <span class="text-muted small">Log Levels:</span>
                            <span class="badge bg-secondary">DEBUG</span>
                            <span class="badge bg-info text-dark">INFO</span>
                            <span class="badge bg-warning text-dark">WARNING</span>
                            <span class="badge bg-danger">ERROR</span>
                            <span class="badge bg-dark">CRITICAL</span>
                            <span class="badge" style="background-color: #ffc107; color: #000;">SECURITY</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function refreshLogs() {
        window.location.reload();
    }

    // Auto-refresh every 30 seconds if on a log file page
    <?php if ($selectedFile): ?>
        let autoRefreshEnabled = false;
        let autoRefreshInterval = null;

        function toggleAutoRefresh() {
            autoRefreshEnabled = !autoRefreshEnabled;
            if (autoRefreshEnabled) {
                autoRefreshInterval = setInterval(() => {
                    window.location.reload();
                }, 30000);
            } else {
                clearInterval(autoRefreshInterval);
            }
        }
    <?php endif; ?>

    // Scroll to bottom of log viewer on load
    document.addEventListener('DOMContentLoaded', function() {
        const logViewer = document.querySelector('.log-viewer');
        if (logViewer) {
            logViewer.scrollTop = logViewer.scrollHeight;
        }
    });
</script>

<?php include '../includes/footer.php'; ?>