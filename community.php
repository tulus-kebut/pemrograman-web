<?php
require_once 'includes/config.php';
$db = getDB();
$pageTitle = 'Community — 3ETube';

$filterVideoId = (int)($_GET['video'] ?? 0);
$filterVideo = null;
if ($filterVideoId) {
    $fv = $db->prepare('SELECT id, title FROM videos WHERE id = ?');
    $fv->execute([$filterVideoId]);
    $filterVideo = $fv->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_body'])) {
    requireLogin();
    $body = trim($_POST['post_body']);
    $taggedVideo = (int)($_POST['video_id'] ?? 0) ?: null;
    if ($body) {
        $db->prepare('INSERT INTO posts (user_id, video_id, body) VALUES (?,?,?)')->execute([$_SESSION['user_id'], $taggedVideo, $body]);
        header('Location: community.php' . ($taggedVideo ? '?video=' . $taggedVideo : '')); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_body'])) {
    requireLogin();
    $postId = (int)$_POST['post_id'];
    $body   = trim($_POST['reply_body']);
    if ($body) {
        $db->prepare('INSERT INTO post_replies (post_id, user_id, body) VALUES (?,?,?)')->execute([$postId, $_SESSION['user_id'], $body]);
        $owner = $db->prepare('SELECT user_id FROM posts WHERE id = ?');
        $owner->execute([$postId]);
        $ownerId = $owner->fetchColumn();
        if ($ownerId && $ownerId != $_SESSION['user_id']) {
            $db->prepare("INSERT INTO notifications (user_id, actor_id, type, post_id, message) VALUES (?,?,?,?,?)")
               ->execute([$ownerId, $_SESSION['user_id'], 'reply', $postId, $_SESSION['username'] . ' membalas post kamu']);
        }
        header('Location: community.php#post-' . $postId); exit;
    }
}

$videoOptions = $db->query("SELECT id, title FROM videos WHERE status='live' ORDER BY title")->fetchAll();

$sql = "SELECT p.*, u.username, v.title AS video_title FROM posts p
        JOIN users u ON u.id = p.user_id LEFT JOIN videos v ON v.id = p.video_id";
$params = [];
if ($filterVideoId) { $sql .= " WHERE p.video_id = ?"; $params[] = $filterVideoId; }
$sql .= " ORDER BY p.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$repliesByPost = [];
if ($posts) {
    $ids = array_column($posts, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $rs  = $db->prepare("SELECT pr.*, u.username FROM post_replies pr JOIN users u ON u.id=pr.user_id WHERE pr.post_id IN ($in) ORDER BY pr.created_at ASC");
    $rs->execute($ids);
    foreach ($rs->fetchAll() as $r) { $repliesByPost[$r['post_id']][] = $r; }
}

$savedIds = [];
if (isLoggedIn()) {
    $sv = $db->prepare('SELECT post_id FROM saved_posts WHERE user_id = ?');
    $sv->execute([$_SESSION['user_id']]);
    $savedIds = $sv->fetchAll(PDO::FETCH_COLUMN);
}

require_once 'includes/header.php';
?>
<div class="page-content narrow">

  <div class="section-hd">
    <span class="section-title">Community</span>
    <?php if ($filterVideo): ?>
      <span class="pc-video-tag"><i class="ti ti-movie"></i> <?= e($filterVideo['title']) ?></span>
      <a href="community.php" class="tab" style="margin-left:auto">Clear filter</a>
    <?php endif; ?>
  </div>

  <?php if (isLoggedIn()): ?>
  <form method="POST" class="post-composer">
    <textarea name="post_body" class="post-input-area" placeholder="What's on your mind? Share a thought, recommendation, or review..." required></textarea>
    <div class="post-tag-select">
      <select name="video_id">
        <option value="">— Tidak terkait video tertentu —</option>
        <?php foreach ($videoOptions as $vo): ?>
        <option value="<?= $vo['id'] ?>" <?= $filterVideoId==$vo['id']?'selected':'' ?>><?= e($vo['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="post-actions">
      <button type="submit" class="btn-primary" style="margin-left:auto;padding:6px 16px;font-size:12px">Post</button>
    </div>
  </form>
  <?php else: ?>
  <div class="post-composer" style="text-align:center;color:var(--dim);font-size:13px">
    <a href="login.php" style="color:var(--accent)">Sign in</a> untuk mulai diskusi atau bikin post.
  </div>
  <?php endif; ?>

  <?php if (!$posts): ?>
    <div class="empty-state"><i class="ti ti-message-off"></i>Belum ada post di sini. Jadi yang pertama!</div>
  <?php else: foreach ($posts as $p): ?>
  <div class="post-card" id="post-<?= $p['id'] ?>">
    <?php if ($p['video_title']): ?><a href="community.php?video=<?= $p['video_id'] ?>" class="pc-video-tag"><i class="ti ti-movie"></i> <?= e($p['video_title']) ?></a><?php endif; ?>
    <div class="pc-head">
      <div class="c-av"><?= strtoupper(substr($p['username'],0,2)) ?></div>
      <div class="pc-name"><?= e($p['username']) ?></div>
      <div class="pc-time"><?= timeAgo($p['created_at']) ?></div>
    </div>
    <div class="pc-text"><?= nl2br(e($p['body'])) ?></div>
    <div class="pc-acts">
      <button class="ca" data-like="post" data-id="<?= $p['id'] ?>"><i class="ti ti-heart"></i> <span class="like-count"><?= formatViews($p['likes']) ?></span></button>
      <span class="ca"><i class="ti ti-repeat"></i> <?= formatViews($p['reposts']) ?></span>
      <span class="ca"><i class="ti ti-message-circle"></i> <?= count($repliesByPost[$p['id']] ?? []) ?> replies</span>
      <?php if (isLoggedIn()): ?>
      <button class="ca <?= in_array($p['id'],$savedIds)?'liked':'' ?>" data-save-post="<?= $p['id'] ?>"><i class="ti ti-bookmark"></i> Save</button>
      <?php endif; ?>
    </div>

    <?php if (!empty($repliesByPost[$p['id']])): ?>
    <span class="toggle-replies" data-target="replies-<?= $p['id'] ?>">Show <?= count($repliesByPost[$p['id']]) ?> replies</span>
    <div class="pc-replies" id="replies-<?= $p['id'] ?>" style="display:none">
      <?php foreach ($repliesByPost[$p['id']] as $r): ?>
      <div class="reply-row">
        <div class="c-av" style="width:26px;height:26px;font-size:9px"><?= strtoupper(substr($r['username'],0,2)) ?></div>
        <div><div class="comment-name"><?= e($r['username']) ?></div><div class="comment-text"><?= nl2br(e($r['body'])) ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (isLoggedIn()): ?>
    <form method="POST" class="reply-form">
      <input type="hidden" name="post_id" value="<?= $p['id'] ?>"/>
      <div class="c-av" style="width:26px;height:26px;font-size:9px"><?= strtoupper(substr($_SESSION['username'],0,2)) ?></div>
      <input type="text" name="reply_body" placeholder="Tulis balasan..." required/>
      <button type="submit" class="btn-primary btn-sm">Reply</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endforeach; endif; ?>

</div>
<?php require_once 'includes/footer.php'; ?>
