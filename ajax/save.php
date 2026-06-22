<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { http_response_code(401); echo json_encode(['error'=>'Login required']); exit; }

$input   = json_decode(file_get_contents('php://input'), true);
$videoId = (int)($input['video_id'] ?? 0);
$db      = getDB();

if (!$videoId) { http_response_code(400); echo json_encode(['error'=>'Invalid video']); exit; }

$chk = $db->prepare('SELECT id FROM saved_videos WHERE user_id=? AND video_id=?');
$chk->execute([$_SESSION['user_id'], $videoId]);

if ($chk->fetch()) {
    $db->prepare('DELETE FROM saved_videos WHERE user_id=? AND video_id=?')->execute([$_SESSION['user_id'], $videoId]);
    echo json_encode(['saved' => false]);
} else {
    $db->prepare('INSERT INTO saved_videos (user_id, video_id) VALUES (?,?)')->execute([$_SESSION['user_id'], $videoId]);
    echo json_encode(['saved' => true]);
}