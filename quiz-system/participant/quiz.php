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

// Check if already completed
if ($participant['completed_at']) {
    header('Location: results.php');
    exit;
}

$session = $db->getSession($sessionId);
$quiz = $db->getQuiz($session['quiz_id']);

// If quiz hasn't started, redirect to waiting room
if ($session['status'] !== 'active') {
    header('Location: waiting.php');
    exit;
}

// Get participant's questions
$questions = $db->getParticipantQuestions($participantId);

// Get already submitted answers
$submittedAnswers = $db->getParticipantAnswers($participantId);
$answeredQuestionIds = array_column($submittedAnswers, 'question_id');

// Calculate time remaining
$startTime = strtotime($session['started_at']);
$endTime = $startTime + $quiz['time_limit'];
$currentTime = time();
$timeRemaining = max(0, $endTime - $currentTime);

// If time is up, redirect to results
if ($timeRemaining == 0 && !$participant['completed_at']) {
    // Auto-submit and mark as complete
    $score = count(array_filter($submittedAnswers, fn($a) => $a['is_correct'] == 1));
    $db->completeParticipant($participantId, $score);
    header('Location: results.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($quiz['name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .question-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 2rem;
            justify-content: center;
        }

        .question-nav-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid var(--border);
            background: white;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .question-nav-btn:hover {
            border-color: var(--primary);
        }

        .question-nav-btn.current {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .question-nav-btn.answered {
            background: var(--success);
            color: white;
            border-color: var(--success);
        }

        .fixed-timer {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: white;
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 100;
        }

        @media (max-width: 768px) {
            .fixed-timer {
                top: 0;
                right: 0;
                left: 0;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Fixed Timer -->
    <div class="fixed-timer">
        <div class="stat-label text-center">Time Remaining</div>
        <div class="timer" id="timer" data-end-time="<?= $endTime ?>">
            <?= floor($timeRemaining / 60) ?>:<?= str_pad($timeRemaining % 60, 2, '0', STR_PAD_LEFT) ?>
        </div>
    </div>

    <main style="padding-top: 6rem;">
        <div class="container" style="max-width: 800px;">
            <!-- Your Color Badge -->
            <div class="card text-center" style="background: linear-gradient(135deg, <?= $participant['color_hex'] ?>20, white);">
                <div style="display: inline-flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $participant['color_hex'] ?>; color: <?= $participant['color_text'] ?>; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        <?= strtoupper(substr($participant['color_name'], 0, 2)) ?>
                    </div>
                    <span style="font-weight: 600; font-size: 1.25rem;"><?= $participant['color_name'] ?></span>
                </div>
            </div>

            <!-- Question Navigation -->
            <div class="card">
                <h3 class="text-center mb-2">Questions</h3>
                <div class="question-nav">
                    <?php foreach ($questions as $index => $q): ?>
                        <button class="question-nav-btn <?= $index == 0 ? 'current' : '' ?> <?= in_array($q['id'], $answeredQuestionIds) ? 'answered' : '' ?>"
                                onclick="showQuestion(<?= $index ?>)"
                                id="nav-<?= $index ?>">
                            <?= $index + 1 ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="text-center">
                    <span class="badge badge-success">● Answered</span>
                    <span class="badge badge-info">● Current</span>
                </div>
            </div>

            <!-- Questions -->
            <?php foreach ($questions as $index => $question): ?>
                <div class="question-container" id="question-<?= $index ?>" style="display: <?= $index == 0 ? 'block' : 'none' ?>;">
                    <div class="question-number">
                        Question <?= $index + 1 ?> of <?= count($questions) ?>
                    </div>

                    <div class="question-text">
                        <?= htmlspecialchars($question['question_text']) ?>
                    </div>

                    <?php
                    $isAnswered = in_array($question['id'], $answeredQuestionIds);
                    $selectedAnswer = '';
                    if ($isAnswered) {
                        foreach ($submittedAnswers as $ans) {
                            if ($ans['question_id'] == $question['id']) {
                                $selectedAnswer = $ans['selected_answer'];
                                break;
                            }
                        }
                    }
                    ?>

                    <ul class="options-list">
                        <?php foreach (['A', 'B', 'C', 'D'] as $option): ?>
                            <li class="option-item">
                                <button class="option-button <?= $selectedAnswer === $option ? 'selected' : '' ?>"
                                        data-question-id="<?= $question['id'] ?>"
                                        data-option="<?= $option ?>"
                                        onclick="selectAnswer(<?= $index ?>, '<?= $option ?>', <?= $question['id'] ?>)"
                                        <?= $isAnswered ? 'disabled' : '' ?>>
                                    <strong><?= $option ?>.</strong>
                                    <?= htmlspecialchars($question['option_' . strtolower($option)]) ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="flex-between mt-3">
                        <?php if ($index > 0): ?>
                            <button class="btn btn-outline" onclick="showQuestion(<?= $index - 1 ?>)">
                                ← Previous
                            </button>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>

                        <?php if ($index < count($questions) - 1): ?>
                            <button class="btn btn-primary" onclick="showQuestion(<?= $index + 1 ?>)">
                                Next →
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary" onclick="finishQuiz()">
                                Finish Quiz
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        const questions = <?= json_encode($questions) ?>;
        const answeredQuestions = new Set(<?= json_encode($answeredQuestionIds) ?>);
        let currentQuestion = 0;

        // Timer
        function updateTimer() {
            const timerEl = document.getElementById('timer');
            const endTime = parseInt(timerEl.dataset.endTime);
            const now = Math.floor(Date.now() / 1000);
            const remaining = Math.max(0, endTime - now);

            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;

            timerEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            // Change color based on time
            timerEl.className = 'timer';
            if (remaining < 60) {
                timerEl.classList.add('danger');
            } else if (remaining < 180) {
                timerEl.classList.add('warning');
            }

            // Auto-submit when time is up
            if (remaining === 0) {
                alert('Time is up! Submitting your quiz...');
                window.location.href = 'results.php';
            }
        }

        setInterval(updateTimer, 1000);
        updateTimer();

        // Navigation
        function showQuestion(index) {
            // Hide current question
            document.getElementById(`question-${currentQuestion}`).style.display = 'none';
            document.getElementById(`nav-${currentQuestion}`).classList.remove('current');

            // Show new question
            currentQuestion = index;
            document.getElementById(`question-${currentQuestion}`).style.display = 'block';
            document.getElementById(`nav-${currentQuestion}`).classList.add('current');

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Answer selection
        function selectAnswer(questionIndex, option, questionId) {
            if (answeredQuestions.has(questionId)) {
                return; // Already answered
            }

            // Submit answer
            fetch('../api/submit-answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    participant_id: <?= $participantId ?>,
                    question_id: questionId,
                    selected_answer: option
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    answeredQuestions.add(questionId);

                    // Mark as answered in UI
                    document.getElementById(`nav-${questionIndex}`).classList.add('answered');

                    // Disable all options for this question
                    const buttons = document.querySelectorAll(`[data-question-id="${questionId}"]`);
                    buttons.forEach(btn => {
                        btn.disabled = true;
                        if (btn.dataset.option === option) {
                            btn.classList.add('selected');
                        }
                    });

                    // Auto-advance to next question
                    setTimeout(() => {
                        if (questionIndex < questions.length - 1) {
                            showQuestion(questionIndex + 1);
                        }
                    }, 500);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to submit answer. Please try again.');
            });
        }

        function finishQuiz() {
            const unanswered = questions.length - answeredQuestions.size;

            if (unanswered > 0) {
                if (!confirm(`You have ${unanswered} unanswered question(s). Are you sure you want to finish?`)) {
                    return;
                }
            }

            // Mark as complete
            fetch('../api/complete-quiz.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    participant_id: <?= $participantId ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'results.php';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to submit quiz. Please try again.');
            });
        }

        // Prevent accidental page refresh
        window.addEventListener('beforeunload', function(e) {
            if (answeredQuestions.size < questions.length) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>
