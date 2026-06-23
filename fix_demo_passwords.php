<?php
/**
 * ─── fix_demo_passwords.php ─────────────────────────────────
 * SCRIPT SEKALI PAKAI — jalankan ini SATU KALI lewat browser untuk
 * memperbaiki password akun demo (admin, nata_aykal, dst) supaya
 * cocok dengan PHP di server kamu sendiri.
 *
 * Cara pakai:
 * 1. Taruh file ini di folder utama 3etube/ (sejajar index.php)
 * 2. Buka di browser: localhost/3etube/fix_demo_passwords.php
 * 3. Setelah muncul "Selesai", HAPUS file ini (untuk keamanan)
 * 4. Coba login lagi pakai akun demo, password: password
 */
require_once 'includes/config.php';

$db = getDB();
$newHash = password_hash('password', PASSWORD_BCRYPT);

$demoEmails = [
    'admin@3etube.com',
    'nata@3etube.com',
    'rendi@3etube.com',
    'fariz@3etube.com',
    'sari@3etube.com',
];

$updated = 0;
foreach ($demoEmails as $email) {
    $stmt = $db->prepare('UPDATE users SET password = ? WHERE email = ?');
    $stmt->execute([$newHash, $email]);
    if ($stmt->rowCount() > 0) $updated++;
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Fix Demo Passwords</title>
<style>body{font-family:sans-serif;background:#141210;color:#f0e8df;padding:40px;line-height:1.7}
code{background:#242018;padding:2px 8px;border-radius:4px;color:#e8a87c}
.box{background:#1a1714;border:1px solid #2e2820;border-radius:10px;padding:24px;max-width:500px}</style>
</head>
<body>
<div class="box">
<h2>✅ Selesai!</h2>
<p><?= $updated ?> akun demo berhasil diperbaiki passwordnya.</p>
<p>Sekarang kamu bisa login pakai:</p>
<ul>
<li>Email: <code>admin@3etube.com</code> atau <code>nata@3etube.com</code></li>
<li>Password: <code>password</code></li>
</ul>
<p style="color:#c87070"><b>PENTING:</b> Hapus file <code>fix_demo_passwords.php</code> ini sekarang dari folder project (untuk keamanan, karena file ini bisa reset password siapapun kalau dibiarkan).</p>
<p><a href="login.php" style="color:#e8a87c">→ Lanjut ke halaman Login</a></p>
</div>
</body>
</html>
