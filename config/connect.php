<?php
require_once __DIR__ . '/config.php';

// Thông tin kết nối từ config
$host = DB_HOST;
$username = DB_USERNAME;
$password = DB_PASSWORD;
$database = DB_NAME;

try {
    // Kết nối PDO
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    
    // Cấu hình PDO để báo lỗi
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Đảm bảo tương thích ngược với code sử dụng mysqli
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Kết nối MySQLi thất bại: " . $conn->connect_error);
    }
    
    // Đặt charset cho kết nối mysqli
    $conn->set_charset("utf8mb4");
    
} catch (PDOException $e) {
    error_log("Lỗi kết nối PDO: " . $e->getMessage());
    die("Không thể kết nối cơ sở dữ liệu. Vui lòng thử lại sau.");
} catch (Exception $e) {
    error_log("Lỗi kết nối: " . $e->getMessage());
    die("Không thể kết nối cơ sở dữ liệu. Vui lòng thử lại sau.");
}
?>