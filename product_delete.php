<?php
require 'config/db.php';
require 'auth.php';
require_admin();

$id = filter_var($_GET['id'] ?? $_POST['id'] ?? '', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Invalid id'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $stmt = mysqli_prepare($conn, 'DELETE FROM products WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: admin.php?ok=1'); exit;
}

// ดึงชื่อมายืนยันก่อนลบ
$stmt = mysqli_prepare($conn, 'SELECT name FROM products WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $name);
$found = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
if (!$found) { http_response_code(404); exit('ไม่พบสินค้า'); }
?>
<!doctype html><html lang="th"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>ลบสินค้า</title>
<link rel="stylesheet" href="assets/css/style.css"></head><body><div class="container">
<h2>ยืนยันการลบ</h2>
<p>ลบสินค้า "<?= esc($name) ?>" (#<?= (int)$id ?>) ใช่หรือไม่? ลบแล้วเอาคืนไม่ได้</p>
<form method="post">
  <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
  <input type="hidden" name="id" value="<?= (int)$id ?>">
  <button class="btn" type="submit" style="background:#dc2626">ลบ</button>
  <a href="admin.php">ยกเลิก</a>
</form>
</div></body></html>