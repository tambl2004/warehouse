-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost
-- Thời gian đã tạo: Th5 31, 2025 lúc 03:50 PM
-- Phiên bản máy phục vụ: 5.7.24
-- Phiên bản PHP: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `warehouse`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `alerts`
--

CREATE TABLE `alerts` (
  `alert_id` int(11) NOT NULL,
  `alert_type` enum('low_stock','expiry_soon','rfid_error','barcode_error','device_error') COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `device_id` int(11) DEFAULT NULL,
  `description` varchar(1000) CHARACTER SET utf8 DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu thông tin cảnh báo';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `barcodes`
--

CREATE TABLE `barcodes` (
  `barcode_id` int(11) NOT NULL,
  `barcode_value` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `lot_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu thông tin mã vạch';

--
-- Đang đổ dữ liệu cho bảng `barcodes`
--

INSERT INTO `barcodes` (`barcode_id`, `barcode_value`, `product_id`, `lot_number`, `expiry_date`, `created_at`, `updated_at`) VALUES
(1, '8934563123456', 1, 'LOT001', '2024-12-31', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(2, '8934563234567', 2, 'LOT002', '2024-06-15', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(3, '8934563345678', 3, 'LOT003', '2025-03-20', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(4, '8934563456789', 4, 'LOT004', '2025-12-31', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(5, '8934563567890', 5, 'LOT005', NULL, '2025-05-30 16:49:23', '2025-05-30 16:49:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `barcode_scan_logs`
--

CREATE TABLE `barcode_scan_logs` (
  `scan_id` int(11) NOT NULL,
  `barcode_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `scan_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `scan_result` enum('success','failed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1000) CHARACTER SET utf8 DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu lịch sử quét mã vạch';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) CHARACTER SET utf8 NOT NULL,
  `description` varchar(1000) CHARACTER SET utf8 DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu thông tin danh mục sản phẩm';

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Thực phẩm khô', 'Gạo, bún, mì, các loại đậu', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(2, 'Thực phẩm tươi sống', 'Thịt, cá, rau củ quả', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(3, 'Đồ uống', 'Nước ngọt, bia, rượu, nước suối', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(4, 'Gia vị', 'Muối, đường, nước mắm, tương ớt', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(5, 'Hàng gia dụng', 'Bát đĩa, nồi niêu, đồ dùng nhà bếp', '2025-05-30 16:49:23', '2025-05-30 16:49:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `export_details`
--

CREATE TABLE `export_details` (
  `export_detail_id` int(11) NOT NULL,
  `export_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `lot_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shelf_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu chi tiết phiếu xuất kho';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `export_orders`
--

CREATE TABLE `export_orders` (
  `export_id` int(11) NOT NULL,
  `export_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `export_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `destination` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'Trạng thái duyệt phiếu xuất',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu thông tin phiếu xuất kho';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `import_details`
--

CREATE TABLE `import_details` (
  `import_detail_id` int(11) NOT NULL,
  `import_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `lot_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `shelf_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu chi tiết phiếu nhập kho';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `import_orders`
--

CREATE TABLE `import_orders` (
  `import_id` int(11) NOT NULL,
  `import_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `import_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'Trạng thái duyệt phiếu nhập',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu thông tin phiếu nhập kho';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_checks`
--

CREATE TABLE `inventory_checks` (
  `check_id` int(11) NOT NULL,
  `check_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `area_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('pending','completed','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu thông tin lịch kiểm kê';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `maintenance_records`
--

CREATE TABLE `maintenance_records` (
  `maintenance_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `maintenance_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` varchar(1000) CHARACTER SET utf8 DEFAULT NULL,
  `status` enum('planned','completed','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'planned',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu lịch sử bảo trì thiết bị';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu token reset mật khẩu';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `sku` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_name` varchar(100) CHARACTER SET utf8 NOT NULL,
  `description` varchar(1000) CHARACTER SET utf8 DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT '0',
  `expiry_date` date DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `volume` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Thể tích sản phẩm (dm³)',
  `image_url` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT 'Đường dẫn hình ảnh sản phẩm',
  `status` enum('in_stock','out_of_stock','discontinued') COLLATE utf8mb4_unicode_ci DEFAULT 'in_stock' COMMENT 'Trạng thái sản phẩm',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu thông tin sản phẩm';

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`product_id`, `sku`, `product_name`, `description`, `unit_price`, `stock_quantity`, `expiry_date`, `category_id`, `volume`, `image_url`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SP001', 'Gạo tám xoan 5kg', 'Gạo tám xoan thơm ngon, bao 5kg', 125000.00, 100, NULL, 1, 5.00, NULL, 'in_stock', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(2, 'SP002', 'Thịt ba chỉ 1kg', 'Thịt ba chỉ tươi ngon, đóng khay 1kg', 180000.00, 50, NULL, 2, 1.00, NULL, 'in_stock', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(3, 'SP003', 'Nước ngọt Coca Cola 330ml', 'Nước ngọt có ga Coca Cola lon 330ml', 12000.00, 500, NULL, 3, 0.33, NULL, 'in_stock', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(4, 'SP004', 'Nước mắm Phú Quốc 500ml', 'Nước mắm nguyên chất Phú Quốc chai 500ml', 45000.00, 200, NULL, 4, 0.50, NULL, 'in_stock', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(5, 'SP005', 'Bát sứ trắng', 'Bát ăn cơm sứ trắng cao cấp', 25000.00, 150, NULL, 5, 0.20, NULL, 'in_stock', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(6, 'SP006', 'Bún khô Bình Tây 500g', 'Bún khô truyền thống Bình Tây gói 500g', 15000.00, 300, NULL, 1, 0.80, NULL, 'in_stock', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(7, 'SP007', 'Cá hồi Na Uy 1kg', 'Cá hồi tươi nhập khẩu Na Uy', 350000.00, 20, NULL, 2, 1.50, NULL, 'in_stock', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(8, 'SP008', 'Bia Heineken 330ml', 'Bia Heineken lon 330ml', 18000.00, 400, NULL, 3, 0.33, NULL, 'in_stock', '2025-05-30 16:49:23', '2025-05-30 16:49:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `report_schedules`
--

CREATE TABLE `report_schedules` (
  `schedule_id` int(11) NOT NULL,
  `report_type` varchar(50) CHARACTER SET utf8 NOT NULL,
  `frequency` enum('daily','weekly','monthly') COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_email` varchar(100) CHARACTER SET utf8 NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu lịch gửi báo cáo định kỳ';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rfid_devices`
--

CREATE TABLE `rfid_devices` (
  `device_id` int(11) NOT NULL,
  `device_name` varchar(100) CHARACTER SET utf8 NOT NULL,
  `area_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','error') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `battery_level` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu thông tin thiết bị RFID';

--
-- Đang đổ dữ liệu cho bảng `rfid_devices`
--

INSERT INTO `rfid_devices` (`device_id`, `device_name`, `area_id`, `status`, `battery_level`, `created_at`, `updated_at`) VALUES
(1, 'RFID Reader A1', 1, 'active', 85, '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(2, 'RFID Reader B1', 2, 'active', 92, '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(3, 'RFID Reader C1', 3, 'active', 78, '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(4, 'RFID Reader D1', 4, 'inactive', 15, '2025-05-30 16:49:23', '2025-05-30 16:49:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rfid_scan_logs`
--

CREATE TABLE `rfid_scan_logs` (
  `scan_id` int(11) NOT NULL,
  `rfid_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `scan_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `scan_result` enum('success','failed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1000) CHARACTER SET utf8 DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu lịch sử quét RFID';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rfid_tags`
--

CREATE TABLE `rfid_tags` (
  `rfid_id` int(11) NOT NULL,
  `rfid_value` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `lot_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `shelf_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu thông tin thẻ RFID';

--
-- Đang đổ dữ liệu cho bảng `rfid_tags`
--

INSERT INTO `rfid_tags` (`rfid_id`, `rfid_value`, `product_id`, `lot_number`, `expiry_date`, `shelf_id`, `created_at`, `updated_at`) VALUES
(1, 'RFID001', 1, 'LOT001', '2024-12-31', 1, '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(2, 'RFID002', 2, 'LOT002', '2024-06-15', 4, '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(3, 'RFID003', 3, 'LOT003', '2025-03-20', 6, '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(4, 'RFID004', 4, 'LOT004', '2025-12-31', 1, '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(5, 'RFID005', 5, 'LOT005', NULL, 8, '2025-05-30 16:49:23', '2025-05-30 16:49:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role` enum('admin','employee','user') COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Khóa quyền, ví dụ: view_product, edit_user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu các quyền cụ thể cho từng vai trò';

--
-- Đang đổ dữ liệu cho bảng `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role`, `permission_key`) VALUES
(3, 'admin', 'delete_all'),
(2, 'admin', 'edit_all'),
(8, 'admin', 'manage_export'),
(7, 'admin', 'manage_import'),
(6, 'admin', 'manage_inventory'),
(4, 'admin', 'manage_users'),
(1, 'admin', 'view_all'),
(5, 'admin', 'view_reports'),
(10, 'employee', 'edit_products'),
(13, 'employee', 'manage_export'),
(12, 'employee', 'manage_import'),
(11, 'employee', 'manage_inventory'),
(9, 'employee', 'view_products'),
(15, 'user', 'view_inventory'),
(14, 'user', 'view_products');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `shelf_product_history`
--

CREATE TABLE `shelf_product_history` (
  `history_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `shelf_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `moved_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu lịch sử di chuyển sản phẩm giữa các kệ';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `shelves`
--

CREATE TABLE `shelves` (
  `shelf_id` int(11) NOT NULL,
  `shelf_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `area_id` int(11) DEFAULT NULL,
  `max_capacity` decimal(10,2) NOT NULL COMMENT 'Sức chứa tối đa (dm³)',
  `current_capacity` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Sức chứa hiện tại',
  `location_description` varchar(1000) CHARACTER SET utf8 DEFAULT NULL,
  `coordinates` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT 'Tọa độ kệ trên sơ đồ kho',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu thông tin kệ kho';

--
-- Đang đổ dữ liệu cho bảng `shelves`
--

INSERT INTO `shelves` (`shelf_id`, `shelf_code`, `area_id`, `max_capacity`, `current_capacity`, `location_description`, `coordinates`, `created_at`, `updated_at`) VALUES
(1, 'A01', 1, 500.00, 0.00, 'Kệ góc trái khu A', 'A1-L', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(2, 'A02', 1, 500.00, 0.00, 'Kệ giữa khu A', 'A2-M', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(3, 'A03', 1, 500.00, 0.00, 'Kệ góc phải khu A', 'A3-R', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(4, 'B01', 2, 300.00, 0.00, 'Kệ lạnh khu B', 'B1-L', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(5, 'B02', 2, 300.00, 0.00, 'Kệ đông khu B', 'B2-M', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(6, 'C01', 3, 400.00, 0.00, 'Kệ đồ uống khu C', 'C1-L', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(7, 'C02', 3, 400.00, 0.00, 'Kệ rượu bia khu C', 'C2-R', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(8, 'D01', 4, 600.00, 0.00, 'Kệ hàng gia dụng khu D', 'D1-M', '2025-05-30 16:49:23', '2025-05-30 16:49:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(100) CHARACTER SET utf8 NOT NULL,
  `contact_info` varchar(1000) CHARACTER SET utf8 DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu thông tin nhà cung cấp';

--
-- Đang đổ dữ liệu cho bảng `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_info`, `created_at`, `updated_at`) VALUES
(1, 'Công ty TNHH Thực phẩm ABC', '123 Đường ABC, Q1, TP.HCM - 0123456789', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(2, 'Công ty CP Thực phẩm XYZ', '456 Đường XYZ, Q2, TP.HCM - 0987654321', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(3, 'Công ty Gia vị Việt Nam', '789 Đường DEF, Q3, TP.HCM - 0111222333', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(4, 'Công ty Hàng gia dụng 123', '101 Đường GHI, Q4, TP.HCM - 0444555666', '2025-05-30 16:49:23', '2025-05-30 16:49:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `log_level` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ví dụ: INFO, ERROR, WARNING',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'system',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu nhật ký hệ thống chung';

--
-- Đang đổ dữ liệu cho bảng `system_logs`
--

INSERT INTO `system_logs` (`log_id`, `log_level`, `message`, `source`, `created_at`) VALUES
(1, 'INFO', 'LOGIN_FAIL_USER_NOT_FOUND_OR_INACTIVE', 'Thất bại đăng nhập, người dùng không tồn tại hoặc chưa kích hoạt: admin1', '2025-05-30 17:42:11'),
(2, 'INFO', 'LOGIN_FAIL_USER_NOT_FOUND_OR_INACTIVE', 'Thất bại đăng nhập, người dùng không tồn tại hoặc chưa kích hoạt: quan', '2025-05-30 17:42:18'),
(3, 'INFO', 'LOGIN_FAIL_USER_NOT_FOUND_OR_INACTIVE', 'Thất bại đăng nhập, người dùng không tồn tại hoặc chưa kích hoạt: nam', '2025-05-30 18:08:22'),
(4, 'INFO', 'INACTIVE_USER_OVERWRITE', 'Xóa tài khoản chưa active (ID: 8) để đăng ký lại với email: nam@gmail.com', '2025-05-30 18:24:54'),
(5, 'INFO', 'REGISTRATION_OTP_SENT_SYS', 'Đã gửi OTP đăng ký cho email: vantamst99@gmail.com, username tạm: nam, UserID (chưa active): 11', '2025-05-30 18:56:14'),
(6, 'INFO', 'REGISTRATION_OTP_SENT_SYS', 'Đã gửi OTP đăng ký cho email: vantamst99@gmail.com, username tạm: tam2, UserID (chưa active): 12', '2025-05-30 18:57:53'),
(7, 'INFO', 'INACTIVE_USER_CLEANUP_ON_REGISTER', 'Xóa tài khoản chưa active (ID: 12) có username/email trùng khi đăng ký mới.', '2025-05-30 18:58:58'),
(8, 'INFO', 'REGISTRATION_OTP_SENT_SYS', 'Đã gửi OTP đăng ký cho email: vantamst99@gmail.com, username tạm: tam2, UserID (chưa active): 13', '2025-05-30 18:59:03'),
(9, 'INFO', 'INACTIVE_USER_CLEANUP_ON_REGISTER', 'Xóa tài khoản chưa active (ID: 14) có username/email trùng khi đăng ký mới.', '2025-05-30 19:13:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) CHARACTER SET utf8 NOT NULL,
  `full_name` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8 NOT NULL,
  `role` enum('admin','employee','user') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `is_locked` tinyint(1) DEFAULT '0',
  `login_attempts` int(11) DEFAULT '0',
  `otp` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP đăng nhập gần nhất',
  `last_login_device` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT 'Thiết bị đăng nhập gần nhất',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu thông tin người dùng';

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`user_id`, `username`, `full_name`, `password_hash`, `email`, `role`, `is_locked`, `login_attempts`, `otp`, `otp_expiry`, `is_active`, `last_login`, `last_login_ip`, `last_login_device`, `created_at`, `updated_at`) VALUES
(5, 'admin', 'Đào Văn Tâm', '$2y$10$W95IGnP7bfiJyzBAAzOCX.emUrt6bJsPV1tedJDSv1dDm4cQfmCrW', 'vantamst97@gmail.com', 'admin', 0, 0, NULL, NULL, 1, '2025-05-31 13:41:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 17:04:12', '2025-05-31 06:41:29'),
(6, 'tam', 'Đào Văn Tâm', '$2y$10$P.hsTE/dRjYmc2CfM8ZidOroMurpYp15xFp/SZo7sxj0ZJaDvlOey', 'zzztamdzzz@gmail.com', 'user', 0, 0, NULL, NULL, 1, '2025-05-31 00:17:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 17:09:33', '2025-05-30 17:17:06'),
(13, 'tam2', 'Đào Văn Tâm', '$2y$10$IYy273UCIEYv1JzFnYNXQe8J5K9nGTOe5kqmL4wesBU1T25PgNQpe', 'vantamst99@gmail.com', 'user', 0, 0, NULL, NULL, 1, '2025-05-31 02:00:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 18:58:58', '2025-05-30 19:00:06');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_logs`
--

CREATE TABLE `user_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) CHARACTER SET utf8 NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `action_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu lịch sử hoạt động người dùng';

--
-- Đang đổ dữ liệu cho bảng `user_logs`
--

INSERT INTO `user_logs` (`log_id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `action_time`) VALUES
(1, 5, 'REGISTER', 'Kích hoạt tài khoản thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 17:04:37'),
(2, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 17:07:48'),
(3, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 17:08:14'),
(4, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 17:09:02'),
(5, 6, 'REGISTER', 'Kích hoạt tài khoản thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 17:09:52'),
(6, 6, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 17:09:55'),
(9, 6, 'RESET_PASSWORD', 'Đặt lại mật khẩu thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 17:17:03'),
(10, 6, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 17:17:06'),
(11, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 17:24:40'),
(12, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 17:42:03'),
(15, 13, 'ACCOUNT_ACTIVATION_SUCCESS', 'Kích hoạt tài khoản thành công qua OTP.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 18:59:38'),
(16, 13, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 19:00:06'),
(17, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 19:00:46'),
(18, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 19:14:49'),
(19, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-31 06:41:24'),
(20, 5, 'LOGIN', 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-31 06:41:29');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `warehouse_areas`
--

CREATE TABLE `warehouse_areas` (
  `area_id` int(11) NOT NULL,
  `area_name` varchar(50) CHARACTER SET utf8 NOT NULL,
  `description` varchar(1000) CHARACTER SET utf8 DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu thông tin khu vực kho';

--
-- Đang đổ dữ liệu cho bảng `warehouse_areas`
--

INSERT INTO `warehouse_areas` (`area_id`, `area_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Khu A', 'Khu vực lưu trữ thực phẩm khô', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(2, 'Khu B', 'Khu vực lưu trữ thực phẩm tươi sống', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(3, 'Khu C', 'Khu vực lưu trữ đồ uống', '2025-05-30 16:49:23', '2025-05-30 16:49:23'),
(4, 'Khu D', 'Khu vực lưu trữ hàng hóa khác', '2025-05-30 16:49:23', '2025-05-30 16:49:23');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `fk_alert_product` (`product_id`),
  ADD KEY `fk_alert_device` (`device_id`);

--
-- Chỉ mục cho bảng `barcodes`
--
ALTER TABLE `barcodes`
  ADD PRIMARY KEY (`barcode_id`),
  ADD UNIQUE KEY `barcode_value` (`barcode_value`),
  ADD KEY `fk_barcode_product` (`product_id`),
  ADD KEY `idx_barcode_value` (`barcode_value`);

--
-- Chỉ mục cho bảng `barcode_scan_logs`
--
ALTER TABLE `barcode_scan_logs`
  ADD PRIMARY KEY (`scan_id`),
  ADD KEY `fk_barcode_scan_logs_barcode` (`barcode_id`),
  ADD KEY `fk_barcode_scan_logs_user` (`user_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Chỉ mục cho bảng `export_details`
--
ALTER TABLE `export_details`
  ADD PRIMARY KEY (`export_detail_id`),
  ADD KEY `fk_export_detail_export` (`export_id`),
  ADD KEY `fk_export_detail_product` (`product_id`),
  ADD KEY `fk_export_detail_shelf` (`shelf_id`);

--
-- Chỉ mục cho bảng `export_orders`
--
ALTER TABLE `export_orders`
  ADD PRIMARY KEY (`export_id`),
  ADD UNIQUE KEY `export_code` (`export_code`),
  ADD KEY `fk_export_user` (`created_by`),
  ADD KEY `idx_export_code` (`export_code`);

--
-- Chỉ mục cho bảng `import_details`
--
ALTER TABLE `import_details`
  ADD PRIMARY KEY (`import_detail_id`),
  ADD KEY `fk_import_detail_import` (`import_id`),
  ADD KEY `fk_import_detail_product` (`product_id`),
  ADD KEY `fk_import_detail_shelf` (`shelf_id`);

--
-- Chỉ mục cho bảng `import_orders`
--
ALTER TABLE `import_orders`
  ADD PRIMARY KEY (`import_id`),
  ADD UNIQUE KEY `import_code` (`import_code`),
  ADD KEY `fk_import_supplier` (`supplier_id`),
  ADD KEY `fk_import_user` (`created_by`),
  ADD KEY `idx_import_code` (`import_code`);

--
-- Chỉ mục cho bảng `inventory_checks`
--
ALTER TABLE `inventory_checks`
  ADD PRIMARY KEY (`check_id`),
  ADD KEY `fk_check_area` (`area_id`),
  ADD KEY `fk_check_user` (`created_by`);

--
-- Chỉ mục cho bảng `maintenance_records`
--
ALTER TABLE `maintenance_records`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `fk_maintenance_records_device` (`device_id`);

--
-- Chỉ mục cho bảng `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `fk_password_reset_tokens_user` (`user_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `fk_product_category` (`category_id`),
  ADD KEY `idx_expiry_date` (`expiry_date`),
  ADD KEY `idx_stock_quantity` (`stock_quantity`);

--
-- Chỉ mục cho bảng `report_schedules`
--
ALTER TABLE `report_schedules`
  ADD PRIMARY KEY (`schedule_id`);

--
-- Chỉ mục cho bảng `rfid_devices`
--
ALTER TABLE `rfid_devices`
  ADD PRIMARY KEY (`device_id`),
  ADD KEY `fk_device_area` (`area_id`);

--
-- Chỉ mục cho bảng `rfid_scan_logs`
--
ALTER TABLE `rfid_scan_logs`
  ADD PRIMARY KEY (`scan_id`),
  ADD KEY `fk_rfid_scan_logs_rfid` (`rfid_id`),
  ADD KEY `fk_rfid_scan_logs_user` (`user_id`);

--
-- Chỉ mục cho bảng `rfid_tags`
--
ALTER TABLE `rfid_tags`
  ADD PRIMARY KEY (`rfid_id`),
  ADD UNIQUE KEY `rfid_value` (`rfid_value`),
  ADD KEY `fk_rfid_product` (`product_id`),
  ADD KEY `fk_rfid_shelf` (`shelf_id`),
  ADD KEY `idx_rfid_value` (`rfid_value`);

--
-- Chỉ mục cho bảng `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_permission_unique` (`role`,`permission_key`);

--
-- Chỉ mục cho bảng `shelf_product_history`
--
ALTER TABLE `shelf_product_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `fk_shelf_product_history_product` (`product_id`),
  ADD KEY `fk_shelf_product_history_shelf` (`shelf_id`);

--
-- Chỉ mục cho bảng `shelves`
--
ALTER TABLE `shelves`
  ADD PRIMARY KEY (`shelf_id`),
  ADD UNIQUE KEY `shelf_code` (`shelf_code`),
  ADD KEY `fk_shelf_area` (`area_id`);

--
-- Chỉ mục cho bảng `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Chỉ mục cho bảng `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_log_user` (`user_id`);

--
-- Chỉ mục cho bảng `warehouse_areas`
--
ALTER TABLE `warehouse_areas`
  ADD PRIMARY KEY (`area_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `alerts`
--
ALTER TABLE `alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `barcodes`
--
ALTER TABLE `barcodes`
  MODIFY `barcode_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `barcode_scan_logs`
--
ALTER TABLE `barcode_scan_logs`
  MODIFY `scan_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `export_details`
--
ALTER TABLE `export_details`
  MODIFY `export_detail_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `export_orders`
--
ALTER TABLE `export_orders`
  MODIFY `export_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `import_details`
--
ALTER TABLE `import_details`
  MODIFY `import_detail_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `import_orders`
--
ALTER TABLE `import_orders`
  MODIFY `import_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `inventory_checks`
--
ALTER TABLE `inventory_checks`
  MODIFY `check_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `maintenance_records`
--
ALTER TABLE `maintenance_records`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `report_schedules`
--
ALTER TABLE `report_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `rfid_devices`
--
ALTER TABLE `rfid_devices`
  MODIFY `device_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `rfid_scan_logs`
--
ALTER TABLE `rfid_scan_logs`
  MODIFY `scan_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `rfid_tags`
--
ALTER TABLE `rfid_tags`
  MODIFY `rfid_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `shelf_product_history`
--
ALTER TABLE `shelf_product_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `shelves`
--
ALTER TABLE `shelves`
  MODIFY `shelf_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `warehouse_areas`
--
ALTER TABLE `warehouse_areas`
  MODIFY `area_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ràng buộc đối với các bảng kết xuất
--

--
-- Ràng buộc cho bảng `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `fk_alert_device` FOREIGN KEY (`device_id`) REFERENCES `rfid_devices` (`device_id`),
  ADD CONSTRAINT `fk_alert_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Ràng buộc cho bảng `barcodes`
--
ALTER TABLE `barcodes`
  ADD CONSTRAINT `fk_barcode_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Ràng buộc cho bảng `barcode_scan_logs`
--
ALTER TABLE `barcode_scan_logs`
  ADD CONSTRAINT `fk_barcode_scan_logs_barcode` FOREIGN KEY (`barcode_id`) REFERENCES `barcodes` (`barcode_id`),
  ADD CONSTRAINT `fk_barcode_scan_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Ràng buộc cho bảng `export_details`
--
ALTER TABLE `export_details`
  ADD CONSTRAINT `fk_export_detail_export` FOREIGN KEY (`export_id`) REFERENCES `export_orders` (`export_id`),
  ADD CONSTRAINT `fk_export_detail_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `fk_export_detail_shelf` FOREIGN KEY (`shelf_id`) REFERENCES `shelves` (`shelf_id`);

--
-- Ràng buộc cho bảng `export_orders`
--
ALTER TABLE `export_orders`
  ADD CONSTRAINT `fk_export_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Ràng buộc cho bảng `import_details`
--
ALTER TABLE `import_details`
  ADD CONSTRAINT `fk_import_detail_import` FOREIGN KEY (`import_id`) REFERENCES `import_orders` (`import_id`),
  ADD CONSTRAINT `fk_import_detail_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `fk_import_detail_shelf` FOREIGN KEY (`shelf_id`) REFERENCES `shelves` (`shelf_id`);

--
-- Ràng buộc cho bảng `import_orders`
--
ALTER TABLE `import_orders`
  ADD CONSTRAINT `fk_import_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `fk_import_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Ràng buộc cho bảng `inventory_checks`
--
ALTER TABLE `inventory_checks`
  ADD CONSTRAINT `fk_check_area` FOREIGN KEY (`area_id`) REFERENCES `warehouse_areas` (`area_id`),
  ADD CONSTRAINT `fk_check_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Ràng buộc cho bảng `maintenance_records`
--
ALTER TABLE `maintenance_records`
  ADD CONSTRAINT `fk_maintenance_records_device` FOREIGN KEY (`device_id`) REFERENCES `rfid_devices` (`device_id`);

--
-- Ràng buộc cho bảng `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Ràng buộc cho bảng `rfid_devices`
--
ALTER TABLE `rfid_devices`
  ADD CONSTRAINT `fk_device_area` FOREIGN KEY (`area_id`) REFERENCES `warehouse_areas` (`area_id`);

--
-- Ràng buộc cho bảng `rfid_scan_logs`
--
ALTER TABLE `rfid_scan_logs`
  ADD CONSTRAINT `fk_rfid_scan_logs_rfid` FOREIGN KEY (`rfid_id`) REFERENCES `rfid_tags` (`rfid_id`),
  ADD CONSTRAINT `fk_rfid_scan_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Ràng buộc cho bảng `rfid_tags`
--
ALTER TABLE `rfid_tags`
  ADD CONSTRAINT `fk_rfid_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `fk_rfid_shelf` FOREIGN KEY (`shelf_id`) REFERENCES `shelves` (`shelf_id`);

--
-- Ràng buộc cho bảng `shelf_product_history`
--
ALTER TABLE `shelf_product_history`
  ADD CONSTRAINT `fk_shelf_product_history_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `fk_shelf_product_history_shelf` FOREIGN KEY (`shelf_id`) REFERENCES `shelves` (`shelf_id`);

--
-- Ràng buộc cho bảng `shelves`
--
ALTER TABLE `shelves`
  ADD CONSTRAINT `fk_shelf_area` FOREIGN KEY (`area_id`) REFERENCES `warehouse_areas` (`area_id`);

--
-- Ràng buộc cho bảng `user_logs`
--
ALTER TABLE `user_logs`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
