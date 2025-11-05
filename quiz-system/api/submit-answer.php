<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['participant_id']) || !isset($input['question_id']) || !isset($input['selected_answer'])) {
    json_response(['success' => false, 'error' => 'Invalid input'], 400);
}

$participantId = intval($input['participant_id']);
$questionId = intval($input['question_id']);
$selectedAnswer = strtoupper(trim($input['selected_answer']));

if (!in_array($selectedAnswer, ['A', 'B', 'C', 'D'])) {
    json_response(['success' => false, 'error' => 'Invalid answer'], 400);
}

$db = Database::getInstance();

// Verify participant exists
$participants = $db->getParticipants($_SESSION['session_id']);
$participant = null;
foreach ($participants as $p) {
    if ($p['id'] == $participantId) {
        $participant = $p;
        break;
    }
}

if (!$participant) {
    json_response(['success' => false, 'error' => 'Participant not found'], 404);
}

// Get question to check correct answer
$questions = $db->getParticipantQuestions($participantId);
$question = null;
foreach ($questions as $q) {
    if ($q['id'] == $questionId) {
        $question = $q;
        break;
    }
}

if (!$question) {
    json_response(['success' => false, 'error' => 'Question not found'], 404);
}

// Check if answer is correct
$isCorrect = ($selectedAnswer === $question['correct_answer']) ? 1 : 0;

// Submit answer
try {
    $db->submitAnswer($participantId, $questionId, $selectedAnswer, $isCorrect);

    log_activity('quiz-participation', 'Answer submitted', [
        'participant_id' => $participantId,
        'question_id' => $questionId,
        'correct' => $isCorrect
    ]);

    json_response([
        'success' => true,
        'is_correct' => $isCorrect
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'error' => 'Database error'], 500);
}
?>
