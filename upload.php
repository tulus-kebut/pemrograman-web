<?php
require_once 'includes/config.php';
requireLogin();
$db = getDB();
$pageTitle = 'Upload Video — 3ETube';

$categories = $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$genres     = $db->query('SELECT * FROM genres ORDER BY name')->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId  = (int)($_POST['category_id'] ?? 0) ?: null;
    $genreIds    = $_POST['genres'] ?? [];
    $manualDur   = (int)($_POST['manual_duration'] ?? 0);

    if (!$title) {
        $error = 'Judul wajib diisi.';
    } elseif (empty($_FILES['video_file']['name'])) {
        $error = 'File video wajib diupload.';
    } else {
        $videoFile = $_FILES['video_file'];
        $ext = strtolower(pathinfo($videoFile['name'], PATHINFO_EXTENSION));

        if ($videoFile['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload gagal, coba lagi.';
        } elseif (!in_array($ext, ['mp4','webm','mov'])) {
            $error = 'Format video harus mp4, webm, atau mov.';
        } elseif ($videoFile['size'] > 500*1024*1024) {
            $error = 'Ukuran file maksimal 500MB.';
        } else {
            $slugBase = slug($title);
            $filename = $slugBase . '_' . time() . '.' . $ext;
            $destPath = __DIR__ . '/uploads/videos/' . $filename;

            if (move_uploaded_file($videoFile['tmp_name'], $destPath)) {
                $duration = detectVideoDuration($destPath);
                if ($duration <= 0) $duration = $manualDur;

                $thumbPath = null;
                if (!empty($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $thumbExt = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
                    if (in_array($thumbExt, ['jpg','jpeg','png','webp'])) {
                        $thumbName = $slugBase . '_' . time() . '.' . $thumbExt;
                        if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], __DIR__ . '/uploads/thumbs/' . $thumbName)) {
                            $thumbPath = 'uploads/thumbs/' . $thumbName;
                        }
                    }
                }

                $status = isAdmin() ? 'live' : 'draft';
                $slugFinal = $slugBase . '-' . time();

                $ins = $db->prepare("INSERT INTO videos (title, slug, description, video_url, thumbnail, duration_sec, category_id, status, uploaded_by) VALUES (?,?,?,?,?,?,?,?,?)");
                $ins->execute([$title, $slugFinal, $description, 'uploads/videos/' . $filename, $thumbPath, $duration, $categoryId, $status, $_SESSION['user_id']]);
                $newId = $db->lastInsertId();

                foreach ($genreIds as $gid) {
                    $db->prepare('INSERT IGNORE INTO video_genres (video_id, genre_id) VALUES (?,?)')->execute([$newId, (int)$gid]);
                }

                header('Location: watch.php?id=' . $newId); exit;
            } else {
                $error = 'Gagal menyimpan file ke server.';
            }
        }
    }
}
require_once 'includes/header.php';
?>
<div class="page-content narrow">
  <div class="upload-form">
    <div class="upload-label">Upload New Video</div>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php if (!isAdmin()): ?>
    <div class="alert alert-info">Video kamu akan masuk status <b>Draft</b> dan butuh approval admin sebelum tayang publik.</div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label class="form-label">Video File (mp4/webm/mov, max 500MB)</label>
        <div class="file-drop">
          <i class="ti ti-cloud-upload"></i>
          <p>Klik atau drag file video di sini</p>
          <span class="fname"></span>
          <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime" required/>
        </div>
        <div class="duration-hint">Durasi otomatis terdeteksi dari file (butuh ffprobe di server). Kalau gagal, isi manual di bawah.</div>
      </div>

      <div class="form-group">
        <label class="form-label">Thumbnail (opsional)</label>
        <div class="file-drop" style="padding:20px">
          <i class="ti ti-photo" style="font-size:24px"></i>
          <p>Klik untuk pilih gambar thumbnail</p>
          <span class="fname"></span>
          <input type="file" name="thumbnail" accept="image/png,image/jpeg,image/webp"/>
        </div>
      </div>

      <div class="form-group"><label class="form-label">Judul Video</label><input class="form-input" type="text" name="title" required placeholder="Judul video/series"/></div>
      <div class="form-group"><label class="form-label">Deskripsi</label><textarea class="form-input" name="description" placeholder="Ceritakan tentang video ini..."></textarea></div>

      <div class="form-row-2">
        <div class="form-group">
          <label class="form-label">Kategori</label>
          <select class="form-input" name="category_id">
            <option value="">— Pilih kategori —</option>
            <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Durasi manual (detik) — fallback</label><input class="form-input" type="number" name="manual_duration" placeholder="cth: 3600" min="0"/></div>
      </div>

      <div class="form-group">
        <label class="form-label">Genre (boleh lebih dari satu)</label>
        <div class="genre-checks">
          <?php foreach ($genres as $g): ?>
          <label class="gc-label"><input type="checkbox" name="genres[]" value="<?= $g['id'] ?>"/> <?= e($g['name']) ?></label>
          <?php endforeach; ?>
        </div>
      </div>

      <button type="submit" class="btn-primary form-submit">Upload Video</button>
    </form>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>