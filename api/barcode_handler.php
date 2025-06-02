<?php
session_start();
require_once '../config/connect.php';
require_once '../inc/auth.php';
require_once '../inc/security.php';
require_once '../vendor/autoload.php';

use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\BarcodeGeneratorHTML;

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_barcodes':
            getBarcodes();
            break;
        case 'add_barcode':
            addBarcode();
            break;
        case 'edit_barcode':
            editBarcode();
            break;
        case 'delete_barcode':
            deleteBarcode();
            break;
        case 'generate_barcode':
            generateBarcode();
            break;
        case 'scan_barcode':
            scanBarcode();
            break;
        case 'get_scan_logs':
            getScanLogs();
            break;
        case 'check_barcode_exists':
            checkBarcodeExists();
            break;
        case 'bulk_generate':
            bulkGenerateBarcodes();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log('Lỗi barcode API: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

// Lấy danh sách mã vạch
function getBarcodes() {
    global $pdo;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $search = $_GET['search'] ?? '';
    $product_filter = $_GET['product_filter'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    // Xây dựng câu truy vấn
    $where = "WHERE 1=1";
    $params = [];
    
    if ($search) {
        $where .= " AND (b.barcode_value LIKE ? OR p.product_name LIKE ? OR b.lot_number LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    if ($product_filter) {
        $where .= " AND b.product_id = ?";
        $params[] = $product_filter;
    }
    
    // Đếm tổng số bản ghi
    $countSql = "SELECT COUNT(*) FROM barcodes b 
                 LEFT JOIN products p ON b.product_id = p.product_id 
                 $where";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    // Lấy dữ liệu
    $sql = "SELECT b.*, p.product_name, p.sku, c.category_name 
            FROM barcodes b 
            LEFT JOIN products p ON b.product_id = p.product_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            $where 
            ORDER BY b.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $barcodes = $stmt->fetchAll();
    
    // Tính toán phân trang
    $totalPages = ceil($total / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $barcodes,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $total,
            'limit' => $limit
        ]
    ]);
}

// Thêm mã vạch mới
function addBarcode() {
    global $pdo;
    
    $barcode_value = cleanInput($_POST['barcode_value'] ?? '');
    $product_id = (int)($_POST['product_id'] ?? 0);
    $lot_number = cleanInput($_POST['lot_number'] ?? '');
    $expiry_date = $_POST['expiry_date'] ?? null;
    
    // Validate dữ liệu
    if (empty($barcode_value)) {
        echo json_encode(['success' => false, 'message' => 'Mã vạch không được trống']);
        return;
    }
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn sản phẩm']);
        return;
    }
    
    // Kiểm tra barcode đã tồn tại
    $checkStmt = $pdo->prepare("SELECT barcode_id FROM barcodes WHERE barcode_value = ?");
    $checkStmt->execute([$barcode_value]);
    if ($checkStmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Mã vạch đã tồn tại trong hệ thống']);
        return;
    }
    
    // Kiểm tra sản phẩm tồn tại
    $productStmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ?");
    $productStmt->execute([$product_id]);
    if (!$productStmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        return;
    }
    
    // Thêm mã vạch
    $sql = "INSERT INTO barcodes (barcode_value, product_id, lot_number, expiry_date, created_at, updated_at) 
            VALUES (?, ?, ?, ?, NOW(), NOW())";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$barcode_value, $product_id, $lot_number, $expiry_date])) {
        $barcode_id = $pdo->lastInsertId();
        
        // Ghi log
        logUserActivity($_SESSION['user_id'], 'ADD_BARCODE', "Thêm mã vạch: $barcode_value cho sản phẩm ID: $product_id");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Thêm mã vạch thành công',
            'barcode_id' => $barcode_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi khi thêm mã vạch']);
    }
}

// Sửa mã vạch
function editBarcode() {
    global $pdo;
    
    $barcode_id = (int)($_POST['barcode_id'] ?? 0);
    $barcode_value = cleanInput($_POST['barcode_value'] ?? '');
    $product_id = (int)($_POST['product_id'] ?? 0);
    $lot_number = cleanInput($_POST['lot_number'] ?? '');
    $expiry_date = $_POST['expiry_date'] ?? null;
    
    // Validate dữ liệu
    if ($barcode_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID mã vạch không hợp lệ']);
        return;
    }
    
    if (empty($barcode_value)) {
        echo json_encode(['success' => false, 'message' => 'Mã vạch không được trống']);
        return;
    }
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn sản phẩm']);
        return;
    }
    
    // Kiểm tra barcode value trùng lặp (trừ chính nó)
    $checkStmt = $pdo->prepare("SELECT barcode_id FROM barcodes WHERE barcode_value = ? AND barcode_id != ?");
    $checkStmt->execute([$barcode_value, $barcode_id]);
    if ($checkStmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Mã vạch đã tồn tại trong hệ thống']);
        return;
    }
    
    // Cập nhật mã vạch
    $sql = "UPDATE barcodes SET barcode_value = ?, product_id = ?, lot_number = ?, 
            expiry_date = ?, updated_at = NOW() WHERE barcode_id = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$barcode_value, $product_id, $lot_number, $expiry_date, $barcode_id])) {
        // Ghi log
        logUserActivity($_SESSION['user_id'], 'EDIT_BARCODE', "Sửa mã vạch ID: $barcode_id");
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật mã vạch thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi khi cập nhật mã vạch']);
    }
}

// Xóa mã vạch
function deleteBarcode() {
    global $pdo;
    
    $barcode_id = (int)($_POST['barcode_id'] ?? 0);
    
    if ($barcode_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID mã vạch không hợp lệ']);
        return;
    }
    
    // Kiểm tra mã vạch có trong lịch sử quét không
    $checkScanStmt = $pdo->prepare("SELECT COUNT(*) FROM barcode_scan_logs WHERE barcode_id = ?");
    $checkScanStmt->execute([$barcode_id]);
    if ($checkScanStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa mã vạch đã có lịch sử quét']);
        return;
    }
    
    // Lấy thông tin mã vạch trước khi xóa
    $infoStmt = $pdo->prepare("SELECT barcode_value FROM barcodes WHERE barcode_id = ?");
    $infoStmt->execute([$barcode_id]);
    $barcode_value = $infoStmt->fetchColumn();
    
    if (!$barcode_value) {
        echo json_encode(['success' => false, 'message' => 'Mã vạch không tồn tại']);
        return;
    }
    
    // Xóa mã vạch
    $stmt = $pdo->prepare("DELETE FROM barcodes WHERE barcode_id = ?");
    
    if ($stmt->execute([$barcode_id])) {
        // Ghi log
        logUserActivity($_SESSION['user_id'], 'DELETE_BARCODE', "Xóa mã vạch: $barcode_value (ID: $barcode_id)");
        
        echo json_encode(['success' => true, 'message' => 'Xóa mã vạch thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi khi xóa mã vạch']);
    }
}

// Sinh mã vạch hình ảnh
function generateBarcode() {
    $barcode_value = $_GET['barcode'] ?? '';
    $type = $_GET['type'] ?? 'png';
    $width = (int)($_GET['width'] ?? 2);
    $height = (int)($_GET['height'] ?? 50);
    
    if (empty($barcode_value)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Mã vạch không được trống']);
        return;
    }
    
    try {
        switch ($type) {
            case 'png':
                $generator = new BarcodeGeneratorPNG();
                header('Content-Type: image/png');
                echo $generator->getBarcode($barcode_value, $generator::TYPE_CODE_128, $width, $height);
                break;
                
            case 'svg':
                $generator = new BarcodeGeneratorSVG();
                header('Content-Type: image/svg+xml');
                echo $generator->getBarcode($barcode_value, $generator::TYPE_CODE_128, $width, $height);
                break;
                
            case 'html':
                $generator = new BarcodeGeneratorHTML();
                header('Content-Type: text/html');
                echo $generator->getBarcode($barcode_value, $generator::TYPE_CODE_128, $width, $height);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Định dạng không hỗ trợ']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi sinh mã vạch: ' . $e->getMessage()]);
    }
}

// Quét mã vạch (mô phỏng)
function scanBarcode() {
    global $pdo;
    
    $barcode_value = cleanInput($_POST['barcode_value'] ?? '');
    $scan_result = $_POST['scan_result'] ?? 'success';
    $description = cleanInput($_POST['description'] ?? '');
    
    if (empty($barcode_value)) {
        echo json_encode(['success' => false, 'message' => 'Mã vạch không được trống']);
        return;
    }
    
    // Kiểm tra mã vạch có tồn tại không
    $barcodeStmt = $pdo->prepare("
        SELECT b.*, p.product_name, p.sku, p.stock_quantity 
        FROM barcodes b 
        LEFT JOIN products p ON b.product_id = p.product_id 
        WHERE b.barcode_value = ?
    ");
    $barcodeStmt->execute([$barcode_value]);
    $barcode = $barcodeStmt->fetch();
    
    if (!$barcode) {
        // Mã vạch không tồn tại
        $scan_result = 'failed';
        $description = 'Mã vạch không tồn tại trong hệ thống';
        
        // Ghi log quét thất bại
        $logSql = "INSERT INTO barcode_scan_logs (barcode_id, user_id, scan_result, description, created_at, updated_at) 
                   VALUES (NULL, ?, ?, ?, NOW(), NOW())";
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute([$_SESSION['user_id'], $scan_result, $description]);
        
        echo json_encode([
            'success' => false, 
            'message' => 'Mã vạch không tồn tại trong hệ thống',
            'suggest_create' => true,
            'barcode_value' => $barcode_value
        ]);
        return;
    }
    
    // Ghi log quét thành công
    $logSql = "INSERT INTO barcode_scan_logs (barcode_id, user_id, scan_result, description, created_at, updated_at) 
               VALUES (?, ?, ?, ?, NOW(), NOW())";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([$barcode['barcode_id'], $_SESSION['user_id'], $scan_result, $description]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Quét mã vạch thành công',
        'data' => $barcode
    ]);
}

// Lấy lịch sử quét mã vạch
function getScanLogs() {
    global $pdo;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $barcode_filter = $_GET['barcode_filter'] ?? '';
    $user_filter = $_GET['user_filter'] ?? '';
    $result_filter = $_GET['result_filter'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    // Xây dựng câu truy vấn
    $where = "WHERE 1=1";
    $params = [];
    
    if ($barcode_filter) {
        $where .= " AND b.barcode_value LIKE ?";
        $params[] = "%$barcode_filter%";
    }
    
    if ($user_filter) {
        $where .= " AND u.username LIKE ?";
        $params[] = "%$user_filter%";
    }
    
    if ($result_filter) {
        $where .= " AND bsl.scan_result = ?";
        $params[] = $result_filter;
    }
    
    // Đếm tổng số bản ghi
    $countSql = "SELECT COUNT(*) FROM barcode_scan_logs bsl 
                 LEFT JOIN barcodes b ON bsl.barcode_id = b.barcode_id
                 LEFT JOIN users u ON bsl.user_id = u.user_id 
                 $where";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    // Lấy dữ liệu
    $sql = "SELECT bsl.*, b.barcode_value, u.username, u.full_name, p.product_name 
            FROM barcode_scan_logs bsl 
            LEFT JOIN barcodes b ON bsl.barcode_id = b.barcode_id
            LEFT JOIN users u ON bsl.user_id = u.user_id
            LEFT JOIN products p ON b.product_id = p.product_id
            $where 
            ORDER BY bsl.scan_time DESC 
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Tính toán phân trang
    $totalPages = ceil($total / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $logs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $total,
            'limit' => $limit
        ]
    ]);
}

// Kiểm tra mã vạch tồn tại
function checkBarcodeExists() {
    global $pdo;
    
    $barcode_value = cleanInput($_GET['barcode_value'] ?? '');
    
    if (empty($barcode_value)) {
        echo json_encode(['success' => false, 'message' => 'Mã vạch không được trống']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT b.*, p.product_name, p.sku 
        FROM barcodes b 
        LEFT JOIN products p ON b.product_id = p.product_id 
        WHERE b.barcode_value = ?
    ");
    $stmt->execute([$barcode_value]);
    $barcode = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'exists' => (bool)$barcode,
        'data' => $barcode ?: null
    ]);
}

// Sinh mã vạch hàng loạt cho sản phẩm
function bulkGenerateBarcodes() {
    global $pdo;
    
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    $lot_number = cleanInput($_POST['lot_number'] ?? '');
    $expiry_date = $_POST['expiry_date'] ?? null;
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn sản phẩm']);
        return;
    }
    
    if ($quantity <= 0 || $quantity > 100) {
        echo json_encode(['success' => false, 'message' => 'Số lượng phải từ 1 đến 100']);
        return;
    }
    
    // Kiểm tra sản phẩm tồn tại
    $productStmt = $pdo->prepare("SELECT sku, product_name FROM products WHERE product_id = ?");
    $productStmt->execute([$product_id]);
    $product = $productStmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        $generated = [];
        
        for ($i = 1; $i <= $quantity; $i++) {
            // Sinh mã vạch duy nhất
            do {
                $barcode_value = $product['sku'] . str_pad(time() + $i, 10, '0', STR_PAD_LEFT) . rand(100, 999);
                
                // Kiểm tra trùng lặp
                $checkStmt = $pdo->prepare("SELECT barcode_id FROM barcodes WHERE barcode_value = ?");
                $checkStmt->execute([$barcode_value]);
            } while ($checkStmt->fetchColumn());
            
            // Thêm mã vạch
            $sql = "INSERT INTO barcodes (barcode_value, product_id, lot_number, expiry_date, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$barcode_value, $product_id, $lot_number, $expiry_date]);
            
            $generated[] = [
                'barcode_id' => $pdo->lastInsertId(),
                'barcode_value' => $barcode_value
            ];
        }
        
        $pdo->commit();
        
        // Ghi log
        logUserActivity($_SESSION['user_id'], 'BULK_GENERATE_BARCODE', "Sinh $quantity mã vạch cho sản phẩm: {$product['product_name']}");
        
        echo json_encode([
            'success' => true,
            'message' => "Sinh thành công $quantity mã vạch",
            'data' => $generated
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo json_encode(['success' => false, 'message' => 'Lỗi sinh mã vạch hàng loạt: ' . $e->getMessage()]);
    }
}
?> 