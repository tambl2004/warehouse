<?php
session_start(); // Thêm dòng này để khởi tạo session
include 'config/connect.php';
include 'inc/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_otp'])) {
        // Xử lý gửi OTP
        $email = trim($_POST['email']);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Tạo OTP và thời gian hết hạn (15 phút)
            $otp = generateOTP();
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Cập nhật OTP vào database
            $stmt = $pdo->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE user_id = ?");
            $stmt->execute([$otp, $otp_expiry, $user['user_id']]);
            
            // Gửi email
            $subject = "Khôi phục mật khẩu";
            $message = "
            <p>Xin chào,</p>
            <p>Chúng tôi nhận được yêu cầu khôi phục mật khẩu cho tài khoản của bạn.</p>
            <p>Mã OTP của bạn là: <strong>{$otp}</strong></p>
            <p>Mã này sẽ hết hạn sau 15 phút.</p>
            <p>Nếu bạn không yêu cầu khôi phục mật khẩu, vui lòng bỏ qua email này.</p>
            <p>Trân trọng,<br>Hệ thống Quản lý Kho</p>";
            
            if (sendMail($email, $subject, $message)) {
                $_SESSION['reset_email'] = $email;
                $success = "Mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra và nhập mã xác thực.";
            } else {
                $error = "Không thể gửi email. Vui lòng thử lại sau.";
            }
        } else {
            $error = "Email không tồn tại trong hệ thống.";
        }
    } elseif (isset($_POST['verify_otp'])) {
        // Xử lý xác thực OTP
        $email = $_POST['email'];
        $otp = $_POST['otp'];
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND otp = ?");
        $stmt->execute([$email, $otp]);
        $user = $stmt->fetch();
        
        if ($user && isValidOTP($user['otp_expiry'])) {
            $_SESSION['reset_user_id'] = $user['user_id'];
            $success = "Xác thực thành công. Vui lòng đặt lại mật khẩu mới.";
        } else {
            $error = "Mã OTP không hợp lệ hoặc đã hết hạn.";
            unset($_SESSION['reset_email']); // Xóa session để yêu cầu nhập lại email
        }
    } elseif (isset($_POST['reset_password']) && isset($_SESSION['reset_user_id'])) {
        // Xử lý đặt lại mật khẩu
        $user_id = $_SESSION['reset_user_id'];
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        
        if ($password !== $confirm_password) {
            $error = "Mật khẩu không khớp.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Cập nhật mật khẩu mới
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, otp = NULL, otp_expiry = NULL, 
                                  is_locked = FALSE, login_attempts = 0 WHERE user_id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            // Ghi log
            logUserActivity($user_id, 'RESET_PASSWORD', 'Đặt lại mật khẩu thành công');
            
            // Xóa session
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_user_id']);
            
            $success = "Đặt lại mật khẩu thành công! Vui lòng đăng nhập với mật khẩu mới.";
        }
    } else {
        $error = "Phiên làm việc không hợp lệ. Vui lòng thử lại.";
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_user_id']);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên Mật Khẩu - Quản Lý Kho Hàng</title>
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
        .success {
            color: green;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="text-center mb-5">Quên Mật Khẩu</h3>
                        
                        <?php if ($error): ?>
                            <div class="error text-center"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="success text-center"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['reset_user_id'])): ?>
                            <!-- Form đặt lại mật khẩu -->
                            <form method="POST">
                                <div class="mb-4">
                                    <label for="password" class="form-label">Mật khẩu mới</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu mới" required>
                                </div>
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Xác nhận mật khẩu mới" required>
                                </div>
                                <button type="submit" name="reset_password" class="btn btn-primary w-100">Đặt lại mật khẩu</button>
                            </form>
                        <?php elseif (isset($_SESSION['reset_email'])): ?>
                            <!-- Form xác thực OTP -->
                            <form method="POST">
                                <div class="mb-4">
                                    <label for="otp" class="form-label">Mã xác thực (OTP)</label>
                                    <input type="text" class="form-control" id="otp" name="otp" placeholder="Nhập mã OTP được gửi đến email của bạn" required>
                                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['reset_email']); ?>">
                                </div>
                                <button type="submit" name="verify_otp" class="btn btn-primary w-100">Xác thực</button>
                            </form>
                        <?php else: ?>
                            <!-- Form nhập email -->
                            <form method="POST">
                                <div class="mb-4">
                                    <label for="email" class="form-label">Nhập email của bạn</label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Nhập email" required>
                                </div>
                                <button type="submit" name="send_otp" class="btn btn-primary w-100">Gửi mã xác thực</button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="mt-4 text-center">
                            <a href="login.php">Quay lại đăng nhập</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>