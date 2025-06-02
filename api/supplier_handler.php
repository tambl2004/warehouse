<?php
// File: api/supplier_handler.php
session_start();
header('Content-Type: application/json');

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'config/connect.php';
require_once ROOT_PATH . 'models/NhaCungCapModel.php'; // Sửa tên file cho đúng
require_once ROOT_PATH . 'inc/auth.php';
require_once ROOT_PATH . 'inc/security.php';

$action = $_REQUEST['action'] ?? '';
$model = new NhaCungCapModel();

switch ($action) {
    case 'get_supplier':
        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ.']); exit;
        }
        $supplier = $model->layNhaCungCapTheoId((int)$id);
        if ($supplier) {
            echo json_encode(['success' => true, 'data' => $supplier]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhà cung cấp.']);
        }
        break;

    case 'add_supplier':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'supplier_code'  => cleanInput($_POST['supplier_code'] ?? ''),
                'supplier_name'  => cleanInput($_POST['supplier_name'] ?? ''),
                'email'          => cleanInput($_POST['email'] ?? ''),
                'phone_number'   => cleanInput($_POST['phone_number'] ?? ''),
                'address'        => cleanInput($_POST['address'] ?? ''),
                'tax_code'       => cleanInput($_POST['tax_code'] ?? ''),
                'contact_person' => cleanInput($_POST['contact_person'] ?? ''),
                'website'        => cleanInput($_POST['website'] ?? ''),
                'notes'          => cleanInput($_POST['notes'] ?? ''),
                'status'         => cleanInput($_POST['status'] ?? 'active'),
            ];
            $errors = $model->validateNhaCungCap($data);
            if (empty($errors)) {
                $result = $model->themNhaCungCap($data);
                if (is_array($result) && isset($result['error_type']) && $result['error_type'] == 'duplicate') {
                    echo json_encode(['success' => false, 'message' => $result['message']]);
                } elseif ($result) {
                    logUserActivity($_SESSION['user_id'] ?? 0, 'ADD_SUPPLIER_API', "API: Thêm NCC: {$data['supplier_name']}");
                    echo json_encode(['success' => true, 'message' => 'Thêm nhà cung cấp thành công!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm nhà cung cấp.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.', 'errors' => $errors]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Yêu cầu POST.']);
        }
        break;

    case 'edit_supplier':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['supplier_id'] ?? null;
            if (!$id || !is_numeric($id)) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ.']); exit;
            }
             $data = [
                'supplier_code'  => cleanInput($_POST['supplier_code'] ?? ''),
                'supplier_name'  => cleanInput($_POST['supplier_name'] ?? ''),
                'email'          => cleanInput($_POST['email'] ?? ''),
                'phone_number'   => cleanInput($_POST['phone_number'] ?? ''),
                'address'        => cleanInput($_POST['address'] ?? ''),
                'tax_code'       => cleanInput($_POST['tax_code'] ?? ''),
                'contact_person' => cleanInput($_POST['contact_person'] ?? ''),
                'website'        => cleanInput($_POST['website'] ?? ''),
                'notes'          => cleanInput($_POST['notes'] ?? ''),
                'status'         => cleanInput($_POST['status'] ?? 'active'),
            ];
            $errors = $model->validateNhaCungCap($data, $id);
             if (empty($errors)) {
                $result = $model->capNhatNhaCungCap($id, $data);
                 if (is_array($result) && isset($result['error_type']) && $result['error_type'] == 'duplicate') {
                    echo json_encode(['success' => false, 'message' => $result['message']]);
                } elseif ($result) {
                    logUserActivity($_SESSION['user_id'] ?? 0, 'EDIT_SUPPLIER_API', "API: Sửa NCC ID: $id");
                    echo json_encode(['success' => true, 'message' => 'Cập nhật nhà cung cấp thành công!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật nhà cung cấp.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.', 'errors' => $errors]);
            }
        } else {
             echo json_encode(['success' => false, 'message' => 'Yêu cầu POST.']);
        }
        break;

    case 'toggle_status_supplier':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['supplier_id'] ?? null;
            $current_status = $_POST['current_status'] ?? null;

            if (!$id || !is_numeric($id) || !in_array($current_status, ['active', 'inactive', 'discontinued'])) {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']); exit;
            }
            // Nếu đang active/inactive thì chuyển sang discontinued
            // Nếu đang discontinued thì chuyển sang active
            $new_status = ($current_status === 'discontinued') ? 'active' : 'discontinued';
            
            if ($model->thayDoiTrangThaiNhaCungCap($id, $new_status)) {
                 logUserActivity($_SESSION['user_id'] ?? 0, 'TOGGLE_SUPPLIER_STATUS_API', "API: Đổi trạng thái NCC ID: $id sang $new_status");
                echo json_encode(['success' => true, 'message' => 'Thay đổi trạng thái nhà cung cấp thành công!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi thay đổi trạng thái.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Yêu cầu POST.']);
        }
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action không được hỗ trợ.']);
        break;
}
exit;
?>