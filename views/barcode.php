<?php
// Lấy danh sách sản phẩm cho dropdown
$products = $pdo->query("SELECT product_id, product_name, sku FROM products WHERE status = 'in_stock' ORDER BY product_name")->fetchAll();
?>
<div class="function-container">
    <div class="container-fluid barcode-management">
        <!-- Header -->
        <h1 class="page-title"><i class="fas fa-barcode me-2"></i>Hệ thống quản lý Barcode</h1>

        <!-- Thống kê tổng quan -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 id="totalBarcodes" class="mb-0">0</h3>
                                <small>Tổng số mã vạch</small>
                            </div>
                            <div class="ms-3">
                                <i class="fas fa-barcode fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 id="todayScans" class="mb-0">0</h3>
                                <small>Quét hôm nay</small>
                            </div>
                            <div class="ms-3">
                                <i class="fas fa-search fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 id="scanSuccess" class="mb-0">0</h3>
                                <small>Quét thành công</small>
                            </div>
                            <div class="ms-3">
                                <i class="fas fa-check-circle fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 id="scanFailed" class="mb-0">0</h3>
                                <small>Quét thất bại</small>
                            </div>
                            <div class="ms-3">
                                <i class="fas fa-times-circle fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="barcodeTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="scan-tab" data-bs-toggle="tab" data-bs-target="#scan" type="button" role="tab">
                    <i class="fas fa-camera me-2"></i>Quét Barcode
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="manage-tab" data-bs-toggle="tab" data-bs-target="#manage" type="button" role="tab">
                    <i class="fas fa-list me-2"></i>Quản Lý Mã Vạch
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="generate-tab" data-bs-toggle="tab" data-bs-target="#generate" type="button" role="tab">
                    <i class="fas fa-plus me-2"></i>Tạo Mã Vạch
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab">
                    <i class="fas fa-history me-2"></i>Lịch Sử Quét
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="barcodeTabContent">
            <!-- Tab Quét Barcode -->
            <div class="tab-pane fade show active" id="scan" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-qrcode me-2"></i>Quét Mã Vạch
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="scanForm">
                                    <div class="mb-3">
                                        <label for="scanBarcodeValue" class="form-label">Mã vạch <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="scanBarcodeValue" 
                                            placeholder="Nhập hoặc quét mã vạch" autofocus>
                                        <div class="form-text">Nhập mã vạch hoặc sử dụng máy quét</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="scanDescription" class="form-label">Ghi chú</label>
                                        <textarea class="form-control" id="scanDescription" rows="3" 
                                                placeholder="Ghi chú về việc quét..."></textarea>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-search me-2"></i>Quét Mã Vạch
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="clearScanForm()">
                                            <i class="fas fa-eraser me-2"></i>Xóa
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Kết Quả Quét
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="scanResult" class="text-center">
                                    <i class="fas fa-qrcode fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Chưa có kết quả quét</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Quản Lý Mã Vạch -->
            <div class="tab-pane fade" id="manage" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-list me-2"></i>Danh Sách Mã Vạch
                                </h5>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-primary" onclick="showAddBarcodeModal()">
                                    <i class="fas fa-plus me-2"></i>Thêm Mã Vạch
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Bộ lọc -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="searchBarcodes" 
                                    placeholder="Tìm kiếm mã vạch, sản phẩm...">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="productFilter">
                                    <option value="">Tất cả sản phẩm</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= $product['product_id'] ?>">
                                            <?= htmlspecialchars($product['product_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary" onclick="searchBarcodes()">
                                    <i class="fas fa-search"></i> Tìm kiếm
                                </button>
                            </div>
                        </div>

                        <!-- Bảng dữ liệu -->
                        <div class="table-responsive">
                            <table class="table table-hover" id="barcodesTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Mã Vạch</th>
                                        <th>Sản Phẩm</th>
                                        <th>SKU</th>
                                        <th>Lô Hàng</th>
                                        <th>Hạn Sử Dụng</th>
                                        <th>Ngày Tạo</th>
                                        <th>Hành Động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dữ liệu sẽ được load bằng JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Phân trang -->
                        <nav aria-label="Phân trang mã vạch">
                            <ul class="pagination justify-content-center" id="barcodePagination">
                                <!-- Phân trang sẽ được tạo bằng JS -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Tab Tạo Mã Vạch -->
            <div class="tab-pane fade" id="generate" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-magic me-2"></i>Tạo Mã Vạch Đơn Lẻ
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="generateForm">
                                    <div class="mb-3">
                                        <label for="generateBarcodeValue" class="form-label">Mã vạch <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="generateBarcodeValue" 
                                                placeholder="Nhập mã vạch">
                                            <button type="button" class="btn btn-outline-secondary" onclick="generateRandomBarcode()">
                                                <i class="fas fa-random"></i> Tự động
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="generateProductId" class="form-label">Sản phẩm <span class="text-danger">*</span></label>
                                        <select class="form-select" id="generateProductId" required>
                                            <option value="">Chọn sản phẩm</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= $product['product_id'] ?>">
                                                    <?= htmlspecialchars($product['product_name']) ?> (<?= $product['sku'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="generateLotNumber" class="form-label">Lô hàng</label>
                                        <input type="text" class="form-control" id="generateLotNumber" 
                                            placeholder="Mã lô hàng">
                                    </div>
                                    <div class="mb-3">
                                        <label for="generateExpiryDate" class="form-label">Hạn sử dụng</label>
                                        <input type="date" class="form-control" id="generateExpiryDate">
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-plus me-2"></i>Tạo Mã Vạch
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-layer-group me-2"></i>Tạo Hàng Loạt
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="bulkGenerateForm">
                                    <div class="mb-3">
                                        <label for="bulkProductId" class="form-label">Sản phẩm <span class="text-danger">*</span></label>
                                        <select class="form-select" id="bulkProductId" required>
                                            <option value="">Chọn sản phẩm</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= $product['product_id'] ?>">
                                                    <?= htmlspecialchars($product['product_name']) ?> (<?= $product['sku'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="bulkQuantity" class="form-label">Số lượng <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="bulkQuantity" 
                                            min="1" max="100" value="1" required>
                                        <div class="form-text">Tối đa 100 mã vạch</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="bulkLotNumber" class="form-label">Lô hàng</label>
                                        <input type="text" class="form-control" id="bulkLotNumber" 
                                            placeholder="Mã lô hàng chung">
                                    </div>
                                    <div class="mb-3">
                                        <label for="bulkExpiryDate" class="form-label">Hạn sử dụng</label>
                                        <input type="date" class="form-control" id="bulkExpiryDate">
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-layer-group me-2"></i>Tạo Hàng Loạt
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Xem trước mã vạch -->
                <div class="card mt-4" id="barcodePreview" style="display: none;">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-eye me-2"></i>Xem Trước Mã Vạch
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div id="barcodeImage"></div>
                        <div class="mt-3">
                            <button class="btn btn-outline-primary me-2" onclick="downloadBarcode('png')">
                                <i class="fas fa-download me-2"></i>Tải PNG
                            </button>
                            <button class="btn btn-outline-secondary" onclick="printBarcode()">
                                <i class="fas fa-print me-2"></i>In
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Lịch Sử Quét -->
            <div class="tab-pane fade" id="logs" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history me-2"></i>Lịch Sử Quét Mã Vạch
                                </h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Bộ lọc lịch sử -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="searchLogs" 
                                    placeholder="Tìm mã vạch...">
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="userFilter" 
                                    placeholder="Tìm người dùng...">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" id="resultFilter">
                                    <option value="">Tất cả kết quả</option>
                                    <option value="success">Thành công</option>
                                    <option value="failed">Thất bại</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary" onclick="searchLogs()">
                                    <i class="fas fa-search"></i> Tìm kiếm
                                </button>
                            </div>
                        </div>

                        <!-- Bảng lịch sử -->
                        <div class="table-responsive">
                            <table class="table table-hover" id="logsTable">
                                <thead>
                                    <tr>
                                        <th>Thời Gian</th>
                                        <th>Mã Vạch</th>
                                        <th>Sản Phẩm</th>
                                        <th>Người Quét</th>
                                        <th>Kết Quả</th>
                                        <th>Ghi Chú</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dữ liệu sẽ được load bằng JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Phân trang lịch sử -->
                        <nav aria-label="Phân trang lịch sử">
                            <ul class="pagination justify-content-center" id="logsPagination">
                                <!-- Phân trang sẽ được tạo bằng JS -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal thêm/sửa mã vạch -->
    <div class="modal fade" id="barcodeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="barcodeModalTitle">Thêm Mã Vạch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="barcodeForm">
                        <input type="hidden" id="barcodeId">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="barcodeValue" class="form-label">Mã vạch <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="barcodeValue" required>
                                </div>
                                <div class="mb-3">
                                    <label for="productId" class="form-label">Sản phẩm <span class="text-danger">*</span></label>
                                    <select class="form-select" id="productId" required>
                                        <option value="">Chọn sản phẩm</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?= $product['product_id'] ?>">
                                                <?= htmlspecialchars($product['product_name']) ?> (<?= $product['sku'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lotNumber" class="form-label">Lô hàng</label>
                                    <input type="text" class="form-control" id="lotNumber">
                                </div>
                                <div class="mb-3">
                                    <label for="expiryDate" class="form-label">Hạn sử dụng</label>
                                    <input type="date" class="form-control" id="expiryDate">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveBarcodeModal()">Lưu</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal tạo sản phẩm mới từ barcode -->
    <div class="modal fade" id="createProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tạo Sản Phẩm Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-info">Mã vạch chưa tồn tại trong hệ thống. Bạn có muốn tạo sản phẩm mới?</p>
                    <form id="createProductForm">
                        <input type="hidden" id="newProductBarcode">
                        <!-- Form tạo sản phẩm sẽ được thêm vào đây -->
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-success" onclick="createNewProduct()">Tạo Sản Phẩm</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="js/barcode.js"></script>