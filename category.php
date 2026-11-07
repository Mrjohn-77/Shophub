<?php
require 'config/db.php';
require 'partials.php';

$lang = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'en';
if (!in_array($lang, ['en','th'], true)) $lang = 'en';
if (isset($_GET['lang'])) { setcookie('lang', $lang, time()+31536000, '/'); }
$T = [
  'en' => ['home'=>'Home','admin'=>'Admin','back'=>'All products','view'=>'View deal','clicks'=>'clicks','new'=>'NEW',
           'items'=>'items','none'=>'No products in this category yet.','prev'=>'Prev','next'=>'Next','sort_by'=>'Sort by',
           'sort_popular'=>'Most popular','sort_new'=>'Newest','sort_cheap'=>'Price: low to high','sort_exp'=>'Price: high to low'],
  'th' => ['home'=>'หน้าแรก','admin'=>'Admin','back'=>'สินค้าทั้งหมด','view'=>'ดูสินค้า','clicks'=>'คลิก','new'=>'ใหม่',
           'items'=>'ชิ้น','none'=>'ยังไม่มีสินค้าในหมวดนี้','prev'=>'ก่อนหน้า','next'=>'ถัดไป','sort_by'=>'เรียงตาม',
           'sort_popular'=>'ฮิตสุด','sort_new'=>'ใหม่ล่าสุด','sort_cheap'=>'ราคาถูกไปแพง','sort_exp'=>'ราคาแพงไปถูก'],
];
$t = $T[$lang];
$other = $lang === 'en' ? 'th' : 'en';

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Invalid category'); }

// ชื่อหมวด (prepared statement)
$stmt = mysqli_prepare($conn, 'SELECT name FROM categories WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $catName);
$found = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
if (!$found) { http_response_code(404); exit('Category not found'); }

$sortMap = ['popular'=>'clicks DESC, p.name', 'new'=>'p.created_at DESC', 'price_asc'=>'p.price ASC', 'price_desc'=>'p.price DESC'];
$sort = (string)($_GET['sort'] ?? 'popular');
if (!isset($sortMap[$sort])) $sort = 'popular';
$order = $sortMap[$sort];
$page = max(1, (int)($_GET['page'] ?? 1));
$cutoff = day_cutoff_sql();

// นับทั้งหมดในหมวด
$cstmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS n FROM products WHERE category_id = ?');
mysqli_stmt_bind_param($cstmt, 'i', $id);
mysqli_stmt_execute($cstmt);
$total = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($cstmt))['n'] ?? 0);
mysqli_stmt_close($cstmt);

$perPage = 12;
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) $page = $pages;
$offset = ($page - 1) * $perPage;

$sql = "SELECT p.id, p.name, p.price, p.image_url, p.created_at, pl.name AS platform,
               COUNT(k.id) AS clicks,
               SUM(CASE WHEN k.clicked_at >= ($cutoff) THEN 1 ELSE 0 END) AS day_clicks
        FROM products p
        JOIN platforms pl ON p.platform_id = pl.id
        LEFT JOIN clicks k ON k.product_id = p.id
        WHERE p.category_id = ?
        GROUP BY p.id, p.name, p.price, p.image_url, p.created_at, pl.name
        ORDER BY $order
        LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'iii', $id, $perPage, $offset);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$items = []; while ($r = mysqli_fetch_assoc($res)) $items[] = $r;
mysqli_stmt_close($stmt);

function clink($overrides = []){ return 'category.php?' . http_build_query(array_merge($_GET, $overrides)); }
function sort_opt($val, $label, $cur){
  return '<option value="'.$val.'"'.($cur===$val?' selected':'').'>'.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</option>';
}
?>
<!doctype html><html lang="<?= $lang ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($catName) ?> — SHOPHUB</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<?= flame_defs() ?>

<div class="topbar">
  <a class="pill" href="<?= h(clink(['lang'=>$other])) ?>"><?= strtoupper($other) ?></a>
  <a class="pill" href="index.php"><?= h($t['home']) ?></a>
</div>

<header class="hero-banner">
  <div class="hero-inner">
    <div class="logo" style="font-size:3rem"><?= h($catName) ?></div>
    <p class="tag"><?= (int)$total ?> <?= h($t['items']) ?></p>
  </div>
</header>

<div class="container pd-wrap">
  <div class="cat-toolbar">
    <a class="back-link" href="index.php?lang=<?= h($lang) ?>">&larr; <?= h($t['back']) ?></a>
    <form method="get" action="category.php" class="sort-form">
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <input type="hidden" name="lang" value="<?= h($lang) ?>">
      <label><?= h($t['sort_by']) ?></label>
      <select class="fb-sel" name="sort" onchange="this.form.submit()">
        <?= sort_opt('popular',$t['sort_popular'],$sort) ?>
        <?= sort_opt('new',$t['sort_new'],$sort) ?>
        <?= sort_opt('price_asc',$t['sort_cheap'],$sort) ?>
        <?= sort_opt('price_desc',$t['sort_exp'],$sort) ?>
      </select>
    </form>
  </div>

  <?php if ($items): ?>
    <div class="grid"><?php foreach ($items as $p) card($p, $t); ?></div>
    <?php if ($pages > 1): ?>
    <nav class="pager">
      <?php if ($page > 1): ?><a href="<?= h(clink(['page'=>$page-1])) ?>">&lsaquo; <?= h($t['prev']) ?></a><?php endif; ?>
      <span class="pager-info"><?= $page ?> / <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= h(clink(['page'=>$page+1])) ?>"><?= h($t['next']) ?> &rsaquo;</a><?php endif; ?>
    </nav>
    <?php endif; ?>
  <?php else: ?>
    <p class="empty"><?= h($t['none']) ?></p>
  <?php endif; ?>
</div>
</body></html>
