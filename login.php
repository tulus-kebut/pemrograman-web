<?php
require_once 'includes/config.php';
if (isLoggedIn()) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email && $pass) {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            header('Location: ' . BASE_URL . '/' . ($user['role'] === 'admin' ? 'admin/index.php' : 'index.php'));
            exit;
        }
    }
    $error = 'Email atau password salah.';
}
$pageTitle = 'Sign In — 3ETube';
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
    <div class="auth-title">Welcome Back</div>
    <div class="auth-sub">Sign in to continue watching</div>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" type="email" name="email" required placeholder="you@email.com" value="<?= e($_POST['email'] ?? '') ?>"/>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input class="form-input" type="password" name="password" required placeholder="••••••••"/>
      </div>
      <button class="btn-primary form-submit" type="submit">Continue Watching →</button>
    </form>
    <div class="form-link">Don't have an account? <a href="register.php">Join Free</a></div>
    <div class="form-link" style="margin-top:8px;font-size:11px">Demo: nata@3etube.com / admin@3etube.com — password: <b>password</b></div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>