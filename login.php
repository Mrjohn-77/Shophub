<?php
require 'config/db.php';
require 'auth.php';
if (!empty($_SESSION['admin_id'])) { header('Location: admin.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $pass  = (string)($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pass === '') {
        $error = 'กรอก email และรหัสผ่าน';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id, password_hash FROM admins WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $adminId, $hash);
        $found = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ($found && password_verify($pass, $hash)) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $adminId;
            $_SESSION['email'] = $email;
            header('Location: admin.php'); exit;
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Login</title>
<link rel="stylesheet" href="assets/css/style.css"></head><body><div class="container">
<h2>SHOPHUB · Admin Login</h2>
<?php if ($error): ?><p style="color:#f87171"><?= esc($error) ?></p><?php endif; ?>
<form method="post" style="max-width:420px">
    <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
    <p><label>Email</label><br><input name="email" type="email" required style="width:100%;padding:10px"></p>
    <p><label>Password</label><br><input name="password" type="password" required style="width:100%;padding:10px"></p>
    <p><button class="btn" type="submit">Login</button></p>
</form>
<p><a href="index.php">← กลับหน้าแรก</a></p>
</div></body></html>