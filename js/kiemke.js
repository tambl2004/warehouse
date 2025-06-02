/**
 * Quản lý kiểm kê kho hàng - JavaScript
 * Hỗ trợ RFID, Barcode scanning và quản lý chênh lệch tồn kho
 */

class InventoryCheckManager {
    constructor() {
        this.currentCheckId = null;
        this.scanningActive = false;
        this.batchScanMode = false;
        this.scannedItems = new Map();
        this.rfidDevices = [];
        this.inventoryChart = null;
        
        this.init();
    }

    init() {
        this.loadInitialData();
        this.setupEventListeners();
        this.initializeComponents();
    }

    /**
     * Load dữ liệu ban đầu
     */
    async loadInitialData() {
        try {
            await Promise.all([
                this.loadStats(),
                this.loadAreas(),
                this.loadRFIDDevices(),
                this.loadInventoryHistory()
            ]);
        } catch (error) {
            console.error('Lỗi load dữ liệu:', error);
            this.showToast('Lỗi load dữ liệu ban đầu', 'error');
        }
    }

    /**
     * Load thống kê tổng quan
     */
    async loadStats() {
        try {
            const response = await fetch('api/inventory_check_handler.php?action=get_stats');
            const result = await response.json();
            
            if (result.success) {
                this.updateStatsDisplay(result.data);
            }
        } catch (error) {
            console.error('Lỗi load stats:', error);
        }
    }

    /**
     * Cập nhật hiển thị thống kê
     */
    updateStatsDisplay(stats) {
        document.getElementById('totalProducts').textContent = stats.total_products || 0;
        document.getElementById('checkedProducts').textContent = stats.checked_products || 0;
        document.getElementById('discrepancies').textContent = stats.discrepancies || 0;
        document.getElementById('activeChecks').textContent = stats.active_checks || 0;
    }

    /**
     * Load danh sách khu vực
     */
    async loadAreas() {
        try {
            const response = await fetch('api/inventory_check_handler.php?action=get_areas');
            const result = await response.json();
            
            if (result.success) {
                this.populateAreaSelects(result.data);
            }
        } catch (error) {
            console.error('Lỗi load areas:', error);
        }
    }

    /**
     * Điền dữ liệu vào select areas
     */
    populateAreaSelects(areas) {
        const selects = ['filterArea', 'newCheckArea'];
        
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                // Xóa options cũ (trừ option đầu tiên)
                while (select.children.length > 1) {
                    select.removeChild(select.lastChild);
                }
                
                areas.forEach(area => {
                    const option = document.createElement('option');
                    option.value = area.area_id;
                    option.textContent = area.area_name;
                    select.appendChild(option);
                });
            }
        });
    }

    /**
     * Load thiết bị RFID
     */
    async loadRFIDDevices() {
        try {
            const response = await fetch('api/inventory_check_handler.php?action=get_rfid_devices');
            const result = await response.json();
            
            if (result.success) {
                this.rfidDevices = result.data;
                this.displayRFIDDevices(result.data);
            }
        } catch (error) {
            console.error('Lỗi load RFID devices:', error);
        }
    }

    /**
     * Hiển thị trạng thái thiết bị RFID
     */
    displayRFIDDevices(devices) {
        const container = document.getElementById('rfidDevices');
        if (!container) return;

        container.innerHTML = '';

        if (devices.length === 0) {
            container.innerHTML = '<p class="text-muted">Không có thiết bị RFID nào</p>';
            return;
        }

        devices.forEach(device => {
            const deviceElement = document.createElement('div');
            deviceElement.className = 'device-item';
            
            const statusClass = this.getDeviceStatusClass(device.status);
            const batteryLevel = device.battery_level || 0;
            
            deviceElement.innerHTML = `
                <div class="device-status-indicator ${statusClass}"></div>
                <div class="device-info">
                    <div class="device-name">${device.device_name}</div>
                    <div class="device-details">
                        <small>${device.area_name || 'Chưa gán khu vực'}</small>
                        <small>Pin: ${batteryLevel}%</small>
                    </div>
                </div>
            `;
            
            container.appendChild(deviceElement);
        });
    }

    /**
     * Lấy class CSS cho trạng thái thiết bị
     */
    getDeviceStatusClass(status) {
        const statusMap = {
            'active': 'device-status-active',
            'inactive': 'device-status-inactive',
            'error': 'device-status-error'
        };
        return statusMap[status] || 'device-status-inactive';
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Filter events
        document.getElementById('filterArea')?.addEventListener('change', (e) => {
            this.onAreaChange(e.target.value);
        });

        // Barcode input events
        const barcodeInput = document.getElementById('barcodeInput');
        if (barcodeInput) {
            barcodeInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.processBarcodeInput();
                }
            });
        }

        // Batch scan mode
        document.getElementById('batchScanMode')?.addEventListener('change', (e) => {
            this.batchScanMode = e.target.checked;
            this.updateBatchScanUI();
        });

        // Tab change events
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                this.onTabChange(e.target.getAttribute('data-bs-target'));
            });
        });
    }

    /**
     * Khi thay đổi khu vực
     */
    async onAreaChange(areaId) {
        if (!areaId) {
            document.getElementById('filterShelf').innerHTML = '<option value="">Tất cả kệ</option>';
            return;
        }

        try {
            const response = await fetch(`api/inventory_check_handler.php?action=get_shelves&area_id=${areaId}`);
            const result = await response.json();
            
            if (result.success) {
                this.populateShelfSelect(result.data);
            }
        } catch (error) {
            console.error('Lỗi load shelves:', error);
        }
    }

    /**
     * Điền dữ liệu vào select shelves
     */
    populateShelfSelect(shelves) {
        const select = document.getElementById('filterShelf');
        if (!select) return;

        select.innerHTML = '<option value="">Tất cả kệ</option>';
        
        shelves.forEach(shelf => {
            const option = document.createElement('option');
            option.value = shelf.shelf_id;
            option.textContent = `${shelf.shelf_code} (${shelf.area_name})`;
            select.appendChild(option);
        });
    }

    /**
     * Khi chuyển tab
     */
    onTabChange(target) {
        switch (target) {
            case '#results-panel':
                this.loadResultsData();
                break;
            case '#history-panel':
                this.loadInventoryHistory();
                break;
        }
    }

    /**
     * Load dữ liệu kết quả kiểm kê
     */
    async loadResultsData() {
        if (!this.currentCheckId) {
            this.showEmptyResults();
            return;
        }

        try {
            await Promise.all([
                this.loadScanResults(),
                this.loadDiscrepancies()
            ]);
            this.updateInventoryChart();
        } catch (error) {
            console.error('Lỗi load results data:', error);
        }
    }

    /**
     * Load lịch sử kiểm kê
     */
    async loadInventoryHistory() {
        try {
            const response = await fetch('api/inventory_check_handler.php?action=get_inventory_history');
            const result = await response.json();
            
            if (result.success) {
                this.displayInventoryHistory(result.data);
            }
        } catch (error) {
            console.error('Lỗi load history:', error);
        }
    }

    /**
     * Hiển thị lịch sử kiểm kê
     */
    displayInventoryHistory(history) {
        const tbody = document.querySelector('#historyTable tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (history.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Chưa có lịch sử kiểm kê</td></tr>';
            return;
        }

        history.forEach(record => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${record.check_id}</td>
                <td>${this.formatDateTime(record.check_date)}</td>
                <td>${record.area_name || 'N/A'}</td>
                <td>${record.creator_name || 'N/A'}</td>
                <td>${record.total_scans || 0}</td>
                <td>${record.discrepancies || 0}</td>
                <td><span class="status-badge status-${record.status}">${this.getStatusText(record.status)}</span></td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="viewCheckDetails(${record.check_id})">
                        <i class="fas fa-eye"></i> Xem
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    /**
     * Initialize components
     */
    initializeComponents() {
        this.initializeChart();
        this.updateBatchScanUI();
    }

    /**
     * Khởi tạo biểu đồ
     */
    initializeChart() {
        const ctx = document.getElementById('inventoryChart');
        if (!ctx) return;

        this.inventoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Chính xác', 'Thiếu hàng', 'Thừa hàng', 'Mất hàng'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107', 
                        '#17a2b8',
                        '#dc3545'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    /**
     * Cập nhật biểu đồ
     */
    updateInventoryChart() {
        if (!this.inventoryChart) return;

        const accurate = parseInt(document.getElementById('accurateCount')?.textContent || 0);
        const shortage = parseInt(document.getElementById('shortageCount')?.textContent || 0);
        const excess = parseInt(document.getElementById('excessCount')?.textContent || 0);
        const missing = parseInt(document.getElementById('missingCount')?.textContent || 0);

        this.inventoryChart.data.datasets[0].data = [accurate, shortage, excess, missing];
        this.inventoryChart.update();
    }

    /**
     * Bắt đầu quét RFID
     */
    startRFIDScan() {
        if (this.scanningActive) return;

        this.scanningActive = true;
        this.updateRFIDScanUI(true);
        
        // Giả lập quét RFID tự động
        this.simulateRFIDScanning();
        
        this.showToast('Bắt đầu quét RFID tự động', 'success');
    }

    /**
     * Dừng quét RFID
     */
    stopRFIDScan() {
        this.scanningActive = false;
        this.updateRFIDScanUI(false);
        
        if (this.rfidScanInterval) {
            clearInterval(this.rfidScanInterval);
            this.rfidScanInterval = null;
        }
        
        this.showToast('Đã dừng quét RFID', 'info');
    }

    /**
     * Cập nhật giao diện quét RFID
     */
    updateRFIDScanUI(scanning) {
        const scanZone = document.getElementById('rfidScanZone');
        const startBtn = document.getElementById('startRfidScan');
        const stopBtn = document.getElementById('stopRfidScan');

        if (scanZone) {
            scanZone.classList.toggle('scanning', scanning);
        }

        if (startBtn) {
            startBtn.style.display = scanning ? 'none' : 'inline-block';
        }

        if (stopBtn) {
            stopBtn.style.display = scanning ? 'inline-block' : 'none';
        }
    }

    /**
     * Giả lập quét RFID (trong thực tế sẽ đọc từ thiết bị)
     */
    simulateRFIDScanning() {
        if (!this.scanningActive) return;

        // Danh sách RFID giả lập cho demo
        const sampleRFIDs = ['RFID001', 'RFID002', 'RFID003', 'RFID004', 'RFID005'];
        
        this.rfidScanInterval = setInterval(() => {
            if (!this.scanningActive) {
                clearInterval(this.rfidScanInterval);
                return;
            }

            // Random chọn một RFID để quét
            const randomRFID = sampleRFIDs[Math.floor(Math.random() * sampleRFIDs.length)];
            this.processRFIDScan(randomRFID);
            
        }, 3000); // Quét mỗi 3 giây
    }

    /**
     * Xử lý quét RFID
     */
    async processRFIDScan(rfidValue) {
        try {
            const response = await fetch('api/inventory_check_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'scan_rfid',
                    rfid_value: rfidValue,
                    check_id: this.currentCheckId
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.addRFIDScanResult(result.data);
                this.updateRFIDSummary();
            } else {
                console.warn('RFID scan failed:', result.message);
            }
        } catch (error) {
            console.error('Lỗi scan RFID:', error);
        }
    }

    /**
     * Thêm kết quả quét RFID vào bảng
     */
    addRFIDScanResult(data) {
        const tbody = document.querySelector('#rfidResultsTable tbody');
        if (!tbody) return;

        const row = document.createElement('tr');
        const statusClass = this.getStatusBadgeClass(data.status);
        
        row.innerHTML = `
            <td class="rfid-value">${data.rfid_value}</td>
            <td>${data.product_info.product_name}</td>
            <td>${data.product_info.shelf_code || 'N/A'}</td>
            <td>${data.system_quantity}</td>
            <td>${data.actual_quantity}</td>
            <td><span class="status-badge ${statusClass}">${this.getStatusText(data.status)}</span></td>
        `;

        tbody.insertBefore(row, tbody.firstChild);

        // Lưu vào map để tính toán
        this.scannedItems.set(data.rfid_value, data);
    }

    /**
     * Cập nhật tóm tắt quét RFID
     */
    updateRFIDSummary() {
        const scannedCount = this.scannedItems.size;
        let matchedCount = 0;
        let errorCount = 0;

        this.scannedItems.forEach(item => {
            if (item.status === 'match') {
                matchedCount++;
            } else {
                errorCount++;
            }
        });

        document.getElementById('rfidScanned').textContent = scannedCount;
        document.getElementById('rfidMatched').textContent = matchedCount;
        document.getElementById('rfidErrors').textContent = errorCount;
    }

    /**
     * Xử lý input barcode
     */
    async processBarcodeInput() {
        const input = document.getElementById('barcodeInput');
        const barcodeValue = input.value.trim();
        
        if (!barcodeValue) {
            this.showToast('Vui lòng nhập mã barcode', 'warning');
            return;
        }

        try {
            const response = await fetch('api/inventory_check_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'scan_barcode',
                    barcode_value: barcodeValue,
                    check_id: this.currentCheckId,
                    quantity: 1
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.addBarcodeScanResult(result.data);
                this.updateBarcodeSummary();
                
                if (this.batchScanMode) {
                    input.value = '';
                    input.focus();
                } else {
                    input.value = '';
                }
                
                this.showToast('Quét barcode thành công', 'success');
            } else {
                this.showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Lỗi scan barcode:', error);
            this.showToast('Lỗi quét barcode', 'error');
        }
    }

    /**
     * Thêm kết quả quét barcode vào bảng
     */
    addBarcodeScanResult(data) {
        const tbody = document.querySelector('#barcodeResultsTable tbody');
        if (!tbody) return;

        const row = document.createElement('tr');
        const statusClass = this.getStatusBadgeClass(data.status);
        
        row.innerHTML = `
            <td class="barcode-value">${data.barcode_value}</td>
            <td>${data.product_info.product_name}</td>
            <td>${data.product_info.lot_number || 'N/A'}</td>
            <td>${data.system_quantity}</td>
            <td>
                <input type="number" class="form-control form-control-sm" 
                       value="${data.actual_quantity}" 
                       onchange="updateBarcodeQuantity('${data.barcode_value}', this.value)"
                       style="width: 80px;">
            </td>
            <td><span class="status-badge ${statusClass}">${this.getStatusText(data.status)}</span></td>
            <td>
                <button class="btn btn-sm btn-warning" onclick="adjustBarcodeStock('${data.barcode_value}')">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        `;

        tbody.insertBefore(row, tbody.firstChild);
        
        // Lưu vào map
        this.scannedItems.set(data.barcode_value, data);
    }

    /**
     * Cập nhật tóm tắt quét barcode
     */
    updateBarcodeSummary() {
        let barcodeScanned = 0;
        let barcodeMatched = 0;
        let barcodeErrors = 0;

        this.scannedItems.forEach(item => {
            if (item.barcode_value) {
                barcodeScanned++;
                if (item.status === 'match') {
                    barcodeMatched++;
                } else {
                    barcodeErrors++;
                }
            }
        });

        document.getElementById('barcodeScanned').textContent = barcodeScanned;
        document.getElementById('barcodeMatched').textContent = barcodeMatched;
        document.getElementById('barcodeErrors').textContent = barcodeErrors;
    }

    /**
     * Cập nhật UI batch scan mode
     */
    updateBatchScanUI() {
        const input = document.getElementById('barcodeInput');
        if (input) {
            input.placeholder = this.batchScanMode ? 
                'Quét liên tục và nhấn Enter...' : 
                'Quét mã vạch tại đây...';
        }
    }

    /**
     * Bắt đầu quét camera (sử dụng QuaggaJS)
     */
    startCameraScan() {
        if (typeof Quagga === 'undefined') {
            this.showToast('Thư viện quét camera chưa được tải', 'error');
            return;
        }

        const preview = document.getElementById('cameraPreview');
        preview.style.display = 'block';

        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector('#preview')
            },
            decoder: {
                readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader"]
            }
        }, (err) => {
            if (err) {
                console.error('Lỗi khởi tạo camera:', err);
                this.showToast('Không thể khởi tạo camera', 'error');
                return;
            }
            Quagga.start();
        });

        Quagga.onDetected((data) => {
            const code = data.codeResult.code;
            document.getElementById('barcodeInput').value = code;
            this.processBarcodeInput();
            this.stopCameraScan();
        });
    }

    /**
     * Dừng quét camera
     */
    stopCameraScan() {
        if (typeof Quagga !== 'undefined') {
            Quagga.stop();
        }
        document.getElementById('cameraPreview').style.display = 'none';
    }

    /**
     * Lấy class CSS cho status badge
     */
    getStatusBadgeClass(status) {
        const statusMap = {
            'match': 'status-success',
            'shortage': 'status-warning',
            'excess': 'status-info',
            'missing': 'status-danger'
        };
        return statusMap[status] || 'status-info';
    }

    /**
     * Lấy text hiển thị cho status
     */
    getStatusText(status) {
        const statusMap = {
            'match': 'Khớp',
            'shortage': 'Thiếu',
            'excess': 'Thừa',
            'missing': 'Mất',
            'pending': 'Chờ xử lý',
            'completed': 'Hoàn thành',
            'failed': 'Thất bại'
        };
        return statusMap[status] || status;
    }

    /**
     * Format datetime
     */
    formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('vi-VN');
    }

    /**
     * Hiển thị toast notification
     */
    showToast(message, type = 'info') {
        const toast = document.getElementById('toastNotification');
        const toastBody = document.getElementById('toastBody');
        
        if (toast && toastBody) {
            toastBody.textContent = message;
            toast.className = `toast toast-${type}`;
            
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
        }
    }

    /**
     * Hiển thị kết quả trống
     */
    showEmptyResults() {
        document.getElementById('accurateCount').textContent = '0';
        document.getElementById('shortageCount').textContent = '0';
        document.getElementById('excessCount').textContent = '0';
        document.getElementById('missingCount').textContent = '0';
        
        if (this.inventoryChart) {
            this.inventoryChart.data.datasets[0].data = [0, 0, 0, 0];
            this.inventoryChart.update();
        }
    }

    /**
     * Xóa kết quả RFID
     */
    clearRfidResults() {
        const tbody = document.querySelector('#rfidResultsTable tbody');
        if (tbody) {
            tbody.innerHTML = '';
        }
        
        // Xóa dữ liệu RFID khỏi map
        this.scannedItems.forEach((value, key) => {
            if (key.startsWith('RFID')) {
                this.scannedItems.delete(key);
            }
        });
        
        this.updateRFIDSummary();
        this.showToast('Đã xóa kết quả quét RFID', 'success');
    }
}

// Khởi tạo manager khi DOM loaded
let inventoryManager;
document.addEventListener('DOMContentLoaded', function() {
    inventoryManager = new InventoryCheckManager();
});

/**
 * Các hàm global được gọi từ HTML
 */

function startNewCheck() {
    const modal = document.getElementById('newCheckModal');
    if (modal) {
        modal.classList.add('show');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

function createNewCheck() {
    const areaId = document.getElementById('newCheckArea').value;
    const useRFID = document.getElementById('useRFID').checked;
    const useBarcode = document.getElementById('useBarcode').checked;
    const notes = document.getElementById('newCheckNotes').value;
    
    if (!areaId) {
        inventoryManager.showToast('Vui lòng chọn khu vực', 'warning');
        return;
    }
    
    if (!useRFID && !useBarcode) {
        inventoryManager.showToast('Vui lòng chọn ít nhất một phương pháp kiểm kê', 'warning');
        return;
    }
    
    // Gọi API tạo phiên kiểm kê
    fetch('api/inventory_check_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'start_inventory_check',
            area_id: areaId,
            use_rfid: useRFID,
            use_barcode: useBarcode,
            notes: notes
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            inventoryManager.currentCheckId = result.data.check_id;
            inventoryManager.showToast('Tạo phiên kiểm kê thành công', 'success');
            closeModal('newCheckModal');
            inventoryManager.loadStats(); // Refresh stats
        } else {
            inventoryManager.showToast(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Lỗi tạo phiên kiểm kê:', error);
        inventoryManager.showToast('Lỗi tạo phiên kiểm kê', 'error');
    });
}

function startRFIDScan() {
    if (inventoryManager) {
        inventoryManager.startRFIDScan();
    }
}

function stopRFIDScan() {
    if (inventoryManager) {
        inventoryManager.stopRFIDScan();
    }
}

function clearRfidResults() {
    if (inventoryManager) {
        inventoryManager.clearRfidResults();
    }
}

function processBarcodeInput() {
    if (inventoryManager) {
        inventoryManager.processBarcodeInput();
    }
}

function startCameraScan() {
    if (inventoryManager) {
        inventoryManager.startCameraScan();
    }
}

function stopCameraScan() {
    if (inventoryManager) {
        inventoryManager.stopCameraScan();
    }
}

function updateBarcodeQuantity(barcodeValue, newQuantity) {
    // Cập nhật số lượng trong scannedItems
    if (inventoryManager && inventoryManager.scannedItems.has(barcodeValue)) {
        const item = inventoryManager.scannedItems.get(barcodeValue);
        item.actual_quantity = parseInt(newQuantity);
        item.difference = item.actual_quantity - item.system_quantity;
        item.status = (item.difference === 0) ? 'match' : (item.difference > 0 ? 'excess' : 'shortage');
        
        inventoryManager.scannedItems.set(barcodeValue, item);
        inventoryManager.updateBarcodeSummary();
    }
}

function adjustBarcodeStock(barcodeValue) {
    if (!inventoryManager || !inventoryManager.scannedItems.has(barcodeValue)) {
        return;
    }
    
    const item = inventoryManager.scannedItems.get(barcodeValue);
    
    // Điền dữ liệu vào modal điều chỉnh
    document.getElementById('adjustProductId').value = item.product_info.product_id;
    document.getElementById('adjustProductName').value = item.product_info.product_name;
    document.getElementById('adjustSystemQty').value = item.system_quantity;
    document.getElementById('adjustActualQty').value = item.actual_quantity;
    document.getElementById('adjustDifference').value = item.difference;
    
    // Hiển thị modal
    document.getElementById('adjustStockModal').classList.add('show');
}

function submitStockAdjustment() {
    const form = document.getElementById('adjustStockForm');
    const formData = new FormData(form);
    
    const data = {
        action: 'adjust_stock',
        product_id: document.getElementById('adjustProductId').value,
        shelf_id: document.getElementById('adjustShelfId').value,
        system_qty: parseInt(document.getElementById('adjustSystemQty').value),
        actual_qty: parseInt(document.getElementById('adjustActualQty').value),
        reason: document.getElementById('adjustReason').value,
        adjust_type: document.getElementById('adjustType').value
    };
    
    if (!data.reason || !data.adjust_type) {
        inventoryManager.showToast('Vui lòng điền đầy đủ thông tin', 'warning');
        return;
    }
    
    fetch('api/inventory_check_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            inventoryManager.showToast('Điều chỉnh tồn kho thành công', 'success');
            closeModal('adjustStockModal');
            // Reset form
            form.reset();
        } else {
            inventoryManager.showToast(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Lỗi điều chỉnh tồn kho:', error);
        inventoryManager.showToast('Lỗi điều chỉnh tồn kho', 'error');
    });
}

function showDiscrepancies() {
    // Hiển thị tab kết quả và focus vào bảng chênh lệch
    const resultsTab = document.querySelector('[data-bs-target="#results-panel"]');
    if (resultsTab) {
        resultsTab.click();
    }
    
    setTimeout(() => {
        const discrepanciesTable = document.querySelector('.discrepancies-table');
        if (discrepanciesTable) {
            discrepanciesTable.scrollIntoView({ behavior: 'smooth' });
        }
    }, 300);
}

function approveAdjustments() {
    // Xử lý duyệt tất cả điều chỉnh
    if (confirm('Bạn có chắc chắn muốn duyệt tất cả điều chỉnh tồn kho?')) {
        inventoryManager.showToast('Chức năng đang được phát triển', 'info');
    }
}

function exportResults() {
    if (!inventoryManager.currentCheckId) {
        inventoryManager.showToast('Chưa có phiên kiểm kê nào được chọn', 'warning');
        return;
    }
    
    // Export kết quả kiểm kê
    window.open(`api/inventory_check_handler.php?action=export_results&check_id=${inventoryManager.currentCheckId}`);
}

function filterHistory() {
    const dateFilter = document.getElementById('historyDateFilter').value;
    if (inventoryManager) {
        inventoryManager.loadInventoryHistory();
    }
}

function viewCheckDetails(checkId) {
    inventoryManager.showToast('Chức năng xem chi tiết đang được phát triển', 'info');
} 