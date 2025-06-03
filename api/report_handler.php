<?php
/**
 * Report API Handler
 * Xử lý các yêu cầu cho module báo cáo và thống kê
 */

require_once '../config/connect.php';
require_once '../inc/auth.php';
require_once '../inc/security.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Yêu cầu đăng nhập']);
    exit;
}

// Lấy action từ request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Xử lý request dựa vào action
switch ($action) {
    case 'inventory_report':
        getInventoryReport();
        break;
    case 'import_export_report':
        getImportExportReport();
        break;
    case 'trend_analysis':
        getTrendAnalysis();
        break;
    case 'financial_report':
        getFinancialReport();
        break;
    case 'performance_report':
        getPerformanceReport();
        break;
    case 'categories':
        getCategories();
        break;
    case 'areas':
        getAreas();
        break;
    case 'products':
        getProducts();
        break;
    case 'suppliers':
        getSuppliers();
        break;
    case 'users':
        getUsers();
        break;
    case 'export_report':
        exportReport();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}

/**
 * Báo cáo tồn kho
 */
function getInventoryReport() {
    global $pdo;
    
    try {
        // Lấy tham số lọc
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $categoryId = $_GET['category_id'] ?? '';
        $areaId = $_GET['area_id'] ?? '';
        
        // Xây dựng query
        $whereConditions = ['p.is_active = 1'];
        $params = [];
        
        if ($categoryId) {
            $whereConditions[] = 'p.category_id = ?';
            $params[] = $categoryId;
        }
        
        if ($areaId) {
            $whereConditions[] = 'p.area_id = ?';
            $params[] = $areaId;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Query chính
        $query = "
            SELECT 
                p.product_id,
                p.product_name,
                p.sku,
                p.stock_quantity,
                p.min_stock_level,
                p.price,
                p.created_at,
                c.category_name,
                a.area_name,
                (p.price * p.stock_quantity) as total_value
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN areas a ON p.area_id = a.area_id
            WHERE $whereClause
            ORDER BY p.product_name
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        // Tính toán thống kê
        $totalItems = count($data);
        $totalValue = array_sum(array_column($data, 'total_value'));
        $lowStockItems = count(array_filter($data, function($item) {
            return $item['stock_quantity'] <= $item['min_stock_level'];
        }));
        $outOfStockItems = count(array_filter($data, function($item) {
            return $item['stock_quantity'] == 0;
        }));
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'stats' => [
                'total_items' => $totalItems,
                'total_value' => $totalValue,
                'low_stock_items' => $lowStockItems,
                'out_of_stock_items' => $outOfStockItems
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Inventory report error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải báo cáo tồn kho'
        ]);
    }
}

/**
 * Báo cáo nhập xuất
 */
function getImportExportReport() {
    global $pdo;
    
    try {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $type = $_GET['type'] ?? 'all'; // all, import, export
        
        $data = ['imports' => [], 'exports' => []];
        
        if ($type === 'all' || $type === 'import') {
            // Lấy dữ liệu nhập
            $stmt = $pdo->prepare("
                SELECT 
                    io.import_order_id,
                    io.order_code,
                    io.total_amount,
                    io.created_at,
                    io.status,
                    s.supplier_name,
                    u.full_name as created_by
                FROM import_orders io
                LEFT JOIN suppliers s ON io.supplier_id = s.supplier_id
                LEFT JOIN users u ON io.created_by = u.user_id
                WHERE DATE(io.created_at) BETWEEN ? AND ?
                ORDER BY io.created_at DESC
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $data['imports'] = $stmt->fetchAll();
        }
        
        if ($type === 'all' || $type === 'export') {
            // Lấy dữ liệu xuất
            $stmt = $pdo->prepare("
                SELECT 
                    eo.export_order_id,
                    eo.order_code,
                    eo.total_amount,
                    eo.created_at,
                    eo.status,
                    eo.customer_name,
                    u.full_name as created_by
                FROM export_orders eo
                LEFT JOIN users u ON eo.created_by = u.user_id
                WHERE DATE(eo.created_at) BETWEEN ? AND ?
                ORDER BY eo.created_at DESC
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $data['exports'] = $stmt->fetchAll();
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        
    } catch (Exception $e) {
        error_log("Import/Export report error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải báo cáo nhập xuất'
        ]);
    }
}

/**
 * Phân tích xu hướng
 */
function getTrendAnalysis() {
    global $pdo;
    
    try {
        $period = $_GET['period'] ?? 30;
        
        $data = [];
        
        // Xu hướng nhập xuất theo ngày
        for ($i = $period - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            
            // Đếm phiếu nhập
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as amount
                FROM import_orders 
                WHERE DATE(created_at) = ? AND status = 'completed'
            ");
            $stmt->execute([$date]);
            $importData = $stmt->fetch();
            
            // Đếm phiếu xuất
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as amount
                FROM export_orders 
                WHERE DATE(created_at) = ? AND status = 'completed'
            ");
            $stmt->execute([$date]);
            $exportData = $stmt->fetch();
            
            $data[] = [
                'date' => $date,
                'import_count' => (int)$importData['count'],
                'import_amount' => (float)$importData['amount'],
                'export_count' => (int)$exportData['count'],
                'export_amount' => (float)$exportData['amount']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        
    } catch (Exception $e) {
        error_log("Trend analysis error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi phân tích xu hướng'
        ]);
    }
}

/**
 * Lấy danh sách categories
 */
function getCategories() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
        $categories = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $categories
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải danh mục'
        ]);
    }
}

/**
 * Lấy danh sách areas
 */
function getAreas() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT area_id, area_name FROM areas ORDER BY area_name");
        $areas = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $areas
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải khu vực'
        ]);
    }
}

/**
 * Lấy danh sách products
 */
function getProducts() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT product_id, product_name, sku 
            FROM products 
            WHERE is_active = 1 
            ORDER BY product_name
        ");
        $products = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $products
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải sản phẩm'
        ]);
    }
}

/**
 * Lấy danh sách suppliers
 */
function getSuppliers() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT supplier_id, supplier_name 
            FROM suppliers 
            WHERE is_active = 1 
            ORDER BY supplier_name
        ");
        $suppliers = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $suppliers
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải nhà cung cấp'
        ]);
    }
}

/**
 * Lấy danh sách users
 */
function getUsers() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT user_id, full_name, username 
            FROM users 
            WHERE is_active = 1 
            ORDER BY full_name
        ");
        $users = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $users
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải người dùng'
        ]);
    }
}

/**
 * Xuất báo cáo
 */
function exportReport() {
    try {
        $type = $_POST['type'] ?? 'pdf';
        $reportType = $_POST['report_type'] ?? 'inventory';
        
        if ($type === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.pdf"');
        } else {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.xlsx"');
        }
        
        // Giả lập xuất file
        echo "Report exported on " . date('Y-m-d H:i:s');
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi xuất báo cáo'
        ]);
    }
}
?> 