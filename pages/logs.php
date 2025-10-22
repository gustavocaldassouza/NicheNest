<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$message = '';
$logContent = '';
$selectedFile = '';

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
    }
}

// Get log statistics
$stats = Logger::getStats();

$page_title = "System Logs - Admin Panel";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-file-text"></i> System Logs</h2>
        <a href="admin.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Admin Panel
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Log Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Log Files</h5>
                    <p class="card-text display-6"><?php echo $stats['total_files']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Size</h5>
                    <p class="card-text display-6">
                        <?php echo number_format($stats['total_size'] / 1024, 2); ?> KB
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Log Status</h5>
                    <p class="card-text">
                        <span class="badge <?php echo LOG_ENABLED ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo LOG_ENABLED ? 'Enabled' : 'Disabled'; ?>
                        </span>
                        <br>
                        <small class="text-muted">Level: <?php echo LOG_LEVEL; ?></small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Log Files List -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Log Files</h5>
                </div>
                <div class="card-body p-0">
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
                                ?>
                                <a href="?file=<?php echo urlencode($file); ?>" 
                                   class="list-group-item list-group-item-action <?php echo $isSelected ? 'active' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($file); ?></h6>
                                    </div>
                                    <?php if ($fileInfo): ?>
                                        <small class="<?php echo $isSelected ? 'text-light' : 'text-muted'; ?>">
                                            <?php echo number_format($fileInfo['size'] / 1024, 2); ?> KB |
                                            <?php echo date('Y-m-d H:i', $fileInfo['modified']); ?>
                                        </small>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Log Cleanup -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Log Cleanup</h5>
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
        </div>

        <!-- Log Viewer -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <?php echo $selectedFile ? htmlspecialchars($selectedFile) : 'Select a log file to view'; ?>
                    </h5>
                    <?php if ($selectedFile): ?>
                        <div class="btn-group">
                            <a href="?file=<?php echo urlencode($selectedFile); ?>&lines=50" 
                               class="btn btn-sm btn-outline-secondary <?php echo (!isset($_GET['lines']) || $_GET['lines'] == 50) ? 'active' : ''; ?>">
                                50 lines
                            </a>
                            <a href="?file=<?php echo urlencode($selectedFile); ?>&lines=100" 
                               class="btn btn-sm btn-outline-secondary <?php echo (isset($_GET['lines']) && $_GET['lines'] == 100) ? 'active' : ''; ?>">
                                100 lines
                            </a>
                            <a href="?file=<?php echo urlencode($selectedFile); ?>&lines=500" 
                               class="btn btn-sm btn-outline-secondary <?php echo (isset($_GET['lines']) && $_GET['lines'] == 500) ? 'active' : ''; ?>">
                                500 lines
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if ($selectedFile && $logContent): ?>
                        <pre class="p-3 mb-0" style="max-height: 600px; overflow-y: auto; font-size: 0.85em; background-color: #f8f9fa;"><?php echo htmlspecialchars($logContent); ?></pre>
                    <?php elseif ($selectedFile): ?>
                        <p class="p-3 text-muted mb-0">Log file is empty or could not be read.</p>
                    <?php else: ?>
                        <p class="p-3 text-muted mb-0">Select a log file from the list to view its contents.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($selectedFile): ?>
                <div class="card mt-3">
                    <div class="card-body">
                        <h6>Log Entry Format</h6>
                        <p class="mb-0 small text-muted">
                            <code>[Timestamp] [Level] Message | Context: {json_data}</code>
                        </p>
                        <hr>
                        <h6>Log Levels</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <span class="badge bg-secondary">DEBUG</span> - Detailed diagnostic info<br>
                                <span class="badge bg-info text-dark">INFO</span> - General informational messages<br>
                                <span class="badge bg-warning text-dark">WARNING</span> - Warning messages
                            </div>
                            <div class="col-md-6">
                                <span class="badge bg-danger">ERROR</span> - Error conditions<br>
                                <span class="badge bg-dark">CRITICAL</span> - Critical conditions
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
