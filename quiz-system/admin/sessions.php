<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

$db = Database::getInstance();

// Get all sessions
$conn = $db->getConnection();
$stmt = $conn->query("
    SELECT qs.*, q.name as quiz_name, t.name as topic_name,
           (SELECT COUNT(*) FROM participants WHERE session_id = qs.id) as participant_count
    FROM quiz_sessions qs
    JOIN quizzes q ON qs.quiz_id = q.id
    JOIN topics t ON q.topic_id = t.id
    ORDER BY qs.created_at DESC
    LIMIT 50
");
$sessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Sessions - Admin</title>
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
            <h2 class="mb-3">Quiz Sessions</h2>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Sessions</h3>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Quiz</th>
                                <th>Topic</th>
                                <th>Status</th>
                                <th>Participants</th>
                                <th>Started</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sessions)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        No sessions yet. <a href="quizzes.php">Launch a quiz</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sessions as $session): ?>
                                    <tr>
                                        <td><strong><?= $session['session_code'] ?></strong></td>
                                        <td><?= htmlspecialchars($session['quiz_name']) ?></td>
                                        <td><?= htmlspecialchars($session['topic_name']) ?></td>
                                        <td>
                                            <?php if ($session['status'] === 'waiting'): ?>
                                                <span class="badge badge-warning">Waiting</span>
                                            <?php elseif ($session['status'] === 'active'): ?>
                                                <span class="badge badge-info">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $session['participant_count'] ?></td>
                                        <td>
                                            <?= $session['started_at'] ? date('M j, Y g:i A', strtotime($session['started_at'])) : 'Not started' ?>
                                        </td>
                                        <td>
                                            <?php if ($session['status'] === 'waiting'): ?>
                                                <a href="launch-quiz.php?session=<?= $session['id'] ?>"
                                                   class="btn btn-primary btn-sm">Open</a>
                                            <?php elseif ($session['status'] === 'active'): ?>
                                                <a href="monitor-quiz.php?session=<?= $session['id'] ?>"
                                                   class="btn btn-secondary btn-sm">Monitor</a>
                                            <?php else: ?>
                                                <a href="session-results.php?session=<?= $session['id'] ?>"
                                                   class="btn btn-outline btn-sm">Results</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
