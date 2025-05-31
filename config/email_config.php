<?php
/**
 * Cấu hình Email cho Hệ thống Quản lý Người dùng
 * File: config/email_config.php
 */

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailConfig {
    // Cấu hình SMTP
    private static $smtp_config = [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-email@gmail.com', // Thay đổi email của bạn
        'password' => 'your-app-password',     // Thay đổi app password
        'from_email' => 'your-email@gmail.com',
        'from_name' => 'Hệ thống Kho Hàng',
        'encryption' => PHPMailer::ENCRYPTION_STARTTLS
    ];

    /**
     * Gửi email OTP cho đăng ký tài khoản
     */
    public static function guiEmailOTP($email, $hoTen, $otp) {
        $subject = 'Mã OTP xác thực tài khoản - Hệ thống Kho Hàng';
        
        $body = self::taoTemplateOTP($hoTen, $otp);
        
        return self::guiEmail($email, $hoTen, $subject, $body);
    }

    /**
     * Gửi email reset mật khẩu
     */
    public static function guiEmailResetPassword($email, $hoTen, $resetToken) {
        $subject = 'Yêu cầu đặt lại mật khẩu - Hệ thống Kho Hàng';
        
        $resetLink = self::taoLinkResetPassword($resetToken);
        $body = self::taoTemplateResetPassword($hoTen, $resetLink);
        
        return self::guiEmail($email, $hoTen, $subject, $body);
    }

    /**
     * Gửi email thông báo tài khoản bị khóa
     */
    public static function guiEmailThongBaoKhoa($email, $hoTen, $lyDo = null) {
        $subject = 'Thông báo tài khoản bị tạm khóa - Hệ thống Kho Hàng';
        
        $body = self::taoTemplateThongBaoKhoa($hoTen, $lyDo);
        
        return self::guiEmail($email, $hoTen, $subject, $body);
    }

    /**
     * Hàm gửi email chính
     */
    private static function guiEmail($email, $name, $subject, $body) {
        try {
            // Tạo instance PHPMailer
            $mail = new PHPMailer(true);

            // Cấu hình server
            $mail->isSMTP();
            $mail->Host = self::$smtp_config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = self::$smtp_config['username'];
            $mail->Password = self::$smtp_config['password'];
            $mail->SMTPSecure = self::$smtp_config['encryption'];
            $mail->Port = self::$smtp_config['port'];
            $mail->CharSet = 'UTF-8';

            // Người gửi
            $mail->setFrom(self::$smtp_config['from_email'], self::$smtp_config['from_name']);
            
            // Người nhận
            $mail->addAddress($email, $name);

            // Nội dung email
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            // Gửi email
            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Lỗi gửi email: " . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Tạo template HTML cho email OTP
     */
    private static function taoTemplateOTP($hoTen, $otp) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .otp-box { background: #f8f9fa; border: 2px dashed #667eea; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
                .otp-code { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 5px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏪 Hệ thống Kho Hàng</h1>
                    <p>Xác thực tài khoản của bạn</p>
                </div>
                <div class='content'>
                    <h2>Xin chào {$hoTen}!</h2>
                    <p>Cảm ơn bạn đã đăng ký tài khoản tại Hệ thống Kho Hàng. Để hoàn tất việc đăng ký, vui lòng sử dụng mã OTP dưới đây:</p>
                    
                    <div class='otp-box'>
                        <p>Mã OTP của bạn là:</p>
                        <div class='otp-code'>{$otp}</div>
                        <p><small>Mã này có hiệu lực trong 15 phút</small></p>
                    </div>
                    
                    <p><strong>Lưu ý quan trọng:</strong></p>
                    <ul>
                        <li>Không chia sẻ mã OTP này với bất kỳ ai</li>
                        <li>Mã OTP chỉ có hiệu lực trong 15 phút</li>
                        <li>Nếu bạn không yêu cầu đăng ký, vui lòng bỏ qua email này</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>© 2024 Hệ thống Kho Hàng. Tất cả quyền được bảo lưu.</p>
                    <p>Email này được gửi tự động, vui lòng không trả lời.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Tạo template HTML cho email reset password
     */
    private static function taoTemplateResetPassword($hoTen, $resetLink) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(45deg, #ff6b6b, #ee5a52); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .button { display: inline-block; background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 12px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0; color: #856404; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Đặt lại mật khẩu</h1>
                    <p>Hệ thống Kho Hàng</p>
                </div>
                <div class='content'>
                    <h2>Xin chào {$hoTen}!</h2>
                    <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Nếu đây là yêu cầu của bạn, vui lòng nhấp vào nút bên dưới:</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$resetLink}' class='button'>Đặt lại mật khẩu</a>
                    </div>
                    
                    <div class='warning'>
                        <strong>⚠️ Lưu ý bảo mật:</strong>
                        <ul>
                            <li>Link này chỉ có hiệu lực trong 1 giờ</li>
                            <li>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này</li>
                            <li>Không chia sẻ link này với bất kỳ ai</li>
                        </ul>
                    </div>
                    
                    <p>Nếu nút không hoạt động, bạn có thể copy và paste link sau vào trình duyệt:</p>
                    <p style='word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace;'>{$resetLink}</p>
                </div>
                <div class='footer'>
                    <p>© 2024 Hệ thống Kho Hàng. Tất cả quyền được bảo lưu.</p>
                    <p>Email này được gửi tự động, vui lòng không trả lời.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Tạo template HTML cho email thông báo khóa tài khoản
     */
    private static function taoTemplateThongBaoKhoa($hoTen, $lyDo) {
        $lyDoText = $lyDo ?: 'Vi phạm quy định sử dụng hệ thống';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(45deg, #dc3545, #c82333); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .alert { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 20px 0; color: #721c24; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚫 Thông báo khóa tài khoản</h1>
                    <p>Hệ thống Kho Hàng</p>
                </div>
                <div class='content'>
                    <h2>Xin chào {$hoTen}!</h2>
                    <p>Chúng tôi thông báo rằng tài khoản của bạn đã bị tạm thời khóa.</p>
                    
                    <div class='alert'>
                        <strong>Lý do khóa:</strong> {$lyDoText}
                    </div>
                    
                    <p><strong>Thông tin chi tiết:</strong></p>
                    <ul>
                        <li>Thời gian khóa: " . date('d/m/Y H:i:s') . "</li>
                        <li>Trạng thái: Tạm thời khóa</li>
                        <li>Liên hệ: Vui lòng liên hệ quản trị viên để được hỗ trợ</li>
                    </ul>
                    
                    <p>Để mở khóa tài khoản, vui lòng liên hệ với bộ phận hỗ trợ qua:</p>
                    <ul>
                        <li>Email: support@warehouse.com</li>
                        <li>Điện thoại: (84) 123-456-789</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>© 2024 Hệ thống Kho Hàng. Tất cả quyền được bảo lưu.</p>
                    <p>Email này được gửi tự động, vui lòng không trả lời.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Tạo link reset password
     */
    private static function taoLinkResetPassword($token) {
        $baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $baseUrl .= $_SERVER['HTTP_HOST'];
        $baseUrl .= dirname($_SERVER['PHP_SELF']);
        
        return $baseUrl . "/reset_password.php?token=" . urlencode($token);
    }

    /**
     * Validate email format
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Tạo OTP ngẫu nhiên
     */
    public static function taoOTP($length = 6) {
        return sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
    }

    /**
     * Tạo reset token
     */
    public static function taoResetToken() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Log email activity
     */
    public static function logEmailActivity($email, $type, $status, $message = null) {
        try {
            // Kết nối database
            global $conn;
            if (!$conn) {
                include_once 'connect.php';
            }

            $sql = "INSERT INTO email_logs (email, type, status, message, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $email, $type, $status, $message);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Lỗi log email: " . $e->getMessage());
        }
    }
}

/**
 * Hàm tiện ích để test cấu hình email
 */
function testEmailConfig() {
    $testEmail = "test@example.com";
    $testName = "Test User";
    $testOTP = EmailConfig::taoOTP();
    
    echo "Đang test cấu hình email...\n";
    echo "OTP test: " . $testOTP . "\n";
    
    if (EmailConfig::guiEmailOTP($testEmail, $testName, $testOTP)) {
        echo "✅ Gửi email thành công!\n";
    } else {
        echo "❌ Gửi email thất bại!\n";
    }
}

// Uncomment dòng dưới để test cấu hình email
// testEmailConfig();
?> 