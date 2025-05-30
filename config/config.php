<?php
// Cấu hình chung cho ứng dụng

// Cấu hình cơ sở dữ liệu
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');
define('DB_NAME', 'warehouse');

// Cấu hình ứng dụng
define('APP_NAME', 'Hệ thống Quản lý Kho');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/warehouse');

// Cấu hình session
define('SESSION_TIMEOUT', 3600); // 1 giờ
define('SESSION_NAME', 'warehouse_session');

// Cấu hình bảo mật
define('PASSWORD_MIN_LENGTH', 6);
define('LOGIN_MAX_ATTEMPTS', 5);
define('ACCOUNT_LOCK_TIME', 900); // 15 phút

// Cấu hình email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'zzztamdzzz@gmail.com');
define('SMTP_PASSWORD', 'lnkl vnjl hgsh ursi');

 // Start of Selection
// Cấu hình OTP
define('OTP_LENGTH', 6);
define('OTP_EXPIRY_TIME', 300); // 5 phút

// Cấu hình file upload
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Cấu hình phân trang
define('ITEMS_PER_PAGE', 20);

// Múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Bật error reporting cho development
if ($_SERVER['HTTP_HOST'] === 'localhost') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Tạo thư mục cần thiết nếu chưa tồn tại
$directories = [
    __DIR__ . '/../tmp',
    __DIR__ . '/../logs',
    __DIR__ . '/../uploads'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

?> 