<?php
require 'config/db.php';
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// --- language (EN default, TH optional) ---
$lang = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'en';
if (!in_array($lang, ['en','th'], true)) $lang = 'en';
if (isset($_GET['lang'])) { setcookie('lang', $lang, time()+31536000, '/'); }
$T = [
  'en' => ['tagline'=>'Handpicked deals, sorted by category — one tap to the store','popular'=>'Popular','items'=>'items','view'=>'View deal','clicks'=>'clicks','admin'=>'Admin'],
  'th' => ['tagline'=>'รวมของเด็ดที่คัดมาแล้ว แยกเป็นหมวดให้เลือกง่าย กดเดียวถึงร้าน','popular'=>'ยอดนิยม','items'=>'ชิ้น','view'=>'ดูสินค้า','clicks'=>'คลิก','admin'=>'Admin'],
];
$t = $T[$lang];
$other = $lang === 'en' ? 'th' : 'en';

// จุดตัดรอบวันคือ 04:00 น. (ก่อนตี 4 ยังนับเป็นของเมื่อวาน) คำนวณด้วยเวลาฝั่ง DB ทั้งหมด
$cutoff = "CASE WHEN HOUR(NOW()) >= 4 THEN CONCAT(CURDATE(),' 04:00:00')
                ELSE CONCAT(CURDATE() - INTERVAL 1 DAY, ' 04:00:00') END";
$sql = "SELECT p.id, p.name, p.price, p.image_url, c.name AS category, pl.name AS platform,
               COUNT(k.id) AS clicks,
               SUM(CASE WHEN k.clicked_at >= ($cutoff) THEN 1 ELSE 0 END) AS day_clicks
        FROM products p
        JOIN categories c  ON p.category_id = c.id
        JOIN platforms pl  ON p.platform_id = pl.id
        LEFT JOIN clicks k ON k.product_id = p.id
        GROUP BY p.id, p.name, p.price, p.image_url, c.name, pl.name
        ORDER BY c.name, clicks DESC, p.name";
$result = mysqli_query($conn, $sql);
$grouped = []; $all = [];
while ($row = mysqli_fetch_assoc($result)) { $grouped[$row['category']][] = $row; $all[] = $row; }
usort($all, fn($a,$b)=> (int)$b['clicks'] <=> (int)$a['clicks']);
$popular = array_slice($all, 0, 10);

function flame($cls='flame'){ return '<svg class="'.$cls.'" viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 1.5c.3 3.2-2.8 4.6-3.6 7.3-.4-1-.3-2.4-1.3-3.1-.2 1.9-2.6 3.5-2.6 6.8a6 6 0 0 0 12 0c0-3.4-2.7-5.2-3.8-8.2-.6 1.7-1.9 1.9-1.9.2 0-1.3.6-2 1.2-3z" fill="url(#fg)"/></svg>'; }

function card($p, $t){
  $hot = (int)($p['day_clicks'] ?? 0) >= 50;  // HOT = ยอดคลิกรายวัน (ตั้งแต่ตี 4) >= 50
  echo '<article class="card">';
  echo '<div class="thumb">';
  echo $p['image_url'] ? '<img src="'.h($p['image_url']).'" alt="'.h($p['name']).'">' : '<span class="noimg">No image</span>';
  echo '<span class="chip">'.h($p['platform']).'</span>';
  if ($hot) echo '<span class="hot"><span class="embers"><i></i><i></i><i></i></span>'.flame('flame-sm').'HOT</span>';
  echo '</div><div class="body"><h3>'.h($p['name']).'</h3>';
  echo '<div class="row"><span class="price">฿'.number_format((float)$p['price'],2).'</span><span class="clicks">'.(int)$p['clicks'].' '.h($t['clicks']).'</span></div>';
  echo '<a class="btn" href="go.php?id='.(int)$p['id'].'" target="_blank" rel="noopener">'.h($t['view']).' →</a>';
  echo '</div></article>';
}
?>
<!doctype html><html lang="<?= $lang ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SHOPHUB</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<svg width="0" height="0" style="position:absolute"><defs>
  <linearGradient id="fg" x1="0" y1="1" x2="0" y2="0">
    <stop offset="0" stop-color="#ff3d2e"/><stop offset=".5" stop-color="#ff8a1e"/><stop offset="1" stop-color="#ffd93b"/>
  </linearGradient>
</defs></svg>

<div class="topbar">
  <a class="pill" href="?lang=<?= $other ?>"><?= strtoupper($other) ?></a>
  <a class="pill" href="login.php"><?= h($t['admin']) ?></a>
</div>

<header class="hero-banner">
  <div class="hero-inner">
    <div class="logo">SHOP<span>HUB</span></div>
    <p class="tag"><?= h($t['tagline']) ?></p>
  </div>
</header>

<div class="container">
  <?php if ($popular): ?>
  <section class="shelf">
    <div class="shelf-head"><h2><?= flame('flame-lg') ?><?= h($t['popular']) ?></h2></div>
    <div class="row-wrap">
      <button class="nav prev" type="button" aria-label="prev">‹</button>
      <div class="row-scroll"><?php foreach ($popular as $p) card($p,$t); ?></div>
      <button class="nav next" type="button" aria-label="next">›</button>
    </div>
  </section>
  <?php endif; ?>

  <?php foreach ($grouped as $category => $items): ?>
  <section class="shelf">
    <div class="shelf-head"><h2><span class="dot"></span><?= h($category) ?></h2><span class="count"><?= count($items) ?> <?= h($t['items']) ?></span></div>
    <div class="row-wrap">
      <button class="nav prev" type="button" aria-label="prev">‹</button>
      <div class="row-scroll"><?php foreach ($items as $p) card($p,$t); ?></div>
      <button class="nav next" type="button" aria-label="next">›</button>
    </div>
  </section>
  <?php endforeach; ?>
</div>

<script>
document.querySelectorAll('.row-wrap').forEach(w => {
  const row = w.querySelector('.row-scroll');
  const amount = () => Math.max(row.clientWidth * 0.8, 240);
  w.querySelector('.prev').addEventListener('click', () => row.scrollBy({left: -amount(), behavior:'smooth'}));
  w.querySelector('.next').addEventListener('click', () => row.scrollBy({left:  amount(), behavior:'smooth'}));
});
</script>
</body></html>
