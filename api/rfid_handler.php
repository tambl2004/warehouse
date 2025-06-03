<?php
/**
 * API Handler cho RFID Management
 * File: api/rfid_handler.php
 */

session_start();
require_once '../config/connect.php';
require_once '../inc/auth.php';
require_once '../inc/security.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get_statistics':
            getStatistics();
            break;
        case 'get_tags':
            getRFIDTags();
            break;
        case 'get_tag_by_id':
            getRFIDTagById();
            break;
        case 'create_tag':
            createRFIDTag();
            break;
        case 'update_tag':
            updateRFIDTag();
            break;
        case 'delete_tag':
            deleteRFIDTag();
            break;
        case 'get_devices':
            getDevices();
            break;
        case 'get_device_by_id':
            getDeviceById();
            break;
        case 'create_device':
            createDevice();
            break;
        case 'update_device':
            updateDevice();
            break;
        case 'delete_device':
            deleteDevice();
            break;
        case 'get_scan_logs':
            getScanLogs();
            break;
        case 'get_scan_stats':
            getScanStats();
            break;
        case 'get_alerts':
            getAlerts();
            break;
        case 'get_shelves':
            getShelves();
            break;
        case 'get_areas':
            getAreas();
            break;
        case 'get_device_status':
            getDeviceStatus();
            break;
        case 'get_recent_activity':
            getRecentActivity();
            break;
        case 'scan_rfid':
            scanRFID();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }
} catch (Exception $e) {
    error_log("RFID API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

/**
 * Lấy thống kê tổng quan RFID
 */
function getStatistics() {
    global $conn;
    
    $stats = [];
    
    // Tổng số thẻ RFID
    $result = $conn->query("SELECT COUNT(*) as total FROM rfid_tags");
    $stats['total_tags'] = $result->fetch_assoc()['total'];
    
    // Thiết bị đang hoạt động
    $result = $conn->query("SELECT COUNT(*) as total FROM rfid_devices WHERE status = 'active'");
    $stats['active_devices'] = $result->fetch_assoc()['total'];
    
    // Số lần quét hôm nay
    $result = $conn->query("SELECT COUNT(*) as total FROM rfid_scan_logs WHERE DATE(scan_time) = CURDATE()");
    $stats['today_scans'] = $result->fetch_assoc()['total'];
    
    // Cảnh báo RFID
    $result = $conn->query("SELECT COUNT(*) as total FROM alerts WHERE alert_type IN ('rfid_error', 'device_error') AND DATE(created_at) = CURDATE()");
    $stats['rfid_alerts'] = $result->fetch_assoc()['total'];
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

/**
 * Lấy danh sách thẻ RFID
 */
function getRFIDTags() {
    global $conn;
    
    $search = cleanInput($_GET['search'] ?? '');
    $productId = (int)($_GET['product_id'] ?? 0);
    $shelfId = (int)($_GET['shelf_id'] ?? 0);
    
    $sql = "SELECT rt.*, p.product_name, p.sku, s.shelf_code, s.location_description 
            FROM rfid_tags rt 
            LEFT JOIN products p ON rt.product_id = p.product_id 
            LEFT JOIN shelves s ON rt.shelf_id = s.shelf_id 
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if (!empty($search)) {
        $sql .= " AND (rt.rfid_value LIKE ? OR p.product_name LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }
    
    if ($productId > 0) {
        $sql .= " AND rt.product_id = ?";
        $params[] = $productId;
        $types .= "i";
    }
    
    if ($shelfId > 0) {
        $sql .= " AND rt.shelf_id = ?";
        $params[] = $shelfId;
        $types .= "i";
    }
    
    $sql .= " ORDER BY rt.created_at DESC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    
    $tags = [];
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $tags]);
}

/**
 * Lấy thông tin thẻ RFID theo ID
 */
function getRFIDTagById() {
    global $conn;
    
    $tagId = (int)($_GET['id'] ?? 0);
    
    if ($tagId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        return;
    }
    
    $sql = "SELECT rt.*, p.product_name 
            FROM rfid_tags rt 
            LEFT JOIN products p ON rt.product_id = p.product_id 
            WHERE rt.rfid_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tagId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thẻ RFID']);
    }
}

/**
 * Tạo thẻ RFID mới
 */
function createRFIDTag() {
    global $conn;
    
    $rfidValue = cleanInput($_POST['rfidValue']);
    $productId = (int)$_POST['productId'];
    $lotNumber = cleanInput($_POST['lotNumber'] ?? '');
    $expiryDate = cleanInput($_POST['expiryDate'] ?? '');
    $shelfId = (int)($_POST['shelfId'] ?? 0);
    
    // Kiểm tra dữ liệu
    if (empty($rfidValue) || $productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc']);
        return;
    }
    
    // Kiểm tra RFID value đã tồn tại
    $checkStmt = $conn->prepare("SELECT rfid_id FROM rfid_tags WHERE rfid_value = ?");
    $checkStmt->bind_param("s", $rfidValue);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Giá trị RFID đã tồn tại trong hệ thống']);
        return;
    }
    
    // Tạo thẻ RFID mới
    $sql = "INSERT INTO rfid_tags (rfid_value, product_id, lot_number, expiry_date, shelf_id) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $expiryDateParam = empty($expiryDate) ? null : $expiryDate;
    $shelfIdParam = $shelfId > 0 ? $shelfId : null;
    $stmt->bind_param("sissi", $rfidValue, $productId, $lotNumber, $expiryDateParam, $shelfIdParam);
    
    if ($stmt->execute()) {
        // Ghi log
        logUserAction($_SESSION['user_id'], 'CREATE_RFID_TAG', "Tạo thẻ RFID: $rfidValue cho sản phẩm ID: $productId");
        
        echo json_encode(['success' => true, 'message' => 'Tạo thẻ RFID thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể tạo thẻ RFID']);
    }
}

/**
 * Cập nhật thẻ RFID
 */
function updateRFIDTag() {
    global $conn;
    
    $tagId = (int)$_POST['tagId'];
    $rfidValue = cleanInput($_POST['rfidValue']);
    $productId = (int)$_POST['productId'];
    $lotNumber = cleanInput($_POST['lotNumber'] ?? '');
    $expiryDate = cleanInput($_POST['expiryDate'] ?? '');
    $shelfId = (int)($_POST['shelfId'] ?? 0);
    
    if ($tagId <= 0 || empty($rfidValue) || $productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    // Kiểm tra RFID value đã tồn tại (trừ chính nó)
    $checkStmt = $conn->prepare("SELECT rfid_id FROM rfid_tags WHERE rfid_value = ? AND rfid_id != ?");
    $checkStmt->bind_param("si", $rfidValue, $tagId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Giá trị RFID đã tồn tại trong hệ thống']);
        return;
    }
    
    $sql = "UPDATE rfid_tags SET rfid_value = ?, product_id = ?, lot_number = ?, expiry_date = ?, shelf_id = ? 
            WHERE rfid_id = ?";
    
    $stmt = $conn->prepare($sql);
    $expiryDateParam = empty($expiryDate) ? null : $expiryDate;
    $shelfIdParam = $shelfId > 0 ? $shelfId : null;
    $stmt->bind_param("sissii", $rfidValue, $productId, $lotNumber, $expiryDateParam, $shelfIdParam, $tagId);
    
    if ($stmt->execute()) {
        logUserAction($_SESSION['user_id'], 'UPDATE_RFID_TAG', "Cập nhật thẻ RFID ID: $tagId");
        echo json_encode(['success' => true, 'message' => 'Cập nhật thẻ RFID thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật thẻ RFID']);
    }
}

/**
 * Xóa thẻ RFID
 */
function deleteRFIDTag() {
    global $conn;
    
    $tagId = (int)($_POST['tagId'] ?? $_GET['id'] ?? 0);
    
    if ($tagId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID thẻ RFID không hợp lệ']);
        return;
    }
    
    // Kiểm tra có logs không
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM rfid_scan_logs WHERE rfid_id = ?");
    $checkStmt->bind_param("i", $tagId);
    $checkStmt->execute();
    $count = $checkStmt->get_result()->fetch_assoc()['count'];
    
    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa thẻ RFID đã có lịch sử quét']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM rfid_tags WHERE rfid_id = ?");
    $stmt->bind_param("i", $tagId);
    
    if ($stmt->execute()) {
        logUserAction($_SESSION['user_id'], 'DELETE_RFID_TAG', "Xóa thẻ RFID ID: $tagId");
        echo json_encode(['success' => true, 'message' => 'Xóa thẻ RFID thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa thẻ RFID']);
    }
}

/**
 * Lấy danh sách thiết bị RFID
 */
function getDevices() {
    global $conn;
    
    $sql = "SELECT rd.*, wa.area_name 
            FROM rfid_devices rd 
            LEFT JOIN warehouse_areas wa ON rd.area_id = wa.area_id 
            ORDER BY rd.created_at DESC";
    
    $result = $conn->query($sql);
    $devices = [];
    
    while ($row = $result->fetch_assoc()) {
        $devices[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $devices]);
}

/**
 * Lấy thông tin thiết bị theo ID
 */
function getDeviceById() {
    global $conn;
    
    $deviceId = (int)($_GET['id'] ?? 0);
    
    if ($deviceId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        return;
    }
    
    $sql = "SELECT rd.*, wa.area_name 
            FROM rfid_devices rd 
            LEFT JOIN warehouse_areas wa ON rd.area_id = wa.area_id 
            WHERE rd.device_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $deviceId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thiết bị']);
    }
}

/**
 * Tạo thiết bị RFID mới
 */
function createDevice() {
    global $conn;
    
    $deviceName = cleanInput($_POST['deviceName']);
    $areaId = (int)$_POST['areaId'];
    $batteryLevel = (int)($_POST['batteryLevel'] ?? 0);
    
    if (empty($deviceName) || $areaId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc']);
        return;
    }
    
    $sql = "INSERT INTO rfid_devices (device_name, area_id, battery_level, status) VALUES (?, ?, ?, 'active')";
    $stmt = $conn->prepare($sql);
    $batteryParam = $batteryLevel > 0 ? $batteryLevel : null;
    $stmt->bind_param("sii", $deviceName, $areaId, $batteryParam);
    
    if ($stmt->execute()) {
        logUserAction($_SESSION['user_id'], 'CREATE_RFID_DEVICE', "Tạo thiết bị RFID: $deviceName");
        echo json_encode(['success' => true, 'message' => 'Tạo thiết bị RFID thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể tạo thiết bị RFID']);
    }
}

/**
 * Cập nhật thiết bị RFID
 */
function updateDevice() {
    global $conn;
    
    $deviceId = (int)$_POST['deviceId'];
    $deviceName = cleanInput($_POST['deviceName']);
    $areaId = (int)$_POST['areaId'];
    $batteryLevel = (int)($_POST['batteryLevel'] ?? 0);
    
    if ($deviceId <= 0 || empty($deviceName) || $areaId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    $sql = "UPDATE rfid_devices SET device_name = ?, area_id = ?, battery_level = ? WHERE device_id = ?";
    $stmt = $conn->prepare($sql);
    $batteryParam = $batteryLevel > 0 ? $batteryLevel : null;
    $stmt->bind_param("siii", $deviceName, $areaId, $batteryParam, $deviceId);
    
    if ($stmt->execute()) {
        logUserAction($_SESSION['user_id'], 'UPDATE_RFID_DEVICE', "Cập nhật thiết bị RFID ID: $deviceId");
        echo json_encode(['success' => true, 'message' => 'Cập nhật thiết bị thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật thiết bị']);
    }
}

/**
 * Xóa thiết bị RFID
 */
function deleteDevice() {
    global $conn;
    
    $deviceId = (int)($_POST['deviceId'] ?? $_GET['id'] ?? 0);
    
    if ($deviceId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID thiết bị không hợp lệ']);
        return;
    }
    
    // Kiểm tra có alerts liên quan không
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM alerts WHERE device_id = ?");
    $checkStmt->bind_param("i", $deviceId);
    $checkStmt->execute();
    $count = $checkStmt->get_result()->fetch_assoc()['count'];
    
    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa thiết bị đã có cảnh báo liên quan']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM rfid_devices WHERE device_id = ?");
    $stmt->bind_param("i", $deviceId);
    
    if ($stmt->execute()) {
        logUserAction($_SESSION['user_id'], 'DELETE_RFID_DEVICE', "Xóa thiết bị RFID ID: $deviceId");
        echo json_encode(['success' => true, 'message' => 'Xóa thiết bị thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa thiết bị']);
    }
}

/**
 * Lấy danh sách kệ
 */
function getShelves() {
    global $conn;
    
    $sql = "SELECT s.*, wa.area_name 
            FROM shelves s 
            LEFT JOIN warehouse_areas wa ON s.area_id = wa.area_id 
            ORDER BY s.shelf_code";
    
    $result = $conn->query($sql);
    $shelves = [];
    
    while ($row = $result->fetch_assoc()) {
        $shelves[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $shelves]);
}

/**
 * Lấy danh sách khu vực
 */
function getAreas() {
    global $conn;
    
    $result = $conn->query("SELECT * FROM warehouse_areas ORDER BY area_name");
    $areas = [];
    
    while ($row = $result->fetch_assoc()) {
        $areas[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $areas]);
}

/**
 * Lấy trạng thái thiết bị
 */
function getDeviceStatus() {
    global $conn;
    
    $sql = "SELECT rd.*, wa.area_name 
            FROM rfid_devices rd 
            LEFT JOIN warehouse_areas wa ON rd.area_id = wa.area_id 
            ORDER BY rd.status DESC, rd.device_name";
    
    $result = $conn->query($sql);
    $devices = [];
    
    while ($row = $result->fetch_assoc()) {
        $devices[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $devices]);
}

/**
 * Lấy hoạt động gần đây
 */
function getRecentActivity() {
    global $conn;
    
    $sql = "SELECT rsl.*, rt.rfid_value, p.product_name, u.full_name 
            FROM rfid_scan_logs rsl 
            LEFT JOIN rfid_tags rt ON rsl.rfid_id = rt.rfid_id 
            LEFT JOIN products p ON rt.product_id = p.product_id 
            LEFT JOIN users u ON rsl.user_id = u.user_id 
            ORDER BY rsl.scan_time DESC 
            LIMIT 10";
    
    $result = $conn->query($sql);
    $activities = [];
    
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $activities]);
}

/**
 * Lấy lịch sử quét
 */
function getScanLogs() {
    global $conn;
    
    $fromDate = cleanInput($_GET['from_date'] ?? '');
    $toDate = cleanInput($_GET['to_date'] ?? '');
    $result = cleanInput($_GET['result'] ?? '');
    
    $sql = "SELECT rsl.*, rt.rfid_value, p.product_name, u.full_name 
            FROM rfid_scan_logs rsl 
            LEFT JOIN rfid_tags rt ON rsl.rfid_id = rt.rfid_id 
            LEFT JOIN products p ON rt.product_id = p.product_id 
            LEFT JOIN users u ON rsl.user_id = u.user_id 
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if (!empty($fromDate)) {
        $sql .= " AND DATE(rsl.scan_time) >= ?";
        $params[] = $fromDate;
        $types .= "s";
    }
    
    if (!empty($toDate)) {
        $sql .= " AND DATE(rsl.scan_time) <= ?";
        $params[] = $toDate;
        $types .= "s";
    }
    
    if (!empty($result)) {
        $sql .= " AND rsl.scan_result = ?";
        $params[] = $result;
        $types .= "s";
    }
    
    $sql .= " ORDER BY rsl.scan_time DESC LIMIT 100";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $logs]);
}

/**
 * Lấy thống kê quét để vẽ chart
 */
function getScanStats() {
    global $conn; // $conn là đối tượng kết nối mysqli của bạn

    // Sửa đổi câu SQL để tuân thủ ONLY_FULL_GROUP_BY
    $sql = "SELECT
                DATE_FORMAT(scan_time, '%H:00') as hour_label,
                COUNT(*) as scan_count
            FROM rfid_scan_logs
            WHERE DATE(scan_time) = CURDATE()
            GROUP BY hour_label 
            ORDER BY hour_label"; 

    $result = $conn->query($sql);

    // Thêm kiểm tra lỗi truy vấn
    if (!$result) {
        error_log("Lỗi SQL trong getScanStats: " . $conn->error);
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi lấy dữ liệu thống kê: ' . $conn->error
        ]);
        return;
    }

    $labels = [];
    $values = [];

    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['hour_label'];
        $values[] = (int)$row['scan_count'];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'labels' => $labels,
            'values' => $values
        ]
    ]);
}


/**
 * Lấy cảnh báo RFID
 */
function getAlerts() {
    global $conn;
    
    $sql = "SELECT a.*, p.product_name, rd.device_name 
            FROM alerts a 
            LEFT JOIN products p ON a.product_id = p.product_id 
            LEFT JOIN rfid_devices rd ON a.device_id = rd.device_id 
            WHERE a.alert_type IN ('rfid_error', 'device_error', 'low_stock', 'expiry_soon') 
            ORDER BY a.created_at DESC 
            LIMIT 20";
    
    $result = $conn->query($sql);
    $alerts = [];
    
    while ($row = $result->fetch_assoc()) {
        $alerts[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $alerts]);
}

/**
 * Quét RFID (API cho thiết bị)
 */
function scanRFID() {
    global $conn;
    
    $rfidValue = cleanInput($_POST['rfid_value'] ?? '');
    $deviceId = (int)($_POST['device_id'] ?? 0);
    $userId = $_SESSION['user_id'];
    $description = cleanInput($_POST['description'] ?? '');
    
    if (empty($rfidValue)) {
        echo json_encode(['success' => false, 'message' => 'Giá trị RFID không được để trống']);
        return;
    }
    
    // Tìm thẻ RFID
    $stmt = $conn->prepare("SELECT * FROM rfid_tags WHERE rfid_value = ?");
    $stmt->bind_param("s", $rfidValue);
    $stmt->execute();
    $tag = $stmt->get_result()->fetch_assoc();
    
    $scanResult = $tag ? 'success' : 'failed';
    $rfidId = $tag ? $tag['rfid_id'] : null;
    
    // Ghi log quét
    $logSql = "INSERT INTO rfid_scan_logs (rfid_id, user_id, scan_time, scan_result, description) 
               VALUES (?, ?, NOW(), ?, ?)";
    $logStmt = $conn->prepare($logSql);
    $logStmt->bind_param("iiss", $rfidId, $userId, $scanResult, $description);
    $logStmt->execute();
    
    if ($scanResult === 'success') {
        echo json_encode([
            'success' => true, 
            'message' => 'Quét RFID thành công',
            'data' => $tag
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Thẻ RFID không tồn tại trong hệ thống'
        ]);
    }
}

?> 