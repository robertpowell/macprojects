<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$sessionId = isset($_GET['session']) ? intval($_GET['session']) : 0;

if ($sessionId == 0) {
    json_response(['success' => false, 'error' => 'Invalid session'], 400);
}

$db = Database::getInstance();
$participants = $db->getParticipants($sessionId);

json_response([
    'success' => true,
    'participants' => $participants
]);
?>
