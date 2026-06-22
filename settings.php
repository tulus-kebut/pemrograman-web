<?php
require_once 'includes/config.php';
requireLogin();
$db = getDB();
$user = currentUser();
$pageTitle = 'Settings — 3ETube';
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_account') {
    $fullname = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $bio      = trim($_POST['bio'] ?? '');
    $chk = $db->prepare('SELECT id FROM users WHERE (email=? OR username=?) AND id != ?');
    $chk->execute([$email, $username, $_SESSION['user_id']]);
    if ($chk->fetch()) {
        $error = 'Email atau username sudah dipakai user lain.';
    } else {
        $db->prepare('UPDATE users SET full_name=?, username=?, email=?, bio=? WHERE id=?')->execute([$fullname, $username, $email, $bio, $_SESSION['user_id']]);
        $_SESSION['username'] = $username;
        $success = 'Profil berhasil diperbarui.';
        $user = currentUser();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!password_verify($current, $user['password'])) $error = 'Password saat ini salah.';
    elseif (strlen($new) < 6) $error = 'Password baru minimal 6 karakter.';
    elseif ($new !== $confirm) $error = 'Konfirmasi password tidak cocok.';
    else {
        $db->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($new, PASSWORD_BCRYPT), $_SESSION['user_id']]);
        $success = 'Password berhasil diubah.';
    }
}

$savedPosts = $db->prepare("SELECT p.*, u.username, v.title AS video_title FROM saved_posts sp
    JOIN posts p ON p.id=sp.post_id JOIN users u ON u.id=p.user_id LEFT JOIN videos v ON v.id=p.video_id
    WHERE sp.user_id=? ORDER BY sp.saved_at DESC");
$savedPosts->execute([$_SESSION['user_id']]);
$savedPosts = $savedPosts->fetchAll();

$writtenPosts = $db->prepare("SELECT p.*, v.title AS video_title FROM posts p LEFT JOIN videos v ON v.id=p.video_id WHERE p.user_id=? ORDER BY p.created_at DESC");
$writtenPosts->execute([$_SESSION['user_id']]);
$writtenPosts = $writtenPosts->fetchAll();

$notifs = $db->prepare("SELECT n.* FROM notifications n WHERE n.user_id=? ORDER BY n.created_at DESC");
$notifs->execute([$_SESSION['user_id']]);
$notifs = $notifs->fetchAll();
$db->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$_SESSION['user_id']]);

require_once 'includes/header.php';
?>
<div class="page-content">
  <div class="section-hd"><span class="section-title">Settings</span></div>
  <?php if ($error):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

  <div class="settings-layout">
    <div class="settings-nav">
      <button class="snav-item active" data-target="sec-account"><i class="ti ti-user"></i> Account Info</button>
      <button class="snav-item" data-target="sec-password"><i class="ti ti-lock"></i> Password</button>
      <button class="snav-item" data-target="sec-community"><i class="ti ti-message-circle"></i> Community</button>
      <button class="snav-item" data-target="sec-notif"><i class="ti ti-bell"></i> Notifications</button>
      <button class="snav-item" data-target="sec-privacy"><i class="ti ti-shield-lock"></i> Privacy</button>
    </div>

    <div class="settings-panel">

      <div class="settings-section active" id="sec-account">
        <h3>Account Info</h3>
        <form method="POST">
          <input type="hidden" name="action" value="update_account"/>
          <div class="form-row-2">
            <div class="form-group"><label class="form-label">Full Name</label><input class="form-input" type="text" name="full_name" value="<?= e($user['full_name']) ?>"/></div>
            <div class="form-group"><label class="form-label">Username</label><input class="form-input" type="text" name="username" value="<?= e($user['username']) ?>" required/></div>
          </div>
          <div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" name="email" value="<?= e($user['email']) ?>" required/></div>
          <div class="form-group"><label class="form-label">Bio</label><textarea class="form-input" name="bio" placeholder="Ceritain dikit soal kamu..."><?= e($user['bio']) ?></textarea></div>
          <button type="submit" class="btn-primary">Save Changes</button>
        </form>
      </div>

      <div class="settings-section" id="sec-password">
        <h3>Change Password</h3>
        <form method="POST">
          <input type="hidden" name="action" value="change_password"/>
          <div class="form-group"><label class="form-label">Current Password</label><input class="form-input" type="password" name="current_password" required/></div>
          <div class="form-group"><label class="form-label">New Password</label><input class="form-input" type="password" name="new_password" required/></div>
          <div class="form-group"><label class="form-label">Confirm New Password</label><input class="form-input" type="password" name="confirm_password" required/></div>
          <button type="submit" class="btn-primary">Update Password</button>
        </form>
      </div>

      <div class="settings-section" id="sec-community">
        <h3>Saved Community Posts</h3>
        <?php if (!$savedPosts): ?>
          <div class="empty-state" style="padding:24px"><i class="ti ti-bookmark-off"></i>Belum ada post yang disimpan.</div>
        <?php else: foreach ($savedPosts as $sp): ?>
          <div class="saved-post-mini">
            <?php if ($sp['video_title']): ?><span class="pc-video-tag"><i class="ti ti-movie"></i> <?= e($sp['video_title']) ?></span><?php endif; ?>
            <div class="comment-name">@<?= e($sp['username']) ?></div>
            <div class="pc-text"><?= e(mb_strimwidth($sp['body'],0,140,'...')) ?></div>
            <a href="community.php#post-<?= $sp['id'] ?>" class="tab" style="display:inline-block;margin-top:2px">Lihat thread →</a>
          </div>
        <?php endforeach; endif; ?>

        <h3 style="margin-top:28px">Posts I Wrote</h3>
        <?php if (!$writtenPosts): ?>
          <div class="empty-state" style="padding:24px"><i class="ti ti-edit-off"></i>Kamu belum pernah nulis post.</div>
        <?php else: foreach ($writtenPosts as $wp): ?>
          <div class="saved-post-mini">
            <?php if ($wp['video_title']): ?><span class="pc-video-tag"><i class="ti ti-movie"></i> <?= e($wp['video_title']) ?></span><?php endif; ?>
            <div class="pc-text"><?= e(mb_strimwidth($wp['body'],0,140,'...')) ?></div>
            <div style="font-size:11px;color:var(--dark);display:flex;gap:10px;margin-top:4px"><span><i class="ti ti-heart"></i> <?= formatViews($wp['likes']) ?></span><span><?= timeAgo($wp['created_at']) ?></span></div>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <div class="settings-section" id="sec-notif">
        <h3>Notifications</h3>
        <?php if (!$notifs): ?>
          <div class="empty-state" style="padding:24px"><i class="ti ti-bell-off"></i>Belum ada notifikasi.</div>
        <?php else: ?>
        <div class="settings-list">
          <?php foreach ($notifs as $n): ?>
          <a href="<?= $n['post_id'] ? 'community.php#post-' . $n['post_id'] : '#' ?>" class="setting-item">
            <div class="si-icon"><i class="ti ti-message-circle"></i></div>
            <div style="flex:1"><div class="si-label"><?= e($n['message']) ?></div><div class="si-sub"><?= timeAgo($n['created_at']) ?></div></div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="settings-section" id="sec-privacy">
        <h3>Privacy & Security</h3>
        <div class="settings-list">
          <div class="setting-item">
            <div class="si-icon"><i class="ti ti-device-desktop"></i></div>
            <div style="flex:1"><div class="si-label">Active Session</div><div class="si-sub">Kamu login sebagai <?= e($user['username']) ?></div></div>
          </div>
          <a href="logout.php" class="setting-item">
            <div class="si-icon" style="background:#2a1010"><i class="ti ti-logout" style="color:var(--danger)"></i></div>
            <div style="flex:1"><div class="si-label" style="color:var(--danger)">Sign Out</div></div>
          </a>
        </div>
      </div>

    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>