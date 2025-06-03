<?php
/**
 * Report API Handler
 * Xử lý các yêu cầu cho module báo cáo và thống kê
 */
session_start();
require_once '../config/connect.php';
require_once '../inc/auth.php';
require_once '../inc/security.php';

if (!isLoggedIn()) { //
    http_response_code(401);
    header('Content-Type: application/json'); //
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để tiếp tục.']);
    exit;
}

// Lấy action từ request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Xử lý request dựa vào action
try {
    switch ($action) {
        case 'inventory_report':
            getInventoryReport();
            break;
        case 'import_export_report':
            getImportExportReport();
            break;
        case 'generate_analysis':
            generateAnalysis();
            break;
        case 'financial_report':
            getFinancialReport();
            break;
        case 'performance_report':
            getPerformanceReport();
            break;
        case 'get_categories':
            getCategories();
            break;
        case 'get_areas':
            getAreas();
            break;
        case 'get_products':
            getProducts();
            break;
        case 'get_suppliers':
            getSuppliers();
            break;
        case 'get_users':
            getUsers();
            break;
        case 'export_report':
            exportReport();
            break;
        default:
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
            break;
    }
} catch (Exception $e) {
    error_log("Report API handler error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json'); //
    echo json_encode(['success' => false, 'message' => 'Có lỗi hệ thống xảy ra. Vui lòng thử lại sau.']);
}
exit; 
/**
 * Báo cáo tồn kho
 */
function getInventoryReport() {
    global $pdo;
    header('Content-Type: application/json'); //

    try {
        // Lấy tham số lọc từ POST body (vì js đang gửi POST)
        $request_payload = file_get_contents('php://input');
        $filters = json_decode($request_payload, true);

        $dateFrom = cleanInput($filters['fromDate'] ?? date('Y-m-01')); // Sửa: fromDate
        $dateTo = cleanInput($filters['toDate'] ?? date('Y-m-d'));     // Sửa: toDate
        $categoryId = cleanInput($filters['category'] ?? '');         // Sửa: category
        $areaId = cleanInput($filters['area'] ?? '');                 // Sửa: area

        // Xây dựng query
        $whereConditions = ["p.status != 'discontinued'"]; // Hoặc p.is_active = 1
        $params = [];

        //  Không có lọc theo ngày trong query gốc của bạn cho báo cáo tồn kho,
        //  vì tồn kho thường là tại một thời điểm. Nếu bạn muốn lịch sử tồn kho,
        //  cần bảng snapshot hoặc logic phức tạp hơn. Hiện tại, bỏ qua dateFrom, dateTo cho query này.

        if (!empty($categoryId)) { //
            $whereConditions[] = 'p.category_id = :category_id'; //
            $params[':category_id'] = $categoryId; //
        }

        if (!empty($areaId)) {
            // Để lọc theo areaId, cần JOIN với bảng `product_locations` và `shelves`
            // HOẶC nếu bạn có `area_id` trực tiếp trong `products` (hiện tại không có trong CSDL của bạn)
            // Giả sử chúng ta cần JOIN để lấy area_name cho hiển thị, và có thể lọc
            // Câu query này sẽ phức tạp hơn nếu một sản phẩm có thể ở nhiều kệ/khu vực
            // Đơn giản hóa: Lấy tất cả sản phẩm rồi lọc ở PHP, hoặc JOIN phức tạp
            // Tạm thời, nếu areaId được cung cấp, chúng ta sẽ JOIN
             $whereConditions[] = 's.area_id = :area_id'; // Kệ thuộc khu vực
             $params[':area_id'] = $areaId; //
        }

        $whereClause = "WHERE " . implode(' AND ', $whereConditions);

        // Query chính
        // Sửa: Đảm bảo tên cột `price` thành `unit_price`
        // Sửa: bảng `areas` trong CSDL của bạn là `warehouse_areas`
        // Sửa: Để lấy `area_name`, cần JOIN qua `product_locations` và `shelves`
        $query = "
            SELECT
                p.product_id,
                p.product_name,
                p.sku,
                p.stock_quantity,
                COALESCE(p.min_stock_level, 0) as min_stock_level, -- Giả sử bạn đã thêm cột này
                p.unit_price, -- SỬA: price -> unit_price
                p.created_at,
                c.category_name,
                wa.area_name, -- Lấy từ warehouse_areas
                (p.unit_price * p.stock_quantity) as total_value
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN product_locations pl ON p.product_id = pl.product_id -- JOIN để lấy kệ
            LEFT JOIN shelves s ON pl.shelf_id = s.shelf_id           -- JOIN để lấy khu vực của kệ
            LEFT JOIN warehouse_areas wa ON s.area_id = wa.area_id   -- SỬA: areas -> warehouse_areas
            $whereClause
            GROUP BY p.product_id, p.product_name, p.sku, p.stock_quantity, p.min_stock_level, p.unit_price, p.created_at, c.category_name, wa.area_name
            ORDER BY p.product_name
        ";
      
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC); //

        // Tính toán thống kê từ $details đã lọc (nếu có)
        $totalItems = 0;
        $totalValue = 0;
        $lowStockItems = 0;
        $outOfStockItems = 0;

        foreach($details as $item){
            $totalItems += $item['stock_quantity']; // Tổng số lượng các item, hoặc count($details) nếu là số loại SP
            $totalValue += $item['total_value'];
            if($item['stock_quantity'] > 0 && $item['stock_quantity'] <= ($item['min_stock_level'] ?? 0) ){ // Giả sử min_stock_level là 0 nếu null
                $lowStockItems++;
            }
            if($item['stock_quantity'] == 0){
                $outOfStockItems++;
            }
        }

        // Dữ liệu cho biểu đồ (ví dụ: tồn kho theo danh mục từ $details đã lọc)
        $categoryDistribution = [];
        foreach ($details as $item) {
            $catName = $item['category_name'] ?? 'Không xác định';
            if (!isset($categoryDistribution[$catName])) {
                $categoryDistribution[$catName] = 0;
            }
            $categoryDistribution[$catName] += $item['stock_quantity'];
        }
        $chartLabels = array_keys($categoryDistribution);
        $chartValues = array_values($categoryDistribution);


        echo json_encode([
            'success' => true,
            'data' => [
                'stats' => [
                    'totalItems' => $totalItems, // Có thể là count($details) nếu muốn số loại sản phẩm
                    'totalValue' => $totalValue,
                    'lowStockItems' => $lowStockItems,
                    'outOfStockItems' => $outOfStockItems
                ],
                'charts' => [
                    'trend' => ['labels' => [], 'data' => []], // Cần logic cho trend nếu có
                    'distribution' => ['labels' => $chartLabels, 'data' => $chartValues]
                ],
                'details' => $details
            ]
        ]);

    } catch (PDOException $e) { //
        error_log("Inventory report PDO error: " . $e->getMessage() . " SQL: " . (isset($stmt) && $stmt instanceof PDOStatement ? $stmt->queryString : "N/A"));
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải báo cáo tồn kho.'
            // , 'debug' => $e->getMessage() // Bật khi debug
        ]);
    }
}

/**
 * Báo cáo nhập xuất
 */
function getImportExportReport() {
    global $pdo;
    header('Content-Type: application/json');

    try {
        // Lấy tham số lọc từ POST body (theo js/baocaothongke.js)
        $request_payload = file_get_contents('php://input');
        $filters = json_decode($request_payload, true);

        // Làm sạch và lấy giá trị mặc định cho các bộ lọc
        $reportType = cleanInput($filters['reportType'] ?? 'both');
        $dateFrom = cleanInput($filters['fromDate'] ?? date('Y-m-01'));
        $dateTo = cleanInput($filters['toDate'] ?? date('Y-m-d'));
        $productId = cleanInput($filters['product'] ?? ''); // Đây là product_id
        $supplierId = cleanInput($filters['supplier'] ?? ''); // Đây là supplier_id
        $userId = cleanInput($filters['user'] ?? '');         // Đây là user_id (created_by)

        $details = [];

        // --- Query cho PHIẾU NHẬP (IMPORT) ---
        if ($reportType === 'both' || $reportType === 'import') {
            $sqlImport = "
                SELECT
                    io.import_id as order_id,         -- Khớp với CSDL
                    io.import_code as order_code,     -- Khớp với CSDL
                    io.import_date as order_date,     -- Khớp với CSDL
                    'Nhập' as type,
                    s.supplier_name,
                    u.full_name as created_by_name,
                    p.product_name,
                    p.sku as product_sku,             -- Thêm SKU sản phẩm
                    id.quantity,
                    id.unit_price,                    -- Giá nhập
                    (id.quantity * id.unit_price) as total_item_value,
                    io.status
                FROM import_details id
                JOIN import_orders io ON id.import_id = io.import_id
                JOIN products p ON id.product_id = p.product_id
                LEFT JOIN suppliers s ON io.supplier_id = s.supplier_id
                LEFT JOIN users u ON io.created_by = u.user_id
                WHERE io.status = 'approved' -- Chỉ lấy phiếu đã duyệt
                AND DATE(io.import_date) BETWEEN :dateFrom AND :dateTo
            ";
            $paramsImport = [':dateFrom' => $dateFrom, ':dateTo' => $dateTo];

            if (!empty($productId)) {
                $sqlImport .= " AND id.product_id = :productId";
                $paramsImport[':productId'] = $productId;
            }
            if (!empty($supplierId)) {
                $sqlImport .= " AND io.supplier_id = :supplierId";
                $paramsImport[':supplierId'] = $supplierId;
            }
            if (!empty($userId)) {
                $sqlImport .= " AND io.created_by = :userId";
                $paramsImport[':userId'] = $userId;
            }
            $sqlImport .= " ORDER BY io.import_date DESC, p.product_name ASC"; // Sắp xếp thêm theo tên SP

            $stmtImport = $pdo->prepare($sqlImport);
            $stmtImport->execute($paramsImport);
            $imports = $stmtImport->fetchAll(PDO::FETCH_ASSOC);
            foreach ($imports as $item) {
                $item['customer_or_supplier_name'] = $item['supplier_name']; // Để đồng bộ tên cột với export
                $details[] = $item;
            }
        }

        // --- Query cho PHIẾU XUẤT (EXPORT) ---
        if ($reportType === 'both' || $reportType === 'export') {
            $sqlExport = "
                SELECT
                    eo.export_id as order_id,         -- Khớp với CSDL
                    eo.export_code as order_code,     -- Khớp với CSDL
                    eo.export_date as order_date,     -- Khớp với CSDL
                    'Xuất' as type,
                    eo.destination as customer_or_supplier_name, -- Đây là tên khách hàng/địa điểm đến
                    u.full_name as created_by_name,
                    p.product_name,
                    p.sku as product_sku,             -- Thêm SKU sản phẩm
                    ed.quantity,
                    ed.unit_price,                    -- Đây là giá bán ra
                    (ed.quantity * ed.unit_price) as total_item_value,
                    eo.status
                FROM export_details ed
                JOIN export_orders eo ON ed.export_id = eo.export_id
                JOIN products p ON ed.product_id = p.product_id
                LEFT JOIN users u ON eo.created_by = u.user_id
                WHERE eo.status = 'approved' -- Chỉ lấy phiếu đã duyệt
                AND DATE(eo.export_date) BETWEEN :dateFrom AND :dateTo
            ";
            $paramsExport = [':dateFrom' => $dateFrom, ':dateTo' => $dateTo];

            if (!empty($productId)) {
                $sqlExport .= " AND ed.product_id = :productId";
                $paramsExport[':productId'] = $productId;
            }
            // Đối với phiếu xuất, không có supplierId, có thể bạn muốn lọc theo `eo.destination` nếu đó là khách hàng.
            // Hiện tại bỏ qua filter supplier cho export.
            if (!empty($userId)) {
                $sqlExport .= " AND eo.created_by = :userId";
                $paramsExport[':userId'] = $userId;
            }
            $sqlExport .= " ORDER BY eo.export_date DESC, p.product_name ASC"; // Sắp xếp thêm

            $stmtExport = $pdo->prepare($sqlExport);
            $stmtExport->execute($paramsExport);
            $exportsData = $stmtExport->fetchAll(PDO::FETCH_ASSOC);
            foreach ($exportsData as $item) {
                $details[] = $item;
            }
        }
        
        // Sắp xếp lại toàn bộ mảng $details theo ngày nếu gộp cả hai loại
        if ($reportType === 'both' && count($details) > 0) {
            usort($details, function($a, $b) {
                $dateA = strtotime($a['order_date']);
                $dateB = strtotime($b['order_date']);
                if ($dateA == $dateB) {
                    return 0;
                }
                return ($dateA < $dateB) ? 1 : -1; // Sắp xếp giảm dần theo ngày
            });
        }

        // Tính toán thống kê tổng quan từ mảng $details đã được lọc
        $totalImportOrdersCount = 0; // Đếm số phiếu nhập duy nhất
        $totalExportOrdersCount = 0; // Đếm số phiếu xuất duy nhất
        $totalImportValue = 0;
        $totalExportValue = 0;
        $uniqueImportOrderIds = [];
        $uniqueExportOrderIds = [];

        foreach($details as $item) {
            if ($item['type'] === 'Nhập') {
                if (!isset($uniqueImportOrderIds[$item['order_id']])) {
                    $totalImportOrdersCount++;
                    $uniqueImportOrderIds[$item['order_id']] = true;
                }
                $totalImportValue += (float)$item['total_item_value'];
            } elseif ($item['type'] === 'Xuất') {
                 if (!isset($uniqueExportOrderIds[$item['order_id']])) {
                    $totalExportOrdersCount++;
                    $uniqueExportOrderIds[$item['order_id']] = true;
                }
                $totalExportValue += (float)$item['total_item_value'];
            }
        }
        
        // Chuẩn bị dữ liệu cho biểu đồ (ví dụ: tổng giá trị nhập/xuất theo ngày)
        $chartDataPoints = [];
        foreach ($details as $item) {
            $dateKey = date('Y-m-d', strtotime($item['order_date']));
            if (!isset($chartDataPoints[$dateKey])) {
                $chartDataPoints[$dateKey] = ['import' => 0, 'export' => 0];
            }
            if ($item['type'] === 'Nhập') {
                $chartDataPoints[$dateKey]['import'] += (float)$item['total_item_value'];
            } elseif ($item['type'] === 'Xuất') {
                $chartDataPoints[$dateKey]['export'] += (float)$item['total_item_value'];
            }
        }
        ksort($chartDataPoints); // Sắp xếp theo ngày

        $chartLabels = [];
        $importValues = [];
        $exportValues = [];
        foreach ($chartDataPoints as $date => $values) {
            $chartLabels[] = date('d/m', strtotime($date));
            $importValues[] = $values['import'];
            $exportValues[] = $values['export'];
        }
       
        echo json_encode([
            'success' => true,
            'data' => [
                'stats' => [
                    'totalImportOrders' => $totalImportOrdersCount,
                    'totalExportOrders' => $totalExportOrdersCount,
                    'totalImportValue' => $totalImportValue,
                    'totalExportValue' => $totalExportValue,
                ],
                'chart' => [
                    'labels' => $chartLabels,
                    'imports' => $importValues,
                    'exports' => $exportValues,
                ],
                'details' => $details
            ]
        ]);

    } catch (PDOException $e) {
        error_log("Import/Export report PDO error: " . $e->getMessage() . " SQL Import: " . (isset($stmtImport) && $stmtImport instanceof PDOStatement ? $stmtImport->queryString : "N/A") . " SQL Export: " . (isset($stmtExport) && $stmtExport instanceof PDOStatement ? $stmtExport->queryString : "N/A"));
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải báo cáo nhập xuất (Lỗi CSDL).'
            // , 'debug' => $e->getMessage() // Chỉ bật khi debug
        ]);
    } catch (Exception $e) {
        error_log("Import/Export report general error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi không xác định xảy ra khi tải báo cáo nhập xuất.'
        ]);
    }
}


/**
 * Phân tích xu hướng
 */
function generateAnalysis() { 
    global $pdo;
    header('Content-Type: application/json');

    try {

        $request_payload = file_get_contents('php://input');
        $filters = json_decode($request_payload, true);

        $analysisType = cleanInput($filters['analysisType'] ?? 'consumption');
        $period = (int)($filters['period'] ?? 30);
        $categoryId = cleanInput($filters['category'] ?? '');

        $dataPoints = [];
        $labels = [];
        $values = [];

        // Mặc định là xu hướng xuất kho
        $sql = "
            SELECT DATE(eo.export_date) as report_date, SUM(ed.quantity) as total_quantity
            FROM export_details ed
            JOIN export_orders eo ON ed.export_id = eo.export_id
            WHERE eo.status = 'approved'
            AND eo.export_date >= DATE_SUB(NOW(), INTERVAL :period DAY)
        ";
        $params = [':period' => $period];

        if (!empty($categoryId)) {
            $sql .= " AND EXISTS (SELECT 1 FROM products p WHERE p.product_id = ed.product_id AND p.category_id = :category_id)";
            $params[':category_id'] = $categoryId;
        }

        $sql .= " GROUP BY DATE(eo.export_date) ORDER BY report_date ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $labels[] = date('d/m', strtotime($row['report_date']));
            $values[] = (int)$row['total_quantity'];
        }

        // TODO: Xây dựng logic cho các analysisType khác: 'demand', 'seasonal', 'performance'

        // Dữ liệu tóm tắt và dự báo mẫu
        $summary = [
            'mainResult' => "Phân tích xu hướng '" . htmlspecialchars($analysisType) . "' cho " . $period . " ngày.",
            'trend' => "Dữ liệu cho thấy xu hướng tăng/giảm dựa trên các yếu tố...",
            'highlights' => ["Điểm 1", "Điểm 2"]
        ];
        $forecast = [
            'prediction' => "Dự báo cho kỳ tới...",
            'recommendations' => ["Khuyến nghị 1", "Khuyến nghị 2"],
            'risks' => ["Rủi ro 1"]
        ];

        echo json_encode([
            'success' => true,
            'data' => [
                'chart' => ['labels' => $labels, 'data' => $values],
                'summary' => $summary,
                'forecast' => $forecast
            ]
        ]);

    } catch (PDOException $e) {
        error_log("Generate analysis PDO error: " . $e->getMessage() . " SQL: " . (isset($stmt) && $stmt instanceof PDOStatement ? $stmt->queryString : "N/A"));
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi truy vấn cơ sở dữ liệu khi tạo phân tích.'
        ]);
    } catch (Exception $e) {
        error_log("Generate analysis general error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi không xác định xảy ra khi tạo phân tích.'
        ]);
    }
}

/**
 * Lấy danh sách categories
 */
function getCategories() {
    global $pdo;
    header('Content-Type: application/json'); 
    try {
        $stmt = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC); 

        echo json_encode([
            'success' => true,
            'data' => $categories
        ]);

    } catch (PDOException $e) { 
        error_log("Get categories PDO error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải danh mục.'
        ]);
    }
}

/**
 * Lấy danh sách areas
 */
function getAreas() {
    global $pdo;
    header('Content-Type: application/json'); 
    try {
        $stmt = $pdo->query("SELECT area_id, area_name FROM warehouse_areas ORDER BY area_name"); 
        $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $areas
        ]);

    } catch (PDOException $e) { 
        error_log("Get areas PDO error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải khu vực.'
        ]);
    }
}

/**
 * Lấy danh sách products
 */
function getProducts() {
    global $pdo;
    header('Content-Type: application/json'); 
    try {
        $stmt = $pdo->query("
            SELECT product_id, product_name, sku
            FROM products
            WHERE is_active = 1 -- Hoặc status != 'discontinued'
            ORDER BY product_name
        "); 
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC); 

        echo json_encode([
            'success' => true,
            'data' => $products
        ]);

    } catch (PDOException $e) { 
        error_log("Get products PDO error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải sản phẩm.'
        ]);
    }
}

/**
 * Lấy danh sách suppliers
 */
function getSuppliers() {
    global $pdo;
    header('Content-Type: application/json'); 
    try {
        $stmt = $pdo->query("
            SELECT supplier_id, supplier_name
            FROM suppliers
            WHERE status = 'active' 
            ORDER BY supplier_name
        ");
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC); 

        echo json_encode([
            'success' => true,
            'data' => $suppliers
        ]);

    } catch (PDOException $e) {
        error_log("Get suppliers PDO error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải nhà cung cấp.'
        ]);
    }
}

/**
 * Lấy danh sách users
 */
function getUsers() {
    global $pdo;
    header('Content-Type: application/json'); 
    try {
        $stmt = $pdo->query("
            SELECT user_id, full_name, username
            FROM users
            WHERE is_active = 1 -- Dựa trên CSDL của bạn
            ORDER BY full_name
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC); 

        echo json_encode([
            'success' => true,
            'data' => $users
        ]);

    } catch (PDOException $e) { 
        error_log("Get users PDO error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tải người dùng.'
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

function getFinancialReport() {
    global $pdo;
    header('Content-Type: application/json');

    try {

        $request_payload = file_get_contents('php://input');
        $filters = json_decode($request_payload, true);

   
        $fromDate = cleanInput($filters['fromDate'] ?? date('Y-m-01'));
        $toDate = cleanInput($filters['toDate'] ?? date('Y-m-d'));

        $stmtRevenue = $pdo->prepare("
            SELECT COALESCE(SUM(ed.quantity * ed.unit_price), 0) as total_revenue
            FROM export_details ed
            JOIN export_orders eo ON ed.export_id = eo.export_id
            WHERE eo.status = 'approved'
            AND DATE(eo.export_date) BETWEEN :fromDate AND :toDate
        ");
        $stmtRevenue->execute([':fromDate' => $fromDate, ':toDate' => $toDate]);
        $totalRevenue = (float)$stmtRevenue->fetchColumn();

        $stmtCost = $pdo->prepare("
            SELECT COALESCE(SUM(id.quantity * id.unit_price), 0) as total_cost
            FROM import_details id
            JOIN import_orders io ON id.import_id = io.import_id
            WHERE io.status = 'approved'
            AND DATE(io.import_date) BETWEEN :fromDate AND :toDate
        ");
        $stmtCost->execute([':fromDate' => $fromDate, ':toDate' => $toDate]);
        $totalCostOfGoods = (float)$stmtCost->fetchColumn(); 

        $totalProfit = $totalRevenue - $totalCostOfGoods; 

       
        $stmtInventoryValue = $pdo->query("
            SELECT SUM(unit_price * stock_quantity)
            FROM products
            WHERE status = 'in_stock' -- Hoặc is_active = 1
        ");
        $currentInventoryValue = (float)($stmtInventoryValue->fetchColumn() ?? 0);

        $chartData = [
            'labels' => [$fromDate . ' - ' . $toDate], 
            'revenue' => [$totalRevenue],
            'cost' => [$totalCostOfGoods],
            'profit' => [$totalProfit]
        ];

        echo json_encode([
            'success' => true,
            'data' => [
                'stats' => [
                    'totalRevenue' => $totalRevenue,
                    'totalCost' => $totalCostOfGoods, 
                    'totalProfit' => $totalProfit,
                    'profitMargin' => ($totalRevenue > 0) ? ($totalProfit / $totalRevenue) * 100 : 0,
                    'currentInventoryValue' => $currentInventoryValue
                ],
                'chart' => $chartData 
            ]
        ]);

    } catch (PDOException $e) {
        error_log("Financial report PDO error: " . $e->getMessage() . " SQL: " . (isset($stmtRevenue) && $stmtRevenue instanceof PDOStatement ? $stmtRevenue->queryString : (isset($stmtCost) ? $stmtCost->queryString : "N/A")));
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi truy vấn cơ sở dữ liệu khi tải báo cáo tài chính.'
        
        ]);
    } catch (Exception $e) {
        error_log("Financial report general error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi không xác định xảy ra khi tải báo cáo tài chính.'
           
        ]);
    }
}

function getPerformanceReport() {
    global $pdo;
    header('Content-Type: application/json');

    try {
      
        $stmtMaxCapacity = $pdo->query("SELECT SUM(max_capacity) FROM shelves");
        $totalMaxCapacity = (float)($stmtMaxCapacity->fetchColumn() ?? 1); 
        if ($totalMaxCapacity == 0) $totalMaxCapacity = 1; 

       
        $stmtCurrentCapacity = $pdo->query("SELECT SUM(current_capacity) FROM shelves");
        $totalCurrentCapacity = (float)($stmtCurrentCapacity->fetchColumn() ?? 0);

        $warehouseFillRate = ($totalCurrentCapacity / $totalMaxCapacity) * 100;

        
        $stmtAvgProcessingTime = $pdo->prepare("
            SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours
            FROM export_orders
            WHERE status = 'approved' AND updated_at > created_at
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) -- Ví dụ trong 30 ngày
        ");
        $stmtAvgProcessingTime->execute();
        $avgProcessingTimeHours = (float)($stmtAvgProcessingTime->fetchColumn() ?? 0);

       
        $accuracyRate = 98.5; 

        $stmtCOGS = $pdo->prepare("
            SELECT SUM(ed.quantity * ed.unit_price)
            FROM export_details ed
            JOIN export_orders eo ON ed.export_id = eo.export_id
            WHERE eo.status = 'approved'
            AND DATE(eo.export_date) BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW() -- Ví dụ 30 ngày
        ");
        $stmtCOGS->execute();
        $cogs = (float)($stmtCOGS->fetchColumn() ?? 0);

        $avgInventoryValue = ($currentInventoryValue ?? $totalMaxCapacity) > 0 ? ($currentInventoryValue ?? $totalMaxCapacity) : 1; // $currentInventoryValue từ getFinancialReport nếu có, hoặc tính lại
        $stmtCurrentInvValue = $pdo->query("SELECT SUM(unit_price * stock_quantity) FROM products WHERE status = 'in_stock'");
        $avgInventoryValue = (float)($stmtCurrentInvValue->fetchColumn() ?? 1);
        if($avgInventoryValue == 0) $avgInventoryValue = 1;


        $inventoryTurnover = $cogs / $avgInventoryValue;


       
        $performanceChartData = [
            'labels' => ['Q1', 'Q2', 'Q3', 'Q4'], // Ví dụ theo quý
            'data' => [rand(70,95), rand(70,95), rand(70,95), rand(70,95)] 
        ];
        $performanceComparisonData = [
             // Dữ liệu cho radar chart, giá trị từ 0-100
            'data' => [
                round($warehouseFillRate,1),
                round(100 - ($avgProcessingTimeHours/24 *100),1), 
                round($accuracyRate,1),
                round($inventoryTurnover * 10,1) > 100 ? 100 : round($inventoryTurnover * 10,1) 
            ]
        ];
        // Thêm một giá trị tổng hợp nếu có
        $performanceComparisonData['data'][] = round(array_sum($performanceComparisonData['data']) / count($performanceComparisonData['data']), 1);


        echo json_encode([
            'success' => true,
            'data' => [
                'stats' => [
                    'fillRate' => round($warehouseFillRate, 2),
                    'avgProcessingTime' => round($avgProcessingTimeHours, 2),
                    'accuracyRate' => round($accuracyRate, 2),
                    'turnover' => round($inventoryTurnover, 2)
                ],
                'charts' => [
                    'timeline' => $performanceChartData,
                    'comparison' => $performanceComparisonData
                ]
            ]
        ]);

    } catch (PDOException $e) {
        error_log("Performance report PDO error: " . $e->getMessage() . " SQL: " . (isset($stmtMaxCapacity) && $stmtMaxCapacity instanceof PDOStatement ? $stmtMaxCapacity->queryString : "N/A"));
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi truy vấn cơ sở dữ liệu khi tải báo cáo hiệu suất.'
            // , 'debug_error' => $e->getMessage()
        ]);
    } catch (Exception $e) {
        error_log("Performance report general error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi không xác định xảy ra khi tải báo cáo hiệu suất.'
            // , 'debug_error' => $e->getMessage()
        ]);
    }
}

?> 