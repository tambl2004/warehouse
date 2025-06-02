<?php
/**
 * Giao diện quản lý xuất kho
 * File: views/xuatkho.php
 */

// Kiểm tra quyền truy cập
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<div class="function-container">
    <div class="export-management">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="page-title">
                <i class="fas fa-truck-loading me-2"></i>
                Quản lý xuất kho
            </h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </button>
                <button type="button" class="btn btn-primary" onclick="openCreateExportModal()">
                    <i class="fas fa-plus"></i> Tạo phiếu xuất
                </button>
            </div>
        </div>

        <!-- Thống kê tổng quan -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="export-stats-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-file-export fa-2x"></i>
                            </div>
                            <div>
                                <h3 class="mb-0" id="totalExports">0</h3>
                                <small>Tổng phiếu xuất</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="export-stats-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                            <div>
                                <h3 class="mb-0" id="pendingExports">0</h3>
                                <small>Chờ duyệt</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="export-stats-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <div>
                                <h3 class="mb-0" id="approvedExports">0</h3>
                                <small>Đã duyệt</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="export-stats-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-dollar-sign fa-2x"></i>
                            </div>
                            <div>
                                <h3 class="mb-0" id="totalValue">0đ</h3>
                                <small>Tổng giá trị xuất</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bộ lọc và tìm kiếm -->
        <div class="export-search-section">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Tìm mã phiếu, đích đến...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending">Chờ duyệt</option>
                        <option value="approved">Đã duyệt</option>
                        <option value="rejected">Từ chối</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" class="form-control" id="fromDate">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" class="form-control" id="toDate">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" onclick="searchExports()">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
            
                    </div>
                </div>
            </div>
        </div>

        <!-- Bảng danh sách phiếu xuất -->
        <div class="export-table-container">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã phiếu</th>
                        <th>Ngày xuất</th>
                        <th>Đích đến</th>
                        <th>Người tạo</th>
                        <th>Tổng giá trị</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="exportTableBody">
                    <!-- Dữ liệu sẽ được load bằng Ajax -->
                </tbody>
            </table>
            
            <!-- Loading state -->
            <div id="loadingState" class="export-loading">
                <div class="spinner-border" role="status"></div>
                <div class="export-loading-text">Đang tải dữ liệu...</div>
            </div>
            
            <!-- Empty state -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <i class="fas fa-inbox"></i>
                <p>Chưa có phiếu xuất nào</p>
            </div>
        </div>

        <!-- Phân trang -->
        <nav aria-label="Export pagination">
            <ul class="pagination justify-content-center mt-4" id="exportPagination">
                <!-- Pagination sẽ được tạo bằng JavaScript -->
            </ul>
        </nav>
    </div>

    <!-- Modal tạo/sửa phiếu xuất -->
    <div class="custom-modal export-modal" id="exportModal">
        <div class="modal-content" style="width: 95%; max-width: 1200px;">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalTitle">Tạo phiếu xuất mới</h5>
                <button type="button" class="modal-close" onclick="closeExportModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <input type="hidden" id="editExportId" name="export_id">
                    
                    <!-- Thông tin chung -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="exportCode">Mã phiếu xuất <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="exportCode" name="export_code" readonly>
                                    <button type="button" class="btn btn-outline-secondary" onclick="generateExportCode()">
                                        <i class="fas fa-sync-alt"></i> Tạo mã
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="destination">Đích đến <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="destination" name="destination" 
                                    placeholder="Nhập đích đến (khách hàng, kho khác...)">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="notes">Ghi chú</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                        placeholder="Ghi chú thêm về phiếu xuất..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Danh sách sản phẩm -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6>Danh sách sản phẩm</h6>
                            <button type="button" class="btn btn-add-product" onclick="addProductRow()">
                                <i class="fas fa-plus"></i> Thêm sản phẩm
                            </button>
                        </div>
                        
                        <div id="productsList">
                            <!-- Các dòng sản phẩm sẽ được thêm vào đây -->
                        </div>
                    </div>

                    <!-- Tổng kết -->
                    <div class="total-calculation-card">
                        <h6>Tổng kết phiếu xuất</h6>
                        <div class="d-flex justify-content-between">
                            <span>Số lượng sản phẩm:</span>
                            <span id="totalItems">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Tổng số lượng:</span>
                            <span id="totalQuantity">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><strong>Tổng giá trị:</strong></span>
                            <span><strong id="totalAmount">0đ</strong></span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeExportModal()">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="saveExport()">
                    <i class="fas fa-save"></i> Lưu phiếu xuất
                </button>
            </div>
        </div>
    </div>

    <!-- Modal chi tiết phiếu xuất -->
    <div class="custom-modal export-modal" id="exportDetailModal">
        <div class="modal-content" style="width: 90%; max-width: 1000px;">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết phiếu xuất</h5>
                <button type="button" class="modal-close" onclick="closeExportDetailModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="exportDetailContent">
                    <!-- Nội dung chi tiết sẽ được load bằng Ajax -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeExportDetailModal()">Đóng</button>
                <button type="button" class="btn btn-info" id="printExportBtn" onclick="printExport()">
                    <i class="fas fa-print"></i> In phiếu
                </button>
                <button type="button" class="btn btn-success" id="exportPdfBtn" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf"></i> Xuất PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Modal xác nhận duyệt -->
    <div class="custom-modal" id="approveModal">
        <div class="modal-content" style="width: 400px;">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận duyệt phiếu</h5>
                <button type="button" class="modal-close" onclick="closeApproveModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn duyệt phiếu xuất này không?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Sau khi duyệt, số lượng tồn kho sẽ được trừ và không thể hoàn tác.
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="approveExportId">
                <button type="button" class="btn btn-secondary" onclick="closeApproveModal()">Hủy</button>
                <button type="button" class="btn btn-success" onclick="confirmApprove()">
                    <i class="fas fa-check"></i> Xác nhận duyệt
                </button>
            </div>
        </div>
    </div>

    <!-- Modal từ chối -->
    <div class="custom-modal" id="rejectModal">
        <div class="modal-content" style="width: 500px;">
            <div class="modal-header">
                <h5 class="modal-title">Từ chối phiếu xuất</h5>
                <button type="button" class="modal-close" onclick="closeRejectModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="rejectReason">Lý do từ chối <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rejectReason" rows="4" 
                            placeholder="Nhập lý do từ chối phiếu xuất..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="rejectExportId">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Hủy</button>
                <button type="button" class="btn btn-danger" onclick="confirmReject()">
                    <i class="fas fa-times"></i> Từ chối
                </button>
            </div>
        </div>
    </div>

    <!-- Template cho dòng sản phẩm -->
    <template id="productRowTemplate">
        <div class="product-item" data-index="">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Sản phẩm <span class="text-danger">*</span></label>
                    <select class="form-select product-select" name="products[INDEX][product_id]" onchange="onProductChange(this)">
                        <option value="">Chọn sản phẩm</option>
                        <!-- Options sẽ được load bằng Ajax -->
                    </select>
                    <div class="product-info mt-2" style="display: none;">
                        <small class="text-muted">
                            <span class="product-sku"></span> | 
                            Tồn kho: <span class="product-stock"></span> | 
                            Giá: <span class="product-price"></span>đ
                        </small>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Số lượng <span class="text-danger">*</span></label>
                    <input type="number" class="form-control quantity-input" name="products[INDEX][quantity]" 
                        min="1" placeholder="Số lượng" onchange="calculateRowTotal(this)">
                    <small class="text-danger stock-error" style="display: none;">Vượt quá tồn kho!</small>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Đơn giá</label>
                    <input type="number" class="form-control unit-price-input" name="products[INDEX][unit_price]" 
                        step="0.01" placeholder="Đơn giá" onchange="calculateRowTotal(this)">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Lô hàng</label>
                    <input type="text" class="form-control" name="products[INDEX][lot_number]" placeholder="Số lô">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Kệ</label>
                    <select class="form-select shelf-select" name="products[INDEX][shelf_id]">
                        <option value="">Chọn kệ</option>
                        <!-- Options sẽ được load dựa vào sản phẩm -->
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Thao tác</label>
                    <button type="button" class="btn btn-remove-product w-100" onclick="removeProductRow(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12 text-end">
                    <strong>Thành tiền: <span class="row-total">0đ</span></strong>
                </div>
            </div>
        </div>
    </template>
</div>
<script src="js/xuatkho.js"></script>

<style>
/* CSS bổ sung cho xuất kho */
.export-management {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
    padding: 20px;
}

/* Export Statistics Cards */
.export-stats-card {
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    padding: 25px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    margin-bottom: 20px;
}

.export-stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--gradient-bg, linear-gradient(90deg, #667eea, #764ba2));
}

.export-stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.export-stats-card.bg-primary {
    --gradient-bg: linear-gradient(90deg, #667eea, #764ba2);
}

.export-stats-card.bg-warning {
    --gradient-bg: linear-gradient(90deg, #ff9a56, #ffeaa7);
}

.export-stats-card.bg-success {
    --gradient-bg: linear-gradient(90deg, #56cc9d, #6bb6ff);
}

.export-stats-card.bg-info {
    --gradient-bg: linear-gradient(90deg, #3dd5f3, #3b82f6);
}

/* Export table enhancements */
.export-table-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.export-table-container .table thead th {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    color: #495057;
    padding: 15px;
}

.export-table-container .table tbody tr {
    transition: all 0.2s ease;
}

.export-table-container .table tbody tr:hover {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    transform: scale(1.002);
}

/* Export code styling */
.export-code {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    font-size: 1.1rem;
    color: #495057;
    background: #f8f9fa;
    padding: 6px 10px;
    border-radius: 6px;
    border-left: 4px solid #667eea;
}

/* Search section styling */
.export-search-section {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 25px;
}

/* Responsive cho export management */
@media (max-width: 768px) {
    .export-management {
        padding: 15px;
    }
    
    .export-stats-card {
        margin-bottom: 15px;
    }
    
    .export-search-section {
        padding: 20px;
    }
    
    .export-search-section .row > div {
        margin-bottom: 15px;
    }
}
</style>