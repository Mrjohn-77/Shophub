<?php
require 'config/db.php';
require 'auth.php';
require_admin();

// จุดตัดรอบวัน 04:00 น. (คำนวณฝั่ง DB)
$cutoff = "CASE WHEN HOUR(NOW()) >= 4 THEN CONCAT(CURDATE(),' 04:00:00')
                ELSE CONCAT(CURDATE() - INTERVAL 1 DAY, ' 04:00:00') END";

// --- สรุปตัวเลขรวม ---
$sumSql = "SELECT
  (SELECT COUNT(*) FROM products) AS products,
  (SELECT COUNT(*) FROM categories) AS categories,
  (SELECT COUNT(*) FROM clicks) AS clicks_total,
  (SELECT COUNT(*) FROM clicks WHERE clicked_at >= ($cutoff)) AS clicks_today";
$sum = mysqli_fetch_assoc(mysqli_query($conn, $sumSql));

// --- สินค้าฮิต 5 อันดับ (JOIN + GROUP BY + ORDER BY) ---
$topSql = "SELECT p.id, p.name, pl.name AS platform, COUNT(k.id) AS c
           FROM products p
           JOIN platforms pl ON p.platform_id = pl.id
           LEFT JOIN clicks k ON k.product_id = p.id
           GROUP BY p.id, p.name, pl.name
           ORDER BY c DESC, p.name
           LIMIT 5";
$top = []; $tr = mysqli_query($conn, $topSql);
while ($r = mysqli_fetch_assoc($tr)) $top[] = $r;
$topMax = 1; foreach ($top as $r) $topMax = max($topMax, (int)$r['c']);

// --- ยอดคลิกต่อหมวด (LEFT JOIN เพื่อให้หมวดที่ยังไม่มีคลิกก็ขึ้น) ---
$catSql = "SELECT c.name, COUNT(k.id) AS c
           FROM categories c
           LEFT JOIN products p ON p.category_id = c.id
           LEFT JOIN clicks k   ON k.product_id = p.id
           GROUP BY c.id, c.name
           ORDER BY c DESC, c.name";
$catRows = []; $cr = mysqli_query($conn, $catSql);
while ($r = mysqli_fetch_assoc($cr)) $catRows[] = $r;
$catMax = 1; foreach ($catRows as $r) $catMax = max($catMax, (int)$r['c']);

// --- ยอดคลิกรวม 7 วันล่าสุด (GROUP BY DATE) ---
$dSql = "SELECT DATE(clicked_at) AS d, COUNT(*) AS c
         FROM clicks
         WHERE clicked_at >= CURDATE() - INTERVAL 6 DAY
         GROUP BY DATE(clicked_at)";
$byDate = []; $dr = mysqli_query($conn, $dSql);
while ($r = mysqli_fetch_assoc($dr)) $byDate[$r['d']] = (int)$r['c'];
$days = [];
for ($i = 6; $i >= 0; $i--) {
  $d = date('Y-m-d', strtotime("-$i day"));
  $days[] = ['label'=>date('D', strtotime($d)), 'c'=>$byDate[$d] ?? 0];
}
$dMax = 1; foreach ($days as $d) $dMax = max($dMax, $d['c']);
?>
<!doctype html><html lang="th"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard · SHOPHUB</title>
<link rel="stylesheet" href="assets/css/style.css"></head><body><div class="container pd-wrap">

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
  <h2>Dashboard สถิติ</h2>
  <div><a href="admin.php">&larr; จัดการสินค้า</a></div>
</div>

<div class="pd-stats" style="margin-top:18px">
  <div class="stat"><strong><?= (int)$sum['clicks_total'] ?></strong><span>คลิกออกทั้งหมด</span></div>
  <div class="stat"><strong><?= (int)$sum['clicks_today'] ?></strong><span>คลิกวันนี้ (ตั้งแต่ตี 4)</span></div>
  <div class="stat"><strong><?= (int)$sum['products'] ?></strong><span>สินค้า</span></div>
  <div class="stat"><strong><?= (int)$sum['categories'] ?></strong><span>หมวดหมู่</span></div>
</div>

<div class="pd-grid">
  <section class="panel">
    <h2 class="panel-h">สินค้าฮิต 5 อันดับ</h2>
    <?php if ($top): ?>
      <div class="ranklist">
        <?php foreach ($top as $i => $r): ?>
        <a class="rankrow" href="product.php?id=<?= (int)$r['id'] ?>">
          <span class="rankno">#<?= $i+1 ?></span>
          <span class="rankname"><?= esc($r['name']) ?> <em><?= esc($r['platform']) ?></em></span>
          <span class="rankbarwrap"><span class="rankbar" style="width:<?= max(6,round((int)$r['c']/$topMax*100)) ?>%"></span></span>
          <span class="rankc"><?= (int)$r['c'] ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?><p class="empty">ยังไม่มีข้อมูลคลิก</p><?php endif; ?>
  </section>

  <section class="panel">
    <h2 class="panel-h">ยอดคลิกต่อหมวด</h2>
    <?php if ($catRows): ?>
      <div class="ranklist">
        <?php foreach ($catRows as $r): ?>
        <div class="rankrow">
          <span class="rankname"><?= esc($r['name']) ?></span>
          <span class="rankbarwrap"><span class="rankbar" style="width:<?= max(6,round((int)$r['c']/$catMax*100)) ?>%"></span></span>
          <span class="rankc"><?= (int)$r['c'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?><p class="empty">ยังไม่มีหมวด</p><?php endif; ?>
  </section>
</div>

<section class="panel" style="margin-top:20px">
  <h2 class="panel-h">ยอดคลิกรวม 7 วันล่าสุด</h2>
  <div class="spark">
    <?php foreach ($days as $d): $pct = round($d['c']/$dMax*100); ?>
      <div class="spark-col">
        <div class="spark-bar-wrap"><div class="spark-bar" style="height:<?= max(4,$pct) ?>%"<?= $d['c']?' title="'.$d['c'].'"':'' ?>></div></div>
        <span class="spark-n"><?= $d['c'] ?></span>
        <span class="spark-d"><?= esc($d['label']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</section>

</div></body></html>
