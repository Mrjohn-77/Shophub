<?php
require 'auth.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    session_destroy();
    header('Location: login.php'); exit;
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><title>Logout</title>
<link rel="stylesheet" href="assets/css/style.css"></head><body><div class="container">
<h2>ออกจากระบบ?</h2>
<form method="post"><input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
<button class="btn" type="submit">ยืนยัน logout</button></form>
<p><a href="admin.php">← กลับ</a></p>
</div></body></html>