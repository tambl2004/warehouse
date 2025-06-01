<?php
// File: api/product_handler.php
session_start();
header('Content-Type: application/json');

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/'); 
}

require_once ROOT_PATH . 'config/connect.php'; 
require_once ROOT_PATH . 'models/sanpham_model.php';
require_once ROOT_PATH . 'inc/auth.php';     
require_once ROOT_PATH . 'inc/security.php';  

$action = $_REQUEST['action'] ?? ''; //

$model = new SanPhamModel(); 

switch ($action) {
    case 'get_product':
        $product_id = $_GET['id'] ?? null;
        if (!$product_id || !is_numeric($product_id)) {
            echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ!']);
            exit;
        }
        $product = $model->laySanPhamTheoId((int)$product_id);
        if ($product) {
            echo json_encode(['success' => true, 'data' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm với ID: ' . htmlspecialchars($product_id)]);
        }
        break;

    case 'delete_product': 
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product_id = $_POST['product_id'] ?? null;

            if (!$product_id || !is_numeric($product_id)) {
                echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ!']);
                exit;
            }

            $product_id = (int)$product_id;
            $product = $model->laySanPhamTheoId($product_id); 

            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm!']);
                exit;
            }

            if ($model->xoaSanPham($product_id)) { 
                if (isset($_SESSION['user_id'])) {
                    logUserActivity($_SESSION['user_id'], 'UPDATE_PRODUCT_STATUS',
                        "API: Ngừng kinh doanh SP: {$product['product_name']} (ID: $product_id)");
                }
                echo json_encode(['success' => true, 'message' => 'Đã chuyển sản phẩm sang trạng thái Ngừng kinh doanh thành công!']);
            } else {
                logSystem('ERROR', 'API_DELETE_PRODUCT_FAIL', "API: Không thể cập nhật trạng thái Ngừng kinh doanh cho SP ID: $product_id");
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật trạng thái sản phẩm!']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Phương thức yêu cầu không hợp lệ.']);
        }
        break;

    case 'add_product':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Xử lý upload hình ảnh trước
            $image_relative_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir_base = ROOT_PATH . 'uploads/'; 
                $upload_dir_specific = $upload_dir_base . 'products/'; 
                
                if (!is_dir($upload_dir_specific)) {
                    if (!mkdir($upload_dir_specific, 0755, true)) {
                        echo json_encode(['success' => false, 'message' => 'Không thể tạo thư mục upload.']);
                        exit;
                    }
                }

                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($_FILES['image']['type'], $allowed_types)) {
                    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh JPG, PNG, GIF!']);
                    exit;
                }
                if ($_FILES['image']['size'] > 5 * 1024 * 1024) { // 5MB
                    echo json_encode(['success' => false, 'message' => 'Kích thước file không được vượt quá 5MB!']);
                    exit;
                }

                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name_only = 'product_' . time() . '_' . uniqid();
                $image_file_name_with_subdir = 'products/' . $file_name_only . '.' . $file_extension; 
                $target_file_path = $upload_dir_base . $image_file_name_with_subdir; 

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file_path)) {
                    $image_relative_path = $image_file_name_with_subdir;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Lỗi khi upload hình ảnh.']);
                    exit;
                }
            }
            
            $data = [
                'sku'            => cleanInput($_POST['sku'] ?? $model->taoSKUTuDong($_POST['category_id'] ?? null)), // Tạo SKU nếu rỗng
                'product_name'   => cleanInput($_POST['product_name'] ?? ''),
                'description'    => cleanInput($_POST['description'] ?? ''),
                'unit_price'     => isset($_POST['unit_price']) ? floatval($_POST['unit_price']) : 0,
                'stock_quantity' => isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : 0,
                'expiry_date'    => !empty($_POST['expiry_date']) ? cleanInput($_POST['expiry_date']) : null,
                'category_id'    => isset($_POST['category_id']) ? intval($_POST['category_id']) : null,
                'volume'         => isset($_POST['volume']) ? floatval($_POST['volume']) : 0,
                'image_url'      => $image_relative_path, // Đường dẫn đã có 'products/'
                'status'         => cleanInput($_POST['status'] ?? 'in_stock')
            ];

            $errors = $model->validateSanPham($data);
            if (empty($errors)) {
                $product_id = $model->themSanPham($data);
                if ($product_id) {
                     if (isset($_SESSION['user_id'])) {
                        logUserActivity($_SESSION['user_id'], 'ADD_PRODUCT_API', 
                            "API: Thêm SP mới: {$data['product_name']} (ID: $product_id)");
                    }
                    echo json_encode(['success' => true, 'message' => 'Thêm sản phẩm thành công!', 'product_id' => $product_id]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Không thể thêm sản phẩm vào CSDL.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.', 'errors' => $errors]);
            }
        } else {
             echo json_encode(['success' => false, 'message' => 'Phương thức POST được yêu cầu.']);
        }
        break;

    case 'edit_product':
         if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product_id = $_POST['product_id'] ?? ($_GET['id'] ?? null);

            if (!$product_id || !is_numeric($product_id)) {
                echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ!']);
                exit;
            }
            $product_id = (int)$product_id;
            $current_product = $model->laySanPhamTheoId($product_id);
            if (!$current_product) {
                 echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại.']);
                 exit;
            }

            $image_relative_path = $current_product['image_url'];

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
               
                $upload_dir_base = ROOT_PATH . 'uploads/';
                $upload_dir_specific = $upload_dir_base . 'products/';
                if (!is_dir($upload_dir_specific)) mkdir($upload_dir_specific, 0755, true);

                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($_FILES['image']['type'], $allowed_types) || $_FILES['image']['size'] > 5 * 1024 * 1024) {
                     echo json_encode(['success' => false, 'message' => 'File ảnh không hợp lệ (chỉ JPG, PNG, GIF và <5MB).']);
                     exit;
                }

                if ($image_relative_path && file_exists($upload_dir_base . $image_relative_path) && strpos($image_relative_path, 'placeholder') === false) {
                    unlink($upload_dir_base . $image_relative_path);
                }
                
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name_only = 'product_' . time() . '_' . uniqid();
                $new_image_file_name_with_subdir = 'products/' . $file_name_only . '.' . $file_extension;
                $target_file_path = $upload_dir_base . $new_image_file_name_with_subdir;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file_path)) {
                    $image_relative_path = $new_image_file_name_with_subdir;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Lỗi khi upload hình ảnh mới.']);
                    exit;
                }
            }
            
            $data = [
                'sku'            => cleanInput($_POST['sku'] ?? $current_product['sku']), // SKU thường không đổi khi sửa
                'product_name'   => cleanInput($_POST['product_name'] ?? ''),
                'description'    => cleanInput($_POST['description'] ?? ''),
                'unit_price'     => isset($_POST['unit_price']) ? floatval($_POST['unit_price']) : 0,
                'stock_quantity' => isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : 0,
                'expiry_date'    => !empty($_POST['expiry_date']) ? cleanInput($_POST['expiry_date']) : null,
                'category_id'    => isset($_POST['category_id']) ? intval($_POST['category_id']) : null,
                'volume'         => isset($_POST['volume']) ? floatval($_POST['volume']) : 0,
                'image_url'      => $image_relative_path,
                'status'         => cleanInput($_POST['status'] ?? 'in_stock')
            ];
            
            $errors = $model->validateSanPham($data, $product_id); 
            if (empty($errors)) {
                if ($model->capNhatSanPham($product_id, $data)) {
                    $model->capNhatTrangThaiTuDong($product_id);
                     if (isset($_SESSION['user_id'])) {
                        logUserActivity($_SESSION['user_id'], 'EDIT_PRODUCT_API', 
                            "API: Cập nhật SP: {$data['product_name']} (ID: $product_id)");
                    }
                    echo json_encode(['success' => true, 'message' => 'Cập nhật sản phẩm thành công!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Không thể cập nhật sản phẩm trong CSDL.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.', 'errors' => $errors]);
            }
        } else {
             echo json_encode(['success' => false, 'message' => 'Phương thức POST được yêu cầu.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ.']);
        break;
}
exit; 
?>