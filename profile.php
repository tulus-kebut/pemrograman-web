<?php
require_once 'includes/config.php';
requireLogin();
$db = getDB();
$user = currentUser();
$pageTitle = $user['username'] . ' — Profile';

$watchedCount = $db->prepare('SELECT COUNT(*) FROM watch_history WHERE user_id=?');
$watchedCount->execute([$_SESSION['user_id']]);
$watchedCount = (int)$watchedCount->fetchColumn();

$savedCount = $db->prepare('SELECT COUNT(*) FROM saved_videos WHERE user_id=?');
$savedCount->execute([$_SESSION['user_id']]);
$savedCount = (int)$savedCount->fetchColumn();

$postsCount = $db->prepare('SELECT COUNT(*) FROM posts WHERE user_id=?');
$postsCount->execute([$_SESSION['user_id']]);
$postsCount = (int)$postsCount->fetchColumn();

$history = $db->prepare("SELECT v.*, wh.watched_at FROM watch_history wh JOIN videos v ON v.id=wh.video_id WHERE wh.user_id=? ORDER BY wh.watched_at DESC LIMIT 12");
$history->execute([$_SESSION['user_id']]);
$history = $history->fetchAll();

$saved = $db->prepare("SELECT v.* FROM saved_videos sv JOIN videos v ON v.id=sv.video_id WHERE sv.user_id=? ORDER BY sv.saved_at DESC LIMIT 12");
$saved->execute([$_SESSION['user_id']]);
$saved = $saved->fetchAll();

$myPosts = $db->prepare("SELECT p.*, v.title AS video_title FROM posts p LEFT JOIN videos v ON v.id=p.video_id WHERE p.user_id=? ORDER BY p.created_at DESC LIMIT 10");
$myPosts->execute([$_SESSION['user_id']]);
$myPosts = $myPosts->fetchAll();

require_once 'includes/header.php';
?>
<div class="page-content">

  <div class="profile-header">
    <div class="profile-av"><?php if ($user['avatar']): ?><img src="<?= e($user['avatar']) ?>" alt=""/><?php else: ?><?= strtoupper(substr($user['username'],0,2)) ?><?php endif; ?></div>
    <div>
      <div class="profile-name"><?= e($user['full_name'] ?: $user['username']) ?></div>
      <div class="profile-sub">@<?= e($user['username']) ?> · Member since <?= date('Y', strtotime($user['created_at'])) ?></div>
      <?php if ($user['bio']): ?><div class="profile-bio"><?= e($user['bio']) ?></div><?php endif; ?>
      <div class="profile-stats">
        <button class="ps-item ptab-link" data-target="tab-history"><div class="ps-num"><?= $watchedCount ?></div><div class="ps-label">Watched</div></button>
        <button class="ps-item ptab-link" data-target="tab-saved"><div class="ps-num"><?= $savedCount ?></div><div class="ps-label">Saved</div></button>
        <button class="ps-item ptab-link" data-target="tab-posts"><div class="ps-num"><?= $postsCount ?></div><div class="ps-label">Posts</div></button>
      </div>
      <a href="settings.php" class="btn-ghost" style="margin-top:10px;display:inline-block">Edit Profile</a>
    </div>
  </div>

  <div class="profile-tabs">
    <button class="ptab active" data-target="tab-history">Watch History</button>
    <button class="ptab" data-target="tab-saved">Saved Videos</button>
    <button class="ptab" data-target="tab-posts">My Posts</button>
  </div>

  <div class="profile-section" id="tab-history">
    <?php if (!$history): ?>
      <div class="empty-state"><i class="ti ti-history-off"></i>Belum ada riwayat tonton.</div>
    <?php else: ?>
    <div class="cards-grid">
      <?php foreach ($history as $v): ?>
      <a href="watch.php?id=<?= $v['id'] ?>" class="vcard">
        <div class="vcard-thumb"><?php if ($v['thumbnail']): ?><img src="<?= e($v['thumbnail']) ?>" alt=""/><?php else: ?><i class="ti ti-movie"></i><?php endif; ?><span class="duration"><?= formatDuration($v['duration_sec']) ?></span></div>
        <div class="vcard-info"><div class="vcard-title"><?= e($v['title']) ?></div><div class="vcard-stat"><?= timeAgo($v['watched_at']) ?></div></div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="profile-section" id="tab-saved" style="display:none">
    <?php if (!$saved): ?>
      <div class="empty-state"><i class="ti ti-bookmark-off"></i>Belum ada video yang disimpan.</div>
    <?php else: ?>
    <div class="cards-grid">
      <?php foreach ($saved as $v): ?>
      <a href="watch.php?id=<?= $v['id'] ?>" class="vcard">
        <div class="vcard-thumb"><?php if ($v['thumbnail']): ?><img src="<?= e($v['thumbnail']) ?>" alt=""/><?php else: ?><i class="ti ti-movie"></i><?php endif; ?><span class="duration"><?= formatDuration($v['duration_sec']) ?></span></div>
        <div class="vcard-info"><div class="vcard-title"><?= e($v['title']) ?></div></div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="profile-section" id="tab-posts" style="display:none">
    <?php if (!$myPosts): ?>
      <div class="empty-state"><i class="ti ti-message-off"></i>Kamu belum bikin post apapun.</div>
    <?php else: foreach ($myPosts as $p): ?>
    <div class="post-card">
      <?php if ($p['video_title']): ?><span class="pc-video-tag"><i class="ti ti-movie"></i> <?= e($p['video_title']) ?></span><?php endif; ?>
      <div class="pc-text"><?= nl2br(e($p['body'])) ?></div>
      <div class="pc-acts">
        <span class="ca"><i class="ti ti-heart"></i> <?= formatViews($p['likes']) ?></span>
        <span class="ca"><i class="ti ti-repeat"></i> <?= formatViews($p['reposts']) ?></span>
        <span class="pc-time"><?= timeAgo($p['created_at']) ?></span>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>

</div>
<script>
document.querySelectorAll('.ptab-link').forEach(b => b.addEventListener('click', () => document.querySelector(`.ptab[data-target="${b.dataset.target}"]`).click()));
</script>
<?php require_once 'includes/footer.php'; ?>