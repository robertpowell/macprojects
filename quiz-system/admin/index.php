<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

$db = Database::getInstance();

// Get statistics
$topics = $db->getTopics();
$quizzes = $db->getQuizzes();

$totalTopics = count($topics);
$totalQuizzes = count($quizzes);
$enabledTopics = count(array_filter($topics, fn($t) => $t['enabled'] == 1));
$enabledQuizzes = count(array_filter($quizzes, fn($q) => $q['enabled'] == 1));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Quiz System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Quiz System Admin</h1>
            <nav>
                <a href="index.php" class="active">Dashboard</a>
                <a href="topics.php">Topics</a>
                <a href="quizzes.php">Quizzes</a>
                <a href="upload.php">Upload Quiz</a>
                <a href="sessions.php">Active Sessions</a>
                <a href="logs.php">Logs</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <h2 class="mb-3">Dashboard</h2>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Topics</div>
                    <div class="stat-value"><?= $totalTopics ?></div>
                    <div class="stat-label mt-1"><?= $enabledTopics ?> enabled</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Total Quizzes</div>
                    <div class="stat-value"><?= $totalQuizzes ?></div>
                    <div class="stat-label mt-1"><?= $enabledQuizzes ?> enabled</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Total Questions</div>
                    <div class="stat-value">
                        <?= array_sum(array_column($quizzes, 'total_questions')) ?>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Quick Actions</div>
                    <a href="upload.php" class="btn btn-primary btn-sm btn-block mt-2">Upload Quiz</a>
                </div>
            </div>

            <!-- Recent Quizzes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Quizzes</h3>
                    <a href="quizzes.php" class="btn btn-primary btn-sm">View All</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Quiz Name</th>
                                <th>Topic</th>
                                <th>Questions</th>
                                <th>Time Limit</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recentQuizzes = array_slice($quizzes, 0, 5);
                            if (empty($recentQuizzes)):
                            ?>
                                <tr>
                                    <td colspan="6" class="text-center">No quizzes yet. <a href="upload.php">Upload your first quiz</a></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentQuizzes as $quiz): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($quiz['name']) ?></td>
                                        <td><?= htmlspecialchars($quiz['topic_name']) ?></td>
                                        <td><?= $quiz['questions_per_quiz'] ?> of <?= $quiz['total_questions'] ?></td>
                                        <td><?= floor($quiz['time_limit'] / 60) ?> min</td>
                                        <td>
                                            <?php if ($quiz['enabled']): ?>
                                                <span class="badge badge-success">Enabled</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Disabled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="launch-quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-secondary btn-sm">Launch</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Topics Overview -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Topics Overview</h3>
                    <a href="topics.php" class="btn btn-primary btn-sm">Manage Topics</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Topic Name</th>
                                <th>Quizzes</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topics)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">No topics yet. <a href="topics.php">Create your first topic</a></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($topics as $topic): ?>
                                    <?php
                                    $topicQuizzes = array_filter($quizzes, fn($q) => $q['topic_id'] == $topic['id']);
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($topic['name']) ?></td>
                                        <td><?= count($topicQuizzes) ?> quiz<?= count($topicQuizzes) != 1 ? 'zes' : '' ?></td>
                                        <td>
                                            <?php if ($topic['enabled']): ?>
                                                <span class="badge badge-success">Enabled</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Disabled</span>
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

    <script src="../assets/js/admin.js"></script>
</body>
</html>
