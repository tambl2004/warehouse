<?php
session_start();
include 'config/connect.php';
require_once 'inc/auth.php';
require_once 'inc/security.php';

$option = isset($_GET['option']) ? $_GET['option'] : 'home';    
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kho Hàng - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/chucnang.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div>
                <img src="image/logo-sm.png" alt="Logo" class="logo">
            </div>
            <div class="title">Kho Hàng</div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Điều Hướng</div>
                <div class="nav-item">
                    <a href="?option=home" class="nav-link <?php echo $option == 'home' ? 'active' : ''; ?>">
                        <img src="gif/dashboard.gif" alt="Icon" class="nav-icon">
                        Dashboard
                    </a>
                </div>
            </div>
            <div class="nav-item">
                <a href="?option=sanpham" class="nav-link <?php echo $option == 'sanpham' ? 'active' : ''; ?>">
                    <img src="gif/sanpham.gif" alt="Icon" class="nav-icon">
                    Quản lý sản phẩm
                </a>
            </div>
           
            <!-- danh mục sản phẩm -->
            <div class="nav-section">
                <div class="nav-item">
                    <a href="?option=danhmuc" class="nav-link <?php echo $option == 'danhmuc' ? 'active' : ''; ?>">
                        <img src="gif/danhmuc.gif" alt="Icon" class="nav-icon">
                        Quản lý danh mục
                    </a>
                </div>
            </div>
            <div class="nav-item">
                <a href="?option=nguoidung" class="nav-link <?php echo $option == 'nguoidung' ? 'active' : ''; ?>">
                    <img src="gif/taikhoan.gif" alt="Icon" class="nav-icon">
                    Quản lý người dùng
                </a>
            </div>

            <div class="nav-item">
                <a href="?option=nhacungcap" class="nav-link <?php echo $option == 'nhacungcap' ? 'active' : ''; ?>">
                    <img src="gif/nhacungcap.gif" alt="Icon" class="nav-icon">
                    Quản lý nhà cung cấp
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-item">
                    <a href="?option=kho" class="nav-link <?php echo $option == 'kho' ? 'active' : ''; ?>">
                        <img src="gif/kho.gif" alt="Icon" class="nav-icon">
                        Quản lý kho
                    </a>
                </div>
            </div>

            <div class="nav-section">
                <div class="nav-item">
                    <a href="?option=nhapkho" class="nav-link <?php echo $option == 'nhapkho' ? 'active' : ''; ?>" onclick="toggleDropdown(event, 'nhapxuatDropdown')">
                        <img src="gif/nhapxuatkho.gif" alt="Icon" class="nav-icon">
                        Chức năng kho
                        <i class="dropdown-toggle ms-auto" id="nhapxuatToggle" style="font-size: 20px;"></i>
                    </a>
                </div>
                <div class="nav-dropdown" id="nhapxuatDropdown">
                    <div class="nav-item">
                        <a href="?option=nhapkho" class="nav-link <?php echo $option == 'nhapkho' ? 'active' : ''; ?>">
                            <img src="gif/nhapkho.gif" alt="Icon" class="nav-icon">
                            Nhập kho
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="?option=xuatkho" class="nav-link <?php echo $option == 'xuatkho' ? 'active' : ''; ?>">
                            <img src="gif/xuatkho.gif" alt="Icon" class="nav-icon">
                            Xuất kho
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="?option=chuyenkho" class="nav-link <?php echo $option == 'chuyenkho' ? 'active' : ''; ?>">
                            <img src="gif/dieuchuyenkho.gif" alt="Icon" class="nav-icon">
                            Điều chuyển kho
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="?option=kiemke" class="nav-link <?php echo $option == 'kiemke' ? 'active' : ''; ?>">
                            <img src="gif/kiemke.gif" alt="Icon" class="nav-icon">
                            Kiểm kê kho
                        </a>
                    </div>
                   
                </div>
            </div>
            

            <div class="nav-section">
                <div class="nav-item">
                    <a href="?option=baocaothongke" class="nav-link <?php echo $option == 'baocaothongke' ? 'active' : ''; ?>">
                        <img src="gif/baocao.gif" alt="Icon" class="nav-icon">
                        Báo cáo thống kê
                    </a>
                </div>
            </div>
            <div class="nav-section">
                <div class="nav-item">
                    <a href="#" class="nav-link" onclick="toggleDropdown(event, 'settingDropdown')">
                        <img src="gif/setting.gif" alt="Icon" class="nav-icon">
                        Cài đặt hệ thống
                        <i class=" dropdown-toggle ms-auto" id="settingToggle" style="font-size: 20px;"></i>
                    </a>
                </div>
                <div class="nav-dropdown" id="settingDropdown">
                    <div class="nav-section">
                        <div class="nav-item">
                            <a href="?option=hethongIoT" class="nav-link <?php echo $option == 'hethongIoT' ? 'active' : ''; ?>">
                                <img src="gif/barcode.gif" alt="Icon" class="nav-icon">
                                Hệ thống Barcode
                            </a>
                        </div>
                    </div>
                    <div class="nav-section">
                        <div class="nav-item">
                            <a href="?option=tichhop" class="nav-link <?php echo $option == 'tichhop' ? 'active' : ''; ?>">
                                <img src="gif/IoT.gif" alt="Icon" class="nav-icon">
                                Hệ thống IoT & RFID
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <div class="d-flex align-items-center">
                <button class="hamburger me-3" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">Admin</li>
                        <li class="breadcrumb-item">Trang chủ</li>
                        <li class="breadcrumb-item active">Nhà cung cấp</li>
                    </ol>
                </nav>
            </div>
            
            <div class="user-profile">
                <div class="user-info">
                    <img src="image/vietnam.png" alt="flag" class="flag-icon">
                    <div class="user-avatar">
                        <img src="image/avatar.png" alt="" class="user-avatar">
                    </div>
                    <div class="dropdown">
                        <span class="user-name dropdown-toggle" id="userDropdownToggle" data-bs-toggle="dropdown" aria-expanded="false">
                            Tâm
                        </span>
                        <ul class="dropdown-menu user-dropdown" aria-labelledby="userDropdownToggle">
                            <li><a class="dropdown-item user-link" href="?option=taikhoan"><i class="fas fa-user"></i> Tài khoản của tôi</a></li>
                            <li><a class="dropdown-item user-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <div>
                <?php
                
                switch ($option) {
                    case 'home':
                        include 'views/dashboard.php';
                        break;
                    case 'sanpham':
                        include 'views/sanpham.php';
                        break;
                    case 'danhmuc':
                        include 'views/danhmucsanpham.php';
                        break;
                    case 'nhacungcap':
                        include 'views/nhacungcap.php';
                        break;
                    case 'taikhoan':
                        include 'views/taikhoancuatoi.php';
                        break;
                    case 'nguoidung':
                        include 'views/nguoidung.php';
                        break;
                    case 'kho':
                        include 'views/kho.php';
                        break;
                    case 'nhapkho':
                        include 'views/nhapkho.php';
                        break;
                    case 'xuatkho':
                        include 'views/xuatkho.php';
                        break;
                    case 'chuyenkho':
                        include 'views/chuyenkho.php';
                        break;
                    case 'kiemke':
                        include 'views/kiemke.php';
                        break;
                    case 'hethongIoT':
                        include 'views/hethongIoT.php';
                        break;
                    case 'baocaothongke':
                        include 'views/baocaothongke.php';
                        break;
                    case 'tichhop':
                        include 'views/tudonghoa.php';
                        break;
                    case 'caidat':
                        include 'views/setting.php';
                        break;
                    default:
                        include '404.php';
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
 <script>

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        // Missing toggleDropdown function - now implemented
        function toggleDropdown(event, dropdownId) {
            event.preventDefault();
            const dropdown = document.getElementById(dropdownId);
            const toggle = event.currentTarget.querySelector('.dropdown-toggle');
            
            dropdown.classList.toggle('show');
            toggle.classList.toggle('rotated');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const hamburger = document.querySelector('.hamburger');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !hamburger.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
            }
        });

        // Add hover effects to nav links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('mouseenter', function() {
                if (!this.classList.contains('active')) {
                    this.style.transform = 'translateX(5px)';
                }
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });



        document.addEventListener('DOMContentLoaded', function () {
            const userName = document.querySelector('#userDropdownToggle');
            const dropdown = document.querySelector('.user-dropdown');

            // Gán sự kiện click cho tên người dùng
            userName.addEventListener('click', function (e) {
                e.preventDefault(); // Ngăn chặn hành động mặc định
                dropdown.classList.toggle('show'); // Hiển thị hoặc ẩn dropdown
            });

            // Đóng dropdown khi click bên ngoài
            document.addEventListener('click', function (e) {
                if (!userName.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('show'); // Ẩn dropdown nếu click bên ngoài
                }
            });
        });
    </script>
</body>
</html>     