/**
 * JavaScript cho quản lý xuất kho
 * File: js/xuatkho.js
 */

// Biến toàn cục
let currentPage = 1;
let productsData = [];
let productRowIndex = 0;
let currentExportId = null;

// Khởi tạo khi tải trang
document.addEventListener('DOMContentLoaded', function() {
    loadExportStatistics();
    loadExportOrders();
    loadProducts();
    
    // Tự động tạo mã phiếu xuất
    generateExportCode();
    
    // Thêm dòng sản phẩm đầu tiên
    addProductRow();
});

/**
 * Tải thống kê xuất kho
 */
function loadExportStatistics() {
    fetch('api/export_handler.php?action=get_statistics')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                document.getElementById('totalExports').textContent = stats.total_exports || 0;
                document.getElementById('pendingExports').textContent = stats.pending_exports || 0;
                document.getElementById('approvedExports').textContent = stats.approved_exports || 0;
                
                const totalValue = parseInt(stats.total_value) || 0;
                document.getElementById('totalValue').textContent = formatCurrency(totalValue);
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải thống kê:', error);
        });
}

/**
 * Tải danh sách phiếu xuất
 */
function loadExportOrders(page = 1) {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;
    
    const params = new URLSearchParams({
        action: 'get_exports',
        page: page,
        limit: 20
    });
    
    if (search) params.append('search', search);
    if (status) params.append('status', status);
    if (fromDate) params.append('from_date', fromDate);
    if (toDate) params.append('to_date', toDate);
    
    showLoading(true);
    
    fetch(`api/export_handler.php?${params}`)
        .then(response => response.json())
        .then(data => {
            showLoading(false);
            
            if (data.success) {
                renderExportTable(data.data);
                renderPagination(data.pagination);
                currentPage = page;
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            showLoading(false);
            console.error('Lỗi khi tải danh sách phiếu xuất:', error);
            showError('Có lỗi xảy ra khi tải dữ liệu');
        });
}

/**
 * Hiển thị bảng danh sách phiếu xuất
 */
function renderExportTable(exports) {
    const tbody = document.getElementById('exportTableBody');
    const emptyState = document.getElementById('emptyState');
    
    if (exports.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    
    let html = '';
    exports.forEach((exportOrder, index) => {
        const exportDate = new Date(exportOrder.export_date).toLocaleDateString('vi-VN');
        const totalValue = formatCurrency(exportOrder.total_value || 0);
        
        let statusBadge = '';
        switch (exportOrder.status) {
            case 'pending':
                statusBadge = '<span class="status-badge bg-warning">Chờ duyệt</span>';
                break;
            case 'approved':
                statusBadge = '<span class="status-badge bg-success">Đã duyệt</span>';
                break;
            case 'rejected':
                statusBadge = '<span class="status-badge bg-danger">Từ chối</span>';
                break;
        }
        
        let actions = '';
        if (exportOrder.status === 'pending') {
            actions = `
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="viewExportDetail(${exportOrder.export_id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-success" onclick="approveExport(${exportOrder.export_id})" title="Duyệt">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="rejectExport(${exportOrder.export_id})" title="Từ chối">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        } else {
            actions = `
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="viewExportDetail(${exportOrder.export_id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="exportToPDF(${exportOrder.export_id})" title="Xuất PDF">
                        <i class="fas fa-file-pdf"></i>
                    </button>
                </div>
            `;
        }
        
        html += `
            <tr>
                <td>${(currentPage - 1) * 20 + index + 1}</td>
                <td><span class="export-code">${exportOrder.export_code}</span></td>
                <td>${exportDate}</td>
                <td>${exportOrder.destination || '-'}</td>
                <td>${exportOrder.creator_name || '-'}</td>
                <td class="text-success fw-bold">${totalValue}</td>
                <td>${statusBadge}</td>
                <td>${actions}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

/**
 * Hiển thị phân trang
 */
function renderPagination(pagination) {
    const container = document.getElementById('exportPagination');
    
    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Nút Previous
    html += `
        <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadExportOrders(${pagination.current_page - 1}); return false;">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `;
    
    // Các số trang
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || 
            (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            html += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadExportOrders(${i}); return false;">${i}</a>
                </li>
            `;
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Nút Next
    html += `
        <li class="page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadExportOrders(${pagination.current_page + 1}); return false;">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;
    
    container.innerHTML = html;
}

/**
 * Tải danh sách sản phẩm
 */
function loadProducts() {
    fetch('api/export_handler.php?action=get_products')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                productsData = data.data;
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải sản phẩm:', error);
        });
}

/**
 * Tìm kiếm phiếu xuất
 */
function searchExports() {
    currentPage = 1;
    loadExportOrders(1);
}

/**
 * Mở modal tạo phiếu xuất
 */
function openCreateExportModal() {
    document.getElementById('exportModalTitle').textContent = 'Tạo phiếu xuất mới';
    document.getElementById('exportForm').reset();
    document.getElementById('editExportId').value = '';
    
    // Xóa tất cả dòng sản phẩm và thêm dòng đầu tiên
    document.getElementById('productsList').innerHTML = '';
    productRowIndex = 0;
    addProductRow();
    
    // Tạo mã phiếu xuất mới
    generateExportCode();
    
    // Reset tổng kết
    updateTotals();
    
    showModal('exportModal');
}

/**
 * Tạo mã phiếu xuất tự động
 */
function generateExportCode() {
    const today = new Date();
    const dateString = today.getFullYear() + 
                      String(today.getMonth() + 1).padStart(2, '0') + 
                      String(today.getDate()).padStart(2, '0');
    
  
    fetch(`api/export_handler.php?action=generate_export_code&date=${dateString}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Lỗi HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.export_code) {
                document.getElementById('exportCode').value = data.export_code;
            } else {
                showError('Không thể tạo mã phiếu: ' + (data.message || 'Lỗi không xác định'));
            }
        })
        .catch(error => {
            console.error('Lỗi khi gọi API tạo mã phiếu:', error);
            showError('Lỗi kết nối khi tạo mã phiếu: ' + error.message);
        });
}
/**
 * Thêm dòng sản phẩm
 */
function addProductRow() {
    const template = document.getElementById('productRowTemplate');
    const clone = template.content.cloneNode(true);
    
    // Thay thế INDEX bằng số thứ tự thực tế
    const html = clone.querySelector('.product-item').outerHTML.replace(/INDEX/g, productRowIndex);
    
    const productsList = document.getElementById('productsList');
    productsList.insertAdjacentHTML('beforeend', html);
    
    // Cập nhật data-index
    const newRow = productsList.lastElementChild;
    newRow.setAttribute('data-index', productRowIndex);
    
    // Populate sản phẩm dropdown
    populateProductSelect(productRowIndex);
    
    productRowIndex++;
}

/**
 * Xóa dòng sản phẩm
 */
function removeProductRow(button) {
    const productItem = button.closest('.product-item');
    productItem.remove();
    updateTotals();
}

/**
 * Populate dropdown sản phẩm
 */
function populateProductSelect(index) {
    const select = document.querySelector(`select[name="products[${index}][product_id]"]`);
    
    let html = '<option value="">Chọn sản phẩm</option>';
    productsData.forEach(product => {
        html += `
            <option value="${product.product_id}" 
                    data-sku="${product.sku}"
                    data-stock="${product.stock_quantity}"
                    data-price="${product.unit_price}"
                    data-category="${product.category_name}">
                ${product.product_name} (${product.sku})
            </option>
        `;
    });
    
    select.innerHTML = html;
}

/**
 * Xử lý khi chọn sản phẩm
 */
function onProductChange(select) {
    const productItem = select.closest('.product-item');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        const sku = selectedOption.getAttribute('data-sku');
        const stock = selectedOption.getAttribute('data-stock');
        const price = selectedOption.getAttribute('data-price');
        const category = selectedOption.getAttribute('data-category');
        
        // Hiển thị thông tin sản phẩm
        const productInfo = productItem.querySelector('.product-info');
        productInfo.style.display = 'block';
        productInfo.querySelector('.product-sku').textContent = sku;
        productInfo.querySelector('.product-stock').textContent = stock;
        productInfo.querySelector('.product-price').textContent = formatNumber(price);
        
        // Set đơn giá mặc định
        const unitPriceInput = productItem.querySelector('.unit-price-input');
        unitPriceInput.value = price;
        
        // Load kệ chứa sản phẩm
        loadProductShelves(selectedOption.value, productItem);
        
        // Cập nhật tính toán
        calculateRowTotal(select);
    } else {
        // Ẩn thông tin sản phẩm
        const productInfo = productItem.querySelector('.product-info');
        productInfo.style.display = 'none';
        
        // Clear kệ dropdown
        const shelfSelect = productItem.querySelector('.shelf-select');
        shelfSelect.innerHTML = '<option value="">Chọn kệ</option>';
        
        // Reset giá
        const unitPriceInput = productItem.querySelector('.unit-price-input');
        unitPriceInput.value = '';
        
        calculateRowTotal(select);
    }
}

/**
 * Tải danh sách kệ chứa sản phẩm
 */
function loadProductShelves(productId, productItem) {
    fetch(`api/export_handler.php?action=get_product_shelves&product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            const shelfSelect = productItem.querySelector('.shelf-select');
            
            if (data.success) {
                let html = '<option value="">Chọn kệ</option>';
                data.data.forEach(shelf => {
                    html += `<option value="${shelf.shelf_id}">${shelf.shelf_display}</option>`;
                });
                shelfSelect.innerHTML = html;
            } else {
                shelfSelect.innerHTML = '<option value="">Chọn kệ</option>';
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải kệ:', error);
        });
}

/**
 * Tính toán tổng tiền cho dòng
 */
function calculateRowTotal(element) {
    const productItem = element.closest('.product-item');
    const quantityInput = productItem.querySelector('.quantity-input');
    const unitPriceInput = productItem.querySelector('.unit-price-input');
    const rowTotal = productItem.querySelector('.row-total');
    const stockError = productItem.querySelector('.stock-error');
    
    const quantity = parseInt(quantityInput.value) || 0;
    const unitPrice = parseFloat(unitPriceInput.value) || 0;
    const total = quantity * unitPrice;
    
    rowTotal.textContent = formatCurrency(total);
    
    // Kiểm tra tồn kho
    const productSelect = productItem.querySelector('.product-select');
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    
    if (selectedOption.value && quantity > 0) {
        const stock = parseInt(selectedOption.getAttribute('data-stock'));
        
        if (quantity > stock) {
            quantityInput.classList.add('is-invalid');
            stockError.style.display = 'block';
        } else {
            quantityInput.classList.remove('is-invalid');
            stockError.style.display = 'none';
        }
    } else {
        quantityInput.classList.remove('is-invalid');
        stockError.style.display = 'none';
    }
    
    // Cập nhật tổng kết
    updateTotals();
}

/**
 * Cập nhật tổng kết phiếu xuất
 */
function updateTotals() {
    const productItems = document.querySelectorAll('.product-item');
    
    let totalItems = 0;
    let totalQuantity = 0;
    let totalAmount = 0;
    
    productItems.forEach(item => {
        const productSelect = item.querySelector('.product-select');
        const quantityInput = item.querySelector('.quantity-input');
        const unitPriceInput = item.querySelector('.unit-price-input');
        
        if (productSelect.value && quantityInput.value && unitPriceInput.value) {
            totalItems++;
            totalQuantity += parseInt(quantityInput.value) || 0;
            totalAmount += (parseInt(quantityInput.value) || 0) * (parseFloat(unitPriceInput.value) || 0);
        }
    });
    
    document.getElementById('totalItems').textContent = totalItems;
    document.getElementById('totalQuantity').textContent = totalQuantity;
    document.getElementById('totalAmount').textContent = formatCurrency(totalAmount);
}

/**
 * Lưu phiếu xuất
 */
function saveExport() {
    const form = document.getElementById('exportForm');
    const formData = new FormData(form);
    
    // Validate form
    if (!validateExportForm()) {
        return;
    }
    
    // Thu thập dữ liệu sản phẩm
    const products = [];
    const productItems = document.querySelectorAll('.product-item');
    
    productItems.forEach((item, index) => {
        const productId = item.querySelector('.product-select').value;
        const quantity = item.querySelector('.quantity-input').value;
        const unitPrice = item.querySelector('.unit-price-input').value;
        const lotNumber = item.querySelector('input[name*="lot_number"]').value;
        const shelfId = item.querySelector('.shelf-select').value;
        
        if (productId && quantity && unitPrice) {
            products.push({
                product_id: productId,
                quantity: parseInt(quantity),
                unit_price: parseFloat(unitPrice),
                lot_number: lotNumber,
                shelf_id: shelfId || null
            });
        }
    });
    
    if (products.length === 0) {
        showError('Vui lòng thêm ít nhất một sản phẩm');
        return;
    }
    
    // Thêm sản phẩm vào FormData
    formData.append('products', JSON.stringify(products));
    formData.append('action', 'create_export');
    
    // Hiển thị loading
    const saveBtn = document.querySelector('#exportModal .btn-primary');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
    saveBtn.disabled = true;
    
    fetch('api/export_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
        
        if (data.success) {
            showSuccess(data.message);
            closeExportModal();
            loadExportOrders(currentPage);
            loadExportStatistics();
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
        console.error('Lỗi khi lưu phiếu xuất:', error);
        showError('Có lỗi xảy ra khi lưu phiếu xuất');
    });
}

/**
 * Validate form phiếu xuất
 */
function validateExportForm() {
    const exportCode = document.getElementById('exportCode').value.trim();
    const destination = document.getElementById('destination').value.trim();
    
    if (!exportCode) {
        showError('Vui lòng nhập mã phiếu xuất');
        return false;
    }
    
    if (!destination) {
        showError('Vui lòng nhập đích đến');
        return false;
    }
    
    // Kiểm tra có sản phẩm nào bị lỗi tồn kho không
    const invalidInputs = document.querySelectorAll('.quantity-input.is-invalid');
    if (invalidInputs.length > 0) {
        showError('Vui lòng kiểm tra lại số lượng các sản phẩm vượt quá tồn kho');
        return false;
    }
    
    return true;
}

/**
 * Xem chi tiết phiếu xuất
 */
function viewExportDetail(exportId) {
    fetch(`api/export_handler.php?action=get_export_detail&id=${exportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('exportDetailContent').innerHTML = data.html;
                currentExportId = exportId;
                
                // Ẩn/hiện các nút theo trạng thái
                const exportOrder = data.data.export_order;
                const printBtn = document.getElementById('printExportBtn');
                const pdfBtn = document.getElementById('exportPdfBtn');
                
                if (exportOrder.status === 'approved') {
                    printBtn.style.display = 'inline-block';
                    pdfBtn.style.display = 'inline-block';
                } else {
                    printBtn.style.display = 'none';
                    pdfBtn.style.display = 'none';
                }
                
                showModal('exportDetailModal');
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải chi tiết phiếu xuất:', error);
            showError('Có lỗi xảy ra khi tải chi tiết phiếu xuất');
        });
}

/**
 * Duyệt phiếu xuất
 */
function approveExport(exportId) {
    document.getElementById('approveExportId').value = exportId;
    showModal('approveModal');
}

/**
 * Xác nhận duyệt
 */
function confirmApprove() {
    const exportId = document.getElementById('approveExportId').value;
    
    const formData = new FormData();
    formData.append('action', 'approve_export');
    formData.append('export_id', exportId);
    
    // Hiển thị loading
    const confirmBtn = document.querySelector('#approveModal .btn-success');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    confirmBtn.disabled = true;
    
    fetch('api/export_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
        
        if (data.success) {
            showSuccess(data.message);
            closeApproveModal();
            loadExportOrders(currentPage);
            loadExportStatistics();
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
        console.error('Lỗi khi duyệt phiếu xuất:', error);
        showError('Có lỗi xảy ra khi duyệt phiếu xuất');
    });
}

/**
 * Từ chối phiếu xuất
 */
function rejectExport(exportId) {
    document.getElementById('rejectExportId').value = exportId;
    document.getElementById('rejectReason').value = '';
    showModal('rejectModal');
}

/**
 * Xác nhận từ chối
 */
function confirmReject() {
    const exportId = document.getElementById('rejectExportId').value;
    const reason = document.getElementById('rejectReason').value.trim();
    
    if (!reason) {
        showError('Vui lòng nhập lý do từ chối');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'reject_export');
    formData.append('export_id', exportId);
    formData.append('reason', reason);
    
    // Hiển thị loading
    const confirmBtn = document.querySelector('#rejectModal .btn-danger');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    confirmBtn.disabled = true;
    
    fetch('api/export_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
        
        if (data.success) {
            showSuccess(data.message);
            closeRejectModal();
            loadExportOrders(currentPage);
            loadExportStatistics();
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
        console.error('Lỗi khi từ chối phiếu xuất:', error);
        showError('Có lỗi xảy ra khi từ chối phiếu xuất');
    });
}

/**
 * In phiếu xuất
 */
function printExport() {
    if (currentExportId) {
        window.open(`api/export_handler.php?action=export_pdf&id=${currentExportId}`, '_blank');
    }
}

/**
 * Xuất PDF
 */
function exportToPDF() {
    if (currentExportId) {
        window.open(`api/export_handler.php?action=export_pdf&id=${currentExportId}`, '_blank');
    }
}

/**
 * Xuất Excel
 */
function exportToExcel() {
    window.open('api/export_handler.php?action=export_excel', '_blank');
}

// Các hàm modal
function closeExportModal() {
    hideModal('exportModal');
}

function closeExportDetailModal() {
    hideModal('exportDetailModal');
    currentExportId = null;
}

function closeApproveModal() {
    hideModal('approveModal');
}

function closeRejectModal() {
    hideModal('rejectModal');
}

// Các hàm utility
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add('show');
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('show');
}

function showLoading(show) {
    const loadingState = document.getElementById('loadingState');
    const table = document.querySelector('.export-table-container table');
    
    if (show) {
        loadingState.style.display = 'flex';
        table.style.display = 'none';
    } else {
        loadingState.style.display = 'none';
        table.style.display = 'table';
    }
}

function showSuccess(message) {
    // Tạo toast thông báo thành công
    const toastContainer = getOrCreateToastContainer();
    const toast = document.createElement('div');
    toast.className = 'toast toast-success';
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="toast-message">${message}</div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Tự động xóa sau 5 giây
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

function showError(message) {
    // Tạo toast thông báo lỗi
    const toastContainer = getOrCreateToastContainer();
    const toast = document.createElement('div');
    toast.className = 'toast toast-error';
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="toast-message">${message}</div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Tự động xóa sau 7 giây
    setTimeout(() => {
        toast.remove();
    }, 7000);
}

function getOrCreateToastContainer() {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    return container;
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

function formatNumber(number) {
    return new Intl.NumberFormat('vi-VN').format(number);
}

// Event listeners
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.custom-modal.show');
        modals.forEach(modal => {
            modal.classList.remove('show');
        });
    }
});

// Click outside để đóng modal
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('custom-modal')) {
        e.target.classList.remove('show');
    }
}); 