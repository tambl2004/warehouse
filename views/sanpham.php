<?php
// File: views/sanpham.php

require_once 'controllers/sanpham_controller.php';
$controller = new SanPhamController();
$data = $controller->xuLyRequest();
?>

<div class="container-fluid">
    <!-- Hiển thị thông báo -->
    <?php $controller->hienThiThongBao(); ?>
    
    <!-- Header với thống kê tổng quan -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="function-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="page-title">
                        <i class="fas fa-boxes text-primary me-2"></i>
                        Quản lý Sản phẩm
                    </h1>
                    
                    <div class="d-flex gap-2">
                        <button class="btn btn-success" onclick="exportProducts('all')">
                            <i class="fas fa-download me-1"></i>Xuất Excel
                        </button>
                        <button class="btn btn-add" onclick="showAddModal()">
                            <i class="fas fa-plus me-1"></i>Thêm sản phẩm
                        </button>
                    </div>
                </div>
                
                <!-- Thống kê nhanh -->
                <div class="row g-3 mb-4">
                    <div class="col-md-2">
                        <div class="card border-0 bg-primary text-white">
                            <div class="card-body text-center p-3">
                             <h3 class="mb-1"><?= $data['statistics']['total_products'] ?></h3>
                                <small>Tổng sản phẩm</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-0 bg-success text-white">
                            <div class="card-body text-center p-3">
                                <h3 class="mb-1"><?= $data['statistics']['in_stock'] ?></h3>
                                <small>Còn hàng</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-0 bg-danger text-white">
                            <div class="card-body text-center p-3">
                                <h3 class="mb-1"><?= $data['statistics']['out_of_stock'] ?></h3>
                                <small>Hết hàng</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-0 bg-warning text-white">
                            <div class="card-body text-center p-3">
                                <h3 class="mb-1"><?= $data['statistics']['low_stock'] ?></h3>
                                <small>Tồn kho thấp</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-0 bg-info text-white">
                            <div class="card-body text-center p-3">
                                <h3 class="mb-1"><?= $data['statistics']['expiring_soon'] ?></h3>
                                <small>Gần hết hạn</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-0 bg-secondary text-white">
                            <div class="card-body text-center p-3">
                                <h3 class="mb-1"><?= $data['statistics']['discontinued'] ?></h3>
                                <small>Ngừng kinh doanh</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Cột chính - Danh sách sản phẩm -->
        <div class="col-lg">
            <div class="function-container">
                <!-- Thanh tìm kiếm và lọc -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchInput" 
                                   placeholder="Tìm theo tên hoặc SKU..." 
                                   value="<?= htmlspecialchars($data['search']) ?>">
                            <button class="btn btn-outline-secondary" onclick="searchProducts()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="categoryFilter" onchange="filterProducts()">
                            <option value="">Tất cả danh mục</option>
                            <?php foreach ($data['categories'] as $category): ?>
                                <option value="<?= $category['category_id'] ?>" 
                                        <?= $data['selected_category'] == $category['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="statusFilter" onchange="filterProducts()">
                            <option value="">Tất cả trạng thái</option>
                            <option value="in_stock" <?= $data['selected_status'] == 'in_stock' ? 'selected' : '' ?>>Còn hàng</option>
                            <option value="out_of_stock" <?= $data['selected_status'] == 'out_of_stock' ? 'selected' : '' ?>>Hết hàng</option>
                            <option value="discontinued" <?= $data['selected_status'] == 'discontinued' ? 'selected' : '' ?>>Ngừng kinh doanh</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-danger w-100" onclick="clearFilters()">
                            <i class="fas fa-times me-1"></i>Xóa bộ lọc
                        </button>
                    </div>
                </div>

                <!-- Bảng danh sách sản phẩm -->
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th width="60">Ảnh</th>
                                <th>SKU</th>
                                <th>Tên sản phẩm</th>
                                <th>Danh mục</th>
                                <th width="100">Giá</th>
                                <th width="80">Tồn kho</th>
                                <th width="100">Hạn sử dụng</th>
                                <th width="100">Trạng thái</th>
                                <th width="120">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data['products'])): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <br>Không có sản phẩm nào
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data['products'] as $product): ?>
                                    <tr>
                                        <td>
                                            <?php if ($product['image_url']): ?>
                                                <img src="uploads/<?= $product['image_url'] ?>" 
                                                     alt="<?= htmlspecialchars($product['product_name']) ?>"
                                                     class="rounded"
                                                     style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="text-primary"><?= htmlspecialchars($product['sku']) ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($product['product_name']) ?></strong>
                                                <?php if ($product['description']): ?>
                                                    <br><small class="text-muted">
                                                        <?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?= htmlspecialchars($product['category_name'] ?? 'Chưa phân loại') ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <strong><?= number_format($product['unit_price'], 0, ',', '.') ?>đ</strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $product['stock_quantity'] == 0 ? 'bg-danger' : 
                                                                 ($product['stock_quantity'] <= 10 ? 'bg-warning' : 'bg-success') ?>">
                                                <?= $product['stock_quantity'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($product['expiry_date']): ?>
                                                <?php 
                                                $expiry = strtotime($product['expiry_date']);
                                                $today = time();
                                                $days_diff = ($expiry - $today) / (60 * 60 * 24);
                                                ?>
                                                <span class="badge <?= $days_diff <= 30 ? 'bg-warning' : 
                                                                    ($days_diff <= 7 ? 'bg-danger' : 'bg-light text-dark') ?>">
                                                    <?= date('d/m/Y', $expiry) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $status_badges = [
                                                'in_stock' => 'bg-success',
                                                'out_of_stock' => 'bg-danger', 
                                                'discontinued' => 'bg-secondary'
                                            ];
                                            $status_texts = [
                                                'in_stock' => 'Còn hàng',
                                                'out_of_stock' => 'Hết hàng',
                                                'discontinued' => 'Ngừng KD'
                                            ];
                                            ?>
                                            <span class="badge <?= $status_badges[$product['status']] ?>">
                                                <?= $status_texts[$product['status']] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="editProduct(<?= $product['product_id'] ?>)"
                                                        title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($product['status'] != 'discontinued'): ?>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="deleteProduct(<?= $product['product_id'] ?>, '<?= htmlspecialchars($product['product_name']) ?>')"
                                                            title="Ngừng kinh doanh">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <?php if ($data['total_pages'] > 1): ?>
                    <nav aria-label="Phân trang sản phẩm">
                        <ul class="pagination justify-content-center">
                            <?php if ($data['current_page'] > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?option=sanpham&page=<?= $data['current_page'] - 1 ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $data['current_page'] - 2); $i <= min($data['total_pages'], $data['current_page'] + 2); $i++): ?>
                                <li class="page-item <?= $i == $data['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="?option=sanpham&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($data['current_page'] < $data['total_pages']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?option=sanpham&page=<?= $data['current_page'] + 1 ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cột bên - Cảnh báo -->
        <div class="col-lg-4">
            <!-- Sản phẩm gần hết hạn -->
            <?php if (!empty($data['expiring_products'])): ?>
                <div class="function-container mb-3">
                    <h5 class="text-warning mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Sản phẩm gần hết hạn
                        <button class="btn btn-sm btn-outline-warning float-end" 
                                onclick="exportProducts('expiring')">
                            <i class="fas fa-download"></i>
                        </button>
                    </h5>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($data['expiring_products'], 0, 5) as $product): ?>
                            <div class="list-group-item px-0 py-2 border-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="text-dark"><?= htmlspecialchars($product['product_name']) ?></strong>
                                        <br><small class="text-muted"><?= $product['sku'] ?></small>
                                    </div>
                                    <div class="text-end">
                                        <small class="badge bg-warning">
                                            <?= $product['days_to_expire'] ?> ngày
                                        </small>
                                        <br><small class="text-muted">
                                            <?= date('d/m/Y', strtotime($product['expiry_date'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($data['expiring_products']) > 5): ?>
                        <div class="text-center mt-2">
                            <small class="text-muted">và <?= count($data['expiring_products']) - 5 ?> sản phẩm khác...</small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Sản phẩm tồn kho thấp -->
            <?php if (!empty($data['low_stock_products'])): ?>
                <div class="function-container">
                    <h5 class="text-danger mb-3">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Tồn kho thấp
                        <button class="btn btn-sm btn-outline-danger float-end" 
                                onclick="exportProducts('low_stock')">
                            <i class="fas fa-download"></i>
                        </button>
                    </h5>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($data['low_stock_products'], 0, 5) as $product): ?>
                            <div class="list-group-item px-0 py-2 border-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="text-dark"><?= htmlspecialchars($product['product_name']) ?></strong>
                                        <br><small class="text-muted"><?= $product['sku'] ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-danger"><?= $product['stock_quantity'] ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($data['low_stock_products']) > 5): ?>
                        <div class="text-center mt-2">
                            <small class="text-muted">và <?= count($data['low_stock_products']) - 5 ?> sản phẩm khác...</small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal thêm/sửa sản phẩm -->
<div class="custom-modal" id="productModal">
    <div class="modal-content" style="width: 800px; max-height: 90vh;">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Thêm sản phẩm mới</h5>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        
        <form id="productForm" method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="row">
                    <!-- Cột trái -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="product_name">Tên sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="product_name" id="product_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">Danh mục <span class="text-danger">*</span></label>
                            <select class="form-select" name="category_id" id="category_id" required>
                                <option value="">Chọn danh mục</option>
                                <?php foreach ($data['categories'] as $category): ?>
                                    <option value="<?= $category['category_id'] ?>">
                                        <?= htmlspecialchars($category['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="unit_price">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="unit_price" id="unit_price" 
                                           min="0" step="1000" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="stock_quantity">Số lượng tồn</label>
                                    <input type="number" class="form-control" name="stock_quantity" id="stock_quantity" 
                                           min="0" value="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="volume">Thể tích (dm³)</label>
                                    <input type="number" class="form-control" name="volume" id="volume" 
                                           min="0" step="0.01" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expiry_date">Hạn sử dụng</label>
                                    <input type="date" class="form-control" name="expiry_date" id="expiry_date">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Trạng thái</label>
                            <select class="form-select" name="status" id="status">
                                <option value="in_stock">Còn hàng</option>
                                <option value="out_of_stock">Hết hàng</option>
                                <option value="discontinued">Ngừng kinh doanh</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Cột phải -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sku">Mã SKU</label>
                            <input type="text" class="form-control" name="sku" id="sku" readonly 
                                   placeholder="Sẽ tự động tạo">
                            <small class="text-muted">Mã SKU sẽ được tạo tự động dựa trên danh mục</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Mô tả sản phẩm</label>
                            <textarea class="form-control" name="description" id="description" 
                                      rows="4" placeholder="Nhập mô tả chi tiết về sản phẩm..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Hình ảnh sản phẩm</label>
                            <div class="image-preview-container" onclick="document.getElementById('image').click()">
                                <img id="imagePreview" class="image-preview" alt="Preview">
                                <div id="imagePreviewPlaceholder">
                                    <i class="fas fa-camera fa-2x text-muted"></i>
                                    <br><span class="text-muted">Nhấp để chọn ảnh</span>
                                    <br><small class="text-muted">JPG, PNG, GIF (tối đa 5MB)</small>
                                </div>
                            </div>
                            <input type="file" name="image" id="image" accept="image/*" style="display: none;" 
                                   onchange="previewImage(this)">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                <button type="submit" class="btn btn-success" id="submitBtn">
                    <i class="fas fa-save me-1"></i>Lưu sản phẩm
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Biến global
let isEditMode = false;
let currentProductId = null;

// Hiển thị modal thêm sản phẩm
function showAddModal() {
    isEditMode = false;
    currentProductId = null;
    
    document.getElementById('modalTitle').textContent = 'Thêm sản phẩm mới';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Lưu sản phẩm';
    document.getElementById('productForm').reset();
    document.getElementById('sku').value = '';
    
    // Reset preview ảnh
    document.getElementById('imagePreview').src = '';
    document.getElementById('imagePreview').classList.remove('has-image');
    document.getElementById('imagePreviewPlaceholder').style.display = 'block';
    
    document.getElementById('productModal').classList.add('show');
}

// Hiển thị modal sửa sản phẩm
function editProduct(productId) {
    isEditMode = true;
    currentProductId = productId;
    
    document.getElementById('modalTitle').textContent = 'Chỉnh sửa sản phẩm';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Cập nhật sản phẩm';
    
    // Lấy thông tin sản phẩm qua API
    fetch(`api/product_handler.php?action=get_product&id=${productId}`)
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`Server returned ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.data) {
                const product = data.data;
                
                // Điền dữ liệu vào form
                document.getElementById('product_name').value = product.product_name || '';
                document.getElementById('description').value = product.description || '';
                document.getElementById('unit_price').value = product.unit_price || 0;
                document.getElementById('stock_quantity').value = product.stock_quantity || 0;
                document.getElementById('expiry_date').value = product.expiry_date || '';
                document.getElementById('category_id').value = product.category_id || '';
                document.getElementById('volume').value = product.volume || 0;
                document.getElementById('status').value = product.status || 'in_stock';
                document.getElementById('sku').value = product.sku || '';
                
                // Hiển thị ảnh nếu có
                if (product.image_url) {
                    document.getElementById('imagePreview').src = `uploads/${product.image_url}`;
                    document.getElementById('imagePreview').classList.add('has-image');
                    document.getElementById('imagePreviewPlaceholder').style.display = 'none';
                } else {
                    document.getElementById('imagePreview').src = '';
                    document.getElementById('imagePreview').classList.remove('has-image');
                    document.getElementById('imagePreviewPlaceholder').style.display = 'block';
                }
                
                // Hiển thị modal
                document.getElementById('productModal').classList.add('show');
            } else {
                alert('Không thể tải thông tin sản phẩm: ' + (data.message || 'Dữ liệu không hợp lệ'));
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải thông tin sản phẩm:', error);
            alert('Có lỗi xảy ra khi tải thông tin sản phẩm: ' + error.message);
        });
}

// Đóng modal
function closeModal() {
    document.getElementById('productModal').classList.remove('show');
}

// Preview ảnh
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('imagePreview').classList.add('has-image');
            document.getElementById('imagePreviewPlaceholder').style.display = 'none';
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Submit form
document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const actionUrl = isEditMode 
        ? `api/product_handler.php?action=edit_product&id=${currentProductId}` 
        : `api/product_handler.php?action=add_product`;
    
    // Disable submit button
    const submitBtn = document.getElementById('submitBtn');
    const originalHtml = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý...';
    submitBtn.disabled = true;
    
    fetch(actionUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`Server returned ${response.status}: ${text}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModal();
            location.reload();
        } else {
            let errorMessage = data.message || 'Có lỗi xảy ra.';
            if (data.errors) {
                errorMessage += '\n' + Object.entries(data.errors)
                    .map(([field, error]) => `- ${field}: ${error}`)
                    .join('\n');
            }
            alert(errorMessage);
        }
    })
    .catch(error => {
        console.error('Lỗi khi lưu sản phẩm:', error);
        alert('Có lỗi xảy ra khi lưu sản phẩm: ' + error.message);
    })
    .finally(() => {
        // Enable submit button
        submitBtn.innerHTML = originalHtml;
        submitBtn.disabled = false;
    });
});

// Xóa sản phẩm
function deleteProduct(productId, productName) {
    if (confirm(`Bạn có chắc muốn ngừng kinh doanh sản phẩm "${productName}"?`)) {
        fetch('api/product_handler.php?action=delete_product', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}`
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`Server returned ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Có lỗi xảy ra: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi khi xóa sản phẩm:', error);
            alert('Có lỗi xảy ra khi xóa sản phẩm: ' + error.message);
        });
    }
}

// Tìm kiếm sản phẩm
function searchProducts() {
    const search = document.getElementById('searchInput').value;
    const categoryId = document.getElementById('categoryFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    const params = new URLSearchParams({
        option: 'sanpham'
    });
    
    if (search) params.append('search', search);
    if (categoryId) params.append('category_id', categoryId);
    if (status) params.append('status', status);
    
    window.location.href = '?' + params.toString();
}

// Lọc sản phẩm
function filterProducts() {
    searchProducts();
}

// Xóa bộ lọc
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('statusFilter').value = '';
    window.location.href = '?option=sanpham';
}

// Xuất báo cáo
function exportProducts(type) {
    window.location.href = `?option=sanpham&action=export&type=${type}`;
}

// Tìm kiếm theo Enter
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchProducts();
    }
});

// Đóng modal khi click bên ngoài
document.getElementById('productModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<style>
/* CSS bổ sung cho trang sản phẩm */
.image-preview-container {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    min-height: 150px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.image-preview-container:hover {
    border-color: #007bff;
    background-color: #f8f9fa;
}

.image-preview {
    max-width: 100%;
    max-height: 120px;
    border-radius: 4px;
    display: none;
}

.image-preview.has-image {
    display: block;
}

.modal-content {
    border-radius: 15px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
}

.badge {
    font-size: 0.75em;
}

.list-group-item {
    transition: background-color 0.2s ease;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}
</style>
