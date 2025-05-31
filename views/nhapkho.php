<?php


// Kiểm tra quyền truy cập
requireLogin();

// Lấy danh sách sản phẩm và nhà cung cấp
$stmt = $pdo->prepare("SELECT * FROM products ORDER BY product_name");
$stmt->execute();
$products = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM suppliers ORDER BY supplier_name");
$stmt->execute();
$suppliers = $stmt->fetchAll();
?>

<div class="import-management">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="page-title">
            <i class="fas fa-truck-loading me-2"></i>
            Nhập kho
        </h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addImportModal">
            <i class="fas fa-plus me-2"></i>
            Tạo phiếu nhập
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Phiếu nhập hôm nay</h5>
                            <h3 class="mb-0">12</h3>
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
                            <h5 class="card-title">Tổng giá trị</h5>
                            <h3 class="mb-0">2.5M</h3>
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
                            <h3 class="mb-0">5</h3>
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
                            <h5 class="card-title">Sản phẩm</h5>
                            <h3 class="mb-0">125</h3>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-boxes fa-2x"></i>
                        </div>
                    </div>
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
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Mã phiếu</th>
                            <th>Nhà cung cấp</th>
                            <th>Ngày nhập</th>
                            <th>Tổng giá trị</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>NK001</strong></td>
                            <td>Công ty TNHH Thực phẩm ABC</td>
                            <td>31/05/2025 10:30</td>
                            <td><span class="text-success fw-bold">2,500,000đ</span></td>
                            <td><span class="badge bg-success">Đã duyệt</span></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>NK002</strong></td>
                            <td>Công ty CP Thực phẩm XYZ</td>
                            <td>31/05/2025 14:15</td>
                            <td><span class="text-warning fw-bold">1,800,000đ</span></td>
                            <td><span class="badge bg-warning">Chờ duyệt</span></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" title="Duyệt">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" title="Từ chối">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Import Modal -->
<div class="modal fade" id="addImportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
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
                                <input type="text" class="form-control" name="import_code" value="NK<?= date('YmdHis') ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nhà cung cấp *</label>
                                <select class="form-select" name="supplier_id" required>
                                    <option value="">Chọn nhà cung cấp</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= $supplier['supplier_id'] ?>"><?= $supplier['supplier_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Sản phẩm nhập kho</label>
                        <div id="productList">
                            <div class="product-item border rounded p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Sản phẩm</label>
                                        <select class="form-select" name="products[0][product_id]" required>
                                            <option value="">Chọn sản phẩm</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= $product['product_id'] ?>"><?= $product['product_name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Số lượng</label>
                                        <input type="number" class="form-control" name="products[0][quantity]" min="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Đơn giá</label>
                                        <input type="number" class="form-control" name="products[0][unit_price]" min="0" step="0.01" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Lô hàng</label>
                                        <input type="text" class="form-control" name="products[0][lot_number]" placeholder="LOT001">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeProduct(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary" onclick="addProduct()">
                            <i class="fas fa-plus me-1"></i>
                            Thêm sản phẩm
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Tạo phiếu nhập</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let productIndex = 1;

function addProduct() {
    const productList = document.getElementById('productList');
    const newProduct = document.querySelector('.product-item').cloneNode(true);
    
    // Update name attributes
    newProduct.querySelectorAll('select, input').forEach(element => {
        const name = element.getAttribute('name');
        if (name) {
            element.setAttribute('name', name.replace('[0]', `[${productIndex}]`));
            element.value = '';
        }
    });
    
    productList.appendChild(newProduct);
    productIndex++;
}

function removeProduct(button) {
    const productItems = document.querySelectorAll('.product-item');
    if (productItems.length > 1) {
        button.closest('.product-item').remove();
    }
}

// Form submission
document.getElementById('addImportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Simulate form submission
    const modal = bootstrap.Modal.getInstance(document.getElementById('addImportModal'));
    modal.hide();
    
    // Show success message
    showAlert('success', 'Tạo phiếu nhập thành công!');
});

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script> 