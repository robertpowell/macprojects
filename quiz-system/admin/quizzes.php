<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

$db = Database::getInstance();

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid request';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            switch ($action) {
                case 'toggle':
                    $id = intval($_POST['id']);
                    $db->toggleQuizStatus($id);
                    $message = 'Quiz status toggled';
                    $messageType = 'success';
                    log_activity('quizzes', 'Quiz status toggled', ['id' => $id]);
                    break;

                case 'delete':
                    $id = intval($_POST['id']);
                    $db->deleteQuiz($id);
                    $message = 'Quiz deleted successfully';
                    $messageType = 'success';
                    log_activity('quizzes', 'Quiz deleted', ['id' => $id]);
                    break;

                case 'update':
                    $id = intval($_POST['id']);
                    $name = sanitize_input($_POST['name']);
                    $description = sanitize_input($_POST['description']);
                    $timeLimit = intval($_POST['time_limit']);
                    $questionsPerQuiz = intval($_POST['questions_per_quiz']);
                    $enabled = intval($_POST['enabled']);
                    $db->updateQuiz($id, $name, $description, $timeLimit, $questionsPerQuiz, $enabled);
                    $message = 'Quiz updated successfully';
                    $messageType = 'success';
                    log_activity('quizzes', 'Quiz updated', ['id' => $id, 'name' => $name]);
                    break;
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$quizzes = $db->getQuizzes();
$topics = $db->getTopics(true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quizzes - Quiz System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Quiz System Admin</h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="topics.php">Topics</a>
                <a href="quizzes.php" class="active">Quizzes</a>
                <a href="upload.php">Upload Quiz</a>
                <a href="sessions.php">Active Sessions</a>
                <a href="logs.php">Logs</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <h2 class="mb-3">Manage Quizzes</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Quizzes List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Quizzes</h3>
                    <a href="upload.php" class="btn btn-primary btn-sm">Upload New Quiz</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Topic</th>
                                <th>Questions</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($quizzes)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        No quizzes yet. <a href="upload.php">Upload your first quiz</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($quizzes as $quiz): ?>
                                    <tr>
                                        <td><?= $quiz['id'] ?></td>
                                        <td><?= htmlspecialchars($quiz['name']) ?></td>
                                        <td><?= htmlspecialchars($quiz['topic_name']) ?></td>
                                        <td><?= $quiz['questions_per_quiz'] ?> / <?= $quiz['total_questions'] ?></td>
                                        <td><?= floor($quiz['time_limit'] / 60) ?> min</td>
                                        <td>
                                            <?php if ($quiz['enabled']): ?>
                                                <span class="badge badge-success">Enabled</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Disabled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="launch-quiz.php?id=<?= $quiz['id'] ?>"
                                               class="btn btn-secondary btn-sm">Launch</a>

                                            <button onclick="editQuiz(<?= htmlspecialchars(json_encode($quiz)) ?>)"
                                                    class="btn btn-primary btn-sm">Edit</button>

                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Toggle status?');">
                                                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?= $quiz['id'] ?>">
                                                <button type="submit" class="btn btn-warning btn-sm">Toggle</button>
                                            </form>

                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this quiz and all its questions?');">
                                                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $quiz['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
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

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Quiz</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label for="edit_name">Quiz Name *</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_time_limit">Time Limit (minutes) *</label>
                    <input type="number" id="edit_time_limit" name="time_limit" min="1" required>
                </div>

                <div class="form-group">
                    <label for="edit_questions_per_quiz">Questions Per Quiz *</label>
                    <input type="number" id="edit_questions_per_quiz" name="questions_per_quiz" min="1" required>
                    <small>Must be less than or equal to total questions</small>
                </div>

                <div class="form-group">
                    <label for="edit_enabled">Status</label>
                    <select id="edit_enabled" name="enabled">
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Update Quiz</button>
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function editQuiz(quiz) {
            document.getElementById('edit_id').value = quiz.id;
            document.getElementById('edit_name').value = quiz.name;
            document.getElementById('edit_description').value = quiz.description || '';
            document.getElementById('edit_time_limit').value = Math.floor(quiz.time_limit / 60);
            document.getElementById('edit_questions_per_quiz').value = quiz.questions_per_quiz;
            document.getElementById('edit_enabled').value = quiz.enabled;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>
