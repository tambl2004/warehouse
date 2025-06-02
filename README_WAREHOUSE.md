# 🏪 Hệ Thống Quản Lý Kho Hàng

## 📋 Tổng Quan

Hệ thống quản lý kho hàng được phát triển để đáp ứng các yêu cầu:

### 🎯 Chức Năng Chính

#### 2.1 Chức Năng Cơ Bản
- **✅ Quản lý khu vực kho:**
  - Phân chia kho thành các khu vực (A, B, C, D...)
  - Lưu thông tin: tên khu vực, mô tả, sức chứa tối đa
  - Thống kê tỷ lệ sử dụng theo khu vực

- **✅ Quản lý kệ kho:**
  - Gán mã kệ, vị trí (ví dụ: Khu A - Kệ 1)
  - Lưu thông tin: kích thước kệ, thể tích tối đa, tọa độ
  - Theo dõi số lượng sản phẩm trên mỗi kệ

- **✅ Theo dõi sức chứa:**
  - Tính toán mức độ sử dụng kệ: `(Thể tích sản phẩm hiện có) / (Sức chứa tối đa) × 100%`
  - Hiển thị tỷ lệ sử dụng theo phần trăm và biểu đồ
  - Cảnh báo khi kệ gần đầy (>80%)

#### 2.2 Chức Năng Mở Rộng
- **✅ Gợi ý vị trí kệ:**
  - Khi nhập kho, hệ thống gợi ý kệ trống phù hợp
  - Dựa trên kích thước sản phẩm và sức chứa kệ
  - Tính điểm ưu tiên cho mỗi gợi ý

- **✅ Sơ đồ trực quan kho:**
  - Hiển thị bản đồ 2D của kho, khu vực, kệ
  - Màu sắc phân biệt mức độ sử dụng
  - Cho phép nhấp vào kệ để xem chi tiết sản phẩm

- **✅ Lịch sử vị trí lưu trữ:**
  - Ghi lại lịch sử di chuyển sản phẩm giữa các kệ/khu vực
  - Theo dõi người thực hiện và lý do di chuyển

## 🚀 Cài Đặt

### Yêu Cầu Hệ Thống
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- Bootstrap 5.3+
- Chart.js (đã tích hợp)

### Hướng Dẫn Cài Đặt

1. **Clone/Copy project vào thư mục web:**
   ```bash
   # Ví dụ với XAMPP/MAMP
   cp -r warehouse /Applications/MAMP/htdocs/
   ```

2. **Tạo cơ sở dữ liệu:**
   ```sql
   -- Import file warehouse.sql vào MySQL
   mysql -u root -p < warehouse.sql
   ```

3. **Cấu hình kết nối database:**
   ```php
   // Chỉnh sửa config/config.php
   define('DB_HOST', 'localhost');
   define('DB_USERNAME', 'root');
   define('DB_PASSWORD', 'your_password');
   define('DB_NAME', 'warehouse');
   ```

4. **Khởi tạo dữ liệu mẫu:**
   ```bash
   php test_warehouse.php
   ```

5. **Truy cập hệ thống:**
   ```
   http://localhost/warehouse/admin.php?option=kho
   ```

## 📱 Giao Diện Người Dùng

### Dashboard Chính
- **Stats Cards:** Hiển thị thống kê tổng quan (khu vực, kệ, sức chứa, tỷ lệ sử dụng)
- **Navigation Tabs:** 5 tab chính cho các chức năng

### 📊 Tab Tổng Quan
- **Biểu đồ sử dụng kho:** Chart.js hiển thị tỷ lệ sử dụng theo khu vực
- **Cảnh báo sức chứa:** Danh sách kệ có mức sử dụng cao (>80%)

### 🏢 Tab Khu Vực Kho
- **Danh sách khu vực:** Bảng hiển thị tất cả khu vực với thống kê
- **Thao tác:** Thêm, sửa, xóa khu vực
- **Progress bars:** Hiển thị tỷ lệ sử dụng trực quan

### 📦 Tab Quản Lý Kệ
- **Danh sách kệ:** Bảng chi tiết tất cả kệ kho
- **Bộ lọc:** Lọc theo khu vực và mức sử dụng
- **Thao tác:** Thêm, sửa, xóa, xem chi tiết kệ

### 🗺️ Tab Sơ Đồ Kho
- **Warehouse Map:** Hiển thị sơ đồ trực quan
- **Color coding:**
  - 🟢 Xanh: Sử dụng thấp (< 50%)
  - 🟡 Vàng: Sử dụng trung bình (50-80%)
  - 🔴 Đỏ: Sử dụng cao (> 80%)
- **Interactive:** Click vào kệ để xem chi tiết

### 📋 Tab Lịch Sử
- **Movement History:** Lịch sử di chuyển sản phẩm
- **Thông tin:** Thời gian, sản phẩm, kệ đích, người thực hiện

## 🔧 API Endpoints

### Quản Lý Khu Vực
```javascript
// Lấy thông tin khu vực
GET api/warehouse_handler.php?action=get_area&id={area_id}

// Lưu khu vực (thêm/sửa)
POST api/warehouse_handler.php
{
    action: 'save_area',
    area_id: '', // Để trống nếu thêm mới
    area_name: 'Tên khu vực',
    description: 'Mô tả'
}

// Xóa khu vực
POST api/warehouse_handler.php
{
    action: 'delete_area',
    area_id: '1'
}
```

### Quản Lý Kệ
```javascript
// Lấy thông tin kệ
GET api/warehouse_handler.php?action=get_shelf&id={shelf_id}

// Lưu kệ (thêm/sửa)
POST api/warehouse_handler.php
{
    action: 'save_shelf',
    shelf_id: '', // Để trống nếu thêm mới
    shelf_code: 'A01',
    area_id: '1',
    max_capacity: '500.00',
    coordinates: 'A1-L',
    location_description: 'Kệ góc trái khu A'
}

// Xóa kệ
POST api/warehouse_handler.php
{
    action: 'delete_shelf',
    shelf_id: '1'
}

// Lấy chi tiết kệ
GET api/warehouse_handler.php?action=get_shelf_details&id={shelf_id}
```

### Gợi Ý & Di Chuyển
```javascript
// Gợi ý kệ phù hợp
GET api/warehouse_handler.php?action=suggest_shelf&volume=10&quantity=5&exclude_shelf=1

// Di chuyển sản phẩm
POST api/warehouse_handler.php
{
    action: 'move_product',
    product_id: '1',
    from_shelf_id: '1',
    to_shelf_id: '2',
    quantity: '10'
}
```

## 📈 Công Thức Tính Toán

### Tỷ Lệ Sử Dụng Kệ
```
Utilization % = (Current Capacity / Max Capacity) × 100

Ví dụ:
- Kệ chứa tối đa: 1000 dm³
- Sản phẩm hiện có: 500 dm³ 
- Tỷ lệ sử dụng: (500/1000) × 100 = 50%
```

### Điểm Ưu Tiên Gợi Ý Kệ
```php
function calculatePriorityScore($utilizationPercent, $availableCapacity, $requiredVolume, $areaPreference) {
    $score = 0;
    
    // Điểm tỷ lệ sử dụng (tối ưu 60-80%): 30 điểm
    if ($utilizationPercent >= 60 && $utilizationPercent <= 80) $score += 30;
    
    // Điểm khả năng chứa (vừa đủ): 25 điểm
    $capacityRatio = $requiredVolume / $availableCapacity;
    if ($capacityRatio >= 0.1 && $capacityRatio <= 0.5) $score += 25;
    
    // Điểm khu vực ưa thích: 20 điểm
    if ($areaPreference) $score += 20;
    
    // Điểm hiệu quả: 25 điểm
    $score += 25;
    
    return min(100, max(0, $score));
}
```

## 🎨 Cấu Trúc File

```
warehouse/
├── api/
│   └── warehouse_handler.php      # API xử lý AJAX requests
├── config/
│   ├── connect.php               # Kết nối database
│   └── config.php               # Cấu hình chung
├── controllers/
│   └── KhoController.php        # Logic nghiệp vụ quản lý kho
├── views/
│   └── kho.php                 # Giao diện chính quản lý kho
├── css/
│   ├── main.css               # CSS chung
│   └── chucnang.css          # CSS cho quản lý kho
├── inc/
│   ├── auth.php              # Xác thực người dùng
│   └── security.php          # Bảo mật
├── admin.php                 # File điều hướng chính
├── test_warehouse.php        # File khởi tạo dữ liệu
└── warehouse.sql            # Database schema
```

## 🔒 Bảo Mật

- **Authentication:** Kiểm tra đăng nhập và phân quyền
- **Authorization:** Chỉ admin và employee mới có quyền quản lý kho
- **Input Validation:** Làm sạch và validate tất cả input
- **SQL Injection:** Sử dụng prepared statements
- **XSS Protection:** Escape output với htmlspecialchars()
- **CSRF Protection:** Token verification cho các form quan trọng

## 📊 Tính Năng Nổi Bật

### 1. Sơ Đồ Kho Trực Quan
- **Interactive Map:** Click vào kệ để xem chi tiết
- **Color Coding:** Phân biệt mức độ sử dụng bằng màu sắc
- **Responsive Design:** Tự động điều chỉnh theo kích thước màn hình
- **Real-time Update:** Cập nhật theo thời gian thực

### 2. Gợi Ý Thông Minh
- **Algorithm:** Tính điểm ưu tiên dựa trên nhiều yếu tố
- **Efficiency Rating:** Đánh giá hiệu quả từ "Tối ưu" đến "Chấp nhận được"
- **Area Preference:** Ưu tiên khu vực phù hợp
- **Capacity Optimization:** Tối ưu hóa sử dụng không gian

### 3. Dashboard Thống Kê
- **Real-time Charts:** Biểu đồ cập nhật theo thời gian thực
- **KPI Cards:** Các chỉ số quan trọng với gradient đẹp
- **Alert System:** Cảnh báo khi kệ gần đầy
- **Progress Bars:** Hiển thị tỷ lệ sử dụng trực quan

### 4. Lịch Sử Chi Tiết
- **Complete Tracking:** Theo dõi mọi di chuyển sản phẩm
- **User Attribution:** Ghi nhận người thực hiện
- **Reason Logging:** Lưu lý do di chuyển
- **Time Stamping:** Ghi nhận thời gian chính xác

## 🛠️ Khắc Phục Sự Cố

### Lỗi Thường Gặp

1. **Lỗi kết nối database:**
   ```
   Kiểm tra config/config.php
   Đảm bảo MySQL đang chạy
   Verify username/password
   ```

2. **Không hiển thị dữ liệu:**
   ```
   Chạy php test_warehouse.php
   Kiểm tra quyền user truy cập database
   ```

3. **JavaScript không hoạt động:**
   ```
   Kiểm tra Console trong Developer Tools
   Đảm bảo Bootstrap và Chart.js đã load
   ```

4. **CSS không hiển thị đúng:**
   ```
   Clear browser cache
   Kiểm tra đường dẫn css/chucnang.css
   ```

### Debug Mode
```php
// Bật debug trong config.php
if ($_SERVER['HTTP_HOST'] === 'localhost') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
```

## 📞 Hỗ Trợ

- **Email:** support@warehouse.com
- **Documentation:** README_WAREHOUSE.md
- **Issue Tracking:** GitHub Issues
- **Version:** 1.0.0

## 🔄 Cập Nhật Trong Tương Lai

- [ ] **IoT Integration:** Tích hợp cảm biến thời gian thực
- [ ] **Mobile App:** Ứng dụng di động cho nhân viên kho
- [ ] **AI Optimization:** AI tối ưu hóa vị trí sản phẩm
- [ ] **Barcode Scanner:** Quét mã vạch bằng camera
- [ ] **3D Warehouse View:** Sơ đồ kho 3D
- [ ] **Advanced Analytics:** Phân tích dự đoán và báo cáo

---

**© 2024 Hệ Thống Quản Lý Kho Hàng. All rights reserved.** 