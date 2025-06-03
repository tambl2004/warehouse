<?php
/**
 * Dashboard API Handler
 * Xử lý các yêu cầu cho dashboard và thống kê tổng quan
 */
session_start();
require_once '../config/connect.php';
require_once '../inc/auth.php';
require_once '../inc/security.php';

if (!isLoggedIn()) { //
    http_response_code(401);
    header('Content-Type: application/json'); 
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để tiếp tục.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Xử lý request dựa vào action
switch ($action) {
    case 'overview_stats':
        getOverviewStats();
        break;
    case 'inventory_by_category':
        getInventoryByCategory();
        break;
    case 'product_distribution':
        getProductDistribution();
        break;
    case 'import_export_trend':
        getImportExportTrend();
        break;
    case 'alerts':
        getAlerts();
        break;
    case 'recent_activities':
        getRecentActivities();
        break;
    case 'top_products':
        getTopProducts();
        break;
    case 'low_stock_products':
        getLowStockProducts();
        break;
    case 'export_dashboard':
        exportDashboard();
        break;
    default:
        http_response_code(400);
        header('Content-Type: application/json'); 
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}

exit;
/**
 * Lấy thống kê tổng quan
 */
function getOverviewStats() {
    global $pdo;
    header('Content-Type: application/json'); 

    try {
        $stats = [];
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE status != 'discontinued'"); 
        $stats['total_products'] = (int)$stmt->fetchColumn(); 

        $stmt = $pdo->query("
            SELECT SUM(p.unit_price * p.stock_quantity) as total_value
            FROM products p
            WHERE p.status = 'in_stock' -- Chỉ tính sản phẩm còn hàng
        "); //
        $stats['total_value'] = (float)($stmt->fetchColumn() ?? 0); 
        $stmt = $pdo->query("
            SELECT COUNT(*) as total
            FROM import_orders
            WHERE DATE_FORMAT(import_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') -- Sử dụng import_date
            AND status = 'approved'
        "); //
        $stats['monthly_imports'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->query("
            SELECT COUNT(*) as total
            FROM export_orders
            WHERE DATE_FORMAT(export_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') -- Sử dụng export_date
            AND status = 'approved'
        "); //
        $stats['monthly_exports'] = (int)$stmt->fetchColumn();

        $stats['products_change'] = calculateChange('products', 'monthly');
        $stats['value_change'] = calculateChange('inventory_value', 'monthly');
        $stats['imports_change'] = calculateChange('imports', 'monthly');
        $stats['exports_change'] = calculateChange('exports', 'monthly');

        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);

    } catch (PDOException $e) { 
        error_log("Dashboard stats PDO error: " . $e->getMessage() . " SQL: " . ($stmt ? $stmt->queryString : "N/A"));
        http_response_code(500); 
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi truy vấn cơ sở dữ liệu khi tải thống kê.'
        ]);
    } catch (Exception $e) { 
        error_log("Dashboard stats general error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi không xác định xảy ra khi tải thống kê.'
        ]);
    }
}
/**
 * Lấy tồn kho theo danh mục
 */
function getInventoryByCategory() {
    global $pdo;
    header('Content-Type: application/json');

    try {
        $stmt = $pdo->prepare("
            SELECT
                c.category_name,
                SUM(p.stock_quantity) as total_stock
            FROM categories c
            LEFT JOIN products p ON c.category_id = p.category_id
            WHERE p.status != 'discontinued' -- HOẶC p.is_active = 1 (nếu bạn đã thêm và cập nhật cột is_active)
            GROUP BY c.category_id, c.category_name
            ORDER BY total_stock DESC
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC); 

        $labels = [];
        $values = [];

        foreach ($results as $row) {
            $labels[] = $row['category_name'];
            $values[] = (int)$row['total_stock']; 
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'labels' => $labels,
                'values' => $values
            ]
        ]);

    } catch (PDOException $e) { 
        error_log("Inventory by category PDO error: " . $e->getMessage() . " SQL: " . ($stmt ? $stmt->queryString : "N/A"));
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi truy vấn cơ sở dữ liệu khi tải tồn kho theo danh mục.'
        ]);
    } catch (Exception $e) { 
        error_log("Inventory by category general error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi không xác định xảy ra khi tải tồn kho theo danh mục.'
        ]);
    }
}

/**
 * Lấy phân bố sản phẩm
 */
function getProductDistribution() {
    global $pdo;
    header('Content-Type: application/json'); // QUAN TRỌNG: Đảm bảo trả về JSON

    try {
        $stmt = $pdo->query("
            SELECT
                c.category_name,
                COUNT(p.product_id) as product_count
            FROM categories c
            LEFT JOIN products p ON c.category_id = p.category_id AND p.status != 'discontinued' -- HOẶC p.is_active = 1 (nếu bạn đã thêm và cập nhật cột is_active)
            GROUP BY c.category_id, c.category_name
            HAVING product_count > 0 -- Chỉ lấy các danh mục có sản phẩm
            ORDER BY product_count DESC
        ");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC); 

        $labels = [];
        $values = [];

        foreach ($results as $row) {
            $labels[] = $row['category_name'];
            $values[] = (int)$row['product_count']; 
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'labels' => $labels,
                'values' => $values
            ]
        ]);

    } catch (PDOException $e) { 
        error_log("Product distribution PDO error: " . $e->getMessage() . " SQL: " . ($stmt instanceof PDOStatement ? $stmt->queryString : "N/A"));
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi truy vấn cơ sở dữ liệu khi tải phân bố sản phẩm.'
        ]);
    } catch (Exception $e) { 
        error_log("Product distribution general error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi không xác định xảy ra khi tải phân bố sản phẩm.'
        ]);
    }
}

/**
 * Lấy xu hướng nhập/xuất kho
 */
function getImportExportTrend() {
    global $pdo;
    
    try {
        $period = $_GET['period'] ?? 30;
        $labels = [];
        $imports = [];
        $exports = [];
        
        for ($i = $period - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('d/m', strtotime($date));
            
            // Đếm phiếu nhập
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM import_orders 
                WHERE DATE(created_at) = ? AND status = 'completed'
            ");
            $stmt->execute([$date]);
            $imports[] = (int)$stmt->fetchColumn();
            
            // Đếm phiếu xuất
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM export_orders 
                WHERE DATE(created_at) = ? AND status = 'completed'
            ");
            $stmt->execute([$date]);
            $exports[] = (int)$stmt->fetchColumn();
        }
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'labels' => $labels,
                'imports' => $imports,
                'exports' => $exports
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Import/Export trend error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải dữ liệu'
        ]);
    }
}

/**
 * Lấy cảnh báo hệ thống
 */
function getAlerts() {
    global $pdo;
    header('Content-Type: application/json'); 

    try {
        $alerts = [];

        $stmtLowStock = $pdo->query("
            SELECT COUNT(*)
            FROM products
            WHERE stock_quantity <= min_stock_level
            AND stock_quantity > 0
            AND status != 'discontinued' -- HOẶC is_active = 1 (nếu bạn đã thêm và cập nhật cột is_active)
        ");
        $lowStockCount = (int)$stmtLowStock->fetchColumn(); 

        if ($lowStockCount > 0) {
            $alerts[] = [
                'type' => 'warning', 
                'title' => 'Sản phẩm tồn kho thấp',
                'message' => "$lowStockCount sản phẩm có tồn kho thấp cần nhập thêm."
            ];
        }

        $stmtOutOfStock = $pdo->query("
            SELECT COUNT(*)
            FROM products
            WHERE stock_quantity = 0
            AND status != 'discontinued' -- HOẶC is_active = 1
        ");
        $outOfStockCount = (int)$stmtOutOfStock->fetchColumn(); 

        if ($outOfStockCount > 0) {
            $alerts[] = [
                'type' => 'danger', // Kiểu cảnh báo
                'title' => 'Sản phẩm hết hàng',
                'message' => "$outOfStockCount sản phẩm đã hết hàng."
            ];
        }

        $stmtExpiringSoon = $pdo->query("
            SELECT COUNT(*)
            FROM products
            WHERE expiry_date IS NOT NULL
            AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) -- Từ hôm nay đến 30 ngày tới
            AND status != 'discontinued' -- HOẶC is_active = 1
        ");
        $expiringCount = (int)$stmtExpiringSoon->fetchColumn(); //

        if ($expiringCount > 0) {
            $alerts[] = [
                'type' => 'warning', // Kiểu cảnh báo
                'title' => 'Sản phẩm sắp hết hạn',
                'message' => "$expiringCount sản phẩm sẽ hết hạn trong 30 ngày tới."
            ];
        }
        
        $stmtExpired = $pdo->query("
            SELECT COUNT(*)
            FROM products
            WHERE expiry_date IS NOT NULL
            AND expiry_date < CURDATE()
            AND status != 'discontinued' -- HOẶC is_active = 1
        ");
        $expiredCount = (int)$stmtExpired->fetchColumn();

        if ($expiredCount > 0) {
            $alerts[] = [
                'type' => 'danger', // Kiểu cảnh báo
                'title' => 'Sản phẩm đã hết hạn',
                'message' => "$expiredCount sản phẩm đã quá hạn sử dụng."
            ];
        }
        echo json_encode([
            'success' => true,
            'data' => $alerts
        ]);

    } catch (PDOException $e) { 
        error_log("Alerts PDO error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi truy vấn cơ sở dữ liệu khi tải cảnh báo.'
        ]);
    } catch (Exception $e) { 
        error_log("Alerts general error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi không xác định xảy ra khi tải cảnh báo.'
        ]);
    }
}

/**
 * Lấy hoạt động gần đây
 */
function getRecentActivities() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ul.action,
                ul.description,
                ul.action_time,
                u.full_name as user_name
            FROM user_logs ul
            LEFT JOIN users u ON ul.user_id = u.user_id
            ORDER BY ul.action_time DESC
            LIMIT 10
        ");
        $stmt->execute();
        $activities = $stmt->fetchAll();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $activities
        ]);
        
    } catch (Exception $e) {
        error_log("Recent activities error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải hoạt động'
        ]);
    }
}

/**
 * Lấy top sản phẩm xuất nhiều nhất
 */
function getTopProducts() {
    global $pdo;
    header('Content-Type: application/json'); 
    try {

        $stmt = $pdo->prepare("
            SELECT
                p.product_id, -- Thêm product_id để GROUP BY chính xác hơn
                p.product_name,
                p.sku,
                p.image_url,
                p.stock_quantity,
                COALESCE(SUM(ed.quantity), 0) as total_exported
            FROM products p
            LEFT JOIN export_details ed ON p.product_id = ed.product_id
            LEFT JOIN export_orders eo ON ed.export_id = eo.export_id -- SỬA: ed.export_id = eo.export_id
            WHERE p.is_active = 1
            AND eo.status = 'approved' -- SỬA: eo.status = 'approved' (thay vì 'completed')
            AND eo.export_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) -- Lấy theo export_date của phiếu xuất
            GROUP BY p.product_id, p.product_name, p.sku, p.image_url, p.stock_quantity -- SỬA: Thêm các cột không tổng hợp vào GROUP BY
            ORDER BY total_exported DESC, p.product_name ASC
            LIMIT 10
        ");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $products
        ]);

    } catch (PDOException $e) {
        error_log("Top products PDO error: " . $e->getMessage() . " SQL: " . ($stmt instanceof PDOStatement ? $stmt->queryString : "N/A"));
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi truy vấn cơ sở dữ liệu khi tải top sản phẩm.'
        ]);
    } catch (Exception $e) {
        error_log("Top products general error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi không xác định xảy ra khi tải top sản phẩm.'
        ]);
    }
}

/**
 * Lấy sản phẩm tồn kho thấp
 */
function getLowStockProducts() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.product_name,
                p.sku,
                p.image_url,
                p.stock_quantity,
                p.min_stock_level
            FROM products p
            WHERE p.is_active = 1 
            AND p.stock_quantity <= p.min_stock_level
            ORDER BY p.stock_quantity ASC
            LIMIT 10
        ");
        $stmt->execute();
        $products = $stmt->fetchAll();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $products
        ]);
        
    } catch (Exception $e) {
        error_log("Low stock products error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải dữ liệu'
        ]);
    }
}

/**
 * Tính toán phần trăm thay đổi
 */
function calculateChange($type, $period) {
    global $pdo;
    
    try {
        $currentValue = 0;
        $previousValue = 0;
        
        switch ($type) {
            case 'products':
                // Sản phẩm hiện tại
                $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
                $currentValue = $stmt->fetchColumn();
                
                // Giả sử không thay đổi nhiều trong tháng
                $previousValue = $currentValue * 0.95; // Giả lập tăng 5%
                break;
                
            case 'inventory_value':
                // Giá trị tồn kho hiện tại
                $stmt = $pdo->query("SELECT SUM(price * stock_quantity) FROM products WHERE is_active = 1");
                $currentValue = $stmt->fetchColumn() ?? 0;
                
                // Giả lập giá trị tháng trước
                $previousValue = $currentValue * 0.92; // Giả lập tăng 8%
                break;
                
            case 'imports':
                // Nhập tháng này
                $stmt = $pdo->query("
                    SELECT COUNT(*) 
                    FROM import_orders 
                    WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
                    AND status = 'completed'
                ");
                $currentValue = $stmt->fetchColumn();
                
                // Nhập tháng trước
                $stmt = $pdo->query("
                    SELECT COUNT(*) 
                    FROM import_orders 
                    WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')
                    AND status = 'completed'
                ");
                $previousValue = $stmt->fetchColumn();
                break;
                
            case 'exports':
                // Xuất tháng này
                $stmt = $pdo->query("
                    SELECT COUNT(*) 
                    FROM export_orders 
                    WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
                    AND status = 'completed'
                ");
                $currentValue = $stmt->fetchColumn();
                
                // Xuất tháng trước
                $stmt = $pdo->query("
                    SELECT COUNT(*) 
                    FROM export_orders 
                    WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')
                    AND status = 'completed'
                ");
                $previousValue = $stmt->fetchColumn();
                break;
        }
        
        if ($previousValue == 0) {
            return $currentValue > 0 ? 100 : 0;
        }
        
        return (($currentValue - $previousValue) / $previousValue) * 100;
        
    } catch (Exception $e) {
        error_log("Calculate change error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Xuất dashboard thành PDF
 */
function exportDashboard() {
    try {
        // Tạo nội dung PDF đơn giản
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="dashboard_report_' . date('Y-m-d') . '.pdf"');
        
        // Trong thực tế, sử dụng thư viện như TCPDF hoặc FPDF
        echo "Dashboard Report exported on " . date('Y-m-d H:i:s');
        
    } catch (Exception $e) {
        error_log("Export dashboard error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi xuất báo cáo'
        ]);
    }
}
?> 