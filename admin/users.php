<?php
require_once '../includes/config.php';
requireAdmin();
$db = getDB();
$pageTitle = 'Manage Users — Admin';

// ── Toggle active/banned ──
if (isset($_GET['toggle'])) {
    $uid = (int)$_GET['toggle'];
    if ($uid !== (int)$_SESSION['user_id']) { // jangan biar admin nonaktifin diri sendiri
        $db->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ?')->execute([$uid]);
    }
    header('Location: users.php'); exit;
}

// ── Change role ──
if (isset($_GET['make_admin'])) {
    $uid = (int)$_GET['make_admin'];
    $db->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$uid]);
    header('Location: users.php'); exit;
}
if (isset($_GET['make_user'])) {
    $uid = (int)$_GET['make_user'];
    if ($uid !== (int)$_SESSION['user_id']) { // jangan biar admin nurunin role diri sendiri
        $db->prepare("UPDATE users SET role = 'user' WHERE id = ?")->execute([$uid]);
    }
    header('Location: users.php'); exit;
}

// ── Delete user ──
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid !== (int)$_SESSION['user_id']) {
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
    }
    header('Location: users.php'); exit;
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT u.*,
        (SELECT COUNT(*) FROM videos WHERE uploaded_by = u.id) AS video_count,
        (SELECT COUNT(*) FROM posts WHERE user_id = u.id) AS post_count
        FROM users u";
$params = [];
if ($search) {
    $sql .= " WHERE u.username LIKE ? OR u.email LIKE ?";
    $params = ["%$search%", "%$search%"];
}
$sql .= " ORDER BY u.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once '../includes/header.php';
?>
<div class="admin-layout">
  <?php include '_sidebar.php'; ?>
  <div class="admin-content">
    <div class="admin-title">Manage Users</div>

    <form method="GET" style="margin-bottom:18px;max-width:320px">
      <input class="form-input" type="text" name="q" placeholder="Cari username / email..." value="<?= e($search) ?>"/>
    </form>

    <div class="add-row"><h3>All Users (<?= count($users) ?>)</h3></div>
    <table class="data-table">
      <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Videos</th><th>Posts</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= e($u['username']) ?><?= $u['id'] == $_SESSION['user_id'] ? ' <span style="color:var(--accent);font-size:10px">(you)</span>' : '' ?></td>
          <td><?= e($u['email']) ?></td>
          <td><?= $u['role']==='admin' ? '<span class="role-admin">Admin</span>' : '<span class="role-user">User</span>' ?></td>
          <td><?= $u['video_count'] ?></td>
          <td><?= $u['post_count'] ?></td>
          <td><?= $u['is_active'] ? '<span class="status-live">Active</span>' : '<span class="status-banned">Inactive</span>' ?></td>
          <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td>
            <div class="tbl-acts">
              <?php if ($u['role'] === 'admin'): ?>
                <a href="users.php?make_user=<?= $u['id'] ?>" title="Jadikan User" onclick="return confirm('Turunkan role jadi User?')"><i class="ti ti-arrow-down"></i></a>
              <?php else: ?>
                <a href="users.php?make_admin=<?= $u['id'] ?>" title="Jadikan Admin" onclick="return confirm('Jadikan user ini Admin?')"><i class="ti ti-arrow-up"></i></a>
              <?php endif; ?>
              <a href="users.php?toggle=<?= $u['id'] ?>" title="<?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>"><i class="ti ti-<?= $u['is_active'] ? 'ban' : 'check' ?>"></i></a>
              <a href="users.php?delete=<?= $u['id'] ?>" class="del" title="Delete" onclick="return confirm('Hapus user ini permanen? Semua video/komentar/post miliknya juga akan terhapus.')"><i class="ti ti-trash"></i></a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>