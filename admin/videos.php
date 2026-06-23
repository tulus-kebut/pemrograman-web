<?php
require_once '../includes/config.php';
requireAdmin();
$db = getDB();
$pageTitle = 'Manage Videos — Admin';

$categories = $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$genres     = $db->query('SELECT * FROM genres ORDER BY name')->fetchAll();

$editId = (int)($_GET['edit'] ?? 0);
$editVideo = null;
$editGenreIds = [];
if ($editId) {
    $ev = $db->prepare('SELECT * FROM videos WHERE id = ?');
    $ev->execute([$editId]);
    $editVideo = $ev->fetch();
    if ($editVideo) {
        $eg = $db->prepare('SELECT genre_id FROM video_genres WHERE video_id = ?');
        $eg->execute([$editId]);
        $editGenreIds = $eg->fetchAll(PDO::FETCH_COLUMN);
    }
}

if (isset($_GET['delete'])) {
    $db->prepare('DELETE FROM videos WHERE id = ?')->execute([(int)$_GET['delete']]);
    header('Location: videos.php'); exit;
}

// Quick approve draft -> live
if (isset($_GET['approve'])) {
    $db->prepare("UPDATE videos SET status='live' WHERE id = ?")->execute([(int)$_GET['approve']]);
    header('Location: videos.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = (int)($_POST['id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId  = (int)($_POST['category_id'] ?? 0) ?: null;
    $duration    = (int)($_POST['duration_sec'] ?? 0);
    $status      = $_POST['status'] ?? 'draft';
    $videoUrl    = trim($_POST['video_url'] ?? '');
    $thumbnail   = trim($_POST['thumbnail'] ?? '');
    $genreIds    = $_POST['genres'] ?? [];

    if (!$title || !$videoUrl) {
        $error = 'Judul dan path video wajib diisi.';
    } else {
        if ($id) {
            $db->prepare("UPDATE videos SET title=?, description=?, category_id=?, duration_sec=?, status=?, video_url=?, thumbnail=? WHERE id=?")
               ->execute([$title, $description, $categoryId, $duration, $status, $videoUrl, $thumbnail, $id]);
            $db->prepare('DELETE FROM video_genres WHERE video_id = ?')->execute([$id]);
        } else {
            $slugFinal = slug($title) . '-' . time();
            $db->prepare("INSERT INTO videos (title, slug, description, video_url, thumbnail, duration_sec, category_id, status, uploaded_by) VALUES (?,?,?,?,?,?,?,?,?)")
               ->execute([$title, $slugFinal, $description, $videoUrl, $thumbnail ?: null, $duration, $categoryId, $status, $_SESSION['user_id']]);
            $id = $db->lastInsertId();
        }
        foreach ($genreIds as $gid) {
            $db->prepare('INSERT IGNORE INTO video_genres (video_id, genre_id) VALUES (?,?)')->execute([$id, (int)$gid]);
        }
        header('Location: videos.php'); exit;
    }
}

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT v.*, c.name AS cat FROM videos v LEFT JOIN categories c ON c.id = v.category_id";
$params = [];
if ($statusFilter) { $sql .= " WHERE v.status = ?"; $params[] = $statusFilter; }
$sql .= " ORDER BY v.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$videos = $stmt->fetchAll();

require_once '../includes/header.php';
?>
<div class="admin-layout">
  <?php include '_sidebar.php'; ?>
  <div class="admin-content">
    <div class="admin-title">Manage Videos</div>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <div class="admin-form-card" style="margin-bottom:24px">
      <h3 style="font-size:14px;color:var(--cream);margin-bottom:14px"><?= $editVideo ? 'Edit Video: ' . e($editVideo['title']) : 'Add New Video' ?></h3>
      <form method="POST">
        <input type="hidden" name="id" value="<?= $editVideo['id'] ?? '' ?>"/>
        <div class="form-group"><label class="form-label">Title</label><input class="form-input" type="text" name="title" required value="<?= e($editVideo['title'] ?? '') ?>"/></div>
        <div class="form-group"><label class="form-label">Description</label><textarea class="form-input" name="description"><?= e($editVideo['description'] ?? '') ?></textarea></div>
        <div class="form-row-2">
          <div class="form-group"><label class="form-label">Video URL / Path</label><input class="form-input" type="text" name="video_url" required placeholder="uploads/videos/file.mp4" value="<?= e($editVideo['video_url'] ?? '') ?>"/></div>
          <div class="form-group"><label class="form-label">Thumbnail Path</label><input class="form-input" type="text" name="thumbnail" placeholder="uploads/thumbs/file.jpg" value="<?= e($editVideo['thumbnail'] ?? '') ?>"/></div>
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Category</label>
            <select class="form-input" name="category_id">
              <option value="">— None —</option>
              <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= ($editVideo['category_id']??0)==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Duration (seconds)</label><input class="form-input" type="number" name="duration_sec" min="0" value="<?= e((string)($editVideo['duration_sec'] ?? 0)) ?>"/></div>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-input" name="status">
            <option value="draft"  <?= ($editVideo['status']??'')==='draft'?'selected':'' ?>>Draft</option>
            <option value="live"   <?= ($editVideo['status']??'')==='live'?'selected':'' ?>>Live</option>
            <option value="banned" <?= ($editVideo['status']??'')==='banned'?'selected':'' ?>>Banned</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Genres</label>
          <div class="genre-checks">
            <?php foreach ($genres as $g): ?>
            <label class="gc-label"><input type="checkbox" name="genres[]" value="<?= $g['id'] ?>" <?= in_array($g['id'],$editGenreIds)?'checked':'' ?>/> <?= e($g['name']) ?></label>
            <?php endforeach; ?>
          </div>
        </div>
        <button type="submit" class="btn-primary"><?= $editVideo ? 'Update Video' : 'Add Video' ?></button>
        <?php if ($editVideo): ?><a href="videos.php" class="btn-ghost">Cancel</a><?php endif; ?>
      </form>
    </div>

    <div class="add-row">
      <h3>All Videos (<?= count($videos) ?>)</h3>
      <div class="tabs" style="margin-left:0">
        <a href="videos.php" class="tab <?= !$statusFilter?'active':'' ?>">All</a>
        <a href="videos.php?status=live" class="tab <?= $statusFilter==='live'?'active':'' ?>">Live</a>
        <a href="videos.php?status=draft" class="tab <?= $statusFilter==='draft'?'active':'' ?>">Draft</a>
        <a href="videos.php?status=banned" class="tab <?= $statusFilter==='banned'?'active':'' ?>">Banned</a>
      </div>
    </div>
    <table class="data-table">
      <thead><tr><th>Title</th><th>Category</th><th>Duration</th><th>Views</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($videos as $v): ?>
        <tr>
          <td><?= e($v['title']) ?></td>
          <td><?= e($v['cat'] ?? '—') ?></td>
          <td><?= formatDuration($v['duration_sec']) ?></td>
          <td><?= formatViews($v['views']) ?></td>
          <td>
            <?php if ($v['status']==='live'): ?><span class="status-live">Live</span>
            <?php elseif ($v['status']==='draft'): ?><span class="status-draft">Draft</span>
            <?php else: ?><span class="status-banned">Banned</span><?php endif; ?>
          </td>
          <td>
            <div class="tbl-acts">
              <a href="../watch.php?id=<?= $v['id'] ?>" target="_blank" title="View"><i class="ti ti-eye"></i></a>
              <?php if ($v['status']==='draft'): ?><a href="videos.php?approve=<?= $v['id'] ?>" title="Approve & Publish"><i class="ti ti-check"></i></a><?php endif; ?>
              <a href="videos.php?edit=<?= $v['id'] ?>" title="Edit"><i class="ti ti-edit"></i></a>
              <a href="videos.php?delete=<?= $v['id'] ?>" class="del" title="Delete" onclick="return confirm('Hapus video ini?')"><i class="ti ti-trash"></i></a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
