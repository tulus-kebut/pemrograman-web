<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<div class="admin-sidebar">
  <div class="admin-logo">3ETube Admin</div>
  <a href="index.php" class="sb-item <?= $currentPage==='index.php'?'active':'' ?>"><i class="ti ti-layout-dashboard"></i><span>Dashboard</span></a>
  <a href="videos.php" class="sb-item <?= $currentPage==='videos.php'?'active':'' ?>"><i class="ti ti-movie"></i><span>Videos</span></a>
  <a href="users.php" class="sb-item <?= $currentPage==='users.php'?'active':'' ?>"><i class="ti ti-users"></i><span>Users</span></a>
  <a href="comments.php" class="sb-item <?= $currentPage==='comments.php'?'active':'' ?>"><i class="ti ti-message-circle"></i><span>Comments</span></a>
  <a href="../index.php" class="sb-item" style="margin-top:auto"><i class="ti ti-arrow-back"></i><span>Back to Site</span></a>
</div>
