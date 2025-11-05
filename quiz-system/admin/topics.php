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
                case 'create':
                    $name = sanitize_input($_POST['name']);
                    $description = sanitize_input($_POST['description']);
                    $db->createTopic($name, $description);
                    $message = 'Topic created successfully';
                    $messageType = 'success';
                    log_activity('topics', 'Topic created', ['name' => $name]);
                    break;

                case 'update':
                    $id = intval($_POST['id']);
                    $name = sanitize_input($_POST['name']);
                    $description = sanitize_input($_POST['description']);
                    $enabled = intval($_POST['enabled']);
                    $db->updateTopic($id, $name, $description, $enabled);
                    $message = 'Topic updated successfully';
                    $messageType = 'success';
                    log_activity('topics', 'Topic updated', ['id' => $id, 'name' => $name]);
                    break;

                case 'toggle':
                    $id = intval($_POST['id']);
                    $db->toggleTopicStatus($id);
                    $message = 'Topic status toggled';
                    $messageType = 'success';
                    log_activity('topics', 'Topic status toggled', ['id' => $id]);
                    break;

                case 'delete':
                    $id = intval($_POST['id']);
                    $db->deleteTopic($id);
                    $message = 'Topic deleted successfully';
                    $messageType = 'success';
                    log_activity('topics', 'Topic deleted', ['id' => $id]);
                    break;
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$topics = $db->getTopics();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Topics - Quiz System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Quiz System Admin</h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="topics.php" class="active">Topics</a>
                <a href="quizzes.php">Quizzes</a>
                <a href="upload.php">Upload Quiz</a>
                <a href="sessions.php">Active Sessions</a>
                <a href="logs.php">Logs</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <h2 class="mb-3">Manage Topics</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Create New Topic -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create New Topic</h3>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                    <input type="hidden" name="action" value="create">

                    <div class="form-group">
                        <label for="name">Topic Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Topic</button>
                </form>
            </div>

            <!-- Existing Topics -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Existing Topics</h3>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Quizzes</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topics)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No topics yet. Create one above.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($topics as $topic): ?>
                                    <?php
                                    $quizzes = $db->getQuizzes($topic['id']);
                                    $quizCount = count($quizzes);
                                    ?>
                                    <tr>
                                        <td><?= $topic['id'] ?></td>
                                        <td><?= htmlspecialchars($topic['name']) ?></td>
                                        <td><?= htmlspecialchars($topic['description']) ?></td>
                                        <td><?= $quizCount ?></td>
                                        <td>
                                            <?php if ($topic['enabled']): ?>
                                                <span class="badge badge-success">Enabled</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Disabled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button onclick="editTopic(<?= htmlspecialchars(json_encode($topic)) ?>)"
                                                    class="btn btn-primary btn-sm">Edit</button>

                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Toggle status?');">
                                                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?= $topic['id'] ?>">
                                                <button type="submit" class="btn btn-warning btn-sm">Toggle</button>
                                            </form>

                                            <?php if ($quizCount == 0): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this topic?');">
                                                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $topic['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
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

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Topic</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label for="edit_name">Topic Name *</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_enabled">Status</label>
                    <select id="edit_enabled" name="enabled">
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Update Topic</button>
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function editTopic(topic) {
            document.getElementById('edit_id').value = topic.id;
            document.getElementById('edit_name').value = topic.name;
            document.getElementById('edit_description').value = topic.description || '';
            document.getElementById('edit_enabled').value = topic.enabled;
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
