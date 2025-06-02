<?php
// File: views/nhacungcap.php
require_once 'controllers/NhaCungCapController.php';
$controller = new NhaCungCapController();
$data = $controller->xuLyRequest();
?>

<div class="function-container">
    <h1 class="page-title"><i class="fas fa-truck-loading me-2"></i>Quản lý Nhà Cung Cấp</h1>
    
    <?php $controller->hienThiThongBao(); ?>

    <div class="content-header mb-3">
        <form action="?option=nhacungcap" method="GET" class="d-flex gap-2 w-100">
            <input type="hidden" name="option" value="nhacungcap">
            <div class="search-box flex-grow-1">
                <input type="text" class="form-control" name="search" placeholder="Tìm kiếm theo Tên, Mã, Email, SĐT..." value="<?= htmlspecialchars($data['search']) ?>">
            </div>
            
            <select class="form-select filter-dropdown" name="status" style="max-width: 200px;">
                <option value="">Tất cả trạng thái</option>
                <option value="active" <?= ($data['selected_status'] == 'active') ? 'selected' : '' ?>>Đang hợp tác</option>
                <option value="inactive" <?= ($data['selected_status'] == 'inactive') ? 'selected' : '' ?>>Tạm ngưng</option>
                <option value="discontinued" <?= ($data['selected_status'] == 'discontinued') ? 'selected' : '' ?>>Ngừng hẳn</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Lọc</button>
            <a href="?option=nhacungcap" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Xóa lọc</a>
        </form>
        
        <button class="btn btn-add ms-2 flex-shrink-0" onclick="showSupplierModal(null)">
            <i class="fas fa-plus me-1"></i>Thêm NCC
        </button>
    </div>

    <div class="table-responsive">
        <table class="table data-table table-hover">
            <thead>
                <tr>
                    <th>Mã NCC</th>
                    <th>Tên Nhà Cung Cấp</th>
                    <th>Email</th>
                    <th>Điện thoại</th>
                    <th>Địa chỉ</th>
                    <th>MST</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th width="120">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['suppliers'])): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state text-center py-5">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <h5>Chưa có nhà cung cấp nào</h5>
                                <p class="mb-0 text-muted">Hãy bắt đầu bằng cách thêm nhà cung cấp mới.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['suppliers'] as $supplier): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($supplier['supplier_code'] ?: 'N/A') ?></strong></td>
                        <td><?= htmlspecialchars($supplier['supplier_name']) ?></td>
                        <td><?= htmlspecialchars($supplier['email'] ?: 'N/A') ?></td>
                        <td><?= htmlspecialchars($supplier['phone_number'] ?: 'N/A') ?></td>
                        <td><?= nl2br(htmlspecialchars($supplier['address'] ?: 'N/A')) ?></td>
                        <td><?= htmlspecialchars($supplier['tax_code'] ?: 'N/A') ?></td>
                        <td>
                            <?php
                                $status_class = 'secondary';
                                $status_text = 'Không xác định';
                                if ($supplier['status'] == 'active') { $status_class = 'success'; $status_text = 'Đang hợp tác'; }
                                elseif ($supplier['status'] == 'inactive') { $status_class = 'warning text-dark'; $status_text = 'Tạm ngưng'; }
                                elseif ($supplier['status'] == 'discontinued') { $status_class = 'danger'; $status_text = 'Ngừng hẳn'; }
                            ?>
                            <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($supplier['created_at'])) ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="showSupplierModal(<?= $supplier['supplier_id'] ?>)" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($supplier['status'] === 'discontinued'): ?>
                                    <button class="btn btn-outline-success" onclick="toggleSupplierStatus(<?= $supplier['supplier_id'] ?>, '<?= $supplier['status'] ?>')" title="Hợp tác lại">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-outline-danger" onclick="toggleSupplierStatus(<?= $supplier['supplier_id'] ?>, '<?= $supplier['status'] ?>')" title="Ngừng cung cấp">
                                        <i class="fas fa-store-slash"></i>
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
    
    <?php if ($data['total_pages'] > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $data['total_pages']; $i++): ?>
                <li class="page-item <?= ($i == $data['current_page']) ? 'active' : '' ?>">
                    <a class="page-link" href="?option=nhacungcap&page=<?= $i ?>&search=<?= urlencode($data['search']) ?>&status=<?= urlencode($data['selected_status']) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<div class="modal fade custom-modal" id="supplierModal" tabindex="-1" aria-labelledby="supplierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="supplierForm" method="POST">
                <input type="hidden" name="supplier_id" id="form_supplier_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="supplierModalLabel">Thêm Nhà Cung Cấp</h5>
                    <button type="button" class="btn-close modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="supplier_code" class="form-label">Mã Nhà Cung Cấp</label>
                                <input type="text" class="form-control" id="supplier_code" name="supplier_code" placeholder="Để trống để tự tạo">
                                <div class="form-text text-danger error-text" id="error_supplier_code"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="supplier_name" class="form-label">Tên Nhà Cung Cấp <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="supplier_name" name="supplier_name" required>
                                <div class="form-text text-danger error-text" id="error_supplier_name"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                                <div class="form-text text-danger error-text" id="error_email"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="phone_number" class="form-label">Số Điện Thoại</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="address" class="form-label">Địa Chỉ</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="tax_code" class="form-label">Mã Số Thuế</label>
                                <input type="text" class="form-control" id="tax_code" name="tax_code">
                                <div class="form-text text-danger error-text" id="error_tax_code"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="contact_person" class="form-label">Người Liên Hệ</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="website" class="form-label">Website</label>
                        <input type="url" class="form-control" id="website" name="website" placeholder="https://example.com">
                    </div>
                    <div class="form-group mb-3">
                        <label for="notes" class="form-label">Ghi Chú</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                    <div class="form-group mb-3" id="status_form_group" style="display:none;">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active">Đang hợp tác</option>
                            <option value="inactive">Tạm ngưng</option>
                            <option value="discontinued">Ngừng hẳn</option>
                        </select>
                    </div>
                    <small class="form-text text-muted">Trường có dấu <span class="text-danger">*</span> là bắt buộc.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitSupplier">Lưu Nhà Cung Cấp</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let isEditModeSupplier = false;
    let currentSupplierId = null;
    const supplierModal = new bootstrap.Modal(document.getElementById('supplierModal'));

    window.showSupplierModal = function(supplierId = null) {
        const form = document.getElementById('supplierForm');
        form.reset();
        document.querySelectorAll('.error-text').forEach(el => el.textContent = '');
        document.getElementById('form_supplier_id').value = '';
        isEditModeSupplier = false;
        currentSupplierId = null;

        const statusGroup = document.getElementById('status_form_group');

        if (supplierId) {
            isEditModeSupplier = true;
            currentSupplierId = supplierId;
            document.getElementById('supplierModalLabel').textContent = 'Chỉnh sửa Nhà Cung Cấp';
            document.getElementById('btnSubmitSupplier').textContent = 'Cập nhật';
            statusGroup.style.display = 'block';

            fetch(`api/supplier_handler.php?action=get_supplier&id=${supplierId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        const s = data.data;
                        document.getElementById('form_supplier_id').value = s.supplier_id;
                        document.getElementById('supplier_code').value = s.supplier_code || '';
                        document.getElementById('supplier_name').value = s.supplier_name;
                        document.getElementById('email').value = s.email || '';
                        document.getElementById('phone_number').value = s.phone_number || '';
                        document.getElementById('address').value = s.address || '';
                        document.getElementById('tax_code').value = s.tax_code || '';
                        document.getElementById('contact_person').value = s.contact_person || '';
                        document.getElementById('website').value = s.website || '';
                        document.getElementById('notes').value = s.notes || '';
                        document.getElementById('status').value = s.status;
                        supplierModal.show();
                    } else {
                        alert('Lỗi: ' + (data.message || 'Không thể tải dữ liệu NCC.'));
                    }
                }).catch(err => {
                    console.error("Fetch error:", err);
                    alert('Lỗi kết nối hoặc xử lý dữ liệu.');
                });
        } else {
            document.getElementById('supplierModalLabel').textContent = 'Thêm Nhà Cung Cấp Mới';
            document.getElementById('btnSubmitSupplier').textContent = 'Lưu Nhà Cung Cấp';
            statusGroup.style.display = 'none';
            supplierModal.show();
        }
    }

    document.getElementById('supplierForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const actionUrl = isEditModeSupplier ? 
                        `api/supplier_handler.php?action=edit_supplier` : 
                        `api/supplier_handler.php?action=add_supplier`;

        fetch(actionUrl, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                document.querySelectorAll('.error-text').forEach(el => el.textContent = '');
                if (data.success) {
                    supplierModal.hide();
                    alert(data.message);
                    location.reload();
                } else {
                    let mainMessage = data.message || 'Vui lòng kiểm tra lại dữ liệu.';
                    if (data.errors) {
                        for (const field in data.errors) {
                            const errorField = document.getElementById(`error_${field}`);
                            if (errorField) {
                                errorField.textContent = data.errors[field];
                            } else {
                                mainMessage += `\n- ${data.errors[field]}`;
                            }
                        }
                    }
                    alert(mainMessage);
                }
            })
            .catch(err => {
                console.error("Submit error:", err);
                alert('Lỗi xử lý yêu cầu.');
            });
    });

    window.toggleSupplierStatus = function(supplierId, currentStatus) {
        const actionText = currentStatus === 'discontinued' ? 'Hợp tác lại với' : 'Ngừng cung cấp cho';
        if (confirm(`Bạn có chắc muốn ${actionText} nhà cung cấp này không?`)) {
            fetch('api/supplier_handler.php?action=toggle_status_supplier', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `supplier_id=${supplierId}&current_status=${currentStatus}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            })
            .catch(err => {
                console.error("Toggle status error:", err);
                alert('Lỗi khi thay đổi trạng thái.');
            });
        }
    }
});
</script>