<?php
// Kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'inc/security.php';
?>
<div class="function-container">
    <div class="inventory-check-management">
        <!-- Header -->
        <h1 class="page-title">Quản lý Kiểm kê</h1>

        <!-- Thống kê tổng quan -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 id="totalProducts">0</h3>
                                <small>Tổng sản phẩm</small>
                            </div>
                            <i class="fas fa-boxes fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 id="checkedProducts">0</h3>
                                <small>Đã kiểm tra</small>
                            </div>
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 id="discrepancies">0</h3>
                                <small>Có chênh lệch</small>
                            </div>
                            <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 id="activeChecks">0</h3>
                                <small>Phiên kiểm kê</small>
                            </div>
                            <i class="fas fa-clipboard-list fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bộ lọc và điều khiển -->
        <div class="function-container mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Khu vực kiểm kê</label>
                    <select class="form-select" id="filterArea">
                        <option value="">Tất cả khu vực</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kệ hàng</label>
                    <select class="form-select" id="filterShelf">
                        <option value="">Tất cả kệ</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" id="filterStatus">
                        <option value="">Tất cả</option>
                        <option value="pending">Chưa kiểm tra</option>
                        <option value="checked">Đã kiểm tra</option>
                        <option value="discrepancy">Có chênh lệch</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="startNewCheck()">
                            <i class="fas fa-plus"></i> Tạo phiên kiểm kê
                        </button>
                        <button class="btn btn-success" onclick="exportResults()">
                            <i class="fas fa-download"></i> Xuất báo cáo
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs cho các phương pháp kiểm kê -->
        <div class="inventory-tabs">
            <ul class="nav nav-tabs" id="inventoryTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="rfid-tab" data-bs-toggle="tab" data-bs-target="#rfid-panel" type="button" role="tab">
                        <i class="fas fa-wifi"></i> Kiểm kê RFID
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="barcode-tab" data-bs-toggle="tab" data-bs-target="#barcode-panel" type="button" role="tab">
                        <i class="fas fa-barcode"></i> Kiểm kê Barcode
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="results-tab" data-bs-toggle="tab" data-bs-target="#results-panel" type="button" role="tab">
                        <i class="fas fa-chart-line"></i> Kết quả kiểm kê
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-panel" type="button" role="tab">
                        <i class="fas fa-history"></i> Lịch sử kiểm kê
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="inventoryTabContent">
                <!-- Tab RFID -->
                <div class="tab-pane fade show active" id="rfid-panel" role="tabpanel">
                    <div class="row">
                        <!-- Khu vực quét RFID -->
                        <div class="col-md-6">
                            <div class="scan-container">
                                <h5><i class="fas fa-wifi"></i> Quét RFID Tự Động</h5>
                                
                                <!-- Trạng thái thiết bị RFID -->
                                <div class="device-status mb-3">
                                    <h6>Trạng thái thiết bị</h6>
                                    <div id="rfidDevices" class="device-list">
                                        <!-- Sẽ được load bằng JavaScript -->
                                    </div>
                                </div>

                                <!-- Khu vực quét -->
                                <div class="scan-area">
                                    <div class="scan-zone" id="rfidScanZone">
                                        <div class="scan-indicator">
                                            <i class="fas fa-wifi fa-3x"></i>
                                            <p>Đặt sản phẩm vào vùng quét</p>
                                            <div class="scan-animation"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="scan-controls">
                                        <button class="btn btn-success" id="startRfidScan" onclick="startRFIDScan()">
                                            <i class="fas fa-play"></i> Bắt đầu quét
                                        </button>
                                        <button class="btn btn-danger" id="stopRfidScan" onclick="stopRFIDScan()" style="display:none;">
                                            <i class="fas fa-stop"></i> Dừng quét
                                        </button>
                                        <button class="btn btn-info" onclick="clearRfidResults()">
                                            <i class="fas fa-trash"></i> Xóa kết quả
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kết quả quét RFID -->
                        <div class="col-md-6">
                            <div class="scan-results">
                                <h5><i class="fas fa-list"></i> Kết quả quét RFID</h5>
                                <div class="scan-summary">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="summary-item">
                                                <h4 id="rfidScanned">0</h4>
                                                <small>Đã quét</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="summary-item">
                                                <h4 id="rfidMatched">0</h4>
                                                <small>Khớp dữ liệu</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="summary-item">
                                                <h4 id="rfidErrors">0</h4>
                                                <small>Lỗi/Chênh lệch</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="results-table-container">
                                    <div class="table-responsive">
                                        <table class="table table-sm" id="rfidResultsTable">
                                            <thead>
                                                <tr>
                                                    <th>RFID</th>
                                                    <th>Sản phẩm</th>
                                                    <th>Kệ</th>
                                                    <th>SL Hệ thống</th>
                                                    <th>SL Thực tế</th>
                                                    <th>Trạng thái</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Barcode -->
                <div class="tab-pane fade" id="barcode-panel" role="tabpanel">
                    <div class="row">
                        <!-- Khu vực quét Barcode -->
                        <div class="col-md-6">
                            <div class="scan-container">
                                <h5><i class="fas fa-barcode"></i> Quét Barcode Thủ Công</h5>
                                
                                <!-- Input quét barcode -->
                                <div class="barcode-input-group">
                                    <div class="form-group">
                                        <label for="barcodeInput">Quét hoặc nhập mã vạch</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="barcodeInput" 
                                                placeholder="Quét mã vạch tại đây..." autofocus>
                                            <button class="btn btn-primary" onclick="processBarcodeInput()">
                                                <i class="fas fa-search"></i> Xử lý
                                            </button>
                                        </div>
                                        <small class="text-muted">Sử dụng máy quét barcode hoặc nhập thủ công</small>
                                    </div>

                                    <!-- Batch scan mode -->
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="batchScanMode">
                                        <label class="form-check-label" for="batchScanMode">
                                            Chế độ quét hàng loạt (Enter để thêm từng sản phẩm)
                                        </label>
                                    </div>
                                </div>

                                <!-- Quét bằng camera (tùy chọn) -->
                                <div class="camera-scan mt-3">
                                    <button class="btn btn-info" onclick="startCameraScan()">
                                        <i class="fas fa-camera"></i> Quét bằng Camera
                                    </button>
                                    <div id="cameraPreview" style="display:none;" class="mt-2">
                                        <video id="preview" width="100%" height="200"></video>
                                        <button class="btn btn-danger btn-sm" onclick="stopCameraScan()">Dừng Camera</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kết quả quét Barcode -->
                        <div class="col-md-6">
                            <div class="scan-results">
                                <h5><i class="fas fa-list"></i> Kết quả quét Barcode</h5>
                                <div class="scan-summary">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="summary-item">
                                                <h4 id="barcodeScanned">0</h4>
                                                <small>Đã quét</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="summary-item">
                                                <h4 id="barcodeMatched">0</h4>
                                                <small>Khớp dữ liệu</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="summary-item">
                                                <h4 id="barcodeErrors">0</h4>
                                                <small>Lỗi/Chênh lệch</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="results-table-container">
                                    <div class="table-responsive">
                                        <table class="table table-sm" id="barcodeResultsTable">
                                            <thead>
                                                <tr>
                                                    <th>Barcode</th>
                                                    <th>Sản phẩm</th>
                                                    <th>Số lô</th>
                                                    <th>SL Hệ thống</th>
                                                    <th>SL Thực tế</th>
                                                    <th>Trạng thái</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Kết quả kiểm kê -->
                <div class="tab-pane fade" id="results-panel" role="tabpanel">
                    <div class="inventory-results">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fas fa-chart-line"></i> Kết quả tổng hợp</h5>
                            <div>
                                <button class="btn btn-warning" onclick="showDiscrepancies()">
                                    <i class="fas fa-exclamation-triangle"></i> Xem chênh lệch
                                </button>
                                <button class="btn btn-success" onclick="approveAdjustments()">
                                    <i class="fas fa-check"></i> Duyệt điều chỉnh
                                </button>
                            </div>
                        </div>

                        <!-- Biểu đồ tổng quan -->
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <div class="chart-container">
                                    <canvas id="inventoryChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="summary-cards">
                                    <div class="summary-card accurate">
                                        <h4 id="accurateCount">0</h4>
                                        <p>Chính xác</p>
                                    </div>
                                    <div class="summary-card shortage">
                                        <h4 id="shortageCount">0</h4>
                                        <p>Thiếu hàng</p>
                                    </div>
                                    <div class="summary-card excess">
                                        <h4 id="excessCount">0</h4>
                                        <p>Thừa hàng</p>
                                    </div>
                                    <div class="summary-card missing">
                                        <h4 id="missingCount">0</h4>
                                        <p>Mất hàng</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bảng chi tiết chênh lệch -->
                        <div class="discrepancies-table">
                            <h6>Chi tiết chênh lệch cần xử lý</h6>
                            <div class="table-responsive">
                                <table class="table table-hover" id="discrepanciesTable">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th>Kệ</th>
                                            <th>SL Hệ thống</th>
                                            <th>SL Thực tế</th>
                                            <th>Chênh lệch</th>
                                            <th>Giá trị chênh lệch</th>
                                            <th>Ghi chú</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Lịch sử kiểm kê -->
                <div class="tab-pane fade" id="history-panel" role="tabpanel">
                    <div class="inventory-history">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fas fa-history"></i> Lịch sử kiểm kê</h5>
                            <div>
                                <input type="date" class="form-control d-inline-block" id="historyDateFilter" style="width: auto;">
                                <button class="btn btn-primary" onclick="filterHistory()">
                                    <i class="fas fa-filter"></i> Lọc
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover" id="historyTable">
                                <thead>
                                    <tr>
                                        <th>Mã phiên</th>
                                        <th>Ngày kiểm kê</th>
                                        <th>Khu vực</th>
                                        <th>Người thực hiện</th>
                                        <th>Tổng SP</th>
                                        <th>Chênh lệch</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal điều chỉnh tồn kho -->
    <div class="custom-modal" id="adjustStockModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Điều chỉnh tồn kho</h5>
                <button type="button" class="modal-close" onclick="closeModal('adjustStockModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="adjustStockForm">
                    <input type="hidden" id="adjustProductId">
                    <input type="hidden" id="adjustShelfId">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Sản phẩm</label>
                                <input type="text" class="form-control" id="adjustProductName" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vị trí kệ</label>
                                <input type="text" class="form-control" id="adjustShelfCode" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>SL hệ thống</label>
                                <input type="number" class="form-control" id="adjustSystemQty" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>SL thực tế</label>
                                <input type="number" class="form-control" id="adjustActualQty" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Chênh lệch</label>
                                <input type="number" class="form-control" id="adjustDifference" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Lý do điều chỉnh <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="adjustReason" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Loại điều chỉnh</label>
                        <select class="form-select" id="adjustType" required>
                            <option value="">Chọn loại điều chỉnh</option>
                            <option value="inventory_loss">Thất thoát hàng hóa</option>
                            <option value="inventory_gain">Thặng dư hàng hóa</option>
                            <option value="counting_error">Lỗi đếm</option>
                            <option value="system_error">Lỗi hệ thống</option>
                            <option value="damage">Hàng hỏng</option>
                            <option value="expired">Hàng hết hạn</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('adjustStockModal')">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="submitStockAdjustment()">Xác nhận điều chỉnh</button>
            </div>
        </div>
    </div>

    <!-- Modal tạo phiên kiểm kê mới -->
    <div class="custom-modal" id="newCheckModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tạo phiên kiểm kê mới</h5>
                <button type="button" class="modal-close" onclick="closeModal('newCheckModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="newCheckForm">
                    <div class="form-group">
                        <label>Khu vực kiểm kê <span class="text-danger">*</span></label>
                        <select class="form-select" id="newCheckArea" required>
                            <option value="">Chọn khu vực</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Phương pháp kiểm kê</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="useRFID" checked>
                            <label class="form-check-label" for="useRFID">
                                Sử dụng RFID
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="useBarcode" checked>
                            <label class="form-check-label" for="useBarcode">
                                Sử dụng Barcode
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea class="form-control" id="newCheckNotes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('newCheckModal')">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="createNewCheck()">Tạo phiên kiểm kê</button>
            </div>
        </div>
    </div>

    <!-- Toast container for notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 11;">
        <div id="toastNotification" class="toast" role="alert">
            <div class="toast-header">
                <strong class="me-auto">Thông báo</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" id="toastBody"></div>
        </div>
    </div>
</div>
<script src="js/kiemke.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Camera scanning library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
