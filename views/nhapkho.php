<?php


// Kiểm tra quyền truy cập
requireLogin();

// Lấy danh sách sản phẩm và nhà cung cấp
$stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'in_stock' ORDER BY product_name");
$stmt->execute();
$products = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE status = 'active' ORDER BY supplier_name");
$stmt->execute();
$suppliers = $stmt->fetchAll();

// Lấy danh sách kho/kệ
$stmt = $pdo->prepare("
    SELECT s.*, wa.area_name 
    FROM shelves s 
    LEFT JOIN warehouse_areas wa ON s.area_id = wa.area_id 
    ORDER BY wa.area_name, s.shelf_code
");
$stmt->execute();
$shelves = $stmt->fetchAll();

// Lấy thống kê
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_imports,
        COUNT(CASE WHEN DATE(import_date) = CURDATE() THEN 1 END) as today_imports,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_imports,
        COALESCE(SUM(CASE WHEN DATE(import_date) = CURDATE() AND status = 'approved' THEN 
            (SELECT SUM(id.quantity * id.unit_price) FROM import_details id WHERE id.import_id = io.import_id)
        END), 0) as today_value
    FROM import_orders io
");
$stmt->execute();
$stats = $stmt->fetch();
?>

<div class="import-management">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="page-title">
            <i class="fas fa-truck-loading me-2"></i>
            Quản lý nhập kho
        </h2>
        <div class="d-flex gap-2">
            <button class="btn btn-success" onclick="exportToExcel()">
                <i class="fas fa-file-excel me-2"></i>
                Xuất Excel
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addImportModal">
                <i class="fas fa-plus me-2"></i>
                Tạo phiếu nhập
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Phiếu nhập hôm nay</h5>
                            <h3 class="mb-0"><?= number_format($stats['today_imports']) ?></h3>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-file-import fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Tổng giá trị hôm nay</h5>
                            <h3 class="mb-0"><?= number_format($stats['today_value']) ?>đ</h3>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Chờ duyệt</h5>
                            <h3 class="mb-0"><?= number_format($stats['pending_imports']) ?></h3>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Tổng phiếu nhập</h5>
                            <h3 class="mb-0"><?= number_format($stats['total_imports']) ?></h3>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-boxes fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Mã phiếu, nhà cung cấp...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả</option>
                        <option value="pending">Chờ duyệt</option>
                        <option value="approved">Đã duyệt</option>
                        <option value="rejected">Từ chối</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Nhà cung cấp</label>
                    <select class="form-select" id="supplierFilter">
                        <option value="">Tất cả</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= $supplier['supplier_id'] ?>"><?= $supplier['supplier_name'] ?></option>
                        <?php endforeach; ?>
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
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadImportOrders()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Orders Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Danh sách phiếu nhập</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="importOrdersTable">
                    <thead class="table-light">
                        <tr>
                            <th>Mã phiếu</th>
                            <th>Nhà cung cấp</th>
                            <th>Ngày nhập</th>
                            <th>Tổng giá trị</th>
                            <th>Trạng thái</th>
                            <th>Người tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Đang tải...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="Phân trang" class="mt-3">
                <ul class="pagination justify-content-center" id="pagination">
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Add Import Modal -->
<div class="modal fade" id="addImportModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tạo phiếu nhập kho</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addImportForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Mã phiếu nhập *</label>
                                <input type="text" class="form-control" name="import_code" id="import_code" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nhà cung cấp *</label>
                                <select class="form-select" name="supplier_id" id="supplier_id" required>
                                    <option value="">Chọn nhà cung cấp</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= $supplier['supplier_id'] ?>"><?= $supplier['supplier_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                <textarea class="form-control" name="notes" rows="2" placeholder="Ghi chú về phiếu nhập..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label mb-0">Sản phẩm nhập kho</label>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addProductRow()">
                                <i class="fas fa-plus me-1"></i>
                                Thêm sản phẩm
                            </button>
                        </div>
                        
                        <div id="productList">
                            <div class="product-item border rounded p-3 mb-3" data-index="0">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">Sản phẩm *</label>
                                        <select class="form-select product-select" name="products[0][product_id]" required onchange="updateProductInfo(this, 0)">
                                            <option value="">Chọn sản phẩm</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= $product['product_id'] ?>" 
                                                        data-sku="<?= $product['sku'] ?>"
                                                        data-name="<?= $product['product_name'] ?>"
                                                        data-price="<?= $product['unit_price'] ?>">
                                                    <?= $product['product_name'] ?> (<?= $product['sku'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Số lượng *</label>
                                        <input type="number" class="form-control quantity-input" name="products[0][quantity]" min="1" required onchange="calculateRowTotal(0)">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Đơn giá *</label>
                                        <input type="number" class="form-control price-input" name="products[0][unit_price]" min="0" step="0.01" required onchange="calculateRowTotal(0)">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Lô hàng</label>
                                        <input type="text" class="form-control" name="products[0][lot_number]" placeholder="LOT001">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Hạn sử dụng</label>
                                        <input type="date" class="form-control" name="products[0][expiry_date]">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeProductRow(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Kệ lưu trữ</label>
                                        <select class="form-select" name="products[0][shelf_id]">
                                            <option value="">Chọn kệ</option>
                                            <?php foreach ($shelves as $shelf): ?>
                                                <option value="<?= $shelf['shelf_id'] ?>">
                                                    <?= $shelf['shelf_code'] ?> - <?= $shelf['area_name'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Thành tiền</label>
                                        <input type="text" class="form-control total-amount" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 offset-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6>Tổng cộng</h6>
                                    <div class="d-flex justify-content-between">
                                        <span>Tổng số lượng:</span>
                                        <span id="totalQuantity">0</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Tổng tiền:</span>
                                        <strong id="totalAmount">0đ</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Tạo phiếu nhập
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Detail Modal -->
<div class="modal fade" id="importDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết phiếu nhập</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="importDetailContent">
                <!-- Content sẽ được load bằng AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf me-2"></i>
                    Xuất PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let productIndex = 1;
let currentImportId = null;

// Load danh sách phiếu nhập
function loadImportOrders(page = 1) {
    const params = new URLSearchParams({
        page: page,
        search: $('#searchInput').val(),
        status: $('#statusFilter').val(),
        supplier_id: $('#supplierFilter').val(),
        from_date: $('#fromDate').val(),
        to_date: $('#toDate').val()
    });

    $.ajax({
        url: 'api/import_handler.php?action=get_imports&' + params.toString(),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayImportOrders(response.data);
                displayPagination(response.pagination, page);
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Lỗi khi tải danh sách phiếu nhập');
        }
    });
}

// Hiển thị danh sách phiếu nhập
function displayImportOrders(imports) {
    const tbody = $('#importOrdersTable tbody');
    tbody.empty();

    if (imports.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="7" class="text-center text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Không có phiếu nhập nào</p>
                </td>
            </tr>
        `);
        return;
    }

    imports.forEach(function(importOrder) {
        const statusBadge = getStatusBadge(importOrder.status);
        const totalValue = formatCurrency(importOrder.total_value || 0);
        const importDate = new Date(importOrder.import_date).toLocaleDateString('vi-VN');
        
        tbody.append(`
            <tr>
                <td><strong>${importOrder.import_code}</strong></td>
                <td>${importOrder.supplier_name}</td>
                <td>${importDate}</td>
                <td>${totalValue}</td>
                <td>${statusBadge}</td>
                <td>${importOrder.creator_name}</td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" title="Xem chi tiết" onclick="viewImportDetail(${importOrder.import_id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${importOrder.status === 'pending' ? `
                            <button class="btn btn-sm btn-outline-success" title="Duyệt" onclick="approveImport(${importOrder.import_id})">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Từ chối" onclick="rejectImport(${importOrder.import_id})">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : ''}
                        <button class="btn btn-sm btn-outline-info" title="Xuất PDF" onclick="exportImportToPDF(${importOrder.import_id})">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `);
    });
}

// Hiển thị phân trang
function displayPagination(pagination, currentPage) {
    const paginationEl = $('#pagination');
    paginationEl.empty();

    if (pagination.total_pages <= 1) return;

    // Previous button
    if (currentPage > 1) {
        paginationEl.append(`
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadImportOrders(${currentPage - 1})">Trước</a>
            </li>
        `);
    }

    // Page numbers
    for (let i = Math.max(1, currentPage - 2); i <= Math.min(pagination.total_pages, currentPage + 2); i++) {
        paginationEl.append(`
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadImportOrders(${i})">${i}</a>
            </li>
        `);
    }

    // Next button
    if (currentPage < pagination.total_pages) {
        paginationEl.append(`
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadImportOrders(${currentPage + 1})">Sau</a>
            </li>
        `);
    }
}

// Lấy badge trạng thái
function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge bg-warning">Chờ duyệt</span>',
        'approved': '<span class="badge bg-success">Đã duyệt</span>',
        'rejected': '<span class="badge bg-danger">Từ chối</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Không xác định</span>';
}

// Tạo mã phiếu nhập tự động
function generateImportCode() {
    const today = new Date();
    const dateStr = today.getFullYear() + 
                   String(today.getMonth() + 1).padStart(2, '0') + 
                   String(today.getDate()).padStart(2, '0');
    
    $.ajax({
        url: 'api/import_handler.php?action=generate_import_code&date=' + dateStr,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#import_code').val(response.import_code);
            }
        }
    });
}

// Thêm dòng sản phẩm
function addProductRow() {
    const productList = $('#productList');
    const newRow = $('.product-item').first().clone();
    
    // Update attributes với index mới
    newRow.attr('data-index', productIndex);
    newRow.find('select, input').each(function() {
        const name = $(this).attr('name');
        if (name) {
            $(this).attr('name', name.replace('[0]', `[${productIndex}]`));
        }
        $(this).val('');
    });
    
    // Update onchange attributes
    newRow.find('.product-select').attr('onchange', `updateProductInfo(this, ${productIndex})`);
    newRow.find('.quantity-input, .price-input').attr('onchange', `calculateRowTotal(${productIndex})`);
    
    productList.append(newRow);
    productIndex++;
}

// Xóa dòng sản phẩm  
function removeProductRow(button) {
    const productItems = $('.product-item');
    if (productItems.length > 1) {
        $(button).closest('.product-item').remove();
        calculateTotalAmount();
    } else {
        showAlert('warning', 'Phải có ít nhất một sản phẩm');
    }
}

// Cập nhật thông tin sản phẩm khi chọn
function updateProductInfo(selectElement, index) {
    const option = $(selectElement).find(':selected');
    const price = option.data('price') || 0;
    
    $(selectElement).closest('.product-item').find('.price-input').val(price);
    calculateRowTotal(index);
}

// Tính toán thành tiền cho từng dòng
function calculateRowTotal(index) {
    const row = $(`.product-item[data-index="${index}"]`);
    const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
    const price = parseFloat(row.find('.price-input').val()) || 0;
    const total = quantity * price;
    
    row.find('.total-amount').val(formatCurrency(total));
    calculateTotalAmount();
}

// Tính tổng số lượng và tiền
function calculateTotalAmount() {
    let totalQuantity = 0;
    let totalAmount = 0;
    
    $('.product-item').each(function() {
        const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
        const price = parseFloat($(this).find('.price-input').val()) || 0;
        
        totalQuantity += quantity;
        totalAmount += quantity * price;
    });
    
    $('#totalQuantity').text(totalQuantity);
    $('#totalAmount').text(formatCurrency(totalAmount));
}

// Format tiền tệ
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

// Xem chi tiết phiếu nhập
function viewImportDetail(importId) {
    currentImportId = importId;
    
    $.ajax({
        url: `api/import_handler.php?action=get_import_detail&id=${importId}`,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#importDetailContent').html(response.html);
                $('#importDetailModal').modal('show');
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Lỗi khi tải chi tiết phiếu nhập');
        }
    });
}

// Duyệt phiếu nhập
function approveImport(importId) {
    if (!confirm('Bạn có chắc chắn muốn duyệt phiếu nhập này?')) return;
    
    $.ajax({
        url: 'api/import_handler.php',
        method: 'POST',
        data: {
            action: 'approve_import',
            import_id: importId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                loadImportOrders();
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Lỗi khi duyệt phiếu nhập');
        }
    });
}

// Từ chối phiếu nhập
function rejectImport(importId) {
    const reason = prompt('Lý do từ chối:');
    if (!reason) return;
    
    $.ajax({
        url: 'api/import_handler.php',
        method: 'POST',
        data: {
            action: 'reject_import',
            import_id: importId,
            reason: reason
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                loadImportOrders();
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Lỗi khi từ chối phiếu nhập');
        }
    });
}

// Xuất PDF phiếu nhập
function exportImportToPDF(importId) {
    window.open(`api/import_handler.php?action=export_pdf&id=${importId}`, '_blank');
}

// Xuất Excel
function exportToExcel() {
    const params = new URLSearchParams({
        search: $('#searchInput').val(),
        status: $('#statusFilter').val(),
        supplier_id: $('#supplierFilter').val(),
        from_date: $('#fromDate').val(),
        to_date: $('#toDate').val()
    });
    
    window.open(`api/import_handler.php?action=export_excel&${params.toString()}`, '_blank');
}

// Xử lý form tạo phiếu nhập
$('#addImportForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'create_import');
    
    // Validate form
    if (!validateImportForm()) return;
    
    $.ajax({
        url: 'api/import_handler.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                $('#addImportModal').modal('hide');
                $('#addImportForm')[0].reset();
                loadImportOrders();
                resetForm();
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Lỗi khi tạo phiếu nhập');
        }
    });
});

// Validate form
function validateImportForm() {
    const supplierId = $('#supplier_id').val();
    if (!supplierId) {
        showAlert('error', 'Vui lòng chọn nhà cung cấp');
        return false;
    }
    
    const productItems = $('.product-item');
    let hasValidProduct = false;
    
    productItems.each(function() {
        const productId = $(this).find('.product-select').val();
        const quantity = $(this).find('.quantity-input').val();
        const price = $(this).find('.price-input').val();
        
        if (productId && quantity && price) {
            hasValidProduct = true;
        }
    });
    
    if (!hasValidProduct) {
        showAlert('error', 'Vui lòng thêm ít nhất một sản phẩm hợp lệ');
        return false;
    }
    
    return true;
}

// Reset form
function resetForm() {
    $('#productList').empty();
    productIndex = 1;
    addProductRow();
    generateImportCode();
}

// Hiển thị thông báo
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    setTimeout(() => {
        $('.alert').fadeOut(() => {
            $('.alert').remove();
        });
    }, 5000);
}

// Khởi tạo khi trang load
$(document).ready(function() {
    generateImportCode();
    loadImportOrders();
    
    // Event listeners cho filters
    $('#searchInput, #statusFilter, #supplierFilter, #fromDate, #toDate').on('change', function() {
        loadImportOrders();
    });
    
    // Reset form khi mở modal
    $('#addImportModal').on('show.bs.modal', function() {
        resetForm();
    });
});
</script> 