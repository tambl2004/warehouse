<!-- Hệ thống IoT & RFID Management -->
<div class="function-container">
    <div class="rfid-management">
        <!-- Header -->
        <h1 class="page-title">Hệ thống IoT & RFID</h1>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="barcode-stats-card bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 id="totalRFIDTags">0</h3>
                            <small>Tổng thẻ RFID</small>
                        </div>
                        <i class="fas fa-tags fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="barcode-stats-card bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 id="activeDevices">0</h3>
                            <small>Thiết bị hoạt động</small>
                        </div>
                        <i class="fas fa-satellite-dish fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="barcode-stats-card bg-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 id="todayScans">0</h3>
                            <small>Quét hôm nay</small>
                        </div>
                        <i class="fas fa-search fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="barcode-stats-card bg-warning text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 id="rfidAlerts">0</h3>
                            <small>Cảnh báo RFID</small>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Tabs -->
        <div class="container">
            <ul class="nav nav-tabs" id="rfidTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab">
                        <i class="fas fa-tachometer-alt"></i> Dashboard RFID
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tags-tab" data-bs-toggle="tab" data-bs-target="#tags" type="button" role="tab">
                        <i class="fas fa-tags"></i> Quản lý Thẻ RFID
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="devices-tab" data-bs-toggle="tab" data-bs-target="#devices" type="button" role="tab">
                        <i class="fas fa-satellite-dish"></i> Thiết bị RFID
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="scan-logs-tab" data-bs-toggle="tab" data-bs-target="#scan-logs" type="button" role="tab">
                        <i class="fas fa-history"></i> Lịch sử Quét
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="alerts-tab" data-bs-toggle="tab" data-bs-target="#alerts" type="button" role="tab">
                        <i class="fas fa-bell"></i> Cảnh báo
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="rfidTabsContent">
                <!-- Dashboard Tab -->
                <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                    <div class="row mt-4">
                        <!-- Real-time Activity -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-line"></i> Hoạt động RFID Real-time</h5>
                                </div>
                                <div class="card-body rfid-chart-container">
                                    <canvas id="realTimeChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <!-- Device Status -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-wifi"></i> Trạng thái Thiết bị</h5>
                                </div>
                                <div class="card-body">
                                    <div id="deviceStatusList">
                                        <div class="rfid-loading">
                                            <div class="spinner-border" role="status"></div>
                                            <div class="rfid-loading-text">Đang tải...</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-clock"></i> Hoạt động Gần đây</h5>
                                </div>
                                <div class="card-body">
                                    <div id="recentActivity">
                                        <div class="rfid-loading">
                                            <div class="spinner-border" role="status"></div>
                                            <div class="rfid-loading-text">Đang tải...</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RFID Tags Management Tab -->
                <div class="tab-pane fade" id="tags" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                        <h4>Quản lý Thẻ RFID</h4>
                        <button class="btn btn-primary" onclick="showAddTagModal()">
                            <i class="fas fa-plus"></i> Thêm Thẻ RFID
                        </button>
                    </div>

                    <!-- Search and Filter -->
                    <div class="rfid-search-section">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="searchTag" placeholder="Tìm kiếm thẻ RFID...">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="filterProduct">
                                    <option value="">Tất cả sản phẩm</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="filterShelf">
                                    <option value="">Tất cả kệ</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-secondary w-100" onclick="refreshTagsTable()">
                                    <i class="fas fa-sync"></i> Làm mới
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- RFID Tags Table -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover rfid-tags-table" id="rfidTagsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Giá trị RFID</th>
                                            <th>Sản phẩm</th>
                                            <th>Số lô</th>
                                            <th>Ngày hết hạn</th>
                                            <th>Vị trí kệ</th>
                                            <th>Ngày tạo</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="8" class="text-center">Đang tải dữ liệu...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RFID Devices Tab -->
                <div class="tab-pane fade" id="devices" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                        <h4>Thiết bị RFID</h4>
                        <button class="btn btn-primary" onclick="showAddDeviceModal()">
                            <i class="fas fa-plus"></i> Thêm Thiết bị
                        </button>
                    </div>

                    <!-- Devices Grid -->
                    <div class="row" id="devicesGrid">
                        <div class="col-12">
                            <div class="rfid-loading">
                                <div class="spinner-border" role="status"></div>
                                <div class="rfid-loading-text">Đang tải thiết bị...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scan Logs Tab -->
                <div class="tab-pane fade" id="scan-logs" role="tabpanel">
                    <h4 class="mt-4 mb-3">Lịch sử Quét RFID</h4>
                    
                    <!-- Date Filter -->
                    <div class="rfid-search-section">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Từ ngày</label>
                                <input type="date" class="form-control" id="fromDate">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Đến ngày</label>
                                <input type="date" class="form-control" id="toDate">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kết quả</label>
                                <select class="form-select" id="filterResult">
                                    <option value="">Tất cả kết quả</option>
                                    <option value="success">Thành công</option>
                                    <option value="failed">Thất bại</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-secondary w-100 d-block" onclick="rfidManager.loadScanLogs()">
                                    <i class="fas fa-search"></i> Lọc
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Scan Logs Table -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover scan-logs-table" id="scanLogsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Thời gian</th>
                                            <th>Thẻ RFID</th>
                                            <th>Sản phẩm</th>
                                            <th>Người dùng</th>
                                            <th>Kết quả</th>
                                            <th>Mô tả</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="6" class="text-center">Đang tải dữ liệu...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts Tab -->
                <div class="tab-pane fade" id="alerts" role="tabpanel">
                    <h4 class="mt-4 mb-3">Cảnh báo RFID</h4>
                    
                    <div class="row" id="alertsContainer">
                        <div class="col-12">
                            <div class="rfid-loading">
                                <div class="spinner-border" role="status"></div>
                                <div class="rfid-loading-text">Đang tải cảnh báo...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Add/Edit RFID Tag Modal -->
    <div class="modal fade rfid-modal" id="tagModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tagModalTitle">Thêm Thẻ RFID</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="tagForm">
                        <input type="hidden" id="tagId" name="tagId">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rfidValue">Giá trị RFID *</label>
                                    <input type="text" class="form-control" id="rfidValue" name="rfidValue" required placeholder="Nhập giá trị RFID">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="productId">Sản phẩm *</label>
                                    <select class="form-select" id="productId" name="productId" required>
                                        <option value="">Chọn sản phẩm</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lotNumber">Số lô</label>
                                    <input type="text" class="form-control" id="lotNumber" name="lotNumber" placeholder="Nhập số lô">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expiryDate">Ngày hết hạn</label>
                                    <input type="date" class="form-control" id="expiryDate" name="expiryDate">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="shelfId">Vị trí kệ</label>
                            <select class="form-select" id="shelfId" name="shelfId">
                                <option value="">Chọn kệ</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveTag()">Lưu</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Device Modal -->
    <div class="modal fade rfid-modal" id="deviceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deviceModalTitle">Thêm Thiết bị RFID</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="deviceForm">
                        <input type="hidden" id="deviceId" name="deviceId">
                        <div class="form-group">
                            <label for="deviceName">Tên thiết bị *</label>
                            <input type="text" class="form-control" id="deviceName" name="deviceName" required placeholder="Nhập tên thiết bị">
                        </div>
                        <div class="form-group">
                            <label for="areaId">Khu vực *</label>
                            <select class="form-select" id="areaId" name="areaId" required>
                                <option value="">Chọn khu vực</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="batteryLevel">Mức pin (%)</label>
                            <input type="number" class="form-control" id="batteryLevel" name="batteryLevel" min="0" max="100" placeholder="0-100">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveDevice()">Lưu</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Scripts -->
 

<script>
    async function saveTag() {
        const form = document.getElementById('tagForm');
        const formData = new FormData(form);
        const tagId = document.getElementById('tagId').value;
        
        formData.append('action', tagId ? 'update_tag' : 'create_tag');
        
        try {
            const response = await fetch('api/rfid_handler.php', { 
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Lưu thẻ RFID thành công!', 'success');
                const tagModalElement = document.getElementById('tagModal');
                if (tagModalElement && typeof bootstrap !== 'undefined') {
                    const tagModalInstance = bootstrap.Modal.getInstance(tagModalElement) || new bootstrap.Modal(tagModalElement);
                    tagModalInstance.hide();
                }

                if (typeof rfidManager !== 'undefined' && rfidManager) {
                    rfidManager.loadRFIDTags();
                    rfidManager.loadStatistics();
                }
            } else {
                showToast(data.message || 'Có lỗi xảy ra khi lưu thẻ!', 'danger');
            }
        } catch (error) {
            console.error('Error saving tag:', error);
            showToast('Lỗi hệ thống khi lưu thẻ!', 'danger');
        }
    }

    async function saveDevice() {
        const form = document.getElementById('deviceForm');
        const formData = new FormData(form);
        const deviceId = document.getElementById('deviceId').value;
        
        formData.append('action', deviceId ? 'update_device' : 'create_device');
        
        try {
            const response = await fetch('api/rfid_handler.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Lưu thiết bị thành công!', 'success');
                const deviceModalElement = document.getElementById('deviceModal');
                if (deviceModalElement && typeof bootstrap !== 'undefined') {
                    const deviceModalInstance = bootstrap.Modal.getInstance(deviceModalElement) || new bootstrap.Modal(deviceModalElement);
                    deviceModalInstance.hide();
                }
                if (typeof rfidManager !== 'undefined' && rfidManager) {
                    rfidManager.loadDevices();
                    rfidManager.loadStatistics();
                }
            } else {
                showToast(data.message || 'Có lỗi xảy ra khi lưu thiết bị!', 'danger');
            }
        } catch (error) {
            console.error('Error saving device:', error);
            showToast('Lỗi hệ thống khi lưu thiết bị!', 'danger');
        }
    }

    // Load dropdown data for modals
    async function loadProductsForModal() { 
        try {
           
            const response = await fetch('api/product_handler.php?action=get_all_products_for_rfid'); 
            const data = await response.json();
            
            if (data.success) {
                const productSelect = document.getElementById('productId'); 
                const filterProductSelect = document.getElementById('filterProduct'); 
                
                const optionsHtml = data.data.map(product => `<option value="${product.product_id}">${product.product_name} (SKU: ${product.sku})</option>`).join('');

                if (productSelect) {
                    productSelect.innerHTML = '<option value="">Chọn sản phẩm</option>' + optionsHtml;
                }
                if (filterProductSelect) {
                    filterProductSelect.innerHTML = '<option value="">Tất cả sản phẩm</option>' + optionsHtml;
                }
            } else {
                console.warn('Could not load products for dropdown:', data.message);
            }
        } catch (error) {
            console.error('Error loading products for modal:', error);
        }
    }

    async function loadShelvesForModal() { 
        try {
            const response = await fetch('api/rfid_handler.php?action=get_shelves'); 
            const data = await response.json();
            
            if (data.success) {
                const shelfSelect = document.getElementById('shelfId'); 
                const filterShelfSelect = document.getElementById('filterShelf'); 

                const optionsHtml = data.data.map(shelf => `<option value="${shelf.shelf_id}">${shelf.shelf_code} (${shelf.location_description || 'N/A'})</option>`).join('');

                if (shelfSelect) {
                    shelfSelect.innerHTML = '<option value="">Chọn kệ</option>' + optionsHtml;
                }
                if (filterShelfSelect) {
                    filterShelfSelect.innerHTML = '<option value="">Tất cả kệ</option>' + optionsHtml;
                }
            } else {
                console.warn('Could not load shelves for dropdown:', data.message);
            }
        } catch (error) {
            console.error('Error loading shelves for modal:', error);
        }
    }

    async function loadAreasForModal() {
        try {
            const response = await fetch('api/rfid_handler.php?action=get_areas'); 
            const data = await response.json();
            
            if (data.success) {
                const areaSelect = document.getElementById('areaId'); 
                if (areaSelect) {
                    areaSelect.innerHTML = '<option value="">Chọn khu vực</option>';
                    data.data.forEach(area => {
                        areaSelect.innerHTML += `<option value="${area.area_id}">${area.area_name}</option>`;
                    });
                }
            } else {
                console.warn('Could not load areas for dropdown:', data.message);
            }
        } catch (error) {
            console.error('Error loading areas for modal:', error);
        }
    }

    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toastPlacement');
        if (!toastContainer) { 
            const fallbackContainer = document.createElement('div');
            fallbackContainer.className = 'position-fixed top-0 end-0 p-3';
            fallbackContainer.style.zIndex = "9999";
            document.body.appendChild(fallbackContainer);
            toastPlacement = fallbackContainer;
        }
        
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type === 'danger' ? 'danger' : type} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        
        let iconClass = 'fa-info-circle';
        if (type === 'success') iconClass = 'fa-check-circle';
        else if (type === 'danger') iconClass = 'fa-times-circle';
        else if (type === 'warning') iconClass = 'fa-exclamation-triangle';

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${iconClass} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        toastPlacement.appendChild(toastEl);
        
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

 
    document.addEventListener('DOMContentLoaded', function() {

        loadProductsForModal();
        loadShelvesForModal();
        loadAreasForModal();
    });

  
    function showAddTagModal() {
        const modalElement = document.getElementById('tagModal');
        if (!modalElement) return;

        const form = document.getElementById('tagForm');
        if (form) form.reset();
        
        const tagIdField = document.getElementById('tagId');
        if (tagIdField) tagIdField.value = '';
        
        const titleElement = document.getElementById('tagModalTitle');
        if (titleElement) titleElement.textContent = 'Thêm Thẻ RFID Mới';
        
        if (typeof bootstrap !== 'undefined') {
            const bsModal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
            bsModal.show();
        }
    }

    function showAddDeviceModal() {
        const modalElement = document.getElementById('deviceModal');
        if (!modalElement) return;

        const form = document.getElementById('deviceForm');
        if (form) form.reset();
        
        const deviceIdField = document.getElementById('deviceId');
        if (deviceIdField) deviceIdField.value = '';
        
        const titleElement = document.getElementById('deviceModalTitle');
        if (titleElement) titleElement.textContent = 'Thêm Thiết bị RFID Mới';
        
        if (typeof bootstrap !== 'undefined') {
            const bsModal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
            bsModal.show();
        }
    }

    function refreshTagsTable() {
        if (typeof rfidManager !== 'undefined' && rfidManager && typeof rfidManager.loadRFIDTags === 'function') {
            rfidManager.loadRFIDTags();
        } else {
            console.error('RFID Manager chưa được khởi tạo hoặc không có hàm loadRFIDTags.');
            showToast('Không thể làm mới dữ liệu. Vui lòng tải lại trang.', 'warning');
        }
    }
</script>