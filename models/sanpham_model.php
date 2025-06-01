<?php

//  File: models/sanpham_model.php

class SanPhamModel {
    private $pdo;
    private $conn;
    
    public function __construct() {
        global $pdo, $conn;
        $this->pdo = $pdo;
        $this->conn = $conn;
    }
    

    // Lấy danh sách tất cả sản phẩm với phân trang và tìm kiếm

    public function layDanhSachSanPham($page = 1, $limit = 20, $search = '', $category_id = null, $status = null) {
        $offset = ($page - 1) * $limit;
        
        $where_conditions = ["1=1"];
        $params = [];
        
        // Tìm kiếm theo tên hoặc SKU
        if (!empty($search)) {
            $where_conditions[] = "(p.product_name LIKE ? OR p.sku LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Lọc theo danh mục
        if (!empty($category_id)) {
            $where_conditions[] = "p.category_id = ?";
            $params[] = $category_id;
        }
        
        // Lọc theo trạng thái
        if (!empty($status)) {
            $where_conditions[] = "p.status = ?";
            $params[] = $status;
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        $sql = "SELECT p.*, c.category_name,
                       CASE 
                           WHEN p.stock_quantity = 0 THEN 'Hết hàng'
                           WHEN p.stock_quantity < 10 THEN 'Tồn kho thấp'
                           ELSE 'Còn hàng'
                       END as stock_status,
                       CASE 
                           WHEN p.expiry_date IS NOT NULL AND p.expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 'Gần hết hạn'
                           ELSE 'Bình thường'
                       END as expiry_status
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                WHERE $where_clause
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Đếm tổng số sản phẩm cho phân trang
    public function demTongSoSanPham($search = '', $category_id = null, $status = null) {
        $where_conditions = ["1=1"];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(product_name LIKE ? OR sku LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($category_id)) {
            $where_conditions[] = "category_id = ?";
            $params[] = $category_id;
        }
        
        if (!empty($status)) {
            $where_conditions[] = "status = ?";
            $params[] = $status;
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        $sql = "SELECT COUNT(*) FROM products WHERE $where_clause";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }
    
    // Lấy thông tin sản phẩm theo ID

    public function laySanPhamTheoId($product_id) {
        $sql = "SELECT p.*, c.category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                WHERE p.product_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$product_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Kiểm tra SKU có trùng lặp không

    public function kiemTraSKUTrungLap($sku, $product_id = null) {
        $sql = "SELECT COUNT(*) FROM products WHERE sku = ?";
        $params = [$sku];
        
        if ($product_id) {
            $sql .= " AND product_id != ?";
            $params[] = $product_id;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }
    
    // Tạo SKU tự động

    public function taoSKUTuDong($category_id = null) {
        // Lấy tiền tố danh mục
        $prefix = 'SP';
        if ($category_id) {
            $sql = "SELECT category_name FROM categories WHERE category_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$category_id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($category) {
                // Lấy 2 ký tự đầu của tên danh mục
                $prefix = strtoupper(substr($this->removeVietnameseAccents($category['category_name']), 0, 2));
            }
        }
        
        // Lấy số thứ tự lớn nhất
        $sql = "SELECT MAX(CAST(SUBSTRING(sku, 3) AS UNSIGNED)) as max_number 
                FROM products 
                WHERE sku LIKE ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$prefix . '%']);
        $max_number = $stmt->fetchColumn();
        
        $next_number = ($max_number ?? 0) + 1;
        
        return $prefix . sprintf('%03d', $next_number);
    }
    
    // Thêm sản phẩm mới

    public function themSanPham($data) {
        // Tạo SKU tự động nếu chưa có
        if (empty($data['sku'])) {
            $data['sku'] = $this->taoSKUTuDong($data['category_id'] ?? null);
        }
        
        $sql = "INSERT INTO products (sku, product_name, description, unit_price, stock_quantity, 
                expiry_date, category_id, volume, image_url, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            $data['sku'],
            $data['product_name'],
            $data['description'],
            $data['unit_price'],
            $data['stock_quantity'] ?? 0,
            $data['expiry_date'] ?: null,
            $data['category_id'],
            $data['volume'] ?? 0,
            $data['image_url'] ?? null,
            $data['status'] ?? 'in_stock'
        ]);
        
        if ($result) {
            return $this->pdo->lastInsertId();
        }
        
        return false;
    }
    
    // Cập nhật sản phẩm

    public function capNhatSanPham($product_id, $data) {
        $sql = "UPDATE products SET 
                product_name = ?, description = ?, unit_price = ?, stock_quantity = ?,
                expiry_date = ?, category_id = ?, volume = ?, image_url = ?, status = ?,
                updated_at = NOW()
                WHERE product_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['product_name'],
            $data['description'],
            $data['unit_price'],
            $data['stock_quantity'],
            $data['expiry_date'] ?: null,
            $data['category_id'],
            $data['volume'] ?? 0,
            $data['image_url'] ?? null,
            $data['status'],
            $product_id
        ]);
    }
    
    // Xóa sản phẩm
    public function xoaSanPham($product_id) {
        $sql = "UPDATE products SET status = 'discontinued', updated_at = NOW() WHERE product_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$product_id]);
    }
    
    // Lấy danh sách danh mục
    public function layDanhSachDanhMuc() {
        $sql = "SELECT * FROM categories ORDER BY category_name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Lấy sản phẩm gần hết hạn
    public function laySanPhamGanHetHan($days = 30) {
        $sql = "SELECT p.*, c.category_name,
                       DATEDIFF(p.expiry_date, NOW()) as days_to_expire
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                WHERE p.expiry_date IS NOT NULL 
                      AND p.expiry_date <= DATE_ADD(NOW(), INTERVAL ? DAY)
                      AND p.expiry_date >= NOW()
                      AND p.status != 'discontinued'
                ORDER BY p.expiry_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$days]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Lấy sản phẩm tồn kho thấp

    public function laySanPhamTonKhoThap($threshold = 10) {
        $sql = "SELECT p.*, c.category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                WHERE p.stock_quantity <= ? 
                      AND p.status != 'discontinued'
                ORDER BY p.stock_quantity ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$threshold]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Cập nhật số lượng tồn kho

    public function capNhatTonKho($product_id, $quantity) {
        $sql = "UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE product_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$quantity, $product_id]);
    }
    
    // Cập nhật trạng thái sản phẩm tự động dựa trên tồn kho

    public function capNhatTrangThaiTuDong($product_id) {
        $sql = "UPDATE products SET 
                status = CASE 
                    WHEN stock_quantity = 0 THEN 'out_of_stock'
                    WHEN stock_quantity > 0 THEN 'in_stock'
                    ELSE status
                END,
                updated_at = NOW()
                WHERE product_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$product_id]);
    }
    
    // Thống kê sản phẩm

    public function thongKeSanPham() {
        $sql = "SELECT 
                    COUNT(*) as total_products,
                    SUM(CASE WHEN status = 'in_stock' THEN 1 ELSE 0 END) as in_stock,
                    SUM(CASE WHEN status = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock,
                    SUM(CASE WHEN status = 'discontinued' THEN 1 ELSE 0 END) as discontinued,
                    SUM(CASE WHEN stock_quantity <= 10 THEN 1 ELSE 0 END) as low_stock,
                    SUM(CASE WHEN expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) AND expiry_date >= NOW() THEN 1 ELSE 0 END) as expiring_soon
                FROM products";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Validate dữ liệu sản phẩm
    public function validateSanPham($data, $product_id = null) {
        $errors = [];
        
        // Validate tên sản phẩm
        if (empty($data['product_name']) || strlen(trim($data['product_name'])) < 3) {
            $errors['product_name'] = 'Tên sản phẩm phải có ít nhất 3 ký tự';
        }
        
        // Validate SKU nếu có
        if (!empty($data['sku'])) {
            if (!preg_match('/^[A-Z]{2}[0-9]{3,}$/', $data['sku'])) {
                $errors['sku'] = 'SKU phải có định dạng: 2 chữ cái + số (ví dụ: SP001)';
            } elseif ($this->kiemTraSKUTrungLap($data['sku'], $product_id)) {
                $errors['sku'] = 'SKU đã tồn tại';
            }
        }
        
        // Validate giá
        if (!is_numeric($data['unit_price']) || $data['unit_price'] < 0) {
            $errors['unit_price'] = 'Giá phải là số không âm';
        }
        
        // Validate số lượng
        if (!is_numeric($data['stock_quantity']) || $data['stock_quantity'] < 0) {
            $errors['stock_quantity'] = 'Số lượng phải là số không âm';
        }
        
        // Validate hạn sử dụng
        if (!empty($data['expiry_date'])) {
            $expiry_date = DateTime::createFromFormat('Y-m-d', $data['expiry_date']);
            if (!$expiry_date || $expiry_date < new DateTime()) {
                $errors['expiry_date'] = 'Hạn sử dụng phải là ngày hợp lệ và không nhỏ hơn ngày hiện tại';
            }
        }
        
        // Validate danh mục
        if (empty($data['category_id'])) {
            $errors['category_id'] = 'Vui lòng chọn danh mục';
        }
        
        return $errors;
    }
    
    // Loại bỏ dấu tiếng Việt

    private function removeVietnameseAccents($str) {
        $accents = array(
            'à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
            'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
            'ì','í','ị','ỉ','ĩ',
            'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
            'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
            'ỳ','ý','ỵ','ỷ','ỹ',
            'đ',
            'À','Á','Ạ','Ả','Ã','Â','Ầ','Ấ','Ậ','Ẩ','Ẫ','Ă','Ằ','Ắ','Ặ','Ẳ','Ẵ',
            'È','É','Ẹ','Ẻ','Ẽ','Ê','Ề','Ế','Ệ','Ể','Ễ',
            'Ì','Í','Ị','Ỉ','Ĩ',
            'Ò','Ó','Ọ','Ỏ','Õ','Ô','Ồ','Ố','Ộ','Ổ','Ỗ','Ơ','Ờ','Ớ','Ợ','Ở','Ỡ',
            'Ù','Ú','Ụ','Ủ','Ũ','Ư','Ừ','Ứ','Ự','Ử','Ữ',
            'Ỳ','Ý','Ỵ','Ỷ','Ỹ',
            'Đ'
        );
        
        $no_accents = array(
            'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
            'e','e','e','e','e','e','e','e','e','e','e',
            'i','i','i','i','i',
            'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
            'u','u','u','u','u','u','u','u','u','u','u',
            'y','y','y','y','y',
            'd',
            'A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A',
            'E','E','E','E','E','E','E','E','E','E','E',
            'I','I','I','I','I',
            'O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O',
            'U','U','U','U','U','U','U','U','U','U','U',
            'Y','Y','Y','Y','Y',
            'D'
        );
        
        return str_replace($accents, $no_accents, $str);
    }
}

?>
