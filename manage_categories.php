<?php
require 'config/db.php';
require 'auth.php';
require_admin();

$error = ''; $ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    // ตารางที่จะจัดการ: categories หรือ platforms (whitelist กันค่าแปลกปลอม)
    $type = $_POST['type'] ?? '';
    $table = $type === 'platform' ? 'platforms' : ($type === 'category' ? 'categories' : '');

    if ($table === '') {
        $error = 'ประเภทไม่ถูกต้อง';
    } elseif ($action === 'add') {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            $error = 'กรอกชื่อให้ถูกต้อง (1-100 ตัวอักษร)';
        } else {
            // กันชื่อซ้ำ (คอลัมน์ name เป็น UNIQUE)
            $stmt = mysqli_prepare($conn, "SELECT id FROM $table WHERE name = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 's', $name);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $dup = mysqli_stmt_num_rows($stmt) > 0;
            mysqli_stmt_close($stmt);
            if ($dup) {
                $error = 'มีชื่อนี้อยู่แล้ว';
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO $table (name) VALUES (?)");
                mysqli_stmt_bind_param($stmt, 's', $name);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $ok = 'เพิ่มแล้ว';
            }
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        $col = $table === 'platforms' ? 'platform_id' : 'category_id';
        // กันลบถ้ายังมีสินค้าใช้อยู่ (ไม่งั้นสินค้าจะกำพร้า / FK RESTRICT จะ error)
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM products WHERE $col = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $used);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ($used > 0) {
            $error = "ลบไม่ได้ ยังมีสินค้า $used ชิ้นใช้รายการนี้อยู่ (ย้ายหรือลบสินค้าก่อน)";
        } else {
            $stmt = mysqli_prepare($conn, "DELETE FROM $table WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $ok = 'ลบแล้ว';
        }
    }
}

// ดึงรายการพร้อมจำนวนสินค้าในแต่ละหมวด/ร้าน (LEFT JOIN เพื่อให้หมวดที่ยังไม่มีสินค้าก็ขึ้น count = 0)
$cats = mysqli_query($conn, "SELECT c.id, c.name, COUNT(p.id) AS n FROM categories c
    LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id, c.name ORDER BY c.name");
$plats = mysqli_query($conn, "SELECT pl.id, pl.name, COUNT(p.id) AS n FROM platforms pl
    LEFT JOIN products p ON p.platform_id = pl.id GROUP BY pl.id, pl.name ORDER BY pl.name");

function render_list($res, $type) {
    while ($r = mysqli_fetch_assoc($res)) {
        echo '<tr style="border-bottom:1px solid #2a1c54">';
        echo '<td>' . esc($r['name']) . '</td>';
        echo '<td>' . (int)$r['n'] . ' ชิ้น</td>';
        echo '<td>';
        if ((int)$r['n'] === 0) {
            echo '<form method="post" style="display:inline" onsubmit="return confirm(\'ลบรายการนี้?\')">';
            echo '<input type="hidden" name="csrf" value="' . esc(csrf_token()) . '">';
            echo '<input type="hidden" name="action" value="delete">';
            echo '<input type="hidden" name="type" value="' . esc($type) . '">';
            echo '<input type="hidden" name="id" value="' . (int)$r['id'] . '">';
            echo '<button type="submit" style="background:none;border:0;color:#f87171;cursor:pointer;padding:0">ลบ</button>';
            echo '</form>';
        } else {
            echo '<span style="color:var(--ink-soft)">มีสินค้าใช้อยู่</span>';
        }
        echo '</td></tr>';
    }
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>จัดการหมวดหมู่ · SHOPHUB</title>
<link rel="stylesheet" href="assets/css/style.css"></head><body><div class="container">
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
  <h2>จัดการหมวดหมู่ และร้าน</h2>
  <a href="admin.php">← จัดการสินค้า</a>
</div>
<?php if ($ok): ?><p class="ok"><?= esc($ok) ?></p><?php endif; ?>
<?php if ($error): ?><p style="color:#f87171"><?= esc($error) ?></p><?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;margin-top:16px">
  <section>
    <h3>หมวดหมู่ (Categories)</h3>
    <form method="post" style="display:flex;gap:8px;margin:10px 0">
      <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="type" value="category">
      <input name="name" required maxlength="100" placeholder="ชื่อหมวดใหม่" style="flex:1;padding:10px">
      <button class="btn" type="submit">เพิ่ม</button>
    </form>
    <table width="100%" cellpadding="8" style="border-collapse:collapse">
      <tr style="text-align:left;border-bottom:2px solid #2a1c54"><th>ชื่อ</th><th>สินค้า</th><th>จัดการ</th></tr>
      <?php render_list($cats, 'category'); ?>
    </table>
  </section>

  <section>
    <h3>ร้าน/แพลตฟอร์ม (Platforms)</h3>
    <form method="post" style="display:flex;gap:8px;margin:10px 0">
      <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="type" value="platform">
      <input name="name" required maxlength="100" placeholder="ชื่อร้านใหม่ เช่น Shopee" style="flex:1;padding:10px">
      <button class="btn" type="submit">เพิ่ม</button>
    </form>
    <table width="100%" cellpadding="8" style="border-collapse:collapse">
      <tr style="text-align:left;border-bottom:2px solid #2a1c54"><th>ชื่อ</th><th>สินค้า</th><th>จัดการ</th></tr>
      <?php render_list($plats, 'platform'); ?>
    </table>
  </section>
</div>
</div></body></html>
