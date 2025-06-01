<?php


require_once 'models/sanpham_model.php';
require_once 'inc/auth.php';
require_once 'inc/security.php';

class SanPhamController {
    private $model;
    
    public function __construct() {
        $this->model = new SanPhamModel();
    }
    

    public function xuLyRequest() {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                return $this->danhSachSanPham();
                break;
            case 'add':
                $this->themSanPham();
                break;
            case 'edit':
                $this->suaSanPham();
                break;
            case 'delete':
                $this->xoaSanPham();
                break;
            case 'view':
                $this->xemChiTiet();
                break;
            case 'get_product':
                $this->layThongTinSanPham();
                break;
            case 'upload_image':
                $this->uploadHinhAnh();
                break;
            case 'search':
                $this->timKiemSanPham();
                break;
            case 'export':
                $this->xuatBaoCao();
                break;
            default:
                return $this->danhSachSanPham();
        }
    }
    

    private function danhSachSanPham() {
        $page = $_GET['page'] ?? 1;
        $search = $_GET['search'] ?? '';
        $category_id = $_GET['category_id'] ?? null;
        $status = $_GET['status'] ?? null;
        $limit = 20;
        
        // Lấy danh sách sản phẩm
        $products = $this->model->layDanhSachSanPham($page, $limit, $search, $category_id, $status);
        $total_products = $this->model->demTongSoSanPham($search, $category_id, $status);
        $total_pages = ceil($total_products / $limit);
        
        // Lấy danh mục cho dropdown
        $categories = $this->model->layDanhSachDanhMuc();
        
        // Lấy thống kê
        $statistics = $this->model->thongKeSanPham();
        
        // Lấy sản phẩm cảnh báo
        $expiring_products = $this->model->laySanPhamGanHetHan();
        $low_stock_products = $this->model->laySanPhamTonKhoThap();
        
        // Truyền dữ liệu vào view
        return [
            'products' => $products,
            'categories' => $categories,
            'statistics' => $statistics,
            'expiring_products' => $expiring_products,
            'low_stock_products' => $low_stock_products,
            'current_page' => $page,
            'total_pages' => $total_pages,
            'search' => $search,
            'selected_category' => $category_id,
            'selected_status' => $status
        ];
    }

    private function themSanPham() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Xử lý upload hình ảnh trước
            $image_url = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image_url = $this->xuLyUploadHinhAnh($_FILES['image']);
            }
            
            $data = [
                'product_name' => cleanInput($_POST['product_name']),
                'description' => cleanInput($_POST['description']),
                'unit_price' => floatval($_POST['unit_price']),
                'stock_quantity' => intval($_POST['stock_quantity']),
                'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
                'category_id' => intval($_POST['category_id']),
                'volume' => floatval($_POST['volume']),
                'image_url' => $image_url,
                'status' => $_POST['status'] ?? 'in_stock'
            ];
            
            // Validate dữ liệu
            $errors = $this->model->validateSanPham($data);
            
            if (empty($errors)) {
                $product_id = $this->model->themSanPham($data);
                
                if ($product_id) {
                    // Ghi log
                    if (isset($_SESSION['user_id'])) {
                        logUserActivity($_SESSION['user_id'], 'ADD_PRODUCT', 
                            "Thêm sản phẩm mới: {$data['product_name']} (ID: $product_id)");
                    }
                    
                    $this->guiThongBao('success', 'Thêm sản phẩm thành công!');
                    header('Location: ?option=sanpham');
                    exit;
                } else {
                    $this->guiThongBao('error', 'Có lỗi xảy ra khi thêm sản phẩm!');
                }
            } else {
                $this->guiThongBao('error', implode('<br>', $errors));
            }
        }
    }
    

    private function suaSanPham() {
        $product_id = $_GET['id'] ?? null;
        
        if (!$product_id) {
            $this->guiThongBao('error', 'Không tìm thấy sản phẩm!');
            header('Location: ?option=sanpham');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Lấy thông tin sản phẩm hiện tại
            $current_product = $this->model->laySanPhamTheoId($product_id);
            $image_url = $current_product['image_url'];
            
            // Xử lý upload hình ảnh mới nếu có
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $new_image = $this->xuLyUploadHinhAnh($_FILES['image']);
                if ($new_image) {
                    // Xóa ảnh cũ nếu có
                    if ($image_url && file_exists("uploads/" . $image_url)) {
                        unlink("uploads/" . $image_url);
                    }
                    $image_url = $new_image;
                }
            }
            
            $data = [
                'product_name' => cleanInput($_POST['product_name']),
                'description' => cleanInput($_POST['description']),
                'unit_price' => floatval($_POST['unit_price']),
                'stock_quantity' => intval($_POST['stock_quantity']),
                'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
                'category_id' => intval($_POST['category_id']),
                'volume' => floatval($_POST['volume']),
                'image_url' => $image_url,
                'status' => $_POST['status']
            ];
            
            // Validate dữ liệu
            $errors = $this->model->validateSanPham($data, $product_id);
            
            if (empty($errors)) {
                if ($this->model->capNhatSanPham($product_id, $data)) {
                    // Cập nhật trạng thái tự động
                    $this->model->capNhatTrangThaiTuDong($product_id);
                    
                    // Ghi log
                    if (isset($_SESSION['user_id'])) {
                        logUserActivity($_SESSION['user_id'], 'EDIT_PRODUCT', 
                            "Cập nhật sản phẩm: {$data['product_name']} (ID: $product_id)");
                    }
                    
                    $this->guiThongBao('success', 'Cập nhật sản phẩm thành công!');
                } else {
                    $this->guiThongBao('error', 'Có lỗi xảy ra khi cập nhật sản phẩm!');
                }
            } else {
                $this->guiThongBao('error', implode('<br>', $errors));
            }
        }
    }
    
    
    private function xoaSanPham() {
        header('Content-Type: application/json'); // Thêm dòng này
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product_id = $_POST['product_id'] ?? null;
            
            if (!$product_id) {
                echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ!']);
                exit;
            }
            
            // Lấy thông tin sản phẩm
            $product_id = (int)$product_id;
            $product = $this->model->laySanPhamTheoId($product_id);
            
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm!']);
                exit;
            }
            
            if ($this->model->xoaSanPham($product_id)) {
                // Ghi log
                if (isset($_SESSION['user_id'])) {
                    logUserActivity($_SESSION['user_id'], 'DELETE_PRODUCT', 
                        "Ngừng kinh doanh sản phẩm: {$product['product_name']} (ID: $product_id)");
                }
                
                echo json_encode(['success' => true, 'message' => 'Ngừng kinh doanh sản phẩm thành công!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra!']);
            }
            exit;
        }
    }
    
   
    private function layThongTinSanPham() {
        header('Content-Type: application/json');
        $product_id = $_GET['id'] ?? null;
        
        if (!$product_id || !is_numeric($product_id)) { // Kiểm tra ID hợp lệ hơn
            echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ!']);
            exit;
        }
    
        
        $product = $this->model->laySanPhamTheoId($product_id);
        
        if ($product) {
            echo json_encode(['success' => true, 'data' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm!']);
        }
        exit;
    }
    
    
    private function xuLyUploadHinhAnh($file) {
        $upload_dir = 'uploads/products/';
        
        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Kiểm tra loại file
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            $this->guiThongBao('error', 'Chỉ chấp nhận file ảnh JPG, PNG, GIF!');
            return null;
        }
        
        // Kiểm tra kích thước file (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            $this->guiThongBao('error', 'Kích thước file không được vượt quá 5MB!');
            return null;
        }
        
        // Tạo tên file duy nhất
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_name = 'product_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        // Upload file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            return 'products/' . $file_name;
        }
        
        return null;
    }
    
    
    private function uploadHinhAnh() {
        if (isset($_FILES['image'])) {
            $image_url = $this->xuLyUploadHinhAnh($_FILES['image']);
            
            if ($image_url) {
                echo json_encode([
                    'success' => true, 
                    'image_url' => $image_url,
                    'full_path' => 'uploads/' . $image_url
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Upload thất bại!']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Không có file được chọn!']);
        }
        exit;
    }
    
    
    private function timKiemSanPham() {
        $search = $_GET['q'] ?? '';
        $category_id = $_GET['category_id'] ?? null;
        $status = $_GET['status'] ?? null;
        
        $products = $this->model->layDanhSachSanPham(1, 50, $search, $category_id, $status);
        
        echo json_encode(['success' => true, 'data' => $products]);
        exit;
    }
    
    
    private function xuatBaoCao() {
        $type = $_GET['type'] ?? 'all';
        
        switch ($type) {
            case 'expiring':
                $data = $this->model->laySanPhamGanHetHan();
                $filename = 'san_pham_gan_het_han_' . date('Y-m-d');
                break;
            case 'low_stock':
                $data = $this->model->laySanPhamTonKhoThap();
                $filename = 'san_pham_ton_kho_thap_' . date('Y-m-d');
                break;
            default:
                $data = $this->model->layDanhSachSanPham(1, 9999);
                $filename = 'danh_sach_san_pham_' . date('Y-m-d');
        }
        
        $this->xuatFileCSV($data, $filename);
    }
    
    
    private function xuatFileCSV($data, $filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // BOM cho UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header
        fputcsv($output, [
            'STT', 'Mã SKU', 'Tên sản phẩm', 'Danh mục', 'Giá', 
            'Tồn kho', 'Hạn sử dụng', 'Trạng thái', 'Ngày tạo'
        ]);
        
        // Dữ liệu
        $stt = 1;
        foreach ($data as $row) {
            fputcsv($output, [
                $stt++,
                $row['sku'],
                $row['product_name'],
                $row['category_name'] ?? '',
                number_format($row['unit_price'], 0, ',', '.') . ' VNĐ',
                $row['stock_quantity'],
                $row['expiry_date'] ? date('d/m/Y', strtotime($row['expiry_date'])) : '',
                $this->layTenTrangThai($row['status']),
                date('d/m/Y H:i', strtotime($row['created_at']))
            ]);
        }
        
        fclose($output);
        exit;
    }
    

    private function layTenTrangThai($status) {
        $statuses = [
            'in_stock' => 'Còn hàng',
            'out_of_stock' => 'Hết hàng',
            'discontinued' => 'Ngừng kinh doanh'
        ];
        
        return $statuses[$status] ?? $status;
    }
    
    
    private function guiThongBao($type, $message) {
        $_SESSION['notification'] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
  
    public function hienThiThongBao() {
        if (isset($_SESSION['notification'])) {
            $notification = $_SESSION['notification'];
            unset($_SESSION['notification']);
            
            $class = $notification['type'] === 'success' ? 'alert-success' : 'alert-danger';
            
            echo "<div class='alert $class alert-dismissible fade show' role='alert'>
                    {$notification['message']}
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
        }
    }
}

?>
