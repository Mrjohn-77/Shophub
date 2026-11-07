<?php
// ฟังก์ชันที่ใช้ร่วมกันทุกหน้า (index, category, product) — รวมไว้ที่เดียว ลดโค้ดซ้ำ
if (!function_exists('h')) {
  function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

// นิยามจุดตัดรอบวัน 04:00 น. สำหรับนับ "ยอดคลิกวันนี้" (ใช้เวลาฝั่ง DB)
function day_cutoff_sql(){
  return "CASE WHEN HOUR(NOW()) >= 4 THEN CONCAT(CURDATE(),' 04:00:00')
               ELSE CONCAT(CURDATE() - INTERVAL 1 DAY, ' 04:00:00') END";
}

// SVG เปลวไฟสำหรับป้าย HOT
function flame($cls='flame'){
  return '<svg class="'.$cls.'" viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 1.5c.3 3.2-2.8 4.6-3.6 7.3-.4-1-.3-2.4-1.3-3.1-.2 1.9-2.6 3.5-2.6 6.8a6 6 0 0 0 12 0c0-3.4-2.7-5.2-3.8-8.2-.6 1.7-1.9 1.9-1.9.2 0-1.3.6-2 1.2-3z" fill="url(#fg)"/></svg>';
}

// defs ไล่สีของเปลวไฟ (ต้องมีครั้งเดียวต่อหน้า)
function flame_defs(){
  return '<svg width="0" height="0" style="position:absolute"><defs>'
       . '<linearGradient id="fg" x1="0" y1="1" x2="0" y2="0">'
       . '<stop offset="0" stop-color="#ff3d2e"/><stop offset=".5" stop-color="#ff8a1e"/><stop offset="1" stop-color="#ffd93b"/>'
       . '</linearGradient></defs></svg>';
}

// การ์ดสินค้า 1 ใบ — ใช้ทั้งหน้าแรก หน้าหมวด และสินค้าใกล้เคียง
// $p ต้องมี: id, name, price, image_url, platform, clicks, day_clicks ; created_at ถ้ามีจะโชว์ป้าย NEW
function card($p, $t = []){
  $view = $t['view']   ?? 'View';
  $clk  = $t['clicks'] ?? 'clicks';
  $newl = $t['new']    ?? 'NEW';
  $hot   = (int)($p['day_clicks'] ?? 0) >= 10;                 // HOT = ยอดคลิกวันนี้ >= 10
  $isnew = !empty($p['created_at']) && strtotime($p['created_at']) >= strtotime('-7 days'); // NEW = เพิ่มภายใน 7 วัน
  echo '<article class="card">';
  echo '<div class="thumb">';
  echo $p['image_url'] ? '<img src="'.h($p['image_url']).'" alt="'.h($p['name']).'">' : '<span class="noimg">No image</span>';
  echo '<span class="chip">'.h($p['platform']).'</span>';
  if ($isnew) echo '<span class="new-badge">'.h($newl).'</span>';
  if ($hot)   echo '<span class="hot"><span class="embers"><i></i><i></i><i></i></span>'.flame('flame-sm').'HOT</span>';
  echo '</div><div class="body"><h3>'.h($p['name']).'</h3>';
  echo '<div class="row"><span class="price">฿'.number_format((float)$p['price'],2).'</span><span class="clicks">'.(int)$p['clicks'].' '.h($clk).'</span></div>';
  echo '<a class="btn" href="product.php?id='.(int)$p['id'].'">'.h($view).' &rarr;</a>';
  echo '</div></article>';
}
