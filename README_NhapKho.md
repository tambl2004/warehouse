# Hệ Thống Quản Lý Nhập Kho

## Mô tả
Hệ thống quản lý nhập kho hoàn chỉnh với các chức năng tạo phiếu nhập, duyệt phiếu, theo dõi tồn kho và xuất báo cáo.

## Các Tính Năng Chính

### 1. Chức Năng Cơ Bản

#### 1.1 Tạo Phiếu Nhập
- **Mục đích**: Ghi nhận thông tin chi tiết về sản phẩm được nhập vào kho
- **Tự động tạo mã phiếu**: Format NHAP-YYYYMMDD-XXX
- **Chọn nhà cung cấp**: Từ danh sách nhà cung cấp đang hoạt động
- **Thêm nhiều sản phẩm**: Hỗ trợ thêm/xóa sản phẩm trong một phiếu
- **Thông tin chi tiết**: Số lượng, đơn giá, lô hàng, hạn sử dụng, vị trí kệ

#### 1.2 Lấy Chi Tiết Phiếu
- Hiển thị đầy đủ thông tin phiếu nhập và chi tiết sản phẩm
- Thông tin nhà cung cấp chi tiết
- Tính toán tổng giá trị phiếu nhập

#### 1.3 Theo Dõi Lịch Sử Di Chuyển
- Ghi lại việc sản phẩm được nhập vào kho
- Cập nhật vị trí sản phẩm trên kệ
- Lịch sử thay đổi vị trí sản phẩm

### 2. Chức Năng Mở Rộng

#### 2.1 Tự Động Tạo Mã Phiếu
- **Cơ chế**: NHAP-YYYYMMDD-XXX (VD: NHAP-20250131-001)
- **Logic**: Lấy ngày hiện tại + số thứ tự phiếu trong ngày

#### 2.2 Kiểm Tra Tồn Kho
- Tự động cập nhật số lượng tồn kho sau khi duyệt phiếu
- Cập nhật vị trí sản phẩm trên kệ
- Cập nhật sức chứa hiện tại của kệ

#### 2.3 Duyệt Phiếu
- **Trạng thái**: pending (chờ duyệt), approved (đã duyệt), rejected (từ chối)
- **Quyền hạn**: Admin và Employee có thể duyệt/từ chối
- **Ghi log**: Lưu lại người duyệt và thời gian duyệt

#### 2.4 Xuất PDF/Excel
- Xuất chi tiết phiếu nhập ra PDF để lưu trữ
- Xuất danh sách phiếu nhập ra Excel để báo cáo
- Định dạng chuyên nghiệp, thông tin đầy đủ

#### 2.5 Kiểm Tra Tính Hợp Lệ
- **Validation dữ liệu đầu vào**:
  - Không để trống các trường bắt buộc
  - Số lượng và đơn giá phải là số dương
  - Hạn sử dụng phải là ngày trong tương lai (nếu có)
  - Kiểm tra sản phẩm và nhà cung cấp tồn tại

## Cấu Trúc Database

### Bảng `import_orders` (Phiếu nhập)
- `import_id`: Mã định danh duy nhất
- `import_code`: Mã phiếu nhập (NHAP-YYYYMMDD-XXX)
- `supplier_id`: Mã nhà cung cấp
- `created_by`: Người tạo phiếu
- `import_date`: Thời gian tạo phiếu
- `status`: Trạng thái (pending/approved/rejected)
- `notes`: Ghi chú
- `approved_by`, `approved_at`: Thông tin duyệt
- `rejected_by`, `rejected_at`, `rejection_reason`: Thông tin từ chối

### Bảng `import_details` (Chi tiết phiếu nhập)
- `import_detail_id`: Mã định danh chi tiết
- `import_id`: Liên kết với phiếu nhập chính
- `product_id`: Sản phẩm được nhập
- `quantity`: Số lượng sản phẩm
- `unit_price`: Đơn giá nhập
- `lot_number`: Số lô sản phẩm
- `expiry_date`: Hạn sử dụng
- `shelf_id`: Kệ nhập hàng

### Bảng `product_locations` (Vị trí sản phẩm)
- `location_id`: Mã định danh vị trí
- `product_id`: ID sản phẩm
- `shelf_id`: ID kệ
- `quantity`: Số lượng trên kệ
- `last_updated`: Thời gian cập nhật cuối

### Bảng `shelf_product_history` (Lịch sử di chuyển)
- `history_id`: Mã lịch sử
- `product_id`: ID sản phẩm
- `shelf_id`: ID kệ
- `quantity`: Số lượng di chuyển
- `moved_at`: Thời gian di chuyển
- `created_by`: Người thực hiện

## Cài Đặt và Sử Dụng

### 1. Cài Đặt Database
```sql
-- Chạy file nhapkho_database.sql để tạo các bảng cần thiết
mysql -u username -p database_name < nhapkho_database.sql
```

### 2. Cấu Hình Files
- `views/nhapkho.php`: Giao diện chính quản lý nhập kho
- `api/import_handler.php`: API xử lý các chức năng backend
- `css/chucnang3.css`: Styling cho module nhập kho

### 3. Quyền Truy Cập
- **Admin**: Toàn quyền tạo, duyệt, từ chối phiếu nhập
- **Employee**: Có thể tạo và duyệt phiếu nhập
- **User**: Chỉ xem (nếu được cấp quyền)

## Hướng Dẫn Sử Dụng

### 1. Tạo Phiếu Nhập Mới
1. Truy cập **Quản lý nhập kho** trong menu admin
2. Click nút **"Tạo phiếu nhập"**
3. Điền thông tin:
   - Mã phiếu (tự động tạo)
   - Chọn nhà cung cấp
   - Thêm ghi chú (nếu có)
4. Thêm sản phẩm:
   - Chọn sản phẩm từ dropdown
   - Nhập số lượng và đơn giá
   - Nhập lô hàng và hạn sử dụng (tùy chọn)
   - Chọn kệ lưu trữ (tùy chọn)
5. Click **"Thêm sản phẩm"** để thêm sản phẩm khác
6. Kiểm tra tổng cộng ở phần dưới
7. Click **"Tạo phiếu nhập"** để hoàn tất

### 2. Duyệt Phiếu Nhập
1. Trong danh sách phiếu nhập, tìm phiếu có trạng thái **"Chờ duyệt"**
2. Click nút **"Duyệt"** (màu xanh) hoặc **"Từ chối"** (màu đỏ)
3. Với từ chối: Nhập lý do từ chối
4. Xác nhận quyết định

### 3. Xem Chi Tiết Phiếu
1. Click nút **"Xem chi tiết"** (icon mắt)
2. Modal hiển thị:
   - Thông tin phiếu nhập
   - Thông tin nhà cung cấp
   - Chi tiết từng sản phẩm
   - Tổng giá trị và số lượng

### 4. Lọc và Tìm Kiếm
- **Tìm kiếm**: Nhập mã phiếu hoặc tên nhà cung cấp
- **Lọc theo trạng thái**: Chọn pending/approved/rejected
- **Lọc theo nhà cung cấp**: Chọn từ dropdown
- **Lọc theo ngày**: Chọn khoảng thời gian

### 5. Xuất Báo Cáo
- **Xuất Excel**: Click **"Xuất Excel"** để tải file danh sách
- **Xuất PDF**: Click icon PDF ở từng phiếu để xuất chi tiết

## API Endpoints

### GET `/api/import_handler.php`
- `action=get_imports`: Lấy danh sách phiếu nhập
- `action=get_import_detail&id=`: Lấy chi tiết phiếu nhập
- `action=generate_import_code&date=`: Tạo mã phiếu tự động

### POST `/api/import_handler.php`
- `action=create_import`: Tạo phiếu nhập mới
- `action=approve_import`: Duyệt phiếu nhập
- `action=reject_import`: Từ chối phiếu nhập

## Tính Năng Bảo Mật

### 1. Authentication & Authorization
- Kiểm tra đăng nhập trước khi truy cập
- Phân quyền theo role (Admin/Employee/User)
- Session management an toàn

### 2. Input Validation
- Sanitize tất cả dữ liệu đầu vào
- Validate số lượng, giá cả, ngày tháng
- Prepared statements chống SQL injection

### 3. Audit Log
- Ghi log tất cả thao tác tạo/sửa/duyệt phiếu
- Theo dõi người thực hiện và thời gian
- Lưu trữ lịch sử thay đổi

## Troubleshooting

### Lỗi Thường Gặp

1. **"Mã phiếu đã tồn tại"**
   - Reload trang để tạo mã mới
   - Kiểm tra format mã phiếu

2. **"Không thể duyệt phiếu"**
   - Kiểm tra quyền truy cập
   - Đảm bảo phiếu đang ở trạng thái "pending"

3. **"Sản phẩm không tồn tại"**
   - Kiểm tra sản phẩm có bị xóa/ngừng kinh doanh
   - Refresh danh sách sản phẩm

4. **Ajax/API không hoạt động**
   - Kiểm tra console browser để xem lỗi
   - Đảm bảo đường dẫn API đúng
   - Kiểm tra session timeout

### Performance Tips

1. **Database Optimization**
   - Sử dụng index đã tạo sẵn
   - Limit số bản ghi khi query lớn
   - Cache kết quả thường dùng

2. **Frontend Optimization**
   - Sử dụng pagination cho danh sách lớn
   - Lazy loading cho modal content
   - Debounce cho search input

## Mở Rộng Tương Lai

### Tính Năng Có Thể Thêm
1. **Workflow approval**: Duyệt phiếu nhiều cấp
2. **Barcode/QR code**: Tích hợp quét mã vạch
3. **Email notification**: Thông báo khi có phiếu mới
4. **Mobile app**: Ứng dụng di động
5. **Integration**: Kết nối ERP/accounting systems

### API Integration
- REST API để tích hợp với hệ thống khác
- Webhook để thông báo real-time
- OAuth2 authentication cho API

---

**Phát triển bởi**: Hệ thống quản lý kho hàng  
**Ngày cập nhật**: 31/01/2025  
**Phiên bản**: 1.0.0 