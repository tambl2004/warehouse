<?php
/**
 * Controller quản lý người dùng
 * Xử lý logic và điều hướng cho chức năng quản lý người dùng
 */

// Bảo vệ không cho truy cập trực tiếp vào file
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__FILE__)) . '/');
}

// Nhúng file model
require_once ROOT_PATH . 'models/nguoidung_model.php';
require_once ROOT_PATH . 'inc/mail_helper.php';
require_once ROOT_PATH . 'inc/auth.php';

class NguoiDungController {
    public $model;
    private $pdo;
    private $error = null;
    private $success = null;
    private $warning = null;
    private $active_tab = 'users';
    private $roles = [];

    /**
     * Khởi tạo controller
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->model = new NguoiDungModel($pdo);
        
        // Kiểm tra đăng nhập và phân quyền
        if (!isAdmin()) {
            $this->error = "Bạn không có quyền truy cập chức năng này!";
            return;
        }

        // Lấy danh sách vai trò
        $this->roles = $this->model->layDanhSachVaiTro();

        // Xác định tab đang hiển thị
        if (isset($_GET['tab']) && in_array($_GET['tab'], ['users', 'roles', 'logs', 'system_logs'])) {
            $this->active_tab = $_GET['tab'];
        }
        
        // Xử lý các action
        $this->xuLyThemNguoiDung();
        $this->xuLyCapNhatNguoiDung();
        $this->xuLyXoaNguoiDung();
        $this->xuLyDatLaiMatKhau();
        $this->xuLyThemVaiTro();
        $this->xuLyCapNhatVaiTro();
        $this->xuLyXoaVaiTro();
        $this->xuLyCapNhatQuyenVaiTro();
        $this->xuLyKhoaTaiKhoan();
        $this->xuLyMoKhoaTaiKhoan();
    }

    /**
     * Lấy dữ liệu cần thiết để hiển thị
     */
    public function getData() {
        $data = [
            'error' => $this->error,
            'success' => $this->success,
            'warning' => $this->warning,
            'active_tab' => $this->active_tab,
            'roles' => $this->roles
        ];

        // Luôn tải dữ liệu người dùng cho tab users
        $data['users'] = $this->model->layDanhSachNguoiDung();
        
        // Luôn tải dữ liệu nhật ký cho tab logs
        $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
        $actionType = isset($_GET['action']) ? $_GET['action'] : null;
        $date = isset($_GET['date']) ? $_GET['date'] : null;
        $data['logs'] = $this->model->layNhatKyNguoiDung($userId, $actionType, $date);
        $data['log_users'] = $this->model->layDanhSachNguoiDungCoLog();
        $data['action_types'] = $this->model->layDanhSachLoaiHanhDong();
        
        // Luôn tải dữ liệu nhật ký hệ thống cho tab system_logs
        $data['is_admin'] = isAdmin();
        if ($data['is_admin']) {
            $level = isset($_GET['level']) ? $_GET['level'] : null;
            $source = isset($_GET['source']) ? $_GET['source'] : null;
            $date = isset($_GET['date']) ? $_GET['date'] : null;
            $data['system_logs'] = $this->model->layNhatKyHeThong($level, $source, $date);
        }

        return $data;
    }

    /**
     * Lấy tên trình duyệt từ chuỗi user-agent
     */
    public function getBrowserName($user_agent) {
        $user_agent = strtolower($user_agent);
        if (strpos($user_agent, 'firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($user_agent, 'chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($user_agent, 'safari') !== false) {
            return 'Safari';
        } elseif (strpos($user_agent, 'edge') !== false || strpos($user_agent, 'edg/') !== false) {
            return 'Edge';
        } elseif (strpos($user_agent, 'msie') !== false || strpos($user_agent, 'trident') !== false) {
            return 'Internet Explorer';
        } else {
            return 'Không xác định';
        }
    }

    /**
     * Xử lý thêm người dùng
     */
    private function xuLyThemNguoiDung() {
        if (!isset($_POST['add_user'])) return;

        $username = $_POST['username'];
        $password = $_POST['password'];
        $email = $_POST['email'];
        $fullName = $_POST['full_name'];
        $phone = $_POST['phone'] ?? '';
        $roleId = $_POST['role_id'];

        $result = $this->model->themNguoiDung($username, $password, $email, $fullName, $phone, $roleId);
        
        if ($result['success']) {
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'ADD_USER', "Thêm người dùng mới: $username");
            $this->success = $result['message'];
            
            // Tạo và gửi OTP (tùy chọn - có thể bỏ vì admin tạo)
            if (isset($_POST['send_otp']) && $_POST['send_otp'] == '1') {
                $otpResult = $this->model->taoOTP($email);
                if ($otpResult['success']) {
                    $subject = "Thông tin tài khoản mới";
                    $message = "
                    <p>Xin chào $fullName,</p>
                    <p>Tài khoản của bạn đã được tạo thành công trên hệ thống quản lý kho.</p>
                    <p>Thông tin đăng nhập của bạn:</p>
                    <p>Tên đăng nhập: $username</p>
                    <p>Mật khẩu tạm: $password</p>
                    <p>Vui lòng đổi mật khẩu sau khi đăng nhập lần đầu.</p>
                    <p>Trân trọng,<br>Hệ thống Quản lý Kho</p>";
                    
                    if (!sendMail($email, $subject, $message)) {
                        $this->warning = "Đã thêm người dùng nhưng không gửi được email thông báo!";
                    }
                }
            }
        } else {
            $this->error = $result['message'];
        }
    }

    /**
     * Xử lý cập nhật người dùng
     */
    private function xuLyCapNhatNguoiDung() {
        if (!isset($_POST['update_user'])) return;

        $userId = $_POST['user_id'];
        $fullName = $_POST['full_name'];
        $phone = $_POST['phone'] ?? '';
        $roleId = $_POST['role_id'];
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $result = $this->model->capNhatNguoiDung($userId, $fullName, $phone, $roleId, $isActive);
        
        if ($result['success']) {
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'UPDATE_USER', "Cập nhật người dùng ID: $userId");
            $this->success = $result['message'];
        } else {
            $this->error = $result['message'];
        }
    }

    /**
     * Xử lý xóa người dùng
     */
    private function xuLyXoaNguoiDung() {
        if (!isset($_POST['delete_user'])) return;

        $userId = $_POST['delete_id'];

        $result = $this->model->xoaNguoiDung($userId, $_SESSION['user_id']);
        
        if ($result['success']) {
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'DELETE_USER', "Xóa người dùng ID: $userId");
            $this->success = $result['message'];
        } else {
            $this->error = $result['message'];
        }
    }

    /**
     * Xử lý đặt lại mật khẩu
     */
    private function xuLyDatLaiMatKhau() {
        if (!isset($_POST['reset_password'])) return;

        $userId = $_POST['reset_id'];
        $email = $_POST['reset_email'];

        $result = $this->model->datLaiMatKhau($userId);
        
        if ($result['success']) {
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'RESET_PASSWORD', "Reset mật khẩu người dùng ID: $userId");
            
            // Gửi email mật khẩu mới
            $subject = "Mật khẩu của bạn đã được đặt lại";
            $message = "
            <p>Xin chào,</p>
            <p>Mật khẩu của bạn đã được đặt lại. Dưới đây là thông tin đăng nhập mới của bạn:</p>
            <p>Mật khẩu mới: <strong>{$result['new_password']}</strong></p>
            <p>Vui lòng đổi mật khẩu sau khi đăng nhập.</p>
            <p>Trân trọng,<br>Hệ thống Quản lý Kho</p>";
            
            if (sendMail($email, $subject, $message)) {
                $this->success = "Đặt lại mật khẩu thành công và đã gửi email thông báo!";
            } else {
                $this->warning = "Đặt lại mật khẩu thành công nhưng không gửi được email!";
            }
        } else {
            $this->error = $result['message'];
        }
    }

    /**
     * Xử lý thêm vai trò
     */
    private function xuLyThemVaiTro() {
        if (!isset($_POST['add_role'])) return;

        $roleName = $_POST['role_name'];
        $description = $_POST['description'] ?? '';

        $result = $this->model->themVaiTro($roleName, $description);
        
        if ($result['success']) {
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'ADD_ROLE', "Thêm vai trò mới: $roleName");
            $this->success = $result['message'];
            
            // Cập nhật lại danh sách vai trò
            $this->roles = $this->model->layDanhSachVaiTro();
        } else {
            $this->error = $result['message'];
        }
        
        // Chuyển đến tab roles
        $this->active_tab = 'roles';
    }

    /**
     * Xử lý cập nhật vai trò
     */
    private function xuLyCapNhatVaiTro() {
        if (!isset($_POST['update_role'])) return;

        $roleId = $_POST['role_id'];
        $roleName = $_POST['role_name'];
        $description = $_POST['description'] ?? '';

        $result = $this->model->capNhatVaiTro($roleId, $roleName, $description);
        
        if ($result['success']) {
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'UPDATE_ROLE', "Cập nhật vai trò ID: $roleId");
            $this->success = $result['message'];
            
            // Cập nhật lại danh sách vai trò
            $this->roles = $this->model->layDanhSachVaiTro();
        } else {
            $this->error = $result['message'];
        }
        
        // Chuyển đến tab roles
        $this->active_tab = 'roles';
    }

    /**
     * Xử lý xóa vai trò
     */
    private function xuLyXoaVaiTro() {
        if (!isset($_POST['delete_role'])) return;

        $roleId = $_POST['delete_role_id'];

        $result = $this->model->xoaVaiTro($roleId);
        
        if ($result['success']) {
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'DELETE_ROLE', "Xóa vai trò ID: $roleId");
            $this->success = $result['message'];
            
            // Cập nhật lại danh sách vai trò
            $this->roles = $this->model->layDanhSachVaiTro();
        } else {
            $this->error = $result['message'];
        }
        
        // Chuyển đến tab roles
        $this->active_tab = 'roles';
    }

    /**
     * Cập nhật quyền cho vai trò
     */
    private function xuLyCapNhatQuyenVaiTro() {
        if (!isset($_POST['save_permissions'])) return;

        $roleId = $_POST['permission_role_id'];
        $permissions = [];
        
        // Lấy danh sách quyền được chọn
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'perm_') === 0) {
                $permission = substr($key, 5); // Bỏ "perm_" ở đầu
                $permissions[] = $permission;
            }
        }

        $result = $this->model->capNhatQuyenVaiTro($roleId, $permissions);
        
        // Kiểm tra nếu đây là một request Ajax
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }
        
        // Xử lý bình thường khi không phải Ajax request
        if ($result['success']) {
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'UPDATE_PERMISSION', "Cập nhật quyền cho vai trò ID: $roleId");
            $this->success = $result['message'];
        } else {
            $this->error = $result['message'];
        }
        
        // Duy trì tab hiện tại là roles
        $this->active_tab = 'roles';
    }

    /**
     * Xử lý khóa tài khoản
     */
    private function xuLyKhoaTaiKhoan() {
        if (!isset($_POST['lock_account'])) return;

        $userId = $_POST['user_id'];

        $result = $this->model->khoaTaiKhoan($userId, $_SESSION['user_id']);
        
        if ($result['success']) {
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'LOCK_ACCOUNT', "Khóa tài khoản người dùng ID: $userId");
            
            // Lấy email của người dùng bị khóa
            $stmt = $this->pdo->prepare("SELECT email, username FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Gửi email thông báo
                $subject = "Tài khoản của bạn đã bị khóa";
                $message = "
                <p>Xin chào {$user['username']},</p>
                <p>Tài khoản của bạn đã bị khóa trên hệ thống.</p>
                <p>Vui lòng liên hệ với quản trị viên để biết thêm chi tiết.</p>
                <p>Trân trọng,<br>Hệ thống Quản lý Kho</p>";
                
                if (sendMail($user['email'], $subject, $message)) {
                    $this->success = $result['message'] . " Đã gửi email thông báo.";
                } else {
                    $this->success = $result['message'] . " Không gửi được email thông báo.";
                }
            } else {
                $this->success = $result['message'];
            }
        } else {
            $this->error = $result['message'];
        }
    }

    /**
     * Xử lý mở khóa tài khoản
     */
    private function xuLyMoKhoaTaiKhoan() {
        if (!isset($_POST['unlock_account'])) return;

        $userId = $_POST['user_id'];

        $result = $this->model->moKhoaTaiKhoan($userId);
        
        if ($result['success']) {
            // Ghi log hoạt động
            logUserActivity($_SESSION['user_id'], 'UNLOCK_ACCOUNT', "Mở khóa tài khoản người dùng ID: $userId");
            
            // Lấy email của người dùng được mở khóa
            $stmt = $this->pdo->prepare("SELECT email, username FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Gửi email thông báo
                $subject = "Tài khoản của bạn đã được mở khóa";
                $message = "
                <p>Xin chào {$user['username']},</p>
                <p>Tài khoản của bạn đã được mở khóa trên hệ thống.</p>
                <p>Bạn có thể đăng nhập lại bình thường.</p>
                <p>Trân trọng,<br>Hệ thống Quản lý Kho</p>";
                
                if (sendMail($user['email'], $subject, $message)) {
                    $this->success = $result['message'] . " Đã gửi email thông báo.";
                } else {
                    $this->success = $result['message'] . " Không gửi được email thông báo.";
                }
            } else {
                $this->success = $result['message'];
            }
        } else {
            $this->error = $result['message'];
        }
    }
} 