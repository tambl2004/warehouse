<?php
// Các hàm bảo mật cho ứng dụng

// Làm sạch input để tránh XSS
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate username (chỉ cho phép chữ cái, số và dấu gạch dưới)
function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

// Validate password (ít nhất 6 ký tự, có chữ và số)
function validatePassword($password) {
    return strlen($password) >= 6 && preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
}

// Tạo CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting đơn giản
function checkRateLimit($key, $max_attempts = 5, $time_window = 300) {
    $rate_limit_file = __DIR__ . '/../tmp/rate_limit_' . md5($key);
    
    if (!file_exists(dirname($rate_limit_file))) {
        mkdir(dirname($rate_limit_file), 0777, true);
    }
    
    $current_time = time();
    $attempts = [];
    
    if (file_exists($rate_limit_file)) {
        $attempts = json_decode(file_get_contents($rate_limit_file), true) ?: [];
    }
    
    // Loại bỏ các attempts cũ
    $attempts = array_filter($attempts, function($timestamp) use ($current_time, $time_window) {
        return ($current_time - $timestamp) < $time_window;
    });
    
    if (count($attempts) >= $max_attempts) {
        return false;
    }
    
    // Thêm attempt mới
    $attempts[] = $current_time;
    file_put_contents($rate_limit_file, json_encode($attempts));
    
    return true;
}

// Escape output để tránh XSS
function escapeOutput($data) {
    if (is_array($data)) {
        return array_map('escapeOutput', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Secure redirect
function secureRedirect($url, $status_code = 302) {
    // Chỉ cho phép redirect trong cùng domain
    $parsed = parse_url($url);
    if (!empty($parsed['host']) && $parsed['host'] !== $_SERVER['HTTP_HOST']) {
        $url = '/';
    }
    
    header("Location: $url", true, $status_code);
    exit();
}

?> 