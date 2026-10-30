<?php
require 'config/db.php';
require 'auth.php';
require_admin();

$sql = "SELECT p.id, p.name, p.price, c.name AS category, pl.name AS platform,
               COUNT(k.id) AS clicks
        FROM products p
        JOIN categories c  ON p.category_id = c.id
        JOIN platforms pl  ON p.platform_id = pl.id
        LEFT JOIN clicks k ON k.product_id = p.id
        GROUP BY p.id, p.name, p.price, c.name, pl.name
        ORDER BY p.id DESC";
$result = mysqli_query($conn, $sql);
?>
<!doctype html><html lang="th"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin · SHOPHUB</title>
<link rel="stylesheet" href="assets/css/style.css"></head><body><div class="container">
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
  <h2>จัดการสินค้า</h2>
  <div>
    <a class="btn" href="product_create.php">+ เพิ่มสินค้า</a>
    <a href="index.php">หน้าแรก</a>
    <form method="post" action="logout.php" style="display:inline">
      <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
      <button class="btn" type="submit">Logout</button>
    </form>
  </div>
</div>
<?php if (isset($_GET['ok'])): ?><p class="price">บันทึกแล้ว</p><?php endif; ?>
<table width="100%" cellpadding="8" style="border-collapse:collapse;margin-top:16px">
  <tr style="text-align:left;border-bottom:2px solid #243352">
    <th>ID</th><th>สินค้า</th><th>หมวด</th><th>ร้าน</th><th>ราคา</th><th>คลิก</th><th>จัดการ</th>
  </tr>
  <?php while ($r = mysqli_fetch_assoc($result)): ?>
  <tr style="border-bottom:1px solid #243352">
    <td><?= (int)$r['id'] ?></td>
    <td><?= esc($r['name']) ?></td>
    <td><?= esc($r['category']) ?></td>
    <td><?= esc($r['platform']) ?></td>
    <td>฿<?= number_format((float)$r['price'], 2) ?></td>
    <td><?= (int)$r['clicks'] ?></td>
    <td>
      <a href="product_edit.php?id=<?= (int)$r['id'] ?>">แก้ไข</a> |
      <a href="product_delete.php?id=<?= (int)$r['id'] ?>" style="color:#f87171">ลบ</a>
    </td>
  </tr>
  <?php endwhile; ?>
</table>
</div></body></html>