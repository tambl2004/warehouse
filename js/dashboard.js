/**
 * Dashboard JavaScript
 * Xử lý biểu đồ, thống kê và cảnh báo cho trang dashboard
 */

// Biến toàn cục
let inventoryChart = null;
let distributionChart = null;
let trendChart = null;

// Khởi tạo dashboard
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
    loadDashboardData();
    setupEventListeners();
});

/**
 * Khởi tạo dashboard
 */
function initializeDashboard() {
    console.log('Initializing Dashboard...');
    
    // Hiển thị loading
    showLoading();
    
    // Khởi tạo biểu đồ
    initializeCharts();
}

/**
 * Thiết lập event listeners
 */
function setupEventListeners() {
    // Lắng nghe thay đổi period cho trend chart
    document.querySelectorAll('input[name="trendPeriod"]').forEach(radio => {
        radio.addEventListener('change', function() {
            updateTrendChart(this.value);
        });
    });
    
    // Auto refresh mỗi 5 phút
    setInterval(refreshDashboard, 300000);
}

/**
 * Tải dữ liệu dashboard
 */
async function loadDashboardData() {
    try {
        // Tải thống kê tổng quan
        await loadOverviewStats();
        
        // Tải dữ liệu biểu đồ
        await loadChartsData();
        
        // Tải cảnh báo
        await loadAlerts();
        
        // Tải hoạt động gần đây
        await loadRecentActivities();
        
        // Tải top sản phẩm
        await loadTopProducts();
        
        // Tải sản phẩm tồn kho thấp
        await loadLowStockProducts();
        
        hideLoading();
        
    } catch (error) {
        console.error('Error loading dashboard data:', error);
        showError('Có lỗi xảy ra khi tải dữ liệu dashboard');
        hideLoading();
    }
}

/**
 * Tải thống kê tổng quan
 */
async function loadOverviewStats() {
    try {
        const response = await fetch('api/dashboard_handler.php?action=overview_stats');
        const data = await response.json();
        
        if (data.success) {
            const stats = data.data;
            
            // Cập nhật số liệu
            document.getElementById('totalProducts').textContent = formatNumber(stats.total_products);
            document.getElementById('totalValue').textContent = formatCurrency(stats.total_value);
            document.getElementById('monthlyImports').textContent = formatNumber(stats.monthly_imports);
            document.getElementById('monthlyExports').textContent = formatNumber(stats.monthly_exports);
            
            // Cập nhật % thay đổi
            updateChange('productsChange', stats.products_change);
            updateChange('valueChange', stats.value_change);
            updateChange('importsChange', stats.imports_change);
            updateChange('exportsChange', stats.exports_change);
        }
    } catch (error) {
        console.error('Error loading overview stats:', error);
    }
}

/**
 * Tải dữ liệu biểu đồ
 */
async function loadChartsData() {
    const period = document.getElementById('chartPeriod').value;
    
    try {
        // Tải dữ liệu tồn kho theo danh mục
        const inventoryResponse = await fetch(`api/dashboard_handler.php?action=inventory_by_category&period=${period}`);
        const inventoryData = await inventoryResponse.json();
        
        if (inventoryData.success) {
            updateInventoryChart(inventoryData.data);
        }
        
        // Tải dữ liệu phân bố sản phẩm
        const distributionResponse = await fetch('api/dashboard_handler.php?action=product_distribution');
        const distributionData = await distributionResponse.json();
        
        if (distributionData.success) {
            updateDistributionChart(distributionData.data);
        }
        
        // Tải dữ liệu xu hướng nhập/xuất
        const trendPeriod = document.querySelector('input[name="trendPeriod"]:checked').value;
        await updateTrendChart(trendPeriod);
        
    } catch (error) {
        console.error('Error loading charts data:', error);
    }
}

/**
 * Khởi tạo biểu đồ
 */
function initializeCharts() {
    // Biểu đồ tồn kho theo danh mục
    const inventoryCtx = document.getElementById('inventoryChart').getContext('2d');
    inventoryChart = new Chart(inventoryCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Số lượng tồn kho',
                data: [],
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 1,
                borderRadius: 8
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
                        display: false
                    }
                }
            }
        }
    });
    
    // Biểu đồ phân bố sản phẩm
    const distributionCtx = document.getElementById('distributionChart').getContext('2d');
    distributionChart = new Chart(distributionCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#667eea', '#764ba2', '#f093fb', '#f5576c',
                    '#4facfe', '#00f2fe', '#43e97b', '#38f9d7'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    });
    
    // Biểu đồ xu hướng
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    trendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Nhập kho',
                data: [],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Xuất kho',
                data: [],
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
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
                        display: false
                    }
                }
            }
        }
    });
}

/**
 * Cập nhật biểu đồ tồn kho
 */
function updateInventoryChart(data) {
    inventoryChart.data.labels = data.labels;
    inventoryChart.data.datasets[0].data = data.values;
    inventoryChart.update();
}

/**
 * Cập nhật biểu đồ phân bố
 */
function updateDistributionChart(data) {
    distributionChart.data.labels = data.labels;
    distributionChart.data.datasets[0].data = data.values;
    distributionChart.update();
}

/**
 * Cập nhật biểu đồ xu hướng
 */
async function updateTrendChart(period) {
    try {
        const response = await fetch(`api/dashboard_handler.php?action=import_export_trend&period=${period}`);
        const data = await response.json();
        
        if (data.success) {
            trendChart.data.labels = data.data.labels;
            trendChart.data.datasets[0].data = data.data.imports;
            trendChart.data.datasets[1].data = data.data.exports;
            trendChart.update();
        }
    } catch (error) {
        console.error('Error updating trend chart:', error);
    }
}

/**
 * Tải cảnh báo
 */
async function loadAlerts() {
    try {
        const response = await fetch('api/dashboard_handler.php?action=alerts');
        const data = await response.json();
        
        if (data.success) {
            displayAlerts(data.data);
        }
    } catch (error) {
        console.error('Error loading alerts:', error);
    }
}

/**
 * Hiển thị cảnh báo
 */
function displayAlerts(alerts) {
    const alertsList = document.getElementById('alertsList');
    const alertCount = document.getElementById('alertCount');
    
    alertCount.textContent = alerts.length;
    
    if (alerts.length === 0) {
        alertsList.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                <p>Không có cảnh báo nào</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    alerts.forEach(alert => {
        const alertClass = alert.type === 'danger' ? 'danger' : 'warning';
        const icon = alert.type === 'danger' ? 'exclamation-triangle' : 'exclamation-circle';
        
        html += `
            <div class="alert-item ${alertClass}">
                <div class="alert-icon text-${alert.type === 'danger' ? 'danger' : 'warning'}">
                    <i class="fas fa-${icon}"></i>
                </div>
                <div class="alert-content">
                    <h6>${alert.title}</h6>
                    <p>${alert.message}</p>
                </div>
            </div>
        `;
    });
    
    alertsList.innerHTML = html;
}

/**
 * Tải hoạt động gần đây
 */
async function loadRecentActivities() {
    try {
        const response = await fetch('api/dashboard_handler.php?action=recent_activities');
        const data = await response.json();
        
        if (data.success) {
            displayRecentActivities(data.data);
        }
    } catch (error) {
        console.error('Error loading recent activities:', error);
    }
}

/**
 * Hiển thị hoạt động gần đây
 */
function displayRecentActivities(activities) {
    const activitiesList = document.getElementById('recentActivities');
    
    if (activities.length === 0) {
        activitiesList.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-history fa-2x mb-3"></i>
                <p>Chưa có hoạt động nào</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    activities.forEach(activity => {
        html += `
            <div class="activity-item">
                <div class="activity-avatar">
                    ${activity.user_name.charAt(0).toUpperCase()}
                </div>
                <div class="activity-content">
                    <h6>${activity.user_name}</h6>
                    <p>${activity.description}</p>
                </div>
                <div class="activity-time">
                    ${formatTimeAgo(activity.action_time)}
                </div>
            </div>
        `;
    });
    
    activitiesList.innerHTML = html;
}

/**
 * Tải top sản phẩm
 */
async function loadTopProducts() {
    try {
        const response = await fetch('api/dashboard_handler.php?action=top_products');
        const data = await response.json();
        
        if (data.success) {
            displayTopProducts(data.data);
        }
    } catch (error) {
        console.error('Error loading top products:', error);
    }
}

/**
 * Hiển thị top sản phẩm
 */
function displayTopProducts(products) {
    const productsList = document.getElementById('topProductsList');
    
    if (products.length === 0) {
        productsList.innerHTML = `
            <tr>
                <td colspan="3" class="text-center text-muted py-3">
                    Chưa có dữ liệu xuất kho
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    products.forEach(product => {
        html += `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        ${product.image_url ? 
                            `<img src="${product.image_url}" alt="${product.product_name}" class="me-2" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">` 
                            : '<div class="me-2" style="width: 30px; height: 30px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-box text-muted"></i></div>'
                        }
                        <div>
                            <div class="fw-medium">${product.product_name}</div>
                            <small class="text-muted">${product.sku}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-success">${formatNumber(product.total_exported)}</span>
                </td>
                <td>
                    <span class="badge ${product.stock_quantity < 10 ? 'bg-danger' : product.stock_quantity < 50 ? 'bg-warning' : 'bg-success'}">${formatNumber(product.stock_quantity)}</span>
                </td>
            </tr>
        `;
    });
    
    productsList.innerHTML = html;
}

/**
 * Tải sản phẩm tồn kho thấp
 */
async function loadLowStockProducts() {
    try {
        const response = await fetch('api/dashboard_handler.php?action=low_stock_products');
        const data = await response.json();
        
        if (data.success) {
            displayLowStockProducts(data.data);
        }
    } catch (error) {
        console.error('Error loading low stock products:', error);
    }
}

/**
 * Hiển thị sản phẩm tồn kho thấp
 */
function displayLowStockProducts(products) {
    const productsList = document.getElementById('lowStockList');
    
    if (products.length === 0) {
        productsList.innerHTML = `
            <tr>
                <td colspan="3" class="text-center text-muted py-3">
                    <i class="fas fa-check-circle text-success"></i>
                    Tất cả sản phẩm đều có tồn kho đầy đủ
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    products.forEach(product => {
        const stockStatus = product.stock_quantity === 0 ? 'Hết hàng' : 
                           product.stock_quantity < 5 ? 'Rất thấp' : 'Thấp';
        const badgeClass = product.stock_quantity === 0 ? 'bg-danger' : 
                          product.stock_quantity < 5 ? 'bg-danger' : 'bg-warning';
        
        html += `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        ${product.image_url ? 
                            `<img src="${product.image_url}" alt="${product.product_name}" class="me-2" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">` 
                            : '<div class="me-2" style="width: 30px; height: 30px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-box text-muted"></i></div>'
                        }
                        <div>
                            <div class="fw-medium">${product.product_name}</div>
                            <small class="text-muted">${product.sku}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="fw-bold">${formatNumber(product.stock_quantity)}</span>
                </td>
                <td>
                    <span class="badge ${badgeClass}">${stockStatus}</span>
                </td>
            </tr>
        `;
    });
    
    productsList.innerHTML = html;
}

/**
 * Làm mới dashboard
 */
function refreshDashboard() {
    console.log('Refreshing dashboard...');
    showLoading();
    loadDashboardData();
}

/**
 * Cập nhật biểu đồ
 */
function updateCharts() {
    loadChartsData();
}

/**
 * Xuất dashboard
 */
function exportDashboard() {
    window.open('api/dashboard_handler.php?action=export_dashboard', '_blank');
}

/**
 * Cập nhật % thay đổi
 */
function updateChange(elementId, change) {
    const element = document.getElementById(elementId);
    const isPositive = change >= 0;
    
    element.textContent = `${isPositive ? '+' : ''}${change.toFixed(1)}%`;
    element.className = `stats-change ${isPositive ? 'positive' : 'negative'}`;
}

/**
 * Hiển thị loading
 */
function showLoading() {
    // Có thể thêm loading spinner
}

/**
 * Ẩn loading
 */
function hideLoading() {
    // Ẩn loading spinner
}

/**
 * Hiển thị lỗi
 */
function showError(message) {
    // Hiển thị thông báo lỗi
    console.error(message);
}

/**
 * Format số
 */
function formatNumber(num) {
    return new Intl.NumberFormat('vi-VN').format(num);
}

/**
 * Format tiền tệ
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

/**
 * Format thời gian từ trước
 */
function formatTimeAgo(datetime) {
    const now = new Date();
    const time = new Date(datetime);
    const diffMs = now - time;
    const diffMins = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    
    if (diffMins < 1) return 'Vừa xong';
    if (diffMins < 60) return `${diffMins} phút trước`;
    if (diffHours < 24) return `${diffHours} giờ trước`;
    return `${diffDays} ngày trước`;
} 