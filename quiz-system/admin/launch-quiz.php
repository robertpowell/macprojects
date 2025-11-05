<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/qr-generator.php';

$db = Database::getInstance();
$qr = new QRCodeGenerator();

$quizId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sessionId = isset($_GET['session']) ? intval($_GET['session']) : 0;

if ($quizId == 0 && $sessionId == 0) {
    header('Location: quizzes.php');
    exit;
}

// If no session, create one
if ($sessionId == 0 && $quizId > 0) {
    $quiz = $db->getQuiz($quizId);
    if (!$quiz) {
        die('Quiz not found');
    }

    // Create new session
    $sessionId = $db->createSession($quizId);
    log_activity('quiz-launches', 'Quiz session created', [
        'quiz_id' => $quizId,
        'session_id' => $sessionId,
        'quiz_name' => $quiz['name']
    ]);

    // Redirect to session view
    header('Location: launch-quiz.php?session=' . $sessionId);
    exit;
}

// Get session and quiz details
$session = $db->getSession($sessionId);
if (!$session) {
    die('Session not found');
}

$quiz = $db->getQuiz($session['quiz_id']);
$participants = $db->getParticipants($sessionId);

// Generate join URL and QR code
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
           '://' . $_SERVER['HTTP_HOST'] .
           str_replace('/admin', '', dirname($_SERVER['SCRIPT_NAME']));
$joinUrl = $baseUrl . '/participant/join.php?code=' . $session['session_code'];

// Handle session actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die('Invalid request');
    }

    $action = $_POST['action'];

    switch ($action) {
        case 'start':
            $db->startSession($sessionId);
            log_activity('quiz-launches', 'Quiz started', [
                'session_id' => $sessionId,
                'participants' => count($participants)
            ]);
            header('Location: monitor-quiz.php?session=' . $sessionId);
            exit;

        case 'cancel':
            $db->endSession($sessionId);
            log_activity('quiz-launches', 'Quiz session cancelled', [
                'session_id' => $sessionId
            ]);
            header('Location: quizzes.php');
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Launch Quiz - <?= htmlspecialchars($quiz['name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .qr-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        #qrcode {
            margin: 1rem 0;
        }
        #qrcode canvas {
            border: 4px solid #E5E7EB;
            border-radius: 0.5rem;
        }
        .participant-count {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary);
            margin: 1rem 0;
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
            <h2 class="mb-3">Launch Quiz: <?= htmlspecialchars($quiz['name']) ?></h2>

            <div class="stats-grid mb-3">
                <div class="stat-card">
                    <div class="stat-label">Topic</div>
                    <div class="stat-value" style="font-size: 1.5rem;">
                        <?= htmlspecialchars($quiz['topic_name']) ?>
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
                    <div class="participant-count" id="participantCount"><?= count($participants) ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Participant Join Information</h3>
                </div>

                <div class="qr-container">
                    <h3>Scan QR Code or Enter Code</h3>
                    <div id="qrcode"></div>
                    <div class="session-code"><?= $session['session_code'] ?></div>
                    <p>Join URL: <a href="<?= $joinUrl ?>" target="_blank"><?= $joinUrl ?></a></p>
                </div>
            </div>

            <!-- Current Participants -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Current Participants (<span id="participantListCount"><?= count($participants) ?></span>)</h3>
                </div>
                <div id="participantList">
                    <?php if (empty($participants)): ?>
                        <p class="text-center">Waiting for participants to join...</p>
                    <?php else: ?>
                        <?php foreach ($participants as $participant): ?>
                            <div class="participant-card" style="background-color: <?= $participant['color_hex'] ?>20;">
                                <div class="participant-color" style="background-color: <?= $participant['color_hex'] ?>; color: <?= $participant['color_text'] ?>;">
                                    <?= strtoupper(substr($participant['color_name'], 0, 2)) ?>
                                </div>
                                <div class="participant-info">
                                    <div class="participant-name"><?= $participant['color_name'] ?></div>
                                    <div class="participant-status">Joined <?= date('h:i A', strtotime($participant['joined_at'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card">
                <div class="flex-between gap-2">
                    <form method="POST" style="flex: 1;">
                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn btn-danger btn-block"
                                onclick="return confirm('Cancel this quiz session?');">
                            Cancel Session
                        </button>
                    </form>

                    <form method="POST" style="flex: 1;">
                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                        <input type="hidden" name="action" value="start">
                        <button type="submit" class="btn btn-primary btn-block"
                                id="startBtn"
                                <?= count($participants) == 0 ? 'disabled' : '' ?>>
                            Start Quiz (<?= count($participants) ?> participants)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        // Generate QR code
        new QRCode(document.getElementById("qrcode"), {
            text: "<?= $joinUrl ?>",
            width: 300,
            height: 300,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        // Auto-refresh participant list every 3 seconds
        setInterval(function() {
            fetch('../api/get-participants.php?session=<?= $sessionId ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateParticipantList(data.participants);
                        updateParticipantCount(data.participants.length);
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 3000);

        function updateParticipantCount(count) {
            document.getElementById('participantCount').textContent = count;
            document.getElementById('participantListCount').textContent = count;

            // Update start button
            const startBtn = document.getElementById('startBtn');
            startBtn.textContent = `Start Quiz (${count} participants)`;
            startBtn.disabled = count === 0;
        }

        function updateParticipantList(participants) {
            const listDiv = document.getElementById('participantList');

            if (participants.length === 0) {
                listDiv.innerHTML = '<p class="text-center">Waiting for participants to join...</p>';
                return;
            }

            let html = '';
            participants.forEach(p => {
                const joinTime = new Date(p.joined_at).toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                html += `
                    <div class="participant-card" style="background-color: ${p.color_hex}20;">
                        <div class="participant-color" style="background-color: ${p.color_hex}; color: ${p.color_text};">
                            ${p.color_name.substring(0, 2).toUpperCase()}
                        </div>
                        <div class="participant-info">
                            <div class="participant-name">${p.color_name}</div>
                            <div class="participant-status">Joined ${joinTime}</div>
                        </div>
                    </div>
                `;
            });

            listDiv.innerHTML = html;
        }
    </script>
</body>
</html>
