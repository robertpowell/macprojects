<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['participant_id'])) {
    json_response(['success' => false, 'error' => 'Invalid input'], 400);
}

$participantId = intval($input['participant_id']);

$db = Database::getInstance();

// Get participant's answers
$answers = $db->getParticipantAnswers($participantId);
$score = count(array_filter($answers, fn($a) => $a['is_correct'] == 1));

// Mark as complete
try {
    $db->completeParticipant($participantId, $score);

    log_activity('quiz-participation', 'Quiz completed', [
        'participant_id' => $participantId,
        'score' => $score,
        'total_questions' => count($answers)
    ]);

    json_response([
        'success' => true,
        'score' => $score,
        'total' => count($answers)
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'error' => 'Database error'], 500);
}
?>
