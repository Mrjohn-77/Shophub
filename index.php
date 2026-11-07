<?php
require 'config/db.php';
require 'partials.php';

// --- language (EN default, TH optional) ---
$lang = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'en';
if (!in_array($lang, ['en','th'], true)) $lang = 'en';
if (isset($_GET['lang'])) { setcookie('lang', $lang, time()+31536000, '/'); }
$T = [
  'en' => ['tagline'=>'Handpicked deals, sorted by category — one tap to the store','popular'=>'Popular',
           'items'=>'items','view'=>'View deal','clicks'=>'clicks','admin'=>'Admin','new'=>'NEW','home'=>'Home',
           'search_ph'=>'Search products...','all_cats'=>'All categories','min'=>'Min ฿','max'=>'Max ฿',
           'apply'=>'Search','clear'=>'Clear','results'=>'Results','found'=>'found','none'=>'No products match your search.',
           'prev'=>'Prev','next'=>'Next',
           'sort_popular'=>'Most popular','sort_new'=>'Newest','sort_cheap'=>'Price: low to high','sort_exp'=>'Price: high to low'],
  'th' => ['tagline'=>'รวมของเด็ดที่คัดมาแล้ว แยกเป็นหมวดให้เลือกง่าย กดเดียวถึงร้าน','popular'=>'ยอดนิยม',
           'items'=>'ชิ้น','view'=>'ดูสินค้า','clicks'=>'คลิก','admin'=>'Admin','new'=>'ใหม่','home'=>'หน้าแรก',
           'search_ph'=>'ค้นหาสินค้า...','all_cats'=>'ทุกหมวด','min'=>'ราคาต่ำสุด','max'=>'ราคาสูงสุด',
           'apply'=>'ค้นหา','clear'=>'ล้าง','results'=>'ผลการค้นหา','found'=>'รายการ','none'=>'ไม่พบสินค้าที่ตรงกับเงื่อนไข',
           'prev'=>'ก่อนหน้า','next'=>'ถัดไป',
           'sort_popular'=>'ฮิตสุด','sort_new'=>'ใหม่ล่าสุด','sort_cheap'=>'ราคาถูกไปแพง','sort_exp'=>'ราคาแพงไปถูก'],
];
$t = $T[$lang];
$other = $lang === 'en' ? 'th' : 'en';

// --- โหลดหมวดไว้ทำ dropdown กรอง ---
$cats = [];
$cr = mysqli_query($conn, 'SELECT id, name FROM categories ORDER BY name');
while ($c = mysqli_fetch_assoc($cr)) $cats[] = $c;

// --- รับค่าตัวกรอง/ค้นหา/เรียง ---
$q    = trim((string)($_GET['q'] ?? ''));
$cat  = filter_var($_GET['cat'] ?? '', FILTER_VALIDATE_INT) ?: 0;
$minp = ($_GET['minp'] ?? '') === '' ? null : filter_var($_GET['minp'], FILTER_VALIDATE_FLOAT);
$maxp = ($_GET['maxp'] ?? '') === '' ? null : filter_var($_GET['maxp'], FILTER_VALIDATE_FLOAT);
$sort = (string)($_GET['sort'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
if ($minp === false) $minp = null;
if ($maxp === false) $maxp = null;

// whitelist ORDER BY (ห้ามเอา string จากผู้ใช้ไปต่อ SQL ตรงๆ)
$sortMap = ['popular'=>'clicks DESC, p.name', 'new'=>'p.created_at DESC', 'price_asc'=>'p.price ASC', 'price_desc'=>'p.price DESC'];

// results mode = มีการค้นหา/กรอง/เรียงอย่างใดอย่างหนึ่ง
$resultsMode = ($q !== '' || $cat > 0 || $minp !== null || $maxp !== null || ($sort !== '' && isset($sortMap[$sort])));
$cutoff = day_cutoff_sql();

// เก็บ query string ปัจจุบันไว้ทำลิงก์เปลี่ยนหน้า (ยกเว้น page)
function qlink($overrides = []){
  $p = array_merge($_GET, $overrides);
  return 'index.php?' . http_build_query($p);
}

if ($resultsMode) {
  if (!isset($sortMap[$sort])) $sort = 'popular';
  // สร้างเงื่อนไขแบบ dynamic ด้วย prepared statement
  $cond = []; $bind = []; $types = '';
  if ($q !== '')     { $cond[] = 'p.name LIKE ?';       $bind[] = '%'.$q.'%'; $types .= 's'; }
  if ($cat > 0)      { $cond[] = 'p.category_id = ?';   $bind[] = $cat;       $types .= 'i'; }
  if ($minp !== null){ $cond[] = 'p.price >= ?';        $bind[] = $minp;      $types .= 'd'; }
  if ($maxp !== null){ $cond[] = 'p.price <= ?';        $bind[] = $maxp;      $types .= 'd'; }
  $where = $cond ? ('WHERE '.implode(' AND ', $cond)) : '';

  // นับจำนวนทั้งหมดเพื่อทำ pagination
  $cstmt = mysqli_prepare($conn, "SELECT COUNT(*) AS n FROM products p $where");
  if ($types !== '') mysqli_stmt_bind_param($cstmt, $types, ...$bind);
  mysqli_stmt_execute($cstmt);
  $total = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($cstmt))['n'] ?? 0);
  mysqli_stmt_close($cstmt);

  $perPage = 12;
  $pages = max(1, (int)ceil($total / $perPage));
  if ($page > $pages) $page = $pages;
  $offset = ($page - 1) * $perPage;

  $order = $sortMap[$sort];
  $gsql = "SELECT p.id, p.name, p.price, p.image_url, p.created_at, c.name AS category, pl.name AS platform,
                  COUNT(k.id) AS clicks,
                  SUM(CASE WHEN k.clicked_at >= ($cutoff) THEN 1 ELSE 0 END) AS day_clicks
           FROM products p
           JOIN categories c ON p.category_id = c.id
           JOIN platforms pl ON p.platform_id = pl.id
           LEFT JOIN clicks k ON k.product_id = p.id
           $where
           GROUP BY p.id, p.name, p.price, p.image_url, p.created_at, c.name, pl.name
           ORDER BY $order
           LIMIT ? OFFSET ?";
  $gtypes = $types . 'ii';
  $gbind  = array_merge($bind, [$perPage, $offset]);
  $gstmt = mysqli_prepare($conn, $gsql);
  mysqli_stmt_bind_param($gstmt, $gtypes, ...$gbind);
  mysqli_stmt_execute($gstmt);
  $res = mysqli_stmt_get_result($gstmt);
  $items = []; while ($r = mysqli_fetch_assoc($res)) $items[] = $r;
  mysqli_stmt_close($gstmt);

} else {
  // showcase mode: popular + per-category shelves (เหมือนเดิม)
  $sql = "SELECT p.id, p.name, p.price, p.image_url, p.created_at, c.name AS category, pl.name AS platform,
                 COUNT(k.id) AS clicks,
                 SUM(CASE WHEN k.clicked_at >= ($cutoff) THEN 1 ELSE 0 END) AS day_clicks
          FROM products p
          JOIN categories c  ON p.category_id = c.id
          JOIN platforms pl  ON p.platform_id = pl.id
          LEFT JOIN clicks k ON k.product_id = p.id
          GROUP BY p.id, p.name, p.price, p.image_url, p.created_at, c.name, pl.name
          ORDER BY c.name, clicks DESC, p.name";
  $result = mysqli_query($conn, $sql);
  $grouped = []; $all = [];
  while ($row = mysqli_fetch_assoc($result)) { $grouped[$row['category']][] = $row; $all[] = $row; }
  usort($all, fn($a,$b)=> (int)$b['clicks'] <=> (int)$a['clicks']);
  $popular = array_slice($all, 0, 10);
}

// map ชื่อหมวด -> id สำหรับลิงก์ไป category.php
$catId = [];
foreach ($cats as $c) $catId[$c['name']] = $c['id'];

function sort_opt($val, $label, $cur){
  return '<option value="'.$val.'"'.($cur===$val?' selected':'').'>'.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</option>';
}
?>
<!doctype html><html lang="<?= $lang ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SHOPHUB</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<?= flame_defs() ?>

<div class="topbar">
  <a class="pill" href="<?= h(qlink(['lang'=>$other])) ?>"><?= strtoupper($other) ?></a>
  <a class="pill" href="login.php"><?= h($t['admin']) ?></a>
</div>

<header class="hero-banner">
  <div class="hero-inner">
    <div class="logo">SHOP<span>HUB</span></div>
    <p class="tag"><?= h($t['tagline']) ?></p>

    <form class="filterbar" method="get" action="index.php">
      <input type="hidden" name="lang" value="<?= h($lang) ?>">
      <input class="fb-search" type="search" name="q" value="<?= h($q) ?>" placeholder="<?= h($t['search_ph']) ?>">
      <select class="fb-sel" name="cat">
        <option value="0"><?= h($t['all_cats']) ?></option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>"<?= $cat==$c['id']?' selected':'' ?>><?= h($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input class="fb-num" type="number" name="minp" min="0" step="1" value="<?= $minp!==null?h($minp):'' ?>" placeholder="<?= h($t['min']) ?>">
      <input class="fb-num" type="number" name="maxp" min="0" step="1" value="<?= $maxp!==null?h($maxp):'' ?>" placeholder="<?= h($t['max']) ?>">
      <select class="fb-sel" name="sort">
        <?= sort_opt('popular',$t['sort_popular'],$sort?:'popular') ?>
        <?= sort_opt('new',$t['sort_new'],$sort) ?>
        <?= sort_opt('price_asc',$t['sort_cheap'],$sort) ?>
        <?= sort_opt('price_desc',$t['sort_exp'],$sort) ?>
      </select>
      <button class="fb-btn" type="submit"><?= h($t['apply']) ?></button>
      <?php if ($resultsMode): ?><a class="fb-clear" href="index.php?lang=<?= h($lang) ?>"><?= h($t['clear']) ?></a><?php endif; ?>
    </form>
  </div>
</header>

<div class="container">
<?php if ($resultsMode): ?>
  <section class="shelf">
    <div class="shelf-head"><h2><span class="dot"></span><?= h($t['results']) ?></h2>
      <span class="count"><?= (int)$total ?> <?= h($t['found']) ?></span></div>
    <?php if ($items): ?>
      <div class="grid"><?php foreach ($items as $p) card($p, $t); ?></div>
      <?php if ($pages > 1): ?>
      <nav class="pager">
        <?php if ($page > 1): ?><a href="<?= h(qlink(['page'=>$page-1])) ?>">&lsaquo; <?= h($t['prev']) ?></a><?php endif; ?>
        <span class="pager-info"><?= $page ?> / <?= $pages ?></span>
        <?php if ($page < $pages): ?><a href="<?= h(qlink(['page'=>$page+1])) ?>"><?= h($t['next']) ?> &rsaquo;</a><?php endif; ?>
      </nav>
      <?php endif; ?>
    <?php else: ?>
      <p class="empty"><?= h($t['none']) ?></p>
    <?php endif; ?>
  </section>

<?php else: ?>
  <?php if (!empty($popular)): ?>
  <section class="shelf">
    <div class="shelf-head"><h2><?= flame('flame-lg') ?><?= h($t['popular']) ?></h2></div>
    <div class="row-wrap">
      <button class="nav prev" type="button" aria-label="prev">&lsaquo;</button>
      <div class="row-scroll"><?php foreach ($popular as $p) card($p, $t); ?></div>
      <button class="nav next" type="button" aria-label="next">&rsaquo;</button>
    </div>
  </section>
  <?php endif; ?>

  <?php foreach ($grouped as $category => $list): ?>
  <section class="shelf">
    <div class="shelf-head">
      <h2><span class="dot"></span>
        <?php if (isset($catId[$category])): ?><a class="cat-link" href="category.php?id=<?= (int)$catId[$category] ?>&lang=<?= h($lang) ?>"><?= h($category) ?></a><?php else: ?><?= h($category) ?><?php endif; ?>
      </h2>
      <span class="count"><?= count($list) ?> <?= h($t['items']) ?></span>
    </div>
    <div class="row-wrap">
      <button class="nav prev" type="button" aria-label="prev">&lsaquo;</button>
      <div class="row-scroll"><?php foreach ($list as $p) card($p, $t); ?></div>
      <button class="nav next" type="button" aria-label="next">&rsaquo;</button>
    </div>
  </section>
  <?php endforeach; ?>
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
