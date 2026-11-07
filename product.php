<?php
require 'config/db.php';
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// --- language (EN default, TH optional) ---
$lang = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'en';
if (!in_array($lang, ['en','th'], true)) $lang = 'en';
if (isset($_GET['lang'])) { setcookie('lang', $lang, time()+31536000, '/'); }
$T = [
  'en' => ['back'=>'Back','store'=>'Go to store','clicks_total'=>'total clicks out',
           'today'=>'today','rank'=>'Rank','in'=>'in','why'=>'Why we picked it',
           'trend'=>'Click-outs, last 7 days','related'=>'More in','admin'=>'Admin','home'=>'Home'],
  'th' => ['back'=>'ย้อนกลับ','store'=>'ไปที่ร้านค้า','clicks_total'=>'คนกดออกไปซื้อแล้ว',
           'today'=>'วันนี้','rank'=>'อันดับ','in'=>'ใน','why'=>'ทำไมเราเลือกตัวนี้',
           'trend'=>'ยอดกดออก 7 วันล่าสุด','related'=>'ดูเพิ่มในหมวด','admin'=>'Admin','home'=>'หน้าแรก'],
];
$t = $T[$lang];
$other = $lang === 'en' ? 'th' : 'en';

// --- validate id ---
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Invalid product'); }

// จุดตัดรอบวันคือ 04:00 น. (เหมือนหน้าแรก) คำนวณด้วยเวลาฝั่ง DB ทั้งหมด
$cutoff = "CASE WHEN HOUR(NOW()) >= 4 THEN CONCAT(CURDATE(),' 04:00:00')
                ELSE CONCAT(CURDATE() - INTERVAL 1 DAY, ' 04:00:00') END";

// --- main product + aggregates (prepared statement) ---
$sql = "SELECT p.id, p.name, p.price, p.image_url, p.affiliate_url, p.blurb, p.category_id,
               c.name AS category, pl.name AS platform,
               COUNT(k.id) AS clicks,
               SUM(CASE WHEN k.clicked_at >= ($cutoff) THEN 1 ELSE 0 END) AS day_clicks
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN platforms pl ON p.platform_id = pl.id
        LEFT JOIN clicks k ON k.product_id = p.id
        WHERE p.id = ?
        GROUP BY p.id, p.name, p.price, p.image_url, p.affiliate_url, p.blurb, p.category_id, c.name, pl.name";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$p) { http_response_code(404); exit('Product not found'); }

$hot = (int)($p['day_clicks'] ?? 0) >= 10;

// --- rank within category (จัดอันดับยอดคลิกสะสมในหมวดเดียวกัน) ---
$rankSql = "SELECT p.id, COUNT(k.id) AS c
            FROM products p LEFT JOIN clicks k ON k.product_id = p.id
            WHERE p.category_id = ?
            GROUP BY p.id
            ORDER BY c DESC, p.name";
$stmt = mysqli_prepare($conn, $rankSql);
mysqli_stmt_bind_param($stmt, 'i', $p['category_id']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$rank = 0; $catTotal = 0; $pos = 0;
while ($r = mysqli_fetch_assoc($res)) { $catTotal++; if ((int)$r['id'] === (int)$p['id']) $pos = $catTotal; }
mysqli_stmt_close($stmt);
$rank = $pos;

// --- click-outs per day, last 7 days (GROUP BY DATE) ---
$trendSql = "SELECT DATE(clicked_at) AS d, COUNT(*) AS c
             FROM clicks
             WHERE product_id = ? AND clicked_at >= CURDATE() - INTERVAL 6 DAY
             GROUP BY DATE(clicked_at)";
$stmt = mysqli_prepare($conn, $trendSql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$byDate = [];
while ($r = mysqli_fetch_assoc($res)) { $byDate[$r['d']] = (int)$r['c']; }
mysqli_stmt_close($stmt);
// สร้างแกน 7 วันเต็ม เติม 0 วันที่ไม่มีคลิก
$days = [];
for ($i = 6; $i >= 0; $i--) {
  $d = date('Y-m-d', strtotime("-$i day"));
  $days[] = ['label' => date('D', strtotime($d)), 'date' => (int)date('j', strtotime($d)), 'c' => $byDate[$d] ?? 0];
}
$maxc = max(1, max(array_column($days, 'c')));

// --- related products: หมวดเดียวกัน ตัวอื่น เรียงยอดคลิกมากไปน้อย ---
$relSql = "SELECT p.id, p.name, p.price, p.image_url, pl.name AS platform, COUNT(k.id) AS clicks,
                  SUM(CASE WHEN k.clicked_at >= ($cutoff) THEN 1 ELSE 0 END) AS day_clicks
           FROM products p
           JOIN platforms pl ON p.platform_id = pl.id
           LEFT JOIN clicks k ON k.product_id = p.id
           WHERE p.category_id = ? AND p.id <> ?
           GROUP BY p.id, p.name, p.price, p.image_url, pl.name
           ORDER BY clicks DESC, p.name
           LIMIT 12";
$stmt = mysqli_prepare($conn, $relSql);
mysqli_stmt_bind_param($stmt, 'ii', $p['category_id'], $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$related = [];
while ($r = mysqli_fetch_assoc($res)) { $related[] = $r; }
mysqli_stmt_close($stmt);

function flame($cls='flame'){ return '<svg class="'.$cls.'" viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 1.5c.3 3.2-2.8 4.6-3.6 7.3-.4-1-.3-2.4-1.3-3.1-.2 1.9-2.6 3.5-2.6 6.8a6 6 0 0 0 12 0c0-3.4-2.7-5.2-3.8-8.2-.6 1.7-1.9 1.9-1.9.2 0-1.3.6-2 1.2-3z" fill="url(#fg)"/></svg>'; }
function relcard($p, $t){
  $hot = (int)($p['day_clicks'] ?? 0) >= 10;
  echo '<article class="card">';
  echo '<div class="thumb">';
  echo $p['image_url'] ? '<img src="'.h($p['image_url']).'" alt="'.h($p['name']).'">' : '<span class="noimg">No image</span>';
  echo '<span class="chip">'.h($p['platform']).'</span>';
  if ($hot) echo '<span class="hot"><span class="embers"><i></i><i></i><i></i></span>'.flame('flame-sm').'HOT</span>';
  echo '</div><div class="body"><h3>'.h($p['name']).'</h3>';
  echo '<div class="row"><span class="price">฿'.number_format((float)$p['price'],2).'</span><span class="clicks">'.(int)$p['clicks'].'</span></div>';
  echo '<a class="btn" href="product.php?id='.(int)$p['id'].'">'.h($t['store']).' →</a>';
  echo '</div></article>';
}
?>
<!doctype html><html lang="<?= $lang ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($p['name']) ?> — SHOPHUB</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<svg width="0" height="0" style="position:absolute"><defs>
  <linearGradient id="fg" x1="0" y1="1" x2="0" y2="0">
    <stop offset="0" stop-color="#ff3d2e"/><stop offset=".5" stop-color="#ff8a1e"/><stop offset="1" stop-color="#ffd93b"/>
  </linearGradient>
</defs></svg>

<div class="topbar">
  <a class="pill" href="?id=<?= (int)$id ?>&lang=<?= $other ?>"><?= strtoupper($other) ?></a>
  <a class="pill" href="index.php"><?= h($t['home']) ?></a>
</div>

<div class="container pd-wrap">
  <a class="back-link" href="index.php">&larr; <?= h($t['back']) ?></a>

  <section class="pd">
    <div class="pd-media">
      <?= $p['image_url'] ? '<img src="'.h($p['image_url']).'" alt="'.h($p['name']).'">' : '<span class="noimg">No image</span>' ?>
      <?php if ($hot): ?><span class="hot pd-hot"><span class="embers"><i></i><i></i><i></i></span><?= flame('flame-sm') ?>HOT</span><?php endif; ?>
    </div>

    <div class="pd-info">
      <span class="chip chip-static"><?= h($p['platform']) ?></span>
      <h1 class="pd-title"><?= h($p['name']) ?></h1>
      <div class="pd-price">฿<?= number_format((float)$p['price'],2) ?></div>

      <div class="pd-stats">
        <div class="stat">
          <strong><?= (int)$p['clicks'] ?></strong>
          <span><?= h($t['clicks_total']) ?></span>
        </div>
        <div class="stat">
          <strong><?= (int)$p['day_clicks'] ?></strong>
          <span><?= h($t['today']) ?></span>
        </div>
        <?php if ($rank): ?>
        <div class="stat">
          <strong>#<?= (int)$rank ?></strong>
          <span><?= h($t['rank']) ?> <?= h($t['in']) ?> <?= h($p['category']) ?></span>
        </div>
        <?php endif; ?>
      </div>

      <a class="btn btn-store" href="go.php?id=<?= (int)$id ?>" target="_blank" rel="noopener"><?= h($t['store']) ?> &rarr;</a>
    </div>
  </section>

  <div class="pd-grid">
    <?php if (trim((string)$p['blurb']) !== ''): ?>
    <section class="panel">
      <h2 class="panel-h"><?= flame('flame-sm') ?><?= h($t['why']) ?></h2>
      <p class="blurb"><?= nl2br(h($p['blurb'])) ?></p>
    </section>
    <?php endif; ?>

    <section class="panel">
      <h2 class="panel-h"><?= h($t['trend']) ?></h2>
      <div class="spark">
        <?php foreach ($days as $d): $pct = round($d['c'] / $maxc * 100); ?>
          <div class="spark-col">
            <div class="spark-bar-wrap"><div class="spark-bar" style="height:<?= max(4,$pct) ?>%"<?= $d['c']?' title="'.$d['c'].'"':'' ?>></div></div>
            <span class="spark-n"><?= $d['c'] ?></span>
            <span class="spark-d"><?= h($d['label']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>

  <?php if ($related): ?>
  <section class="shelf">
    <div class="shelf-head"><h2><span class="dot"></span><?= h($t['related']) ?> <?= h($p['category']) ?></h2></div>
    <div class="row-wrap">
      <button class="nav prev" type="button" aria-label="prev">&lsaquo;</button>
      <div class="row-scroll"><?php foreach ($related as $r) relcard($r,$t); ?></div>
      <button class="nav next" type="button" aria-label="next">&rsaquo;</button>
    </div>
  </section>
  <?php endif; ?>
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
