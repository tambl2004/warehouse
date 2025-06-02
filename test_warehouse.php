<?php
/**
 * File test và khởi tạo dữ liệu mẫu cho hệ thống quản lý kho
 */

require_once 'config/connect.php';

try {
    // Kiểm tra và thêm cột 'reason' vào bảng shelf_product_history nếu chưa có
    $checkColumn = $pdo->query("SHOW COLUMNS FROM shelf_product_history LIKE 'reason'");
    if ($checkColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE shelf_product_history ADD COLUMN reason VARCHAR(255) DEFAULT NULL AFTER quantity");
        echo "✅ Đã thêm cột 'reason' vào bảng shelf_product_history\n";
    } else {
        echo "✅ Cột 'reason' đã tồn tại trong bảng shelf_product_history\n";
    }
    
    // Kiểm tra và thêm cột 'created_by' vào bảng shelf_product_history nếu chưa có
    $checkCreatedBy = $pdo->query("SHOW COLUMNS FROM shelf_product_history LIKE 'created_by'");
    if ($checkCreatedBy->rowCount() == 0) {
        $pdo->exec("ALTER TABLE shelf_product_history ADD COLUMN created_by INT DEFAULT NULL AFTER reason");
        echo "✅ Đã thêm cột 'created_by' vào bảng shelf_product_history\n";
    } else {
        echo "✅ Cột 'created_by' đã tồn tại trong bảng shelf_product_history\n";
    }
    
    // Cập nhật current_capacity cho các kệ dựa trên sản phẩm thực tế
    echo "\n📊 Đang cập nhật sức chứa hiện tại cho các kệ...\n";
    
    $shelvesStmt = $pdo->query("SELECT shelf_id FROM shelves");
    $shelves = $shelvesStmt->fetchAll();
    
    foreach ($shelves as $shelf) {
        $capacityStmt = $pdo->prepare("
            SELECT COALESCE(SUM(pl.quantity * p.volume), 0) as calculated_capacity
            FROM product_locations pl
            LEFT JOIN products p ON pl.product_id = p.product_id
            WHERE pl.shelf_id = ?
        ");
        $capacityStmt->execute([$shelf['shelf_id']]);
        $calculatedCapacity = $capacityStmt->fetchColumn();
        
        $updateStmt = $pdo->prepare("UPDATE shelves SET current_capacity = ? WHERE shelf_id = ?");
        $updateStmt->execute([$calculatedCapacity, $shelf['shelf_id']]);
    }
    
    echo "✅ Đã cập nhật sức chứa hiện tại cho " . count($shelves) . " kệ\n";
    
    // Thêm dữ liệu mẫu vào product_locations nếu chưa có
    $checkProducts = $pdo->query("SELECT COUNT(*) FROM product_locations")->fetchColumn();
    if ($checkProducts == 0) {
        echo "\n📦 Đang thêm dữ liệu mẫu vào product_locations...\n";
        
        $sampleData = [
            ['product_id' => 1, 'shelf_id' => 1, 'quantity' => 20], // Gạo tám xoan
            ['product_id' => 2, 'shelf_id' => 4, 'quantity' => 15], // Thịt ba chỉ
            ['product_id' => 3, 'shelf_id' => 6, 'quantity' => 100], // Coca Cola
            ['product_id' => 4, 'shelf_id' => 1, 'quantity' => 30], // Nước mắm
            ['product_id' => 5, 'shelf_id' => 8, 'quantity' => 50], // Bát sứ trắng
            ['product_id' => 6, 'shelf_id' => 2, 'quantity' => 40], // Bún khô
            ['product_id' => 7, 'shelf_id' => 5, 'quantity' => 5], // Cá hồi
            ['product_id' => 8, 'shelf_id' => 7, 'quantity' => 80], // Bia Heineken
            ['product_id' => 9, 'shelf_id' => 8, 'quantity' => 3], // MacBook Pro
            ['product_id' => 10, 'shelf_id' => 8, 'quantity' => 8], // iPhone 15
        ];
        
        $insertStmt = $pdo->prepare("INSERT INTO product_locations (product_id, shelf_id, quantity) VALUES (?, ?, ?)");
        
        foreach ($sampleData as $data) {
            $insertStmt->execute([$data['product_id'], $data['shelf_id'], $data['quantity']]);
        }
        
        echo "✅ Đã thêm " . count($sampleData) . " bản ghi vào product_locations\n";
        
        // Cập nhật lại current_capacity sau khi thêm dữ liệu
        echo "\n🔄 Đang cập nhật lại sức chứa sau khi thêm sản phẩm...\n";
        foreach ($shelves as $shelf) {
            $capacityStmt = $pdo->prepare("
                SELECT COALESCE(SUM(pl.quantity * p.volume), 0) as calculated_capacity
                FROM product_locations pl
                LEFT JOIN products p ON pl.product_id = p.product_id
                WHERE pl.shelf_id = ?
            ");
            $capacityStmt->execute([$shelf['shelf_id']]);
            $calculatedCapacity = $capacityStmt->fetchColumn();
            
            $updateStmt = $pdo->prepare("UPDATE shelves SET current_capacity = ? WHERE shelf_id = ?");
            $updateStmt->execute([$calculatedCapacity, $shelf['shelf_id']]);
        }
        echo "✅ Đã cập nhật lại sức chứa hiện tại\n";
    } else {
        echo "✅ Product_locations đã có dữ liệu ($checkProducts bản ghi)\n";
    }
    
    // Thêm một số lịch sử di chuyển mẫu
    $checkHistory = $pdo->query("SELECT COUNT(*) FROM shelf_product_history")->fetchColumn();
    if ($checkHistory == 0) {
        echo "\n📋 Đang thêm lịch sử di chuyển mẫu...\n";
        
        $historyData = [
            ['product_id' => 3, 'shelf_id' => 6, 'quantity' => 50, 'reason' => 'Nhập kho mới'],
            ['product_id' => 1, 'shelf_id' => 1, 'quantity' => 10, 'reason' => 'Tối ưu hóa không gian'],
            ['product_id' => 8, 'shelf_id' => 7, 'quantity' => 30, 'reason' => 'Sắp xếp lại kho'],
        ];
        
        $historyStmt = $pdo->prepare("
            INSERT INTO shelf_product_history (product_id, shelf_id, quantity, moved_at, reason, created_by) 
            VALUES (?, ?, ?, NOW() - INTERVAL FLOOR(RAND() * 30) DAY, ?, 5)
        ");
        
        foreach ($historyData as $data) {
            $historyStmt->execute([$data['product_id'], $data['shelf_id'], $data['quantity'], $data['reason']]);
        }
        
        echo "✅ Đã thêm " . count($historyData) . " bản ghi lịch sử di chuyển\n";
    } else {
        echo "✅ Đã có lịch sử di chuyển ($checkHistory bản ghi)\n";
    }
    
    // Hiển thị thống kê tổng quan
    echo "\n📊 THỐNG KÊ TỔNG QUAN:\n";
    echo "==========================================\n";
    
    $totalAreas = $pdo->query("SELECT COUNT(*) FROM warehouse_areas")->fetchColumn();
    $totalShelves = $pdo->query("SELECT COUNT(*) FROM shelves")->fetchColumn();
    $totalCapacity = $pdo->query("SELECT SUM(max_capacity) FROM shelves")->fetchColumn();
    $usedCapacity = $pdo->query("SELECT SUM(current_capacity) FROM shelves")->fetchColumn();
    $utilizationRate = $totalCapacity > 0 ? ($usedCapacity / $totalCapacity) * 100 : 0;
    $totalProducts = $pdo->query("SELECT SUM(quantity) FROM product_locations")->fetchColumn();
    
    echo "📍 Tổng khu vực: $totalAreas\n";
    echo "📦 Tổng kệ: $totalShelves\n";
    echo "🏗️  Tổng sức chứa: " . number_format($totalCapacity, 1) . " dm³\n";
    echo "📊 Đã sử dụng: " . number_format($usedCapacity, 1) . " dm³\n";
    echo "⚡ Tỷ lệ sử dụng: " . number_format($utilizationRate, 1) . "%\n";
    echo "📦 Tổng sản phẩm: $totalProducts\n";
    echo "==========================================\n";
    
    // Hiển thị chi tiết từng khu vực
    echo "\n🏢 CHI TIẾT CÁC KHU VỰC:\n";
    $areasStmt = $pdo->query("
        SELECT wa.*, 
               COUNT(s.shelf_id) as total_shelves,
               COALESCE(SUM(s.max_capacity), 0) as total_capacity,
               COALESCE(SUM(s.current_capacity), 0) as used_capacity
        FROM warehouse_areas wa 
        LEFT JOIN shelves s ON wa.area_id = s.area_id 
        GROUP BY wa.area_id
        ORDER BY wa.area_name
    ");
    
    while ($area = $areasStmt->fetch()) {
        $utilizationPercent = $area['total_capacity'] > 0 ? 
            ($area['used_capacity'] / $area['total_capacity']) * 100 : 0;
        
        echo "🏢 {$area['area_name']}: {$area['total_shelves']} kệ, " . 
             number_format($utilizationPercent, 1) . "% sử dụng\n";
    }
    
    echo "\n✅ Khởi tạo dữ liệu thành công! Hệ thống quản lý kho đã sẵn sàng.\n";
    echo "🔗 Truy cập: http://localhost/warehouse/admin.php?option=kho\n";
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    exit(1);
}

?> 