// File JavaScript xử lý các chức năng frontend cho module nhập kho
class NhapKhoManager {
    constructor() {
        this.currentPage = 1;
        this.pageSize = 10;
        this.searchTimeout = null;
        this.init();
    }

    // Khởi tạo các event listeners và load dữ liệu ban đầu
    init() {
        this.loadImports();
        this.loadSuppliers();
        this.loadProducts();
        this.loadShelves();
        this.setupEventListeners();
        this.generateImportCode();
    }

    // Thiết lập các event listeners
    setupEventListeners() {
        // Tìm kiếm với debounce
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.currentPage = 1;
                    this.loadImports();
                }, 300);
            });
        }

        // Lọc theo trạng thái
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => {
                this.currentPage = 1;
                this.loadImports();
            });
        }

        // Lọc theo nhà cung cấp
        const supplierFilter = document.getElementById('supplierFilter');
        if (supplierFilter) {
            supplierFilter.addEventListener('change', () => {
                this.currentPage = 1;
                this.loadImports();
            });
        }

        // Nút thêm sản phẩm mới trong modal
        const addProductBtn = document.getElementById('addProductBtn');
        if (addProductBtn) {
            addProductBtn.addEventListener('click', () => {
                this.addProductRow();
            });
        }

        // Form tạo phiếu nhập
        const createImportForm = document.getElementById('createImportForm');
        if (createImportForm) {
            createImportForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createImport();
            });
        }

        // Reset modal khi mở
        const createImportModal = document.getElementById('createImportModal');
        if (createImportModal) {
            createImportModal.addEventListener('show.bs.modal', () => {
                this.resetModal();
                this.generateImportCode();
            });
        }

        // Thêm event listener cho xuất Excel
        const exportExcelBtn = document.getElementById('exportExcelBtn');
        if (exportExcelBtn) {
            exportExcelBtn.addEventListener('click', () => {
                this.exportToExcel();
            });
        }
    }

    // Load danh sách phiếu nhập với phân trang và filter
    async loadImports() {
        try {
            this.showLoading('importsTableContainer');
            
            const searchValue = document.getElementById('searchInput')?.value || '';
            const statusFilter = document.getElementById('statusFilter')?.value || '';
            const supplierFilter = document.getElementById('supplierFilter')?.value || '';
            
            const params = new URLSearchParams({
                action: 'get_imports',
                page: this.currentPage,
                limit: this.pageSize,
                search: searchValue,
                status: statusFilter,
                supplier_id: supplierFilter
            });

            const response = await fetch(`api/import_handler.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.displayImports(data.data.imports);
                this.updatePagination(data.data.total, data.data.current_page, data.data.total_pages);
                this.updateStatistics(data.data.statistics);
            } else {
                this.showError('Không thể tải danh sách phiếu nhập: ' + data.message);
            }
        } catch (error) {
            console.error('Error loading imports:', error);
            this.showError('Có lỗi xảy ra khi tải dữ liệu');
        } finally {
            this.hideLoading('importsTableContainer');
        }
    }

    // Hiển thị danh sách phiếu nhập trong bảng
    displayImports(imports) {
        const tbody = document.getElementById('importsTableBody');
        if (!tbody) return;

        if (!imports || imports.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="mb-0">Không có phiếu nhập nào</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = imports.map(importOrder => `
            <tr>
                <td>
                    <span class="fw-bold">${importOrder.import_code}</span>
                </td>
                <td>
                    <div>
                        <div class="fw-semibold">${importOrder.supplier_name}</div>
                        <small class="text-muted">${importOrder.supplier_phone || ''}</small>
                    </div>
                </td>
                <td>
                    <span class="badge badge-${this.getStatusColor(importOrder.status)} status-badge">
                        ${this.getStatusText(importOrder.status)}
                    </span>
                </td>
                <td>
                    <div>
                        <div>${new Date(importOrder.import_date).toLocaleDateString('vi-VN')}</div>
                        <small class="text-muted">${new Date(importOrder.import_date).toLocaleTimeString('vi-VN')}</small>
                    </div>
                </td>
                <td>${importOrder.created_by_name}</td>
                <td class="text-end">
                    <span class="fw-bold text-primary">
                        ${new Intl.NumberFormat('vi-VN').format(importOrder.total_value || 0)} VNĐ
                    </span>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-info" 
                                onclick="nhapKhoManager.viewImportDetail(${importOrder.import_id})"
                                title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${importOrder.status === 'pending' ? `
                            <button type="button" class="btn btn-sm btn-outline-success" 
                                    onclick="nhapKhoManager.approveImport(${importOrder.import_id})"
                                    title="Duyệt phiếu">
                                <i class="fas fa-check"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="nhapKhoManager.rejectImport(${importOrder.import_id})"
                                    title="Từ chối">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : ''}
                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                onclick="nhapKhoManager.exportToPDF(${importOrder.import_id})"
                                title="Xuất PDF">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    // Cập nhật thống kê hiển thị trên dashboard
    updateStatistics(stats) {
        if (!stats) return;

        // Cập nhật các card thống kê
        const totalImportsEl = document.getElementById('totalImports');
        if (totalImportsEl) totalImportsEl.textContent = stats.total_imports || 0;

        const todayImportsEl = document.getElementById('todayImports');
        if (todayImportsEl) todayImportsEl.textContent = stats.today_imports || 0;

        const pendingImportsEl = document.getElementById('pendingImports');
        if (pendingImportsEl) pendingImportsEl.textContent = stats.pending_imports || 0;

        const todayValueEl = document.getElementById('todayValue');
        if (todayValueEl) {
            todayValueEl.textContent = new Intl.NumberFormat('vi-VN').format(stats.today_value || 0) + ' VNĐ';
        }
    }

    // Cập nhật phân trang
    updatePagination(total, currentPage, totalPages) {
        const paginationEl = document.getElementById('pagination');
        if (!paginationEl) return;

        let paginationHTML = '';
        
        // Nút Previous
        paginationHTML += `
            <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="nhapKhoManager.goToPage(${currentPage - 1})">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;

        // Số trang
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="nhapKhoManager.goToPage(1)">1</a></li>`;
            if (startPage > 2) {
                paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="nhapKhoManager.goToPage(${i})">${i}</a>
                </li>
            `;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="nhapKhoManager.goToPage(${totalPages})">${totalPages}</a></li>`;
        }

        // Nút Next
        paginationHTML += `
            <li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="nhapKhoManager.goToPage(${currentPage + 1})">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;

        paginationEl.innerHTML = paginationHTML;

        // Cập nhật thông tin phân trang
        const paginationInfo = document.getElementById('paginationInfo');
        if (paginationInfo) {
            const startItem = (currentPage - 1) * this.pageSize + 1;
            const endItem = Math.min(currentPage * this.pageSize, total);
            paginationInfo.textContent = `Hiển thị ${startItem}-${endItem} của ${total} phiếu nhập`;
        }
    }

    // Chuyển trang
    goToPage(page) {
        if (page < 1) return;
        this.currentPage = page;
        this.loadImports();
    }

    // Load danh sách nhà cung cấp cho dropdown
    async loadSuppliers() {
        try {
            const response = await fetch('api/import_handler.php?action=get_suppliers');
            const data = await response.json();

            if (data.success) {
                this.populateSupplierDropdowns(data.data);
            }
        } catch (error) {
            console.error('Error loading suppliers:', error);
        }
    }

    // Điền dữ liệu vào các dropdown nhà cung cấp
    populateSupplierDropdowns(suppliers) {
        // Dropdown trong filter
        const supplierFilter = document.getElementById('supplierFilter');
        if (supplierFilter) {
            supplierFilter.innerHTML = '<option value="">Tất cả nhà cung cấp</option>' +
                suppliers.map(supplier => 
                    `<option value="${supplier.supplier_id}">${supplier.name}</option>`
                ).join('');
        }

        // Dropdown trong modal tạo phiếu
        const supplierSelect = document.getElementById('supplierId');
        if (supplierSelect) {
            supplierSelect.innerHTML = '<option value="">Chọn nhà cung cấp...</option>' +
                suppliers.map(supplier => 
                    `<option value="${supplier.supplier_id}" data-phone="${supplier.phone || ''}" data-address="${supplier.address || ''}">${supplier.name}</option>`
                ).join('');
        }
    }

    // Load danh sách sản phẩm
    async loadProducts() {
        try {
            const response = await fetch('api/import_handler.php?action=get_products');
            const data = await response.json();

            if (data.success) {
                this.products = data.data;
                this.populateProductDropdowns();
            }
        } catch (error) {
            console.error('Error loading products:', error);
        }
    }

    // Điền dữ liệu vào dropdown sản phẩm
    populateProductDropdowns() {
        const productSelects = document.querySelectorAll('.product-select');
        productSelects.forEach(select => {
            const currentValue = select.value;
            select.innerHTML = '<option value="">Chọn sản phẩm...</option>' +
                this.products.map(product => 
                    `<option value="${product.product_id}" data-price="${product.price || 0}">${product.name} - ${product.category_name || ''}</option>`
                ).join('');
            if (currentValue) select.value = currentValue;
        });
    }

    // Load danh sách kệ
    async loadShelves() {
        try {
            const response = await fetch('api/import_handler.php?action=get_shelves');
            const data = await response.json();

            if (data.success) {
                this.shelves = data.data;
                this.populateShelfDropdowns();
            }
        } catch (error) {
            console.error('Error loading shelves:', error);
        }
    }

    // Điền dữ liệu vào dropdown kệ
    populateShelfDropdowns() {
        const shelfSelects = document.querySelectorAll('.shelf-select');
        shelfSelects.forEach(select => {
            const currentValue = select.value;
            select.innerHTML = '<option value="">Chọn kệ (tùy chọn)...</option>' +
                this.shelves.map(shelf => 
                    `<option value="${shelf.shelf_id}">${shelf.shelf_code} - ${shelf.location || ''}</option>`
                ).join('');
            if (currentValue) select.value = currentValue;
        });
    }

    // Tạo mã phiếu nhập tự động
    async generateImportCode() {
        try {
            const response = await fetch('api/import_handler.php?action=generate_import_code');
            const data = await response.json();

            if (data.success) {
                const importCodeInput = document.getElementById('importCode');
                if (importCodeInput) {
                    importCodeInput.value = data.data.import_code;
                }
            }
        } catch (error) {
            console.error('Error generating import code:', error);
        }
    }

    // Thêm dòng sản phẩm mới vào modal
    addProductRow() {
        const container = document.getElementById('productRowsContainer');
        if (!container) return;

        const rowIndex = container.children.length;
        const newRow = document.createElement('div');
        newRow.className = 'product-row border rounded p-3 mb-3';
        newRow.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="mb-0">Sản phẩm ${rowIndex + 1}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.parentElement.parentElement.remove(); nhapKhoManager.calculateTotal();">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Sản phẩm <span class="text-danger">*</span></label>
                    <select class="form-select product-select" name="products[${rowIndex}][product_id]" required>
                        <option value="">Chọn sản phẩm...</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Số lượng <span class="text-danger">*</span></label>
                    <input type="number" class="form-control quantity-input" name="products[${rowIndex}][quantity]" 
                           min="1" step="1" required onchange="nhapKhoManager.calculateTotal()">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Đơn giá (VNĐ) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control price-input" name="products[${rowIndex}][unit_price]" 
                           min="0" step="0.01" required onchange="nhapKhoManager.calculateTotal()">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Số lô</label>
                    <input type="text" class="form-control" name="products[${rowIndex}][lot_number]" 
                           placeholder="Nhập số lô...">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Hạn sử dụng</label>
                    <input type="date" class="form-control" name="products[${rowIndex}][expiry_date]">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Kệ lưu trữ</label>
                    <select class="form-select shelf-select" name="products[${rowIndex}][shelf_id]">
                        <option value="">Chọn kệ (tùy chọn)...</option>
                    </select>
                </div>
            </div>
        `;

        container.appendChild(newRow);
        
        // Populate dropdowns cho row mới
        this.populateProductDropdowns();
        this.populateShelfDropdowns();
    }

    // Tính tổng tiền của phiếu nhập
    calculateTotal() {
        const quantityInputs = document.querySelectorAll('.quantity-input');
        const priceInputs = document.querySelectorAll('.price-input');
        
        let totalItems = 0;
        let totalValue = 0;

        quantityInputs.forEach((quantityInput, index) => {
            const quantity = parseFloat(quantityInput.value) || 0;
            const price = parseFloat(priceInputs[index]?.value) || 0;
            
            totalItems += quantity;
            totalValue += quantity * price;
        });

        // Cập nhật hiển thị
        const totalItemsEl = document.getElementById('totalItems');
        if (totalItemsEl) totalItemsEl.textContent = totalItems;

        const totalValueEl = document.getElementById('totalValue');
        if (totalValueEl) {
            totalValueEl.textContent = new Intl.NumberFormat('vi-VN').format(totalValue) + ' VNĐ';
        }
    }

    // Reset modal về trạng thái ban đầu
    resetModal() {
        const form = document.getElementById('createImportForm');
        if (form) form.reset();

        const container = document.getElementById('productRowsContainer');
        if (container) container.innerHTML = '';

        // Reset tổng cộng
        const totalItemsEl = document.getElementById('totalItems');
        if (totalItemsEl) totalItemsEl.textContent = '0';

        const totalValueEl = document.getElementById('totalValue');
        if (totalValueEl) totalValueEl.textContent = '0 VNĐ';

        // Thêm một dòng sản phẩm mặc định
        this.addProductRow();
    }

    // Tạo phiếu nhập mới
    async createImport() {
        try {
            const form = document.getElementById('createImportForm');
            const formData = new FormData(form);
            formData.append('action', 'create_import');

            // Hiển thị loading
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang tạo...';
            submitBtn.disabled = true;

            const response = await fetch('api/import_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Tạo phiếu nhập thành công!');
                bootstrap.Modal.getInstance(document.getElementById('createImportModal')).hide();
                this.loadImports(); // Reload danh sách
            } else {
                this.showError('Lỗi tạo phiếu nhập: ' + data.message);
            }
        } catch (error) {
            console.error('Error creating import:', error);
            this.showError('Có lỗi xảy ra khi tạo phiếu nhập');
        } finally {
            // Reset button
            const submitBtn = document.querySelector('#createImportForm button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Tạo phiếu nhập';
                submitBtn.disabled = false;
            }
        }
    }

    // Xem chi tiết phiếu nhập
    async viewImportDetail(importId) {
        try {
            const response = await fetch(`api/import_handler.php?action=get_import_detail&id=${importId}`);
            const data = await response.json();

            if (data.success) {
                this.showImportDetailModal(data.data);
            } else {
                this.showError('Không thể tải chi tiết phiếu nhập: ' + data.message);
            }
        } catch (error) {
            console.error('Error loading import detail:', error);
            this.showError('Có lỗi xảy ra khi tải chi tiết');
        }
    }

    // Hiển thị modal chi tiết phiếu nhập
    showImportDetailModal(importData) {
        const modal = document.getElementById('importDetailModal');
        if (!modal) return;

        // Cập nhật thông tin cơ bản
        modal.querySelector('#detailImportCode').textContent = importData.import_code;
        modal.querySelector('#detailSupplierName').textContent = importData.supplier_name;
        modal.querySelector('#detailSupplierPhone').textContent = importData.supplier_phone || 'Không có';
        modal.querySelector('#detailSupplierAddress').textContent = importData.supplier_address || 'Không có';
        modal.querySelector('#detailImportDate').textContent = new Date(importData.import_date).toLocaleString('vi-VN');
        modal.querySelector('#detailCreatedBy').textContent = importData.created_by_name;
        modal.querySelector('#detailStatus').innerHTML = `<span class="badge badge-${this.getStatusColor(importData.status)}">${this.getStatusText(importData.status)}</span>`;
        modal.querySelector('#detailNotes').textContent = importData.notes || 'Không có ghi chú';

        // Cập nhật thông tin duyệt/từ chối
        const approvalInfo = modal.querySelector('#detailApprovalInfo');
        if (importData.status === 'approved' && importData.approved_by_name) {
            approvalInfo.innerHTML = `
                <div class="alert alert-success">
                    <strong>Đã duyệt bởi:</strong> ${importData.approved_by_name}<br>
                    <strong>Thời gian duyệt:</strong> ${new Date(importData.approved_at).toLocaleString('vi-VN')}
                </div>
            `;
        } else if (importData.status === 'rejected' && importData.rejected_by_name) {
            approvalInfo.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Từ chối bởi:</strong> ${importData.rejected_by_name}<br>
                    <strong>Thời gian từ chối:</strong> ${new Date(importData.rejected_at).toLocaleString('vi-VN')}<br>
                    <strong>Lý do:</strong> ${importData.rejection_reason || 'Không có lý do'}
                </div>
            `;
        } else {
            approvalInfo.innerHTML = '';
        }

        // Cập nhật danh sách sản phẩm
        const productsList = modal.querySelector('#detailProductsList');
        productsList.innerHTML = importData.products.map((product, index) => `
            <tr>
                <td>${index + 1}</td>
                <td>
                    <div>
                        <div class="fw-semibold">${product.product_name}</div>
                        <small class="text-muted">${product.category_name || ''}</small>
                    </div>
                </td>
                <td class="text-center">${product.quantity}</td>
                <td class="text-end">${new Intl.NumberFormat('vi-VN').format(product.unit_price)} VNĐ</td>
                <td class="text-end fw-bold">${new Intl.NumberFormat('vi-VN').format(product.quantity * product.unit_price)} VNĐ</td>
                <td class="text-center">${product.lot_number || '-'}</td>
                <td class="text-center">${product.expiry_date ? new Date(product.expiry_date).toLocaleDateString('vi-VN') : '-'}</td>
                <td class="text-center">${product.shelf_code || '-'}</td>
            </tr>
        `).join('');

        // Cập nhật tổng cộng
        const totalItems = importData.products.reduce((sum, product) => sum + parseInt(product.quantity), 0);
        const totalValue = importData.products.reduce((sum, product) => sum + (product.quantity * product.unit_price), 0);
        
        modal.querySelector('#detailTotalItems').textContent = totalItems;
        modal.querySelector('#detailTotalValue').textContent = new Intl.NumberFormat('vi-VN').format(totalValue) + ' VNĐ';

        // Hiển thị modal
        new bootstrap.Modal(modal).show();
    }

    // Duyệt phiếu nhập
    async approveImport(importId) {
        const confirmed = await this.showConfirm('Bạn có chắc chắn muốn duyệt phiếu nhập này?', 'Duyệt phiếu nhập');
        if (!confirmed) return;

        try {
            const formData = new FormData();
            formData.append('action', 'approve_import');
            formData.append('import_id', importId);

            const response = await fetch('api/import_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Đã duyệt phiếu nhập thành công!');
                this.loadImports(); // Reload danh sách
            } else {
                this.showError('Lỗi duyệt phiếu nhập: ' + data.message);
            }
        } catch (error) {
            console.error('Error approving import:', error);
            this.showError('Có lỗi xảy ra khi duyệt phiếu nhập');
        }
    }

    // Từ chối phiếu nhập
    async rejectImport(importId) {
        const reason = await this.showPrompt('Nhập lý do từ chối:', 'Từ chối phiếu nhập');
        if (!reason) return;

        try {
            const formData = new FormData();
            formData.append('action', 'reject_import');
            formData.append('import_id', importId);
            formData.append('rejection_reason', reason);

            const response = await fetch('api/import_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Đã từ chối phiếu nhập!');
                this.loadImports(); // Reload danh sách
            } else {
                this.showError('Lỗi từ chối phiếu nhập: ' + data.message);
            }
        } catch (error) {
            console.error('Error rejecting import:', error);
            this.showError('Có lỗi xảy ra khi từ chối phiếu nhập');
        }
    }

    // Xuất PDF cho một phiếu nhập
    async exportToPDF(importId) {
        try {
            window.open(`api/import_handler.php?action=export_pdf&import_id=${importId}`, '_blank');
        } catch (error) {
            console.error('Error exporting PDF:', error);
            this.showError('Có lỗi xảy ra khi xuất PDF');
        }
    }

    // Xuất Excel cho danh sách phiếu nhập
    async exportToExcel() {
        try {
            const searchValue = document.getElementById('searchInput')?.value || '';
            const statusFilter = document.getElementById('statusFilter')?.value || '';
            const supplierFilter = document.getElementById('supplierFilter')?.value || '';
            
            const params = new URLSearchParams({
                action: 'export_excel',
                search: searchValue,
                status: statusFilter,
                supplier_id: supplierFilter
            });

            window.open(`api/import_handler.php?${params}`, '_blank');
        } catch (error) {
            console.error('Error exporting Excel:', error);
            this.showError('Có lỗi xảy ra khi xuất Excel');
        }
    }

    // Utility functions
    getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'approved': 'success',
            'rejected': 'danger'
        };
        return colors[status] || 'secondary';
    }

    getStatusText(status) {
        const texts = {
            'pending': 'Chờ duyệt',
            'approved': 'Đã duyệt',
            'rejected': 'Từ chối'
        };
        return texts[status] || 'Không xác định';
    }

    showLoading(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            const loadingEl = container.querySelector('.loading-spinner');
            if (loadingEl) loadingEl.style.display = 'block';
        }
    }

    hideLoading(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            const loadingEl = container.querySelector('.loading-spinner');
            if (loadingEl) loadingEl.style.display = 'none';
        }
    }

    showSuccess(message) {
        // Sử dụng toast hoặc alert tùy vào framework UI
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Thành công',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            alert(message);
        }
    }

    showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: message
            });
        } else {
            alert('Lỗi: ' + message);
        }
    }

    async showConfirm(message, title = 'Xác nhận') {
        if (typeof Swal !== 'undefined') {
            const result = await Swal.fire({
                icon: 'question',
                title: title,
                text: message,
                showCancelButton: true,
                confirmButtonText: 'Xác nhận',
                cancelButtonText: 'Hủy'
            });
            return result.isConfirmed;
        } else {
            return confirm(message);
        }
    }

    async showPrompt(message, title = 'Nhập dữ liệu') {
        if (typeof Swal !== 'undefined') {
            const result = await Swal.fire({
                icon: 'question',
                title: title,
                text: message,
                input: 'textarea',
                inputPlaceholder: 'Nhập lý do...',
                showCancelButton: true,
                confirmButtonText: 'Xác nhận',
                cancelButtonText: 'Hủy',
                inputValidator: (value) => {
                    if (!value || value.trim() === '') {
                        return 'Vui lòng nhập lý do!';
                    }
                }
            });
            return result.isConfirmed ? result.value : null;
        } else {
            return prompt(message);
        }
    }
}

// Khởi tạo đối tượng quản lý nhập kho khi DOM đã load
document.addEventListener('DOMContentLoaded', function() {
    window.nhapKhoManager = new NhapKhoManager();
});

// Export class cho việc sử dụng ở nơi khác
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NhapKhoManager;
} 