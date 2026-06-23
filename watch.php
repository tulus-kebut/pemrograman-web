<?php
require_once 'includes/config.php';
$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$video = $db->prepare("SELECT v.*, c.name AS cat, u.username AS uploader,
    ROUND(AVG(r.score),1) AS avg_rating, COUNT(DISTINCT r.id) AS rating_count
    FROM videos v
    LEFT JOIN categories c ON c.id = v.category_id
    LEFT JOIN users u ON u.id = v.uploaded_by
    LEFT JOIN ratings r ON r.video_id = v.id
    WHERE v.id = ? AND v.status != 'banned'
    GROUP BY v.id");
$video->execute([$id]);
$video = $video->fetch();
if (!$video) { header('Location: index.php'); exit; }

$genres = $db->prepare("SELECT g.* FROM genres g JOIN video_genres vg ON vg.genre_id=g.id WHERE vg.video_id=?");
$genres->execute([$id]);
$genres = $genres->fetchAll();

$db->prepare('UPDATE videos SET views = views + 1 WHERE id = ?')->execute([$id]);

if (isLoggedIn()) {
    $db->prepare("INSERT INTO watch_history (user_id, video_id) VALUES (?,?) ON DUPLICATE KEY UPDATE watched_at = NOW()")
       ->execute([$_SESSION['user_id'], $id]);
}

$cmtError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if (!isLoggedIn()) { header('Location: login.php'); exit; }
    $body = trim($_POST['comment']);
    if ($body) {
        $db->prepare('INSERT INTO comments (video_id, user_id, body) VALUES (?,?,?)')->execute([$id, $_SESSION['user_id'], $body]);
        header('Location: watch.php?id=' . $id . '#comments'); exit;
    }
    $cmtError = 'Komentar tidak boleh kosong.';
}

if (isset($_POST['rating'])) {
    if (!isLoggedIn()) { header('Location: login.php'); exit; }
    $score = max(1, min(5, (int)$_POST['rating']));
    $db->prepare("INSERT INTO ratings (video_id, user_id, score) VALUES (?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score)")
       ->execute([$id, $_SESSION['user_id'], $score]);
    header('Location: watch.php?id=' . $id); exit;
}

$myRating = 0;
$myLiked  = false;
if (isLoggedIn()) {
    $r = $db->prepare('SELECT score FROM ratings WHERE video_id=? AND user_id=?');
    $r->execute([$id, $_SESSION['user_id']]);
    $myRating = (int)($r->fetchColumn() ?: 0);

    $l = $db->prepare('SELECT id FROM video_likes WHERE video_id=? AND user_id=?');
    $l->execute([$id, $_SESSION['user_id']]);
    $myLiked = (bool)$l->fetchColumn();

    $sv = $db->prepare('SELECT id FROM saved_videos WHERE video_id=? AND user_id=?');
    $sv->execute([$id, $_SESSION['user_id']]);
    $mySaved = (bool)$sv->fetchColumn();
} else {
    $mySaved = false;
}

$comments = $db->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON u.id=c.user_id
    WHERE c.video_id=? AND c.parent_id IS NULL ORDER BY c.likes DESC, c.created_at DESC");
$comments->execute([$id]);
$comments = $comments->fetchAll();

$related = $db->prepare("SELECT * FROM videos WHERE category_id=? AND id!=? AND status='live' ORDER BY views DESC LIMIT 6");
$related->execute([$video['category_id'], $id]);
$related = $related->fetchAll();

$pageTitle = $video['title'] . ' — 3ETube';
require_once 'includes/header.php';
?>
<div class="page-content">
  <div class="player-layout">

    <div class="player-main">
      <div class="player-box">
        <div class="player-screen">
          <video controls preload="metadata" poster="<?= e($video['thumbnail']) ?>">
            <source src="<?= e($video['video_url']) ?>" type="video/mp4"/>
            Browser kamu tidak mendukung video HTML5.
          </video>
        </div>
      </div>

      <div class="video-meta">
        <div class="video-title"><?= e($video['title']) ?></div>
        <div class="video-info">
          <span><i class="ti ti-eye" style="font-size:13px;margin-right:3px"></i><?= formatViews($video['views']) ?> views</span>
          <?php if ($video['avg_rating']): ?><span>★ <?= $video['avg_rating'] ?> (<?= $video['rating_count'] ?> ratings)</span>
          <?php else: ?><span style="color:var(--dark)">Belum ada rating</span><?php endif; ?>
          <span><?= formatDuration($video['duration_sec']) ?></span>
          <?php if ($video['cat']): ?><span><?= e($video['cat']) ?></span><?php endif; ?>
          <div class="video-actions">
            <button class="vact-btn <?= $myLiked?'liked':'' ?>" data-like="video" data-id="<?= $id ?>"><i class="ti ti-thumb-up"></i> Like</button>
            <button class="vact-btn <?= $mySaved?'liked':'' ?>" data-save="<?= $id ?>"><i class="ti ti-bookmark"></i> Save</button>
            <button class="vact-btn" onclick="navigator.share&&navigator.share({title:'<?= e($video['title'])?>',url:location.href})"><i class="ti ti-share"></i> Share</button>
          </div>
        </div>
        <?php if ($genres): ?>
        <div class="genre-tags"><?php foreach ($genres as $g): ?><span class="genre-tag">#<?= e($g['name']) ?></span><?php endforeach; ?></div>
        <?php endif; ?>
      </div>

      <?php if ($video['description']): ?>
      <div style="background:var(--bg2);border-radius:var(--radius);padding:14px 16px;margin-bottom:20px;font-size:13px;color:var(--dim);line-height:1.6;border:.5px solid var(--border)">
        <?= nl2br(e($video['description'])) ?>
      </div>
      <?php endif; ?>

      <form method="POST" class="rate-row">
        <span class="lbl">Rate this:</span>
        <?php for ($s=1;$s<=5;$s++): ?>
        <button type="submit" name="rating" value="<?= $s ?>" class="rate-star <?= $myRating>=$s?'filled':'' ?>">★</button>
        <?php endfor; ?>
        <?php if ($myRating): ?><span class="rate-summary">Rating kamu: <?= $myRating ?>/5</span><?php endif; ?>
      </form>

      <a href="community.php?video=<?= $id ?>" class="vact-btn" style="display:inline-flex;margin-bottom:20px">
        <i class="ti ti-message-circle-2"></i> Diskusikan video ini di Community →
      </a>

      <div class="comments-section" id="comments">
        <div class="cs-title2">Comments (<?= count($comments) ?>)</div>

        <?php if ($cmtError): ?><div class="alert alert-error"><?= e($cmtError) ?></div><?php endif; ?>
        <form method="POST" class="comment-input">
          <div class="c-av"><?= isLoggedIn() ? strtoupper(substr($_SESSION['username'],0,2)) : '?' ?></div>
          <div style="flex:1">
            <textarea name="comment" placeholder="<?= isLoggedIn() ? 'Tulis komentar...' : 'Sign in untuk berkomentar' ?>" rows="3"></textarea>
            <div style="display:flex;justify-content:flex-end;margin-top:6px">
              <button type="submit" class="btn-primary" style="padding:6px 14px;font-size:12px">Post</button>
            </div>
          </div>
        </form>

        <?php foreach ($comments as $i => $c): ?>
        <?php if ($i === 0): ?>
        <div class="pinned-comment">
          <div class="pin-label"><i class="ti ti-pin"></i> Pinned · most liked</div>
          <div class="comment-card" style="margin-bottom:0">
            <div class="c-av"><?= strtoupper(substr($c['username'],0,2)) ?></div>
            <div class="comment-body">
              <div class="comment-name"><?= e($c['username']) ?></div>
              <div class="comment-text"><?= nl2br(e($c['body'])) ?></div>
              <div class="comment-acts">
                <button class="ca" data-like="comment" data-id="<?= $c['id'] ?>"><i class="ti ti-heart"></i> <span class="like-count"><?= formatViews($c['likes']) ?></span></button>
                <span class="ca"><i class="ti ti-message-circle"></i> Reply</span>
              </div>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="comment-card">
          <div class="c-av"><?= strtoupper(substr($c['username'],0,2)) ?></div>
          <div class="comment-body">
            <div class="comment-name"><?= e($c['username']) ?></div>
            <div class="comment-text"><?= nl2br(e($c['body'])) ?></div>
            <div class="comment-acts">
              <button class="ca" data-like="comment" data-id="<?= $c['id'] ?>"><i class="ti ti-heart"></i> <span class="like-count"><?= formatViews($c['likes']) ?></span></button>
              <span class="ca"><i class="ti ti-message-circle"></i> Reply</span>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="player-sidebar">
      <div class="section-hd" style="margin-bottom:12px"><span class="section-title">Up Next</span></div>
      <?php foreach ($related as $r): ?>
      <a href="watch.php?id=<?= $r['id'] ?>" class="mini-card" style="margin-bottom:8px">
        <div class="mini-thumb" style="width:80px;height:50px"><?php if ($r['thumbnail']): ?><img src="<?= e($r['thumbnail']) ?>" alt=""/><?php else: ?><i class="ti ti-player-play"></i><?php endif; ?></div>
        <div><div class="mini-title"><?= e($r['title']) ?></div><div class="mini-views"><?= formatViews($r['views']) ?> views · <?= formatDuration($r['duration_sec']) ?></div></div>
      </a>
      <?php endforeach; ?>
    </div>

  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
