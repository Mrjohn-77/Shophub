<?php
require 'config/db.php';
require 'auth.php';
require_admin();

$sql = "SELECT p.id, p.name, p.price, p.image_url, c.name AS category, pl.name AS platform,
               COUNT(k.id) AS clicks
        FROM products p
        JOIN categories c  ON p.category_id = c.id
        JOIN platforms pl  ON p.platform_id = pl.id
        LEFT JOIN clicks k ON k.product_id = p.id
        GROUP BY p.id, p.name, p.price, p.image_url, c.name, pl.name
        ORDER BY c.name, p.name";
$result = mysqli_query($conn, $sql);
$byCat = [];
while ($r = mysqli_fetch_assoc($result)) { $byCat[$r['category']][] = $r; }
?>
<!doctype html><html lang="th"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin · SHOPHUB</title>
<link rel="stylesheet" href="assets/css/style.css"></head><body><div class="container">

<div class="admin-bar">
  <h2>จัดการสินค้า</h2>
  <nav class="btnrow">
    <a class="btn btn-primary" href="product_create.php">+ เพิ่มสินค้า</a>
    <a class="btn btn-ghost" href="admin_stats.php">Dashboard</a>
    <a class="btn btn-ghost" href="manage_categories.php">หมวดหมู่</a>
    <a class="btn btn-ghost" href="manage_scenes.php">Showcase</a>
    <a class="btn btn-ghost" href="manage_admins.php">แอดมิน</a>
    <a class="btn btn-ghost" href="index.php">หน้าแรก</a>
    <form method="post" action="logout.php" class="inline">
      <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
      <button class="btn btn-danger" type="submit">ออกจากระบบ</button>
    </form>
  </nav>
</div>

<?php if (isset($_GET['ok'])): ?><p class="flash-ok">บันทึกแล้ว</p><?php endif; ?>

<?php if (!$byCat): ?>
  <p class="empty">ยังไม่มีสินค้า — กด "เพิ่มสินค้า" เพื่อเริ่ม</p>
<?php else: foreach ($byCat as $catName => $rows): ?>
  <section class="cat-block">
    <div class="cat-block-head">
      <h3><span class="dot"></span><?= esc($catName) ?></h3>
      <span class="count"><?= count($rows) ?> รายการ</span>
    </div>
    <div class="tbl-wrap">
      <table class="admin-tbl">
        <thead><tr><th>สินค้า</th><th>ร้าน</th><th class="ta-r">ราคา</th><th class="ta-r">คลิก</th><th class="ta-r">จัดการ</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="td-name">
              <?php if ($r['image_url']): ?><img class="td-thumb" src="<?= esc($r['image_url']) ?>" alt=""><?php endif; ?>
              <span><?= esc($r['name']) ?></span>
            </td>
            <td><?= esc($r['platform']) ?></td>
            <td class="ta-r">฿<?= number_format((float)$r['price'], 2) ?></td>
            <td class="ta-r"><?= (int)$r['clicks'] ?></td>
            <td class="ta-r td-actions">
              <a class="btn btn-sm btn-ghost" href="product_edit.php?id=<?= (int)$r['id'] ?>">แก้ไข</a>
              <a class="btn btn-sm btn-danger-soft" href="product_delete.php?id=<?= (int)$r['id'] ?>">ลบ</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endforeach; endif; ?>

</div></body></html>
