<?php
require 'config/db.php';

// รับ id สินค้าจาก URL แล้วตรวจว่าเป็นเลขจำนวนเต็มจริง
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Invalid product'); }

// 1) หา affiliate_url ของสินค้านี้ (prepared statement กัน SQL injection)
$stmt = mysqli_prepare($conn, 'SELECT affiliate_url FROM products WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $url);
$found = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
if (!$found) { http_response_code(404); exit('Product not found'); }

// 2) บันทึก 1 คลิกลงตาราง clicks
$stmt = mysqli_prepare($conn, 'INSERT INTO clicks (product_id) VALUES (?)');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// 3) พาผู้ใช้ไปหน้าร้านค้าจริง
header('Location: ' . $url);
exit;