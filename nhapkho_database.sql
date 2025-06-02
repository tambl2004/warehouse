-- SQL Script tạo các bảng cho hệ thống quản lý nhập kho

-- Bảng phiếu nhập kho
CREATE TABLE IF NOT EXISTS import_orders (
    import_id INT AUTO_INCREMENT PRIMARY KEY,
    import_code VARCHAR(50) UNIQUE NOT NULL,
    supplier_id INT NOT NULL,
    created_by INT NOT NULL,
    import_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    notes TEXT,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejected_by INT NULL,
    rejected_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_import_code (import_code),
    INDEX idx_supplier_id (supplier_id),
    INDEX idx_status (status),
    INDEX idx_import_date (import_date),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (rejected_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Bảng chi tiết phiếu nhập
CREATE TABLE IF NOT EXISTS import_details (
    import_detail_id INT AUTO_INCREMENT PRIMARY KEY,
    import_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    unit_price DECIMAL(15,2) NOT NULL CHECK (unit_price >= 0),
    lot_number VARCHAR(100),
    expiry_date DATE NULL,
    shelf_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_import_id (import_id),
    INDEX idx_product_id (product_id),
    INDEX idx_lot_number (lot_number),
    INDEX idx_expiry_date (expiry_date),
    FOREIGN KEY (import_id) REFERENCES import_orders(import_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    FOREIGN KEY (shelf_id) REFERENCES shelves(shelf_id) ON DELETE SET NULL
);

-- Bảng vị trí sản phẩm trên kệ (nếu chưa có)
CREATE TABLE IF NOT EXISTS product_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    shelf_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_product_shelf (product_id, shelf_id),
    INDEX idx_product_id (product_id),
    INDEX idx_shelf_id (shelf_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (shelf_id) REFERENCES shelves(shelf_id) ON DELETE CASCADE
);

-- Bảng lịch sử di chuyển sản phẩm trên kệ (nếu chưa có)
CREATE TABLE IF NOT EXISTS shelf_product_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    shelf_id INT NOT NULL,
    quantity INT NOT NULL,
    moved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    notes TEXT,
    INDEX idx_product_id (product_id),
    INDEX idx_shelf_id (shelf_id),
    INDEX idx_moved_at (moved_at),
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (shelf_id) REFERENCES shelves(shelf_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT
);

-- Thêm các ràng buộc kiểm tra bổ sung
ALTER TABLE import_details 
ADD CONSTRAINT chk_import_quantity_positive CHECK (quantity > 0),
ADD CONSTRAINT chk_import_price_non_negative CHECK (unit_price >= 0);

-- Trigger cập nhật current_capacity của kệ khi có sản phẩm mới
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_shelf_capacity_after_import
AFTER UPDATE ON import_orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' AND OLD.status != 'approved' THEN
        -- Cập nhật current_capacity cho tất cả kệ có sản phẩm trong phiếu nhập này
        UPDATE shelves s
        JOIN import_details id ON s.shelf_id = id.shelf_id
        JOIN products p ON id.product_id = p.product_id
        SET s.current_capacity = s.current_capacity + (id.quantity * COALESCE(p.volume, 0))
        WHERE id.import_id = NEW.import_id AND id.shelf_id IS NOT NULL;
    END IF;
END//
DELIMITER ;

-- Tạo index cho hiệu suất
CREATE INDEX IF NOT EXISTS idx_import_orders_composite ON import_orders(status, import_date, supplier_id);
CREATE INDEX IF NOT EXISTS idx_import_details_composite ON import_details(import_id, product_id);

-- View để lấy thông tin tổng hợp phiếu nhập
CREATE OR REPLACE VIEW import_orders_summary AS
SELECT 
    io.*,
    s.supplier_name,
    s.supplier_code,
    u.full_name as creator_name,
    COUNT(id.import_detail_id) as total_items,
    COALESCE(SUM(id.quantity * id.unit_price), 0) as total_value,
    COALESCE(SUM(id.quantity), 0) as total_quantity
FROM import_orders io
LEFT JOIN suppliers s ON io.supplier_id = s.supplier_id
LEFT JOIN users u ON io.created_by = u.user_id
LEFT JOIN import_details id ON io.import_id = id.import_id
GROUP BY io.import_id;

-- Thêm dữ liệu mẫu cho testing (tùy chọn)
INSERT IGNORE INTO import_orders (import_code, supplier_id, created_by, notes, status) VALUES
('NHAP-20250131-001', 1, 1, 'Phiếu nhập đầu tiên - test', 'approved'),
('NHAP-20250131-002', 1, 1, 'Phiếu nhập thứ hai - test', 'pending'),
('NHAP-20250131-003', 2, 1, 'Phiếu nhập thứ ba - test', 'rejected');

-- Thêm chi tiết phiếu nhập mẫu
INSERT IGNORE INTO import_details (import_id, product_id, quantity, unit_price, lot_number, expiry_date) VALUES
(1, 1, 100, 15000.00, 'LOT001', '2025-12-31'),
(1, 2, 50, 25000.00, 'LOT002', '2025-11-30'),
(2, 1, 200, 16000.00, 'LOT003', '2026-01-31'),
(3, 3, 75, 12000.00, 'LOT004', '2025-10-31'); 