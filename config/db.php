<?php
// Local defaults are safe for XAMPP. Real credentials live in secret/config.php (gitignored).
if (file_exists(__DIR__ . '/../secret/config.php')) {
    require __DIR__ . '/../secret/config.php';
}
$host   = 'localhost';
$user   = 'root';
$pass   = '';
$dbname = 'shophub';
$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) { error_log(mysqli_connect_error()); http_response_code(500); exit('Database unavailable'); }
mysqli_set_charset($conn, 'utf8mb4');