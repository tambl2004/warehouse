<?php
/**
 * Controller quản lý kho hàng
 * Xử lý logic nghiệp vụ cho quản lý khu vực, kệ kho và theo dõi sức chứa
 */

require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/security.php';

class KhoController {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Lấy thống kê tổng quan kho
     */
    public function getWarehouseStats() {
        try {
            $stats = [];
            
            // Tổng số khu vực
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM warehouse_areas");
            $stats['total_areas'] = $stmt->fetchColumn();
            
            // Tổng số kệ
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM shelves");
            $stats['total_shelves'] = $stmt->fetchColumn();
            
            // Tổng sức chứa
            $stmt = $this->pdo->query("SELECT SUM(max_capacity) FROM shelves");
            $stats['total_capacity'] = $stmt->fetchColumn() ?: 0;
            
            // Sức chứa đã sử dụng
            $stmt = $this->pdo->query("SELECT SUM(current_capacity) FROM shelves");
            $stats['used_capacity'] = $stmt->fetchColumn() ?: 0;
            
            // Tỷ lệ sử dụng
            $stats['utilization_rate'] = $stats['total_capacity'] > 0 ? 
                ($stats['used_capacity'] / $stats['total_capacity']) * 100 : 0;
            
            // Số kệ có mức sử dụng cao (>80%)
            $stmt = $this->pdo->query("
                SELECT COUNT(*) FROM shelves 
                WHERE (current_capacity / max_capacity * 100) > 80
            ");
            $stats['high_utilization_shelves'] = $stmt->fetchColumn();
            
            // Số sản phẩm đang lưu trữ
            $stmt = $this->pdo->query("SELECT SUM(quantity) FROM product_locations");
            $stats['total_products'] = $stmt->fetchColumn() ?: 0;
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Lỗi lấy thống kê kho: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy danh sách khu vực với thống kê
     */
    public function getAreasWithStats() {
        try {
            $stmt = $this->pdo->query("
                SELECT wa.*, 
                       COUNT(s.shelf_id) as total_shelves,
                       COALESCE(SUM(s.max_capacity), 0) as total_capacity,
                       COALESCE(SUM(s.current_capacity), 0) as used_capacity,
                       COALESCE(COUNT(pl.product_id), 0) as total_products
                FROM warehouse_areas wa 
                LEFT JOIN shelves s ON wa.area_id = s.area_id 
                LEFT JOIN product_locations pl ON s.shelf_id = pl.shelf_id
                GROUP BY wa.area_id
                ORDER BY wa.area_name
            ");
            
            $areas = $stmt->fetchAll();
            
            // Tính tỷ lệ sử dụng cho mỗi khu vực
            foreach ($areas as &$area) {
                $area['utilization_percent'] = $area['total_capacity'] > 0 ? 
                    ($area['used_capacity'] / $area['total_capacity']) * 100 : 0;
                
                $area['available_capacity'] = $area['total_capacity'] - $area['used_capacity'];
            }
            
            return $areas;
            
        } catch (Exception $e) {
            error_log("Lỗi lấy danh sách khu vực: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy danh sách kệ với thông tin chi tiết
     */
    public function getShelvesWithDetails() {
        try {
            $stmt = $this->pdo->query("
                SELECT s.*, wa.area_name,
                       (s.current_capacity / s.max_capacity * 100) as utilization_percent,
                       (s.max_capacity - s.current_capacity) as available_capacity,
                       COUNT(pl.product_id) as product_count,
                       COALESCE(SUM(pl.quantity), 0) as total_quantity
                FROM shelves s
                LEFT JOIN warehouse_areas wa ON s.area_id = wa.area_id
                LEFT JOIN product_locations pl ON s.shelf_id = pl.shelf_id
                GROUP BY s.shelf_id
                ORDER BY wa.area_name, s.shelf_code
            ");
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Lỗi lấy danh sách kệ: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Tính toán mức độ sử dụng kệ
     */
    public function calculateShelfUtilization($shelfId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT max_capacity, current_capacity 
                FROM shelves 
                WHERE shelf_id = ?
            ");
            $stmt->execute([$shelfId]);
            $shelf = $stmt->fetch();
            
            if (!$shelf) {
                return false;
            }
            
            $utilization = $shelf['max_capacity'] > 0 ? 
                ($shelf['current_capacity'] / $shelf['max_capacity']) * 100 : 0;
            
            return [
                'max_capacity' => $shelf['max_capacity'],
                'current_capacity' => $shelf['current_capacity'],
                'available_capacity' => $shelf['max_capacity'] - $shelf['current_capacity'],
                'utilization_percent' => $utilization,
                'status' => $this->getUtilizationStatus($utilization)
            ];
            
        } catch (Exception $e) {
            error_log("Lỗi tính toán sức chứa kệ: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gợi ý kệ phù hợp cho sản phẩm
     */
    public function suggestOptimalShelf($productVolume, $quantity = 1, $preferredAreaId = null, $excludeShelfId = null) {
        try {
            $totalVolume = $productVolume * $quantity;
            
            $sql = "
                SELECT s.*, wa.area_name,
                       (s.max_capacity - s.current_capacity) as available_capacity,
                       (s.current_capacity / s.max_capacity * 100) as utilization_percent,
                       CASE 
                           WHEN ? IS NOT NULL AND s.area_id = ? THEN 1
                           ELSE 0
                       END as area_priority
                FROM shelves s
                LEFT JOIN warehouse_areas wa ON s.area_id = wa.area_id
                WHERE (s.max_capacity - s.current_capacity) >= ?
            ";
            
            $params = [$preferredAreaId, $preferredAreaId, $totalVolume];
            
            if ($excludeShelfId) {
                $sql .= " AND s.shelf_id != ?";
                $params[] = $excludeShelfId;
            }
            
            // Sắp xếp theo độ ưu tiên: khu vực ưa thích > sức chứa thấp > dung lượng lớn
            $sql .= " ORDER BY area_priority DESC, s.current_capacity ASC, s.max_capacity DESC LIMIT 10";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $suggestions = $stmt->fetchAll();
            
            // Tính điểm ưu tiên cho mỗi gợi ý
            foreach ($suggestions as &$suggestion) {
                $suggestion['priority_score'] = $this->calculatePriorityScore(
                    $suggestion['utilization_percent'],
                    $suggestion['available_capacity'],
                    $totalVolume,
                    $suggestion['area_priority']
                );
                
                $suggestion['efficiency_rating'] = $this->getEfficiencyRating($suggestion['priority_score']);
            }
            
            // Sắp xếp lại theo điểm ưu tiên
            usort($suggestions, function($a, $b) {
                return $b['priority_score'] <=> $a['priority_score'];
            });
            
            return $suggestions;
            
        } catch (Exception $e) {
            error_log("Lỗi gợi ý kệ: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ghi lịch sử di chuyển sản phẩm
     */
    public function logProductMovement($productId, $fromShelfId, $toShelfId, $quantity, $userId, $reason = '') {
        try {
            $this->pdo->beginTransaction();
            
            // Ghi lịch sử di chuyển
            $stmt = $this->pdo->prepare("
                INSERT INTO shelf_product_history (product_id, shelf_id, quantity, moved_at, created_by, reason) 
                VALUES (?, ?, ?, NOW(), ?, ?)
            ");
            $stmt->execute([$productId, $toShelfId, $quantity, $userId, $reason]);
            
            // Lấy thông tin sản phẩm và kệ để log
            $productStmt = $this->pdo->prepare("SELECT product_name FROM products WHERE product_id = ?");
            $productStmt->execute([$productId]);
            $productName = $productStmt->fetchColumn();
            
            $fromShelfStmt = $this->pdo->prepare("SELECT shelf_code FROM shelves WHERE shelf_id = ?");
            $fromShelfStmt->execute([$fromShelfId]);
            $fromShelfCode = $fromShelfStmt->fetchColumn();
            
            $toShelfStmt = $this->pdo->prepare("SELECT shelf_code FROM shelves WHERE shelf_id = ?");
            $toShelfStmt->execute([$toShelfId]);
            $toShelfCode = $toShelfStmt->fetchColumn();
            
            $this->pdo->commit();
            
            // Log hoạt động
            logUserAction($userId, 'MOVE_PRODUCT', 
                "Di chuyển {$quantity} {$productName} từ kệ {$fromShelfCode} sang kệ {$toShelfCode}");
            
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Lỗi ghi lịch sử di chuyển: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy lịch sử di chuyển sản phẩm
     */
    public function getMovementHistory($limit = 50, $productId = null, $shelfId = null, $dateFrom = null, $dateTo = null) {
        try {
            $sql = "
                SELECT sph.*, p.product_name, p.sku,
                       s.shelf_code, wa.area_name,
                       u.full_name as moved_by_name
                FROM shelf_product_history sph
                LEFT JOIN products p ON sph.product_id = p.product_id
                LEFT JOIN shelves s ON sph.shelf_id = s.shelf_id
                LEFT JOIN warehouse_areas wa ON s.area_id = wa.area_id
                LEFT JOIN users u ON sph.created_by = u.user_id
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($productId) {
                $sql .= " AND sph.product_id = ?";
                $params[] = $productId;
            }
            
            if ($shelfId) {
                $sql .= " AND sph.shelf_id = ?";
                $params[] = $shelfId;
            }
            
            if ($dateFrom) {
                $sql .= " AND sph.moved_at >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $sql .= " AND sph.moved_at <= ?";
                $params[] = $dateTo . ' 23:59:59';
            }
            
            $sql .= " ORDER BY sph.moved_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Lỗi lấy lịch sử di chuyển: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cập nhật sức chứa hiện tại của kệ
     */
    public function updateShelfCurrentCapacity($shelfId) {
        try {
            // Tính toán sức chứa hiện tại dựa trên sản phẩm thực tế
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(pl.quantity * p.volume), 0) as calculated_capacity
                FROM product_locations pl
                LEFT JOIN products p ON pl.product_id = p.product_id
                WHERE pl.shelf_id = ?
            ");
            $stmt->execute([$shelfId]);
            $calculatedCapacity = $stmt->fetchColumn();
            
            // Cập nhật sức chứa hiện tại
            $updateStmt = $this->pdo->prepare("
                UPDATE shelves 
                SET current_capacity = ?, updated_at = NOW() 
                WHERE shelf_id = ?
            ");
            $updateStmt->execute([$calculatedCapacity, $shelfId]);
            
            return $calculatedCapacity;
            
        } catch (Exception $e) {
            error_log("Lỗi cập nhật sức chứa kệ: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kiểm tra cảnh báo sức chứa
     */
    public function getCapacityAlerts($threshold = 80) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, wa.area_name,
                       (s.current_capacity / s.max_capacity * 100) as utilization_percent
                FROM shelves s
                LEFT JOIN warehouse_areas wa ON s.area_id = wa.area_id
                WHERE (s.current_capacity / s.max_capacity * 100) > ?
                ORDER BY utilization_percent DESC
            ");
            $stmt->execute([$threshold]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Lỗi kiểm tra cảnh báo sức chứa: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Tối ưu hóa phân bổ sản phẩm
     */
    public function optimizeProductAllocation($areaId = null) {
        try {
            $recommendations = [];
            
            // Tìm các kệ có sức chứa thấp có thể di chuyển sản phẩm
            $sql = "
                SELECT s.*, wa.area_name,
                       (s.current_capacity / s.max_capacity * 100) as utilization_percent,
                       pl.product_id, pl.quantity, p.product_name, p.volume
                FROM shelves s
                LEFT JOIN warehouse_areas wa ON s.area_id = wa.area_id
                LEFT JOIN product_locations pl ON s.shelf_id = pl.shelf_id
                LEFT JOIN products p ON pl.product_id = p.product_id
                WHERE (s.current_capacity / s.max_capacity * 100) < 30
                AND pl.product_id IS NOT NULL
            ";
            
            $params = [];
            if ($areaId) {
                $sql .= " AND s.area_id = ?";
                $params[] = $areaId;
            }
            
            $sql .= " ORDER BY utilization_percent ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $underutilizedShelves = $stmt->fetchAll();
            
            foreach ($underutilizedShelves as $shelf) {
                // Tìm kệ tối ưu hơn cho sản phẩm này
                $suggestions = $this->suggestOptimalShelf(
                    $shelf['volume'], 
                    $shelf['quantity'], 
                    $shelf['area_id'], 
                    $shelf['shelf_id']
                );
                
                if (!empty($suggestions)) {
                    $bestSuggestion = $suggestions[0];
                    if ($bestSuggestion['priority_score'] > 70) { // Chỉ đề xuất nếu có lợi ích rõ ràng
                        $recommendations[] = [
                            'type' => 'move_product',
                            'product_id' => $shelf['product_id'],
                            'product_name' => $shelf['product_name'],
                            'quantity' => $shelf['quantity'],
                            'from_shelf' => [
                                'shelf_id' => $shelf['shelf_id'],
                                'shelf_code' => $shelf['shelf_code'],
                                'utilization' => $shelf['utilization_percent']
                            ],
                            'to_shelf' => [
                                'shelf_id' => $bestSuggestion['shelf_id'],
                                'shelf_code' => $bestSuggestion['shelf_code'],
                                'utilization' => $bestSuggestion['utilization_percent']
                            ],
                            'benefit_score' => $bestSuggestion['priority_score'],
                            'reason' => 'Tối ưu hóa sử dụng không gian kho'
                        ];
                    }
                }
            }
            
            return $recommendations;
            
        } catch (Exception $e) {
            error_log("Lỗi tối ưu hóa phân bổ: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Tính điểm ưu tiên cho gợi ý kệ
     */
    private function calculatePriorityScore($utilizationPercent, $availableCapacity, $requiredVolume, $areaPreference) {
        $score = 0;
        
        // Điểm cho tỷ lệ sử dụng (tối ưu khoảng 60-80%)
        if ($utilizationPercent >= 60 && $utilizationPercent <= 80) {
            $score += 30;
        } elseif ($utilizationPercent < 60) {
            $score += 20 - ($utilizationPercent / 3); // Ưu tiên kệ có sử dụng cao hơn
        } else {
            $score += 10; // Kệ gần đầy ít được ưu tiên
        }
        
        // Điểm cho khả năng chứa (ưu tiên vừa đủ hơn thừa quá nhiều)
        $capacityRatio = $requiredVolume / $availableCapacity;
        if ($capacityRatio >= 0.1 && $capacityRatio <= 0.5) {
            $score += 25;
        } elseif ($capacityRatio < 0.1) {
            $score += 15; // Quá thừa
        } else {
            $score += 20; // Vừa đủ
        }
        
        // Điểm cho khu vực ưa thích
        if ($areaPreference) {
            $score += 20;
        }
        
        // Điểm thưởng cho hiệu quả (25 điểm còn lại)
        $score += 25;
        
        return min(100, max(0, $score));
    }
    
    /**
     * Đánh giá hiệu quả của gợi ý
     */
    private function getEfficiencyRating($score) {
        if ($score >= 80) return 'Tối ưu';
        if ($score >= 60) return 'Tốt';
        if ($score >= 40) return 'Khá';
        return 'Chấp nhận được';
    }
    
    /**
     * Lấy trạng thái sức chứa
     */
    private function getUtilizationStatus($percent) {
        if ($percent >= 90) return 'critical';
        if ($percent >= 80) return 'high';
        if ($percent >= 60) return 'medium';
        if ($percent >= 30) return 'normal';
        return 'low';
    }
    
    /**
     * Xuất báo cáo sức chứa kho
     */
    public function exportCapacityReport($format = 'json') {
        try {
            $areas = $this->getAreasWithStats();
            $shelves = $this->getShelvesWithDetails();
            $stats = $this->getWarehouseStats();
            $alerts = $this->getCapacityAlerts();
            
            $report = [
                'generated_at' => date('Y-m-d H:i:s'),
                'generated_by' => $_SESSION['username'] ?? 'system',
                'summary' => $stats,
                'areas' => $areas,
                'shelves' => $shelves,
                'alerts' => $alerts
            ];
            
            switch ($format) {
                case 'json':
                    return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    
                case 'csv':
                    return $this->generateCSVReport($report);
                    
                default:
                    return $report;
            }
            
        } catch (Exception $e) {
            error_log("Lỗi xuất báo cáo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Tạo báo cáo CSV
     */
    private function generateCSVReport($data) {
        $csv = "Báo cáo sức chứa kho - " . $data['generated_at'] . "\n\n";
        
        // Thống kê tổng quan
        $csv .= "THỐNG KÊ TỔNG QUAN\n";
        $csv .= "Tổng khu vực," . $data['summary']['total_areas'] . "\n";
        $csv .= "Tổng kệ," . $data['summary']['total_shelves'] . "\n";
        $csv .= "Tổng sức chứa (dm³)," . number_format($data['summary']['total_capacity'], 2) . "\n";
        $csv .= "Đã sử dụng (dm³)," . number_format($data['summary']['used_capacity'], 2) . "\n";
        $csv .= "Tỷ lệ sử dụng (%)," . number_format($data['summary']['utilization_rate'], 2) . "\n\n";
        
        // Chi tiết kệ
        $csv .= "CHI TIẾT CÁC KỆ\n";
        $csv .= "Mã kệ,Khu vực,Sức chứa tối đa,Đã sử dụng,Tỷ lệ sử dụng (%),Số sản phẩm\n";
        
        foreach ($data['shelves'] as $shelf) {
            $csv .= sprintf("%s,%s,%s,%s,%s,%s\n",
                $shelf['shelf_code'],
                $shelf['area_name'],
                number_format($shelf['max_capacity'], 2),
                number_format($shelf['current_capacity'], 2),
                number_format($shelf['utilization_percent'], 2),
                $shelf['product_count']
            );
        }
        
        return $csv;
    }
}

?> 