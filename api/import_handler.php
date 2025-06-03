<?php
/**
 * API Handler cho quản lý nhập kho
 * File: api/import_handler.php
 */
use TCPDF;
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
        case 'get_imports':
            getImportOrders();
            break;
        case 'get_import_detail':
            getImportDetail();
            break;
        case 'create_import':
            createImportOrder();
            break;
        case 'approve_import':
            approveImport();
            break;
        case 'reject_import':
            rejectImport();
            break;
        case 'generate_import_code':
            generateImportCode();
            break;
        case 'export_pdf':
            exportToPDF();
            break;
        case 'export_excel': 
            exportToExcel();
            break;
        case 'get_suppliers':
            getSuppliers();
            break;
        case 'get_products':
            getProducts();
            break;
        case 'get_shelves':
            getShelves();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
    }
} catch (Exception $e) {
    error_log("Import API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

/**
 * Lấy danh sách phiếu nhập với phân trang và lọc
 */
function getImportOrders() {
    global $pdo;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $search = cleanInput($_GET['search'] ?? '');
    $status = cleanInput($_GET['status'] ?? '');
    $supplier_id = (int)($_GET['supplier_id'] ?? 0);
    $from_date = cleanInput($_GET['from_date'] ?? '');
    $to_date = cleanInput($_GET['to_date'] ?? '');
    
    $offset = ($page - 1) * $limit;
    
    // Xây dựng câu truy vấn WHERE
    $where_conditions = ['1=1'];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(io.import_code LIKE ? OR s.supplier_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($status)) {
        $where_conditions[] = "io.status = ?";
        $params[] = $status;
    }
    
    if ($supplier_id > 0) {
        $where_conditions[] = "io.supplier_id = ?";
        $params[] = $supplier_id;
    }
    
    if (!empty($from_date)) {
        $where_conditions[] = "DATE(io.import_date) >= ?";
        $params[] = $from_date;
    }
    
    if (!empty($to_date)) {
        $where_conditions[] = "DATE(io.import_date) <= ?";
        $params[] = $to_date;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Đếm tổng số bản ghi
    $count_sql = "
        SELECT COUNT(*) 
        FROM import_orders io
        LEFT JOIN suppliers s ON io.supplier_id = s.supplier_id
        WHERE $where_clause
    ";
    
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    
    // Lấy dữ liệu với phân trang
    $data_sql = "
        SELECT 
            io.*,
            s.supplier_name,
            u.full_name as creator_name,
            COALESCE(SUM(id.quantity * id.unit_price), 0) as total_value
        FROM import_orders io
        LEFT JOIN suppliers s ON io.supplier_id = s.supplier_id
        LEFT JOIN users u ON io.created_by = u.user_id
        LEFT JOIN import_details id ON io.import_id = id.import_id
        WHERE $where_clause
        GROUP BY io.import_id
        ORDER BY io.import_date DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($data_sql);
    $stmt->execute($params);
    $imports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_pages = ceil($total_records / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $imports,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'limit' => $limit
        ]
    ]);
}

/**
 * Lấy chi tiết phiếu nhập
 */
function getImportDetail() {
    global $pdo;
    
    $import_id = (int)($_GET['id'] ?? 0);
    
    if ($import_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID phiếu nhập không hợp lệ']);
        return;
    }
    
    // Lấy thông tin phiếu nhập
    $import_sql = "
        SELECT 
            io.*,
            s.supplier_name, s.supplier_code, s.address as supplier_address,
            s.phone_number as supplier_phone, s.email as supplier_email,
            u.full_name as creator_name,
            COALESCE(SUM(id.quantity * id.unit_price), 0) as total_value,
            COUNT(id.import_detail_id) as total_items
        FROM import_orders io
        LEFT JOIN suppliers s ON io.supplier_id = s.supplier_id
        LEFT JOIN users u ON io.created_by = u.user_id
        LEFT JOIN import_details id ON io.import_id = id.import_id
        WHERE io.import_id = ?
        GROUP BY io.import_id
    ";
    
    $stmt = $pdo->prepare($import_sql);
    $stmt->execute([$import_id]);
    $import_order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$import_order) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu nhập']);
        return;
    }
    
    // Lấy chi tiết sản phẩm
    $details_sql = "
        SELECT 
            id.*,
            p.product_name, p.sku, p.unit_price as current_price,
            c.category_name,
            sh.shelf_code, wa.area_name,
            (id.quantity * id.unit_price) as total_amount
        FROM import_details id
        LEFT JOIN products p ON id.product_id = p.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN shelves sh ON id.shelf_id = sh.shelf_id
        LEFT JOIN warehouse_areas wa ON sh.area_id = wa.area_id
        WHERE id.import_id = ?
        ORDER BY p.product_name
    ";
    
    $stmt = $pdo->prepare($details_sql);
    $stmt->execute([$import_id]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tạo HTML hiển thị
    $html = generateImportDetailHTML($import_order, $details);
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'data' => [
            'import_order' => $import_order,
            'details' => $details
        ]
    ]);
}

/**
 * Tạo phiếu nhập mới
 */
function createImportOrder() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Validate dữ liệu đầu vào
        $import_code = cleanInput($_POST['import_code'] ?? '');
        $supplier_id = (int)($_POST['supplier_id'] ?? 0);
        $notes = cleanInput($_POST['notes'] ?? '');
        $products = $_POST['products'] ?? [];
        $user_id = $_SESSION['user_id'];
        
        if (empty($import_code) || $supplier_id <= 0) {
            throw new Exception('Vui lòng nhập đầy đủ thông tin bắt buộc');
        }
        
        if (empty($products)) {
            throw new Exception('Vui lòng thêm ít nhất một sản phẩm');
        }
        
        // Kiểm tra mã phiếu nhập đã tồn tại
        $check_stmt = $pdo->prepare("SELECT import_id FROM import_orders WHERE import_code = ?");
        $check_stmt->execute([$import_code]);
        if ($check_stmt->fetch()) {
            throw new Exception('Mã phiếu nhập đã tồn tại');
        }
        
        // Tạo phiếu nhập
        $import_sql = "
            INSERT INTO import_orders (import_code, supplier_id, created_by, notes, status, import_date)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ";
        $stmt = $pdo->prepare($import_sql);
        $stmt->execute([$import_code, $supplier_id, $user_id, $notes]);
        
        $import_id = $pdo->lastInsertId();
        
        // Thêm chi tiết sản phẩm
        $detail_sql = "
            INSERT INTO import_details (import_id, product_id, quantity, unit_price, lot_number, expiry_date, shelf_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        $detail_stmt = $pdo->prepare($detail_sql);
        
        foreach ($products as $product) {
            $product_id = (int)($product['product_id'] ?? 0);
            $quantity = (int)($product['quantity'] ?? 0);
            $unit_price = (float)($product['unit_price'] ?? 0);
            $lot_number = cleanInput($product['lot_number'] ?? '');
            $expiry_date = !empty($product['expiry_date']) ? $product['expiry_date'] : null;
            $shelf_id = !empty($product['shelf_id']) ? (int)$product['shelf_id'] : null;
            
            if ($product_id <= 0 || $quantity <= 0 || $unit_price <= 0) {
                continue; // Bỏ qua sản phẩm không hợp lệ
            }
            
            // Kiểm tra sản phẩm tồn tại
            $product_check = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ?");
            $product_check->execute([$product_id]);
            if (!$product_check->fetch()) {
                throw new Exception("Sản phẩm ID $product_id không tồn tại");
            }
            
            $detail_stmt->execute([
                $import_id, $product_id, $quantity, $unit_price, 
                $lot_number, $expiry_date, $shelf_id
            ]);
        }
        
        $pdo->commit();
        
        // Ghi log
        logUserActivity($user_id, 'CREATE_IMPORT_ORDER', "Tạo phiếu nhập: $import_code");
        
        echo json_encode([
            'success' => true,
            'message' => 'Tạo phiếu nhập thành công',
            'import_id' => $import_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Duyệt phiếu nhập
 */
function approveImport() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        $import_id = (int)($_POST['import_id'] ?? 0);
        $user_id = $_SESSION['user_id'];
        
        if ($import_id <= 0) {
            throw new Exception('ID phiếu nhập không hợp lệ');
        }
        
        // Kiểm tra phiếu nhập tồn tại và đang chờ duyệt
        $check_stmt = $pdo->prepare("
            SELECT import_code, status 
            FROM import_orders 
            WHERE import_id = ? AND status = 'pending'
        ");
        $check_stmt->execute([$import_id]);
        $import_order = $check_stmt->fetch();
        
        if (!$import_order) {
            throw new Exception('Phiếu nhập không tồn tại hoặc đã được xử lý');
        }
        
        // Cập nhật trạng thái phiếu nhập
        $update_stmt = $pdo->prepare("
            UPDATE import_orders 
            SET status = 'approved', approved_by = ?, approved_at = NOW() 
            WHERE import_id = ?
        ");
        $update_stmt->execute([$user_id, $import_id]);
        
        // Cập nhật tồn kho sản phẩm
        $details_stmt = $pdo->prepare("
            SELECT id.*, p.volume 
            FROM import_details id 
            LEFT JOIN products p ON id.product_id = p.product_id
            WHERE id.import_id = ?
        ");
        $details_stmt->execute([$import_id]);
        $details = $details_stmt->fetchAll();
        
        foreach ($details as $detail) {
            // Cập nhật số lượng tồn kho
            $update_stock_stmt = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity + ?, updated_at = NOW()
                WHERE product_id = ?
            ");
            $update_stock_stmt->execute([$detail['quantity'], $detail['product_id']]);
            
            // Cập nhật vị trí sản phẩm trên kệ (nếu có)
            if ($detail['shelf_id']) {
                updateProductLocation($pdo, $detail['product_id'], $detail['shelf_id'], $detail['quantity'], $detail['volume']);
            }
        }
        
        $pdo->commit();
        
        // Ghi log
        logUserActivity($user_id, 'APPROVE_IMPORT', "Duyệt phiếu nhập: {$import_order['import_code']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Duyệt phiếu nhập thành công'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Từ chối phiếu nhập
 */
function rejectImport() {
    global $pdo;
    
    try {
        $import_id = (int)($_POST['import_id'] ?? 0);
        $reason = cleanInput($_POST['reason'] ?? '');
        $user_id = $_SESSION['user_id'];
        
        if ($import_id <= 0) {
            throw new Exception('ID phiếu nhập không hợp lệ');
        }
        
        if (empty($reason)) {
            throw new Exception('Vui lòng nhập lý do từ chối');
        }
        
        // Kiểm tra phiếu nhập
        $check_stmt = $pdo->prepare("
            SELECT import_code, status 
            FROM import_orders 
            WHERE import_id = ? AND status = 'pending'
        ");
        $check_stmt->execute([$import_id]);
        $import_order = $check_stmt->fetch();
        
        if (!$import_order) {
            throw new Exception('Phiếu nhập không tồn tại hoặc đã được xử lý');
        }
        
        // Cập nhật trạng thái
        $update_stmt = $pdo->prepare("
            UPDATE import_orders 
            SET status = 'rejected', rejected_by = ?, rejected_at = NOW(), rejection_reason = ?
            WHERE import_id = ?
        ");
        $update_stmt->execute([$user_id, $reason, $import_id]);
        
        // Ghi log
        logUserActivity($user_id, 'REJECT_IMPORT', "Từ chối phiếu nhập: {$import_order['import_code']} - Lý do: $reason");
        
        echo json_encode([
            'success' => true,
            'message' => 'Từ chối phiếu nhập thành công'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Tạo mã phiếu nhập tự động
 */
function generateImportCode() {
    global $pdo;
    
    $date = cleanInput($_GET['date'] ?? date('Ymd'));
    
    // Lấy số thứ tự trong ngày
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) + 1 as next_number
        FROM import_orders 
        WHERE DATE(import_date) = ? OR import_code LIKE ?
    ");
    $count_stmt->execute([
        date('Y-m-d', strtotime($date)), 
        "NHAP-$date-%"
    ]);
    $next_number = $count_stmt->fetchColumn();
    
    $import_code = sprintf("NHAP-%s-%03d", $date, $next_number);
    
    echo json_encode([
        'success' => true,
        'import_code' => $import_code
    ]);
}

/**
 * Cập nhật vị trí sản phẩm trên kệ
 */
function updateProductLocation($pdo, $product_id, $shelf_id, $quantity, $volume = 0) {
    // Kiểm tra sản phẩm đã có trên kệ chưa
    $check_stmt = $pdo->prepare("
        SELECT quantity FROM product_locations 
        WHERE product_id = ? AND shelf_id = ?
    ");
    $check_stmt->execute([$product_id, $shelf_id]);
    $existing = $check_stmt->fetch();
    
    if ($existing) {
        // Cập nhật số lượng hiện có
        $update_stmt = $pdo->prepare("
            UPDATE product_locations 
            SET quantity = quantity + ?, last_updated = NOW()
            WHERE product_id = ? AND shelf_id = ?
        ");
        $update_stmt->execute([$quantity, $product_id, $shelf_id]);
    } else {
        // Thêm mới
        $insert_stmt = $pdo->prepare("
            INSERT INTO product_locations (product_id, shelf_id, quantity, last_updated)
            VALUES (?, ?, ?, NOW())
        ");
        $insert_stmt->execute([$product_id, $shelf_id, $quantity]);
    }
    
    // Cập nhật sức chứa hiện tại của kệ
    $total_volume = $quantity * $volume;
    if ($total_volume > 0) {
        $update_shelf_stmt = $pdo->prepare("
            UPDATE shelves 
            SET current_capacity = current_capacity + ?
            WHERE shelf_id = ?
        ");
        $update_shelf_stmt->execute([$total_volume, $shelf_id]);
    }
    
    // Ghi lịch sử di chuyển
    $history_stmt = $pdo->prepare("
        INSERT INTO shelf_product_history (product_id, shelf_id, quantity, moved_at, created_by)
        VALUES (?, ?, ?, NOW(), ?)
    ");
    $history_stmt->execute([$product_id, $shelf_id, $quantity, $_SESSION['user_id']]);
}

/**
 * Tạo HTML hiển thị chi tiết phiếu nhập
 */
function generateImportDetailHTML($import_order, $details) {
    $status_badge = '';
    switch ($import_order['status']) {
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
    
    $import_date = date('d/m/Y H:i', strtotime($import_order['import_date']));
    $total_value = number_format($import_order['total_value'], 0, ',', '.') . 'đ';
    
    $html = "
    <div class='import-detail-content'>
        <div class='row mb-4'>
            <div class='col-md-6'>
                <h6>Thông tin phiếu nhập</h6>
                <table class='table table-sm table-borderless'>
                    <tr><td width='150'><strong>Mã phiếu:</strong></td><td>{$import_order['import_code']}</td></tr>
                    <tr><td><strong>Ngày nhập:</strong></td><td>$import_date</td></tr>
                    <tr><td><strong>Trạng thái:</strong></td><td>$status_badge</td></tr>
                    <tr><td><strong>Người tạo:</strong></td><td>{$import_order['creator_name']}</td></tr>
                    <tr><td><strong>Tổng giá trị:</strong></td><td class='text-success fw-bold'>$total_value</td></tr>
                </table>
            </div>
            <div class='col-md-6'>
                <h6>Thông tin nhà cung cấp</h6>
                <table class='table table-sm table-borderless'>
                    <tr><td width='150'><strong>Tên:</strong></td><td>{$import_order['supplier_name']}</td></tr>
                    <tr><td><strong>Mã NCC:</strong></td><td>{$import_order['supplier_code']}</td></tr>
                    <tr><td><strong>Điện thoại:</strong></td><td>{$import_order['supplier_phone']}</td></tr>
                    <tr><td><strong>Email:</strong></td><td>{$import_order['supplier_email']}</td></tr>
                    <tr><td><strong>Địa chỉ:</strong></td><td>{$import_order['supplier_address']}</td></tr>
                </table>
            </div>
        </div>
        
        " . (!empty($import_order['notes']) ? "
        <div class='row mb-4'>
            <div class='col-12'>
                <h6>Ghi chú</h6>
                <div class='alert alert-info'>{$import_order['notes']}</div>
            </div>
        </div>
        " : "") . "
        
        <h6>Chi tiết sản phẩm ({$import_order['total_items']} sản phẩm)</h6>
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
                        <th>Hạn SD</th>
                        <th>Vị trí</th>
                    </tr>
                </thead>
                <tbody>
    ";
    
    $stt = 1;
    foreach ($details as $detail) {
        $expiry_date = $detail['expiry_date'] ? date('d/m/Y', strtotime($detail['expiry_date'])) : '-';
        $location = $detail['shelf_code'] ? "{$detail['shelf_code']} ({$detail['area_name']})" : '-';
        $unit_price = number_format($detail['unit_price'], 0, ',', '.') . 'đ';
        $total_amount = number_format($detail['total_amount'], 0, ',', '.') . 'đ';
        
        $html .= "
                    <tr>
                        <td>$stt</td>
                        <td>{$detail['product_name']}</td>
                        <td><code>{$detail['sku']}</code></td>
                        <td><span class='badge bg-secondary'>{$detail['category_name']}</span></td>
                        <td><span class='badge bg-info'>{$detail['quantity']}</span></td>
                        <td>$unit_price</td>
                        <td class='text-success fw-bold'>$total_amount</td>
                        <td>{$detail['lot_number']}</td>
                        <td>$expiry_date</td>
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

    $import_id = (int)($_GET['id'] ?? 0);

    if ($import_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID phiếu nhập không hợp lệ']);
        return;
    }

    try {
        // Lấy thông tin phiếu nhập
        $import_sql = "
            SELECT 
                io.*,
                s.supplier_name, s.supplier_code, s.address as supplier_address,
                s.phone_number as supplier_phone, s.email as supplier_email,
                u.full_name as creator_name,
                DATE_FORMAT(io.import_date, '%d/%m/%Y %H:%i') as formatted_import_date,
                DATE_FORMAT(io.created_at, '%d/%m/%Y %H:%i') as formatted_created_at,
                (SELECT us.full_name FROM users us WHERE us.user_id = io.approved_by) as approver_name,
                DATE_FORMAT(io.approved_at, '%d/%m/%Y %H:%i') as formatted_approved_at
            FROM import_orders io
            LEFT JOIN suppliers s ON io.supplier_id = s.supplier_id
            LEFT JOIN users u ON io.created_by = u.user_id
            WHERE io.import_id = ?
        ";
        $stmt_import = $pdo->prepare($import_sql);
        $stmt_import->execute([$import_id]);
        $import_order = $stmt_import->fetch(PDO::FETCH_ASSOC);

        if (!$import_order) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu nhập.']);
            return;
        }

        // Lấy chi tiết sản phẩm
        $details_sql = "
            SELECT 
                id.*,
                p.product_name, p.sku, 
                c.category_name
            FROM import_details id
            LEFT JOIN products p ON id.product_id = p.product_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE id.import_id = ?
            ORDER BY p.product_name
        ";
        $stmt_details = $pdo->prepare($details_sql);
        $stmt_details->execute([$import_id]);
        $details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

        // --- BẮT ĐẦU TẠO PDF ---
        if (!file_exists('../vendor/autoload.php')) {
             error_log("Lỗi xuất PDF: Thư viện TCPDF (vendor/autoload.php) không tìm thấy.");
             echo json_encode(['success' => false, 'message' => 'Lỗi cấu hình thư viện PDF. Vui lòng liên hệ quản trị viên.']);
             return;
        }
        require_once '../vendor/autoload.php'; // (Đảm bảo đường dẫn đúng)

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); //

        $pdf->SetCreator('Hệ Thống Quản Lý Kho'); //
        $pdf->SetAuthor('Hệ Thống Quản Lý Kho'); //
        $pdf->SetTitle('Phiếu Nhập Kho - ' . $import_order['import_code']); //
        $pdf->SetMargins(15, 15, 15); //
        $pdf->SetHeaderMargin(5); //
        $pdf->SetFooterMargin(10); //
        $pdf->SetAutoPageBreak(TRUE, 15); //
        $pdf->SetFont('dejavusans', '', 10); // (dejavusans hỗ trợ tiếng Việt tốt)

        $pdf->AddPage(); //

        // HTML Content - Bạn có thể tùy chỉnh chi tiết hơn
        $html = '
        <style>
            body { font-family: "dejavusans", sans-serif; line-height: 1.5; }
            h1 { text-align: center; color: #333; font-size: 18px; margin-bottom: 5px; }
            h2 { text-align: center; color: #555; font-size: 16px; margin-bottom: 20px; }
            .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
            .info-table td { padding: 6px; font-size: 10px; }
            .info-table .label { font-weight: bold; width: 120px; }
            .details-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            .details-table th { background-color: #f2f2f2; font-weight: bold; padding: 8px; border: 1px solid #ccc; text-align: center; font-size: 10px; }
            .details-table td { padding: 7px; border: 1px solid #ccc; font-size: 10px; }
            .text-right { text-align: right; }
            .total-row td { font-weight: bold; background-color: #f8f8f8; }
            .signatures { margin-top: 40px; width: 100%; }
            .signatures td { width: 33.33%; text-align: center; font-size: 10px; vertical-align: top; }
            .signatures .signature-space { height: 60px; display: block; margin-bottom: 5px; }
        </style>
        ';

        $html .= '<h1>CÔNG TY TNHH ABC</h1>'; // Thay thế bằng thông tin công ty của bạn
        $html .= '<p style="text-align:center; font-size:9px;">Địa chỉ: 123 Đường XYZ, Quận 1, TP. HCM<br>Điện thoại: (028) 38123456</p>';
        $html .= '<h2>PHIẾU NHẬP KHO</h2>';

        // Thông tin chung
        $html .= '<table class="info-table">
                    <tr>
                        <td class="label">Mã phiếu:</td><td><strong>' . htmlspecialchars($import_order['import_code']) . '</strong></td>
                        <td class="label">Ngày nhập:</td><td>' . htmlspecialchars($import_order['formatted_import_date']) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Nhà cung cấp:</td><td colspan="3">' . htmlspecialchars($import_order['supplier_name']) . ' ('.htmlspecialchars($import_order['supplier_code']).')</td>
                    </tr>
                    <tr>
                        <td class="label">Địa chỉ NCC:</td><td colspan="3">' . htmlspecialchars($import_order['supplier_address']) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Người tạo:</td><td>' . htmlspecialchars($import_order['creator_name']) . '</td>
                        <td class="label">Trạng thái:</td><td>' . htmlspecialchars(ucfirst($import_order['status'])) . '</td>
                    </tr>';
        if (!empty($import_order['notes'])) {
            $html .= '<tr><td class="label">Ghi chú:</td><td colspan="3">' . nl2br(htmlspecialchars($import_order['notes'])) . '</td></tr>';
        }
        if ($import_order['status'] == 'approved' && !empty($import_order['approver_name'])) {
             $html .= '<tr><td class="label">Người duyệt:</td><td>' . htmlspecialchars($import_order['approver_name']) . '</td>';
             $html .= '<td class="label">Ngày duyệt:</td><td>' . htmlspecialchars($import_order['formatted_approved_at']) . '</td></tr>';
        }
        $html .= '</table>';

        // Chi tiết sản phẩm
        $html .= '<table class="details-table">
                    <thead>
                        <tr>
                            <th width="5%">STT</th>
                            <th width="30%">Tên sản phẩm</th>
                            <th width="15%">SKU</th>
                            <th width="10%">SL</th>
                            <th width="15%" class="text-right">Đơn giá (VNĐ)</th>
                            <th width="25%" class="text-right">Thành tiền (VNĐ)</th>
                        </tr>
                    </thead>
                    <tbody>';
        $totalValue = 0;
        $totalQuantity = 0;
        foreach ($details as $index => $item) {
            $subtotal = (float)$item['quantity'] * (float)$item['unit_price'];
            $totalValue += $subtotal;
            $totalQuantity += (int)$item['quantity'];
            $html .= '<tr>
                        <td style="text-align:center;">'.($index + 1).'</td>
                        <td>'.htmlspecialchars($item['product_name']).'</td>
                        <td>'.htmlspecialchars($item['sku']).'</td>
                        <td style="text-align:center;">'.number_format($item['quantity']).'</td>
                        <td class="text-right">'.number_format($item['unit_price'], 0, ',', '.').'</td>
                        <td class="text-right">'.number_format($subtotal, 0, ',', '.').'</td>
                      </tr>';
        }
         $html .= '<tr class="total-row">
                    <td colspan="3" class="text-right"><strong>TỔNG CỘNG</strong></td>
                    <td style="text-align:center;"><strong>'.number_format($totalQuantity).'</strong></td>
                    <td></td>
                    <td class="text-right"><strong>'.number_format($totalValue, 0, ',', '.').'</strong></td>
                  </tr>';
        $html .= '</tbody></table>';



        // Chữ ký
        $html .= '
        <table class="signatures">
            <tr>
                <td><strong>Người lập phiếu</strong><br>(Ký, ghi rõ họ tên)<br><span class="signature-space"></span><br>'.htmlspecialchars($import_order['creator_name']).'</td>
                <td><strong>Kế toán trưởng</strong><br>(Ký, ghi rõ họ tên)<br><span class="signature-space"></span><br></td>
                <td><strong>Thủ kho</strong><br>(Ký, ghi rõ họ tên)<br><span class="signature-space"></span><br></td>
            </tr>
        </table>';

        $pdf->writeHTML($html, true, false, true, false, ''); //

        $filename = 'PhieuNhapKho_' . str_replace('-', '', $import_order['import_code']) . '.pdf';
        $pdf->Output($filename, 'I'); // I: hiển thị inline, D: download //
        exit; // Quan trọng

    } catch (Exception $e) {
        error_log("Lỗi xuất PDF phiếu nhập: " . $e->getMessage());
        if (!headers_sent()) {
             echo json_encode(['success' => false, 'message' => 'Có lỗi khi xuất PDF: ' . $e->getMessage()]);
        }
    }
}
/**
 * Xuất Excel
 */
function exportToExcel() {
    // Tương tự như exportToPDF nhưng tạo file Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="danh_sach_nhap_kho.xls"');
    
    echo "
    <table border='1'>
        <tr>
            <th>Mã phiếu</th>
            <th>Nhà cung cấp</th>
            <th>Ngày nhập</th>
            <th>Tổng giá trị</th>
            <th>Trạng thái</th>
        </tr>
        <!-- Dữ liệu sẽ được xuất ở đây -->
    </table>
    ";
}

function getSuppliers() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT supplier_id, supplier_name, phone_number, address FROM suppliers WHERE status = 'active' ORDER BY supplier_name");
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $formattedSuppliers = array_map(function($supplier) {
            return [
                'supplier_id' => $supplier['supplier_id'],
                'name' => $supplier['supplier_name'], // JS mong đợi 'name'
                'phone' => $supplier['phone_number'], // JS mong đợi 'phone'
                'address' => $supplier['address']     // JS mong đợi 'address'
            ];
        }, $suppliers);
        echo json_encode(['success' => true, 'data' => $formattedSuppliers]);
    } catch (Exception $e) {
        error_log("Lỗi getSuppliers: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách nhà cung cấp.']);
    }
}

/**
 * Lấy danh sách sản phẩm đang kinh doanh và còn hoạt động
 */
function getProducts() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT p.product_id, p.product_name, p.unit_price, p.sku, c.category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE p.status = 'in_stock' AND p.is_active = 1
            ORDER BY p.product_name
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $products]); //
    } catch (Exception $e) {
        error_log("Lỗi getProducts: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách sản phẩm.']);
    }
}
/**
 * Lấy danh sách kệ kho
 */
function getShelves() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT s.shelf_id, s.shelf_code, wa.area_name
            FROM shelves s
            LEFT JOIN warehouse_areas wa ON s.area_id = wa.area_id
            ORDER BY wa.area_name, s.shelf_code
        ");
        $shelves = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $formattedShelves = array_map(function($shelf) {
            return [
                'shelf_id' => $shelf['shelf_id'],
                'shelf_code' => $shelf['shelf_code'],
                'location' => $shelf['area_name'] // JS mong đợi 'location'
            ];
        }, $shelves);
        echo json_encode(['success' => true, 'data' => $formattedShelves]);
    } catch (Exception $e) {
        error_log("Lỗi getShelves: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách kệ.']);
    }
}
?> 