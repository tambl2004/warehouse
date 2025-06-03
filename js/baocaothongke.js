/**
 * Báo cáo Thống kê JavaScript
 * Xử lý tất cả chức năng báo cáo và thống kê
 */

// Biến toàn cục cho các biểu đồ
let inventoryTrendChart = null;
let categoryDistributionChart = null;
let importExportChart = null;
let analysisChart = null;
let financialChart = null;
let performanceChart = null;
let performanceComparisonChart = null;

// Khởi tạo khi DOM ready
document.addEventListener('DOMContentLoaded', function() {
    initializeReportManagement();
    loadInitialData();
    setupEventListeners();
});

/**
 * Khởi tạo module báo cáo thống kê
 */
function initializeReportManagement() {
    console.log('Initializing Report Management...');
    
    // Thiết lập ngày mặc định (30 ngày qua)
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    
    // Set default dates
    document.getElementById('inventoryFromDate').value = formatDate(thirtyDaysAgo);
    document.getElementById('inventoryToDate').value = formatDate(today);
    document.getElementById('ieFromDate').value = formatDate(thirtyDaysAgo);
    document.getElementById('ieToDate').value = formatDate(today);
    document.getElementById('financialFromDate').value = formatDate(thirtyDaysAgo);
    document.getElementById('financialToDate').value = formatDate(today);
    
    // Khởi tạo biểu đồ
    initializeCharts();
}

/**
 * Thiết lập event listeners
 */
function setupEventListeners() {
    // Tab change events
    document.querySelectorAll('#reportTabs button[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(event) {
            const target = event.target.getAttribute('data-bs-target');
            handleTabChange(target);
        });
    });
    
    // Custom report type change
    const customReportType = document.getElementById('customReportType');
    if (customReportType) {
        customReportType.addEventListener('change', updateCustomFields);
    }
}

/**
 * Xử lý thay đổi tab
 */
function handleTabChange(target) {
    switch(target) {
        case '#inventory':
            loadInventoryReportData();
            break;
        case '#import-export':
            loadImportExportReportData();
            break;
        case '#analysis':
            // Không tự động load, chờ user chọn
            break;
        case '#financial':
            loadFinancialReportData();
            break;
        case '#performance':
            loadPerformanceData();
            break;
    }
}

/**
 * Tải dữ liệu ban đầu
 */
async function loadInitialData() {
    try {
        // Load dropdown data
        await Promise.all([
            loadCategories(),
            loadAreas(),
            loadProducts(),
            loadSuppliers(),
            loadUsers()
        ]);
        
        // Load default report data
        await loadInventoryReportData();
        
    } catch (error) {
        console.error('Error loading initial data:', error);
        showError('Có lỗi xảy ra khi tải dữ liệu ban đầu');
    }
}

/**
 * Load danh mục
 */
async function loadCategories() {
    try {
        const response = await fetch('api/report_handler.php?action=get_categories');
        const data = await response.json();
        
        if (data.success) {
            const selects = ['inventoryCategory', 'analysisCategory'];
            selects.forEach(selectId => {
                const select = document.getElementById(selectId);
                if (select) {
                    data.data.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.category_id;
                        option.textContent = category.category_name;
                        select.appendChild(option);
                    });
                }
            });
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

/**
 * Load khu vực
 */
async function loadAreas() {
    try {
        const response = await fetch('api/report_handler.php?action=get_areas');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('inventoryArea');
            if (select) {
                data.data.forEach(area => {
                    const option = document.createElement('option');
                    option.value = area.area_id;
                    option.textContent = area.area_name;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading areas:', error);
    }
}

/**
 * Load sản phẩm
 */
async function loadProducts() {
    try {
        const response = await fetch('api/report_handler.php?action=get_products');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('ieProduct');
            if (select) {
                data.data.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.product_id;
                    option.textContent = `${product.product_name} (${product.sku})`;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

/**
 * Load nhà cung cấp
 */
async function loadSuppliers() {
    try {
        const response = await fetch('api/report_handler.php?action=get_suppliers');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('ieSupplier');
            if (select) {
                data.data.forEach(supplier => {
                    const option = document.createElement('option');
                    option.value = supplier.supplier_id;
                    option.textContent = supplier.supplier_name;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading suppliers:', error);
    }
}

/**
 * Load người dùng
 */
async function loadUsers() {
    try {
        const response = await fetch('api/report_handler.php?action=get_users');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('ieUser');
            if (select) {
                data.data.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.user_id;
                    option.textContent = user.full_name;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

/**
 * Khởi tạo tất cả biểu đồ
 */
function initializeCharts() {
    initInventoryCharts();
    initImportExportChart();
    initAnalysisChart();
    initFinancialChart();
    initPerformanceCharts();
}

/**
 * Khởi tạo biểu đồ tồn kho
 */
function initInventoryCharts() {
    // Biểu đồ xu hướng tồn kho
    const inventoryCtx = document.getElementById('inventoryTrendChart').getContext('2d');
    inventoryTrendChart = new Chart(inventoryCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Tồn kho',
                data: [],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                fill: true,
                tension: 0.4
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
                    beginAtZero: true
                }
            }
        }
    });
    
    // Biểu đồ phân bố danh mục
    const categoryCtx = document.getElementById('categoryDistributionChart').getContext('2d');
    categoryDistributionChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#667eea', '#764ba2', '#f093fb', '#f5576c',
                    '#4facfe', '#00f2fe', '#43e97b', '#38f9d7'
                ]
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
 * Khởi tạo biểu đồ nhập/xuất
 */
function initImportExportChart() {
    const ctx = document.getElementById('importExportChart').getContext('2d');
    importExportChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Nhập kho',
                data: [],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                fill: true
            }, {
                label: 'Xuất kho',
                data: [],
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Khởi tạo biểu đồ phân tích
 */
function initAnalysisChart() {
    const ctx = document.getElementById('analysisChart').getContext('2d');
    analysisChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Dữ liệu',
                data: [],
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Khởi tạo biểu đồ tài chính
 */
function initFinancialChart() {
    const ctx = document.getElementById('financialChart').getContext('2d');
    financialChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Doanh thu',
                data: [],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                fill: true
            }, {
                label: 'Chi phí',
                data: [],
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Khởi tạo biểu đồ hiệu suất
 */
function initPerformanceCharts() {
    // Biểu đồ hiệu suất theo thời gian
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    performanceChart = new Chart(performanceCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Hiệu suất (%)',
                data: [],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    
    // Biểu đồ so sánh hiệu suất
    const comparisonCtx = document.getElementById('performanceComparisonChart').getContext('2d');
    performanceComparisonChart = new Chart(comparisonCtx, {
        type: 'radar',
        data: {
            labels: ['Tỷ lệ lấp đầy', 'Thời gian xử lý', 'Độ chính xác', 'Vòng quay kho', 'Hiệu suất tổng'],
            datasets: [{
                label: 'Hiện tại',
                data: [],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.2)',
                pointBackgroundColor: '#667eea'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

/**
 * Tải dữ liệu báo cáo tồn kho
 */
async function loadInventoryReportData() {
    try {
        showLoading('inventory');
        
        const filters = getInventoryFilters();
        const response = await fetch('api/report_handler.php?action=inventory_report', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(filters)
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateInventoryStats(data.data.stats);
            updateInventoryCharts(data.data.charts);
            updateInventoryTable(data.data.details);
        } else {
            showError(data.message || 'Có lỗi xảy ra khi tải dữ liệu');
        }
        
        hideLoading('inventory');
        
    } catch (error) {
        console.error('Error loading inventory report:', error);
        showError('Có lỗi xảy ra khi tải báo cáo tồn kho');
        hideLoading('inventory');
    }
}

/**
 * Lấy filters cho báo cáo tồn kho
 */
function getInventoryFilters() {
    return {
        fromDate: document.getElementById('inventoryFromDate').value,
        toDate: document.getElementById('inventoryToDate').value,
        category: document.getElementById('inventoryCategory').value,
        area: document.getElementById('inventoryArea').value
    };
}

/**
 * Cập nhật thống kê tồn kho
 */
function updateInventoryStats(stats) {
    document.getElementById('totalInventoryItems').textContent = formatNumber(stats.totalItems);
    document.getElementById('totalInventoryValue').textContent = formatCurrency(stats.totalValue);
    document.getElementById('lowStockItems').textContent = formatNumber(stats.lowStockItems);
    document.getElementById('outOfStockItems').textContent = formatNumber(stats.outOfStockItems);
}

/**
 * Cập nhật biểu đồ tồn kho
 */
function updateInventoryCharts(chartsData) {
    // Cập nhật biểu đồ xu hướng
    inventoryTrendChart.data.labels = chartsData.trend.labels;
    inventoryTrendChart.data.datasets[0].data = chartsData.trend.data;
    inventoryTrendChart.update();
    
    // Cập nhật biểu đồ phân bố
    categoryDistributionChart.data.labels = chartsData.distribution.labels;
    categoryDistributionChart.data.datasets[0].data = chartsData.distribution.data;
    categoryDistributionChart.update();
}

/**
 * Cập nhật bảng chi tiết tồn kho
 */
function updateInventoryTable(details) {
    const tbody = document.querySelector('#inventoryDetailTable tbody');
    tbody.innerHTML = '';
    
    details.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.sku}</td>
            <td>${item.product_name}</td>
            <td>${item.category_name}</td>
            <td>${formatNumber(item.stock_quantity)}</td>
            <td>${formatCurrency(item.total_value)}</td>
            <td>${item.area_name || 'N/A'}</td>
            <td>
                <span class="badge ${getStockStatusClass(item.stock_quantity, item.min_stock)}">
                    ${getStockStatusText(item.stock_quantity, item.min_stock)}
                </span>
            </td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Lấy class CSS cho trạng thái tồn kho
 */
function getStockStatusClass(current, min) {
    if (current === 0) return 'bg-danger';
    if (current <= min) return 'bg-warning';
    return 'bg-success';
}

/**
 * Lấy text cho trạng thái tồn kho
 */
function getStockStatusText(current, min) {
    if (current === 0) return 'Hết hàng';
    if (current <= min) return 'Tồn kho thấp';
    return 'Đầy đủ';
}

/**
 * Áp dụng bộ lọc tồn kho
 */
function applyInventoryFilter() {
    loadInventoryReportData();
}

/**
 * Reset bộ lọc tồn kho
 */
function resetInventoryFilter() {
    document.getElementById('inventoryFromDate').value = '';
    document.getElementById('inventoryToDate').value = '';
    document.getElementById('inventoryCategory').value = '';
    document.getElementById('inventoryArea').value = '';
    loadInventoryReportData();
}

/**
 * Tải dữ liệu báo cáo nhập/xuất
 */
async function loadImportExportReportData() {
    try {
        showLoading('import-export');
        
        const filters = getImportExportFilters();
        const response = await fetch('api/report_handler.php?action=import_export_report', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(filters)
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateImportExportStats(data.data.stats);
            updateImportExportChart(data.data.chart);
            updateImportExportTable(data.data.details);
        }
        
        hideLoading('import-export');
        
    } catch (error) {
        console.error('Error loading import/export report:', error);
        showError('Có lỗi xảy ra khi tải báo cáo nhập/xuất');
        hideLoading('import-export');
    }
}

/**
 * Lấy filters cho báo cáo nhập/xuất
 */
function getImportExportFilters() {
    return {
        reportType: document.getElementById('reportType').value,
        fromDate: document.getElementById('ieFromDate').value,
        toDate: document.getElementById('ieToDate').value,
        product: document.getElementById('ieProduct').value,
        supplier: document.getElementById('ieSupplier').value,
        user: document.getElementById('ieUser').value
    };
}

/**
 * Cập nhật thống kê nhập/xuất
 */
function updateImportExportStats(stats) {
    document.getElementById('totalImportOrders').textContent = formatNumber(stats.totalImportOrders);
    document.getElementById('totalExportOrders').textContent = formatNumber(stats.totalExportOrders);
    document.getElementById('totalImportValue').textContent = formatCurrency(stats.totalImportValue);
    document.getElementById('totalExportValue').textContent = formatCurrency(stats.totalExportValue);
}

/**
 * Tạo phân tích
 */
async function generateAnalysis() {
    try {
        const analysisType = document.getElementById('analysisType').value;
        const period = document.getElementById('analysisPeriod').value;
        const category = document.getElementById('analysisCategory').value;
        const topProducts = document.getElementById('topProducts').value;
        
        showLoading('analysis');
        
        const response = await fetch('api/report_handler.php?action=generate_analysis', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                analysisType,
                period,
                category,
                topProducts
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateAnalysisChart(data.data.chart, analysisType);
            updateAnalysisSummary(data.data.summary);
            updateForecast(data.data.forecast);
        }
        
        hideLoading('analysis');
        
    } catch (error) {
        console.error('Error generating analysis:', error);
        showError('Có lỗi xảy ra khi tạo phân tích');
        hideLoading('analysis');
    }
}

/**
 * Cập nhật biểu đồ phân tích
 */
function updateAnalysisChart(chartData, analysisType) {
    const titleMap = {
        'consumption': 'Xu hướng tiêu thụ',
        'demand': 'Dự báo nhu cầu',
        'seasonal': 'Phân tích theo mùa',
        'performance': 'Hiệu suất sản phẩm'
    };
    
    document.getElementById('analysisChartTitle').textContent = titleMap[analysisType];
    
    analysisChart.data.labels = chartData.labels;
    analysisChart.data.datasets[0].data = chartData.data;
    analysisChart.update();
}

/**
 * Cập nhật tóm tắt phân tích
 */
function updateAnalysisSummary(summary) {
    const summaryDiv = document.getElementById('analysisSummary');
    summaryDiv.innerHTML = `
        <div class="summary-item">
            <h6>Kết quả chính:</h6>
            <p>${summary.mainResult}</p>
        </div>
        <div class="summary-item">
            <h6>Xu hướng:</h6>
            <p>${summary.trend}</p>
        </div>
        <div class="summary-item">
            <h6>Điểm nổi bật:</h6>
            <ul>
                ${summary.highlights.map(item => `<li>${item}</li>`).join('')}
            </ul>
        </div>
    `;
}

/**
 * Cập nhật dự báo
 */
function updateForecast(forecast) {
    const forecastDiv = document.getElementById('forecastContent');
    forecastDiv.innerHTML = `
        <div class="forecast-item">
            <h6>Dự báo:</h6>
            <p>${forecast.prediction}</p>
        </div>
        <div class="forecast-item">
            <h6>Khuyến nghị:</h6>
            <ul>
                ${forecast.recommendations.map(item => `<li>${item}</li>`).join('')}
            </ul>
        </div>
        <div class="forecast-item">
            <h6>Rủi ro tiềm ẩn:</h6>
            <ul>
                ${forecast.risks.map(item => `<li>${item}</li>`).join('')}
            </ul>
        </div>
    `;
}

/**
 * Tạo báo cáo tài chính
 */
async function generateFinancialReport() {
    try {
        const filters = {
            fromDate: document.getElementById('financialFromDate').value,
            toDate: document.getElementById('financialToDate').value,
            reportType: document.getElementById('financialReportType').value,
            groupBy: document.getElementById('financialGroupBy').value
        };
        
        showLoading('financial');
        
        const response = await fetch('api/report_handler.php?action=financial_report', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(filters)
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateFinancialStats(data.data.stats);
            updateFinancialChart(data.data.chart);
        }
        
        hideLoading('financial');
        
    } catch (error) {
        console.error('Error generating financial report:', error);
        showError('Có lỗi xảy ra khi tạo báo cáo tài chính');
        hideLoading('financial');
    }
}

/**
 * Cập nhật thống kê tài chính
 */
function updateFinancialStats(stats) {
    document.getElementById('totalRevenue').textContent = formatCurrency(stats.totalRevenue);
    document.getElementById('totalCost').textContent = formatCurrency(stats.totalCost);
    document.getElementById('totalProfit').textContent = formatCurrency(stats.totalProfit);
    document.getElementById('profitMargin').textContent = stats.profitMargin.toFixed(1) + '%';
}

/**
 * Tải dữ liệu hiệu suất
 */
async function loadPerformanceData() {
    try {
        showLoading('performance');
        
        const response = await fetch('api/report_handler.php?action=performance_report');
        const data = await response.json();
        
        if (data.success) {
            updatePerformanceStats(data.data.stats);
            updatePerformanceCharts(data.data.charts);
        }
        
        hideLoading('performance');
        
    } catch (error) {
        console.error('Error loading performance data:', error);
        showError('Có lỗi xảy ra khi tải dữ liệu hiệu suất');
        hideLoading('performance');
    }
}

/**
 * Cập nhật thống kê hiệu suất
 */
function updatePerformanceStats(stats) {
    document.getElementById('warehouseFillRate').textContent = stats.fillRate.toFixed(1) + '%';
    document.getElementById('avgProcessingTime').textContent = stats.avgProcessingTime.toFixed(1) + 'h';
    document.getElementById('accuracyRate').textContent = stats.accuracyRate.toFixed(1) + '%';
    document.getElementById('inventoryTurnover').textContent = stats.turnover.toFixed(1) + 'x';
}

/**
 * Cập nhật biểu đồ hiệu suất
 */
function updatePerformanceCharts(chartsData) {
    // Cập nhật biểu đồ theo thời gian
    performanceChart.data.labels = chartsData.timeline.labels;
    performanceChart.data.datasets[0].data = chartsData.timeline.data;
    performanceChart.update();
    
    // Cập nhật biểu đồ so sánh
    performanceComparisonChart.data.datasets[0].data = chartsData.comparison.data;
    performanceComparisonChart.update();
}

/**
 * Hiển thị modal tạo báo cáo tùy chỉnh
 */
function generateReport() {
    const modal = new bootstrap.Modal(document.getElementById('customReportModal'));
    modal.show();
    updateCustomFields();
}

/**
 * Hiển thị modal lập lịch báo cáo
 */
function scheduleReport() {
    const modal = new bootstrap.Modal(document.getElementById('scheduleReportModal'));
    modal.show();
}

/**
 * Cập nhật các trường tùy chỉnh
 */
function updateCustomFields() {
    const reportType = document.getElementById('customReportType').value;
    const fieldsContainer = document.getElementById('customFields');
    
    const fieldsByType = {
        inventory: [
            { id: 'include_sku', label: 'Mã SKU', checked: true },
            { id: 'include_name', label: 'Tên sản phẩm', checked: true },
            { id: 'include_category', label: 'Danh mục', checked: true },
            { id: 'include_stock', label: 'Tồn kho', checked: true },
            { id: 'include_value', label: 'Giá trị', checked: true },
            { id: 'include_location', label: 'Vị trí', checked: false }
        ],
        import_export: [
            { id: 'include_order_code', label: 'Mã phiếu', checked: true },
            { id: 'include_date', label: 'Ngày tạo', checked: true },
            { id: 'include_product', label: 'Sản phẩm', checked: true },
            { id: 'include_quantity', label: 'Số lượng', checked: true },
            { id: 'include_value', label: 'Giá trị', checked: true },
            { id: 'include_user', label: 'Người tạo', checked: false }
        ],
        financial: [
            { id: 'include_revenue', label: 'Doanh thu', checked: true },
            { id: 'include_cost', label: 'Chi phí', checked: true },
            { id: 'include_profit', label: 'Lợi nhuận', checked: true },
            { id: 'include_margin', label: 'Tỷ suất lợi nhuận', checked: true }
        ],
        performance: [
            { id: 'include_fill_rate', label: 'Tỷ lệ lấp đầy', checked: true },
            { id: 'include_processing_time', label: 'Thời gian xử lý', checked: true },
            { id: 'include_accuracy', label: 'Độ chính xác', checked: true },
            { id: 'include_turnover', label: 'Vòng quay kho', checked: true }
        ]
    };
    
    const fields = fieldsByType[reportType] || [];
    fieldsContainer.innerHTML = '';
    
    fields.forEach(field => {
        const col = document.createElement('div');
        col.className = 'col-md-6';
        col.innerHTML = `
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="${field.id}" ${field.checked ? 'checked' : ''}>
                <label class="form-check-label" for="${field.id}">
                    ${field.label}
                </label>
            </div>
        `;
        fieldsContainer.appendChild(col);
    });
}

/**
 * Tạo báo cáo tùy chỉnh
 */
async function generateCustomReport() {
    try {
        const formData = {
            name: document.getElementById('customReportName').value,
            type: document.getElementById('customReportType').value,
            fromDate: document.getElementById('customFromDate').value,
            toDate: document.getElementById('customToDate').value,
            fields: []
        };
        
        // Lấy các trường đã chọn
        document.querySelectorAll('#customFields input[type="checkbox"]:checked').forEach(checkbox => {
            formData.fields.push(checkbox.id);
        });
        
        if (!formData.name || !formData.fromDate || !formData.toDate) {
            showError('Vui lòng điền đầy đủ thông tin');
            return;
        }
        
        const response = await fetch('api/report_handler.php?action=generate_custom_report', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Mở báo cáo trong tab mới
            window.open(data.data.reportUrl, '_blank');
            
            // Đóng modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('customReportModal'));
            modal.hide();
            
            showSuccess('Báo cáo đã được tạo thành công');
        } else {
            showError(data.message || 'Có lỗi xảy ra khi tạo báo cáo');
        }
        
    } catch (error) {
        console.error('Error generating custom report:', error);
        showError('Có lỗi xảy ra khi tạo báo cáo');
    }
}

/**
 * Tạo lịch báo cáo
 */
async function createSchedule() {
    try {
        const scheduleData = {
            name: document.getElementById('scheduleName').value,
            reportType: document.getElementById('scheduleReportType').value,
            frequency: document.getElementById('scheduleFrequency').value,
            email: document.getElementById('scheduleEmail').value,
            time: document.getElementById('scheduleTime').value
        };
        
        if (!scheduleData.name || !scheduleData.email || !scheduleData.time) {
            showError('Vui lòng điền đầy đủ thông tin');
            return;
        }
        
        const response = await fetch('api/report_handler.php?action=create_schedule', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(scheduleData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Đóng modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('scheduleReportModal'));
            modal.hide();
            
            showSuccess('Lịch báo cáo đã được tạo thành công');
        } else {
            showError(data.message || 'Có lỗi xảy ra khi tạo lịch báo cáo');
        }
        
    } catch (error) {
        console.error('Error creating schedule:', error);
        showError('Có lỗi xảy ra khi tạo lịch báo cáo');
    }
}

// Export functions
function exportInventoryReport() {
    exportReport('inventory', 'excel');
}

function exportInventoryPDF() {
    exportReport('inventory', 'pdf');
}

function exportImportExportReport() {
    exportReport('import_export', 'excel');
}

function exportImportExportPDF() {
    exportReport('import_export', 'pdf');
}

function exportAnalysisReport() {
    exportReport('analysis', 'excel');
}

function exportFinancialReport() {
    exportReport('financial', 'excel');
}

function exportFinancialPDF() {
    exportReport('financial', 'pdf');
}

/**
 * Xuất báo cáo
 */
function exportReport(type, format) {
    const params = new URLSearchParams({
        action: 'export_report',
        type: type,
        format: format
    });
    
    // Thêm filters tùy theo loại báo cáo
    switch(type) {
        case 'inventory':
            Object.assign(params, getInventoryFilters());
            break;
        case 'import_export':
            Object.assign(params, getImportExportFilters());
            break;
        case 'financial':
            params.append('fromDate', document.getElementById('financialFromDate').value);
            params.append('toDate', document.getElementById('financialToDate').value);
            params.append('reportType', document.getElementById('financialReportType').value);
            break;
    }
    
    window.open(`api/report_handler.php?${params.toString()}`, '_blank');
}

// Apply filter functions cho từng tab
function applyImportExportFilter() {
    loadImportExportReportData();
}

function resetImportExportFilter() {
    document.getElementById('reportType').value = 'both';
    document.getElementById('ieFromDate').value = '';
    document.getElementById('ieToDate').value = '';
    document.getElementById('ieProduct').value = '';
    document.getElementById('ieSupplier').value = '';
    document.getElementById('ieUser').value = '';
    loadImportExportReportData();
}

// Utility functions
function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function formatNumber(num) {
    return new Intl.NumberFormat('vi-VN').format(num);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

function showLoading(section) {
    // Hiển thị loading cho section cụ thể
    console.log(`Loading ${section}...`);
}

function hideLoading(section) {
    // Ẩn loading cho section cụ thể
    console.log(`Loaded ${section}.`);
}

function showError(message) {
    alert(message); // Có thể thay thế bằng toast notification
}

function showSuccess(message) {
    alert(message); // Có thể thay thế bằng toast notification
} 