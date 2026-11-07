<?php
require 'config/db.php';
require 'auth.php';
require_admin();

$err = '';

// ---------- POST actions (CSRF + prepared statements) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $action = $_POST['action'] ?? '';

  if ($action === 'scene_add') {
    $title = trim((string)($_POST['title'] ?? ''));
    $sub   = trim((string)($_POST['subtitle'] ?? ''));
    $img   = trim((string)($_POST['image_url'] ?? ''));
    if ($title === '' || $img === '') { $err = 'ใส่ชื่อฉากและลิงก์รูปให้ครบ'; }
    else {
      $next = (int)(mysqli_fetch_assoc(mysqli_query($conn,'SELECT COALESCE(MAX(sort_order),0)+1 AS n FROM scenes'))['n']);
      $stmt = mysqli_prepare($conn, 'INSERT INTO scenes (title, subtitle, image_url, sort_order) VALUES (?,?,?,?)');
      mysqli_stmt_bind_param($stmt, 'sssi', $title, $sub, $img, $next);
      mysqli_stmt_execute($stmt); $newId = mysqli_insert_id($conn); mysqli_stmt_close($stmt);
      header('Location: manage_scenes.php?edit='.$newId); exit;
    }
  }
  elseif ($action === 'scene_update') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    $sub   = trim((string)($_POST['subtitle'] ?? ''));
    $img   = trim((string)($_POST['image_url'] ?? ''));
    if ($id && $title !== '' && $img !== '') {
      $stmt = mysqli_prepare($conn, 'UPDATE scenes SET title=?, subtitle=?, image_url=? WHERE id=?');
      mysqli_stmt_bind_param($stmt, 'sssi', $title, $sub, $img, $id);
      mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
      header('Location: manage_scenes.php?edit='.$id.'&ok=1'); exit;
    }
    $err = 'แก้ไขไม่สำเร็จ';
  }
  elseif ($action === 'scene_delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) { $stmt = mysqli_prepare($conn, 'DELETE FROM scenes WHERE id=?');
      mysqli_stmt_bind_param($stmt,'i',$id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); }
    header('Location: manage_scenes.php'); exit;
  }
  elseif ($action === 'spot_add') {
    $sid = (int)($_POST['scene_id'] ?? 0);
    $x = (float)($_POST['x'] ?? -1); $y = (float)($_POST['y'] ?? -1);
    $cid = (int)($_POST['category_id'] ?? 0);
    if ($sid && $cid && $x>=0 && $x<=100 && $y>=0 && $y<=100) {
      $stmt = mysqli_prepare($conn, 'INSERT INTO scene_hotspots (scene_id,x,y,category_id) VALUES (?,?,?,?)');
      mysqli_stmt_bind_param($stmt, 'iddi', $sid, $x, $y, $cid);
      mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
      header('Location: manage_scenes.php?edit='.$sid); exit;
    }
    $err = 'เพิ่มจุดไม่สำเร็จ (คลิกบนรูปและเลือกหมวดก่อน)';
    header('Location: manage_scenes.php?edit='.$sid.'&err=1'); exit;
  }
  elseif ($action === 'spot_delete') {
    $id = (int)($_POST['id'] ?? 0); $sid = (int)($_POST['scene_id'] ?? 0);
    if ($id) { $stmt = mysqli_prepare($conn, 'DELETE FROM scene_hotspots WHERE id=?');
      mysqli_stmt_bind_param($stmt,'i',$id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); }
    header('Location: manage_scenes.php?edit='.$sid); exit;
  }
}

// categories (สำหรับ dropdown ปลายทาง)
$cats = [];
$cr = mysqli_query($conn, 'SELECT id, name FROM categories ORDER BY name');
while ($c = mysqli_fetch_assoc($cr)) $cats[] = $c;

$editId = filter_var($_GET['edit'] ?? '', FILTER_VALIDATE_INT) ?: 0;
?>
<!doctype html><html lang="th"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>จัดการ Showcase · SHOPHUB</title>
<link rel="stylesheet" href="assets/css/style.css"></head><body><div class="container">

<div class="admin-bar">
  <h2>จัดการ Showcase</h2>
  <nav class="btnrow">
    <a class="btn btn-ghost" href="admin.php">&larr; จัดการสินค้า</a>
    <a class="btn btn-ghost" href="showcase.php" target="_blank">เปิดหน้า Showcase ↗</a>
  </nav>
</div>
<?php if ($err): ?><p class="flash-err"><?= esc($err) ?></p><?php endif; ?>

<?php
if ($editId):
  // ----- โหมดแก้ไขฉากเดียว: ตั้งรูป + วางจุด -----
  $stmt = mysqli_prepare($conn, 'SELECT id,title,subtitle,image_url FROM scenes WHERE id=?');
  mysqli_stmt_bind_param($stmt,'i',$editId); mysqli_stmt_execute($stmt);
  $scene = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);
  if (!$scene) { echo '<p class="empty">ไม่พบฉากนี้</p></div></body></html>'; exit; }
  $hs = [];
  $hstmt = mysqli_prepare($conn, 'SELECT h.id,h.x,h.y,h.category_id,c.name AS cat FROM scene_hotspots h JOIN categories c ON c.id=h.category_id WHERE h.scene_id=? ORDER BY h.id');
  mysqli_stmt_bind_param($hstmt,'i',$editId); mysqli_stmt_execute($hstmt);
  $hres = mysqli_stmt_get_result($hstmt); while ($r = mysqli_fetch_assoc($hres)) $hs[] = $r; mysqli_stmt_close($hstmt);
?>
  <?php if (isset($_GET['ok'])): ?><p class="flash-ok">บันทึกแล้ว</p><?php endif; ?>

  <section class="panel">
    <h3 class="panel-h">ข้อมูลฉาก</h3>
    <form method="post" class="form-grid">
      <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
      <input type="hidden" name="action" value="scene_update">
      <input type="hidden" name="id" value="<?= (int)$scene['id'] ?>">
      <label>ชื่อฉาก (ไทย)<input name="title" required value="<?= esc($scene['title']) ?>"></label>
      <label>คำบรรยาย/อังกฤษ<input name="subtitle" value="<?= esc($scene['subtitle']) ?>"></label>
      <label class="full">ลิงก์รูป (assets/img/... หรือ URL)<input name="image_url" required value="<?= esc($scene['image_url']) ?>"></label>
      <div class="full"><button class="btn btn-primary" type="submit">บันทึกชื่อ/รูป</button></div>
    </form>
  </section>

  <section class="panel">
    <h3 class="panel-h">วางจุดคลิก — คลิกบนรูปตรงตำแหน่งที่ต้องการ แล้วเลือกหมวด</h3>
    <div class="editor-stage" id="estage">
      <img src="<?= esc($scene['image_url']) ?>" alt="" id="eimg">
      <?php foreach ($hs as $sp): ?>
        <span class="edot" style="left:<?= (float)$sp['x'] ?>%;top:<?= (float)$sp['y'] ?>%" title="<?= esc($sp['cat']) ?>"></span>
      <?php endforeach; ?>
      <span class="edot edot-new" id="enew" hidden></span>
    </div>

    <form method="post" class="spot-form" id="spotform">
      <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
      <input type="hidden" name="action" value="spot_add">
      <input type="hidden" name="scene_id" value="<?= (int)$scene['id'] ?>">
      <input type="hidden" name="x" id="fx" value="">
      <input type="hidden" name="y" id="fy" value="">
      <span class="pos-readout" id="readout">ยังไม่ได้เลือกตำแหน่ง</span>
      <select name="category_id" required>
        <option value="">-- เลือกหมวดปลายทาง --</option>
        <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= esc($c['name']) ?></option><?php endforeach; ?>
      </select>
      <button class="btn btn-primary" type="submit" id="addspot" disabled>+ เพิ่มจุด</button>
    </form>

    <?php if ($hs): ?>
    <div class="spot-list">
      <?php foreach ($hs as $sp): ?>
        <div class="spot-item">
          <span class="spot-item-pos">(<?= round($sp['x']) ?>%, <?= round($sp['y']) ?>%)</span>
          <span class="spot-item-cat">&rarr; <?= esc($sp['cat']) ?></span>
          <form method="post" class="inline">
            <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
            <input type="hidden" name="action" value="spot_delete">
            <input type="hidden" name="id" value="<?= (int)$sp['id'] ?>">
            <input type="hidden" name="scene_id" value="<?= (int)$scene['id'] ?>">
            <button class="btn btn-sm btn-danger-soft" type="submit">ลบจุด</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?><p class="muted">ยังไม่มีจุดในฉากนี้</p><?php endif; ?>
  </section>

  <script>
  (function(){
    const stage=document.getElementById('estage'), img=document.getElementById('eimg'),
          dot=document.getElementById('enew'), fx=document.getElementById('fx'), fy=document.getElementById('fy'),
          readout=document.getElementById('readout'), btn=document.getElementById('addspot');
    stage.addEventListener('click', e=>{
      if (e.target.classList.contains('edot')) return;
      const r=img.getBoundingClientRect();
      const x=Math.max(0,Math.min(100,(e.clientX-r.left)/r.width*100));
      const y=Math.max(0,Math.min(100,(e.clientY-r.top)/r.height*100));
      fx.value=x.toFixed(2); fy.value=y.toFixed(2);
      dot.style.left=x+'%'; dot.style.top=y+'%'; dot.hidden=false;
      readout.textContent='ตำแหน่ง: '+x.toFixed(0)+'%, '+y.toFixed(0)+'%';
      btn.disabled=false;
    });
  })();
  </script>

<?php else:
  // ----- โหมดรายการฉากทั้งหมด -----
  $scenes = [];
  $sq = mysqli_query($conn, 'SELECT s.id,s.title,s.subtitle,s.image_url,
            (SELECT COUNT(*) FROM scene_hotspots h WHERE h.scene_id=s.id) AS spots
            FROM scenes s ORDER BY s.sort_order, s.id');
  while ($r = mysqli_fetch_assoc($sq)) $scenes[] = $r;
?>
  <section class="panel">
    <h3 class="panel-h">เพิ่มฉากใหม่</h3>
    <form method="post" class="form-grid">
      <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
      <input type="hidden" name="action" value="scene_add">
      <label>ชื่อฉาก (ไทย)<input name="title" required placeholder="เช่น ห้องสายเกม"></label>
      <label>คำบรรยาย/อังกฤษ<input name="subtitle" placeholder="เช่น Gaming Room"></label>
      <label class="full">ลิงก์รูป<input name="image_url" required placeholder="assets/img/room-warm.svg หรือ https://..."></label>
      <div class="full"><button class="btn btn-primary" type="submit">+ เพิ่มฉาก แล้วไปวางจุด</button></div>
    </form>
  </section>

  <?php if ($scenes): ?>
  <div class="scene-grid">
    <?php foreach ($scenes as $s): ?>
    <div class="scene-card">
      <a class="scene-thumb" href="manage_scenes.php?edit=<?= (int)$s['id'] ?>"><img src="<?= esc($s['image_url']) ?>" alt=""></a>
      <div class="scene-meta">
        <div class="scene-title"><?= esc($s['title']) ?></div>
        <div class="scene-sub"><?= esc($s['subtitle']) ?> · <?= (int)$s['spots'] ?> จุด</div>
      </div>
      <div class="scene-actions">
        <a class="btn btn-sm btn-primary" href="manage_scenes.php?edit=<?= (int)$s['id'] ?>">แก้ไข / วางจุด</a>
        <form method="post" class="inline" onsubmit="return confirm('ลบฉากนี้และจุดทั้งหมด?')">
          <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
          <input type="hidden" name="action" value="scene_delete">
          <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
          <button class="btn btn-sm btn-danger-soft" type="submit">ลบ</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?><p class="empty">ยังไม่มีฉาก — เพิ่มฉากแรกด้านบน</p><?php endif; ?>
<?php endif; ?>

</div></body></html>
