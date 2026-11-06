<?php
require 'config/db.php';
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$sql = "SELECT p.id, p.name, p.price, p.image_url, c.name AS category, pl.name AS platform, COUNT(k.id) AS clicks
        FROM products p JOIN categories c ON p.category_id=c.id JOIN platforms pl ON p.platform_id=pl.id
        LEFT JOIN clicks k ON k.product_id=p.id
        GROUP BY p.id,p.name,p.price,p.image_url,c.name,pl.name ORDER BY c.name,p.name";
$result=mysqli_query($conn,$sql);
$grouped=[]; $total=0; $totalClicks=0;
while($row=mysqli_fetch_assoc($result)){ $grouped[$row['category']][]=$row; $total++; $totalClicks+=(int)$row['clicks']; }
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SHOPHUB</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<div class="container">
  <header class="hero">
    <div class="logo">SHOP<span>HUB</span></div>
    <p class="tag">รวมของเด็ดที่คัดมาแล้ว แยกเป็นหมวดให้เลือกง่าย กดเดียวถึงร้าน</p>
    <div class="stats">
      <div class="stat"><strong><?= $total ?></strong><span>สินค้า</span></div>
      <div class="stat"><strong><?= count($grouped) ?></strong><span>หมวดหมู่</span></div>
      <div class="stat"><strong><?= $totalClicks ?></strong><span>คลิกทั้งหมด</span></div>
    </div>
  </header>
  <?php foreach($grouped as $category=>$items): ?>
  <section class="category">
    <h2><span class="dot"></span><?= h($category) ?><em><?= count($items) ?> ชิ้น</em></h2>
    <div class="grid">
      <?php foreach($items as $p): $hot=(int)$p['clicks']>=3; ?>
      <article class="card">
        <div class="thumb">
          <?php if($p['image_url']): ?><img src="<?= h($p['image_url']) ?>" alt="<?= h($p['name']) ?>"><?php else: ?><span class="noimg">No image</span><?php endif; ?>
          <span class="chip"><?= h($p['platform']) ?></span>
          <?php if($hot): ?><span class="hot">HOT</span><?php endif; ?>
        </div>
        <div class="body">
          <h3><?= h($p['name']) ?></h3>
          <div class="row">
            <span class="price">฿<?= number_format((float)$p['price'],2) ?></span>
            <span class="clicks"><?= (int)$p['clicks'] ?> คลิก</span>
          </div>
          <a class="btn" href="go.php?id=<?= (int)$p['id'] ?>" target="_blank" rel="noopener">ดูสินค้า →</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endforeach; ?>
  <footer>SHOPHUB · Database Final Project · Mongkol Thongkam</footer>
</div></body></html>
