<?php
require_once 'includes/config.php';
if (isLoggedIn()) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $fullname = trim($_POST['full_name']?? '');
    $pass     = $_POST['password']      ?? '';
    $pass2    = $_POST['password2']     ?? '';

    if (!$username || !$email || !$pass)    $error = 'Semua field wajib diisi.';
    elseif ($pass !== $pass2)               $error = 'Password tidak cocok.';
    elseif (strlen($pass) < 6)              $error = 'Password minimal 6 karakter.';
    else {
        $db   = getDB();
        $chk  = $db->prepare('SELECT id FROM users WHERE email=? OR username=?');
        $chk->execute([$email, $username]);
        if ($chk->fetch()) {
            $error = 'Email atau username sudah digunakan.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $ins  = $db->prepare('INSERT INTO users (username,email,password,full_name) VALUES (?,?,?,?)');
            $ins->execute([$username, $email, $hash, $fullname]);
            $success = 'Akun berhasil dibuat! <a href="login.php">Sign in sekarang</a>';
        }
    }
}
$pageTitle = 'Join 3ETube';
require_once 'includes/header.php';
?>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <a href="index.php" class="logo-link">
        <svg class="logo-svg" viewBox="0 0 52 32" fill="none">
          <text x="13" y="27" font-family="Arial Black,Arial" font-weight="900" font-size="30" fill="#e8a87c" text-anchor="middle" transform="matrix(-1,0,0,1,26,0)">3</text>
          <text x="39" y="27" font-family="Arial Black,Arial" font-weight="900" font-size="30" fill="#e8a87c" text-anchor="middle">3</text>
          <circle cx="26" cy="16" r="7.5" fill="#1a1714"/>
          <polygon points="23,12 31,16 23,20" fill="#f0e8df"/>
        </svg>
        <span class="logo-word">Tube</span>
      </a>
    </div>
    <div class="auth-title">Create Account</div>
    <div class="auth-sub">Join the community, watch anything</div>
    <?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input class="form-input" type="text" name="full_name" placeholder="Nama lengkap" value="<?= e($_POST['full_name']??'') ?>"/>
      </div>
      <div class="form-group">
        <label class="form-label">Username</label>
        <input class="form-input" type="text" name="username" required placeholder="username_kamu" value="<?= e($_POST['username']??'') ?>"/>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" type="email" name="email" required placeholder="you@email.com" value="<?= e($_POST['email']??'') ?>"/>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input class="form-input" type="password" name="password" required placeholder="Min. 6 karakter"/>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <input class="form-input" type="password" name="password2" required placeholder="Ulangi password"/>
      </div>
      <button class="btn-primary form-submit" type="submit">Create Account</button>
    </form>
    <div class="form-link">Sudah punya akun? <a href="login.php">Sign in</a></div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>