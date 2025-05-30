<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "warehouse";

try {
    // Tạo kết nối PDO
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Thiết lập chế độ báo lỗi
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Xử lý lỗi kết nối
    die("Kết nối thất bại: " . $e->getMessage());
}
?>
