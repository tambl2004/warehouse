# Hệ thống Quản lý Kho Thông Minh (Smart Warehouse Management System)

Một ứng dụng web quản lý kho hàng thông minh, hỗ trợ theo dõi sản phẩm, quản lý nhập xuất, kiểm kê và tối ưu hóa không gian lưu trữ bằng công nghệ RFID và Barcode.

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
