// Quản lý Barcode JavaScript
class BarcodeManager {
    constructor() {
        this.currentPage = 1;
        this.currentLogsPage = 1;
        this.limit = 20;
        this.init();
    }

    init() {
        this.loadStatistics();
        this.loadBarcodes();
        this.loadScanLogs();
        this.bindEvents();
        
        // Auto refresh statistics mỗi 30 giây
        setInterval(() => this.loadStatistics(), 30000);
    }

    bindEvents() {
        // Form quét barcode
        document.getElementById('scanForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.scanBarcode();
        });

        // Form tạo mã vạch đơn lẻ
        document.getElementById('generateForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.addBarcode();
        });

        // Form tạo hàng loạt
        document.getElementById('bulkGenerateForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.bulkGenerateBarcodes();
        });

        // Form modal barcode
        document.getElementById('barcodeForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveBarcodeModal();
        });

        // Tự động quét khi nhập xong mã vạch
        document.getElementById('scanBarcodeValue').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.scanBarcode();
            }
        });

        // Xem trước mã vạch khi nhập
        document.getElementById('generateBarcodeValue').addEventListener('input', 
            this.debounce(() => this.previewBarcode(), 500)
        );

        // Tab events
        document.querySelectorAll('#barcodeTab button').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                const target = e.target.getAttribute('data-bs-target');
                if (target === '#manage') {
                    this.loadBarcodes();
                } else if (target === '#logs') {
                    this.loadScanLogs();
                }
            });
        });
    }

    // Tải thống kê
    async loadStatistics() {
        try {
            const [barcodesResponse, logsResponse] = await Promise.all([
                fetch('api/barcode_handler.php?action=get_barcodes&limit=1'),
                fetch('api/barcode_handler.php?action=get_scan_logs&limit=1')
            ]);

            const barcodesData = await barcodesResponse.json();
            const logsData = await logsResponse.json();

            if (barcodesData.success) {
                document.getElementById('totalBarcodes').textContent = 
                    barcodesData.pagination.total_records.toLocaleString();
            }

            if (logsData.success) {
                // Tính toán thống kê từ logs (simplified)
                const logs = logsData.data;
                const today = new Date().toISOString().split('T')[0];
                
                const todayLogs = logs.filter(log => 
                    log.scan_time && log.scan_time.startsWith(today)
                );
                
                const successLogs = logs.filter(log => log.scan_result === 'success');
                const failedLogs = logs.filter(log => log.scan_result === 'failed');

                document.getElementById('todayScans').textContent = todayLogs.length;
                document.getElementById('scanSuccess').textContent = successLogs.length;
                document.getElementById('scanFailed').textContent = failedLogs.length;
            }
        } catch (error) {
            console.error('Lỗi tải thống kê:', error);
        }
    }

    // Quét mã vạch
    async scanBarcode() {
        const barcodeValue = document.getElementById('scanBarcodeValue').value.trim();
        const description = document.getElementById('scanDescription').value.trim();

        if (!barcodeValue) {
            this.showAlert('Vui lòng nhập mã vạch', 'warning');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'scan_barcode');
            formData.append('barcode_value', barcodeValue);
            formData.append('description', description);

            const response = await fetch('api/barcode_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.displayScanResult(data.data, true);
                this.showAlert('Quét mã vạch thành công!', 'success');
                
                // Refresh statistics
                this.loadStatistics();
                
                // Clear form
                this.clearScanForm();
            } else {
                this.displayScanResult(null, false, data.message);
                
                // Hiển thị option tạo sản phẩm mới
                if (data.suggest_create) {
                    this.showCreateProductOption(data.barcode_value);
                }
                
                this.showAlert(data.message, 'error');
            }
        } catch (error) {
            console.error('Lỗi quét mã vạch:', error);
            this.showAlert('Có lỗi xảy ra khi quét mã vạch', 'error');
        }
    }

    // Hiển thị kết quả quét
    displayScanResult(data, success, message = '') {
        const resultDiv = document.getElementById('scanResult');
        
        if (success && data) {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="alert-heading">Quét thành công!</h6>
                            <p class="mb-1"><strong>Mã vạch:</strong> ${data.barcode_value}</p>
                            <p class="mb-1"><strong>Sản phẩm:</strong> ${data.product_name || 'N/A'}</p>
                            <p class="mb-1"><strong>SKU:</strong> ${data.sku || 'N/A'}</p>
                            <p class="mb-1"><strong>Tồn kho:</strong> ${data.stock_quantity || 0} ${data.unit || ''}</p>
                            ${data.lot_number ? `<p class="mb-1"><strong>Lô hàng:</strong> ${data.lot_number}</p>` : ''}
                            ${data.expiry_date ? `<p class="mb-0"><strong>Hạn sử dụng:</strong> ${this.formatDate(data.expiry_date)}</p>` : ''}
                        </div>
                        <div class="col-md-4 text-center">
                            <img src="api/barcode_handler.php?action=generate_barcode&barcode=${data.barcode_value}&type=png&width=2&height=50" 
                                 alt="Barcode" class="img-fluid" style="max-height: 60px;">
                        </div>
                    </div>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <h6 class="alert-heading">Quét thất bại!</h6>
                    <p class="mb-0">${message || 'Mã vạch không tồn tại trong hệ thống'}</p>
                </div>
            `;
        }
    }

    // Hiển thị option tạo sản phẩm mới
    showCreateProductOption(barcodeValue) {
        const resultDiv = document.getElementById('scanResult');
        const currentContent = resultDiv.innerHTML;
        
        resultDiv.innerHTML = currentContent + `
            <div class="alert alert-info mt-3">
                <h6 class="alert-heading">Tạo sản phẩm mới?</h6>
                <p class="mb-2">Mã vạch "${barcodeValue}" chưa có trong hệ thống.</p>
                <button class="btn btn-success btn-sm" onclick="barcodeManager.showCreateProductModal('${barcodeValue}')">
                    <i class="fas fa-plus me-1"></i>Tạo sản phẩm mới
                </button>
            </div>
        `;
    }

    // Hiển thị modal tạo sản phẩm
    showCreateProductModal(barcodeValue) {
        document.getElementById('newProductBarcode').value = barcodeValue;
        
        // TODO: Tích hợp với form tạo sản phẩm từ module sản phẩm
        const createForm = document.getElementById('createProductForm');
        createForm.innerHTML = `
            <div class="mb-3">
                <label for="newProductName" class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="newProductName" required>
            </div>
            <div class="mb-3">
                <label for="newProductSku" class="form-label">SKU <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="newProductSku" required>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="newProductPrice" class="form-label">Giá <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="newProductPrice" step="0.01" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="newProductCategory" class="form-label">Danh mục</label>
                        <select class="form-select" id="newProductCategory">
                            <option value="">Chọn danh mục</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label for="newProductDescription" class="form-label">Mô tả</label>
                <textarea class="form-control" id="newProductDescription" rows="3"></textarea>
            </div>
        `;
        
        new bootstrap.Modal(document.getElementById('createProductModal')).show();
    }

    // Tạo sản phẩm mới (placeholder)
    async createNewProduct() {
        // TODO: Tích hợp với API tạo sản phẩm
        this.showAlert('Chức năng tạo sản phẩm mới đang được phát triển', 'info');
        bootstrap.Modal.getInstance(document.getElementById('createProductModal')).hide();
    }

    // Tải danh sách mã vạch
    async loadBarcodes(page = 1) {
        this.currentPage = page;
        
        try {
            const search = document.getElementById('searchBarcodes')?.value || '';
            const productFilter = document.getElementById('productFilter')?.value || '';
            
            const params = new URLSearchParams({
                action: 'get_barcodes',
                page: page,
                limit: this.limit,
                search: search,
                product_filter: productFilter
            });

            const response = await fetch(`api/barcode_handler.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.renderBarcodesTable(data.data);
                this.renderPagination(data.pagination, 'barcodePagination', 'loadBarcodes');
            } else {
                this.showAlert(data.message, 'error');
            }
        } catch (error) {
            console.error('Lỗi tải danh sách mã vạch:', error);
            this.showAlert('Có lỗi xảy ra khi tải danh sách mã vạch', 'error');
        }
    }

    // Render bảng mã vạch
    renderBarcodesTable(barcodes) {
        const tbody = document.querySelector('#barcodesTable tbody');
        
        if (barcodes.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Không có mã vạch nào</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = barcodes.map(barcode => `
            <tr>
                <td>${barcode.barcode_id}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <code class="me-2">${barcode.barcode_value}</code>
                        <button class="btn btn-sm btn-outline-secondary" 
                                onclick="barcodeManager.showBarcodeImage('${barcode.barcode_value}')"
                                title="Xem mã vạch">
                            <i class="fas fa-qrcode"></i>
                        </button>
                    </div>
                </td>
                <td>${barcode.product_name || '<span class="text-muted">N/A</span>'}</td>
                <td><span class="badge bg-secondary">${barcode.sku || 'N/A'}</span></td>
                <td>${barcode.lot_number || '<span class="text-muted">N/A</span>'}</td>
                <td>${barcode.expiry_date ? this.formatDate(barcode.expiry_date) : '<span class="text-muted">N/A</span>'}</td>
                <td>${this.formatDateTime(barcode.created_at)}</td>
                <td>
                    <div class="btn-group-sm">
                        <button class="btn btn-outline-primary btn-sm me-1" 
                                onclick="barcodeManager.editBarcode(${barcode.barcode_id})"
                                title="Sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm" 
                                onclick="barcodeManager.deleteBarcode(${barcode.barcode_id}, '${barcode.barcode_value}')"
                                title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    // Hiển thị hình ảnh mã vạch
    showBarcodeImage(barcodeValue) {
        const modal = new bootstrap.Modal(document.createElement('div'));
        const modalElement = document.createElement('div');
        modalElement.innerHTML = `
            <div class="modal fade" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Mã vạch: ${barcodeValue}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="api/barcode_handler.php?action=generate_barcode&barcode=${barcodeValue}&type=png&width=3&height=80" 
                                 alt="Barcode" class="img-fluid mb-3">
                            <div>
                                <button class="btn btn-outline-primary me-2" onclick="window.open('api/barcode_handler.php?action=generate_barcode&barcode=${barcodeValue}&type=png&width=3&height=80', '_blank')">
                                    <i class="fas fa-download me-1"></i>Tải PNG
                                </button>
                                <button class="btn btn-outline-secondary" onclick="window.print()">
                                    <i class="fas fa-print me-1"></i>In
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modalElement);
        new bootstrap.Modal(modalElement.querySelector('.modal')).show();
        
        // Xóa modal sau khi đóng
        modalElement.querySelector('.modal').addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modalElement);
        });
    }

    // Thêm mã vạch
    async addBarcode() {
        const barcodeValue = document.getElementById('generateBarcodeValue').value.trim();
        const productId = document.getElementById('generateProductId').value;
        const lotNumber = document.getElementById('generateLotNumber').value.trim();
        const expiryDate = document.getElementById('generateExpiryDate').value;

        if (!barcodeValue || !productId) {
            this.showAlert('Vui lòng nhập đầy đủ thông tin bắt buộc', 'warning');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'add_barcode');
            formData.append('barcode_value', barcodeValue);
            formData.append('product_id', productId);
            formData.append('lot_number', lotNumber);
            formData.append('expiry_date', expiryDate);

            const response = await fetch('api/barcode_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('Thêm mã vạch thành công!', 'success');
                document.getElementById('generateForm').reset();
                document.getElementById('barcodePreview').style.display = 'none';
                this.loadBarcodes();
                this.loadStatistics();
            } else {
                this.showAlert(data.message, 'error');
            }
        } catch (error) {
            console.error('Lỗi thêm mã vạch:', error);
            this.showAlert('Có lỗi xảy ra khi thêm mã vạch', 'error');
        }
    }

    // Tạo mã vạch hàng loạt
    async bulkGenerateBarcodes() {
        const productId = document.getElementById('bulkProductId').value;
        const quantity = document.getElementById('bulkQuantity').value;
        const lotNumber = document.getElementById('bulkLotNumber').value.trim();
        const expiryDate = document.getElementById('bulkExpiryDate').value;

        if (!productId || !quantity || quantity < 1) {
            this.showAlert('Vui lòng nhập đầy đủ thông tin hợp lệ', 'warning');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'bulk_generate');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('lot_number', lotNumber);
            formData.append('expiry_date', expiryDate);

            const response = await fetch('api/barcode_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert(`Tạo thành công ${quantity} mã vạch!`, 'success');
                document.getElementById('bulkGenerateForm').reset();
                this.loadBarcodes();
                this.loadStatistics();
                
                // Hiển thị danh sách mã vạch vừa tạo
                this.showGeneratedBarcodes(data.data);
            } else {
                this.showAlert(data.message, 'error');
            }
        } catch (error) {
            console.error('Lỗi tạo mã vạch hàng loạt:', error);
            this.showAlert('Có lỗi xảy ra khi tạo mã vạch hàng loạt', 'error');
        }
    }

    // Hiển thị danh sách mã vạch vừa tạo
    showGeneratedBarcodes(barcodes) {
        const modalElement = document.createElement('div');
        modalElement.innerHTML = `
            <div class="modal fade" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Mã vạch vừa tạo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                ${barcodes.map(barcode => `
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <div class="card-body text-center">
                                                <img src="api/barcode_handler.php?action=generate_barcode&barcode=${barcode.barcode_value}&type=png&width=2&height=50" 
                                                     alt="Barcode" class="img-fluid mb-2">
                                                <p class="small mb-0">${barcode.barcode_value}</p>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="button" class="btn btn-primary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i>In tất cả
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modalElement);
        const modal = new bootstrap.Modal(modalElement.querySelector('.modal'));
        modal.show();
        
        // Xóa modal sau khi đóng
        modalElement.querySelector('.modal').addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modalElement);
        });
    }

    // Xem trước mã vạch
    async previewBarcode() {
        const barcodeValue = document.getElementById('generateBarcodeValue').value.trim();
        const previewDiv = document.getElementById('barcodePreview');
        const imageDiv = document.getElementById('barcodeImage');

        if (!barcodeValue) {
            previewDiv.style.display = 'none';
            return;
        }

        try {
            imageDiv.innerHTML = `
                <img src="api/barcode_handler.php?action=generate_barcode&barcode=${barcodeValue}&type=png&width=3&height=80" 
                     alt="Barcode Preview" class="img-fluid">
                <p class="mt-2 mb-0">${barcodeValue}</p>
            `;
            previewDiv.style.display = 'block';
        } catch (error) {
            console.error('Lỗi xem trước mã vạch:', error);
        }
    }

    // Tạo mã vạch ngẫu nhiên
    generateRandomBarcode() {
        const timestamp = Date.now();
        const random = Math.floor(Math.random() * 1000);
        const barcodeValue = `BC${timestamp}${random}`;
        
        document.getElementById('generateBarcodeValue').value = barcodeValue;
        this.previewBarcode();
    }

    // Hiển thị modal thêm mã vạch
    showAddBarcodeModal() {
        document.getElementById('barcodeModalTitle').textContent = 'Thêm Mã Vạch';
        document.getElementById('barcodeForm').reset();
        document.getElementById('barcodeId').value = '';
        
        new bootstrap.Modal(document.getElementById('barcodeModal')).show();
    }

    // Sửa mã vạch
    async editBarcode(barcodeId) {
        try {
            const response = await fetch(`api/barcode_handler.php?action=get_barcodes&page=1&limit=1000`);
            const data = await response.json();
            
            if (data.success) {
                const barcode = data.data.find(b => b.barcode_id == barcodeId);
                if (barcode) {
                    document.getElementById('barcodeModalTitle').textContent = 'Sửa Mã Vạch';
                    document.getElementById('barcodeId').value = barcode.barcode_id;
                    document.getElementById('barcodeValue').value = barcode.barcode_value;
                    document.getElementById('productId').value = barcode.product_id;
                    document.getElementById('lotNumber').value = barcode.lot_number || '';
                    document.getElementById('expiryDate').value = barcode.expiry_date || '';
                    
                    new bootstrap.Modal(document.getElementById('barcodeModal')).show();
                }
            }
        } catch (error) {
            console.error('Lỗi tải thông tin mã vạch:', error);
            this.showAlert('Có lỗi xảy ra khi tải thông tin mã vạch', 'error');
        }
    }

    // Lưu mã vạch từ modal
    async saveBarcodeModal() {
        const barcodeId = document.getElementById('barcodeId').value;
        const barcodeValue = document.getElementById('barcodeValue').value.trim();
        const productId = document.getElementById('productId').value;
        const lotNumber = document.getElementById('lotNumber').value.trim();
        const expiryDate = document.getElementById('expiryDate').value;

        if (!barcodeValue || !productId) {
            this.showAlert('Vui lòng nhập đầy đủ thông tin bắt buộc', 'warning');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', barcodeId ? 'edit_barcode' : 'add_barcode');
            if (barcodeId) formData.append('barcode_id', barcodeId);
            formData.append('barcode_value', barcodeValue);
            formData.append('product_id', productId);
            formData.append('lot_number', lotNumber);
            formData.append('expiry_date', expiryDate);

            const response = await fetch('api/barcode_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert(barcodeId ? 'Cập nhật mã vạch thành công!' : 'Thêm mã vạch thành công!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('barcodeModal')).hide();
                this.loadBarcodes();
                this.loadStatistics();
            } else {
                this.showAlert(data.message, 'error');
            }
        } catch (error) {
            console.error('Lỗi lưu mã vạch:', error);
            this.showAlert('Có lỗi xảy ra khi lưu mã vạch', 'error');
        }
    }

    // Xóa mã vạch
    async deleteBarcode(barcodeId, barcodeValue) {
        if (!confirm(`Bạn có chắc chắn muốn xóa mã vạch "${barcodeValue}"?`)) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'delete_barcode');
            formData.append('barcode_id', barcodeId);

            const response = await fetch('api/barcode_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('Xóa mã vạch thành công!', 'success');
                this.loadBarcodes();
                this.loadStatistics();
            } else {
                this.showAlert(data.message, 'error');
            }
        } catch (error) {
            console.error('Lỗi xóa mã vạch:', error);
            this.showAlert('Có lỗi xảy ra khi xóa mã vạch', 'error');
        }
    }

    // Tải lịch sử quét
    async loadScanLogs(page = 1) {
        this.currentLogsPage = page;
        
        try {
            const barcodeFilter = document.getElementById('searchLogs')?.value || '';
            const userFilter = document.getElementById('userFilter')?.value || '';
            const resultFilter = document.getElementById('resultFilter')?.value || '';
            
            const params = new URLSearchParams({
                action: 'get_scan_logs',
                page: page,
                limit: this.limit,
                barcode_filter: barcodeFilter,
                user_filter: userFilter,
                result_filter: resultFilter
            });

            const response = await fetch(`api/barcode_handler.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.renderLogsTable(data.data);
                this.renderPagination(data.pagination, 'logsPagination', 'loadScanLogs');
            } else {
                this.showAlert(data.message, 'error');
            }
        } catch (error) {
            console.error('Lỗi tải lịch sử quét:', error);
            this.showAlert('Có lỗi xảy ra khi tải lịch sử quét', 'error');
        }
    }

    // Render bảng lịch sử
    renderLogsTable(logs) {
        const tbody = document.querySelector('#logsTable tbody');
        
        if (logs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có lịch sử quét</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = logs.map(log => `
            <tr>
                <td>${this.formatDateTime(log.scan_time)}</td>
                <td>
                    ${log.barcode_value ? 
                        `<code>${log.barcode_value}</code>` : 
                        '<span class="text-muted">N/A</span>'
                    }
                </td>
                <td>${log.product_name || '<span class="text-muted">N/A</span>'}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-placeholder me-2">${(log.full_name || log.username || 'U').charAt(0).toUpperCase()}</div>
                        <div>
                            <div class="fw-medium">${log.full_name || 'N/A'}</div>
                            <small class="text-muted">@${log.username || 'unknown'}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge ${log.scan_result === 'success' ? 'bg-success' : 'bg-danger'}">
                        ${log.scan_result === 'success' ? 'Thành công' : 'Thất bại'}
                    </span>
                </td>
                <td>${log.description || '<span class="text-muted">N/A</span>'}</td>
            </tr>
        `).join('');
    }

    // Tìm kiếm mã vạch
    searchBarcodes() {
        this.loadBarcodes(1);
    }

    // Tìm kiếm lịch sử
    searchLogs() {
        this.loadScanLogs(1);
    }

    // Render phân trang
    renderPagination(pagination, containerId, callbackFunction) {
        const container = document.getElementById(containerId);
        
        if (pagination.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let paginationHtml = '';
        
        // Previous button
        if (pagination.current_page > 1) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="barcodeManager.${callbackFunction}(${pagination.current_page - 1})">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            `;
        }

        // Page numbers
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="barcodeManager.${callbackFunction}(${i})">${i}</a>
                </li>
            `;
        }

        // Next button
        if (pagination.current_page < pagination.total_pages) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="barcodeManager.${callbackFunction}(${pagination.current_page + 1})">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;
        }

        container.innerHTML = paginationHtml;
    }

    // Xóa form quét
    clearScanForm() {
        document.getElementById('scanForm').reset();
        document.getElementById('scanBarcodeValue').focus();
    }

    // Tải xuống barcode
    downloadBarcode(format) {
        const barcodeValue = document.getElementById('generateBarcodeValue').value.trim();
        if (!barcodeValue) {
            this.showAlert('Vui lòng nhập mã vạch', 'warning');
            return;
        }

        const url = `api/barcode_handler.php?action=generate_barcode&barcode=${barcodeValue}&type=${format}&width=3&height=80`;
        const link = document.createElement('a');
        link.href = url;
        link.download = `barcode_${barcodeValue}.${format}`;
        link.click();
    }

    // In barcode
    printBarcode() {
        const barcodeValue = document.getElementById('generateBarcodeValue').value.trim();
        if (!barcodeValue) {
            this.showAlert('Vui lòng nhập mã vạch', 'warning');
            return;
        }

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>In mã vạch - ${barcodeValue}</title>
                    <style>
                        body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
                        img { max-width: 100%; }
                        @media print { body { margin: 0; } }
                    </style>
                </head>
                <body>
                    <h2>Mã vạch: ${barcodeValue}</h2>
                    <img src="api/barcode_handler.php?action=generate_barcode&barcode=${barcodeValue}&type=png&width=3&height=80" alt="Barcode">
                    <script>window.print(); window.close();</script>
                </body>
            </html>
        `);
    }

    // Utility functions
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN');
    }

    formatDateTime(dateTimeString) {
        if (!dateTimeString) return 'N/A';
        const date = new Date(dateTimeString);
        return date.toLocaleString('vi-VN');
    }

    showAlert(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 
                          type === 'warning' ? 'alert-warning' : 'alert-info';
        
        const icon = type === 'success' ? 'fas fa-check-circle' :
                    type === 'error' ? 'fas fa-times-circle' :
                    type === 'warning' ? 'fas fa-exclamation-triangle' : 'fas fa-info-circle';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Tìm container để hiển thị alert
        let container = document.querySelector('.alert-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'alert-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        const alertElement = document.createElement('div');
        alertElement.innerHTML = alertHtml;
        container.appendChild(alertElement.firstElementChild);

        // Tự động xóa alert sau 5 giây
        setTimeout(() => {
            const alertDiv = container.querySelector('.alert');
            if (alertDiv) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

// Khởi tạo khi DOM loaded
document.addEventListener('DOMContentLoaded', function() {
    window.barcodeManager = new BarcodeManager();
}); 