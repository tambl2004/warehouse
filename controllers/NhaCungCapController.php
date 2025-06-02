<?php
// File: controllers/NhaCungCapController.php
require_once 'models/NhaCungCapModel.php'; // Sửa tên file model cho đúng
require_once 'inc/security.php';

class NhaCungCapController {
    private $model;

    public function __construct() {
        $this->model = new NhaCungCapModel();
    }

    public function xuLyRequest() {
        // Controller này chỉ chuẩn bị dữ liệu cho view list
        return $this->danhSachNhaCungCap();
    }

    private function danhSachNhaCungCap() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = cleanInput($_GET['search'] ?? '');
        $status = cleanInput($_GET['status'] ?? '');
        $limit = 10;

        $suppliers = $this->model->layDanhSachNhaCungCap($page, $limit, $search, $status);
        $total_suppliers = $this->model->demTongSoNhaCungCap($search, $status);
        $total_pages = ceil($total_suppliers / $limit);

        return [
            'suppliers' => $suppliers,
            'current_page' => $page,
            'total_pages' => $total_pages,
            'search' => $search,
            'selected_status' => $status
        ];
    }
    
    // Hàm hiển thị thông báo (có thể dùng chung)
    public function hienThiThongBao() {
        if (isset($_SESSION['notification'])) {
            $notification = $_SESSION['notification'];
            unset($_SESSION['notification']);
            $class = $notification['type'] === 'success' ? 'alert-success' : 'alert-danger';
            echo "<div class='alert $class alert-dismissible fade show' role='alert' style='position: fixed; top: 20px; right: 20px; z-index: 2000;'>
                    {$notification['message']}
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
        }
    }
}
?>