<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$sessionId = isset($_GET['session']) ? intval($_GET['session']) : 0;

if ($sessionId == 0) {
    json_response(['success' => false, 'error' => 'Invalid session'], 400);
}

$db = Database::getInstance();
$stats = $db->getSessionStats($sessionId);

json_response([
    'success' => true,
    'total' => $stats['total_participants'],
    'completed' => $stats['completed_participants'],
    'average_score' => $stats['average_score'],
    'highest_score' => $stats['highest_score'],
    'lowest_score' => $stats['lowest_score']
]);
?>
