<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

// Check if participant is authenticated
if (!isset($_SESSION['participant_id']) || !isset($_SESSION['session_id'])) {
    header('Location: join.php');
    exit;
}

$db = Database::getInstance();
$participantId = $_SESSION['participant_id'];
$sessionId = $_SESSION['session_id'];

// Get participant and session info
$participants = $db->getParticipants($sessionId);
$participant = null;
foreach ($participants as $p) {
    if ($p['id'] == $participantId) {
        $participant = $p;
        break;
    }
}

if (!$participant) {
    header('Location: join.php');
    exit;
}

$session = $db->getSession($sessionId);
$quiz = $db->getQuiz($session['quiz_id']);

// If quiz has started, redirect to quiz page
if ($session['status'] === 'active') {
    header('Location: quiz.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiting Room</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .your-color {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 2rem auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: pulse-slow 2s infinite;
        }

        @keyframes pulse-slow {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .waiting-message {
            font-size: 1.5rem;
            text-align: center;
            margin: 2rem 0;
            animation: fade 2s infinite;
        }

        @keyframes fade {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <main>
        <div class="container" style="max-width: 600px; margin-top: 2rem;">
            <div class="card text-center">
                <h1 style="color: var(--primary);">You're In! 🎉</h1>

                <div class="your-color" style="background-color: <?= $participant['color_hex'] ?>; color: <?= $participant['color_text'] ?>;">
                    <?= strtoupper(substr($participant['color_name'], 0, 2)) ?>
                </div>

                <h2 style="color: <?= $participant['color_hex'] ?>;">
                    You are <?= $participant['color_name'] ?>
                </h2>

                <div class="waiting-message">
                    Waiting for quiz to start...
                </div>

                <div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="stat-card">
                        <div class="stat-label">Quiz</div>
                        <div class="stat-value" style="font-size: 1.2rem;">
                            <?= htmlspecialchars($quiz['name']) ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Questions</div>
                        <div class="stat-value"><?= $quiz['questions_per_quiz'] ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Time Limit</div>
                        <div class="stat-value"><?= floor($quiz['time_limit'] / 60) ?></div>
                        <div class="stat-label">minutes</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Participants</div>
                        <div class="stat-value" id="participantCount"><?= count($participants) ?></div>
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    <strong>Get Ready!</strong><br>
                    The quiz will start when your instructor is ready.<br>
                    Make sure you have a stable internet connection.
                </div>
            </div>
        </div>
    </main>

    <script>
        // Check for quiz start every 2 seconds
        setInterval(function() {
            fetch('../api/check-session-status.php?session=<?= $sessionId ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'active') {
                        window.location.href = 'quiz.php';
                    }
                    if (data.participant_count) {
                        document.getElementById('participantCount').textContent = data.participant_count;
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 2000);
    </script>
</body>
</html>
