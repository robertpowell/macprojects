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

// Sort participants by score
usort($participants, fn($a, $b) => $b['score'] - $a['score']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Results - <?= htmlspecialchars($quiz['name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
            <h2 class="mb-3">Session Results: <?= htmlspecialchars($quiz['name']) ?></h2>

            <!-- Session Info -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Session Code</div>
                    <div class="stat-value" style="font-size: 1.5rem;">
                        <?= $session['session_code'] ?>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Total Participants</div>
                    <div class="stat-value"><?= $stats['total_participants'] ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Completed</div>
                    <div class="stat-value"><?= $stats['completed_participants'] ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Average Score</div>
                    <div class="stat-value">
                        <?= $stats['completed_participants'] > 0 ? round($stats['average_score'], 1) : 'N/A' ?>
                    </div>
                </div>

                <?php if ($stats['completed_participants'] > 0): ?>
                    <div class="stat-card">
                        <div class="stat-label">Highest Score</div>
                        <div class="stat-value"><?= $stats['highest_score'] ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Lowest Score</div>
                        <div class="stat-value"><?= $stats['lowest_score'] ?></div>
                    </div>
                <?php endif; ?>

                <div class="stat-card">
                    <div class="stat-label">Started</div>
                    <div class="stat-value" style="font-size: 1rem;">
                        <?= date('M j, Y', strtotime($session['started_at'])) ?><br>
                        <?= date('g:i A', strtotime($session['started_at'])) ?>
                    </div>
                </div>

                <?php if ($session['ended_at']): ?>
                    <div class="stat-card">
                        <div class="stat-label">Ended</div>
                        <div class="stat-value" style="font-size: 1rem;">
                            <?= date('M j, Y', strtotime($session['ended_at'])) ?><br>
                            <?= date('g:i A', strtotime($session['ended_at'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Leaderboard -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Final Leaderboard</h3>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Participant</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Completed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $index => $participant): ?>
                                <tr>
                                    <td>
                                        <strong style="font-size: 1.25rem;">
                                            <?php if ($index === 0 && $participant['completed_at']): ?>
                                                🥇
                                            <?php elseif ($index === 1 && $participant['completed_at']): ?>
                                                🥈
                                            <?php elseif ($index === 2 && $participant['completed_at']): ?>
                                                🥉
                                            <?php else: ?>
                                                #<?= $index + 1 ?>
                                            <?php endif; ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div style="width: 30px; height: 30px; border-radius: 50%; background: <?= $participant['color_hex'] ?>; color: <?= $participant['color_text'] ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem;">
                                                <?= strtoupper(substr($participant['color_name'], 0, 2)) ?>
                                            </div>
                                            <strong><?= $participant['color_name'] ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <strong style="font-size: 1.25rem;">
                                            <?= $participant['score'] ?> / <?= $quiz['questions_per_quiz'] ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php
                                        $percentage = round(($participant['score'] / $quiz['questions_per_quiz']) * 100);
                                        $badgeClass = $percentage >= 80 ? 'badge-success' : ($percentage >= 60 ? 'badge-info' : 'badge-warning');
                                        ?>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= $percentage ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($participant['completed_at']): ?>
                                            <?= date('g:i:s A', strtotime($participant['completed_at'])) ?>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Incomplete</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Export Options -->
            <div class="card">
                <h3 class="card-title mb-2">Export Results</h3>
                <div class="flex gap-2">
                    <button onclick="exportToCSV()" class="btn btn-primary">
                        📊 Export to CSV
                    </button>
                    <button onclick="window.print()" class="btn btn-outline">
                        🖨️ Print Results
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script>
        function exportToCSV() {
            const participants = <?= json_encode($participants) ?>;
            const quizName = <?= json_encode($quiz['name']) ?>;
            const sessionCode = <?= json_encode($session['session_code']) ?>;

            let csv = 'Rank,Participant,Score,Percentage,Completed At\n';

            participants.forEach((p, index) => {
                const percentage = Math.round((p.score / <?= $quiz['questions_per_quiz'] ?>) * 100);
                const completedAt = p.completed_at || 'Incomplete';
                csv += `${index + 1},"${p.color_name}",${p.score},${percentage}%,"${completedAt}"\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `quiz-results-${sessionCode}.csv`;
            a.click();
        }
    </script>
</body>
</html>
