<?php
require 'config/db.php';
require 'partials.php';

// --- language ---
$lang = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'en';
if (!in_array($lang, ['en','th'], true)) $lang = 'en';
if (isset($_GET['lang'])) { setcookie('lang', $lang, time()+31536000, '/'); }
$T = [
  'en' => ['tagline'=>'Scroll through room styles — tap an item to shop that category','enter'=>'Browse all products','admin'=>'Admin','home'=>'List view','go'=>'View category','scroll'=>'Scroll down for more styles'],
  'th' => ['tagline'=>'เลื่อนดูห้องแต่ละสไตล์ กดสินค้าในรูปเพื่อดูหมวดนั้น','enter'=>'ดูสินค้าทั้งหมด','admin'=>'Admin','home'=>'ดูแบบรายการ','go'=>'ดูหมวดนี้','scroll'=>'เลื่อนลงดูสไตล์อื่น'],
];
$t = $T[$lang];
$other = $lang === 'en' ? 'th' : 'en';

/* ==========================================================================
   LOOKBOOK: ห้องหลายสไตล์ เลื่อนลงไปเรื่อยๆ
   - แต่ละห้อง: title/sub = ชื่อสไตล์, img = รูป (เปลี่ยนเป็นรูปจริงได้),
     spots = จุดคลิก (x,y เป็น % ของรูป ; cat = id หมวด, 0 = map อัตโนมัติ)
   - เพิ่มห้องใหม่ = เพิ่ม 1 ก้อนใน $SCENES
   ========================================================================== */
// ชุดจุดคลิกของ "ฉากห้อง" (โครงเดียวกัน 3 สไตล์)
$ROOM_SPOTS = [
  ['x'=>63.5,'y'=>49],  // จอคอม
  ['x'=>30,  'y'=>52],  // เก้าอี้
  ['x'=>86,  'y'=>44],  // โคมไฟ
  ['x'=>75,  'y'=>60],  // โต๊ะ
  ['x'=>12,  'y'=>74],  // ต้นไม้/ของแต่งห้อง
];
// ชุดจุดคลิกของ "ฉาก flat-lay" (มองจากด้านบน คนละ layout กับห้อง)
$FLAT_SPOTS = [
  ['x'=>49.5,'y'=>53],  // แล็ปท็อป
  ['x'=>22.5,'y'=>43],  // หูฟัง
  ['x'=>82.5,'y'=>49],  // มือถือ
  ['x'=>80,  'y'=>82],  // กล้อง
  ['x'=>19.6,'y'=>80],  // สมุด
  ['x'=>81.7,'y'=>18.4],// กาแฟ
];
// เพิ่มสไตล์ใหม่ = เพิ่ม 1 ก้อนที่นี่ (img = รูปอะไรก็ได้ ไม่จำเป็นต้องเป็นห้อง)
$SCENES = [
  ['th'=>'โทนอบอุ่น','en'=>'Warm Minimal', 'img'=>'assets/img/room-warm.svg',  'spots'=>$ROOM_SPOTS,'offset'=>0],
  ['th'=>'โทนเข้ม',  'en'=>'Industrial',   'img'=>'assets/img/room-dark.svg',  'spots'=>$ROOM_SPOTS,'offset'=>1],
  ['th'=>'โทนสว่าง', 'en'=>'Modern Bright', 'img'=>'assets/img/room-bright.svg','spots'=>$ROOM_SPOTS,'offset'=>2],
  ['th'=>'จัดโต๊ะมุมบน','en'=>'Desk Flat-lay','img'=>'assets/img/flatlay.svg',  'spots'=>$FLAT_SPOTS,'offset'=>0],
];

// โหลดหมวดจริงมา map จุดคลิก
$catList = [];
$cr = mysqli_query($conn, 'SELECT id, name FROM categories ORDER BY id');
while ($c = mysqli_fetch_assoc($cr)) $catList[] = $c;
$nCat = count($catList);

function spot_cat($i, $offset, $catList, $nCat){
  if (!$nCat) return [0, ''];
  $c = $catList[($i + $offset) % $nCat];
  return [(int)$c['id'], $c['name']];
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
    <div class="scroll-hint"><?= h($t['scroll']) ?> &darr;</div>
  </div>

  <?php foreach ($SCENES as $si => $sc): ?>
  <div class="look">
    <div class="look-head">
      <span class="look-no"><?= $si+1 ?></span>
      <div>
        <div class="look-title"><?= h($lang==='th' ? $sc['th'] : $sc['en']) ?></div>
        <div class="look-sub"><?= h($lang==='th' ? $sc['en'] : $sc['th']) ?></div>
      </div>
    </div>
    <div class="stage">
      <img class="stage-img" src="<?= h($sc['img']) ?>" alt="<?= h($sc['en']) ?>" loading="lazy">
      <?php foreach ($sc['spots'] as $i => $hs):
        [$cid, $cn] = spot_cat($i, $sc['offset'], $catList, $nCat);
        if (!$cid) continue; ?>
        <a class="spot" style="left:<?= (float)$hs['x'] ?>%;top:<?= (float)$hs['y'] ?>%"
           href="category.php?id=<?= $cid ?>&lang=<?= h($lang) ?>">
          <span class="spot-dot"></span>
          <span class="spot-tip"><?= h($cn) ?> <small><?= h($t['go']) ?> &rarr;</small></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</section>

</body></html>
