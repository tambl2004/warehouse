<?php
session_start();
include 'config/connect.php';
include 'inc/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    try {
        // Thêm điều kiện AND is_active = TRUE (hoặc is_active = 1)
        $stmt = $pdo->prepare("SELECT user_id, username, password_hash, role, is_locked, full_name 
                              FROM users 
                              WHERE username = ? AND is_active = TRUE"); 
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            // Kiểm tra tài khoản bị khóa
            if ($user['is_locked']) {
                $error = "Tài khoản đã bị khóa do đăng nhập sai nhiều lần. Vui lòng sử dụng chức năng quên mật khẩu.";
            } 
            // Kiểm tra mật khẩu
            elseif (password_verify($password, $user['password_hash'])) {
                // Cập nhật thông tin đăng nhập
                resetLoginAttempts($user['user_id']);
                updateLastLogin($user['user_id']);
                
                // Lưu session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                
                // Ghi log
                logUserActivity($user['user_id'], 'LOGIN', 'Đăng nhập thành công');
                
                // Chuyển hướng theo vai trò
                if ($user['role'] == 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: nhanvien.php");
                }
                exit;
            } else {
                // Tăng số lần đăng nhập sai
                incrementLoginAttempts($user['user_id']);
                $error = "Tên đăng nhập hoặc mật khẩu không đúng.";
            }
        } else {
            // Ghi log cho trường hợp username không tồn tại hoặc chưa active
            logSystem('INFO', 'LOGIN_FAIL_USER_NOT_FOUND_OR_INACTIVE', 'Thất bại đăng nhập, người dùng không tồn tại hoặc chưa kích hoạt: ' . htmlspecialchars($username));
            $error = "Tên đăng nhập hoặc mật khẩu không đúng, hoặc tài khoản chưa được kích hoạt.";
        }
    } catch (PDOException $e) {
        error_log("Lỗi đăng nhập: " . $e->getMessage());
        $error = "Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - Quản Lý Kho Hàng</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            border: none;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            border-radius: 20px;
            padding: 50px;
            width: 100%;
            max-width: 600px;
            background: #ffffff;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            padding: 12px;
            font-size: 1.2rem;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px;
            font-size: 1.1rem;
        }
        .form-label {
            font-size: 1.2rem;
        }
        a {
            text-decoration: none;
            color: #007bff;
            font-size: 1.1rem;
        }
        a:hover {
            text-decoration: underline;
        }
        h3 {
            font-size: 2rem;
            font-weight: bold;
        }
        .error {
            color: red;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        .quenmk{
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="text-center mb-5">Đăng Nhập</h3>
                        <?php if ($error): ?>
                            <div class="error text-center"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-4">
                                <label for="username" class="form-label">Tên đăng nhập</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Nhập tên đăng nhập" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Đăng Nhập</button>
                        </form>
                        <div class="mt-4 text-center">
                            <a href="register.php">Đăng ký tài khoản mới</a> | 
                            <a class="quenmk" href="forgot_password.php">Quên mật khẩu?</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>