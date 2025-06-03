<?php
require_once 'models/nguoidung_model.php';

// Khởi tạo model
$nguoiDungModel = new NguoiDungModel();

// Xử lý các action
$action = $_GET['action'] ?? 'list';
$message = '';
$messageType = '';

// Xử lý POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    switch ($_POST['action']) {
        case 'create':
            $result = $nguoiDungModel->taoNguoiDung($_POST);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            break;
            
        case 'update':
            $result = $nguoiDungModel->capNhatNguoiDung($_POST['user_id'], $_POST);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            break;
            
        case 'deactivate':
            $result = $nguoiDungModel->ngungHoatDongNguoiDung($_POST['user_id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            break;

        case 'activate':
            $result = $nguoiDungModel->kichHoatNguoiDung($_POST['user_id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            break;
            
        case 'toggle_lock':
            $result = $nguoiDungModel->thayDoiTrangThaiKhoa($_POST['user_id'], $_POST['is_locked']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            break;
            
        case 'reset_password':
            $result = $nguoiDungModel->resetMatKhau($_POST['user_id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            break;

        case 'update_permissions':
            $permissions = [];
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'perm_') === 0) {
                    $permissions[] = substr($key, 5);
                }
            }
            $result = $nguoiDungModel->capNhatQuyenVaiTro($_POST['role'], $permissions);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            $action = 'permissions';
            break;
    }
}

// Lấy dữ liệu cho danh sách
$page = $_GET['page'] ?? 1;
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$limit = 10;

$danhSachNguoiDung = $nguoiDungModel->getDanhSachNguoiDung($page, $limit, $search, $roleFilter, $statusFilter);
$tongSo = $nguoiDungModel->getTongSoNguoiDung($search, $roleFilter, $statusFilter);
$tongSoTrang = ceil($tongSo / $limit);

// Lấy thống kê
$thongKe = $nguoiDungModel->getThongKeNguoiDung();

// Lấy log hoạt động nếu cần
if ($action == 'logs') {
    $logUserId = $_GET['user_id'] ?? null;
    $logPage = $_GET['log_page'] ?? 1;
    $logs = $nguoiDungModel->getLogHoatDong($logUserId, $logPage);
}

// Lấy dữ liệu phân quyền
if ($action == 'permissions') {
    $danhSachVaiTro = $nguoiDungModel->getDanhSachVaiTro();
    $selectedRole = $_GET['role'] ?? 'admin';
    $quyenHienTai = $nguoiDungModel->getQuyenTheoVaiTro($selectedRole);
}

// Tất cả quyền có sẵn
$allPermissions = [
    'view_all' => 'Xem tất cả',
    'edit_all' => 'Chỉnh sửa tất cả', 
    'delete_all' => 'Xóa tất cả',
    'manage_users' => 'Quản lý người dùng',
    'view_reports' => 'Xem báo cáo',
    'manage_inventory' => 'Quản lý kho',
    'manage_import' => 'Quản lý nhập kho',
    'manage_export' => 'Quản lý xuất kho',
    'view_products' => 'Xem sản phẩm',
    'edit_products' => 'Chỉnh sửa sản phẩm',
    'view_inventory' => 'Xem tồn kho'
];
?>

<div class="function-container">
    <!-- Header và thống kê -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="page-title">
                     Quản lý người dùng
                </h2>
                <button class="btn btn btn-add" data-bs-toggle="modal" data-bs-target="#themNguoiDungModal">
                    <i class="fas fa-plus"></i> Thêm người dùng
                </button>
            </div>
            
            <!-- Thống kê nhanh -->
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h4 class="mb-0"><?php echo $thongKe['tong_so']; ?></h4>
                                    <p class="mb-0">Tổng người dùng</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h4 class="mb-0"><?php echo $thongKe['dang_hoat_dong']; ?></h4>
                                    <p class="mb-0">Đang hoạt động</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h4 class="mb-0"><?php echo $thongKe['bi_khoa']; ?></h4>
                                    <p class="mb-0">Bị khóa</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-lock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h4 class="mb-0"><?php echo $thongKe['ngung_hoat_dong']; ?></h4>
                                    <p class="mb-0">Ngưng hoạt động</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-user-times fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thông báo -->
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Tabs điều hướng -->
    <ul class="nav nav-tabs mb-4" id="userTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $action == 'list' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?option=nguoidung&action=list'">
                <i class="fas fa-list"></i> Danh sách người dùng
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $action == 'permissions' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?option=nguoidung&action=permissions'">
                <i class="fas fa-user-shield"></i> Phân quyền
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $action == 'logs' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?option=nguoidung&action=logs'">
                <i class="fas fa-history"></i> Log hoạt động
            </button>
        </li>
    </ul>

    <!-- Tab content -->
    <div class="tab-content">
        <?php if ($action == 'list'): ?>
        <!-- Tab danh sách người dùng -->
        <div class="tab-pane fade show active">
            <!-- Bộ lọc và tìm kiếm -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="option" value="nguoidung">
                        <input type="hidden" name="action" value="list">
                        
                        <div class="col-md-4">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" class="form-control" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Tên đăng nhập, họ tên, email...">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Vai trò</label>
                            <select name="role" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="admin" <?php echo $roleFilter == 'admin' ? 'selected' : ''; ?>>Quản trị viên</option>
                                <option value="employee" <?php echo $roleFilter == 'employee' ? 'selected' : ''; ?>>Nhân viên</option>
                                <option value="user" <?php echo $roleFilter == 'user' ? 'selected' : ''; ?>>Người dùng</option>
                            </select>
                        </div>
                        
                        <!-- Bộ lọc trạng thái -->
                        <div class="col-md-2">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="active" <?php echo $statusFilter == 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                                <option value="locked" <?php echo $statusFilter == 'locked' ? 'selected' : ''; ?>>Bị khóa</option>
                                <option value="inactive" <?php echo $statusFilter == 'inactive' ? 'selected' : ''; ?>>Ngưng hoạt động</option>
                            </select>
                        </div>
            
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Tìm kiếm
                            </button>
                            <a href="?option=nguoidung&action=list" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Xóa bộ lọc
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bảng danh sách -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Tên đăng nhập</th>
                                    <th>Họ và tên</th>
                                    <th>Email</th>
                                    <th>Vai trò</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($danhSachNguoiDung)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-users fa-3x mb-3"></i>
                                            <p>Không có dữ liệu người dùng</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($danhSachNguoiDung as $user): ?>
                                <tr>
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $user['role'] == 'admin' ? 'danger' : 
                                                ($user['role'] == 'employee' ? 'warning' : 'info'); 
                                        ?>">
                                            <?php echo $user['ten_vai_tro']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!$user['is_active']): ?>
                                            <span class="badge bg-secondary">
                                                Ngưng hoạt động
                                            </span>
                                        <?php elseif ($user['is_locked']): ?>
                                            <span class="badge bg-danger">
                                                Bị khóa
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                Hoạt động
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <div class="action-buttons" role="group">
                                            <button type="button" class="btn btn-edit" 
                                                    onclick="suaNguoiDung(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                                    title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <!-- Hiển thị button khóa/mở khóa chỉ với tài khoản active -->
                                            <?php if ($user['is_active']): ?>
                                            <button type="button" class="btn btn-<?php echo $user['is_locked'] ? 'success' : 'secondary'; ?>" 
                                                    onclick="toggleKhoa(<?php echo $user['user_id']; ?>, <?php echo $user['is_locked'] ? 'false' : 'true'; ?>)"
                                                    title="<?php echo $user['is_locked'] ? 'Mở khóa' : 'Khóa'; ?>">
                                                <i class="fas fa-<?php echo $user['is_locked'] ? 'unlock' : 'lock'; ?>"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <button type="button" class="btn btn-warning" 
                                                    onclick="resetMatKhau(<?php echo $user['user_id']; ?>)"
                                                    title="Reset mật khẩu">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            
                                            <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                <?php if ($user['is_active']): ?>
                                                <!-- Nút ngưng hoạt động -->
                                                <button type="button" class="btn btn-delete" 
                                                        onclick="ngungHoatDong(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                                        title="Ngưng hoạt động">
                                                    <i class="fas fa-user-times"></i>
                                                </button>
                                                <?php else: ?>
                                                <!-- Nút kích hoạt lại -->
                                                <button type="button" class="btn btn-success" 
                                                        onclick="kichHoatLai(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                                        title="Kích hoạt lại">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Phân trang -->
                    <?php if ($tongSoTrang > 1): ?>
                    <nav aria-label="Phân trang người dùng" class="mt-3">
                        <ul class="pagination justify-content-center">
                            <!-- Nút Previous -->
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?option=nguoidung&action=list&page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php endif; ?>

                            <!-- Các số trang -->
                            <?php
                            $start = max(1, $page - 2);
                            $end = min($tongSoTrang, $page + 2);
                            
                            if ($start > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?option=nguoidung&action=list&page=1&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">1</a>
                                </li>
                                <?php if ($start > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?option=nguoidung&action=list&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>

                            <?php if ($end < $tongSoTrang): ?>
                                <?php if ($end < $tongSoTrang - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?option=nguoidung&action=list&page=<?php echo $tongSoTrang; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $tongSoTrang; ?></a>
                                </li>
                            <?php endif; ?>

                            <!-- Nút Next -->
                            <?php if ($page < $tongSoTrang): ?>
                            <li class="page-item">
                                <a class="page-link" href="?option=nguoidung&action=list&page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>

                    <!-- Thông tin phân trang -->
                    <div class="text-center text-muted">
                        Hiển thị <?php echo (($page-1) * $limit + 1); ?> - <?php echo min($page * $limit, $tongSo); ?> 
                        trong tổng số <?php echo $tongSo; ?> người dùng
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php elseif ($action == 'permissions'): ?>
        <!-- Tab phân quyền -->
        <div class="tab-pane fade show active">
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user-tag"></i> Vai trò
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($danhSachVaiTro as $vaiTro): ?>
                                <a href="?option=nguoidung&action=permissions&role=<?php echo $vaiTro['role']; ?>" 
                                   class="list-group-item list-group-item-action <?php echo $selectedRole == $vaiTro['role'] ? 'active' : ''; ?>">
                                    <i class="fas fa-<?php echo $vaiTro['role'] == 'admin' ? 'user-shield' : ($vaiTro['role'] == 'employee' ? 'user-tie' : 'user'); ?>"></i>
                                    <?php echo $vaiTro['name']; ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-key"></i> Quyền của vai trò: 
                                <span class="text-primary">
                                    <?php 
                                    $currentRole = array_filter($danhSachVaiTro, function($r) use ($selectedRole) {
                                        return $r['role'] == $selectedRole;
                                    });
                                    echo !empty($currentRole) ? array_values($currentRole)[0]['name'] : $selectedRole;
                                    ?>
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_permissions">
                                <input type="hidden" name="role" value="<?php echo $selectedRole; ?>">
                                
                                <div class="row">
                                    <?php foreach ($allPermissions as $key => $label): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="perm_<?php echo $key; ?>" 
                                                   id="perm_<?php echo $key; ?>"
                                                   <?php echo in_array($key, $quyenHienTai) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_<?php echo $key; ?>">
                                                <strong><?php echo $label; ?></strong>
                                                <br><small class="text-muted"><?php echo $key; ?></small>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Lưu quyền
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                        <i class="fas fa-undo"></i> Hủy bỏ
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php elseif ($action == 'logs'): ?>
        <!-- Tab log hoạt động -->
        <div class="tab-pane fade show active">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> Lịch sử hoạt động người dùng
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Thời gian</th>
                                    <th>Người dùng</th>
                                    <th>Hành động</th>
                                    <th>Mô tả</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-history fa-3x mb-3"></i>
                                            <p>Chưa có log hoạt động</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['thoi_gian_format']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($log['username'] ?? 'Hệ thống'); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($log['full_name'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo strpos($log['action'], 'LOGIN') !== false ? 'success' : 
                                                (strpos($log['action'], 'DELETE') !== false ? 'danger' : 'primary'); 
                                        ?>">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['description'] ?? ''); ?></td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal thêm người dùng -->
<div class="modal fade" id="themNguoiDungModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i> Thêm người dùng mới
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formThemNguoiDung">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Vai trò <span class="text-danger">*</span></label>
                                <select class="form-select" name="role" required>
                                    <option value="">Chọn vai trò</option>
                                    <option value="user">Người dùng</option>
                                    <option value="employee">Nhân viên</option>
                                    <option value="admin">Quản trị viên</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" checked>
                            <label class="form-check-label">Kích hoạt tài khoản ngay</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="send_otp" value="1">
                            <label class="form-check-label">Gửi OTP kích hoạt qua email</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Tạo tài khoản
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal sửa người dùng -->
<div class="modal fade" id="suaNguoiDungModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit"></i> Sửa thông tin người dùng
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formSuaNguoiDung">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="username" id="edit_username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" id="edit_email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Vai trò <span class="text-danger">*</span></label>
                                <select class="form-select" name="role" id="edit_role" required>
                                    <option value="user">Người dùng</option>
                                    <option value="employee">Nhân viên</option>
                                    <option value="admin">Quản trị viên</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu mới (để trống nếu không đổi)</label>
                        <input type="password" class="form-control" name="password">
                        <div class="form-text">Chỉ nhập nếu muốn thay đổi mật khẩu</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Xử lý form thêm người dùng
document.getElementById('formThemNguoiDung').addEventListener('submit', function(e) {
    const password = this.querySelector('input[name="password"]').value;
    const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Mật khẩu xác nhận không khớp!');
        return false;
    }
});

// Hàm sửa người dùng
function suaNguoiDung(user) {
    document.getElementById('edit_user_id').value = user.user_id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_full_name').value = user.full_name || '';
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    
    new bootstrap.Modal(document.getElementById('suaNguoiDungModal')).show();
}

// Hàm khóa/mở khóa tài khoản
function toggleKhoa(userId, isLocked) {
    const action = isLocked ? 'khóa' : 'mở khóa';
    if (confirm(`Bạn có chắc chắn muốn ${action} tài khoản này?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        form.innerHTML = `
            <input type="hidden" name="action" value="toggle_lock">
            <input type="hidden" name="user_id" value="${userId}">
            <input type="hidden" name="is_locked" value="${isLocked ? '1' : '0'}">
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Hàm ngưng hoạt động tài khoản
function ngungHoatDong(userId, username) {
    if (confirm(`Bạn có chắc chắn muốn ngưng hoạt động tài khoản "${username}"?\nTài khoản sẽ không thể đăng nhập cho đến khi được kích hoạt lại.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        form.innerHTML = `
            <input type="hidden" name="action" value="deactivate">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Hàm kích hoạt lại tài khoản
function kichHoatLai(userId, username) {
    if (confirm(`Bạn có chắc chắn muốn kích hoạt lại tài khoản "${username}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        form.innerHTML = `
            <input type="hidden" name="action" value="activate">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Hàm reset mật khẩu
function resetMatKhau(userId) {
    if (confirm('Bạn có chắc chắn muốn gửi email reset mật khẩu cho người dùng này?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        form.innerHTML = `
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-hide alerts
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        if (alert.classList.contains('show')) {
            new bootstrap.Alert(alert).close();
        }
    });
}, 5000);
</script>
