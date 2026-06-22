<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { http_response_code(401); echo json_encode(['error'=>'Login required']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$type  = $input['type'] ?? '';
$id    = (int)($input['id'] ?? 0);
$db    = getDB();

if (!$id) { http_response_code(400); echo json_encode(['error'=>'Invalid request']); exit; }

if ($type === 'video') {
    $chk = $db->prepare('SELECT id FROM video_likes WHERE video_id=? AND user_id=?');
    $chk->execute([$id, $_SESSION['user_id']]);
    if ($chk->fetch()) {
        $db->prepare('DELETE FROM video_likes WHERE video_id=? AND user_id=?')->execute([$id, $_SESSION['user_id']]);
        echo json_encode(['success'=>true,'liked'=>false]);
    } else {
        $db->prepare('INSERT INTO video_likes (video_id, user_id) VALUES (?,?)')->execute([$id, $_SESSION['user_id']]);
        echo json_encode(['success'=>true,'liked'=>true]);
    }
    exit;
}

$table = match($type) { 'comment' => 'comments', 'post' => 'posts', default => null };
if (!$table) { http_response_code(400); echo json_encode(['error'=>'Invalid type']); exit; }

$db->prepare("UPDATE $table SET likes = likes + 1 WHERE id = ?")->execute([$id]);
echo json_encode(['success' => true]);