<?php
session_start();
require_once '../config/connect.php';
require_once '../inc/auth.php';
require_once '../inc/security.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để tiếp tục']);
    exit;
}

// Kiểm tra quyền admin hoặc employee
if (!isAdmin() && (!isset($_SESSION['role']) || $_SESSION['role'] != 'employee')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get_area':
            getArea();
            break;
        case 'save_area':
            saveArea();
            break;
        case 'delete_area':
            deleteArea();
            break;
        case 'get_shelf':
            getShelf();
            break;
        case 'save_shelf':
            saveShelf();
            break;
        case 'delete_shelf':
            deleteShelf();
            break;
        case 'get_shelf_details':
            getShelfDetails();
            break;
        case 'suggest_shelf':
            suggestShelf();
            break;
        case 'move_product':
            moveProduct();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
    }
} catch (Exception $e) {
    error_log("Lỗi API warehouse: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi hệ thống']);
}

// Lấy thông tin khu vực
function getArea() {
    global $pdo;
    
    $areaId = cleanInput($_GET['id'] ?? '');
    if (empty($areaId)) {
        echo json_encode(['success' => false, 'message' => 'ID khu vực không hợp lệ']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM warehouse_areas WHERE area_id = ?");
    $stmt->execute([$areaId]);
    $area = $stmt->fetch();
    
    if (!$area) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy khu vực']);
        return;
    }
    
    echo json_encode(['success' => true, 'area' => $area]);
}

// Lưu khu vực (thêm/sửa)
function saveArea() {
    global $pdo;
    
    $areaId = cleanInput($_POST['area_id'] ?? '');
    $areaName = cleanInput($_POST['area_name'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    
    // Validate
    if (empty($areaName)) {
        echo json_encode(['success' => false, 'message' => 'Tên khu vực không được để trống']);
        return;
    }
    
    if (strlen($areaName) > 50) {
        echo json_encode(['success' => false, 'message' => 'Tên khu vực không được vượt quá 50 ký tự']);
        return;
    }
    
    if (!empty($areaId)) {
        // Cập nhật khu vực
        $checkStmt = $pdo->prepare("SELECT area_id FROM warehouse_areas WHERE area_name = ? AND area_id != ?");
        $checkStmt->execute([$areaName, $areaId]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Tên khu vực đã tồn tại']);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE warehouse_areas SET area_name = ?, description = ?, updated_at = NOW() WHERE area_id = ?");
        $result = $stmt->execute([$areaName, $description, $areaId]);
        
        if ($result) {
            logUserAction($_SESSION['user_id'], 'EDIT_AREA', "Cập nhật khu vực: $areaName (ID: $areaId)");
            echo json_encode(['success' => true, 'message' => 'Cập nhật khu vực thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cập nhật khu vực thất bại']);
        }
    } else {
        // Thêm khu vực mới
        $checkStmt = $pdo->prepare("SELECT area_id FROM warehouse_areas WHERE area_name = ?");
        $checkStmt->execute([$areaName]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Tên khu vực đã tồn tại']);
            return;
        }
        
        $stmt = $pdo->prepare("INSERT INTO warehouse_areas (area_name, description) VALUES (?, ?)");
        $result = $stmt->execute([$areaName, $description]);
        
        if ($result) {
            $newAreaId = $pdo->lastInsertId();
            logUserAction($_SESSION['user_id'], 'ADD_AREA', "Thêm khu vực mới: $areaName (ID: $newAreaId)");
            echo json_encode(['success' => true, 'message' => 'Thêm khu vực thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Thêm khu vực thất bại']);
        }
    }
}

// Xóa khu vực
function deleteArea() {
    global $pdo;
    
    $areaId = cleanInput($_POST['area_id'] ?? '');
    if (empty($areaId)) {
        echo json_encode(['success' => false, 'message' => 'ID khu vực không hợp lệ']);
        return;
    }
    
    // Kiểm tra xem khu vực có kệ nào không
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM shelves WHERE area_id = ?");
    $checkStmt->execute([$areaId]);
    $shelfCount = $checkStmt->fetchColumn();
    
    if ($shelfCount > 0) {
        echo json_encode(['success' => false, 'message' => "Không thể xóa khu vực này vì còn $shelfCount kệ đang sử dụng"]);
        return;
    }
    
    // Lấy tên khu vực để log
    $nameStmt = $pdo->prepare("SELECT area_name FROM warehouse_areas WHERE area_id = ?");
    $nameStmt->execute([$areaId]);
    $areaName = $nameStmt->fetchColumn();
    
    $stmt = $pdo->prepare("DELETE FROM warehouse_areas WHERE area_id = ?");
    $result = $stmt->execute([$areaId]);
    
    if ($result) {
        logUserAction($_SESSION['user_id'], 'DELETE_AREA', "Xóa khu vực: $areaName (ID: $areaId)");
        echo json_encode(['success' => true, 'message' => 'Xóa khu vực thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Xóa khu vực thất bại']);
    }
}

// Lấy thông tin kệ
function getShelf() {
    global $pdo;
    
    $shelfId = cleanInput($_GET['id'] ?? '');
    if (empty($shelfId)) {
        echo json_encode(['success' => false, 'message' => 'ID kệ không hợp lệ']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM shelves WHERE shelf_id = ?");
    $stmt->execute([$shelfId]);
    $shelf = $stmt->fetch();
    
    if (!$shelf) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy kệ']);
        return;
    }
    
    echo json_encode(['success' => true, 'shelf' => $shelf]);
}

// Lưu kệ (thêm/sửa)
function saveShelf() {
    global $pdo;
    
    $shelfId = cleanInput($_POST['shelf_id'] ?? '');
    $shelfCode = cleanInput($_POST['shelf_code'] ?? '');
    $areaId = cleanInput($_POST['area_id'] ?? '');
    $maxCapacity = cleanInput($_POST['max_capacity'] ?? '');
    $coordinates = cleanInput($_POST['coordinates'] ?? '');
    $locationDescription = cleanInput($_POST['location_description'] ?? '');
    
    // Validate
    if (empty($shelfCode) || empty($areaId) || empty($maxCapacity)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
        return;
    }
    
    if (!is_numeric($maxCapacity) || $maxCapacity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Sức chứa tối đa phải là số dương']);
        return;
    }
    
    // Kiểm tra khu vực tồn tại
    $areaStmt = $pdo->prepare("SELECT area_id FROM warehouse_areas WHERE area_id = ?");
    $areaStmt->execute([$areaId]);
    if (!$areaStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Khu vực không tồn tại']);
        return;
    }
    
    if (!empty($shelfId)) {
        // Cập nhật kệ
        $checkStmt = $pdo->prepare("SELECT shelf_id FROM shelves WHERE shelf_code = ? AND shelf_id != ?");
        $checkStmt->execute([$shelfCode, $shelfId]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Mã kệ đã tồn tại']);
            return;
        }
        
        $stmt = $pdo->prepare("
            UPDATE shelves 
            SET shelf_code = ?, area_id = ?, max_capacity = ?, coordinates = ?, location_description = ?, updated_at = NOW() 
            WHERE shelf_id = ?
        ");
        $result = $stmt->execute([$shelfCode, $areaId, $maxCapacity, $coordinates, $locationDescription, $shelfId]);
        
        if ($result) {
            logUserAction($_SESSION['user_id'], 'EDIT_SHELF', "Cập nhật kệ: $shelfCode (ID: $shelfId)");
            echo json_encode(['success' => true, 'message' => 'Cập nhật kệ thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cập nhật kệ thất bại']);
        }
    } else {
        // Thêm kệ mới
        $checkStmt = $pdo->prepare("SELECT shelf_id FROM shelves WHERE shelf_code = ?");
        $checkStmt->execute([$shelfCode]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Mã kệ đã tồn tại']);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO shelves (shelf_code, area_id, max_capacity, coordinates, location_description) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([$shelfCode, $areaId, $maxCapacity, $coordinates, $locationDescription]);
        
        if ($result) {
            $newShelfId = $pdo->lastInsertId();
            logUserAction($_SESSION['user_id'], 'ADD_SHELF', "Thêm kệ mới: $shelfCode (ID: $newShelfId)");
            echo json_encode(['success' => true, 'message' => 'Thêm kệ thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Thêm kệ thất bại']);
        }
    }
}

// Xóa kệ
function deleteShelf() {
    global $pdo;
    
    $shelfId = cleanInput($_POST['shelf_id'] ?? '');
    if (empty($shelfId)) {
        echo json_encode(['success' => false, 'message' => 'ID kệ không hợp lệ']);
        return;
    }
    
    // Kiểm tra xem kệ có sản phẩm nào không
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM product_locations WHERE shelf_id = ?");
    $checkStmt->execute([$shelfId]);
    $productCount = $checkStmt->fetchColumn();
    
    if ($productCount > 0) {
        echo json_encode(['success' => false, 'message' => "Không thể xóa kệ này vì còn $productCount sản phẩm đang lưu trữ"]);
        return;
    }
    
    // Lấy mã kệ để log
    $codeStmt = $pdo->prepare("SELECT shelf_code FROM shelves WHERE shelf_id = ?");
    $codeStmt->execute([$shelfId]);
    $shelfCode = $codeStmt->fetchColumn();
    
    $stmt = $pdo->prepare("DELETE FROM shelves WHERE shelf_id = ?");
    $result = $stmt->execute([$shelfId]);
    
    if ($result) {
        logUserAction($_SESSION['user_id'], 'DELETE_SHELF', "Xóa kệ: $shelfCode (ID: $shelfId)");
        echo json_encode(['success' => true, 'message' => 'Xóa kệ thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Xóa kệ thất bại']);
    }
}

// Lấy chi tiết kệ
function getShelfDetails() {
    global $pdo;
    
    $shelfId = cleanInput($_GET['id'] ?? '');
    if (empty($shelfId)) {
        echo json_encode(['success' => false, 'message' => 'ID kệ không hợp lệ']);
        return;
    }
    
    // Lấy thông tin kệ
    $shelfStmt = $pdo->prepare("
        SELECT s.*, wa.area_name,
               (s.current_capacity / s.max_capacity * 100) as utilization_percent
        FROM shelves s
        LEFT JOIN warehouse_areas wa ON s.area_id = wa.area_id
        WHERE s.shelf_id = ?
    ");
    $shelfStmt->execute([$shelfId]);
    $shelf = $shelfStmt->fetch();
    
    if (!$shelf) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy kệ']);
        return;
    }
    
    // Lấy danh sách sản phẩm trên kệ
    $productsStmt = $pdo->prepare("
        SELECT pl.*, p.product_name, p.sku, p.volume, c.category_name
        FROM product_locations pl
        LEFT JOIN products p ON pl.product_id = p.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE pl.shelf_id = ?
        ORDER BY p.product_name
    ");
    $productsStmt->execute([$shelfId]);
    $products = $productsStmt->fetchAll();
    
    // Tạo HTML cho modal
    $html = '
    <div class="shelf-info mb-4">
        <div class="row">
            <div class="col-md-6">
                <h6>Thông tin kệ</h6>
                <table class="table table-sm">
                    <tr><td><strong>Mã kệ:</strong></td><td>' . htmlspecialchars($shelf['shelf_code']) . '</td></tr>
                    <tr><td><strong>Khu vực:</strong></td><td>' . htmlspecialchars($shelf['area_name']) . '</td></tr>
                    <tr><td><strong>Vị trí:</strong></td><td>' . htmlspecialchars($shelf['location_description']) . '</td></tr>
                    <tr><td><strong>Tọa độ:</strong></td><td>' . htmlspecialchars($shelf['coordinates']) . '</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Sức chứa</h6>
                <div class="capacity-info">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Đã sử dụng:</span>
                        <span>' . number_format($shelf['current_capacity'], 1) . ' / ' . number_format($shelf['max_capacity'], 1) . ' dm³</span>
                    </div>
                    <div class="progress mb-2" style="height: 25px;">
                        <div class="progress-bar ' . ($shelf['utilization_percent'] > 80 ? 'bg-danger' : ($shelf['utilization_percent'] > 60 ? 'bg-warning' : 'bg-success')) . '" 
                             style="width: ' . $shelf['utilization_percent'] . '%">
                            ' . number_format($shelf['utilization_percent'], 1) . '%
                        </div>
                    </div>
                    <small class="text-muted">Còn trống: ' . number_format($shelf['max_capacity'] - $shelf['current_capacity'], 1) . ' dm³</small>
                </div>
            </div>
        </div>
    </div>
    
    <h6>Danh sách sản phẩm (' . count($products) . ' sản phẩm)</h6>
    ';
    
    if (empty($products)) {
        $html .= '<div class="alert alert-info">Kệ này hiện chưa có sản phẩm nào.</div>';
    } else {
        $html .= '
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Tên sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Số lượng</th>
                        <th>Thể tích</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($products as $product) {
            $totalVolume = $product['quantity'] * $product['volume'];
            $html .= '
                <tr>
                    <td><small>' . htmlspecialchars($product['sku']) . '</small></td>
                    <td>' . htmlspecialchars($product['product_name']) . '</td>
                    <td><span class="badge bg-secondary">' . htmlspecialchars($product['category_name']) . '</span></td>
                    <td><span class="badge bg-info">' . $product['quantity'] . '</span></td>
                    <td>' . number_format($totalVolume, 1) . ' dm³</td>
                    <td>
                        <button class="btn btn-sm btn-outline-warning" onclick="moveProductModal(' . $product['product_id'] . ', ' . $shelfId . ')">
                            <i class="fas fa-exchange-alt"></i> Di chuyển
                        </button>
                    </td>
                </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }
    
    echo json_encode(['success' => true, 'html' => $html]);
}

// Gợi ý kệ phù hợp
function suggestShelf() {
    global $pdo;
    
    $productVolume = floatval($_GET['volume'] ?? 0);
    $quantity = intval($_GET['quantity'] ?? 1);
    $excludeShelfId = intval($_GET['exclude_shelf'] ?? 0);
    
    $totalVolume = $productVolume * $quantity;
    
    if ($totalVolume <= 0) {
        echo json_encode(['success' => false, 'message' => 'Thể tích sản phẩm không hợp lệ']);
        return;
    }
    
    // Tìm kệ có đủ chỗ trống
    $query = "
        SELECT s.*, wa.area_name,
               (s.max_capacity - s.current_capacity) as available_capacity,
               (s.current_capacity / s.max_capacity * 100) as utilization_percent
        FROM shelves s
        LEFT JOIN warehouse_areas wa ON s.area_id = wa.area_id
        WHERE (s.max_capacity - s.current_capacity) >= ?
    ";
    
    $params = [$totalVolume];
    
    if ($excludeShelfId > 0) {
        $query .= " AND s.shelf_id != ?";
        $params[] = $excludeShelfId;
    }
    
    $query .= " ORDER BY s.current_capacity ASC, s.max_capacity DESC LIMIT 10";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $suggestions = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'suggestions' => $suggestions]);
}

// Di chuyển sản phẩm giữa các kệ
function moveProduct() {
    global $pdo;
    
    $productId = intval($_POST['product_id'] ?? 0);
    $fromShelfId = intval($_POST['from_shelf_id'] ?? 0);
    $toShelfId = intval($_POST['to_shelf_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    
    if ($productId <= 0 || $fromShelfId <= 0 || $toShelfId <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    if ($fromShelfId == $toShelfId) {
        echo json_encode(['success' => false, 'message' => 'Kệ nguồn và kệ đích không được giống nhau']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Kiểm tra sản phẩm có tồn tại trên kệ nguồn không
        $checkStmt = $pdo->prepare("
            SELECT pl.quantity, p.volume, p.product_name
            FROM product_locations pl
            LEFT JOIN products p ON pl.product_id = p.product_id
            WHERE pl.product_id = ? AND pl.shelf_id = ?
        ");
        $checkStmt->execute([$productId, $fromShelfId]);
        $productData = $checkStmt->fetch();
        
        if (!$productData) {
            throw new Exception('Không tìm thấy sản phẩm trên kệ nguồn');
        }
        
        if ($productData['quantity'] < $quantity) {
            throw new Exception('Số lượng di chuyển vượt quá số lượng hiện có');
        }
        
        $productVolume = $productData['volume'] * $quantity;
        
        // Kiểm tra kệ đích có đủ chỗ không
        $shelfStmt = $pdo->prepare("
            SELECT max_capacity, current_capacity, shelf_code
            FROM shelves 
            WHERE shelf_id = ?
        ");
        $shelfStmt->execute([$toShelfId]);
        $toShelf = $shelfStmt->fetch();
        
        if (!$toShelf) {
            throw new Exception('Kệ đích không tồn tại');
        }
        
        $availableCapacity = $toShelf['max_capacity'] - $toShelf['current_capacity'];
        if ($availableCapacity < $productVolume) {
            throw new Exception('Kệ đích không đủ chỗ trống');
        }
        
        // Cập nhật số lượng trên kệ nguồn
        if ($productData['quantity'] == $quantity) {
            // Xóa hoàn toàn khỏi kệ nguồn
            $deleteStmt = $pdo->prepare("DELETE FROM product_locations WHERE product_id = ? AND shelf_id = ?");
            $deleteStmt->execute([$productId, $fromShelfId]);
        } else {
            // Giảm số lượng
            $updateStmt = $pdo->prepare("UPDATE product_locations SET quantity = quantity - ? WHERE product_id = ? AND shelf_id = ?");
            $updateStmt->execute([$quantity, $productId, $fromShelfId]);
        }
        
        // Cập nhật hoặc thêm vào kệ đích
        $existStmt = $pdo->prepare("SELECT quantity FROM product_locations WHERE product_id = ? AND shelf_id = ?");
        $existStmt->execute([$productId, $toShelfId]);
        $existingQuantity = $existStmt->fetchColumn();
        
        if ($existingQuantity) {
            // Tăng số lượng
            $updateStmt = $pdo->prepare("UPDATE product_locations SET quantity = quantity + ? WHERE product_id = ? AND shelf_id = ?");
            $updateStmt->execute([$quantity, $productId, $toShelfId]);
        } else {
            // Thêm mới
            $insertStmt = $pdo->prepare("INSERT INTO product_locations (product_id, shelf_id, quantity) VALUES (?, ?, ?)");
            $insertStmt->execute([$productId, $toShelfId, $quantity]);
        }
        
        // Cập nhật sức chứa hiện tại của kệ
        $updateFromShelfStmt = $pdo->prepare("UPDATE shelves SET current_capacity = current_capacity - ? WHERE shelf_id = ?");
        $updateFromShelfStmt->execute([$productVolume, $fromShelfId]);
        
        $updateToShelfStmt = $pdo->prepare("UPDATE shelves SET current_capacity = current_capacity + ? WHERE shelf_id = ?");
        $updateToShelfStmt->execute([$productVolume, $toShelfId]);
        
        // Ghi lịch sử di chuyển
        $historyStmt = $pdo->prepare("
            INSERT INTO shelf_product_history (product_id, shelf_id, quantity, created_by) 
            VALUES (?, ?, ?, ?)
        ");
        $historyStmt->execute([$productId, $toShelfId, $quantity, $_SESSION['user_id']]);
        
        $pdo->commit();
        
        // Log hoạt động
        logUserAction($_SESSION['user_id'], 'MOVE_PRODUCT', 
            "Di chuyển {$quantity} sản phẩm {$productData['product_name']} từ kệ $fromShelfId sang kệ {$toShelf['shelf_code']}");
        
        echo json_encode(['success' => true, 'message' => 'Di chuyển sản phẩm thành công']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

?> 