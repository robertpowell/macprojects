<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$sessionId = isset($_GET['session']) ? intval($_GET['session']) : 0;

if ($sessionId == 0) {
    json_response(['success' => false, 'error' => 'Invalid session'], 400);
}

$db = Database::getInstance();
$session = $db->getSession($sessionId);

if (!$session) {
    json_response(['success' => false, 'error' => 'Session not found'], 404);
}

$participants = $db->getParticipants($sessionId);

json_response([
    'success' => true,
    'status' => $session['status'],
    'participant_count' => count($participants),
    'started_at' => $session['started_at']
]);
?>
