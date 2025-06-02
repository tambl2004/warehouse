<?php
session_start();
require_once '../config/connect.php';
require_once '../inc/security.php';
require_once '../inc/auth.php';

// Kiểm tra quyền truy cập
checkUserPermission(['admin', 'employee']);

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    switch ($action) {
        case 'get_stats':
            $response = getInventoryStats($pdo);
            break;
            
        case 'get_areas':
            $response = getWarehouseAreas($pdo);
            break;
            
        case 'get_shelves':
            $area_id = $_GET['area_id'] ?? '';
            $response = getShelvesByArea($pdo, $area_id);
            break;
            
        case 'get_rfid_devices':
            $response = getRFIDDevices($pdo);
            break;
            
        case 'start_inventory_check':
            $data = json_decode(file_get_contents('php://input'), true);
            $response = startInventoryCheck($pdo, $data);
            break;
            
        case 'scan_rfid':
            $data = json_decode(file_get_contents('php://input'), true);
            $response = scanRFID($pdo, $data);
            break;
            
        case 'scan_barcode':
            $data = json_decode(file_get_contents('php://input'), true);
            $response = scanBarcode($pdo, $data);
            break;
            
        case 'get_product_by_rfid':
            $rfid_value = $_GET['rfid_value'] ?? '';
            $response = getProductByRFID($pdo, $rfid_value);
            break;
            
        case 'get_product_by_barcode':
            $barcode_value = $_GET['barcode_value'] ?? '';
            $response = getProductByBarcode($pdo, $barcode_value);
            break;
            
        case 'get_scan_results':
            $check_id = $_GET['check_id'] ?? '';
            $response = getScanResults($pdo, $check_id);
            break;
            
        case 'get_discrepancies':
            $check_id = $_GET['check_id'] ?? '';
            $response = getDiscrepancies($pdo, $check_id);
            break;
            
        case 'adjust_stock':
            $data = json_decode(file_get_contents('php://input'), true);
            $response = adjustStock($pdo, $data);
            break;
            
        case 'approve_adjustments':
            $data = json_decode(file_get_contents('php://input'), true);
            $response = approveAdjustments($pdo, $data);
            break;
            
        case 'get_inventory_history':
            $filters = $_GET;
            $response = getInventoryHistory($pdo, $filters);
            break;
            
        case 'export_results':
            $check_id = $_GET['check_id'] ?? '';
            $response = exportInventoryResults($pdo, $check_id);
            break;
            
        default:
            $response['message'] = 'Hành động không hợp lệ';
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
    error_log("Inventory Check Error: " . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

/**
 * Lấy thống kê tổng quan kiểm kê
 */
function getInventoryStats($pdo) {
    try {
        // Tổng số sản phẩm trong kho
        $stmt = $pdo->query("
            SELECT COUNT(*) as total_products,
                   SUM(stock_quantity) as total_stock
            FROM products 
            WHERE status = 'in_stock'
        ");
        $totalStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Số phiên kiểm kê đang hoạt động
        $stmt = $pdo->query("
            SELECT COUNT(*) as active_checks
            FROM inventory_checks 
            WHERE status = 'pending'
        ");
        $activeChecks = $stmt->fetchColumn();
        
        // Số sản phẩm có chênh lệch (giả lập - cần tính toán thực tế)
        $discrepancies = 0;
        
        // Số sản phẩm đã kiểm tra hôm nay
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT product_id) as checked_today
            FROM (
                SELECT DISTINCT rt.product_id
                FROM rfid_scan_logs rsl
                JOIN rfid_tags rt ON rsl.rfid_id = rt.rfid_id
                WHERE DATE(rsl.scan_time) = CURDATE()
                AND rsl.scan_result = 'success'
                
                UNION
                
                SELECT DISTINCT b.product_id
                FROM barcode_scan_logs bsl
                JOIN barcodes b ON bsl.barcode_id = b.barcode_id
                WHERE DATE(bsl.scan_time) = CURDATE()
                AND bsl.scan_result = 'success'
            ) as checked_products
        ");
        $checkedToday = $stmt->fetchColumn();
        
        return [
            'success' => true,
            'data' => [
                'total_products' => (int)$totalStats['total_products'],
                'total_stock' => (int)$totalStats['total_stock'],
                'checked_products' => (int)$checkedToday,
                'discrepancies' => (int)$discrepancies,
                'active_checks' => (int)$activeChecks
            ]
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Lấy danh sách khu vực kho
 */
function getWarehouseAreas($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT area_id, area_name, description
            FROM warehouse_areas
            ORDER BY area_name
        ");
        $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $areas
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Lấy danh sách kệ theo khu vực
 */
function getShelvesByArea($pdo, $area_id = '') {
    try {
        $sql = "
            SELECT s.shelf_id, s.shelf_code, s.max_capacity, s.current_capacity,
                   wa.area_name
            FROM shelves s
            JOIN warehouse_areas wa ON s.area_id = wa.area_id
        ";
        
        $params = [];
        if (!empty($area_id)) {
            $sql .= " WHERE s.area_id = ?";
            $params[] = $area_id;
        }
        
        $sql .= " ORDER BY wa.area_name, s.shelf_code";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $shelves = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $shelves
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Lấy danh sách thiết bị RFID
 */
function getRFIDDevices($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT rd.device_id, rd.device_name, rd.status, rd.battery_level,
                   wa.area_name
            FROM rfid_devices rd
            LEFT JOIN warehouse_areas wa ON rd.area_id = wa.area_id
            ORDER BY rd.device_name
        ");
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $devices
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Bắt đầu phiên kiểm kê mới
 */
function startInventoryCheck($pdo, $data) {
    try {
        $pdo->beginTransaction();
        
        $area_id = $data['area_id'] ?? null;
        $use_rfid = $data['use_rfid'] ?? false;
        $use_barcode = $data['use_barcode'] ?? false;
        $notes = $data['notes'] ?? '';
        $user_id = $_SESSION['user_id'];
        
        // Tạo phiên kiểm kê mới
        $stmt = $pdo->prepare("
            INSERT INTO inventory_checks (area_id, created_by, status, notes)
            VALUES (?, ?, 'pending', ?)
        ");
        $stmt->execute([$area_id, $user_id, $notes]);
        
        $check_id = $pdo->lastInsertId();
        
        // Ghi log người dùng
        logUserActivity($pdo, $user_id, 'CREATE_INVENTORY_CHECK', 
                       "Tạo phiên kiểm kê mới ID: $check_id cho khu vực: $area_id");
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Tạo phiên kiểm kê thành công',
            'data' => ['check_id' => $check_id]
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Quét RFID và so sánh với tồn kho
 */
function scanRFID($pdo, $data) {
    try {
        $rfid_value = $data['rfid_value'] ?? '';
        $check_id = $data['check_id'] ?? '';
        $user_id = $_SESSION['user_id'];
        
        // Tìm thông tin RFID tag
        $stmt = $pdo->prepare("
            SELECT rt.rfid_id, rt.product_id, rt.shelf_id, rt.lot_number,
                   p.product_name, p.sku, p.stock_quantity, p.unit_price,
                   s.shelf_code,
                   c.category_name
            FROM rfid_tags rt
            JOIN products p ON rt.product_id = p.product_id
            LEFT JOIN shelves s ON rt.shelf_id = s.shelf_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE rt.rfid_value = ?
        ");
        $stmt->execute([$rfid_value]);
        $rfid_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rfid_info) {
            // Ghi log scan thất bại
            return [
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm với RFID này',
                'data' => ['rfid_value' => $rfid_value]
            ];
        }
        
        // Đếm số lượng thực tế (giả lập - trong thực tế sẽ đọc từ thiết bị RFID)
        $actual_quantity = 1; // Mỗi lần quét được 1 sản phẩm
        
        // So sánh với tồn kho hệ thống
        $system_quantity = (int)$rfid_info['stock_quantity'];
        $difference = $actual_quantity - $system_quantity;
        
        $scan_result = ($difference == 0) ? 'success' : 'failed';
        $status = ($difference == 0) ? 'match' : ($difference > 0 ? 'excess' : 'shortage');
        
        // Ghi log scan
        $stmt = $pdo->prepare("
            INSERT INTO rfid_scan_logs (rfid_id, user_id, scan_result, description)
            VALUES (?, ?, ?, ?)
        ");
        $description = "Kiểm kê: SL hệ thống = $system_quantity, SL thực tế = $actual_quantity";
        $stmt->execute([$rfid_info['rfid_id'], $user_id, $scan_result, $description]);
        
        // Nếu có chênh lệch, tạo cảnh báo
        if ($difference != 0) {
            $stmt = $pdo->prepare("
                INSERT INTO alerts (alert_type, product_id, description)
                VALUES ('inventory_discrepancy', ?, ?)
            ");
            $alert_desc = "Chênh lệch tồn kho sản phẩm {$rfid_info['product_name']}: " .
                         "Hệ thống $system_quantity, Thực tế $actual_quantity";
            $stmt->execute([$rfid_info['product_id'], $alert_desc]);
        }
        
        return [
            'success' => true,
            'message' => 'Quét RFID thành công',
            'data' => [
                'rfid_value' => $rfid_value,
                'product_info' => $rfid_info,
                'system_quantity' => $system_quantity,
                'actual_quantity' => $actual_quantity,
                'difference' => $difference,
                'status' => $status
            ]
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Quét Barcode và so sánh với tồn kho
 */
function scanBarcode($pdo, $data) {
    try {
        $barcode_value = $data['barcode_value'] ?? '';
        $check_id = $data['check_id'] ?? '';
        $quantity = $data['quantity'] ?? 1; // Số lượng quét được
        $user_id = $_SESSION['user_id'];
        
        // Tìm thông tin barcode
        $stmt = $pdo->prepare("
            SELECT b.barcode_id, b.product_id, b.lot_number, b.expiry_date,
                   p.product_name, p.sku, p.stock_quantity, p.unit_price,
                   c.category_name
            FROM barcodes b
            JOIN products p ON b.product_id = p.product_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE b.barcode_value = ?
        ");
        $stmt->execute([$barcode_value]);
        $barcode_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$barcode_info) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm với barcode này',
                'data' => ['barcode_value' => $barcode_value]
            ];
        }
        
        // So sánh với tồn kho hệ thống
        $system_quantity = (int)$barcode_info['stock_quantity'];
        $actual_quantity = (int)$quantity;
        $difference = $actual_quantity - $system_quantity;
        
        $scan_result = 'success'; // Quét thành công
        $status = ($difference == 0) ? 'match' : ($difference > 0 ? 'excess' : 'shortage');
        
        // Ghi log scan
        $stmt = $pdo->prepare("
            INSERT INTO barcode_scan_logs (barcode_id, user_id, scan_result, description)
            VALUES (?, ?, ?, ?)
        ");
        $description = "Kiểm kê: SL hệ thống = $system_quantity, SL thực tế = $actual_quantity";
        $stmt->execute([$barcode_info['barcode_id'], $user_id, $scan_result, $description]);
        
        // Nếu có chênh lệch, tạo cảnh báo
        if ($difference != 0) {
            $stmt = $pdo->prepare("
                INSERT INTO alerts (alert_type, product_id, description)
                VALUES ('inventory_discrepancy', ?, ?)
            ");
            $alert_desc = "Chênh lệch tồn kho sản phẩm {$barcode_info['product_name']}: " .
                         "Hệ thống $system_quantity, Thực tế $actual_quantity";
            $stmt->execute([$barcode_info['product_id'], $alert_desc]);
        }
        
        return [
            'success' => true,
            'message' => 'Quét barcode thành công',
            'data' => [
                'barcode_value' => $barcode_value,
                'product_info' => $barcode_info,
                'system_quantity' => $system_quantity,
                'actual_quantity' => $actual_quantity,
                'difference' => $difference,
                'status' => $status
            ]
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Lấy thông tin sản phẩm theo RFID
 */
function getProductByRFID($pdo, $rfid_value) {
    try {
        $stmt = $pdo->prepare("
            SELECT rt.rfid_id, rt.rfid_value, rt.lot_number, rt.expiry_date,
                   p.product_id, p.product_name, p.sku, p.stock_quantity,
                   s.shelf_code,
                   wa.area_name
            FROM rfid_tags rt
            JOIN products p ON rt.product_id = p.product_id
            LEFT JOIN shelves s ON rt.shelf_id = s.shelf_id
            LEFT JOIN warehouse_areas wa ON s.area_id = wa.area_id
            WHERE rt.rfid_value = ?
        ");
        $stmt->execute([$rfid_value]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $product
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Lấy thông tin sản phẩm theo Barcode
 */
function getProductByBarcode($pdo, $barcode_value) {
    try {
        $stmt = $pdo->prepare("
            SELECT b.barcode_id, b.barcode_value, b.lot_number, b.expiry_date,
                   p.product_id, p.product_name, p.sku, p.stock_quantity
            FROM barcodes b
            JOIN products p ON b.product_id = p.product_id
            WHERE b.barcode_value = ?
        ");
        $stmt->execute([$barcode_value]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $product
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Điều chỉnh tồn kho dựa trên kết quả kiểm kê
 */
function adjustStock($pdo, $data) {
    try {
        $pdo->beginTransaction();
        
        $product_id = $data['product_id'];
        $shelf_id = $data['shelf_id'] ?? null;
        $system_qty = $data['system_qty'];
        $actual_qty = $data['actual_qty'];
        $difference = $actual_qty - $system_qty;
        $reason = $data['reason'];
        $adjust_type = $data['adjust_type'];
        $user_id = $_SESSION['user_id'];
        
        // Cập nhật tồn kho sản phẩm
        $stmt = $pdo->prepare("
            UPDATE products 
            SET stock_quantity = ?, updated_at = CURRENT_TIMESTAMP
            WHERE product_id = ?
        ");
        $stmt->execute([$actual_qty, $product_id]);
        
        // Ghi log điều chỉnh
        $stmt = $pdo->prepare("
            INSERT INTO inventory_adjustments 
            (product_id, shelf_id, old_quantity, new_quantity, difference, 
             reason, adjust_type, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            $product_id, $shelf_id, $system_qty, $actual_qty, 
            $difference, $reason, $adjust_type, $user_id
        ]);
        
        // Ghi log người dùng
        $stmt = $pdo->prepare("SELECT product_name FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $product_name = $stmt->fetchColumn();
        
        logUserActivity($pdo, $user_id, 'ADJUST_STOCK', 
                       "Điều chỉnh tồn kho sản phẩm $product_name: $system_qty → $actual_qty");
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Điều chỉnh tồn kho thành công'
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Lấy lịch sử kiểm kê
 */
function getInventoryHistory($pdo, $filters) {
    try {
        $sql = "
            SELECT ic.check_id, ic.check_date, ic.status,
                   wa.area_name,
                   u.full_name as creator_name,
                   COUNT(DISTINCT rsl.scan_id) + COUNT(DISTINCT bsl.scan_id) as total_scans
            FROM inventory_checks ic
            LEFT JOIN warehouse_areas wa ON ic.area_id = wa.area_id
            LEFT JOIN users u ON ic.created_by = u.user_id
            LEFT JOIN rfid_scan_logs rsl ON DATE(rsl.scan_time) = DATE(ic.check_date)
            LEFT JOIN barcode_scan_logs bsl ON DATE(bsl.scan_time) = DATE(ic.check_date)
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(ic.check_date) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(ic.check_date) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " GROUP BY ic.check_id ORDER BY ic.check_date DESC LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $history
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Ghi log hoạt động người dùng
 */

?> 