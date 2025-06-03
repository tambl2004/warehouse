<?php
require_once 'inc/auth.php';

?>

<!-- Module Báo cáo và Thống kê -->
<div class="function-container">
    <div class="report-management">
        <!-- Header -->
        <div class="function-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-chart-line text-primary me-3"></i>
                        Báo cáo và Thống kê
                    </h1>
                    <p class="text-muted">Phân tích dữ liệu và tạo báo cáo tùy chỉnh</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-success" onclick="scheduleReport()">
                        <i class="fas fa-clock me-2"></i>Lập lịch
                    </button>
                    <button class="btn btn-primary" onclick="generateReport()">
                        <i class="fas fa-plus me-2"></i>Tạo báo cáo
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs chính -->
        <ul class="nav nav-tabs" id="reportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab">
                    <i class="fas fa-boxes me-2"></i>Báo cáo Tồn kho
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="import-export-tab" data-bs-toggle="tab" data-bs-target="#import-export" type="button" role="tab">
                    <i class="fas fa-exchange-alt me-2"></i>Nhập/Xuất kho
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="analysis-tab" data-bs-toggle="tab" data-bs-target="#analysis" type="button" role="tab">
                    <i class="fas fa-chart-bar me-2"></i>Phân tích xu hướng
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button" role="tab">
                    <i class="fas fa-dollar-sign me-2"></i>Báo cáo tài chính
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performance" type="button" role="tab">
                    <i class="fas fa-tachometer-alt me-2"></i>Hiệu suất
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="reportTabsContent">
            <!-- Báo cáo Tồn kho -->
            <div class="tab-pane fade show active" id="inventory" role="tabpanel">
                <div class="row">
                    <!-- Bộ lọc -->
                    <div class="col-12 mb-4">
                        <div class="filter-card">
                            <h5 class="filter-title">
                                <i class="fas fa-filter me-2"></i>Bộ lọc báo cáo
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Từ ngày</label>
                                    <input type="date" class="form-control" id="inventoryFromDate">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Đến ngày</label>
                                    <input type="date" class="form-control" id="inventoryToDate">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Danh mục</label>
                                    <select class="form-select" id="inventoryCategory">
                                        <option value="">Tất cả danh mục</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Khu vực</label>
                                    <select class="form-select" id="inventoryArea">
                                        <option value="">Tất cả khu vực</option>
                                    </select>
                                </div>
                            </div>
                            <div class="filter-actions">
                                <button class="btn btn-primary" onclick="applyInventoryFilter()">
                                    <i class="fas fa-search me-2"></i>Lọc
                                </button>
                                <button class="btn btn-outline-secondary" onclick="resetInventoryFilter()">
                                    <i class="fas fa-refresh me-2"></i>Đặt lại
                                </button>
                                <button class="btn btn-success" onclick="exportInventoryReport()">
                                    <i class="fas fa-download me-2"></i>Xuất Excel
                                </button>
                                <button class="btn btn-danger" onclick="exportInventoryPDF()">
                                    <i class="fas fa-file-pdf me-2"></i>Xuất PDF
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Thống kê tổng quan -->
                    <div class="col-12 mb-4">
                        <div class="row g-4">
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card bg-primary text-white">
                                    <div class="stats-card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="stats-title">Tổng SP tồn kho</h6>
                                                <h2 class="stats-value" id="totalInventoryItems">0</h2>
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
                                                <h6 class="stats-title">Tổng giá trị</h6>
                                                <h2 class="stats-value" id="totalInventoryValue">0đ</h2>
                                            </div>
                                            <div class="stats-icon">
                                                <i class="fas fa-money-bill-wave"></i>
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
                                                <h6 class="stats-title">SP tồn kho thấp</h6>
                                                <h2 class="stats-value" id="lowStockItems">0</h2>
                                            </div>
                                            <div class="stats-icon">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card bg-danger text-white">
                                    <div class="stats-card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="stats-title">SP hết hàng</h6>
                                                <h2 class="stats-value" id="outOfStockItems">0</h2>
                                            </div>
                                            <div class="stats-icon">
                                                <i class="fas fa-times-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ -->
                    <div class="col-xl-8 mb-4">
                        <div class="chart-card">
                            <div class="chart-card-header">
                                <h5 class="chart-title">Tồn kho theo thời gian</h5>
                            </div>
                            <div class="chart-card-body">
                                <canvas id="inventoryTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 mb-4">
                        <div class="chart-card">
                            <div class="chart-card-header">
                                <h5 class="chart-title">Phân bố theo danh mục</h5>
                            </div>
                            <div class="chart-card-body">
                                <canvas id="categoryDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Bảng chi tiết -->
                    <div class="col-12">
                        <div class="data-table-container">
                            <h5 class="table-title">Chi tiết tồn kho</h5>
                            <div class="table-responsive">
                                <table class="table table-hover" id="inventoryDetailTable">
                                    <thead>
                                        <tr>
                                            <th>Mã SKU</th>
                                            <th>Tên sản phẩm</th>
                                            <th>Danh mục</th>
                                            <th>Tồn kho</th>
                                            <th>Giá trị</th>
                                            <th>Khu vực</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Báo cáo Nhập/Xuất kho -->
            <div class="tab-pane fade" id="import-export" role="tabpanel">
                <div class="row">
                    <!-- Bộ lọc -->
                    <div class="col-12 mb-4">
                        <div class="filter-card">
                            <h5 class="filter-title">
                                <i class="fas fa-filter me-2"></i>Bộ lọc báo cáo
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Loại báo cáo</label>
                                    <select class="form-select" id="reportType">
                                        <option value="both">Cả nhập và xuất</option>
                                        <option value="import">Chỉ nhập kho</option>
                                        <option value="export">Chỉ xuất kho</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Từ ngày</label>
                                    <input type="date" class="form-control" id="ieFromDate">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Đến ngày</label>
                                    <input type="date" class="form-control" id="ieToDate">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Sản phẩm</label>
                                    <select class="form-select" id="ieProduct">
                                        <option value="">Tất cả sản phẩm</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Nhà cung cấp</label>
                                    <select class="form-select" id="ieSupplier">
                                        <option value="">Tất cả nhà cung cấp</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Người tạo</label>
                                    <select class="form-select" id="ieUser">
                                        <option value="">Tất cả người dùng</option>
                                    </select>
                                </div>
                            </div>
                            <div class="filter-actions">
                                <button class="btn btn-primary" onclick="applyImportExportFilter()">
                                    <i class="fas fa-search me-2"></i>Lọc
                                </button>
                                <button class="btn btn-outline-secondary" onclick="resetImportExportFilter()">
                                    <i class="fas fa-refresh me-2"></i>Đặt lại
                                </button>
                                <button class="btn btn-success" onclick="exportImportExportReport()">
                                    <i class="fas fa-download me-2"></i>Xuất Excel
                                </button>
                                <button class="btn btn-danger" onclick="exportImportExportPDF()">
                                    <i class="fas fa-file-pdf me-2"></i>Xuất PDF
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Thống kê tổng quan -->
                    <div class="col-12 mb-4">
                        <div class="row g-4">
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card bg-info text-white">
                                    <div class="stats-card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="stats-title">Tổng phiếu nhập</h6>
                                                <h2 class="stats-value" id="totalImportOrders">0</h2>
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
                                                <h6 class="stats-title">Tổng phiếu xuất</h6>
                                                <h2 class="stats-value" id="totalExportOrders">0</h2>
                                            </div>
                                            <div class="stats-icon">
                                                <i class="fas fa-arrow-up"></i>
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
                                                <h6 class="stats-title">Giá trị nhập</h6>
                                                <h2 class="stats-value" id="totalImportValue">0đ</h2>
                                            </div>
                                            <div class="stats-icon">
                                                <i class="fas fa-plus-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card bg-danger text-white">
                                    <div class="stats-card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="stats-title">Giá trị xuất</h6>
                                                <h2 class="stats-value" id="totalExportValue">0đ</h2>
                                            </div>
                                            <div class="stats-icon">
                                                <i class="fas fa-minus-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ -->
                    <div class="col-12 mb-4">
                        <div class="chart-card">
                            <div class="chart-card-header">
                                <h5 class="chart-title">Xu hướng nhập/xuất kho</h5>
                            </div>
                            <div class="chart-card-body">
                                <canvas id="importExportChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Bảng chi tiết -->
                    <div class="col-12">
                        <div class="data-table-container">
                            <h5 class="table-title">Chi tiết nhập/xuất kho</h5>
                            <div class="table-responsive">
                                <table class="table table-hover" id="importExportDetailTable">
                                    <thead>
                                        <tr>
                                            <th>Mã phiếu</th>
                                            <th>Loại</th>
                                            <th>Ngày tạo</th>
                                            <th>Sản phẩm</th>
                                            <th>Số lượng</th>
                                            <th>Giá trị</th>
                                            <th>Người tạo</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Phân tích xu hướng -->
            <div class="tab-pane fade" id="analysis" role="tabpanel">
                <div class="row">
                    <!-- Bộ lọc -->
                    <div class="col-12 mb-4">
                        <div class="filter-card">
                            <h5 class="filter-title">
                                <i class="fas fa-filter me-2"></i>Tùy chọn phân tích
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Loại phân tích</label>
                                    <select class="form-select" id="analysisType">
                                        <option value="consumption">Xu hướng tiêu thụ</option>
                                        <option value="demand">Dự báo nhu cầu</option>
                                        <option value="seasonal">Phân tích theo mùa</option>
                                        <option value="performance">Hiệu suất sản phẩm</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Khoảng thời gian</label>
                                    <select class="form-select" id="analysisPeriod">
                                        <option value="7">7 ngày qua</option>
                                        <option value="30" selected>30 ngày qua</option>
                                        <option value="90">3 tháng qua</option>
                                        <option value="180">6 tháng qua</option>
                                        <option value="365">1 năm qua</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Danh mục</label>
                                    <select class="form-select" id="analysisCategory">
                                        <option value="">Tất cả danh mục</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Top sản phẩm</label>
                                    <select class="form-select" id="topProducts">
                                        <option value="10">Top 10</option>
                                        <option value="20">Top 20</option>
                                        <option value="50">Top 50</option>
                                    </select>
                                </div>
                            </div>
                            <div class="filter-actions">
                                <button class="btn btn-primary" onclick="generateAnalysis()">
                                    <i class="fas fa-chart-line me-2"></i>Phân tích
                                </button>
                                <button class="btn btn-success" onclick="exportAnalysisReport()">
                                    <i class="fas fa-download me-2"></i>Xuất báo cáo
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ phân tích -->
                    <div class="col-xl-8 mb-4">
                        <div class="chart-card">
                            <div class="chart-card-header">
                                <h5 class="chart-title" id="analysisChartTitle">Xu hướng tiêu thụ</h5>
                            </div>
                            <div class="chart-card-body">
                                <canvas id="analysisChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Thống kê chi tiết -->
                    <div class="col-xl-4 mb-4">
                        <div class="analysis-summary-card">
                            <h5 class="summary-title">Tóm tắt phân tích</h5>
                            <div id="analysisSummary">
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-chart-pie fa-3x mb-3"></i>
                                    <p>Chọn loại phân tích để xem kết quả</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dự báo và khuyến nghị -->
                    <div class="col-12">
                        <div class="forecast-card">
                            <h5 class="forecast-title">
                                <i class="fas fa-crystal-ball me-2"></i>Dự báo và Khuyến nghị
                            </h5>
                            <div id="forecastContent">
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-magic fa-3x mb-3"></i>
                                    <p>Thực hiện phân tích để xem dự báo</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Báo cáo tài chính -->
            <div class="tab-pane fade" id="financial" role="tabpanel">
                <div class="row">
                    <!-- Bộ lọc -->
                    <div class="col-12 mb-4">
                        <div class="filter-card">
                            <h5 class="filter-title">
                                <i class="fas fa-filter me-2"></i>Bộ lọc báo cáo tài chính
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Từ ngày</label>
                                    <input type="date" class="form-control" id="financialFromDate">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Đến ngày</label>
                                    <input type="date" class="form-control" id="financialToDate">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Loại báo cáo</label>
                                    <select class="form-select" id="financialReportType">
                                        <option value="revenue">Doanh thu</option>
                                        <option value="cost">Chi phí</option>
                                        <option value="profit">Lợi nhuận</option>
                                        <option value="inventory_value">Giá trị tồn kho</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Nhóm theo</label>
                                    <select class="form-select" id="financialGroupBy">
                                        <option value="day">Theo ngày</option>
                                        <option value="week">Theo tuần</option>
                                        <option value="month">Theo tháng</option>
                                        <option value="quarter">Theo quý</option>
                                    </select>
                                </div>
                            </div>
                            <div class="filter-actions">
                                <button class="btn btn-primary" onclick="generateFinancialReport()">
                                    <i class="fas fa-calculator me-2"></i>Tạo báo cáo
                                </button>
                                <button class="btn btn-success" onclick="exportFinancialReport()">
                                    <i class="fas fa-download me-2"></i>Xuất Excel
                                </button>
                                <button class="btn btn-danger" onclick="exportFinancialPDF()">
                                    <i class="fas fa-file-pdf me-2"></i>Xuất PDF
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Thống kê tài chính -->
                    <div class="col-12 mb-4">
                        <div class="row g-4">
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card bg-success text-white">
                                    <div class="stats-card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="stats-title">Tổng doanh thu</h6>
                                                <h2 class="stats-value" id="totalRevenue">0đ</h2>
                                            </div>
                                            <div class="stats-icon">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card bg-danger text-white">
                                    <div class="stats-card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="stats-title">Tổng chi phí</h6>
                                                <h2 class="stats-value" id="totalCost">0đ</h2>
                                            </div>
                                            <div class="stats-icon">
                                                <i class="fas fa-chart-line-down"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card bg-primary text-white">
                                    <div class="stats-card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="stats-title">Lợi nhuận</h6>
                                                <h2 class="stats-value" id="totalProfit">0đ</h2>
                                            </div>
                                            <div class="stats-icon">
                                                <i class="fas fa-dollar-sign"></i>
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
                                                <h6 class="stats-title">Tỷ suất lợi nhuận</h6>
                                                <h2 class="stats-value" id="profitMargin">0%</h2>
                                            </div>
                                            <div class="stats-icon">
                                                <i class="fas fa-percentage"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ tài chính -->
                    <div class="col-12 mb-4">
                        <div class="chart-card">
                            <div class="chart-card-header">
                                <h5 class="chart-title">Biểu đồ tài chính</h5>
                            </div>
                            <div class="chart-card-body">
                                <canvas id="financialChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hiệu suất -->
            <div class="tab-pane fade" id="performance" role="tabpanel">
                <div class="row">
                    <!-- Performance metrics -->
                    <div class="col-12 mb-4">
                        <div class="row g-4">
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card bg-primary text-white">
                                    <div class="stats-card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="stats-title">Tỷ lệ lấp đầy kho</h6>
                                                <h2 class="stats-value" id="warehouseFillRate">0%</h2>
                                            </div>
                                            <div class="stats-icon">
                                                <i class="fas fa-warehouse"></i>
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
                                                <h6 class="stats-title">Thời gian xử lý TB</h6>
                                                <h2 class="stats-value" id="avgProcessingTime">0h</h2>
                                            </div>
                                            <div class="stats-icon">
                                                <i class="fas fa-clock"></i>
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
                                                <h6 class="stats-title">Tỷ lệ chính xác</h6>
                                                <h2 class="stats-value" id="accuracyRate">0%</h2>
                                            </div>
                                            <div class="stats-icon">
                                                <i class="fas fa-bullseye"></i>
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
                                                <h6 class="stats-title">Vòng quay kho</h6>
                                                <h2 class="stats-value" id="inventoryTurnover">0x</h2>
                                            </div>
                                            <div class="stats-icon">
                                                <i class="fas fa-sync-alt"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance charts -->
                    <div class="col-xl-6 mb-4">
                        <div class="chart-card">
                            <div class="chart-card-header">
                                <h5 class="chart-title">Hiệu suất theo thời gian</h5>
                            </div>
                            <div class="chart-card-body">
                                <canvas id="performanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 mb-4">
                        <div class="chart-card">
                            <div class="chart-card-header">
                                <h5 class="chart-title">So sánh hiệu suất</h5>
                            </div>
                            <div class="chart-card-body">
                                <canvas id="performanceComparisonChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal tạo báo cáo tùy chỉnh -->
    <div class="modal fade" id="customReportModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tạo báo cáo tùy chỉnh</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tên báo cáo</label>
                            <input type="text" class="form-control" id="customReportName">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Loại báo cáo</label>
                            <select class="form-select" id="customReportType">
                                <option value="inventory">Tồn kho</option>
                                <option value="import_export">Nhập/Xuất kho</option>
                                <option value="financial">Tài chính</option>
                                <option value="performance">Hiệu suất</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Từ ngày</label>
                            <input type="date" class="form-control" id="customFromDate">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Đến ngày</label>
                            <input type="date" class="form-control" id="customToDate">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Các trường dữ liệu</label>
                            <div class="row" id="customFields">
                                <!-- Dynamic fields will be added here -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="generateCustomReport()">Tạo báo cáo</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal lập lịch báo cáo -->
    <div class="modal fade" id="scheduleReportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Lập lịch báo cáo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tên lịch báo cáo</label>
                        <input type="text" class="form-control" id="scheduleName">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Loại báo cáo</label>
                        <select class="form-select" id="scheduleReportType">
                            <option value="inventory">Tồn kho</option>
                            <option value="import_export">Nhập/Xuất kho</option>
                            <option value="financial">Tài chính</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tần suất</label>
                        <select class="form-select" id="scheduleFrequency">
                            <option value="daily">Hàng ngày</option>
                            <option value="weekly">Hàng tuần</option>
                            <option value="monthly">Hàng tháng</option>
                            <option value="quarterly">Hàng quý</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email nhận báo cáo</label>
                        <input type="email" class="form-control" id="scheduleEmail">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Thời gian gửi</label>
                        <input type="time" class="form-control" id="scheduleTime">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="createSchedule()">Tạo lịch</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/baocaothongke.js"></script>

<style>
/* Report Management Styles */
.stats-card-body {
    padding: 1.5rem;
}
.report-management {
    padding: 20px;
}

.filter-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 25px;
}

.filter-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.filter-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.data-table-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #495057;
    padding: 20px 20px 0;
    margin: 0;
}

.analysis-summary-card, .forecast-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    padding: 25px;
}

.summary-title, .forecast-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

/* Nav tabs styling */
.nav-tabs {
    border-bottom: 2px solid #dee2e6;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 15px 15px 0 0;
    padding: 15px 20px 0;
    margin-bottom: 0;
}

.nav-tabs .nav-link {
    background: transparent;
    border: none;
    border-radius: 10px 10px 0 0;
    padding: 12px 20px;
    margin-right: 8px;
    color: #6c757d;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
}

.nav-tabs .nav-link:hover {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
}

.nav-tabs .nav-link.active {
    background: white;
    color: #667eea;
    box-shadow: 0 -3px 10px rgba(0,0,0,0.1);
}

.tab-content {
    background: white;
    border-radius: 0 0 15px 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    padding: 30px;
}

/* Responsive */
@media (max-width: 768px) {
    .report-management {
        padding: 15px;
    }
    
    .filter-card {
        padding: 20px;
    }
    
    .filter-actions {
        justify-content: center;
    }
    
    .filter-actions .btn {
        flex: 1;
        min-width: 120px;
    }
    
    .nav-tabs {
        padding: 10px 15px 0;
    }
    
    .nav-tabs .nav-link {
        font-size: 0.9rem;
        padding: 10px 15px;
    }
    
    .tab-content {
        padding: 20px;
    }
}
</style>