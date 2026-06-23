<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { http_response_code(401); echo json_encode(['error'=>'Login required']); exit; }

$input  = json_decode(file_get_contents('php://input'), true);
$postId = (int)($input['post_id'] ?? 0);
$db     = getDB();

if (!$postId) { http_response_code(400); echo json_encode(['error'=>'Invalid post']); exit; }

$chk = $db->prepare('SELECT id FROM saved_posts WHERE user_id=? AND post_id=?');
$chk->execute([$_SESSION['user_id'], $postId]);

if ($chk->fetch()) {
    $db->prepare('DELETE FROM saved_posts WHERE user_id=? AND post_id=?')->execute([$_SESSION['user_id'], $postId]);
    echo json_encode(['saved' => false]);
} else {
    $db->prepare('INSERT INTO saved_posts (user_id, post_id) VALUES (?,?)')->execute([$_SESSION['user_id'], $postId]);
    echo json_encode(['saved' => true]);
}
