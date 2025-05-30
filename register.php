<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/config.php';
require_once 'inc/security.php';
require_once 'config/connect.php';
require_once 'inc/auth.php';

// Tự động dọn dẹp các tài khoản OTP hết hạn (chạy ngẫu nhiên 10% lần truy cập)
if (rand(1, 10) === 1) {
    try {
        $stmt_cleanup_expired = $pdo->prepare("DELETE FROM users WHERE is_active = FALSE AND otp_expiry < NOW()");
        $stmt_cleanup_expired->execute();
        $deleted_count = $stmt_cleanup_expired->rowCount();
        if ($deleted_count > 0) {
            // Sử dụng hàm logSystem từ auth.php
            logSystem('INFO', 'AUTO_CLEANUP_EXPIRED_OTP', "Đã tự động xóa {$deleted_count} tài khoản có OTP hết hạn.");
        }
    } catch (PDOException $e) {
        error_log("Lỗi khi tự động dọn dẹp tài khoản OTP hết hạn: " . $e->getMessage());
    }
}

// Xử lý yêu cầu thay đổi email hoặc bắt đầu lại quá trình đăng ký
if (isset($_GET['action']) && $_GET['action'] == 'change_email') {
    if (isset($_SESSION['verify_email'])) {
        $email_to_cleanup = $_SESSION['verify_email'];
        try {
            $stmt_get_user = $pdo->prepare("SELECT user_id, username FROM users WHERE email = ? AND is_active = FALSE");
            $stmt_get_user->execute([$email_to_cleanup]);
            $user_to_cleanup = $stmt_get_user->fetch();

            if ($user_to_cleanup) {
                $stmt_cleanup = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND is_active = FALSE");
                $stmt_cleanup->execute([$user_to_cleanup['user_id']]);

                if ($stmt_cleanup->rowCount() > 0) {
                    logSystem('INFO', 'REGISTRATION_ATTEMPT_CLEANUP', 'Đã xóa tài khoản chờ kích hoạt (ID: ' . $user_to_cleanup['user_id'] . ', Username: ' . escapeOutput($user_to_cleanup['username']) . ', Email: ' . escapeOutput($email_to_cleanup) . ') do người dùng yêu cầu thay đổi email.');
                }
            }
        } catch (PDOException $e) {
            error_log("Lỗi khi dọn dẹp tài khoản chờ kích hoạt (email: " . escapeOutput($email_to_cleanup) . "): " . $e->getMessage());
        }
        unset($_SESSION['verify_email']);
    }
    header("Location: register.php");
    exit;
}

$error = '';
$success = '';
$show_otp_form = false;

$input_username = '';
$input_full_name = '';
$input_email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Lỗi xác thực không hợp lệ (CSRF). Vui lòng thử lại.";
        logSystem('CRITICAL', 'CSRF_VALIDATION_FAILED', 'CSRF Token không hợp lệ từ IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
    } else {
        if (isset($_POST['verify_otp'])) {
            // ----- XỬ LÝ XÁC THỰC OTP -----
            $email_to_verify = cleanInput($_POST['email_for_otp'] ?? '');
            $otp_entered = cleanInput($_POST['otp'] ?? '');

            if (empty($email_to_verify) || empty($otp_entered)) {
                $error = "Vui lòng nhập mã OTP.";
                $show_otp_form = true;
                if (!isset($_SESSION['verify_email']) && !empty($email_to_verify) && validateEmail($email_to_verify)) {
                     $_SESSION['verify_email'] = $email_to_verify;
                }
            } elseif (!validateEmail($email_to_verify)) {
                $error = "Địa chỉ email không hợp lệ.";
                $show_otp_form = true;
                if (!isset($_SESSION['verify_email']) && !empty($email_to_verify) && validateEmail($email_to_verify)) {
                     $_SESSION['verify_email'] = $email_to_verify;
                }
            } else {
                $rate_limit_key_otp = 'otp_verify_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $email_to_verify);
                if (!checkRateLimit($rate_limit_key_otp, 5, (defined('OTP_EXPIRY_TIME') ? OTP_EXPIRY_TIME : 300))) {
                    $error = "Bạn đã thử quá nhiều lần để xác thực OTP. Vui lòng đợi trong ít phút.";
                    $show_otp_form = true;
                    if (!isset($_SESSION['verify_email']) && !empty($email_to_verify)) {
                         $_SESSION['verify_email'] = $email_to_verify;
                    }
                } else {
                    try {
                        $stmt = $pdo->prepare("SELECT user_id, otp_expiry FROM users WHERE email = ? AND otp = ? AND is_active = FALSE");
                        $stmt->execute([$email_to_verify, $otp_entered]);
                        $user = $stmt->fetch();
                        
                        if ($user && isValidOTP($user['otp_expiry'])) {
                            $stmt_activate = $pdo->prepare("UPDATE users SET is_active = TRUE, otp = NULL, otp_expiry = NULL WHERE user_id = ?");
                            $stmt_activate->execute([$user['user_id']]);
                            
                            // *** GHI LOG KHI KÍCH HOẠT THÀNH CÔNG ***
                            logUserActivity($user['user_id'], 'ACCOUNT_ACTIVATION_SUCCESS', 'Kích hoạt tài khoản thành công qua OTP.');
                            
                            $_SESSION['flash_success'] = "Kích hoạt tài khoản thành công! Vui lòng đăng nhập.";
                            unset($_SESSION['verify_email']);
                            secureRedirect('login.php');
                        } else {
                            logSystem('WARNING', 'OTP_VERIFY_FAIL', 'Xác thực OTP thất bại hoặc OTP hết hạn cho email: ' . escapeOutput($email_to_verify));
                            $error = "Mã OTP không hợp lệ hoặc đã hết hạn.";
                            // ... (logic giữ session và $show_otp_form như cũ)
                            $show_otp_form = true; 
                            if (!isset($_SESSION['verify_email']) && !empty($email_to_verify)) {
                                $_SESSION['verify_email'] = $email_to_verify;
                            }
                        }
                    } catch (PDOException $e) {
                        error_log("Lỗi xác thực OTP khi đăng ký: " . $e->getMessage());
                        $error = "Đã xảy ra lỗi hệ thống khi xác thực OTP. Vui lòng thử lại sau.";
                        // ... (logic giữ session và $show_otp_form như cũ)
                        $show_otp_form = true;
                        if (!isset($_SESSION['verify_email']) && !empty($email_to_verify)) {
                            $_SESSION['verify_email'] = $email_to_verify;
                        }
                    }
                }
            }
        } elseif (isset($_POST['register'])) {
            // ----- XỬ LÝ ĐĂNG KÝ BAN ĐẦU -----
            $input_username = cleanInput($_POST['username'] ?? '');
            $input_full_name = cleanInput($_POST['full_name'] ?? '');
            $input_email = cleanInput($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');

            $min_pass_len = defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 6;

            if (empty($input_username) || empty($input_full_name) || empty($input_email) || empty($password) || empty($confirm_password)) {
                $error = "Vui lòng điền đầy đủ thông tin.";
            } elseif (!validateUsername($input_username)) {
                $error = "Tên đăng nhập không hợp lệ (3-20 ký tự, chỉ gồm chữ, số và dấu gạch dưới).";
            } elseif (!validateEmail($input_email)) {
                $error = "Địa chỉ email không hợp lệ.";
            } elseif (strlen($password) < $min_pass_len) {
                 $error = "Mật khẩu phải có ít nhất " . $min_pass_len . " ký tự.";
            } elseif (!validatePassword($password)) {
                 $error = "Mật khẩu phải chứa ít nhất một chữ cái và một chữ số.";
            } elseif ($password !== $confirm_password) {
                $error = "Mật khẩu và xác nhận mật khẩu không khớp.";
            } else {
                $rate_limit_key_register = 'register_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $_SERVER['REMOTE_ADDR'] ?? 'unknown_ip');
                if (!checkRateLimit($rate_limit_key_register, 5, 3600)) {
                    $error = "Bạn đã thực hiện quá nhiều yêu cầu đăng ký. Vui lòng thử lại sau.";
                } else {
                    try {
                        $stmt_check = $pdo->prepare("SELECT user_id, username, email, is_active FROM users WHERE username = ? OR email = ?");
                        $stmt_check->execute([$input_username, $input_email]);
                        $existing_users = $stmt_check->fetchAll();
                        $has_active_conflict = false;

                        if ($existing_users) {
                            foreach ($existing_users as $existing_user) {
                                // Kiểm tra trùng username hoặc email đã active
                                if ($existing_user['is_active'] && (strtolower($existing_user['username']) === strtolower($input_username) || strtolower($existing_user['email']) === strtolower($input_email))) {
                                    $has_active_conflict = true;
                                    $error = "Tên đăng nhập hoặc email đã tồn tại và đã được kích hoạt.";
                                    break;
                                }
                            }
                            // Nếu không có conflict active, xóa các bản ghi inactive trùng username hoặc email
                            if (!$has_active_conflict) {
                                foreach ($existing_users as $existing_user) {
                                    if (!$existing_user['is_active'] && (strtolower($existing_user['username']) === strtolower($input_username) || strtolower($existing_user['email']) === strtolower($input_email))) {
                                        $stmt_delete_inactive = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                                        $stmt_delete_inactive->execute([$existing_user['user_id']]);
                                        logSystem('INFO', 'INACTIVE_USER_CLEANUP_ON_REGISTER', 'Xóa tài khoản chưa active (ID: '.$existing_user['user_id'].') có username/email trùng khi đăng ký mới.');
                                    }
                                }
                            }
                        }
                        
                        if (empty($error)) {
                            $otp = generateOTP();
                            $otp_expiry_seconds = defined('OTP_EXPIRY_TIME') ? OTP_EXPIRY_TIME : 1800;
                            $otp_expiry_datetime = date('Y-m-d H:i:s', strtotime("+" . $otp_expiry_seconds . " seconds"));
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $default_role = 'user';

                            $stmt_insert = $pdo->prepare(
                                "INSERT INTO users (username, full_name, email, password_hash, role, is_active, otp, otp_expiry, created_at) 
                                 VALUES (?, ?, ?, ?, ?, FALSE, ?, ?, NOW())"
                            );
                            $stmt_insert->execute([$input_username, $input_full_name, $input_email, $hashed_password, $default_role, $otp, $otp_expiry_datetime]);
                            $user_id = $pdo->lastInsertId(); // Lấy user_id vừa tạo

                            $subject = "Xác thực đăng ký tài khoản - " . (defined('APP_NAME') ? escapeOutput(APP_NAME) : "Hệ thống");
                            $otp_expiry_minutes = $otp_expiry_seconds / 60;
                            $message_body = "<p>Xin chào " . escapeOutput($input_full_name) . ",</p>
                                             <p>Cảm ơn bạn đã đăng ký tài khoản tại " . (defined('APP_NAME') ? escapeOutput(APP_NAME) : "Hệ thống của chúng tôi") . ".</p>
                                             <p>Mã OTP của bạn để kích hoạt tài khoản là: <strong>{$otp}</strong></p>
                                             <p>Mã này sẽ hết hạn sau {$otp_expiry_minutes} phút.</p>
                                             <p>Vui lòng nhập mã này trên trang đăng ký để hoàn tất.</p>
                                             <p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email.</p>
                                             <p>Trân trọng,<br>" . (defined('APP_NAME') ? escapeOutput(APP_NAME) : "Đội ngũ hỗ trợ") . "</p>";
                            
                            if (sendMail($input_email, $subject, $message_body)) {
                                $_SESSION['verify_email'] = $input_email;
                                $success = "Đăng ký thành công bước đầu! Mã OTP đã được gửi đến email của bạn (" . escapeOutput($input_email) . "). Vui lòng kiểm tra (kể cả thư mục spam) và nhập mã xác thực bên dưới để hoàn tất.";
                                $show_otp_form = true;
                                
                                logSystem('INFO', 'REGISTRATION_OTP_SENT_SYS', 'Đã gửi OTP đăng ký cho email: ' . escapeOutput($input_email) . ', username tạm: ' . escapeOutput($input_username) . ', UserID (chưa active): ' . $user_id);
                                
                                $input_username = $input_full_name = $input_email = '';
                            } else {
                                logSystem('ERROR', 'MAIL_SEND_FAIL_REGISTER', 'Không thể gửi email OTP đăng ký cho: ' . escapeOutput($input_email));
                                $error = "Đã xảy ra lỗi khi gửi email xác thực. Tài khoản của bạn đã được tạo nhưng chưa kích hoạt. Vui lòng thử lại sau hoặc liên hệ quản trị viên. (Lỗi cấu hình email hệ thống có thể là nguyên nhân).";
                                // Xóa user vừa tạo nếu không gửi được mail
                                $stmt_delete_on_mail_fail = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND is_active = FALSE");
                                $stmt_delete_on_mail_fail->execute([$user_id]);
                                if($stmt_delete_on_mail_fail->rowCount() > 0){
                                     logSystem('INFO', 'USER_DELETED_ON_MAIL_FAIL', 'Đã xóa user (ID: '.$user_id.') do không gửi được mail OTP.');
                                }
                            }
                        }
                    } catch (PDOException $e) {
                        error_log("Lỗi trong quá trình đăng ký: " . $e->getMessage());
                        $error = "Đã xảy ra lỗi máy chủ khi đăng ký. Vui lòng thử lại.";
                       
                    }
                }
            }
            // Giữ lại input nếu có lỗi và form register vẫn hiển thị
            if (!empty($error) && !$show_otp_form) {
                // $input_username, $input_full_name, $input_email đã được gán giá trị từ $_POST ở đầu
            }
        }
    }
}

// Cập nhật logic hiển thị form OTP
if (!$show_otp_form && isset($_SESSION['verify_email'])) {
    if (!isset($_POST['verify_otp']) || (isset($_POST['verify_otp']) && !empty($error))) {
        $show_otp_form = true;
    }
}
if ($show_otp_form && !isset($_SESSION['verify_email']) && !(isset($_SESSION['flash_success']))) {
    $show_otp_form = false; 
}

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - Quản Lý Kho Hàng</title>
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

        .return:hover {
            color: red;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="text-center mb-5">Đăng Ký Tài Khoản</h3>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger text-center" role="alert"><?php echo escapeOutput($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success) && !$show_otp_form): ?>
                            <div class="alert alert-success text-center" role="alert"><?php echo $success; /* Cho phép HTML nếu có link */ ?></div>
                        <?php endif; ?>
                        
                        <?php if ($show_otp_form): ?>
                            <?php if (!empty($success) && strpos($success, "Mã OTP đã được gửi") !== false): ?>
                                <div class="alert alert-info text-center" role="alert"><?php echo escapeOutput($success); ?></div>
                            <?php endif; ?>

                            <form method="POST" action="register.php" id="otpForm">
                                <input type="hidden" name="csrf_token" value="<?php echo escapeOutput($csrf_token); ?>">
                                <input type="hidden" name="email_for_otp" value="<?php echo escapeOutput($_SESSION['verify_email']); ?>">
                                <div class="mb-4">
                                    <label for="otp" class="form-label">Mã xác thực (OTP)</label>
                                    <input type="text" class="form-control" id="otp" name="otp" placeholder="Nhập mã OTP" required autofocus pattern="\d{<?php echo defined('OTP_LENGTH') ? OTP_LENGTH : 6; ?>}" title="OTP phải là <?php echo defined('OTP_LENGTH') ? OTP_LENGTH : 6; ?> chữ số">
                                </div>
                                <button type="submit" name="verify_otp" class="btn btn-primary w-100">Xác thực OTP</button>
                            </form>
                            <div class="mt-3 text-center">
                                <p class="mb-1"><a href="register.php?action=change_email">Nhập sai email hoặc muốn thử lại?</a></p>
                                <hr class="my-2">
                                <a href="login.php">Quay lại đăng nhập</a>
                            </div>
                        <?php elseif (empty($success) || (!empty($success) && strpos($success, "Mã OTP đã được gửi") === false) ): ?>
                            <form method="POST" action="register.php" id="registerForm">
                                <input type="hidden" name="csrf_token" value="<?php echo escapeOutput($csrf_token); ?>">
                                <div class="mb-4">
                                    <label for="username" class="form-label">Tên đăng nhập</label>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="VD: user123" required value="<?php echo escapeOutput($input_username); ?>" pattern="^[a-zA-Z0-9_]{3,20}$" title="3-20 ký tự, chỉ gồm chữ cái, số và dấu gạch dưới.">
                                </div>
                                <div class="mb-4">
                                    <label for="full_name" class="form-label">Họ và tên</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Nhập họ và tên đầy đủ" required value="<?php echo escapeOutput($input_full_name); ?>">
                                </div>
                                <div class="mb-4">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="VD: email@example.com" required value="<?php echo escapeOutput($input_email); ?>">
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="form-label">Mật khẩu</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Ít nhất <?php echo defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 6; ?> ký tự, gồm chữ và số" required>
                                </div>
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
                                </div>
                                <button type="submit" name="register" class="btn btn-primary w-100">Đăng Ký</button>
                            </form>
                            <div class="mt-3 text-center">
                                <a href="login.php">Đã có tài khoản? Đăng nhập</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
   
</body>
</html>