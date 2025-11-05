<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

$db = Database::getInstance();

$code = isset($_GET['code']) ? strtoupper(trim($_GET['code'])) : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['session_code']));
}

if ($code) {
    // Verify session exists
    $session = $db->getSessionByCode($code);

    if (!$session) {
        $error = 'Invalid session code';
    } elseif ($session['status'] !== 'waiting') {
        $error = 'This quiz has already started or ended';
    } else {
        // Check participant limit
        $participants = $db->getParticipants($session['id']);
        if (count($participants) >= MAX_PARTICIPANTS_PER_QUIZ) {
            $error = 'This quiz is full';
        } else {
            // Assign random color
            $usedColors = $db->getUsedColors($session['id']);
            $color = get_random_color($usedColors);

            // Create participant
            $participantId = $db->addParticipant($session['id'], $color);

            // Get quiz details
            $quiz = $db->getQuiz($session['quiz_id']);

            // Assign random questions
            $questions = $db->getRandomQuestions($quiz['id'], $quiz['questions_per_quiz']);
            $questionIds = array_column($questions, 'id');
            $db->assignQuestionsToParticipant($participantId, $questionIds);

            log_activity('quiz-participation', 'Participant joined', [
                'session_id' => $session['id'],
                'participant_id' => $participantId,
                'color' => $color['name']
            ]);

            // Store participant ID in session
            $_SESSION['participant_id'] = $participantId;
            $_SESSION['session_id'] = $session['id'];

            // Redirect to waiting room
            header('Location: waiting.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Quiz</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <main>
        <div class="container" style="max-width: 500px; margin-top: 4rem;">
            <div class="card text-center">
                <h1 style="color: var(--primary); margin-bottom: 2rem;">🎯 Join Quiz</h1>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="session_code">Enter Quiz Code</label>
                        <input type="text"
                               id="session_code"
                               name="session_code"
                               value="<?= htmlspecialchars($code) ?>"
                               maxlength="6"
                               style="text-align: center; font-size: 2rem; letter-spacing: 0.2em; text-transform: uppercase;"
                               placeholder="ABC123"
                               required
                               autofocus>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Join Quiz
                    </button>
                </form>
            </div>

            <div class="card mt-3">
                <h3>How to Join</h3>
                <ol style="text-align: left; line-height: 2;">
                    <li>Scan the QR code displayed by your instructor, OR</li>
                    <li>Enter the 6-digit code shown on screen</li>
                    <li>Wait for the quiz to start</li>
                    <li>Answer all questions before time runs out</li>
                </ol>
            </div>
        </div>
    </main>

    <script>
        // Auto-uppercase input
        document.getElementById('session_code').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    </script>
</body>
</html>
