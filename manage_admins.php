<?php
require 'config/db.php';
require 'auth.php';
require_admin();

$error = ''; $ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $pass  = (string)($_POST['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 10) {
            $error = 'กรอก email ให้ถูกต้อง และรหัสผ่านอย่างน้อย 10 ตัว';
        } else {
            // email ซ้ำไม่ได้ (คอลัมน์ UNIQUE) ตรวจก่อนเพื่อขึ้นข้อความที่เข้าใจง่าย
            $stmt = mysqli_prepare($conn, 'SELECT id FROM admins WHERE email = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $exists = mysqli_stmt_num_rows($stmt) > 0;
            mysqli_stmt_close($stmt);
            if ($exists) {
                $error = 'มีแอดมินที่ใช้ email นี้อยู่แล้ว';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, 'INSERT INTO admins (email, password_hash) VALUES (?, ?)');
                mysqli_stmt_bind_param($stmt, 'ss', $email, $hash);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $ok = 'เพิ่มแอดมิน ' . $email . ' แล้ว';
            }
        }
    }

    if ($action === 'delete') {
        $delId = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        // กันลบตัวเอง
        if ($delId === (int)$_SESSION['admin_id']) {
            $error = 'ลบบัญชีตัวเองไม่ได้';
        } else {
            // กันลบจนไม่เหลือแอดมิน
            $res = mysqli_query($conn, 'SELECT COUNT(*) AS c FROM admins');
            $cnt = (int)mysqli_fetch_assoc($res)['c'];
            if ($cnt <= 1) {
                $error = 'ต้องมีแอดมินอย่างน้อย 1 คน';
            } else {
                $stmt = mysqli_prepare($conn, 'DELETE FROM admins WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 'i', $delId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $ok = 'ลบแอดมินแล้ว';
            }
        }
    }
}

$list = mysqli_query($conn, 'SELECT id, email, created_at FROM admins ORDER BY id');
?>
<!doctype html><html lang="th"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>จัดการแอดมิน · SHOPHUB</title>
<link rel="stylesheet" href="assets/css/style.css"></head><body><div class="container">
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
  <h2>จัดการแอดมิน (ทีม)</h2>
  <a href="admin.php">← จัดการสินค้า</a>
</div>
<?php if ($ok): ?><p class="ok"><?= esc($ok) ?></p><?php endif; ?>
<?php if ($error): ?><p style="color:#f87171"><?= esc($error) ?></p><?php endif; ?>

<h3 style="margin:18px 0 10px">เพิ่มแอดมินใหม่</h3>
<form method="post" style="max-width:480px">
  <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
  <input type="hidden" name="action" value="add">
  <p><label>Email ของเพื่อนร่วมทีม</label><input name="email" type="email" required style="width:100%;padding:10px"></p>
  <p><label>รหัสผ่านตั้งต้น (10+ ตัว)</label><input name="password" type="text" required minlength="10" style="width:100%;padding:10px"></p>
  <p><button class="btn" type="submit">เพิ่มแอดมิน</button></p>
</form>
<p style="color:var(--ink-soft);font-size:.85rem;max-width:480px">บอกรหัสตั้งต้นให้เพื่อนทางช่องทางส่วนตัว แล้วให้เพื่อนเปลี่ยนรหัสเองภายหลัง</p>

<h3 style="margin:26px 0 10px">แอดมินทั้งหมด</h3>
<table width="100%" cellpadding="8" style="border-collapse:collapse">
  <tr style="text-align:left;border-bottom:2px solid #2a1c54"><th>ID</th><th>Email</th><th>สร้างเมื่อ</th><th>จัดการ</th></tr>
  <?php while ($a = mysqli_fetch_assoc($list)): ?>
  <tr style="border-bottom:1px solid #2a1c54">
    <td><?= (int)$a['id'] ?></td>
    <td><?= esc($a['email']) ?><?= (int)$a['id']===(int)$_SESSION['admin_id'] ? ' (คุณ)' : '' ?></td>
    <td><?= esc($a['created_at']) ?></td>
    <td>
      <?php if ((int)$a['id'] !== (int)$_SESSION['admin_id']): ?>
      <form method="post" style="display:inline" onsubmit="return confirm('ลบแอดมินนี้?')">
        <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
        <button type="submit" style="background:none;border:0;color:#f87171;cursor:pointer;padding:0">ลบ</button>
      </form>
      <?php else: ?>—<?php endif; ?>
    </td>
  </tr>
  <?php endwhile; ?>
</table>
</div></body></html>
