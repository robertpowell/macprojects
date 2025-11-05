<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

$sessionId = isset($_GET['session']) ? intval($_GET['session']) : 0;

if ($sessionId == 0) {
    header('Location: sessions.php');
    exit;
}

$db = Database::getInstance();
$session = $db->getSession($sessionId);

if (!$session) {
    die('Session not found');
}

$quiz = $db->getQuiz($session['quiz_id']);
$participants = $db->getParticipants($sessionId);
$stats = $db->getSessionStats($sessionId);

// Handle end session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'end') {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $db->endSession($sessionId);
        log_activity('quiz-launches', 'Quiz session ended', [
            'session_id' => $sessionId,
            'completed_participants' => $stats['completed_participants']
        ]);
        header('Location: sessions.php');
        exit;
    }
}

// Calculate time remaining
$startTime = strtotime($session['started_at']);
$endTime = $startTime + $quiz['time_limit'];
$currentTime = time();
$timeRemaining = max(0, $endTime - $currentTime);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Quiz - <?= htmlspecialchars($quiz['name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .progress-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
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
                <a href="sessions.php" class="active">Active Sessions</a>
                <a href="logs.php">Logs</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <h2 class="mb-3">Monitoring: <?= htmlspecialchars($quiz['name']) ?></h2>

            <!-- Session Info -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Session Code</div>
                    <div class="stat-value" style="font-size: 1.5rem;">
                        <?= $session['session_code'] ?>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Time Remaining</div>
                    <div class="timer" id="timer" data-end-time="<?= $endTime ?>">
                        <?= floor($timeRemaining / 60) ?>:<?= str_pad($timeRemaining % 60, 2, '0', STR_PAD_LEFT) ?>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Participants</div>
                    <div class="stat-value"><?= $stats['total_participants'] ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Completed</div>
                    <div class="stat-value" id="completedCount">
                        <?= $stats['completed_participants'] ?>
                    </div>
                </div>

                <?php if ($stats['completed_participants'] > 0): ?>
                    <div class="stat-card">
                        <div class="stat-label">Average Score</div>
                        <div class="stat-value">
                            <?= round($stats['average_score'], 1) ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Highest Score</div>
                        <div class="stat-value"><?= $stats['highest_score'] ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Lowest Score</div>
                        <div class="stat-value"><?= $stats['lowest_score'] ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Progress Bar -->
            <div class="card">
                <h3 class="card-title">Overall Progress</h3>
                <div class="progress">
                    <div class="progress-bar" id="progressBar"
                         style="width: <?= $stats['total_participants'] > 0 ? ($stats['completed_participants'] / $stats['total_participants']) * 100 : 0 ?>%">
                        <?= $stats['completed_participants'] ?> / <?= $stats['total_participants'] ?>
                    </div>
                </div>
            </div>

            <!-- Participants Grid -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Participant Status</h3>
                </div>
                <div class="progress-grid" id="participantGrid">
                    <?php foreach ($participants as $participant): ?>
                        <div class="stat-card" style="background: linear-gradient(135deg, <?= $participant['color_hex'] ?>20, white); border-left: 4px solid <?= $participant['color_hex'] ?>;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $participant['color_hex'] ?>; color: <?= $participant['color_text'] ?>; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                    <?= strtoupper(substr($participant['color_name'], 0, 2)) ?>
                                </div>
                                <div style="font-weight: 600;">
                                    <?= $participant['color_name'] ?>
                                </div>
                            </div>

                            <?php if ($participant['completed_at']): ?>
                                <div class="badge badge-success">✓ Completed</div>
                                <div style="font-size: 1.5rem; font-weight: 700; color: <?= $participant['color_hex'] ?>; margin-top: 0.5rem;">
                                    <?= $participant['score'] ?> / <?= $quiz['questions_per_quiz'] ?>
                                </div>
                            <?php else: ?>
                                <div class="badge badge-warning">⏳ In Progress</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <form method="POST" onsubmit="return confirm('End this quiz session? All unfinished participants will be auto-submitted.');">
                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                    <input type="hidden" name="action" value="end">
                    <button type="submit" class="btn btn-danger btn-block">
                        End Quiz Session
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Update timer
        function updateTimer() {
            const timerEl = document.getElementById('timer');
            const endTime = parseInt(timerEl.dataset.endTime);
            const now = Math.floor(Date.now() / 1000);
            const remaining = Math.max(0, endTime - now);

            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;

            timerEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            // Change color based on time
            timerEl.className = 'timer';
            if (remaining < 60) {
                timerEl.classList.add('danger');
            } else if (remaining < 180) {
                timerEl.classList.add('warning');
            }

            // Auto-refresh when time is up
            if (remaining === 0) {
                setTimeout(() => location.reload(), 2000);
            }
        }

        setInterval(updateTimer, 1000);
        updateTimer();

        // Auto-refresh participants every 3 seconds
        setInterval(function() {
            fetch('../api/get-session-progress.php?session=<?= $sessionId ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('completedCount').textContent = data.completed;

                        const total = data.total;
                        const completed = data.completed;
                        const percentage = total > 0 ? (completed / total) * 100 : 0;

                        const progressBar = document.getElementById('progressBar');
                        progressBar.style.width = percentage + '%';
                        progressBar.textContent = `${completed} / ${total}`;

                        // If all completed, show option to end
                        if (completed === total && total > 0) {
                            // Could add a notification here
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 3000);
    </script>
</body>
</html>
