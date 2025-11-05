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

// Get participant info
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

// Get participant's score
$answers = $db->getParticipantAnswers($participantId);
$questions = $db->getParticipantQuestions($participantId);
$score = $participant['score'];
$totalQuestions = count($questions);
$percentage = $totalQuestions > 0 ? round(($score / $totalQuestions) * 100) : 0;

// Get all completed participants for ranking
$completedParticipants = array_filter($participants, fn($p) => $p['completed_at'] !== null);
usort($completedParticipants, fn($a, $b) => $b['score'] - $a['score']);

// Find participant's rank
$rank = 0;
foreach ($completedParticipants as $index => $p) {
    if ($p['id'] == $participantId) {
        $rank = $index + 1;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f0f;
            position: absolute;
            animation: confetti-fall 3s linear;
        }

        @keyframes confetti-fall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }

        .trophy {
            font-size: 5rem;
            margin: 2rem 0;
        }

        .grade {
            font-size: 1.5rem;
            font-weight: 700;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            display: inline-block;
            margin: 1rem 0;
        }

        .grade-a { background: #10B981; color: white; }
        .grade-b { background: #3B82F6; color: white; }
        .grade-c { background: #F59E0B; color: white; }
        .grade-d { background: #EF4444; color: white; }
    </style>
</head>
<body>
    <main>
        <div class="container" style="max-width: 800px; margin-top: 2rem;">
            <!-- Results Card -->
            <div class="card text-center">
                <?php if ($percentage >= 80): ?>
                    <div class="trophy">🏆</div>
                    <h1 style="color: var(--success);">Excellent!</h1>
                <?php elseif ($percentage >= 60): ?>
                    <div class="trophy">⭐</div>
                    <h1 style="color: var(--primary);">Good Job!</h1>
                <?php else: ?>
                    <div class="trophy">📚</div>
                    <h1 style="color: var(--warning);">Keep Practicing!</h1>
                <?php endif; ?>

                <div style="display: inline-flex; align-items: center; gap: 1rem; margin: 1rem 0;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: <?= $participant['color_hex'] ?>; color: <?= $participant['color_text'] ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.5rem;">
                        <?= strtoupper(substr($participant['color_name'], 0, 2)) ?>
                    </div>
                    <span style="font-weight: 600; font-size: 1.5rem;"><?= $participant['color_name'] ?></span>
                </div>

                <div class="final-score">
                    <?= $score ?> / <?= $totalQuestions ?>
                </div>

                <div class="grade grade-<?= $percentage >= 90 ? 'a' : ($percentage >= 80 ? 'b' : ($percentage >= 70 ? 'c' : 'd')) ?>">
                    <?= $percentage ?>%
                </div>

                <?php if ($rank > 0): ?>
                    <div style="margin: 2rem 0;">
                        <div class="stat-label">Your Rank</div>
                        <div style="font-size: 3rem; font-weight: 700; color: var(--primary);">
                            #<?= $rank ?>
                        </div>
                        <div class="stat-label">out of <?= count($completedParticipants) ?> completed</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Question Review -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Your Answers</h3>
                </div>

                <?php foreach ($questions as $index => $question): ?>
                    <?php
                    $userAnswer = null;
                    foreach ($answers as $ans) {
                        if ($ans['question_id'] == $question['id']) {
                            $userAnswer = $ans;
                            break;
                        }
                    }
                    $isCorrect = $userAnswer ? $userAnswer['is_correct'] : false;
                    ?>

                    <div style="padding: 1rem; margin-bottom: 1rem; border-left: 4px solid <?= $isCorrect ? 'var(--success)' : 'var(--danger)' ?>; background: <?= $isCorrect ? '#D1FAE5' : '#FEE2E2' ?>;">
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">
                            <?= $isCorrect ? '✓' : '✗' ?> Question <?= $index + 1 ?>
                        </div>

                        <div style="margin-bottom: 1rem;">
                            <?= htmlspecialchars($question['question_text']) ?>
                        </div>

                        <?php if ($userAnswer): ?>
                            <div>
                                <strong>Your answer:</strong>
                                <?= $userAnswer['selected_answer'] ?>.
                                <?= htmlspecialchars($question['option_' . strtolower($userAnswer['selected_answer'])]) ?>
                            </div>
                        <?php else: ?>
                            <div><strong>Your answer:</strong> Not answered</div>
                        <?php endif; ?>

                        <?php if (!$isCorrect): ?>
                            <div style="color: var(--success); margin-top: 0.5rem;">
                                <strong>Correct answer:</strong>
                                <?= $question['correct_answer'] ?>.
                                <?= htmlspecialchars($question['option_' . strtolower($question['correct_answer'])]) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($question['explanation'])): ?>
                            <div style="margin-top: 0.5rem; font-style: italic; color: #666;">
                                <strong>Explanation:</strong> <?= htmlspecialchars($question['explanation']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Leaderboard -->
            <?php if (count($completedParticipants) > 1): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Leaderboard</h3>
                    </div>
                    <div class="leaderboard">
                        <?php foreach ($completedParticipants as $index => $p): ?>
                            <div class="leaderboard-item" style="<?= $p['id'] == $participantId ? 'background: ' . $p['color_hex'] . '20; border: 2px solid ' . $p['color_hex'] . ';' : '' ?>">
                                <div class="rank">#<?= $index + 1 ?></div>
                                <div class="leaderboard-color" style="background-color: <?= $p['color_hex'] ?>; color: <?= $p['color_text'] ?>; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                    <?= strtoupper(substr($p['color_name'], 0, 2)) ?>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600;"><?= $p['color_name'] ?></div>
                                    <div style="font-size: 0.875rem; color: #666;">
                                        Completed <?= date('h:i A', strtotime($p['completed_at'])) ?>
                                    </div>
                                </div>
                                <div class="leaderboard-score">
                                    <?= $p['score'] ?>/<?= $totalQuestions ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Waiting for others -->
            <?php if (count($completedParticipants) < count($participants)): ?>
                <div class="alert alert-info">
                    Waiting for <?= count($participants) - count($completedParticipants) ?> more participant(s) to finish...
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Confetti animation for good scores
        <?php if ($percentage >= 80): ?>
        function createConfetti() {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + '%';
            confetti.style.background = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff'][Math.floor(Math.random() * 5)];
            confetti.style.animationDelay = Math.random() * 3 + 's';
            document.body.appendChild(confetti);

            setTimeout(() => confetti.remove(), 3000);
        }

        for (let i = 0; i < 50; i++) {
            setTimeout(createConfetti, i * 50);
        }
        <?php endif; ?>

        // Auto-refresh leaderboard
        setInterval(function() {
            location.reload();
        }, 10000); // Refresh every 10 seconds
    </script>
</body>
</html>
