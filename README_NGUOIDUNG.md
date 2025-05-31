# 👥 Module Quản lý Người dùng - Hệ thống Kho Hàng

## 📋 Tổng quan

Module Quản lý Người dùng là một hệ thống hoàn chỉnh để quản lý tài khoản người dùng trong hệ thống kho hàng, bao gồm các tính năng bảo mật cao và giao diện hiện đại.

## ✨ Tính năng chính

### 🔐 Quản lý Tài khoản
- ➕ Thêm người dùng mới với xác thực OTP
- ✏️ Chỉnh sửa thông tin người dùng
- 🗑️ Xóa tài khoản (có kiểm tra ràng buộc)
- 🔒 Khóa/mở khóa tài khoản
- 🔄 Reset mật khẩu qua email

### 👤 Phân quyền
- **Admin**: Toàn quyền quản lý hệ thống
- **Employee**: Nhân viên với quyền hạn giới hạn  
- **User**: Người dùng cơ bản

### 🛡️ Bảo mật
- 🔢 Xác thực OTP khi đăng ký
- 📧 Reset mật khẩu qua email
- 🚫 Khóa tự động sau nhiều lần đăng nhập sai
- 📝 Ghi log mọi hoạt động

### 📊 Theo dõi & Báo cáo
- 📈 Thống kê người dùng theo thời gian thực
- 📋 Lịch sử hoạt động chi tiết
- 🔍 Tìm kiếm và lọc nâng cao
- 📄 Phân trang thông minh

## 🗂️ Cấu trúc File

```
├── models/
│   └── nguoidung_model.php      # Model xử lý logic backend
├── views/
│   └── nguoidung.php            # Giao diện quản lý người dùng
├── config/
│   └── email_config.php         # Cấu hình gửi email
├── css/
│   └── main.css                 # CSS đã được cập nhật
├── js/
│   └── nguoidung.js             # JavaScript cho tương tác
└── admin.php                    # File chính đã tích hợp module
```

## 🚀 Cài đặt và Cấu hình

### 1. Cấu hình Database
Đảm bảo các bảng sau đã được tạo trong database:

```sql
-- Bảng users (đã có)
-- Bảng user_logs (đã có) 
-- Bảng password_reset_tokens (đã có)
-- Bảng role_permissions (đã có)
```

### 2. Cấu hình Email
Cập nhật thông tin SMTP trong `config/email_config.php`:

```php
private static $smtp_config = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your-email@gmail.com',    // Email của bạn
    'password' => 'your-app-password',        // App password
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'Hệ thống Kho Hàng',
];
```

### 3. Cài đặt PHPMailer (nếu chưa có)
```bash
composer require phpmailer/phpmailer
```

## 🎯 Cách sử dụng

### Truy cập Module
- Đăng nhập vào admin panel
- Nhấp vào "Quản lý người dùng" trong sidebar
- URL: `admin.php?option=nguoidung`

### Các thao tác chính

#### ➕ Thêm người dùng mới
1. Nhấp button "Thêm người dùng" 
2. Điền thông tin trong form modal
3. Hệ thống sẽ gửi OTP qua email
4. Người dùng xác thực OTP để kích hoạt tài khoản

#### ✏️ Chỉnh sửa người dùng
1. Nhấp icon "Edit" trên dòng người dùng
2. Cập nhật thông tin trong modal
3. Lưu thay đổi

#### 🔒 Khóa/mở khóa tài khoản  
1. Nhấp icon "Lock/Unlock" 
2. Xác nhận hành động
3. Email thông báo sẽ được gửi đến người dùng

#### 🔄 Reset mật khẩu
1. Nhấp icon "Reset" 
2. Xác nhận hành động
3. Email reset sẽ được gửi đến người dùng

#### 🗑️ Xóa người dùng
1. Nhấp icon "Delete"
2. Xác nhận xóa (không thể hoàn tác)
3. Hệ thống kiểm tra ràng buộc trước khi xóa

## 🎨 Tính năng Giao diện

### 📊 Dashboard Statistics
- Tổng số người dùng
- Người dùng đang hoạt động  
- Tài khoản bị khóa
- Hoạt động gần đây

### 🔍 Tìm kiếm & Lọc
- Tìm kiếm theo tên, email, username
- Lọc theo vai trò (Admin/Employee/User)
- Gợi ý tìm kiếm thông minh
- Tìm kiếm realtime

### 📱 Responsive Design
- Hoạt động mượt trên mọi thiết bị
- UI/UX hiện đại với Bootstrap 5
- Animations và transitions mượt mà
- Dark/Light mode tự động

## 🛡️ Bảo mật

### Xác thực OTP
- Mã OTP 6 số ngẫu nhiên
- Hết hạn sau 15 phút
- Gửi qua email với template đẹp

### Reset Password  
- Token bảo mật 64 ký tự
- Hết hạn sau 1 giờ
- Link một lần sử dụng

### Logging
- Ghi log mọi hành động quan trọng
- Theo dõi IP, thời gian, thiết bị
- Phát hiện hoạt động bất thường

## 📝 Logging & Monitoring

### User Logs
```sql
- user_id: ID người dùng
- action: Hành động (login, logout, update, etc.)
- ip_address: Địa chỉ IP  
- user_agent: Thông tin trình duyệt
- created_at: Thời gian thực hiện
```

### Email Logs
```sql  
- email: Email người nhận
- type: Loại email (otp, reset, notification)
- status: Trạng thái gửi (success/failed)
- message: Thông tin chi tiết
- created_at: Thời gian gửi
```

## 🎛️ API Endpoints

### GET Requests
- `?option=nguoidung` - Hiển thị danh sách người dùng
- `?option=nguoidung&search=keyword` - Tìm kiếm
- `?option=nguoidung&role_filter=admin` - Lọc theo vai trò
- `?option=nguoidung&page=2` - Phân trang

### POST Requests  
- `action=create` - Tạo người dùng mới
- `action=update` - Cập nhật người dùng
- `action=delete` - Xóa người dùng
- `action=toggle_lock` - Khóa/mở khóa
- `action=reset_password` - Reset mật khẩu

## 🚨 Xử lý Lỗi

### Validation
- Kiểm tra định dạng email
- Validate độ mạnh mật khẩu
- Xác thực username unique
- Kiểm tra quyền hạn

### Error Messages
- Thông báo lỗi rõ ràng bằng tiếng Việt
- Toast notifications đẹp mắt
- Logging chi tiết cho debugging

## 📚 Customization

### Thêm vai trò mới
1. Cập nhật enum trong database
2. Thêm role mới vào `nguoidung_model.php`
3. Cập nhật UI trong `nguoidung.php`
4. Thêm CSS styling trong `main.css`

### Tùy chỉnh Email Templates
1. Chỉnh sửa methods trong `EmailConfig` class
2. Cập nhật CSS inline trong templates
3. Thêm logo và branding

### Mở rộng Logging
1. Thêm columns mới vào `user_logs`
2. Cập nhật `logHoatDong()` method
3. Thêm filters mới trong UI

## 🔧 Troubleshooting

### Email không gửi được
1. Kiểm tra cấu hình SMTP
2. Verify app password Gmail
3. Check firewall/port blocking
4. Xem error logs

### OTP không hoạt động
1. Kiểm tra bảng `users` có cột `otp_code`
2. Verify expiry time logic
3. Check email delivery

### UI không hiển thị đúng
1. Clear browser cache
2. Kiểm tra file CSS/JS load đúng
3. Check console errors
4. Verify Bootstrap version

## 📞 Hỗ trợ

Nếu gặp vấn đề khi sử dụng module, vui lòng:

1. Kiểm tra logs trong `/logs/` directory
2. Verify database connections
3. Check file permissions
4. Review error messages

## 🎉 Tính năng sắp tới

- [ ] Two-factor authentication (2FA)
- [ ] Social login (Google, Facebook)
- [ ] Bulk user import/export  
- [ ] Advanced permission system
- [ ] User activity analytics
- [ ] Mobile app integration

---

## 📄 License

Module này được phát triển cho Hệ thống Kho Hàng và tuân thủ các quy định bảo mật doanh nghiệp.

**© 2024 Hệ thống Kho Hàng - Tất cả quyền được bảo lưu** 