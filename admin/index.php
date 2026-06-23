<?php
require_once '../includes/config.php';
requireAdmin();
$db = getDB();
$pageTitle = 'Admin Dashboard — 3ETube';

$totalVideos   = $db->query("SELECT COUNT(*) FROM videos")->fetchColumn();
$totalUsers    = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalViews    = $db->query("SELECT SUM(views) FROM videos")->fetchColumn() ?: 0;
$totalComments = $db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$draftCount    = $db->query("SELECT COUNT(*) FROM videos WHERE status='draft'")->fetchColumn();

$recentVideos = $db->query("SELECT v.*, c.name AS cat FROM videos v
    LEFT JOIN categories c ON c.id = v.category_id
    ORDER BY v.created_at DESC LIMIT 8")->fetchAll();

require_once '../includes/header.php';
?>
<div class="admin-layout">
  <?php include '_sidebar.php'; ?>
  <div class="admin-content">
    <div class="admin-title">Dashboard Overview</div>
    <div class="stat-grid">
      <div class="stat-card"><div class="sc-val"><?= $totalVideos ?></div><div class="sc-label">Total Videos</div></div>
      <div class="stat-card"><div class="sc-val"><?= number_format($totalUsers) ?></div><div class="sc-label">Total Users</div></div>
      <div class="stat-card"><div class="sc-val"><?= formatViews((int)$totalViews) ?></div><div class="sc-label">Total Views</div></div>
      <div class="stat-card"><div class="sc-val"><?= number_format($totalComments) ?></div><div class="sc-label">Comments</div></div>
    </div>

    <?php if ($draftCount > 0): ?>
    <div class="alert alert-info">
      <i class="ti ti-clock"></i> Ada <b><?= $draftCount ?></b> video status <b>Draft</b> menunggu approval. <a href="videos.php?status=draft" style="color:var(--accent)">Lihat & approve →</a>
    </div>
    <?php endif; ?>

    <div class="add-row"><h3>Recently Added Videos</h3><a href="videos.php" class="btn-ghost btn-sm">Manage All Videos →</a></div>
    <table class="data-table">
      <thead><tr><th>Title</th><th>Category</th><th>Views</th><th>Status</th><th>Uploaded</th></tr></thead>
      <tbody>
      <?php foreach ($recentVideos as $v): ?>
        <tr>
          <td><?= e($v['title']) ?></td>
          <td><?= e($v['cat'] ?? '—') ?></td>
          <td><?= formatViews($v['views']) ?></td>
          <td>
            <?php if ($v['status']==='live'): ?><span class="status-live">Live</span>
            <?php elseif ($v['status']==='draft'): ?><span class="status-draft">Draft</span>
            <?php else: ?><span class="status-banned">Banned</span><?php endif; ?>
          </td>
          <td><?= timeAgo($v['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
