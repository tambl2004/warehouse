<?php
/**
 * Xử lý xuất PDF phiếu xuất kho
 * File: api/pdf_export.php
 */

require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../vendor/autoload.php'; // TCPDF

use TCPDF;

// Kiểm tra đăng nhập
requireLogin();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['export_id'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID phiếu xuất']);
    exit;
}

try {
    $exportId = intval($_GET['export_id']);
    
    // Lấy thông tin phiếu xuất
    $stmt = $pdo->prepare("
        SELECT eo.*, u.full_name as creator_name, 
               DATE_FORMAT(eo.export_date, '%d/%m/%Y') as formatted_date,
               DATE_FORMAT(eo.created_at, '%d/%m/%Y %H:%i') as formatted_created_at
        FROM export_orders eo
        LEFT JOIN users u ON eo.created_by = u.user_id
        WHERE eo.export_id = ?
    ");
    $stmt->execute([$exportId]);
    $exportOrder = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exportOrder) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu xuất']);
        exit;
    }
    
    // Lấy chi tiết sản phẩm
    $stmt = $pdo->prepare("
        SELECT ed.*, p.product_name, p.sku, p.unit, c.category_name,
               s.shelf_name, s.row_number, s.column_number
        FROM export_details ed
        JOIN products p ON ed.product_id = p.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN shelves s ON ed.shelf_id = s.shelf_id
        WHERE ed.export_id = ?
        ORDER BY ed.detail_id
    ");
    $stmt->execute([$exportId]);
    $exportDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tạo PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Thiết lập thông tin document
    $pdf->SetCreator('Warehouse Management System');
    $pdf->SetAuthor('Warehouse Management System');
    $pdf->SetTitle('Phiếu xuất kho ' . $exportOrder['export_code']);
    $pdf->SetSubject('Phiếu xuất kho');
    
    // Thiết lập margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Bật auto page break
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    // Thiết lập font
    $pdf->SetFont('dejavusans', '', 10);
    
    // Thêm trang
    $pdf->AddPage();
    
    // Header công ty
    $html = '<style>
        .header { text-align: center; margin-bottom: 20px; }
        .company-name { font-size: 18px; font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
        .company-address { font-size: 12px; color: #7f8c8d; margin-bottom: 15px; }
        .doc-title { font-size: 16px; font-weight: bold; color: #e74c3c; text-align: center; margin-bottom: 20px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 8px; border: 1px solid #bdc3c7; }
        .info-label { background-color: #ecf0f1; font-weight: bold; width: 30%; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .details-table th { background-color: #3498db; color: white; padding: 10px; text-align: center; border: 1px solid #2980b9; }
        .details-table td { padding: 8px; border: 1px solid #bdc3c7; text-align: center; }
        .total-row { background-color: #ecf0f1; font-weight: bold; }
        .signature-table { width: 100%; margin-top: 30px; }
        .signature-table td { text-align: center; padding: 10px; }
        .signature-box { height: 80px; border-bottom: 1px solid #000; margin-bottom: 10px; }
        .money-words { font-style: italic; color: #e74c3c; margin: 15px 0; }
        .status-badge { padding: 5px 10px; border-radius: 15px; color: white; font-weight: bold; }
        .status-approved { background-color: #27ae60; }
        .status-pending { background-color: #f39c12; }
        .status-rejected { background-color: #e74c3c; }
    </style>';
    
    // Header thông tin công ty
    $html .= '<div class="header">
        <div class="company-name">HỆ THỐNG QUẢN LÝ KHO</div>
        <div class="company-address">Địa chỉ: 123 Đường ABC, Quận XYZ, TP.HCM<br>
        Điện thoại: 0123.456.789 | Email: info@warehouse.com</div>
    </div>';
    
    // Tiêu đề phiếu
    $html .= '<div class="doc-title">PHIẾU XUẤT KHO</div>';
    
    // Thông tin phiếu xuất
    $statusBadge = '';
    switch ($exportOrder['status']) {
        case 'approved':
            $statusBadge = '<span class="status-badge status-approved">ĐÃ DUYỆT</span>';
            break;
        case 'pending':
            $statusBadge = '<span class="status-badge status-pending">CHỜ DUYỆT</span>';
            break;
        case 'rejected':
            $statusBadge = '<span class="status-badge status-rejected">TỪ CHỐI</span>';
            break;
    }
    
    $html .= '<table class="info-table">
        <tr>
            <td class="info-label">Mã phiếu xuất:</td>
            <td><strong>' . $exportOrder['export_code'] . '</strong></td>
            <td class="info-label">Ngày xuất:</td>
            <td>' . $exportOrder['formatted_date'] . '</td>
        </tr>
        <tr>
            <td class="info-label">Đích đến:</td>
            <td>' . htmlspecialchars($exportOrder['destination']) . '</td>
            <td class="info-label">Người tạo:</td>
            <td>' . htmlspecialchars($exportOrder['creator_name']) . '</td>
        </tr>
        <tr>
            <td class="info-label">Trạng thái:</td>
            <td>' . $statusBadge . '</td>
            <td class="info-label">Ngày tạo:</td>
            <td>' . $exportOrder['formatted_created_at'] . '</td>
        </tr>';
    
    if (!empty($exportOrder['notes'])) {
        $html .= '<tr>
            <td class="info-label">Ghi chú:</td>
            <td colspan="3">' . htmlspecialchars($exportOrder['notes']) . '</td>
        </tr>';
    }
    
    $html .= '</table>';
    
    // Bảng chi tiết sản phẩm
    $html .= '<table class="details-table">
        <thead>
            <tr>
                <th width="5%">STT</th>
                <th width="20%">Mã SP</th>
                <th width="30%">Tên sản phẩm</th>
                <th width="10%">ĐVT</th>
                <th width="8%">SL</th>
                <th width="12%">Đơn giá</th>
                <th width="15%">Thành tiền</th>
            </tr>
        </thead>
        <tbody>';
    
    $totalQuantity = 0;
    $totalAmount = 0;
    
    foreach ($exportDetails as $index => $detail) {
        $quantity = intval($detail['quantity']);
        $unitPrice = floatval($detail['unit_price']);
        $amount = $quantity * $unitPrice;
        
        $totalQuantity += $quantity;
        $totalAmount += $amount;
        
        $shelfInfo = '';
        if ($detail['shelf_name']) {
            $shelfInfo = ' (Kệ: ' . $detail['shelf_name'] . ')';
        }
        
        $html .= '<tr>
            <td>' . ($index + 1) . '</td>
            <td>' . htmlspecialchars($detail['sku']) . '</td>
            <td>' . htmlspecialchars($detail['product_name']) . $shelfInfo . '</td>
            <td>' . htmlspecialchars($detail['unit']) . '</td>
            <td>' . number_format($quantity) . '</td>
            <td>' . number_format($unitPrice, 0, ',', '.') . '</td>
            <td>' . number_format($amount, 0, ',', '.') . '</td>
        </tr>';
    }
    
    // Dòng tổng cộng
    $html .= '<tr class="total-row">
            <td colspan="4"><strong>TỔNG CỘNG</strong></td>
            <td><strong>' . number_format($totalQuantity) . '</strong></td>
            <td></td>
            <td><strong>' . number_format($totalAmount, 0, ',', '.') . ' VNĐ</strong></td>
        </tr>';
    
    $html .= '</tbody></table>';
    
    // Số tiền bằng chữ
    $amountInWords = convertNumberToWords($totalAmount);
    $html .= '<div class="money-words">
        <strong>Bằng chữ:</strong> ' . $amountInWords . ' đồng.
    </div>';
    
    // Bảng chữ ký
    $html .= '<table class="signature-table">
        <tr>
            <td width="33%">
                <strong>Người lập phiếu</strong><br>
                <div class="signature-box"></div>
                ' . htmlspecialchars($exportOrder['creator_name']) . '
            </td>
            <td width="33%">
                <strong>Thủ kho</strong><br>
                <div class="signature-box"></div>
                (Ký và ghi rõ họ tên)
            </td>
            <td width="33%">
                <strong>Người nhận hàng</strong><br>
                <div class="signature-box"></div>
                (Ký và ghi rõ họ tên)
            </td>
        </tr>
    </table>';
    
    // Footer
    $html .= '<div style="text-align: center; margin-top: 20px; font-size: 10px; color: #7f8c8d;">
        Phiếu này được tạo tự động bởi hệ thống quản lý kho vào ' . date('d/m/Y H:i') . '
    </div>';
    
    // Ghi HTML vào PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Xuất file PDF
    $filename = 'PhieuXuatKho_' . $exportOrder['export_code'] . '_' . date('YmdHis') . '.pdf';
    
    // Output PDF
    $pdf->Output($filename, 'I'); // I = inline, D = download
    
} catch (Exception $e) {
    error_log("Lỗi xuất PDF: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi xuất PDF: ' . $e->getMessage()]);
}

/**
 * Chuyển đổi số thành chữ
 */
function convertNumberToWords($number) {
    $ones = array(
        '', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín',
        'mười', 'mười một', 'mười hai', 'mười ba', 'mười bốn', 'mười lăm',
        'mười sáu', 'mười bảy', 'mười tám', 'mười chín'
    );
    
    $tens = array('', '', 'hai mười', 'ba mười', 'bốn mười', 'năm mười', 
                  'sáu mười', 'bảy mười', 'tám mười', 'chín mười');
    
    if ($number == 0) return 'không';
    
    if ($number < 20) {
        return $ones[$number];
    }
    
    if ($number < 100) {
        return $tens[intval($number / 10)] . ' ' . $ones[$number % 10];
    }
    
    if ($number < 1000) {
        return $ones[intval($number / 100)] . ' trăm ' . 
               convertNumberToWords($number % 100);
    }
    
    if ($number < 1000000) {
        return convertNumberToWords(intval($number / 1000)) . ' nghìn ' . 
               convertNumberToWords($number % 1000);
    }
    
    if ($number < 1000000000) {
        return convertNumberToWords(intval($number / 1000000)) . ' triệu ' . 
               convertNumberToWords($number % 1000000);
    }
    
    return convertNumberToWords(intval($number / 1000000000)) . ' tỷ ' . 
           convertNumberToWords($number % 1000000000);
}
?> 