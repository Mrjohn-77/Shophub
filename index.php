<?php
require 'config/db.php';
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ดึงสินค้าทั้งหมด พร้อมชื่อหมวด ชื่อแพลตฟอร์ม และยอดคลิก (ฝึก JOIN แบบ W9)
$sql = "SELECT p.id, p.name, p.price, p.image_url,
               c.name AS category, pl.name AS platform,
               COUNT(k.id) AS clicks
        FROM products p
        JOIN categories c  ON p.category_id = c.id
        JOIN platforms pl  ON p.platform_id = pl.id
        LEFT JOIN clicks k ON k.product_id = p.id
        GROUP BY p.id, p.name, p.price, p.image_url, c.name, pl.name
        ORDER BY c.name, p.name";
$result = mysqli_query($conn, $sql);

// จัดกลุ่มตามหมวดใน PHP
$grouped = [];
while ($row = mysqli_fetch_assoc($result)) {
    $grouped[$row['category']][] = $row;
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SHOPHUB</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <header class="hero">
        <h1>SHOPHUB</h1>
        <p>รวมสินค้าแนะนำ แยกตามหมวดหมู่</p>
    </header>

    <?php foreach ($grouped as $category => $items): ?>
        <section class="category">
            <h2><?= h($category) ?></h2>
            <div class="grid">
                <?php foreach ($items as $p): ?>
                    <div class="card">
                        <div class="thumb">
                            <?php if ($p['image_url']): ?>
                                <img src="<?= h($p['image_url']) ?>" alt="<?= h($p['name']) ?>">
                            <?php else: ?>
                                <span class="noimg">No image</span>
                            <?php endif; ?>
                        </div>
                        <h3><?= h($p['name']) ?></h3>
                        <p class="price">฿<?= number_format((float)$p['price'], 2) ?></p>
                        <p class="meta"><span class="badge"><?= h($p['platform']) ?></span> · <?= (int)$p['clicks'] ?> clicks</p>
                        <a class="btn" href="go.php?id=<?= (int)$p['id'] ?>" target="_blank" rel="noopener">ดูสินค้า</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <footer>SHOPHUB · Database Final Project</footer>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>