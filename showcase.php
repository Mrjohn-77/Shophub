<?php
require 'config/db.php';
require 'partials.php';

// --- language ---
$lang = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'en';
if (!in_array($lang, ['en','th'], true)) $lang = 'en';
if (isset($_GET['lang'])) { setcookie('lang', $lang, time()+31536000, '/'); }
$T = [
  'en' => ['tagline'=>'Scroll through styles — tap an item to shop that category','enter'=>'Browse all products','admin'=>'Admin','home'=>'List view','go'=>'View category','scroll'=>'Scroll down for more','empty'=>'No scenes yet.'],
  'th' => ['tagline'=>'เลื่อนดูแต่ละสไตล์ กดสินค้าในรูปเพื่อดูหมวดนั้น','enter'=>'ดูสินค้าทั้งหมด','admin'=>'Admin','home'=>'ดูแบบรายการ','go'=>'ดูหมวดนี้','scroll'=>'เลื่อนลงดูสไตล์อื่น','empty'=>'ยังไม่มีฉาก'],
];
$t = $T[$lang];
$other = $lang === 'en' ? 'th' : 'en';

// ---- โหลดฉากจากฐานข้อมูล (ถ้ายังไม่ migrate จะ fallback เป็นชุดเริ่มต้น) ----
$scenes = [];
$sr = @mysqli_query($conn, "SELECT id, title, subtitle, image_url FROM scenes ORDER BY sort_order, id");
if ($sr) {
  while ($s = mysqli_fetch_assoc($sr)) { $s['spots'] = []; $scenes[$s['id']] = $s; }
  if ($scenes) {
    $hr = mysqli_query($conn, "SELECT h.scene_id, h.x, h.y, h.category_id, c.name AS cat_name
                               FROM scene_hotspots h JOIN categories c ON c.id = h.category_id
                               ORDER BY h.sort_order, h.id");
    while ($h = mysqli_fetch_assoc($hr)) {
      if (isset($scenes[$h['scene_id']])) $scenes[$h['scene_id']]['spots'][] = $h;
    }
  }
  $scenes = array_values($scenes);
}

// ---- fallback: ยังไม่มีตาราง/ยังไม่มีฉาก → ใช้ชุดเริ่มต้นเดิม ----
if (!$scenes) {
  $catList = [];
  $cr = mysqli_query($conn, 'SELECT id, name FROM categories ORDER BY id');
  while ($c = mysqli_fetch_assoc($cr)) $catList[] = $c;
  $n = count($catList);
  $mk = function($coords, $off) use ($catList, $n) {
    $out = [];
    foreach ($coords as $i => $xy) {
      if (!$n) break;
      $c = $catList[($i + $off) % $n];
      $out[] = ['x'=>$xy[0],'y'=>$xy[1],'category_id'=>$c['id'],'cat_name'=>$c['name']];
    }
    return $out;
  };
  $room = [[63.5,49],[30,52],[86,44],[75,60],[12,74]];
  $flat = [[49.5,53],[22.5,43],[82.5,49],[80,82],[19.6,80],[81.7,18.4]];
  $scenes = [
    ['title'=>'โทนอบอุ่น','subtitle'=>'Warm Minimal','image_url'=>'assets/img/room-warm.svg','spots'=>$mk($room,0)],
    ['title'=>'โทนเข้ม','subtitle'=>'Industrial','image_url'=>'assets/img/room-dark.svg','spots'=>$mk($room,1)],
    ['title'=>'โทนสว่าง','subtitle'=>'Modern Bright','image_url'=>'assets/img/room-bright.svg','spots'=>$mk($room,2)],
    ['title'=>'จัดโต๊ะมุมบน','subtitle'=>'Desk Flat-lay','image_url'=>'assets/img/flatlay.svg','spots'=>$mk($flat,0)],
  ];
}
?>
<!doctype html><html lang="<?= $lang ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SHOPHUB — Lookbook</title><link rel="stylesheet" href="assets/css/style.css"></head><body class="sc-body">

<div class="topbar">
  <a class="pill" href="?lang=<?= $other ?>"><?= strtoupper($other) ?></a>
  <a class="pill" href="index.php?lang=<?= h($lang) ?>"><?= h($t['home']) ?></a>
  <a class="pill" href="login.php"><?= h($t['admin']) ?></a>
</div>

<section class="scene">
  <div class="scene-head">
    <div class="logo">SHOP<span>HUB</span></div>
    <p class="tag"><?= h($t['tagline']) ?></p>
    <a class="scene-cta" href="index.php?lang=<?= h($lang) ?>"><?= h($t['enter']) ?> &rarr;</a>
    <?php if (count($scenes) > 1): ?><div class="scroll-hint"><?= h($t['scroll']) ?> &darr;</div><?php endif; ?>
  </div>

  <?php if (!$scenes): ?>
    <p class="empty"><?= h($t['empty']) ?></p>
  <?php else: foreach ($scenes as $si => $sc): ?>
  <div class="look">
    <div class="look-head">
      <span class="look-no"><?= $si+1 ?></span>
      <div>
        <div class="look-title"><?= h($sc['title']) ?></div>
        <?php if (!empty($sc['subtitle'])): ?><div class="look-sub"><?= h($sc['subtitle']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="stage">
      <img class="stage-img" src="<?= h($sc['image_url']) ?>" alt="<?= h($sc['title']) ?>" loading="lazy">
      <?php foreach ($sc['spots'] as $hs): ?>
        <a class="spot" style="left:<?= (float)$hs['x'] ?>%;top:<?= (float)$hs['y'] ?>%"
           href="index.php?focus=<?= (int)$hs['category_id'] ?>&lang=<?= h($lang) ?>">
          <span class="spot-dot"></span>
          <span class="spot-tip"><?= h($hs['cat_name']) ?> <small><?= h($t['go']) ?> &rarr;</small></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; endif; ?>
</section>

</body></html>
