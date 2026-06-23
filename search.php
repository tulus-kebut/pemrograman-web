<?php
require_once 'includes/config.php';
$db = getDB();
$q  = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'relevance';
$pageTitle = $q ? "Search: $q — 3ETube" : 'Search — 3ETube';

$results = [];
if ($q !== '') {
    $order = match($sort) {
        'views'  => 'v.views DESC',
        'rating' => 'avg_rating DESC',
        'newest' => 'v.created_at DESC',
        default  => 'v.views DESC',
    };
    $stmt = $db->prepare("SELECT v.*, ROUND(AVG(r.score),1) AS avg_rating
        FROM videos v LEFT JOIN ratings r ON r.video_id=v.id
        WHERE v.status='live' AND (v.title LIKE ? OR v.description LIKE ?)
        GROUP BY v.id ORDER BY $order LIMIT 30");
    $like = "%$q%";
    $stmt->execute([$like, $like]);
    $results = $stmt->fetchAll();
}
require_once 'includes/header.php';
?>
<div class="page-content">
  <div class="search-header">
    <?php if ($q === ''): ?>
      <div class="search-q">Cari video, series, atau dokumenter</div>
    <?php else: ?>
      <div class="search-q">Results for "<?= e($q) ?>"</div>
      <div class="search-count">Showing <?= count($results) ?> results</div>
    <?php endif; ?>
  </div>

  <?php if ($q !== ''): ?>
  <div class="filter-row">
    <a href="?q=<?= urlencode($q) ?>&sort=relevance" class="fchip <?= $sort==='relevance'?'active':'' ?>">Relevance</a>
    <a href="?q=<?= urlencode($q) ?>&sort=views" class="fchip <?= $sort==='views'?'active':'' ?>">Most Viewed</a>
    <a href="?q=<?= urlencode($q) ?>&sort=rating" class="fchip <?= $sort==='rating'?'active':'' ?>">Best Rated</a>
    <a href="?q=<?= urlencode($q) ?>&sort=newest" class="fchip <?= $sort==='newest'?'active':'' ?>">Newest</a>
  </div>

  <?php if (!$results): ?>
    <div class="empty-state"><i class="ti ti-search-off"></i>Nggak ada video yang cocok dengan "<?= e($q) ?>". Coba kata kunci lain.</div>
  <?php else: ?>
  <div class="search-results">
    <?php foreach ($results as $v): ?>
    <a href="watch.php?id=<?= $v['id'] ?>" class="sr-item">
      <div class="sr-thumb"><?php if ($v['thumbnail']): ?><img src="<?= e($v['thumbnail']) ?>" alt=""/><?php else: ?><i class="ti ti-movie"></i><?php endif; ?><span class="duration" style="z-index:2"><?= formatDuration($v['duration_sec']) ?></span></div>
      <div class="sr-info">
        <div class="sr-title"><?= e($v['title']) ?></div>
        <div class="sr-meta"><?= formatViews($v['views']) ?> views<?php if ($v['avg_rating']): ?> · ★ <?= $v['avg_rating'] ?><?php endif; ?></div>
        <div class="sr-desc"><?= e(mb_strimwidth($v['description'] ?? '', 0, 160, '...')) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
