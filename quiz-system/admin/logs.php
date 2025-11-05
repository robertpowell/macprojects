<?php
require_once '../includes/config.php';

$logFiles = [
    'quiz-launches' => 'Quiz Launches',
    'quiz-participation' => 'Participation',
    'quiz-uploads' => 'Quiz Uploads',
    'topics' => 'Topics'
];

$selectedLog = isset($_GET['log']) ? $_GET['log'] : 'quiz-launches';
if (!isset($logFiles[$selectedLog])) {
    $selectedLog = 'quiz-launches';
}

$logPath = LOG_PATH . '/' . $selectedLog . '.log';
$logEntries = [];

if (file_exists($logPath)) {
    $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logEntries = array_reverse(array_slice($lines, -100)); // Last 100 entries
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .log-entry {
            font-family: 'Courier New', monospace;
            padding: 0.5rem;
            margin-bottom: 0.25rem;
            background: #f5f5f5;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            word-wrap: break-word;
        }
        .log-timestamp {
            color: #666;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Quiz System Admin</h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="topics.php">Topics</a>
                <a href="quizzes.php">Quizzes</a>
                <a href="upload.php">Upload Quiz</a>
                <a href="sessions.php">Active Sessions</a>
                <a href="logs.php" class="active">Logs</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <h2 class="mb-3">System Logs</h2>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Log Files</h3>
                </div>

                <div class="form-group">
                    <label for="logSelect">Select Log File</label>
                    <select id="logSelect" onchange="window.location.href='logs.php?log='+this.value">
                        <?php foreach ($logFiles as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $selectedLog === $key ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <?= $logFiles[$selectedLog] ?> Log
                        <span class="badge badge-info"><?= count($logEntries) ?> entries</span>
                    </h3>
                </div>

                <?php if (empty($logEntries)): ?>
                    <p class="text-center">No log entries yet.</p>
                <?php else: ?>
                    <div style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($logEntries as $entry): ?>
                            <div class="log-entry">
                                <?= htmlspecialchars($entry) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div style="padding: 1rem; border-top: 1px solid var(--border);">
                    <button onclick="location.reload()" class="btn btn-primary btn-sm">
                        Refresh Logs
                    </button>
                    <span style="color: #666; margin-left: 1rem;">
                        Showing last <?= count($logEntries) ?> entries
                    </span>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
