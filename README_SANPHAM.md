# Hệ thống Quản lý Sản phẩm

Chức năng quản lý sản phẩm được xây dựng theo mô hình MVC với đầy đủ các tính năng theo yêu cầu.

## Cấu trúc File

```
warehouse/
├── models/
│   └── sanpham_model.php          # Model xử lý dữ liệu sản phẩm
├── controllers/
│   └── sanpham_controller.php     # Controller xử lý logic nghiệp vụ
├── views/
│   └── sanpham.php               # Giao diện quản lý sản phẩm
├── uploads/
│   ├── products/                 # Thư mục lưu ảnh sản phẩm
│   └── .htaccess                 # Bảo mật thư mục uploads
└── css/
    └── chucnang.css              # CSS được cập nhật cho sản phẩm
```

## Tính năng chính

### 1. Thống kê tổng quan
- Hiển thị tổng số sản phẩm
- Thống kê theo trạng thái (Còn hàng, Hết hàng, Ngừng kinh doanh)
- Cảnh báo tồn kho thấp và sản phẩm gần hết hạn

### 2. Quản lý sản phẩm
- **Thêm sản phẩm mới:**
  - Tự động tạo mã SKU dựa trên danh mục
  - Upload hình ảnh (JPG, PNG, GIF, tối đa 5MB)
  - Validation đầy đủ cho tất cả trường dữ liệu
  - Hỗ trợ thể tích, hạn sử dụng

- **Sửa sản phẩm:**
  - Chỉnh sửa tất cả thông tin
  - Thay đổi hình ảnh
  - Cập nhật trạng thái tự động dựa trên tồn kho

- **Xóa sản phẩm:**
  - Chuyển trạng thái sang "Ngừng kinh doanh" thay vì xóa vật lý
  - Bảo toàn lịch sử dữ liệu

### 3. Tìm kiếm và lọc
- Tìm kiếm theo tên sản phẩm hoặc mã SKU
- Lọc theo danh mục
- Lọc theo trạng thái
- Xóa bộ lọc nhanh

### 4. Cảnh báo và thông báo
- **Sản phẩm gần hết hạn:** Hiển thị sản phẩm sắp hết hạn trong 30 ngày
- **Tồn kho thấp:** Cảnh báo sản phẩm có số lượng ≤ 10
- Xuất báo cáo Excel cho từng loại cảnh báo

### 5. Xuất báo cáo
- Xuất danh sách tất cả sản phẩm
- Xuất sản phẩm gần hết hạn
- Xuất sản phẩm tồn kho thấp
- Định dạng CSV với UTF-8 BOM

## Validation

### Mã SKU
- Định dạng: 2 chữ cái + số (ví dụ: SP001, TH002)
- Tự động tạo dựa trên danh mục
- Không trùng lặp
- Người dùng chỉ xem, không sửa được

### Tên sản phẩm
- Bắt buộc nhập
- Tối thiểu 3 ký tự
- Loại bỏ HTML tags

### Giá bán
- Phải là số
- Không được âm
- Đơn vị VNĐ

### Số lượng tồn kho
- Phải là số nguyên
- Không được âm
- Tự động cập nhật trạng thái

### Hạn sử dụng
- Định dạng ngày hợp lệ
- Không được nhỏ hơn ngày hiện tại
- Không bắt buộc

### Hình ảnh
- Định dạng: JPG, PNG, GIF
- Kích thước tối đa: 5MB
- Tự động resize preview

## Bảo mật

### Upload File
- Kiểm tra MIME type
- Giới hạn kích thước file
- Tên file ngẫu nhiên để tránh trùng lặp
- Thư mục uploads được bảo vệ bằng .htaccess

### Dữ liệu
- Tất cả input được sanitize
- Sử dụng Prepared Statements để tránh SQL Injection
- XSS protection với htmlspecialchars()

### Phân quyền
- Chỉ admin và employee mới có quyền quản lý sản phẩm
- Ghi log tất cả hoạt động thêm/sửa/xóa

## Cách sử dụng

### 1. Truy cập
Từ admin panel, chọn "Quản lý sản phẩm" từ menu sidebar.

### 2. Thêm sản phẩm mới
1. Nhấn nút "Thêm sản phẩm"
2. Điền đầy đủ thông tin:
   - Tên sản phẩm (bắt buộc)
   - Chọn danh mục (bắt buộc)
   - Giá bán (bắt buộc)
   - Số lượng tồn kho
   - Thể tích (dm³)
   - Hạn sử dụng (nếu có)
   - Mô tả sản phẩm
   - Hình ảnh (tùy chọn)
3. Nhấn "Lưu sản phẩm"

### 3. Sửa sản phẩm
1. Nhấn nút "Chỉnh sửa" (biểu tượng bút)
2. Cập nhật thông tin cần thiết
3. Nhấn "Cập nhật sản phẩm"

### 4. Ngừng kinh doanh
1. Nhấn nút "Ngừng kinh doanh" (biểu tượng X)
2. Xác nhận trong popup

### 5. Tìm kiếm
- Nhập từ khóa vào ô tìm kiếm
- Có thể tìm theo tên hoặc mã SKU
- Nhấn Enter hoặc nút tìm kiếm

### 6. Lọc dữ liệu
- Chọn danh mục từ dropdown
- Chọn trạng thái từ dropdown
- Kết hợp với tìm kiếm

### 7. Xuất báo cáo
- Nhấn "Xuất Excel" để xuất tất cả
- Nhấn biểu tượng download ở các cảnh báo để xuất riêng

## Cơ sở dữ liệu

### Bảng products
- `product_id`: ID tự tăng
- `sku`: Mã sản phẩm (unique)
- `product_name`: Tên sản phẩm
- `description`: Mô tả
- `unit_price`: Giá bán
- `stock_quantity`: Số lượng tồn kho
- `expiry_date`: Hạn sử dụng
- `category_id`: ID danh mục
- `volume`: Thể tích (dm³)
- `image_url`: Đường dẫn ảnh
- `status`: Trạng thái (in_stock, out_of_stock, discontinued)
- `created_at`, `updated_at`: Thời gian tạo/cập nhật

### Quan hệ
- `products.category_id` → `categories.category_id`
- Có thể mở rộng với `suppliers`, `warehouses`

## Lưu ý kỹ thuật

### Performance
- Sử dụng phân trang (20 items/trang)
- Index trên các cột thường xuyên tìm kiếm
- Lazy loading cho hình ảnh

### Responsive
- Giao diện responsive, tương thích mobile
- Table horizontal scroll trên mobile
- Modal responsive

### Browser Support
- Chrome/Edge 88+
- Firefox 85+
- Safari 14+
- IE không được hỗ trợ

### Server Requirements
- PHP 7.4+
- MySQL 5.7+
- Extensions: PDO, GD (cho xử lý ảnh)
- Memory: tối thiểu 128MB

## Troubleshooting

### Lỗi upload ảnh
1. Kiểm tra quyền write cho thư mục `uploads/products/`
2. Kiểm tra `upload_max_filesize` trong php.ini
3. Kiểm tra `post_max_size` trong php.ini

### Lỗi không hiển thị dữ liệu
1. Kiểm tra kết nối database
2. Kiểm tra quyền user trong database
3. Xem error log trong `logs/`

### Lỗi validation
- Tất cả lỗi validation sẽ hiển thị ngay trên form
- Kiểm tra đúng định dạng dữ liệu đầu vào

## Mở rộng tương lai

1. **Barcode/QR Code:**
   - Tích hợp với bảng `barcodes`
   - Tự động tạo mã vạch

2. **RFID:**
   - Tích hợp với bảng `rfid_tags`
   - Quản lý vị trí sản phẩm

3. **Multi-warehouse:**
   - Quản lý tồn kho theo nhiều kho
   - Transfer giữa các kho

4. **Pricing:**
   - Nhiều mức giá (bán lẻ, bán sỉ)
   - Khuyến mãi, giảm giá

5. **Variants:**
   - Sản phẩm có nhiều biến thể
   - Size, màu sắc, v.v.

## Liên hệ hỗ trợ

Nếu có vấn đề hoặc cần hỗ trợ thêm, vui lòng liên hệ team phát triển. 