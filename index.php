<?php
require_once 'includes/config.php';
$pageTitle = '3ETube — Watch Anything';
$db = getDB();

$hero = $db->query("SELECT v.*, c.name AS cat, ROUND(AVG(r.score),1) AS avg_rating
  FROM videos v LEFT JOIN categories c ON c.id=v.category_id LEFT JOIN ratings r ON r.video_id=v.id
  WHERE v.status='live' GROUP BY v.id ORDER BY v.views DESC LIMIT 1")->fetch();

$activeCat = $_GET['cat'] ?? '';
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$catSql = "SELECT v.*, ROUND(AVG(r.score),1) AS avg_rating FROM videos v
  LEFT JOIN ratings r ON r.video_id=v.id LEFT JOIN categories c ON c.id=v.category_id
  WHERE v.status='live'";
$params = [];
if ($activeCat) { $catSql .= " AND c.slug = ?"; $params[] = $activeCat; }
$catSql .= " GROUP BY v.id ORDER BY avg_rating DESC, v.views DESC LIMIT 12";
$stmt = $db->prepare($catSql); $stmt->execute($params);
$best = $stmt->fetchAll();

$trending = $db->query("SELECT * FROM videos WHERE status='live' ORDER BY views DESC LIMIT 6")->fetchAll();
$most_watched = $db->query("SELECT * FROM videos WHERE status='live' ORDER BY views DESC LIMIT 5")->fetchAll();
$side = $db->query("SELECT * FROM videos WHERE status='live' ORDER BY RAND() LIMIT 3")->fetchAll();

$continueWatching = [];
if (isLoggedIn()) {
    $cw = $db->prepare("SELECT v.*, wh.watched_at FROM watch_history wh
        JOIN videos v ON v.id=wh.video_id WHERE wh.user_id=? ORDER BY wh.watched_at DESC LIMIT 6");
    $cw->execute([$_SESSION['user_id']]);
    $continueWatching = $cw->fetchAll();
}

require_once 'includes/header.php';
?>
<div class="page-content">

  <?php if ($hero): ?>
  <div class="hero-row">
    <a href="watch.php?id=<?= $hero['id'] ?>" class="hero-main">
      <div class="hero-thumb">
        <?php if ($hero['thumbnail']): ?><img src="<?= e($hero['thumbnail']) ?>" alt=""/><?php else: ?><i class="ti ti-movie fallback"></i><?php endif; ?>
        <span class="htag">Trending #1</span>
        <div class="play-overlay"><div class="play-circle"><i class="ti ti-player-play"></i></div></div>
      </div>
      <div class="hero-info">
        <div class="hero-title"><?= e($hero['title']) ?></div>
        <div class="hero-meta">
          <?= formatViews($hero['views']) ?> views &nbsp;·&nbsp;
          <?php if ($hero['avg_rating']): ?>★ <?= $hero['avg_rating'] ?> &nbsp;·&nbsp;<?php else: ?>Belum ada rating &nbsp;·&nbsp;<?php endif; ?>
          <?= formatDuration($hero['duration_sec']) ?>
        </div>
      </div>
    </a>
    <div class="hero-side">
      <?php foreach ($side as $s): ?>
      <a href="watch.php?id=<?= $s['id'] ?>" class="mini-card">
        <div class="mini-thumb"><?php if ($s['thumbnail']): ?><img src="<?= e($s['thumbnail']) ?>" alt=""/><?php else: ?><i class="ti ti-player-play"></i><?php endif; ?></div>
        <div><div class="mini-title"><?= e($s['title']) ?></div><div class="mini-views"><?= formatViews($s['views']) ?> views</div></div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($continueWatching): ?>
  <div class="section">
    <div class="section-hd"><span class="section-title">Continue Watching</span></div>
    <div class="cards-grid">
      <?php foreach ($continueWatching as $v): ?>
      <a href="watch.php?id=<?= $v['id'] ?>" class="vcard">
        <div class="vcard-thumb"><?php if ($v['thumbnail']): ?><img src="<?= e($v['thumbnail']) ?>" alt=""/><?php else: ?><i class="ti ti-movie"></i><?php endif; ?><span class="duration"><?= formatDuration($v['duration_sec']) ?></span></div>
        <div class="vcard-info"><div class="vcard-title"><?= e($v['title']) ?></div><div class="vcard-stat"><?= timeAgo($v['watched_at']) ?></div></div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="section">
    <div class="section-hd">
      <span class="section-title">Best Rated</span><span class="section-badge">TOP</span>
      <div class="tabs">
        <a href="index.php" class="tab <?= !$activeCat?'active':'' ?>">All</a>
        <?php foreach ($categories as $c): ?>
        <a href="index.php?cat=<?= e($c['slug']) ?>" class="tab <?= $activeCat===$c['slug']?'active':'' ?>"><?= e($c['name']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if (!$best): ?>
      <div class="empty-state"><i class="ti ti-movie-off"></i>Belum ada video di kategori ini.</div>
    <?php else: ?>
    <div class="cards-grid">
      <?php foreach ($best as $v): ?>
      <a href="watch.php?id=<?= $v['id'] ?>" class="vcard">
        <div class="vcard-thumb"><?php if ($v['thumbnail']): ?><img src="<?= e($v['thumbnail']) ?>" alt=""/><?php else: ?><i class="ti ti-movie"></i><?php endif; ?><span class="duration"><?= formatDuration($v['duration_sec']) ?></span></div>
        <div class="vcard-info">
          <div class="vcard-title"><?= e($v['title']) ?></div>
          <div class="vcard-stat">
            <i class="ti ti-eye"></i><?= formatViews($v['views']) ?>
            <?php if ($v['avg_rating']): ?><span class="stars"><?= str_repeat('★',(int)round($v['avg_rating'])).str_repeat('☆',5-(int)round($v['avg_rating'])) ?></span>
            <?php else: ?><span class="no-rating">Belum dirate</span><?php endif; ?>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="two-col">
    <div class="col section">
      <div class="section-hd"><span class="section-title">Trending Now</span><i class="ti ti-flame" style="color:var(--accent);font-size:15px"></i></div>
      <div class="trend-list">
        <?php foreach ($trending as $i => $v): ?>
        <a href="watch.php?id=<?= $v['id'] ?>" class="trend-item">
          <span class="tnum <?= $i<2?'hot':'' ?>"><?= $i+1 ?></span>
          <div><div class="ttitle"><?= e($v['title']) ?></div><div class="tmeta">↑ <?= formatViews(intval($v['views']*.18)) ?> views today</div></div>
          <?php if ($i<2): ?><i class="ti ti-flame" style="color:var(--accent);font-size:14px;margin-left:auto"></i><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="col section">
      <div class="section-hd"><span class="section-title">Most Watched</span></div>
      <div class="trend-list">
        <?php foreach ($most_watched as $i => $v): ?>
        <a href="watch.php?id=<?= $v['id'] ?>" class="trend-item">
          <span class="tnum <?= $i<2?'hot':'' ?>"><?= $i+1 ?></span>
          <div><div class="ttitle"><?= e($v['title']) ?></div><div class="tmeta"><?= formatViews($v['views']) ?> total views</div></div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>
<?php require_once 'includes/footer.php'; ?>
