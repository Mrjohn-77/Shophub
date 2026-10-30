<?php
require 'config/db.php';
require 'auth.php';
require_admin();

// โหลดหมวดและแพลตฟอร์มไว้ทำ dropdown
$cats = mysqli_query($conn, 'SELECT id, name FROM categories ORDER BY name');
$plats = mysqli_query($conn, 'SELECT id, name FROM platforms ORDER BY name');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $category_id = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);
    $platform_id = filter_var($_POST['platform_id'] ?? '', FILTER_VALIDATE_INT);
    $name  = trim((string)($_POST['name'] ?? ''));
    $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
    $image = trim((string)($_POST['image_url'] ?? ''));
    $url   = trim((string)($_POST['affiliate_url'] ?? ''));

    if (!$category_id || !$platform_id || $name === '' || $price === false || $url === '') {
        $error = 'กรอกข้อมูลให้ครบ (ชื่อ ราคา หมวด ร้าน และลิงก์)';
    } else {
        $sql = 'INSERT INTO products (category_id, platform_id, name, price, image_url, affiliate_url)
                VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'iisdss', $category_id, $platform_id, $name, $price, $image, $url);
        if (mysqli_stmt_execute($stmt)) { mysqli_stmt_close($stmt); header('Location: admin.php?ok=1'); exit; }
        $error = 'บันทึกไม่สำเร็จ'; mysqli_stmt_close($stmt);
    }
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>เพิ่มสินค้า</title>
<link rel="stylesheet" href="assets/css/style.css"></head><body><div class="container">
<h2>เพิ่มสินค้า</h2>
<?php if ($error): ?><p style="color:#f87171"><?= esc($error) ?></p><?php endif; ?>
<form method="post" style="max-width:520px">
  <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
  <p><label>ชื่อสินค้า</label><br><input name="name" required style="width:100%;padding:10px"></p>
  <p><label>ราคา (บาท)</label><br><input name="price" type="number" step="0.01" min="0" required style="width:100%;padding:10px"></p>
  <p><label>หมวดหมู่</label><br>
    <select name="category_id" required style="width:100%;padding:10px">
      <option value="">-- เลือก --</option>
      <?php while ($c = mysqli_fetch_assoc($cats)): ?>
        <option value="<?= (int)$c['id'] ?>"><?= esc($c['name']) ?></option>
      <?php endwhile; ?>
    </select></p>
  <p><label>ร้าน/แพลตฟอร์ม</label><br>
    <select name="platform_id" required style="width:100%;padding:10px">
      <option value="">-- เลือก --</option>
      <?php while ($pl = mysqli_fetch_assoc($plats)): ?>
        <option value="<?= (int)$pl['id'] ?>"><?= esc($pl['name']) ?></option>
      <?php endwhile; ?>
    </select></p>
  <p><label>ลิงก์รูปภาพ (ใส่หรือเว้นว่างก็ได้)</label><br><input name="image_url" style="width:100%;padding:10px"></p>
  <p><label>ลิงก์ Affiliate</label><br><input name="affiliate_url" required style="width:100%;padding:10px"></p>
  <p><button class="btn" type="submit">บันทึก</button> <a href="admin.php">ยกเลิก</a></p>
</form>
</div></body></html>