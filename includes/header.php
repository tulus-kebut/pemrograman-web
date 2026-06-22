<?php
// ─── includes/header.php ────────────────────────────────
require_once __DIR__ . '/config.php';
$user = currentUser();
$notifCount = unreadNotifCount();

$notifList = [];
if (isLoggedIn()) {
    $db = getDB();
    $stmt = $db->prepare("SELECT n.*, u.username AS actor_name FROM notifications n
        LEFT JOIN users u ON u.id = n.actor_id
        WHERE n.user_id = ? ORDER BY n.created_at DESC LIMIT 6");
    $stmt->execute([$_SESSION['user_id']]);
    $notifList = $stmt->fetchAll();
}

// Categories for sidebar (always fetched, cheap query)
$sidebarCategories = getDB()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= e($pageTitle ?? SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/main.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet"/>
</head>
<body>

<nav class="navbar" id="navbar">
  <div class="nav-left">
    <button class="hamburger" id="sidebarToggle" aria-label="Toggle category menu">
      <span></span><span></span><span></span>
    </button>
    <a href="<?= BASE_URL ?>/index.php" class="logo-link">
      <svg class="logo-svg" viewBox="0 0 52 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="3ETube logo">
        <text x="13" y="27" font-family="Arial Black,Arial" font-weight="900" font-size="30"
              fill="#e8a87c" text-anchor="middle" transform="matrix(-1,0,0,1,26,0)">3</text>
        <text x="39" y="27" font-family="Arial Black,Arial" font-weight="900" font-size="30"
              fill="#e8a87c" text-anchor="middle">3</text>
        <circle cx="26" cy="16" r="7.5" fill="#0e0c0a"/>
        <polygon points="23,12 31,16 23,20" fill="#f0e8df"/>
      </svg>
      <span class="logo-word">Tube</span>
    </a>
  </div>

  <div class="nav-center">
    <form class="search-wrap" action="<?= BASE_URL ?>/search.php" method="GET">
      <input type="text" name="q" placeholder="Search videos, series..." value="<?= e($_GET['q'] ?? '') ?>"/>
      <button type="submit" class="search-btn" aria-label="Search"><i class="ti ti-search"></i></button>
    </form>
  </div>

  <div class="nav-right">
    <?php if (isLoggedIn()): ?>
      <a href="<?= BASE_URL ?>/upload.php" class="upload-btn"><i class="ti ti-plus"></i> Upload</a>
      <div class="ib notif-wrap" id="notifBtn">
        <i class="ti ti-bell"></i>
        <?php if ($notifCount > 0): ?><span class="bdg"><?= $notifCount > 9 ? '9+' : $notifCount ?></span><?php endif; ?>
        <div class="dropdown notif-dropdown" id="notifDropdown">
          <div class="notif-head">Notifications</div>
          <?php if (!$notifList): ?>
            <div class="notif-empty">Belum ada notifikasi.</div>
          <?php else: foreach ($notifList as $n): ?>
            <a href="<?= BASE_URL ?>/community.php<?= $n['post_id'] ? '#post-' . $n['post_id'] : '' ?>" class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
              <div class="notif-dot"></div>
              <div class="notif-text"><?= e($n['message']) ?><span class="notif-time"><?= timeAgo($n['created_at']) ?></span></div>
            </a>
          <?php endforeach; endif; ?>
        </div>
      </div>
      <div class="nav-avatar" id="avatarBtn">
        <?= strtoupper(substr($user['username'], 0, 2)) ?>
        <div class="dropdown" id="avatarDropdown">
          <a href="<?= BASE_URL ?>/profile.php"><i class="ti ti-user"></i> Profile</a>
          <a href="<?= BASE_URL ?>/settings.php"><i class="ti ti-settings"></i> Settings</a>
          <a href="<?= BASE_URL ?>/community.php"><i class="ti ti-message-circle"></i> Community</a>
          <?php if (isAdmin()): ?>
          <a href="<?= BASE_URL ?>/admin/index.php"><i class="ti ti-layout-dashboard"></i> Admin Panel</a>
          <?php endif; ?>
          <div class="dd-divider"></div>
          <a href="<?= BASE_URL ?>/logout.php" class="dd-danger"><i class="ti ti-logout"></i> Sign Out</a>
        </div>
      </div>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/login.php"    class="btn-ghost">Sign In</a>
      <a href="<?= BASE_URL ?>/register.php" class="btn-primary">Join Free</a>
    <?php endif; ?>
  </div>
</nav>

<!-- Sidebar kategori, toggle dari hamburger -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="category-sidebar" id="categorySidebar">
  <div class="cs-title">Browse</div>
  <a href="<?= BASE_URL ?>/index.php" class="cs-item"><i class="ti ti-home"></i> Home</a>
  <a href="<?= BASE_URL ?>/index.php?sort=trending" class="cs-item"><i class="ti ti-flame"></i> Trending</a>
  <a href="<?= BASE_URL ?>/community.php" class="cs-item"><i class="ti ti-message-circle"></i> Community</a>
  <div class="cs-divider"></div>
  <div class="cs-title">Categories</div>
  <?php foreach ($sidebarCategories as $c): ?>
  <a href="<?= BASE_URL ?>/index.php?cat=<?= e($c['slug']) ?>" class="cs-item"><i class="ti ti-movie"></i> <?= e($c['name']) ?></a>
  <?php endforeach; ?>
  <?php if (isLoggedIn()): ?>
  <div class="cs-divider"></div>
  <a href="<?= BASE_URL ?>/profile.php" class="cs-item"><i class="ti ti-history"></i> Watch History</a>
  <a href="<?= BASE_URL ?>/upload.php" class="cs-item"><i class="ti ti-cloud-upload"></i> Upload Video</a>
  <?php endif; ?>
</aside>