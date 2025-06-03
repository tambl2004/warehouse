<?php
session_start();
require_once '../config/connect.php';
require_once '../inc/auth.php';
require_once '../inc/security.php';
require_once '../vendor/autoload.php';

use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\BarcodeGeneratorHTML;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// QUAN TRỌNG: Xử lý action 'generate_barcode' trước tiên để tránh các output không mong muốn
if ($action === 'generate_barcode') {
    generateBarcode(); // Gọi hàm tạo mã vạch và exit luôn
}

// Kiểm tra đăng nhập cho các action khác
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

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
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log('Lỗi barcode API: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Có lỗi máy chủ xảy ra: ' . $e->getMessage()]);
}
exit;

// --- Các định nghĩa hàm ---

// Lấy danh sách mã vạch
function getBarcodes() {
    global $pdo;
    header('Content-Type: application/json');

    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $search = $_GET['search'] ?? '';
    $product_filter = $_GET['product_filter'] ?? '';

    $offset = ($page - 1) * $limit;

    $where_conditions = [];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(b.barcode_value LIKE :search_term OR p.product_name LIKE :search_term OR b.lot_number LIKE :search_term)";
        $params[':search_term'] = "%$search%";
    }

    if (!empty($product_filter) && is_numeric($product_filter)) {
        $where_conditions[] = "b.product_id = :product_id";
        $params[':product_id'] = $product_filter;
    }

    $where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

    $countSql = "SELECT COUNT(b.barcode_id) FROM barcodes b 
                 LEFT JOIN products p ON b.product_id = p.product_id 
                 $where_clause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT b.*, p.product_name, p.sku, c.category_name 
            FROM barcodes b 
            LEFT JOIN products p ON b.product_id = p.product_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            $where_clause 
            ORDER BY b.created_at DESC 
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $barcodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    header('Content-Type: application/json');

    $barcode_value = cleanInput($_POST['barcode_value'] ?? '');
    $product_id = (int)($_POST['product_id'] ?? 0);
    $lot_number = cleanInput($_POST['lot_number'] ?? '');
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

    if (empty($barcode_value)) {
        echo json_encode(['success' => false, 'message' => 'Mã vạch không được trống']);
        return;
    }
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn sản phẩm']);
        return;
    }

    $checkStmt = $pdo->prepare("SELECT barcode_id FROM barcodes WHERE barcode_value = ?");
    $checkStmt->execute([$barcode_value]);
    if ($checkStmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Mã vạch đã tồn tại trong hệ thống']);
        return;
    }

    $productStmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ?");
    $productStmt->execute([$product_id]);
    if (!$productStmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        return;
    }

    $sql = "INSERT INTO barcodes (barcode_value, product_id, lot_number, expiry_date, created_at, updated_at) 
            VALUES (?, ?, ?, ?, NOW(), NOW())";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$barcode_value, $product_id, $lot_number, $expiry_date])) {
        $barcode_id = $pdo->lastInsertId();
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
    header('Content-Type: application/json');

    $barcode_id = (int)($_POST['barcode_id'] ?? 0);
    $barcode_value = cleanInput($_POST['barcode_value'] ?? '');
    $product_id = (int)($_POST['product_id'] ?? 0);
    $lot_number = cleanInput($_POST['lot_number'] ?? '');
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

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

    $checkStmt = $pdo->prepare("SELECT barcode_id FROM barcodes WHERE barcode_value = ? AND barcode_id != ?");
    $checkStmt->execute([$barcode_value, $barcode_id]);
    if ($checkStmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Mã vạch đã tồn tại trong hệ thống']);
        return;
    }

    $sql = "UPDATE barcodes SET barcode_value = ?, product_id = ?, lot_number = ?, 
            expiry_date = ?, updated_at = NOW() WHERE barcode_id = ?";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$barcode_value, $product_id, $lot_number, $expiry_date, $barcode_id])) {
        logUserActivity($_SESSION['user_id'], 'EDIT_BARCODE', "Sửa mã vạch ID: $barcode_id");
        echo json_encode(['success' => true, 'message' => 'Cập nhật mã vạch thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi khi cập nhật mã vạch']);
    }
}

// Xóa mã vạch
function deleteBarcode() {
    global $pdo;
    header('Content-Type: application/json');

    $barcode_id = (int)($_POST['barcode_id'] ?? 0);

    if ($barcode_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID mã vạch không hợp lệ']);
        return;
    }

    $checkScanStmt = $pdo->prepare("SELECT COUNT(*) FROM barcode_scan_logs WHERE barcode_id = ?");
    $checkScanStmt->execute([$barcode_id]);
    if ($checkScanStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa mã vạch đã có lịch sử quét']);
        return;
    }

    $infoStmt = $pdo->prepare("SELECT barcode_value FROM barcodes WHERE barcode_id = ?");
    $infoStmt->execute([$barcode_id]);
    $barcode_value = $infoStmt->fetchColumn();

    if (!$barcode_value) {
        echo json_encode(['success' => false, 'message' => 'Mã vạch không tồn tại']);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM barcodes WHERE barcode_id = ?");

    if ($stmt->execute([$barcode_id])) {
        logUserActivity($_SESSION['user_id'], 'DELETE_BARCODE', "Xóa mã vạch: $barcode_value (ID: $barcode_id)");
        echo json_encode(['success' => true, 'message' => 'Xóa mã vạch thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi khi xóa mã vạch']);
    }
}

// Sinh mã vạch hình ảnh
function generateBarcode() {
    $barcode_value = $_GET['barcode'] ?? '';
    $type = strtolower($_GET['type'] ?? 'png');
    $width = (int)($_GET['width'] ?? 2);
    $height = (int)($_GET['height'] ?? 50);

    if (empty($barcode_value)) {
        http_response_code(400);
        exit;
    }

    try {
        $generator = null;
        $contentType = '';

        switch ($type) {
            case 'png':
                $generator = new BarcodeGeneratorPNG();
                $contentType = 'image/png';
                break;
            case 'svg':
                $generator = new BarcodeGeneratorSVG();
                $contentType = 'image/svg+xml';
                break;
            case 'html':
                $generator = new BarcodeGeneratorHTML();
                $contentType = 'text/html; charset=utf-8';
                break;
            default:
                http_response_code(400);
                exit;
        }

        // QUAN TRỌNG: Clear any output buffer trước khi output hình ảnh
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: ' . $contentType);
        header('Cache-Control: max-age=3600'); // Cache 1 giờ
        echo $generator->getBarcode($barcode_value, $generator::TYPE_CODE_128, $width, $height);
        exit; // QUAN TRỌNG: Dừng script ngay

    } catch (Exception $e) {
        http_response_code(500);
        exit;
    }
}

// Quét mã vạch (mô phỏng)
function scanBarcode() {
    global $pdo;
    header('Content-Type: application/json');

    $barcode_value = cleanInput($_POST['barcode_value'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');

    if (empty($barcode_value)) {
        echo json_encode(['success' => false, 'message' => 'Mã vạch không được trống']);
        return;
    }

    $barcodeStmt = $pdo->prepare("
        SELECT b.*, p.product_name, p.sku, p.stock_quantity 
        FROM barcodes b 
        LEFT JOIN products p ON b.product_id = p.product_id 
        WHERE b.barcode_value = ?
    ");
    $barcodeStmt->execute([$barcode_value]);
    $barcode = $barcodeStmt->fetch(PDO::FETCH_ASSOC);

    $current_user_id = $_SESSION['user_id'] ?? null;
    if (!$current_user_id) {
        echo json_encode(['success' => false, 'message' => 'Không thể xác định người dùng.']);
        return;
    }
    
    $scan_result_db = 'failed';
    $log_barcode_id = null;

    if ($barcode) {
        $scan_result_db = 'success';
        $log_barcode_id = $barcode['barcode_id'];
        $logSql = "INSERT INTO barcode_scan_logs (barcode_id, user_id, scan_time, scan_result, description) 
                   VALUES (?, ?, NOW(), ?, ?)";
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute([$log_barcode_id, $current_user_id, $scan_result_db, $description]);

        echo json_encode([
            'success' => true,
            'message' => 'Quét mã vạch thành công',
            'data' => $barcode
        ]);
    } else {
        $description_log = 'Mã vạch không tồn tại trong hệ thống: ' . htmlspecialchars($barcode_value);
        if (!empty($description)) {
            $description_log .= " | Ghi chú người dùng: " . $description;
        }
        
        $logSql = "INSERT INTO barcode_scan_logs (barcode_id, user_id, scan_time, scan_result, description) 
                   VALUES (NULL, ?, NOW(), ?, ?)";
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute([$current_user_id, $scan_result_db, $description_log]);

        echo json_encode([
            'success' => false,
            'message' => 'Mã vạch không tồn tại trong hệ thống',
            'suggest_create' => true,
            'barcode_value' => $barcode_value
        ]);
    }
}

// Lấy lịch sử quét mã vạch
function getScanLogs() {
    global $pdo;
    header('Content-Type: application/json');

    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $barcode_filter = $_GET['barcode_filter'] ?? '';
    $user_filter = $_GET['user_filter'] ?? '';
    $result_filter = $_GET['result_filter'] ?? '';

    $offset = ($page - 1) * $limit;

    $where_conditions = [];
    $params = [];

    if (!empty($barcode_filter)) {
        $where_conditions[] = "b.barcode_value LIKE :barcode_val";
        $params[':barcode_val'] = "%$barcode_filter%";
    }
    if (!empty($user_filter)) {
        $where_conditions[] = "(u.username LIKE :user_term OR u.full_name LIKE :user_term)";
        $params[':user_term'] = "%$user_filter%";
    }
    if (!empty($result_filter)) {
        $where_conditions[] = "bsl.scan_result = :scan_result";
        $params[':scan_result'] = $result_filter;
    }
    $where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

    $countSql = "SELECT COUNT(bsl.scan_id) FROM barcode_scan_logs bsl 
                 LEFT JOIN barcodes b ON bsl.barcode_id = b.barcode_id
                 LEFT JOIN users u ON bsl.user_id = u.user_id 
                 $where_clause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT bsl.*, b.barcode_value, u.username, u.full_name, p.product_name 
            FROM barcode_scan_logs bsl 
            LEFT JOIN barcodes b ON bsl.barcode_id = b.barcode_id
            LEFT JOIN users u ON bsl.user_id = u.user_id
            LEFT JOIN products p ON b.product_id = p.product_id
            $where_clause 
            ORDER BY bsl.scan_time DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    header('Content-Type: application/json');

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
    $barcode = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'exists' => (bool)$barcode,
        'data' => $barcode ?: null
    ]);
}

// Sinh mã vạch hàng loạt cho sản phẩm
function bulkGenerateBarcodes() {
    global $pdo;
    header('Content-Type: application/json');

    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    $lot_number = cleanInput($_POST['lot_number'] ?? '');
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn sản phẩm']);
        return;
    }
    if ($quantity <= 0 || $quantity > 100) {
        echo json_encode(['success' => false, 'message' => 'Số lượng phải từ 1 đến 100']);
        return;
    }

    $productStmt = $pdo->prepare("SELECT sku, product_name FROM products WHERE product_id = ?");
    $productStmt->execute([$product_id]);
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        return;
    }

    $pdo->beginTransaction();
    try {
        $generated = [];
        $baseSkt = preg_replace('/[^A-Za-z0-9]/', '', $product['sku']);

        for ($i = 0; $i < $quantity; $i++) {
            $unique_part = '';
            $barcode_value = '';
            $is_unique = false;
            $max_tries = 10;
            $try_count = 0;

            do {
                $unique_part = substr(str_replace('.', '', microtime(true)), -6) . str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
                $barcode_value = $baseSkt . '-' . $unique_part;
                $barcode_value = substr($barcode_value, 0, 30); 

                $checkStmt = $pdo->prepare("SELECT barcode_id FROM barcodes WHERE barcode_value = ?");
                $checkStmt->execute([$barcode_value]);
                $is_unique = !$checkStmt->fetchColumn();
                $try_count++;
            } while (!$is_unique && $try_count < $max_tries);

            if (!$is_unique) {
                throw new Exception("Không thể tạo mã vạch duy nhất sau $max_tries lần thử cho sản phẩm ID: $product_id.");
            }
            
            $sql = "INSERT INTO barcodes (barcode_value, product_id, lot_number, expiry_date, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            if (!$stmt->execute([$barcode_value, $product_id, $lot_number, $expiry_date])) {
                throw new Exception("Lỗi khi chèn mã vạch vào CSDL.");
            }
            
            $generated[] = [
                'barcode_id' => $pdo->lastInsertId(),
                'barcode_value' => $barcode_value
            ];
        }
        
        $pdo->commit();
        logUserActivity($_SESSION['user_id'], 'BULK_GENERATE_BARCODE', "Sinh $quantity mã vạch cho sản phẩm: {$product['product_name']} (ID: $product_id)");
        
        echo json_encode([
            'success' => true,
            'message' => "Sinh thành công $quantity mã vạch",
            'data' => $generated
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Lỗi bulkGenerateBarcodes: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi sinh mã vạch hàng loạt: ' . $e->getMessage()]);
    }
}

?>