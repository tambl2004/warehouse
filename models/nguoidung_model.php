<?php
require_once 'config/connect.php';
require_once 'inc/auth.php';
require_once 'inc/mail_helper.php';

class NguoiDungModel {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Lấy danh sách người dùng với phân trang và tìm kiếm
     */
    public function getDanhSachNguoiDung($page = 1, $limit = 10, $search = '', $role = '', $status = '') {
        $offset = ($page - 1) * $limit;
        $params = [];
        
        $sql = "SELECT u.*, 
                       DATE_FORMAT(u.created_at, '%d/%m/%Y %H:%i') as ngay_tao,
                       DATE_FORMAT(u.last_login, '%d/%m/%Y %H:%i') as lan_dang_nhap_cuoi,
                       CASE 
                           WHEN u.role = 'admin' THEN 'Quản trị viên'
                           WHEN u.role = 'employee' THEN 'Nhân viên' 
                           ELSE 'Người dùng'
                       END as ten_vai_tro
                FROM users u WHERE 1=1";
        
        if (!empty($search)) {
            $sql .= " AND (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
        }
        
        if (!empty($role)) {
            $sql .= " AND u.role = ?";
            $params[] = $role;
        }

        // Thêm bộ lọc trạng thái
        if (!empty($status)) {
            if ($status == 'active') {
                $sql .= " AND u.is_active = 1 AND u.is_locked = 0";
            } elseif ($status == 'locked') {
                $sql .= " AND u.is_locked = 1";
            } elseif ($status == 'inactive') {
                $sql .= " AND u.is_active = 0";
            }
        }
        
        $sql .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Đếm tổng số người dùng
     */
    public function getTongSoNguoiDung($search = '', $role = '', $status = '') {
        $params = [];
        $sql = "SELECT COUNT(*) FROM users WHERE 1=1";
        
        if (!empty($search)) {
            $sql .= " AND (username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
            $searchParam = "%{$search}%";
            $params = [$searchParam, $searchParam, $searchParam];
        }
        
        if (!empty($role)) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }

        // Thêm bộ lọc trạng thái
        if (!empty($status)) {
            if ($status == 'active') {
                $sql .= " AND is_active = 1 AND is_locked = 0";
            } elseif ($status == 'locked') {
                $sql .= " AND is_locked = 1";
            } elseif ($status == 'inactive') {
                $sql .= " AND is_active = 0";
            }
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Lấy thông tin người dùng theo ID
     */
    public function getNguoiDungById($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Tạo người dùng mới
     */
    public function taoNguoiDung($data) {
        try {
            // Kiểm tra username và email đã tồn tại
            if ($this->kiemTraTonTai($data['username'], $data['email'])) {
                return ['success' => false, 'message' => 'Tên đăng nhập hoặc email đã tồn tại!'];
            }
            
            // Mã hóa mật khẩu
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Tạo OTP nếu cần xác thực
            $otp = rand(100000, 999999);
            $otpExpiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            $sql = "INSERT INTO users (username, full_name, password_hash, email, role, is_active, otp, otp_expiry) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['username'],
                $data['full_name'],
                $passwordHash,
                $data['email'],
                $data['role'],
                isset($data['is_active']) ? $data['is_active'] : 1,
                $otp,
                $otpExpiry
            ]);
            
            if ($result) {
                $userId = $this->pdo->lastInsertId();
                
                // Gửi email OTP nếu cần
                if (isset($data['send_otp']) && $data['send_otp']) {
                    $this->guiOTPEmail($data['email'], $otp, $data['full_name']);
                }
                
                // Ghi log
                logUserActivity($_SESSION['user_id'], 'CREATE_USER', 
                    "Tạo tài khoản mới: {$data['username']} (ID: {$userId})");
                
                return ['success' => true, 'message' => 'Tạo tài khoản thành công!', 'user_id' => $userId];
            }
            
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi tạo tài khoản!'];
            
        } catch (PDOException $e) {
            error_log("Lỗi tạo người dùng: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống!'];
        }
    }
    
    /**
     * Cập nhật thông tin người dùng
     */
    public function capNhatNguoiDung($userId, $data) {
        try {
            // Kiểm tra username và email đã tồn tại (trừ user hiện tại)
            if ($this->kiemTraTonTai($data['username'], $data['email'], $userId)) {
                return ['success' => false, 'message' => 'Tên đăng nhập hoặc email đã tồn tại!'];
            }
            
            $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, 
                    updated_at = CURRENT_TIMESTAMP WHERE user_id = ?";
            
            $params = [
                $data['username'],
                $data['full_name'],
                $data['email'],
                $data['role'],
                $userId
            ];
            
            // Cập nhật mật khẩu nếu có
            if (!empty($data['password'])) {
                $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, 
                        password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?";
                $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
                $params = [
                    $data['username'],
                    $data['full_name'],
                    $data['email'],
                    $data['role'],
                    $passwordHash,
                    $userId
                ];
            }
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                // Ghi log
                logUserActivity($_SESSION['user_id'], 'UPDATE_USER', 
                    "Cập nhật tài khoản: {$data['username']} (ID: {$userId})");
                
                return ['success' => true, 'message' => 'Cập nhật thành công!'];
            }
            
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật!'];
            
        } catch (PDOException $e) {
            error_log("Lỗi cập nhật người dùng: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống!'];
        }
    }
    
    /**
     * Ngưng hoạt động tài khoản (thay vì xóa)
     */
    public function ngungHoatDongNguoiDung($userId) {
        try {
            // Không cho phép ngưng hoạt động admin cuối cùng
            if ($this->isAdminCuoiCung($userId)) {
                return ['success' => false, 'message' => 'Không thể ngưng hoạt động admin cuối cùng!'];
            }
            
            // Lấy thông tin user trước khi ngưng hoạt động
            $user = $this->getNguoiDungById($userId);
            
            $stmt = $this->pdo->prepare("UPDATE users SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
            $result = $stmt->execute([$userId]);
            
            if ($result) {
                // Ghi log
                logUserActivity($_SESSION['user_id'], 'DEACTIVATE_USER', 
                    "Ngưng hoạt động tài khoản: {$user['username']} (ID: {$userId})");
                
                return ['success' => true, 'message' => 'Ngưng hoạt động tài khoản thành công!'];
            }
            
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi ngưng hoạt động!'];
            
        } catch (PDOException $e) {
            error_log("Lỗi ngưng hoạt động người dùng: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống!'];
        }
    }

    /**
     * Kích hoạt lại tài khoản
     */
    public function kichHoatNguoiDung($userId) {
        try {
            $user = $this->getNguoiDungById($userId);
            
            $stmt = $this->pdo->prepare("UPDATE users SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
            $result = $stmt->execute([$userId]);
            
            if ($result) {
                // Ghi log
                logUserActivity($_SESSION['user_id'], 'ACTIVATE_USER', 
                    "Kích hoạt lại tài khoản: {$user['username']} (ID: {$userId})");
                
                return ['success' => true, 'message' => 'Kích hoạt tài khoản thành công!'];
            }
            
            return ['success' => false, 'message' => 'Có lỗi xảy ra!'];
            
        } catch (PDOException $e) {
            error_log("Lỗi kích hoạt người dùng: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống!'];
        }
    }
    
    /**
     * Khóa/Mở khóa tài khoản với gửi email thông báo
     */
    public function thayDoiTrangThaiKhoa($userId, $isLocked) {
        try {
            $user = $this->getNguoiDungById($userId);
            
            $stmt = $this->pdo->prepare("UPDATE users SET is_locked = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
            $result = $stmt->execute([$isLocked, $userId]);
            
            if ($result) {
                $action = $isLocked ? 'LOCK_USER' : 'UNLOCK_USER';
                $message = $isLocked ? 'khóa' : 'mở khóa';
                
                // Gửi email thông báo
                if ($isLocked) {
                    $this->guiEmailThongBaoKhoa($user['email'], $user['full_name']);
                } else {
                    $this->guiEmailThongBaoMoKhoa($user['email'], $user['full_name']);
                }
                
                // Ghi log
                logUserActivity($_SESSION['user_id'], $action, 
                    "Đã {$message} tài khoản: {$user['username']} (ID: {$userId})");
                
                return ['success' => true, 'message' => "Đã {$message} tài khoản thành công và gửi email thông báo!"];
            }
            
            return ['success' => false, 'message' => 'Có lỗi xảy ra!'];
            
        } catch (PDOException $e) {
            error_log("Lỗi thay đổi trạng thái khóa: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống!'];
        }
    }
    
    /**
     * Reset mật khẩu người dùng
     */
    public function resetMatKhau($userId) {
        try {
            $user = $this->getNguoiDungById($userId);
            
            // Tạo token reset
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Lưu token vào database
            $stmt = $this->pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expiry_time) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $token, $expiry]);
            
            // Gửi email reset
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/warehouse/reset_password.php?token=" . $token;
            
            if ($this->guiEmailResetPassword($user['email'], $resetLink, $user['full_name'])) {
                // Ghi log
                logUserActivity($_SESSION['user_id'], 'RESET_PASSWORD_INIT', 
                    "Khởi tạo reset mật khẩu cho: {$user['username']} (ID: {$userId})");
                
                return ['success' => true, 'message' => 'Đã gửi link reset mật khẩu qua email!'];
            }
            
            return ['success' => false, 'message' => 'Có lỗi khi gửi email!'];
            
        } catch (PDOException $e) {
            error_log("Lỗi reset mật khẩu: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống!'];
        }
    }
    
    /**
     * Lấy log hoạt động của người dùng
     */
    public function getLogHoatDong($userId = null, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $params = [$limit, $offset];
        
        $sql = "SELECT ul.*, u.username, u.full_name,
                       DATE_FORMAT(ul.action_time, '%d/%m/%Y %H:%i:%s') as thoi_gian_format
                FROM user_logs ul 
                LEFT JOIN users u ON ul.user_id = u.user_id";
        
        if ($userId) {
            $sql .= " WHERE ul.user_id = ?";
            array_unshift($params, $userId);
        }
        
        $sql .= " ORDER BY ul.action_time DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy thống kê người dùng
     */
    public function getThongKeNguoiDung() {
        $sql = "SELECT 
                    COUNT(*) as tong_so,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin,
                    SUM(CASE WHEN role = 'employee' THEN 1 ELSE 0 END) as nhan_vien,
                    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as nguoi_dung,
                    SUM(CASE WHEN is_active = 1 AND is_locked = 0 THEN 1 ELSE 0 END) as dang_hoat_dong,
                    SUM(CASE WHEN is_locked = 1 THEN 1 ELSE 0 END) as bi_khoa,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as ngung_hoat_dong,
                    SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as hoat_dong_gan_day
                FROM users";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách vai trò
     */
    public function getDanhSachVaiTro() {
        $sql = "SELECT DISTINCT role as id, 
                       CASE 
                           WHEN role = 'admin' THEN 'Quản trị viên'
                           WHEN role = 'employee' THEN 'Nhân viên' 
                           ELSE 'Người dùng'
                       END as name,
                       role
                FROM users 
                ORDER BY FIELD(role, 'admin', 'employee', 'user')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy quyền theo vai trò
     */
    public function getQuyenTheoVaiTro($role) {
        $stmt = $this->pdo->prepare("SELECT permission_key FROM role_permissions WHERE role = ?");
        $stmt->execute([$role]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Cập nhật quyền cho vai trò
     */
    public function capNhatQuyenVaiTro($role, $permissions) {
        try {
            $this->pdo->beginTransaction();
            
            // Xóa quyền cũ
            $stmt = $this->pdo->prepare("DELETE FROM role_permissions WHERE role = ?");
            $stmt->execute([$role]);
            
            // Thêm quyền mới
            if (!empty($permissions)) {
                $sql = "INSERT INTO role_permissions (role, permission_key) VALUES (?, ?)";
                $stmt = $this->pdo->prepare($sql);
                
                foreach ($permissions as $permission) {
                    $stmt->execute([$role, $permission]);
                }
            }
            
            $this->pdo->commit();
            
            // Ghi log
            logUserActivity($_SESSION['user_id'], 'UPDATE_ROLE_PERMISSIONS', 
                "Cập nhật quyền cho vai trò: {$role}");
            
            return ['success' => true, 'message' => 'Cập nhật quyền thành công!'];
            
        } catch (PDOException $e) {
            $this->pdo->rollback();
            error_log("Lỗi cập nhật quyền: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống!'];
        }
    }
    
    // Helper methods
    
    private function kiemTraTonTai($username, $email, $excludeUserId = null) {
        $sql = "SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?)";
        $params = [$username, $email];
        
        if ($excludeUserId) {
            $sql .= " AND user_id != ?";
            $params[] = $excludeUserId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }
    
    private function isAdminCuoiCung($userId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND user_id != ? AND is_active = 1");
        $stmt->execute([$userId]);
        
        return $stmt->fetchColumn() == 0;
    }
    
    private function guiOTPEmail($email, $otp, $fullName) {
        $subject = "Mã OTP kích hoạt tài khoản";
        $message = "
            <h3>Xin chào {$fullName},</h3>
            <p>Mã OTP để kích hoạt tài khoản của bạn là: <strong>{$otp}</strong></p>
            <p>Mã này có hiệu lực trong 15 phút.</p>
            <p>Trân trọng,<br>Hệ thống Quản lý Kho</p>
        ";
        
        return sendMail($email, $subject, $message);
    }
    
    private function guiEmailResetPassword($email, $resetLink, $fullName) {
        $subject = "Reset mật khẩu tài khoản";
        $message = "
            <h3>Xin chào {$fullName},</h3>
            <p>Bạn đã yêu cầu reset mật khẩu. Vui lòng click vào link sau để reset:</p>
            <p><a href='{$resetLink}'>Reset mật khẩu</a></p>
            <p>Link này có hiệu lực trong 1 giờ.</p>
            <p>Nếu bạn không yêu cầu reset mật khẩu, vui lòng bỏ qua email này.</p>
            <p>Trân trọng,<br>Hệ thống Quản lý Kho</p>
        ";
        
        return sendMail($email, $subject, $message);
    }

    private function guiEmailThongBaoKhoa($email, $fullName) {
        $subject = "Thông báo tài khoản bị khóa";
        $message = "
            <h3>Xin chào {$fullName},</h3>
            <p>Tài khoản của bạn đã bị khóa bởi quản trị viên.</p>
            <p>Vui lòng liên hệ với quản trị viên để biết thêm chi tiết.</p>
            <p>Trân trọng,<br>Hệ thống Quản lý Kho</p>
        ";
        
        return sendMail($email, $subject, $message);
    }

    private function guiEmailThongBaoMoKhoa($email, $fullName) {
        $subject = "Thông báo tài khoản được mở khóa";
        $message = "
            <h3>Xin chào {$fullName},</h3>
            <p>Tài khoản của bạn đã được mở khóa bởi quản trị viên.</p>
            <p>Bây giờ bạn có thể đăng nhập lại bình thường.</p>
            <p>Trân trọng,<br>Hệ thống Quản lý Kho</p>
        ";
        
        return sendMail($email, $subject, $message);
    }
}
?>
