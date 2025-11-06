<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

$db = Database::getInstance();
$topics = $db->getTopics(true);

$message = '';
$messageType = '';
$uploadStats = null;

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['quiz_file'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid request';
        $messageType = 'error';
    } else {
        $topicId = intval($_POST['topic_id']);
        $quizName = sanitize_input($_POST['quiz_name']);
        $quizDescription = sanitize_input($_POST['quiz_description']);
        $timeLimit = intval($_POST['time_limit']) * 60; // Convert to seconds
        $questionsPerQuiz = intval($_POST['questions_per_quiz']);

        $file = $_FILES['quiz_file'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message = 'File upload error';
            $messageType = 'error';
        } elseif (!in_array($fileExt, ['csv', 'json'])) {
            $message = 'Only CSV and JSON files are allowed';
            $messageType = 'error';
        } else {
            try {
                // Parse file
                $questions = [];

                if ($fileExt === 'csv') {
                    $questions = parseCSV($file['tmp_name']);
                } else {
                    $questions = parseJSON($file['tmp_name']);
                }

                if (empty($questions)) {
                    throw new Exception('No valid questions found in file');
                }

                // Validate questions
                foreach ($questions as $index => $q) {
                    $missingFields = [];
                    // Use !isset() || trim() === '' instead of empty() to handle "0" values correctly
                    if (!isset($q['question_text']) || trim($q['question_text']) === '') $missingFields[] = 'question_text';
                    if (!isset($q['option_a']) || trim($q['option_a']) === '') $missingFields[] = 'option_a';
                    if (!isset($q['option_b']) || trim($q['option_b']) === '') $missingFields[] = 'option_b';
                    if (!isset($q['option_c']) || trim($q['option_c']) === '') $missingFields[] = 'option_c';
                    if (!isset($q['option_d']) || trim($q['option_d']) === '') $missingFields[] = 'option_d';
                    if (!isset($q['correct_answer']) || trim($q['correct_answer']) === '') $missingFields[] = 'correct_answer';

                    if (!empty($missingFields)) {
                        throw new Exception('Invalid question format at row ' . ($index + 2) . ': Missing fields: ' . implode(', ', $missingFields));
                    }
                }

                // Create quiz
                $quizId = $db->createQuiz($topicId, $quizName, $quizDescription, $timeLimit, $questionsPerQuiz);

                // Add questions
                $successCount = 0;
                foreach ($questions as $question) {
                    if ($db->addQuestion($quizId, $question)) {
                        $successCount++;
                    }
                }

                // Move uploaded file to data directory
                $newFilename = 'quiz_' . $quizId . '_' . time() . '.' . $fileExt;
                move_uploaded_file($file['tmp_name'], UPLOAD_PATH . '/' . $newFilename);

                $uploadStats = [
                    'total' => count($questions),
                    'success' => $successCount,
                    'quiz_id' => $quizId
                ];

                $message = "Quiz uploaded successfully! $successCount questions added.";
                $messageType = 'success';

                log_activity('quiz-uploads', 'Quiz uploaded', [
                    'quiz_id' => $quizId,
                    'name' => $quizName,
                    'questions' => $successCount
                ]);

            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

function parseCSV($filepath) {
    $questions = [];
    if (($handle = fopen($filepath, 'r')) !== false) {
        $headers = fgetcsv($handle); // Skip header row

        // Normalize headers
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 6) continue; // Skip invalid rows

            $question = [];
            foreach ($headers as $index => $header) {
                if (isset($row[$index])) {
                    $question[$header] = trim($row[$index]);
                }
            }

            // Map common header variations
            if (!isset($question['question_text']) && isset($question['question'])) {
                $question['question_text'] = $question['question'];
            }
            if (!isset($question['correct_answer']) && isset($question['answer'])) {
                $question['correct_answer'] = $question['answer'];
            }

            $questions[] = $question;
        }
        fclose($handle);
    }
    return $questions;
}

function parseJSON($filepath) {
    $content = file_get_contents($filepath);
    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON format');
    }

    // Support both array of questions and nested structure
    if (isset($data['questions'])) {
        $data = $data['questions'];
    }

    return $data;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Quiz - Quiz System</title>
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
                <a href="upload.php" class="active">Upload Quiz</a>
                <a href="sessions.php">Active Sessions</a>
                <a href="logs.php">Logs</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <h2 class="mb-3">Upload Quiz</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                    <?php if ($uploadStats): ?>
                        <br><br>
                        <strong>Upload Statistics:</strong><br>
                        - Total questions: <?= $uploadStats['total'] ?><br>
                        - Successfully imported: <?= $uploadStats['success'] ?><br>
                        - Quiz ID: <?= $uploadStats['quiz_id'] ?><br>
                        <a href="launch-quiz.php?id=<?= $uploadStats['quiz_id'] ?>">Launch this quiz</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Upload Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upload Quiz File</h3>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

                    <div class="form-group">
                        <label for="topic_id">Topic *</label>
                        <select id="topic_id" name="topic_id" required>
                            <option value="">Select a topic</option>
                            <?php foreach ($topics as $topic): ?>
                                <option value="<?= $topic['id'] ?>">
                                    <?= htmlspecialchars($topic['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($topics)): ?>
                            <small>No topics available. <a href="topics.php">Create a topic first</a></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="quiz_name">Quiz Name *</label>
                        <input type="text" id="quiz_name" name="quiz_name" required
                               placeholder="e.g., Chapter 1 Quiz">
                    </div>

                    <div class="form-group">
                        <label for="quiz_description">Description</label>
                        <textarea id="quiz_description" name="quiz_description" rows="3"
                                  placeholder="Optional description of the quiz"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="time_limit">Time Limit (minutes) *</label>
                        <input type="number" id="time_limit" name="time_limit" min="1" value="10" required>
                    </div>

                    <div class="form-group">
                        <label for="questions_per_quiz">Questions Per Quiz *</label>
                        <input type="number" id="questions_per_quiz" name="questions_per_quiz" min="1" value="10" required>
                        <small>Number of random questions each participant will get</small>
                    </div>

                    <div class="form-group">
                        <label for="quiz_file">Quiz File (CSV or JSON) *</label>
                        <input type="file" id="quiz_file" name="quiz_file" accept=".csv,.json" required>
                    </div>

                    <button type="submit" class="btn btn-primary" <?= empty($topics) ? 'disabled' : '' ?>>
                        Upload Quiz
                    </button>
                </form>
            </div>

            <!-- File Format Documentation -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">File Format Guide</h3>
                </div>

                <h4>CSV Format</h4>
                <p>Your CSV file should have the following columns (header row required):</p>
                <pre style="background: #f5f5f5; padding: 1rem; border-radius: 0.5rem; overflow-x: auto;">
question,option_a,option_b,option_c,option_d,correct_answer,explanation,difficulty
"What is 2+2?","3","4","5","6","B","Basic addition","easy"
"What is the capital of France?","London","Paris","Berlin","Madrid","B","Paris is the capital","medium"</pre>

                <h4 class="mt-3">JSON Format</h4>
                <p>Your JSON file should be an array of question objects:</p>
                <pre style="background: #f5f5f5; padding: 1rem; border-radius: 0.5rem; overflow-x: auto;">
[
  {
    "question_text": "What is 2+2?",
    "option_a": "3",
    "option_b": "4",
    "option_c": "5",
    "option_d": "6",
    "correct_answer": "B",
    "explanation": "Basic addition",
    "difficulty": "easy"
  }
]</pre>

                <h4 class="mt-3">Field Descriptions</h4>
                <ul>
                    <li><strong>question_text</strong>: The question (required)</li>
                    <li><strong>option_a, option_b, option_c, option_d</strong>: Answer options (required)</li>
                    <li><strong>correct_answer</strong>: Letter A, B, C, or D (required)</li>
                    <li><strong>explanation</strong>: Explanation for the answer (optional)</li>
                    <li><strong>difficulty</strong>: easy, medium, or hard (optional, default: medium)</li>
                </ul>

                <div class="alert alert-info mt-2">
                    <strong>Tip:</strong> You can include more questions in your file than the "Questions Per Quiz" setting.
                    Each participant will receive a random selection of questions from the pool.
                </div>
            </div>
        </div>
    </main>
</body>
</html>
