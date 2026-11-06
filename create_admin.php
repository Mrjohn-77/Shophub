<?php
require 'config/db.php';
require 'auth.php';

// กันไม่ให้สร้างซ้ำ: ถ้ามีแอดมินอยู่แล้วให้หยุด
$res = mysqli_query($conn, 'SELECT COUNT(*) AS c FROM admins');
$row = mysqli_fetch_assoc($res);
if ((int)$row['c'] > 0) { exit('มีแอดมินอยู่แล้ว กรุณาลบไฟล์ create_admin.php ทิ้ง'); }

$error = ''; $done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $pass  = (string)($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 10) {
        $error = 'กรอก email ให้ถูกต้อง และรหัสผ่านอย่างน้อย 10 ตัว';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);   // เก็บเป็น hash ไม่ใช่ plain text
        $stmt = mysqli_prepare($conn, 'INSERT INTO admins (email, password_hash) VALUES (?, ?)');
        mysqli_stmt_bind_param($stmt, 'ss', $email, $hash);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $done = true;
    }
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>Create Admin</title>
<link rel="stylesheet" href="assets/css/style.css"></head><body><div class="container">
<?php if ($done): ?>
    <h2>สร้างบัญชีแอดมินสำเร็จ</h2>
    <p class="price">สำคัญ: ลบไฟล์ create_admin.php ทิ้งทันที แล้วไปที่ <a href="login.php">login.php</a></p>
<?php else: ?>
    <h2>สร้างบัญชีแอดมิน (ครั้งเดียว)</h2>
    <?php if ($error): ?><p style="color:#f87171"><?= esc($error) ?></p><?php endif; ?>
    <form method="post" style="max-width:420px">
        <p><label>Email</label><br><input name="email" type="email" required style="width:100%;padding:10px"></p>
        <p><label>Password (10+ ตัว)</label><br><input name="password" type="password" required minlength="10" style="width:100%;padding:10px"></p>
        <p><button class="btn" type="submit">สร้างบัญชี</button></p>
    </form>
<?php endif; ?>
</div></body></html>