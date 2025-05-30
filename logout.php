<?php
include 'inc/auth.php';
include 'config/connect.php';

if (isLoggedIn()) {
    // Ghi log đăng xuất
    logUserActivity($_SESSION['user_id'], 'LOGOUT', 'Đăng xuất thành công');
    
    // Xóa session
    session_unset();
    session_destroy();
}

// Chuyển hướng về trang đăng nhập
header("Location: login.php");
exit;
?>