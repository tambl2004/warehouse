<?php
/**
 * API Handler cho quản lý xuất kho
 * File: api/export_handler.php
 */

session_start();
require_once '../config/connect.php';
require_once '../inc/auth.php';
require_once '../inc/security.php';

header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get_statistics':
            getExportStatistics();
            break;
        case 'get_exports':
            getExportOrders();
            break;
        case 'get_export_detail':
            getExportDetail();
            break;
        case 'create_export':
            createExportOrder();
            break;
        case 'approve_export':
            approveExport();
            break;
        case 'reject_export':
            rejectExport();
            break;
        case 'generate_export_code':
            generateExportCode();
            break;
        case 'get_products':
            getProducts();
            break;
        case 'get_product_shelves':
            getProductShelves();
            break;
        case 'check_stock':
            checkStock();
            break;
        case 'export_pdf':
            exportToPDF();
            break;
        case 'export_excel':
            exportToExcel();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
    }
} catch (Exception $e) {
    error_log("Export API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

/**
 * Lấy thống kê xuất kho
 */
function getExportStatistics() {
    global $pdo;
    
    try {
        $stats = [];
        
        // Tổng số phiếu xuất
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM export_orders");
        $stats['total_exports'] = $stmt->fetchColumn();
        
        // Phiếu chờ duyệt
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM export_orders WHERE status = 'pending'");
        $stats['pending_exports'] = $stmt->fetchColumn();
        
        // Phiếu đã duyệt
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM export_orders WHERE status = 'approved'");
        $stats['approved_exports'] = $stmt->fetchColumn();
        
        // Tổng giá trị xuất
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(ed.quantity * ed.unit_price), 0) as total_value
            FROM export_orders eo
            JOIN export_details ed ON eo.export_id = ed.export_id
            WHERE eo.status = 'approved'
        ");
        $stats['total_value'] = $stmt->fetchColumn();
        
        echo json_encode(['success' => true, 'data' => $stats]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thống kê: ' . $e->getMessage()]);
    }
}

/**
 * Lấy danh sách phiếu xuất với phân trang và lọc
 */
function getExportOrders() {
    global $pdo;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $search = cleanInput($_GET['search'] ?? '');
    $status = cleanInput($_GET['status'] ?? '');
    $from_date = cleanInput($_GET['from_date'] ?? '');
    $to_date = cleanInput($_GET['to_date'] ?? '');
    
    $offset = ($page - 1) * $limit;
    
    // Xây dựng câu truy vấn WHERE
    $where_conditions = ['1=1'];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(eo.export_code LIKE ? OR eo.destination LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($status)) {
        $where_conditions[] = "eo.status = ?";
        $params[] = $status;
    }
    
    if (!empty($from_date)) {
        $where_conditions[] = "DATE(eo.export_date) >= ?";
        $params[] = $from_date;
    }
    
    if (!empty($to_date)) {
        $where_conditions[] = "DATE(eo.export_date) <= ?";
        $params[] = $to_date;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Đếm tổng số bản ghi
    $count_sql = "
        SELECT COUNT(*) 
        FROM export_orders eo
        WHERE $where_clause
    ";
    
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    
    // Lấy dữ liệu với phân trang
    $data_sql = "
        SELECT 
            eo.*,
            u.full_name as creator_name,
            COALESCE(SUM(ed.quantity * ed.unit_price), 0) as total_value
        FROM export_orders eo
        LEFT JOIN users u ON eo.created_by = u.user_id
        LEFT JOIN export_details ed ON eo.export_id = ed.export_id
        WHERE $where_clause
        GROUP BY eo.export_id
        ORDER BY eo.export_date DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($data_sql);
    $stmt->execute($params);
    $exports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_pages = ceil($total_records / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $exports,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'limit' => $limit
        ]
    ]);
}

/**
 * Lấy chi tiết phiếu xuất
 */
function getExportDetail() {
    global $pdo;
    
    $export_id = (int)($_GET['id'] ?? 0);
    
    if ($export_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID phiếu xuất không hợp lệ']);
        return;
    }
    
    // Lấy thông tin phiếu xuất
    $export_sql = "
        SELECT 
            eo.*,
            u.full_name as creator_name,
            approver.full_name as approver_name,
            rejecter.full_name as rejecter_name,
            COALESCE(SUM(ed.quantity * ed.unit_price), 0) as total_value,
            COUNT(ed.export_detail_id) as total_items
        FROM export_orders eo
        LEFT JOIN users u ON eo.created_by = u.user_id
        LEFT JOIN users approver ON eo.approved_by = approver.user_id
        LEFT JOIN users rejecter ON eo.rejected_by = rejecter.user_id
        LEFT JOIN export_details ed ON eo.export_id = ed.export_id
        WHERE eo.export_id = ?
        GROUP BY eo.export_id
    ";
    
    $stmt = $pdo->prepare($export_sql);
    $stmt->execute([$export_id]);
    $export_order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$export_order) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu xuất']);
        return;
    }
    
    // Lấy chi tiết sản phẩm
    $details_sql = "
        SELECT 
            ed.*,
            p.product_name, p.sku,
            c.category_name,
            sh.shelf_code, wa.area_name,
            (ed.quantity * ed.unit_price) as total_amount
        FROM export_details ed
        LEFT JOIN products p ON ed.product_id = p.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN shelves sh ON ed.shelf_id = sh.shelf_id
        LEFT JOIN warehouse_areas wa ON sh.area_id = wa.area_id
        WHERE ed.export_id = ?
        ORDER BY p.product_name
    ";
    
    $stmt = $pdo->prepare($details_sql);
    $stmt->execute([$export_id]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tạo HTML hiển thị
    $html = generateExportDetailHTML($export_order, $details);
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'data' => [
            'export_order' => $export_order,
            'details' => $details
        ]
    ]);
}

/**
 * Tạo phiếu xuất mới
 */
function createExportOrder() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Validate dữ liệu đầu vào
        $export_code = cleanInput($_POST['export_code'] ?? '');
        $destination = cleanInput($_POST['destination'] ?? '');
        $notes = cleanInput($_POST['notes'] ?? ''); 
        
        // Sửa lỗi xử lý dữ liệu products
        $productsJson = $_POST['products'] ?? '[]';
        $products = json_decode($productsJson, true); 

        // Kiểm tra lỗi giải mã JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Dữ liệu sản phẩm không hợp lệ (JSON decode failed): ' . json_last_error_msg());
        }
        if (!is_array($products)) {
            $products = [];
        }

        $user_id = $_SESSION['user_id'];
        
        if (empty($export_code) || empty($destination)) {
            throw new Exception('Vui lòng nhập đầy đủ thông tin bắt buộc (Mã phiếu xuất, Đích đến)');
        }
        
        if (empty($products)) {
            throw new Exception('Vui lòng thêm ít nhất một sản phẩm vào phiếu xuất');
        }
        
        // Kiểm tra mã phiếu xuất đã tồn tại
        $check_stmt = $pdo->prepare("SELECT export_id FROM export_orders WHERE export_code = ?");
        $check_stmt->execute([$export_code]);
        if ($check_stmt->fetch()) {
            throw new Exception('Mã phiếu xuất đã tồn tại. Vui lòng tạo mã mới.');
        }
        
        // Kiểm tra tồn kho trước khi tạo phiếu
        foreach ($products as $product) {
            $product_id = (int)($product['product_id'] ?? 0);
            $quantity = (int)($product['quantity'] ?? 0);
            
            if ($product_id <= 0 || $quantity <= 0) {
                error_log("Sản phẩm không hợp lệ trong phiếu xuất: ID $product_id, Số lượng $quantity");
                continue; 
            }
            
            $stock_check = $pdo->prepare("SELECT stock_quantity, product_name FROM products WHERE product_id = ?");
            $stock_check->execute([$product_id]);
            $stock_data = $stock_check->fetch();
            
            if (!$stock_data) {
                throw new Exception("Sản phẩm có ID $product_id không tồn tại trong hệ thống.");
            }
            
            if ($stock_data['stock_quantity'] < $quantity) {
                throw new Exception("Sản phẩm '{$stock_data['product_name']}' không đủ tồn kho. Tồn kho hiện tại: {$stock_data['stock_quantity']}, yêu cầu: $quantity");
            }
        }
        
        // Tạo phiếu xuất - Đã bao gồm cột 'notes'
        $export_sql = "
            INSERT INTO export_orders (export_code, destination, created_by, notes, status, export_date)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ";
        $stmt = $pdo->prepare($export_sql);
        $stmt->execute([$export_code, $destination, $user_id, $notes]); // Truyền $notes vào đây
        
        $export_id = $pdo->lastInsertId();
        
        // Thêm chi tiết sản phẩm
        $detail_sql = "
            INSERT INTO export_details (export_id, product_id, quantity, unit_price, lot_number, shelf_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        $detail_stmt = $pdo->prepare($detail_sql);
        
        $hasValidProduct = false; 
        foreach ($products as $product) {
            $product_id = (int)($product['product_id'] ?? 0);
            $quantity = (int)($product['quantity'] ?? 0);
            $unit_price = (float)($product['unit_price'] ?? 0);
            $lot_number = cleanInput($product['lot_number'] ?? '');
            $shelf_id_input = $product['shelf_id'] ?? null;
            $shelf_id = ($shelf_id_input === '' || $shelf_id_input === null) ? null : (int)$shelf_id_input;

            if ($product_id <= 0 || $quantity <= 0 || $unit_price < 0) { 
                error_log("Bỏ qua chi tiết sản phẩm không hợp lệ: ID $product_id, SL $quantity, Giá $unit_price");
                continue;
            }
            
            $detail_stmt->execute([
                $export_id, $product_id, $quantity, $unit_price, 
                $lot_number, $shelf_id
            ]);
            $hasValidProduct = true;
        }

        if (!$hasValidProduct && empty($products)) { 
             $pdo->rollBack();
             throw new Exception('Phiếu xuất phải có ít nhất một sản phẩm hợp lệ.');
        }
        
        $pdo->commit();
        if (function_exists('logUserActivity')) {
            logUserActivity($user_id, 'CREATE_EXPORT_ORDER', "Tạo phiếu xuất: $export_code");
        }
        echo json_encode([
            'success' => true,
            'message' => 'Tạo phiếu xuất thành công!',
            'export_id' => $export_id
        ]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { 
            $pdo->rollBack();
        }
        error_log("Lỗi khi tạo phiếu xuất (createExportOrder): " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'Lỗi khi tạo phiếu xuất: ' . $e->getMessage()]);
    }
}

/**
 * Duyệt phiếu xuất
 */
function approveExport() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        $export_id = (int)($_POST['export_id'] ?? 0);
        $user_id = $_SESSION['user_id'];
        
        if ($export_id <= 0) {
            throw new Exception('ID phiếu xuất không hợp lệ');
        }
        
        // Kiểm tra phiếu xuất tồn tại và đang chờ duyệt
        $check_stmt = $pdo->prepare("
            SELECT export_code, status 
            FROM export_orders 
            WHERE export_id = ? AND status = 'pending'
        ");
        $check_stmt->execute([$export_id]);
        $export_order = $check_stmt->fetch();
        
        if (!$export_order) {
            throw new Exception('Phiếu xuất không tồn tại hoặc đã được xử lý');
        }
        
        // Kiểm tra tồn kho lần cuối trước khi duyệt
        $details_stmt = $pdo->prepare("
            SELECT ed.*, p.stock_quantity, p.product_name 
            FROM export_details ed 
            LEFT JOIN products p ON ed.product_id = p.product_id
            WHERE ed.export_id = ?
        ");
        $details_stmt->execute([$export_id]);
        $details = $details_stmt->fetchAll();
        
        foreach ($details as $detail) {
            if ($detail['stock_quantity'] < $detail['quantity']) {
                throw new Exception("Sản phẩm {$detail['product_name']} không đủ tồn kho. Tồn kho hiện tại: {$detail['stock_quantity']}, yêu cầu: {$detail['quantity']}");
            }
        }
        
        // Cập nhật trạng thái phiếu xuất
        $update_stmt = $pdo->prepare("
            UPDATE export_orders 
            SET status = 'approved', approved_by = ?, approved_at = NOW() 
            WHERE export_id = ?
        ");
        $update_stmt->execute([$user_id, $export_id]);
        
        // Cập nhật tồn kho sản phẩm
        foreach ($details as $detail) {
            // Trừ số lượng tồn kho
            $update_stock_stmt = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - ?, updated_at = NOW()
                WHERE product_id = ?
            ");
            $update_stock_stmt->execute([$detail['quantity'], $detail['product_id']]);
            
            // Cập nhật vị trí sản phẩm trên kệ (nếu có)
            if ($detail['shelf_id']) {
                updateProductLocationOnExport($pdo, $detail['product_id'], $detail['shelf_id'], $detail['quantity']);
            }
        }
        
        $pdo->commit();
        
        // Ghi log
        logUserActivity($user_id, 'APPROVE_EXPORT', "Duyệt phiếu xuất: {$export_order['export_code']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Duyệt phiếu xuất thành công'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Từ chối phiếu xuất
 */
function rejectExport() {
    global $pdo;
    
    try {
        $export_id = (int)($_POST['export_id'] ?? 0);
        $reason = cleanInput($_POST['reason'] ?? '');
        $user_id = $_SESSION['user_id'];
        
        if ($export_id <= 0) {
            throw new Exception('ID phiếu xuất không hợp lệ');
        }
        
        if (empty($reason)) {
            throw new Exception('Vui lòng nhập lý do từ chối');
        }
        
        // Kiểm tra phiếu xuất
        $check_stmt = $pdo->prepare("
            SELECT export_code, status 
            FROM export_orders 
            WHERE export_id = ? AND status = 'pending'
        ");
        $check_stmt->execute([$export_id]);
        $export_order = $check_stmt->fetch();
        
        if (!$export_order) {
            throw new Exception('Phiếu xuất không tồn tại hoặc đã được xử lý');
        }
        
        // Cập nhật trạng thái
        $update_stmt = $pdo->prepare("
            UPDATE export_orders 
            SET status = 'rejected', rejected_by = ?, rejected_at = NOW(), rejection_reason = ?
            WHERE export_id = ?
        ");
        $update_stmt->execute([$user_id, $reason, $export_id]);
        
        // Ghi log
        logUserActivity($user_id, 'REJECT_EXPORT', "Từ chối phiếu xuất: {$export_order['export_code']} - Lý do: $reason");
        
        echo json_encode([
            'success' => true,
            'message' => 'Từ chối phiếu xuất thành công'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Tạo mã phiếu xuất tự động
 */
function generateExportCode() {
    global $pdo;
    
    $date = cleanInput($_GET['date'] ?? date('Ymd'));
    
    // Lấy số thứ tự trong ngày
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) + 1 as next_number
        FROM export_orders 
        WHERE DATE(export_date) = ? OR export_code LIKE ?
    ");
    $count_stmt->execute([
        date('Y-m-d', strtotime($date)), 
        "XUAT-$date-%"
    ]);
    $next_number = $count_stmt->fetchColumn();
    
    $export_code = sprintf("XUAT-%s-%03d", $date, $next_number);
    
    echo json_encode([
        'success' => true,
        'export_code' => $export_code
    ]);
}

/**
 * Lấy danh sách sản phẩm có tồn kho
 */
function getProducts() {
    global $pdo;
    
    $search = cleanInput($_GET['search'] ?? '');
    
    $sql = "
        SELECT p.product_id, p.product_name, p.sku, p.stock_quantity, p.unit_price, c.category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.status = 'in_stock' AND p.stock_quantity > 0
    ";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (p.product_name LIKE ? OR p.sku LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $sql .= " ORDER BY p.product_name LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $products]);
}

/**
 * Lấy danh sách kệ chứa sản phẩm
 */
function getProductShelves() {
    global $pdo;
    
    $product_id = (int)($_GET['product_id'] ?? 0);
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ']);
        return;
    }
    
    $sql = "
        SELECT 
            pl.shelf_id, 
            s.shelf_code, 
            wa.area_name,
            pl.quantity,
            CONCAT(s.shelf_code, ' (', wa.area_name, ') - SL: ', pl.quantity) as shelf_display
        FROM product_locations pl
        JOIN shelves s ON pl.shelf_id = s.shelf_id
        LEFT JOIN warehouse_areas wa ON s.area_id = wa.area_id
        WHERE pl.product_id = ? AND pl.quantity > 0
        ORDER BY s.shelf_code
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product_id]);
    $shelves = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $shelves]);
}

/**
 * Kiểm tra tồn kho
 */
function checkStock() {
    global $pdo;
    
    $product_id = (int)($_GET['product_id'] ?? 0);
    $quantity = (int)($_GET['quantity'] ?? 0);
    
    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT stock_quantity, product_name FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        return;
    }
    
    $available = $product['stock_quantity'] >= $quantity;
    
    echo json_encode([
        'success' => true,
        'available' => $available,
        'current_stock' => $product['stock_quantity'],
        'requested' => $quantity,
        'product_name' => $product['product_name']
    ]);
}

/**
 * Cập nhật vị trí sản phẩm khi xuất kho
 */
function updateProductLocationOnExport($pdo, $product_id, $shelf_id, $quantity) {
    // Lấy thông tin sản phẩm trên kệ
    $check_stmt = $pdo->prepare("
        SELECT quantity FROM product_locations 
        WHERE product_id = ? AND shelf_id = ?
    ");
    $check_stmt->execute([$product_id, $shelf_id]);
    $current_quantity = $check_stmt->fetchColumn();
    
    if ($current_quantity !== false && $current_quantity > 0) {
        if ($current_quantity <= $quantity) {
            // Xóa hoàn toàn khỏi kệ
            $delete_stmt = $pdo->prepare("
                DELETE FROM product_locations 
                WHERE product_id = ? AND shelf_id = ?
            ");
            $delete_stmt->execute([$product_id, $shelf_id]);
        } else {
            // Giảm số lượng
            $update_stmt = $pdo->prepare("
                UPDATE product_locations 
                SET quantity = quantity - ?, last_updated = NOW()
                WHERE product_id = ? AND shelf_id = ?
            ");
            $update_stmt->execute([$quantity, $product_id, $shelf_id]);
        }
        
        // Ghi lịch sử di chuyển (số lượng âm cho xuất kho)
        $history_stmt = $pdo->prepare("
            INSERT INTO shelf_product_history (product_id, shelf_id, quantity, moved_at, created_by)
            VALUES (?, ?, ?, NOW(), ?)
        ");
        $history_stmt->execute([$product_id, $shelf_id, -$quantity, $_SESSION['user_id']]);
    }
}

/**
 * Tạo HTML hiển thị chi tiết phiếu xuất
 */
function generateExportDetailHTML($export_order, $details) {
    $status_badge = '';
    switch ($export_order['status']) {
        case 'pending':
            $status_badge = '<span class="badge bg-warning">Chờ duyệt</span>';
            break;
        case 'approved':
            $status_badge = '<span class="badge bg-success">Đã duyệt</span>';
            break;
        case 'rejected':
            $status_badge = '<span class="badge bg-danger">Từ chối</span>';
            break;
    }
    
    $export_date = date('d/m/Y H:i', strtotime($export_order['export_date']));
    $total_value = number_format($export_order['total_value'], 0, ',', '.') . 'đ';
    
    $html = "
    <div class='export-detail-content'>
        <div class='row mb-4'>
            <div class='col-md-6'>
                <h6>Thông tin phiếu xuất</h6>
                <table class='table table-sm table-borderless'>
                    <tr><td width='150'><strong>Mã phiếu:</strong></td><td>{$export_order['export_code']}</td></tr>
                    <tr><td><strong>Ngày xuất:</strong></td><td>$export_date</td></tr>
                    <tr><td><strong>Đích đến:</strong></td><td>{$export_order['destination']}</td></tr>
                    <tr><td><strong>Trạng thái:</strong></td><td>$status_badge</td></tr>
                    <tr><td><strong>Người tạo:</strong></td><td>{$export_order['creator_name']}</td></tr>
                    <tr><td><strong>Tổng giá trị:</strong></td><td class='text-success fw-bold'>$total_value</td></tr>
                </table>
            </div>
            <div class='col-md-6'>
                <h6>Thông tin xử lý</h6>
                <table class='table table-sm table-borderless'>
    ";
    
    if ($export_order['status'] == 'approved' && $export_order['approver_name']) {
        $approved_date = date('d/m/Y H:i', strtotime($export_order['approved_at']));
        $html .= "
                    <tr><td width='150'><strong>Người duyệt:</strong></td><td>{$export_order['approver_name']}</td></tr>
                    <tr><td><strong>Ngày duyệt:</strong></td><td>$approved_date</td></tr>
        ";
    } elseif ($export_order['status'] == 'rejected' && $export_order['rejecter_name']) {
        $rejected_date = date('d/m/Y H:i', strtotime($export_order['rejected_at']));
        $html .= "
                    <tr><td width='150'><strong>Người từ chối:</strong></td><td>{$export_order['rejecter_name']}</td></tr>
                    <tr><td><strong>Ngày từ chối:</strong></td><td>$rejected_date</td></tr>
                    <tr><td><strong>Lý do:</strong></td><td>{$export_order['rejection_reason']}</td></tr>
        ";
    } else {
        $html .= "
                    <tr><td colspan='2'><em>Phiếu chưa được xử lý</em></td></tr>
        ";
    }
    
    $html .= "
                </table>
            </div>
        </div>
        
        " . (!empty($export_order['notes']) ? "
        <div class='row mb-4'>
            <div class='col-12'>
                <h6>Ghi chú</h6>
                <div class='alert alert-info'>{$export_order['notes']}</div>
            </div>
        </div>
        " : "") . "
        
        <h6>Chi tiết sản phẩm ({$export_order['total_items']} sản phẩm)</h6>
        <div class='table-responsive'>
            <table class='table table-hover'>
                <thead class='table-light'>
                    <tr>
                        <th>STT</th>
                        <th>Sản phẩm</th>
                        <th>SKU</th>
                        <th>Danh mục</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                        <th>Lô hàng</th>
                        <th>Vị trí</th>
                    </tr>
                </thead>
                <tbody>
    ";
    
    $stt = 1;
    foreach ($details as $detail) {
        $location = $detail['shelf_code'] ? "{$detail['shelf_code']} ({$detail['area_name']})" : '-';
        $unit_price = number_format($detail['unit_price'], 0, ',', '.') . 'đ';
        $total_amount = number_format($detail['total_amount'], 0, ',', '.') . 'đ';
        $lot_number = $detail['lot_number'] ?: '-';
        
        $html .= "
                    <tr>
                        <td>$stt</td>
                        <td>{$detail['product_name']}</td>
                        <td><code>{$detail['sku']}</code></td>
                        <td><span class='badge bg-secondary'>{$detail['category_name']}</span></td>
                        <td><span class='badge bg-info'>{$detail['quantity']}</span></td>
                        <td>$unit_price</td>
                        <td class='text-success fw-bold'>$total_amount</td>
                        <td>$lot_number</td>
                        <td>$location</td>
                    </tr>
        ";
        $stt++;
    }
    
    $html .= "
                </tbody>
            </table>
        </div>
    </div>
    ";
    
    return $html;
}

/**
 * Xuất PDF
 */
function exportToPDF() {
    global $pdo;
    
    $export_id = (int)($_GET['id'] ?? 0);
    
    if ($export_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        return;
    }
    
    header('Content-Type: text/html; charset=utf-8');
    
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <title>Phiếu xuất kho</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .header { text-align: center; margin-bottom: 30px; }
            .info-table { width: 100%; margin-bottom: 20px; }
            .info-table td { padding: 5px; border: 1px solid #ddd; }
            .detail-table { width: 100%; border-collapse: collapse; }
            .detail-table th, .detail-table td { 
                padding: 8px; border: 1px solid #ddd; text-align: left; 
            }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h2>PHIẾU XUẤT KHO</h2>
            <p>Mã phiếu: [MÃ PHIẾU]</p>
        </div>
        <!-- Nội dung PDF sẽ được tạo ở đây -->
    </body>
    </html>
    ";
}

/**
 * Xuất Excel
 */
function exportToExcel() {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="danh_sach_xuat_kho.xls"');
    
    echo "
    <table border='1'>
        <tr>
            <th>Mã phiếu</th>
            <th>Đích đến</th>
            <th>Ngày xuất</th>
            <th>Tổng giá trị</th>
            <th>Trạng thái</th>
        </tr>
        <!-- Dữ liệu sẽ được xuất ở đây -->
    </table>
    ";
}

?> 