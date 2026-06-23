<?php
require_once '../includes/config.php';
requireAdmin();
$db = getDB();
$pageTitle = 'Moderate Comments — Admin';
$tab = $_GET['tab'] ?? 'comments';

if (isset($_GET['delete_comment'])) { $db->prepare('DELETE FROM comments WHERE id=?')->execute([(int)$_GET['delete_comment']]); header('Location: comments.php?tab=comments'); exit; }
if (isset($_GET['delete_post']))    { $db->prepare('DELETE FROM posts WHERE id=?')->execute([(int)$_GET['delete_post']]); header('Location: comments.php?tab=posts'); exit; }

$comments = $db->query("SELECT c.*, u.username, v.title AS video_title FROM comments c
    JOIN users u ON u.id=c.user_id LEFT JOIN videos v ON v.id=c.video_id ORDER BY c.created_at DESC LIMIT 100")->fetchAll();

$posts = $db->query("SELECT p.*, u.username, v.title AS video_title,
    (SELECT COUNT(*) FROM post_replies WHERE post_id=p.id) AS reply_count
    FROM posts p JOIN users u ON u.id=p.user_id LEFT JOIN videos v ON v.id=p.video_id
    ORDER BY p.created_at DESC LIMIT 100")->fetchAll();

require_once '../includes/header.php';
?>
<div class="admin-layout">
  <?php include '_sidebar.php'; ?>
  <div class="admin-content">
    <div class="admin-title">Moderate Comments & Posts</div>
    <div class="tabs" style="margin-bottom:18px;margin-left:0">
      <a href="comments.php?tab=comments" class="tab <?= $tab==='comments'?'active':'' ?>">Video Comments (<?= count($comments) ?>)</a>
      <a href="comments.php?tab=posts" class="tab <?= $tab==='posts'?'active':'' ?>">Community Posts (<?= count($posts) ?>)</a>
    </div>

    <?php if ($tab === 'comments'): ?>
    <table class="data-table">
      <thead><tr><th>User</th><th>Video</th><th>Comment</th><th>Likes</th><th>Posted</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (!$comments): ?><tr><td colspan="6" style="text-align:center;color:var(--dark);padding:24px">Belum ada komentar.</td></tr>
      <?php else: foreach ($comments as $c): ?>
        <tr>
          <td><?= e($c['username']) ?></td>
          <td><?= e($c['video_title'] ?? '—') ?></td>
          <td style="max-width:280px"><?= e(mb_strimwidth($c['body'],0,80,'...')) ?></td>
          <td><?= formatViews($c['likes']) ?></td>
          <td><?= timeAgo($c['created_at']) ?></td>
          <td><div class="tbl-acts"><a href="comments.php?delete_comment=<?= $c['id'] ?>" class="del" title="Delete" onclick="return confirm('Hapus komentar ini?')"><i class="ti ti-trash"></i></a></div></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    <?php else: ?>
    <table class="data-table">
      <thead><tr><th>User</th><th>Tagged Video</th><th>Post</th><th>Likes</th><th>Replies</th><th>Posted</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (!$posts): ?><tr><td colspan="7" style="text-align:center;color:var(--dark);padding:24px">Belum ada post komunitas.</td></tr>
      <?php else: foreach ($posts as $p): ?>
        <tr>
          <td><?= e($p['username']) ?></td>
          <td><?= e($p['video_title'] ?? '—') ?></td>
          <td style="max-width:280px"><?= e(mb_strimwidth($p['body'],0,80,'...')) ?></td>
          <td><?= formatViews($p['likes']) ?></td>
          <td><?= $p['reply_count'] ?></td>
          <td><?= timeAgo($p['created_at']) ?></td>
          <td><div class="tbl-acts"><a href="../community.php#post-<?= $p['id'] ?>" target="_blank" title="View"><i class="ti ti-eye"></i></a><a href="comments.php?delete_post=<?= $p['id'] ?>" class="del" title="Delete" onclick="return confirm('Hapus post ini?')"><i class="ti ti-trash"></i></a></div></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
