# Hệ thống Quản lý Kho Hàng

## Mô tả
Hệ thống quản lý kho hàng hiện đại với các tính năng:
- Quản lý sản phẩm, danh mục, nhà cung cấp
- Nhập/xuất kho với phê duyệt
- Quản lý RFID và mã vạch
- Hệ thống phân quyền người dùng
- Báo cáo và thống kê
- Cảnh báo tồn kho và hạn sử dụng

## Yêu cầu hệ thống
- PHP 7.4 trở lên
- MySQL 5.7 trở lên
- XAMPP/WAMP/MAMP hoặc web server tương tự
- Composer (để quản lý thư viện)

## Cài đặt

### 1. Sao chép project
```bash
git clone https://github.com/tambl2004/warehouse.git
cd warehouse
```

### 2. Cài đặt dependencies
```bash
composer install
```

### 3. Tạo cơ sở dữ liệu
1. Mở phpMyAdmin
2. Tạo database tên `warehouse`
3. Import file `warehouse.sql`
4. Import file `sample_data.sql` để có dữ liệu mẫu

### 4. Cấu hình
1. Chỉnh sửa file `config/config.php` theo môi trường của bạn
2. Cập nhật thông tin email trong `inc/mail_helper.php`

### 5. Chạy ứng dụng
1. Khởi động XAMPP/WAMP/MAMP
2. Truy cập: `http://localhost/warehouse`

## Tài khoản mặc định

Sau khi import dữ liệu mẫu, bạn có thể đăng nhập với:

### Admin
- Username: `admin`
- Password: `123`

### Nhân viên
- Username: `tam`
- Password: `123`

### User
- Username: `tam2`
- Password: `123`

## Cấu trúc thư mục

```
warehouse/
├── config/          # Cấu hình ứng dụng
├── inc/            # Các file include chung
├── css/            # File CSS
├── js/             # File JavaScript
├── vendor/         # Thư viện Composer
├── uploads/        # File upload
├── tmp/            # File tạm
├── logs/           # Log files
├── login.php       # Trang đăng nhập
├── register.php    # Trang đăng ký
├── forgot_password.php # Quên mật khẩu
└── index.php       # Trang chủ
```

## Tính năng chính

### 1. Xác thực người dùng
- Đăng ký với xác thực email OTP
- Đăng nhập an toàn
- Quên mật khẩu với OTP
- Khóa tài khoản tự động khi đăng nhập sai

### 2. Phân quyền
- **Admin**: Toàn quyền quản lý hệ thống
- **Employee**: Quản lý kho, sản phẩm, nhập/xuất
- **User**: Chỉ xem thông tin

### 3. Quản lý sản phẩm
- CRUD sản phẩm với hình ảnh
- Quản lý danh mục
- Theo dõi tồn kho
- Cảnh báo hết hạn

### 4. Nhập/xuất kho
- Tạo phiếu nhập/xuất
- Hệ thống phê duyệt
- Theo dõi lịch sử

### 5. RFID & Mã vạch
- Quản lý thẻ RFID
- Quản lý mã vạch
- Lịch sử quét

### 6. Báo cáo
- Báo cáo tồn kho
- Báo cáo nhập/xuất
- Xuất PDF/Excel

## Bảo mật

Hệ thống đã tích hợp các biện pháp bảo mật:
- Mã hóa mật khẩu với bcrypt
- CSRF protection
- SQL injection prevention
- XSS protection
- Rate limiting
- Session security

## Hỗ trợ

Nếu gặp vấn đề, vui lòng:
1. Kiểm tra log trong thư mục `logs/`
2. Đảm bảo cấu hình database đúng
3. Kiểm tra quyền thư mục upload và tmp

## Phát triển

Để phát triển thêm tính năng:
1. Tuân thủ cấu trúc MVC
2. Sử dụng prepared statements cho database
3. Validate và sanitize input
4. Ghi log các hoạt động quan trọng
5. Test thoroughly trước khi deploy

## License

[Chọn license phù hợp]

---

## ✨ Giới thiệu

Hệ thống Quản lý Kho Thông Minh là một giải pháp phần mềm được xây dựng nhằm hiện đại hóa và tự động hóa các quy trình quản lý kho. Hệ thống cho phép người dùng:

* Quản lý sản phẩm, danh mục, khu vực kho, kệ kho
* Theo dõi lịch sử nhập xuất, kiểm kê hàng hóa một cách hiệu quả và chính xác
* Tích hợp RFID và Barcode giúp tăng tốc độ xử lý, giảm thiểu sai sót và cung cấp dữ liệu thời gian thực

---

## 🚀 Tính năng chính

### 📅 1. Quản lý Sản phẩm

* CRUD sản phẩm (đầy đủ thông tin: tên, mã, giá, danh mục, HSD, hình ảnh...)
* Tìm kiếm, lọc theo danh mục, tên, giá, tồn kho
* Cảnh báo hết hạn, tồn kho thấp

### 🏢 2. Quản lý Kho

* Quản lý khu vực, kệ, vị trí
* Tính toán mức độ sử dụng kệ
* Gợi ý vị trí khi nhập kho
* Sơ đồ trực quan (2D/3D)

### 🔎 3. Kiểm kê Kho

* Hỗ trợ RFID/Barcode
* Có lịch sử kiểm kê, sai lệch

### 🌐 4. Nhập/Xuất Kho

* Phiếu nhập, xuất
* Găn Barcode, RFID
* Kiểm tra tồn trước khi xuất
* Xuất PDF, tìm kiếm, duyệt phiếu

### 🔹 5. Hệ thống Barcode

* Tạo + quét barcode
* In barcode
* Lịch sử quét

### 💻 6. RFID & IoT

* Quản lý thiết bị RFID, dashboard real-time
* Tự động nhập/xuất, ghi nhật ký

### 📄 7. Quản lý người dùng

* Tài khoản, phân quyền
* Ghi log thao tác
* OTP/email, reset mật khẩu

### 📊 8. Báo cáo & Thống kê

* Báo cáo nhập, xuất, kiểm kê
* Biểu đồ, xu hướng, xuất PDF/Excel

### ⚙️ 9. Bảo trì & Log Hệ thống

* Bảo trì thiết bị, ghi log sự kiện
* Tìm kiếm log, phân quyền truy cập log

---

## 🎓 Công nghệ sử dụng

* **Ngôn ngữ Server:** PHP 7.4+ (hoặc framework Laravel/CI)
* **Database:** MySQL 5.7+
* **Frontend:** HTML5, CSS3, JavaScript (ES6+)
* **Thư viện:**

  * PHPMailer: Gửi email OTP, reset password
  * mPDF / FPDF: In PDF phiếu
  * Chart.js, SweetAlert2, jQuery, DataTables
* **Khác:** Git, Composer, Apache/Nginx

---

## 🚫 Yêu cầu Hệ thống

* PHP >= 7.4
* MySQL >= 5.7
* Apache (bật mod\_rewrite) / Nginx
* Composer
* Trình duyệt mới (Chrome/Firefox)
* (Tùy chọn) Node.js và npm/yarn

---

## 📖 Hướng dẫn cài đặt

1. **Clone project:**

   ```bash
   git clone https://github.com/tambl2004/warehouse.git
   cd warehouse
   ```

2. **Cài thư viện PHP:**

   ```bash
   composer install
   ```

3. **Tạo DB và import SQL:**

   ```bash
   mysql -u root -p warehouse < config/warehouse.sql
   ```

4. **Cấu hình DB:**

   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'warehouse');
   ```

5. **Truy cập local:**
   Mở browser: `http://localhost/warehouse`

---

## 🙌 Đóng góp

Chúng tôi hoan nghênh mọi đóng góp! Hãy fork repo, tạo pull request hoặc mở issue để đóng góp ý tưởng, sửa lỗi hoặc bổ sung tính năng.

---

## 👨‍💼 Tác giả

* Đào Văn Tâm, Lương Ngọc Thành

---
