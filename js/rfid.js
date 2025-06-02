/**
 * RFID Management JavaScript
 * File: js/rfid.js
 */

class RFIDManager {
    constructor() {
        this.realTimeChart = null;
        this.autoRefreshInterval = null;
        this.scanInterval = null;
        this.isScanning = false;
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadInitialData();
        this.startAutoRefresh();
    }
    
    setupEventListeners() {
        // Search and filter events
        $('#searchTag').on('input', () => this.debounce(this.loadRFIDTags.bind(this), 500));
        $('#filterProduct, #filterShelf').on('change', () => this.loadRFIDTags());
        
        // Date filter events
        $('#fromDate, #toDate, #filterResult').on('change', () => this.loadScanLogs());
        
        // Tab change events
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', (e) => {
            const target = $(e.target).attr('data-bs-target');
            this.handleTabChange(target);
        });
        
        // Form submission events
        $('#tagForm').on('submit', (e) => {
            e.preventDefault();
            this.saveTag();
        });
        
        $('#deviceForm').on('submit', (e) => {
            e.preventDefault();
            this.saveDevice();
        });
    }
    
    loadInitialData() {
        this.loadStatistics();
       
        this.setupRealTimeChart();
        this.loadDashboardData();
        this.loadRFIDTags();
        this.loadDevices();
        this.loadScanLogs();
        this.loadAlerts();
    }
    
    startAutoRefresh() {
        // Auto refresh every 30 seconds
        this.autoRefreshInterval = setInterval(() => {
            if ($('#dashboard-tab').hasClass('active')) {
                this.loadStatistics();
                this.loadDashboardData();
                this.updateRealTimeChart();
            }
        }, 30000);
    }
    
    handleTabChange(target) {
        switch(target) {
            case '#dashboard':
                this.loadDashboardData();
                this.updateRealTimeChart();
                break;
            case '#tags':
                this.loadRFIDTags();
                break;
            case '#devices':
                this.loadDevices();
                break;
            case '#scan-logs':
                this.loadScanLogs();
                break;
            case '#alerts':
                this.loadAlerts();
                break;
        }
    }
    
    // Statistics Methods
    async loadStatistics() {
        try {
            const response = await fetch('api/rfid_handler.php?action=get_statistics');
            const data = await response.json();
            
            if (data.success) {
                this.updateStatisticsDisplay(data.data);
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    }
    
    updateStatisticsDisplay(stats) {
        $('#totalRFIDTags').text(stats.total_tags || 0);
        $('#activeDevices').text(stats.active_devices || 0);
        $('#todayScans').text(stats.today_scans || 0);
        $('#rfidAlerts').text(stats.rfid_alerts || 0);
        
        // Add animation to stats
        $('.barcode-stats-card h3').each(function() {
            $(this).addClass('activity-pulse');
            setTimeout(() => $(this).removeClass('activity-pulse'), 1000);
        });
    }
    
    // Chart Methods
    setupRealTimeChart() {
        const ctx = document.getElementById('realTimeChart')?.getContext('2d');
        if (!ctx) return;
        
        this.realTimeChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Số lượng quét RFID',
                    data: [],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
    
    async updateRealTimeChart() {
        try {
            const response = await fetch('api/rfid_handler.php?action=get_scan_stats');
            const data = await response.json();
            
            if (data.success && this.realTimeChart) {
                this.realTimeChart.data.labels = data.data.labels;
                this.realTimeChart.data.datasets[0].data = data.data.values;
                this.realTimeChart.update('none');
            }
        } catch (error) {
            console.error('Error updating chart:', error);
        }
    }
    
    // Dashboard Methods
    async loadDashboardData() {
        try {
            await Promise.all([
                this.loadDeviceStatus(),
                this.loadRecentActivity()
            ]);
        } catch (error) {
            console.error('Error loading dashboard data:', error);
        }
    }
    
    async loadDeviceStatus() {
        try {
            const response = await fetch('api/rfid_handler.php?action=get_device_status');
            const data = await response.json();
            
            if (data.success) {
                this.updateDeviceStatusDisplay(data.data);
            }
        } catch (error) {
            console.error('Error loading device status:', error);
        }
    }
    
    updateDeviceStatusDisplay(devices) {
        const container = document.getElementById('deviceStatusList');
        if (!container) return;
        
        container.innerHTML = '';
        
        devices.forEach(device => {
            const statusClass = this.getDeviceStatusClass(device.status);
            const batteryLevel = device.battery_level || 0;
            const batteryClass = this.getBatteryClass(batteryLevel);
            
            const deviceElement = document.createElement('div');
            deviceElement.className = 'd-flex justify-content-between align-items-center mb-3 p-3 border rounded';
            deviceElement.innerHTML = `
                <div class="d-flex align-items-center">
                    <span class="device-status-indicator ${statusClass}"></span>
                    <div>
                        <strong>${device.device_name}</strong>
                        <small class="text-muted d-block">${device.area_name || 'Không xác định'}</small>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge ${this.getStatusBadgeClass(device.status)}">${this.getStatusText(device.status)}</span>
                    ${device.battery_level ? `
                        <div class="battery-indicator mt-1" style="width: 60px;">
                            <div class="battery-level ${batteryClass}" style="width: ${batteryLevel}%"></div>
                        </div>
                        <small class="d-block">Pin: ${batteryLevel}%</small>
                    ` : ''}
                </div>
            `;
            
            container.appendChild(deviceElement);
        });
    }
    
    async loadRecentActivity() {
        try {
            const response = await fetch('api/rfid_handler.php?action=get_recent_activity');
            const data = await response.json();
            
            if (data.success) {
                this.updateRecentActivityDisplay(data.data);
            }
        } catch (error) {
            console.error('Error loading recent activity:', error);
        }
    }
    
    updateRecentActivityDisplay(activities) {
        const container = document.getElementById('recentActivity');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (activities.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">Chưa có hoạt động nào</p>';
            return;
        }
        
        activities.forEach(activity => {
            const resultClass = activity.scan_result === 'success' ? 'bg-success' : 'bg-danger';
            
            const activityElement = document.createElement('div');
            activityElement.className = 'd-flex justify-content-between align-items-center mb-2 p-2 border rounded rfid-scan-animation';
            activityElement.innerHTML = `
                <div>
                    <div class="rfid-value">${activity.rfid_value}</div>
                    <small class="text-muted">${activity.product_name || 'Sản phẩm không xác định'}</small>
                </div>
                <div class="text-end">
                    <span class="badge ${resultClass}">${this.getScanResultText(activity.scan_result)}</span>
                    <small class="d-block">${this.formatDateTime(activity.scan_time)}</small>
                </div>
            `;
            
            container.appendChild(activityElement);
        });
    }
    
    // RFID Tags Methods
    async loadRFIDTags() {
        try {
            const search = document.getElementById('searchTag')?.value || '';
            const productFilter = document.getElementById('filterProduct')?.value || '';
            const shelfFilter = document.getElementById('filterShelf')?.value || '';
            
            const params = new URLSearchParams({
                action: 'get_tags',
                search: search,
                product_id: productFilter,
                shelf_id: shelfFilter
            });
            
            const response = await fetch(`api/rfid_handler.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateRFIDTagsTable(data.data);
            }
        } catch (error) {
            console.error('Error loading RFID tags:', error);
        }
    }
    
    updateRFIDTagsTable(tags) {
        const tbody = document.querySelector('#rfidTagsTable tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (tags.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Không có dữ liệu</td></tr>';
            return;
        }
        
        tags.forEach(tag => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${tag.rfid_id}</td>
                <td><span class="rfid-value-cell">${tag.rfid_value}</span></td>
                <td>${tag.product_name || '<span class="text-muted">Không xác định</span>'}</td>
                <td>${tag.lot_number || '-'}</td>
                <td class="${this.getExpiryClass(tag.expiry_date)}">${this.formatDate(tag.expiry_date) || '-'}</td>
                <td>${tag.shelf_code || '-'}</td>
                <td>${this.formatDateTime(tag.created_at)}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="rfidManager.editTag(${tag.rfid_id})" title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="rfidManager.deleteTag(${tag.rfid_id})" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
    
    // Edit and Delete Tag Methods
    async editTag(tagId) {
        try {
            const response = await fetch(`api/rfid_handler.php?action=get_tag_by_id&id=${tagId}`);
            const data = await response.json();
            
            if (data.success) {
                const tag = data.data;
                
                // Fill form with existing data
                document.getElementById('tagModalTitle').textContent = 'Chỉnh sửa Thẻ RFID';
                document.getElementById('tagId').value = tag.rfid_id;
                document.getElementById('rfidValue').value = tag.rfid_value;
                document.getElementById('productId').value = tag.product_id || '';
                document.getElementById('lotNumber').value = tag.lot_number || '';
                document.getElementById('expiryDate').value = tag.expiry_date || '';
                document.getElementById('shelfId').value = tag.shelf_id || '';
                
                // Show modal
                new bootstrap.Modal(document.getElementById('tagModal')).show();
            } else {
                this.showToast(data.message || 'Không thể tải thông tin thẻ RFID', 'danger');
            }
        } catch (error) {
            console.error('Error loading tag:', error);
            this.showToast('Có lỗi xảy ra khi tải thông tin', 'danger');
        }
    }
    
    async deleteTag(tagId) {
        if (!confirm('Bạn có chắc chắn muốn xóa thẻ RFID này?')) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'delete_tag');
            formData.append('tagId', tagId);
            
            const response = await fetch('api/rfid_handler.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Xóa thẻ RFID thành công!', 'success');
                this.loadRFIDTags();
                this.loadStatistics();
            } else {
                this.showToast(data.message || 'Không thể xóa thẻ RFID', 'danger');
            }
        } catch (error) {
            console.error('Error deleting tag:', error);
            this.showToast('Có lỗi xảy ra khi xóa', 'danger');
        }
    }
    
    // Device Methods
    async loadDevices() {
        try {
            const response = await fetch('api/rfid_handler.php?action=get_devices');
            const data = await response.json();
            
            if (data.success) {
                this.updateDevicesGrid(data.data);
            }
        } catch (error) {
            console.error('Error loading devices:', error);
        }
    }
    
    updateDevicesGrid(devices) {
        const container = document.getElementById('devicesGrid');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (devices.length === 0) {
            container.innerHTML = '<div class="col-12"><p class="text-center text-muted">Chưa có thiết bị nào</p></div>';
            return;
        }
        
        devices.forEach(device => {
            const statusClass = device.status || 'inactive';
            const batteryLevel = device.battery_level || 0;
            
            const deviceCard = document.createElement('div');
            deviceCard.className = 'col-md-4 mb-3';
            deviceCard.innerHTML = `
                <div class="device-card ${statusClass}">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="card-title mb-1">${device.device_name}</h6>
                            <p class="card-text text-muted mb-0">${device.area_name || 'Không xác định'}</p>
                        </div>
                        <span class="badge ${this.getStatusBadgeClass(device.status)}">${this.getStatusText(device.status)}</span>
                    </div>
                    ${device.battery_level ? `
                        <div class="battery-indicator mb-2">
                            <div class="battery-level ${this.getBatteryClass(batteryLevel)}" style="width: ${batteryLevel}%"></div>
                        </div>
                        <small class="text-muted d-block mb-3">Pin: ${batteryLevel}%</small>
                    ` : ''}
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary flex-fill" onclick="rfidManager.editDevice(${device.device_id})">
                            <i class="fas fa-edit"></i> Sửa
                        </button>
                        <button class="btn btn-sm btn-outline-danger flex-fill" onclick="rfidManager.deleteDevice(${device.device_id})">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(deviceCard);
        });
    }
    
    // Edit and Delete Device Methods
    async editDevice(deviceId) {
        try {
            const response = await fetch(`api/rfid_handler.php?action=get_device_by_id&id=${deviceId}`);
            const data = await response.json();
            
            if (data.success) {
                const device = data.data;
                
                // Fill form with existing data
                document.getElementById('deviceModalTitle').textContent = 'Chỉnh sửa Thiết bị RFID';
                document.getElementById('deviceId').value = device.device_id;
                document.getElementById('deviceName').value = device.device_name;
                document.getElementById('areaId').value = device.area_id || '';
                document.getElementById('batteryLevel').value = device.battery_level || '';
                
                // Show modal
                new bootstrap.Modal(document.getElementById('deviceModal')).show();
            } else {
                this.showToast(data.message || 'Không thể tải thông tin thiết bị', 'danger');
            }
        } catch (error) {
            console.error('Error loading device:', error);
            this.showToast('Có lỗi xảy ra khi tải thông tin', 'danger');
        }
    }
    
    async deleteDevice(deviceId) {
        if (!confirm('Bạn có chắc chắn muốn xóa thiết bị này?')) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'delete_device');
            formData.append('deviceId', deviceId);
            
            const response = await fetch('api/rfid_handler.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Xóa thiết bị thành công!', 'success');
                this.loadDevices();
                this.loadStatistics();
            } else {
                this.showToast(data.message || 'Không thể xóa thiết bị', 'danger');
            }
        } catch (error) {
            console.error('Error deleting device:', error);
            this.showToast('Có lỗi xảy ra khi xóa', 'danger');
        }
    }
    
    // Scan Logs Methods
    async loadScanLogs() {
        try {
            const fromDate = document.getElementById('fromDate')?.value || '';
            const toDate = document.getElementById('toDate')?.value || '';
            const resultFilter = document.getElementById('filterResult')?.value || '';
            
            const params = new URLSearchParams({
                action: 'get_scan_logs',
                from_date: fromDate,
                to_date: toDate,
                result: resultFilter
            });
            
            const response = await fetch(`api/rfid_handler.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateScanLogsTable(data.data);
            }
        } catch (error) {
            console.error('Error loading scan logs:', error);
        }
    }
    
    updateScanLogsTable(logs) {
        const tbody = document.querySelector('#scanLogsTable tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Không có dữ liệu</td></tr>';
            return;
        }
        
        logs.forEach(log => {
            const resultClass = log.scan_result === 'success' ? 'scan-success' : 'scan-failed';
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${this.formatDateTime(log.scan_time)}</td>
                <td><span class="rfid-value-cell">${log.rfid_value}</span></td>
                <td>${log.product_name || '<span class="text-muted">Không xác định</span>'}</td>
                <td>${log.full_name || 'Hệ thống'}</td>
                <td><span class="${resultClass}">${this.getScanResultText(log.scan_result)}</span></td>
                <td>${log.description || '-'}</td>
            `;
            tbody.appendChild(row);
        });
    }
    
    // Alerts Methods
    async loadAlerts() {
        try {
            const response = await fetch('api/rfid_handler.php?action=get_alerts');
            const data = await response.json();
            
            if (data.success) {
                this.updateAlertsContainer(data.data);
            }
        } catch (error) {
            console.error('Error loading alerts:', error);
        }
    }
    
    updateAlertsContainer(alerts) {
        const container = document.getElementById('alertsContainer');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (alerts.length === 0) {
            container.innerHTML = '<div class="col-12"><p class="text-center text-muted">Không có cảnh báo nào</p></div>';
            return;
        }
        
        alerts.forEach(alert => {
            const alertConfig = this.getAlertTypeConfig(alert.alert_type);
            
            const alertElement = document.createElement('div');
            alertElement.className = 'col-md-6 mb-3';
            alertElement.innerHTML = `
                <div class="rfid-alert-card alert alert-${alertConfig.color}">
                    <div class="d-flex align-items-center">
                        <i class="${alertConfig.icon} me-2 fa-lg"></i>
                        <div class="flex-grow-1">
                            <strong>${alertConfig.title}</strong>
                            <p class="mb-1">${alert.description}</p>
                            <small class="text-muted">${this.formatDateTime(alert.created_at)}</small>
                        </div>
                    </div>
                </div>
            `;
            
            container.appendChild(alertElement);
        });
    }
    
    // Helper Methods
    getDeviceStatusClass(status) {
        switch(status) {
            case 'active': return 'device-status-active';
            case 'error': return 'device-status-error';
            default: return 'device-status-inactive';
        }
    }
    
    getStatusBadgeClass(status) {
        switch(status) {
            case 'active': return 'bg-success';
            case 'error': return 'bg-danger';
            default: return 'bg-secondary';
        }
    }
    
    getStatusText(status) {
        switch(status) {
            case 'active': return 'Hoạt động';
            case 'error': return 'Lỗi';
            case 'inactive': return 'Không hoạt động';
            default: return 'Không xác định';
        }
    }
    
    getBatteryClass(level) {
        if (level > 60) return 'high';
        if (level > 30) return 'medium';
        return 'low';
    }
    
    getScanResultText(result) {
        return result === 'success' ? 'Thành công' : 'Thất bại';
    }
    
    getExpiryClass(expiryDate) {
        if (!expiryDate) return '';
        
        const today = new Date();
        const expiry = new Date(expiryDate);
        const diffDays = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));
        
        if (diffDays < 0) return 'expiry-warning';
        if (diffDays <= 7) return 'expiry-warning';
        return 'expiry-ok';
    }
    
    getAlertTypeConfig(type) {
        const configs = {
            'low_stock': { color: 'warning', icon: 'fas fa-exclamation-triangle', title: 'Tồn kho thấp' },
            'expiry_soon': { color: 'danger', icon: 'fas fa-clock', title: 'Sắp hết hạn' },
            'rfid_error': { color: 'danger', icon: 'fas fa-tags', title: 'Lỗi thẻ RFID' },
            'device_error': { color: 'danger', icon: 'fas fa-exclamation-circle', title: 'Lỗi thiết bị' }
        };
        return configs[type] || { color: 'info', icon: 'fas fa-info', title: 'Thông báo' };
    }
    
    formatDateTime(dateString) {
        if (!dateString) return '';
        return new Date(dateString).toLocaleString('vi-VN');
    }
    
    formatDate(dateString) {
        if (!dateString) return '';
        return new Date(dateString).toLocaleDateString('vi-VN');
    }
    
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
    
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${type === 'success' ? 'check' : type === 'danger' ? 'times' : 'info'}-circle me-2"></i>
                ${message}
            </div>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    
    // Cleanup method
    destroy() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
        }
        if (this.scanInterval) {
            clearInterval(this.scanInterval);
        }
        if (this.realTimeChart) {
            this.realTimeChart.destroy();
        }
    }
}

// Global instance
let rfidManager;

// Initialize when document is ready
$(document).ready(function() {
    rfidManager = new RFIDManager();
});

// Global functions for onclick events
function showAddTagModal() {
    document.getElementById('tagModalTitle').textContent = 'Thêm Thẻ RFID';
    document.getElementById('tagForm').reset();
    document.getElementById('tagId').value = '';
    new bootstrap.Modal(document.getElementById('tagModal')).show();
}

function showAddDeviceModal() {
    document.getElementById('deviceModalTitle').textContent = 'Thêm Thiết bị RFID';
    document.getElementById('deviceForm').reset();
    document.getElementById('deviceId').value = '';
    new bootstrap.Modal(document.getElementById('deviceModal')).show();
}

function refreshTagsTable() {
    rfidManager.loadRFIDTags();
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (rfidManager) {
        rfidManager.destroy();
    }
}); 