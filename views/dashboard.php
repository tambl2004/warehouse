<?php
require_once 'inc/auth.php';

?>

<!-- Dashboard Hệ thống Quản lý Kho -->
<div class="function-container">
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-tachometer-alt text-primary me-3"></i>
                        Dashboard - Tổng quan Hệ thống
                    </h1>
                    <p class="text-muted">Theo dõi hoạt động kho hàng theo thời gian thực</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt me-2"></i>Làm mới
                    </button>
                    <button class="btn btn-primary" onclick="exportDashboard()">
                        <i class="fas fa-download me-2"></i>Xuất báo cáo
                    </button>
                </div>
            </div>
        </div>

        <!-- Thống kê tổng quan -->
        <div class="overview-stats">
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card bg-primary text-white">
                        <div class="stats-card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="stats-title">Tổng sản phẩm</h6>
                                    <h2 class="stats-value" id="totalProducts">0</h2>
                                    <small class="stats-change positive" id="productsChange">+0%</small>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-boxes"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stats-card bg-success text-white">
                        <div class="stats-card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="stats-title">Giá trị tồn kho</h6>
                                    <h2 class="stats-value" id="totalValue">0đ</h2>
                                    <small class="stats-change positive" id="valueChange">+0%</small>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stats-card bg-info text-white">
                        <div class="stats-card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="stats-title">Phiếu nhập tháng</h6>
                                    <h2 class="stats-value" id="monthlyImports">0</h2>
                                    <small class="stats-change positive" id="importsChange">+0%</small>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-arrow-down"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stats-card bg-warning text-white">
                        <div class="stats-card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="stats-title">Phiếu xuất tháng</h6>
                                    <h2 class="stats-value" id="monthlyExports">0</h2>
                                    <small class="stats-change negative" id="exportsChange">+0%</small>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-arrow-up"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Biểu đồ và thống kê -->
        <div class="row g-4 mb-4">
            <!-- Biểu đồ tồn kho theo danh mục -->
            <div class="col-xl-8">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <h5 class="chart-title">
                            <i class="fas fa-chart-bar me-2"></i>
                            Tồn kho theo danh mục
                        </h5>
                        <div class="chart-filters">
                            <select class="form-select form-select-sm" id="chartPeriod" onchange="updateCharts()">
                                <option value="7">7 ngày qua</option>
                                <option value="30" selected>30 ngày qua</option>
                                <option value="90">3 tháng qua</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-card-body">
                        <canvas id="inventoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Biểu đồ tròn phân bố sản phẩm -->
            <div class="col-xl-4">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <h5 class="chart-title">
                            <i class="fas fa-chart-pie me-2"></i>
                            Phân bố sản phẩm
                        </h5>
                    </div>
                    <div class="chart-card-body">
                        <canvas id="distributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Biểu đồ nhập/xuất kho -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <h5 class="chart-title">
                            <i class="fas fa-chart-line me-2"></i>
                            Xu hướng nhập/xuất kho
                        </h5>
                        <div class="chart-filters">
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="trendPeriod" id="trend7" value="7">
                                <label class="btn btn-outline-primary btn-sm" for="trend7">7 ngày</label>

                                <input type="radio" class="btn-check" name="trendPeriod" id="trend30" value="30" checked>
                                <label class="btn btn-outline-primary btn-sm" for="trend30">30 ngày</label>

                                <input type="radio" class="btn-check" name="trendPeriod" id="trend90" value="90">
                                <label class="btn btn-outline-primary btn-sm" for="trend90">3 tháng</label>
                            </div>
                        </div>
                    </div>
                    <div class="chart-card-body">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cảnh báo và hoạt động gần đây -->
        <div class="row g-4">
            <!-- Cảnh báo -->
            <div class="col-xl-6">
                <div class="alert-card">
                    <div class="alert-card-header">
                        <h5 class="alert-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Cảnh báo hệ thống
                        </h5>
                        <span class="alert-count badge bg-danger" id="alertCount">0</span>
                    </div>
                    <div class="alert-card-body" id="alertsList">
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-check-circle fa-3x mb-3"></i>
                            <p>Không có cảnh báo nào</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hoạt động gần đây -->
            <div class="col-xl-6">
                <div class="activity-card">
                    <div class="activity-card-header">
                        <h5 class="activity-title">
                            <i class="fas fa-history me-2"></i>
                            Hoạt động gần đây
                        </h5>
                        <a href="?option=nguoidung" class="btn btn-outline-primary btn-sm">
                            Xem tất cả
                        </a>
                    </div>
                    <div class="activity-card-body" id="recentActivities">
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p>Đang tải...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sản phẩm bán chạy và tồn kho thấp -->
        <div class="row g-4 mt-2">
            <!-- Top sản phẩm -->
            <div class="col-xl-6">
                <div class="products-card">
                    <div class="products-card-header">
                        <h5 class="products-title">
                            <i class="fas fa-star me-2"></i>
                            Sản phẩm xuất nhiều nhất
                        </h5>
                    </div>
                    <div class="products-card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Đã xuất</th>
                                        <th>Tồn kho</th>
                                    </tr>
                                </thead>
                                <tbody id="topProductsList">
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">
                                            <i class="fas fa-spinner fa-spin"></i> Đang tải...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tồn kho thấp -->
            <div class="col-xl-6">
                <div class="products-card">
                    <div class="products-card-header">
                        <h5 class="products-title">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Sản phẩm tồn kho thấp
                        </h5>
                    </div>
                    <div class="products-card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Tồn kho</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody id="lowStockList">
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">
                                            <i class="fas fa-spinner fa-spin"></i> Đang tải...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/dashboard.js"></script>

<style>
/* Dashboard Styles */
.dashboard-container {
    padding: 20px;
}

.dashboard-header .page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

/* Stats Cards */
.stats-card {
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: rgba(255,255,255,0.3);
}

.stats-card-body {
    padding: 1.5rem;
}

.stats-title {
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    opacity: 0.9;
}

.stats-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stats-icon {
    font-size: 2.5rem;
    opacity: 0.7;
}

.stats-change {
    font-size: 0.75rem;
    font-weight: 600;
}

.stats-change.positive {
    color: #28a745;
}

.stats-change.negative {
    color: #dc3545;
}

/* Chart Cards */
.chart-card, .alert-card, .activity-card, .products-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.chart-card-header, .alert-card-header, .activity-card-header, .products-card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chart-title, .alert-title, .activity-title, .products-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #495057;
    margin: 0;
}

.chart-card-body, .alert-card-body, .activity-card-body, .products-card-body {
    padding: 1.5rem;
}

.chart-filters .form-select {
    width: auto;
    min-width: 120px;
}

/* Alerts */
.alert-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    border-left: 4px solid transparent;
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
    transition: all 0.2s ease;
}

.alert-item:hover {
    background: #f8f9fa;
}

.alert-item.danger {
    border-left-color: #dc3545;
    background: rgba(220, 53, 69, 0.05);
}

.alert-item.warning {
    border-left-color: #ffc107;
    background: rgba(255, 193, 7, 0.05);
}

.alert-icon {
    font-size: 1.2rem;
    margin-right: 0.75rem;
}

.alert-content h6 {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.alert-content p {
    font-size: 0.8rem;
    color: #6c757d;
    margin: 0;
}

/* Activities */
.activity-item {
    display: flex;
    align-items: start;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #667eea;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-right: 0.75rem;
    flex-shrink: 0;
}

.activity-content h6 {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.activity-content p {
    font-size: 0.8rem;
    color: #6c757d;
    margin: 0;
}

.activity-time {
    font-size: 0.75rem;
    color: #adb5bd;
    margin-left: auto;
    flex-shrink: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 15px;
    }
    
    .dashboard-header .page-title {
        font-size: 1.5rem;
    }
    
    .stats-value {
        font-size: 1.5rem;
    }
    
    .chart-card-header, .alert-card-header, .activity-card-header, .products-card-header {
        flex-direction: column;
        gap: 0.5rem;
        align-items: start;
    }
}

/* Loading animation */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.loading {
    animation: pulse 1.5s infinite;
}
</style>