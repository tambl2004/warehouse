<?php
session_start();
require_once '../config/connect.php';
require_once '../inc/auth.php';
require_once '../inc/security.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'scan_for_import':
            scanForImport();
            break;
        case 'scan_for_export':
            scanForExport();
            break;
        case 'process_import':
            processImport();
            break;
        case 'process_export':
            processExport();
            break;
        case 'inventory_check':
            inventoryCheck();
            break;
        case 'get_product_locations':
            getProductLocations();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log('Lỗi inventory barcode API: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

// Quét barcode cho nhập kho
function scanForImport() {
    global $pdo;
    
    $barcode_value = cleanInput($_POST['barcode_value'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $shelf_id = (int)($_POST['shelf_id'] ?? 0);
    $lot_number = cleanInput($_POST['lot_number'] ?? '');
    $expiry_date = $_POST['expiry_date'] ?? null;
    
    if (empty($barcode_value)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng quét mã vạch']);
        return;
    }
    
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Số lượng phải lớn hơn 0']);
        return;
    }
    
    if ($shelf_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn kệ lưu trữ']);
        return;
    }
    
    // Kiểm tra mã vạch tồn tại
    $barcodeStmt = $pdo->prepare("
        SELECT b.*, p.product_name, p.sku, p.stock_quantity, p.volume 
        FROM barcodes b 
        JOIN products p ON b.product_id = p.product_id 
        WHERE b.barcode_value = ?
    ");
    $barcodeStmt->execute([$barcode_value]);
    $barcode = $barcodeStmt->fetch();
    
    if (!$barcode) {
        // Ghi log quét thất bại
        logScanAttempt(null, $barcode_value, 'failed', 'Mã vạch không tồn tại', 'import');
        
        echo json_encode([
            'success' => false, 
            'message' => 'Mã vạch không tồn tại trong hệ thống',
            'suggest_create' => true,
            'barcode_value' => $barcode_value
        ]);
        return;
    }
    
    // Kiểm tra kệ tồn tại và sức chứa
    $shelfStmt = $pdo->prepare("
        SELECT shelf_id, shelf_code, max_capacity, current_capacity, area_id 
        FROM shelves 
        WHERE shelf_id = ?
    ");
    $shelfStmt->execute([$shelf_id]);
    $shelf = $shelfStmt->fetch();
    
    if (!$shelf) {
        echo json_encode(['success' => false, 'message' => 'Kệ không tồn tại']);
        return;
    }
    
    // Tính toán thể tích cần thêm
    $volumeToAdd = $barcode['volume'] * $quantity;
    $newCapacity = $shelf['current_capacity'] + $volumeToAdd;
    
    if ($newCapacity > $shelf['max_capacity']) {
        echo json_encode([
            'success' => false, 
            'message' => "Kệ {$shelf['shelf_code']} không đủ chỗ. Sức chứa hiện tại: {$shelf['current_capacity']}/{$shelf['max_capacity']} dm³"
        ]);
        return;
    }
    
    // Ghi log quét thành công
    logScanAttempt($barcode['barcode_id'], $barcode_value, 'success', "Nhập kho: {$quantity} sản phẩm vào kệ {$shelf['shelf_code']}", 'import');
    
    echo json_encode([
        'success' => true,
        'message' => 'Quét mã vạch thành công, sẵn sàng nhập kho',
        'data' => [
            'barcode' => $barcode,
            'shelf' => $shelf,
            'quantity' => $quantity,
            'volume_impact' => $volumeToAdd,
            'new_capacity' => $newCapacity
        ]
    ]);
}

// Quét barcode cho xuất kho
function scanForExport() {
    global $pdo;
    
    $barcode_value = cleanInput($_POST['barcode_value'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $shelf_id = (int)($_POST['shelf_id'] ?? 0);
    
    if (empty($barcode_value)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng quét mã vạch']);
        return;
    }
    
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Số lượng phải lớn hơn 0']);
        return;
    }
    
    // Kiểm tra mã vạch tồn tại
    $barcodeStmt = $pdo->prepare("
        SELECT b.*, p.product_name, p.sku, p.stock_quantity, p.volume 
        FROM barcodes b 
        JOIN products p ON b.product_id = p.product_id 
        WHERE b.barcode_value = ?
    ");
    $barcodeStmt->execute([$barcode_value]);
    $barcode = $barcodeStmt->fetch();
    
    if (!$barcode) {
        logScanAttempt(null, $barcode_value, 'failed', 'Mã vạch không tồn tại', 'export');
        echo json_encode(['success' => false, 'message' => 'Mã vạch không tồn tại trong hệ thống']);
        return;
    }
    
    // Kiểm tra tồn kho
    if ($barcode['stock_quantity'] < $quantity) {
        echo json_encode([
            'success' => false, 
            'message' => "Không đủ hàng trong kho. Tồn kho hiện tại: {$barcode['stock_quantity']}"
        ]);
        return;
    }
    
    // Lấy danh sách vị trí lưu trữ sản phẩm
    $locationStmt = $pdo->prepare("
        SELECT pl.*, s.shelf_code, s.area_id, wa.area_name 
        FROM product_locations pl 
        JOIN shelves s ON pl.shelf_id = s.shelf_id 
        JOIN warehouse_areas wa ON s.area_id = wa.area_id 
        WHERE pl.product_id = ? AND pl.quantity > 0
        ORDER BY pl.quantity DESC
    ");
    $locationStmt->execute([$barcode['product_id']]);
    $locations = $locationStmt->fetchAll();
    
    if (empty($locations)) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm trong kho']);
        return;
    }
    
    // Nếu người dùng chỉ định kệ cụ thể
    if ($shelf_id > 0) {
        $specificLocation = array_filter($locations, function($loc) use ($shelf_id) {
            return $loc['shelf_id'] == $shelf_id;
        });
        
        if (empty($specificLocation)) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không có trong kệ được chỉ định']);
            return;
        }
        
        $location = array_shift($specificLocation);
        if ($location['quantity'] < $quantity) {
            echo json_encode([
                'success' => false, 
                'message' => "Kệ {$location['shelf_code']} chỉ có {$location['quantity']} sản phẩm"
            ]);
            return;
        }
        
        $locations = [$location];
    }
    
    // Ghi log quét thành công
    logScanAttempt($barcode['barcode_id'], $barcode_value, 'success', "Xuất kho: {$quantity} sản phẩm", 'export');
    
    echo json_encode([
        'success' => true,
        'message' => 'Quét mã vạch thành công, sẵn sàng xuất kho',
        'data' => [
            'barcode' => $barcode,
            'locations' => $locations,
            'quantity' => $quantity
        ]
    ]);
}

// Xử lý nhập kho
function processImport() {
    global $pdo;
    
    $barcode_value = cleanInput($_POST['barcode_value'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $shelf_id = (int)($_POST['shelf_id'] ?? 0);
    $lot_number = cleanInput($_POST['lot_number'] ?? '');
    $expiry_date = $_POST['expiry_date'] ?? null;
    $unit_price = (float)($_POST['unit_price'] ?? 0);
    
    $pdo->beginTransaction();
    
    try {
        // Lấy thông tin mã vạch và sản phẩm
        $barcodeStmt = $pdo->prepare("
            SELECT b.*, p.product_id, p.volume 
            FROM barcodes b 
            JOIN products p ON b.product_id = p.product_id 
            WHERE b.barcode_value = ?
        ");
        $barcodeStmt->execute([$barcode_value]);
        $barcode = $barcodeStmt->fetch();
        
        if (!$barcode) {
            throw new Exception('Mã vạch không tồn tại');
        }
        
        // Cập nhật tồn kho sản phẩm
        $updateProductStmt = $pdo->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity + ? 
            WHERE product_id = ?
        ");
        $updateProductStmt->execute([$quantity, $barcode['product_id']]);
        
        // Cập nhật vị trí sản phẩm trong kệ
        $checkLocationStmt = $pdo->prepare("
            SELECT quantity FROM product_locations 
            WHERE product_id = ? AND shelf_id = ?
        ");
        $checkLocationStmt->execute([$barcode['product_id'], $shelf_id]);
        $existingLocation = $checkLocationStmt->fetch();
        
        if ($existingLocation) {
            // Cập nhật số lượng hiện có
            $updateLocationStmt = $pdo->prepare("
                UPDATE product_locations 
                SET quantity = quantity + ?, last_updated = NOW() 
                WHERE product_id = ? AND shelf_id = ?
            ");
            $updateLocationStmt->execute([$quantity, $barcode['product_id'], $shelf_id]);
        } else {
            // Thêm vị trí mới
            $insertLocationStmt = $pdo->prepare("
                INSERT INTO product_locations (product_id, shelf_id, quantity, last_updated) 
                VALUES (?, ?, ?, NOW())
            ");
            $insertLocationStmt->execute([$barcode['product_id'], $shelf_id, $quantity]);
        }
        
        // Cập nhật sức chứa kệ
        $volumeToAdd = $barcode['volume'] * $quantity;
        $updateShelfStmt = $pdo->prepare("
            UPDATE shelves 
            SET current_capacity = current_capacity + ? 
            WHERE shelf_id = ?
        ");
        $updateShelfStmt->execute([$volumeToAdd, $shelf_id]);
        
        // Ghi lịch sử di chuyển
        $insertHistoryStmt = $pdo->prepare("
            INSERT INTO shelf_product_history (product_id, shelf_id, quantity, moved_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $insertHistoryStmt->execute([$barcode['product_id'], $shelf_id, $quantity]);
        
        // Cập nhật thông tin barcode nếu có
        if ($lot_number || $expiry_date) {
            $updateBarcodeStmt = $pdo->prepare("
                UPDATE barcodes 
                SET lot_number = COALESCE(?, lot_number), 
                    expiry_date = COALESCE(?, expiry_date),
                    updated_at = NOW() 
                WHERE barcode_id = ?
            ");
            $updateBarcodeStmt->execute([$lot_number, $expiry_date, $barcode['barcode_id']]);
        }
        
        $pdo->commit();
        
        // Ghi log
        logUserActivity($_SESSION['user_id'], 'BARCODE_IMPORT', "Nhập kho qua barcode: {$barcode_value}, SL: {$quantity}");
        
        echo json_encode([
            'success' => true,
            'message' => "Nhập kho thành công {$quantity} sản phẩm"
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Xử lý xuất kho
function processExport() {
    global $pdo;
    
    $barcode_value = cleanInput($_POST['barcode_value'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $shelf_selections = json_decode($_POST['shelf_selections'] ?? '[]', true);
    $destination = cleanInput($_POST['destination'] ?? '');
    
    $pdo->beginTransaction();
    
    try {
        // Lấy thông tin mã vạch và sản phẩm
        $barcodeStmt = $pdo->prepare("
            SELECT b.*, p.product_id, p.volume 
            FROM barcodes b 
            JOIN products p ON b.product_id = p.product_id 
            WHERE b.barcode_value = ?
        ");
        $barcodeStmt->execute([$barcode_value]);
        $barcode = $barcodeStmt->fetch();
        
        if (!$barcode) {
            throw new Exception('Mã vạch không tồn tại');
        }
        
        $totalExported = 0;
        $volumeToRemove = 0;
        
        // Xử lý xuất kho từ các kệ được chọn
        foreach ($shelf_selections as $selection) {
            $shelf_id = (int)$selection['shelf_id'];
            $export_quantity = (int)$selection['quantity'];
            
            if ($export_quantity <= 0) continue;
            
            // Kiểm tra số lượng có sẵn trong kệ
            $checkStmt = $pdo->prepare("
                SELECT quantity FROM product_locations 
                WHERE product_id = ? AND shelf_id = ?
            ");
            $checkStmt->execute([$barcode['product_id'], $shelf_id]);
            $available = $checkStmt->fetchColumn();
            
            if (!$available || $available < $export_quantity) {
                throw new Exception("Kệ không đủ sản phẩm để xuất");
            }
            
            // Cập nhật số lượng trong kệ
            $updateLocationStmt = $pdo->prepare("
                UPDATE product_locations 
                SET quantity = quantity - ?, last_updated = NOW() 
                WHERE product_id = ? AND shelf_id = ?
            ");
            $updateLocationStmt->execute([$export_quantity, $barcode['product_id'], $shelf_id]);
            
            // Xóa record nếu quantity = 0
            $cleanupStmt = $pdo->prepare("
                DELETE FROM product_locations 
                WHERE product_id = ? AND shelf_id = ? AND quantity <= 0
            ");
            $cleanupStmt->execute([$barcode['product_id'], $shelf_id]);
            
            // Cập nhật sức chứa kệ
            $volumeReduced = $barcode['volume'] * $export_quantity;
            $updateShelfStmt = $pdo->prepare("
                UPDATE shelves 
                SET current_capacity = current_capacity - ? 
                WHERE shelf_id = ?
            ");
            $updateShelfStmt->execute([$volumeReduced, $shelf_id]);
            
            // Ghi lịch sử di chuyển
            $insertHistoryStmt = $pdo->prepare("
                INSERT INTO shelf_product_history (product_id, shelf_id, quantity, moved_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $insertHistoryStmt->execute([$barcode['product_id'], $shelf_id, -$export_quantity]);
            
            $totalExported += $export_quantity;
            $volumeToRemove += $volumeReduced;
        }
        
        if ($totalExported != $quantity) {
            throw new Exception("Số lượng xuất không khớp: {$totalExported}/{$quantity}");
        }
        
        // Cập nhật tồn kho sản phẩm
        $updateProductStmt = $pdo->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity - ? 
            WHERE product_id = ?
        ");
        $updateProductStmt->execute([$quantity, $barcode['product_id']]);
        
        $pdo->commit();
        
        // Ghi log
        logUserActivity($_SESSION['user_id'], 'BARCODE_EXPORT', "Xuất kho qua barcode: {$barcode_value}, SL: {$quantity}, Đích: {$destination}");
        
        echo json_encode([
            'success' => true,
            'message' => "Xuất kho thành công {$quantity} sản phẩm"
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Kiểm kê với barcode
function inventoryCheck() {
    global $pdo;
    
    $area_id = (int)($_POST['area_id'] ?? 0);
    $scanned_barcodes = json_decode($_POST['scanned_barcodes'] ?? '[]', true);
    
    if (empty($scanned_barcodes)) {
        echo json_encode(['success' => false, 'message' => 'Chưa có dữ liệu quét để kiểm kê']);
        return;
    }
    
    try {
        // Tạo phiên kiểm kê
        $createCheckStmt = $pdo->prepare("
            INSERT INTO inventory_checks (area_id, created_by, status, created_at) 
            VALUES (?, ?, 'pending', NOW())
        ");
        $createCheckStmt->execute([$area_id, $_SESSION['user_id']]);
        $check_id = $pdo->lastInsertId();
        
        $discrepancies = [];
        
        foreach ($scanned_barcodes as $scan) {
            $barcode_value = $scan['barcode_value'];
            $actual_quantity = (int)$scan['quantity'];
            $shelf_id = (int)$scan['shelf_id'];
            
            // Lấy thông tin mã vạch và số lượng hệ thống
            $barcodeStmt = $pdo->prepare("
                SELECT b.*, p.product_name, p.stock_quantity,
                       pl.quantity as shelf_quantity 
                FROM barcodes b 
                JOIN products p ON b.product_id = p.product_id 
                LEFT JOIN product_locations pl ON p.product_id = pl.product_id AND pl.shelf_id = ?
                WHERE b.barcode_value = ?
            ");
            $barcodeStmt->execute([$shelf_id, $barcode_value]);
            $barcode = $barcodeStmt->fetch();
            
            if (!$barcode) {
                $discrepancies[] = [
                    'barcode_value' => $barcode_value,
                    'type' => 'not_found',
                    'message' => 'Mã vạch không tồn tại trong hệ thống'
                ];
                continue;
            }
            
            $system_quantity = (int)($barcode['shelf_quantity'] ?? 0);
            
            if ($actual_quantity != $system_quantity) {
                $discrepancies[] = [
                    'barcode_value' => $barcode_value,
                    'product_name' => $barcode['product_name'],
                    'shelf_id' => $shelf_id,
                    'type' => 'quantity_mismatch',
                    'system_quantity' => $system_quantity,
                    'actual_quantity' => $actual_quantity,
                    'difference' => $actual_quantity - $system_quantity
                ];
                
                // Tạo cảnh báo
                $alertStmt = $pdo->prepare("
                    INSERT INTO alerts (alert_type, product_id, description, created_at) 
                    VALUES ('rfid_error', ?, ?, NOW())
                ");
                $alertStmt->execute([
                    $barcode['product_id'],
                    "Chênh lệch kiểm kê: Hệ thống {$system_quantity}, Thực tế {$actual_quantity}"
                ]);
            }
        }
        
        // Cập nhật trạng thái kiểm kê
        $status = empty($discrepancies) ? 'completed' : 'failed';
        $updateCheckStmt = $pdo->prepare("
            UPDATE inventory_checks 
            SET status = ?, updated_at = NOW() 
            WHERE check_id = ?
        ");
        $updateCheckStmt->execute([$status, $check_id]);
        
        // Ghi log
        logUserActivity($_SESSION['user_id'], 'INVENTORY_CHECK', "Kiểm kê barcode khu vực {$area_id}, phát hiện " . count($discrepancies) . " chênh lệch");
        
        echo json_encode([
            'success' => true,
            'message' => 'Hoàn thành kiểm kê',
            'check_id' => $check_id,
            'discrepancies' => $discrepancies,
            'total_scanned' => count($scanned_barcodes),
            'total_discrepancies' => count($discrepancies)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Lấy vị trí sản phẩm
function getProductLocations() {
    global $pdo;
    
    $product_id = (int)($_GET['product_id'] ?? 0);
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT pl.*, s.shelf_code, s.area_id, wa.area_name 
            FROM product_locations pl 
            JOIN shelves s ON pl.shelf_id = s.shelf_id 
            JOIN warehouse_areas wa ON s.area_id = wa.area_id 
            WHERE pl.product_id = ? AND pl.quantity > 0
            ORDER BY pl.quantity DESC
        ");
        $stmt->execute([$product_id]);
        $locations = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $locations
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Ghi log quét mã vạch
function logScanAttempt($barcode_id, $barcode_value, $result, $description, $context = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO barcode_scan_logs (barcode_id, user_id, scan_result, description, created_at, updated_at) 
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$barcode_id, $_SESSION['user_id'], $result, $context . ': ' . $description]);
    } catch (Exception $e) {
        error_log('Lỗi ghi log quét barcode: ' . $e->getMessage());
    }
}
?> 