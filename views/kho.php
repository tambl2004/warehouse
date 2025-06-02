<?php
require_once __DIR__ . '/../config/connect.php';

// Lấy thống kê tổng quan
$totalAreas = $pdo->query("SELECT COUNT(*) FROM warehouse_areas")->fetchColumn();
$totalShelves = $pdo->query("SELECT COUNT(*) FROM shelves")->fetchColumn();
$totalCapacity = $pdo->query("SELECT SUM(max_capacity) FROM shelves")->fetchColumn();
$usedCapacity = $pdo->query("SELECT SUM(current_capacity) FROM shelves")->fetchColumn();
$utilizationRate = $totalCapacity > 0 ? ($usedCapacity / $totalCapacity) * 100 : 0;

// Lấy danh sách khu vực
$areasQuery = $pdo->query("
    SELECT wa.*, 
           COUNT(s.shelf_id) as total_shelves,
           COALESCE(SUM(s.max_capacity), 0) as total_capacity,
           COALESCE(SUM(s.current_capacity), 0) as used_capacity
    FROM warehouse_areas wa 
    LEFT JOIN shelves s ON wa.area_id = s.area_id 
    GROUP BY wa.area_id
    ORDER BY wa.area_name
");
$areas = $areasQuery->fetchAll();

// Lấy danh sách kệ với thông tin sức chứa
$shelvesQuery = $pdo->query("
    SELECT s.*, wa.area_name,
           (s.current_capacity / s.max_capacity * 100) as utilization_percent,
           COUNT(pl.product_id) as product_count
    FROM shelves s
    LEFT JOIN warehouse_areas wa ON s.area_id = wa.area_id
    LEFT JOIN product_locations pl ON s.shelf_id = pl.shelf_id
    GROUP BY s.shelf_id
    ORDER BY wa.area_name, s.shelf_code
");
$shelves = $shelvesQuery->fetchAll();

// Lấy lịch sử di chuyển gần đây
$historyQuery = $pdo->query("
    SELECT sph.*, p.product_name, s.shelf_code, wa.area_name,
           u.full_name as moved_by
    FROM shelf_product_history sph
    LEFT JOIN products p ON sph.product_id = p.product_id
    LEFT JOIN shelves s ON sph.shelf_id = s.shelf_id
    LEFT JOIN warehouse_areas wa ON s.area_id = wa.area_id
    LEFT JOIN users u ON sph.created_by = u.user_id
    ORDER BY sph.moved_at DESC
    LIMIT 10
");
$recentMoves = $historyQuery->fetchAll();
?>
<div class="function-container">
    <div class="warehouse-management">
        <!-- Header -->
        <h1 class="page-title"></i>Quản lý Kho</h1>
       

        <!-- Dashboard Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="mb-0"><?= $totalAreas ?></h3>
                                <small>Khu vực kho</small>
                            </div>
                            <div class="ms-3">
                                <i class="fas fa-map-marked-alt fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="mb-0"><?= $totalShelves ?></h3>
                                <small>Tổng số kệ</small>
                            </div>
                            <div class="ms-3">
                                <i class="fas fa-layer-group fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="mb-0"><?= number_format($totalCapacity, 1) ?></h3>
                                <small>Tổng sức chứa (dm³)</small>
                            </div>
                            <div class="ms-3">
                                <i class="fas fa-cubes fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stats-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="mb-0"><?= number_format($utilizationRate, 1) ?>%</h3>
                                <small>Tỷ lệ sử dụng</small>
                            </div>
                            <div class="ms-3">
                                <i class="fas fa-chart-pie fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" id="warehouseTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button">
                    <i class="fas fa-chart-bar me-1"></i>Tổng quan
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="areas-tab" data-bs-toggle="tab" data-bs-target="#areas" type="button">
                    <i class="fas fa-map-marked-alt me-1"></i>Khu vực kho
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="shelves-tab" data-bs-toggle="tab" data-bs-target="#shelves" type="button">
                    <i class="fas fa-layer-group me-1"></i>Quản lý kệ
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="map-tab" data-bs-toggle="tab" data-bs-target="#map" type="button">
                    <i class="fas fa-sitemap me-1"></i>Sơ đồ kho
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button">
                    <i class="fas fa-history me-1"></i>Lịch sử
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="warehouseTabContent">
            <!-- Tổng quan Tab -->
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Biểu đồ sử dụng kho theo khu vực
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="warehouseUtilizationChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Cảnh báo sức chứa
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="capacityAlerts">
                                    <?php foreach($shelves as $shelf): ?>
                                        <?php if($shelf['utilization_percent'] > 80): ?>
                                            <div class="alert alert-warning alert-sm mb-2">
                                                <strong><?= htmlspecialchars($shelf['shelf_code']) ?></strong>
                                                <br>Sử dụng: <?= number_format($shelf['utilization_percent'], 1) ?>%
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Khu vực kho Tab -->
            <div class="tab-pane fade" id="areas" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-map-marked-alt me-2"></i>
                            Danh sách khu vực kho
                        </h5>
                        <button class="btn btn-primary btn-sm" onclick="openAreaModal()">
                            <i class="fas fa-plus me-1"></i>Thêm khu vực
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã khu vực</th>
                                        <th>Tên khu vực</th>
                                        <th>Mô tả</th>
                                        <th>Số kệ</th>
                                        <th>Sức chứa</th>
                                        <th>Tỷ lệ sử dụng</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($areas as $area): ?>
                                        <?php 
                                        $utilizationPercent = $area['total_capacity'] > 0 ? 
                                            ($area['used_capacity'] / $area['total_capacity']) * 100 : 0;
                                        ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($area['area_id']) ?></strong></td>
                                            <td><?= htmlspecialchars($area['area_name']) ?></td>
                                            <td><?= htmlspecialchars($area['description']) ?></td>
                                            <td><span class="badge bg-info"><?= $area['total_shelves'] ?></span></td>
                                            <td>
                                                <?= number_format($area['used_capacity'], 1) ?> / 
                                                <?= number_format($area['total_capacity'], 1) ?> dm³
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar <?= $utilizationPercent > 80 ? 'bg-danger' : ($utilizationPercent > 60 ? 'bg-warning' : 'bg-success') ?>" 
                                                        style="width: <?= $utilizationPercent ?>%">
                                                        <?= number_format($utilizationPercent, 1) ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="editArea(<?= $area['area_id'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="deleteArea(<?= $area['area_id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quản lý kệ Tab -->
            <div class="tab-pane fade" id="shelves" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-layer-group me-2"></i>
                            Danh sách kệ kho
                        </h5>
                        <button class="btn btn-primary btn-sm" onclick="openShelfModal()">
                            <i class="fas fa-plus me-1"></i>Thêm kệ
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <select class="form-select" id="filterArea" onchange="filterShelves()">
                                    <option value="">Tất cả khu vực</option>
                                    <?php foreach($areas as $area): ?>
                                        <option value="<?= $area['area_id'] ?>"><?= htmlspecialchars($area['area_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="filterUtilization" onchange="filterShelves()">
                                    <option value="">Tất cả mức sử dụng</option>
                                    <option value="low">Thấp (< 50%)</option>
                                    <option value="medium">Trung bình (50-80%)</option>
                                    <option value="high">Cao (> 80%)</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover" id="shelvesTable">
                                <thead>
                                    <tr>
                                        <th>Mã kệ</th>
                                        <th>Khu vực</th>
                                        <th>Vị trí</th>
                                        <th>Sức chứa</th>
                                        <th>Tỷ lệ sử dụng</th>
                                        <th>Sản phẩm</th>
                                        <th>Tọa độ</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($shelves as $shelf): ?>
                                        <tr data-area="<?= $shelf['area_id'] ?>" data-utilization="<?= $shelf['utilization_percent'] ?>">
                                            <td><strong><?= htmlspecialchars($shelf['shelf_code']) ?></strong></td>
                                            <td><?= htmlspecialchars($shelf['area_name']) ?></td>
                                            <td><?= htmlspecialchars($shelf['location_description']) ?></td>
                                            <td>
                                                <?= number_format($shelf['current_capacity'], 1) ?> / 
                                                <?= number_format($shelf['max_capacity'], 1) ?> dm³
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar <?= $shelf['utilization_percent'] > 80 ? 'bg-danger' : ($shelf['utilization_percent'] > 60 ? 'bg-warning' : 'bg-success') ?>" 
                                                        style="width: <?= $shelf['utilization_percent'] ?>%">
                                                        <?= number_format($shelf['utilization_percent'], 1) ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-secondary"><?= $shelf['product_count'] ?></span></td>
                                            <td><?= htmlspecialchars($shelf['coordinates']) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-info" onclick="viewShelfDetails(<?= $shelf['shelf_id'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-primary" onclick="editShelf(<?= $shelf['shelf_id'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="deleteShelf(<?= $shelf['shelf_id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sơ đồ kho Tab -->
            <div class="tab-pane fade" id="map" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-sitemap me-2"></i>
                            Sơ đồ kho trực quan
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="warehouseMap" class="warehouse-map">
                            <!-- Sơ đồ kho sẽ được render bằng JavaScript -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lịch sử Tab -->
            <div class="tab-pane fade" id="history" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Lịch sử di chuyển sản phẩm
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Thời gian</th>
                                        <th>Sản phẩm</th>
                                        <th>Kệ đích</th>
                                        <th>Khu vực</th>
                                        <th>Số lượng</th>
                                        <th>Người thực hiện</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recentMoves as $move): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($move['moved_at'])) ?></td>
                                            <td><?= htmlspecialchars($move['product_name']) ?></td>
                                            <td><?= htmlspecialchars($move['shelf_code']) ?></td>
                                            <td><?= htmlspecialchars($move['area_name']) ?></td>
                                            <td><span class="badge bg-info"><?= $move['quantity'] ?></span></td>
                                            <td><?= htmlspecialchars($move['moved_by'] ?? 'Hệ thống') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal thêm/sửa khu vực -->
    <div class="modal fade" id="areaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="areaModalTitle">Thêm khu vực mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="areaForm" onsubmit="saveArea(event)">
                    <div class="modal-body">
                        <input type="hidden" id="areaId" name="area_id">
                        <div class="mb-3">
                            <label for="areaName" class="form-label">Tên khu vực <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="areaName" name="area_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="areaDescription" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="areaDescription" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal thêm/sửa kệ -->
    <div class="modal fade" id="shelfModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shelfModalTitle">Thêm kệ mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="shelfForm" onsubmit="saveShelf(event)">
                    <div class="modal-body">
                        <input type="hidden" id="shelfId" name="shelf_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="shelfCode" class="form-label">Mã kệ <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="shelfCode" name="shelf_code" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="shelfAreaId" class="form-label">Khu vực <span class="text-danger">*</span></label>
                                    <select class="form-select" id="shelfAreaId" name="area_id" required>
                                        <option value="">Chọn khu vực</option>
                                        <?php foreach($areas as $area): ?>
                                            <option value="<?= $area['area_id'] ?>"><?= htmlspecialchars($area['area_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="maxCapacity" class="form-label">Sức chứa tối đa (dm³) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="maxCapacity" name="max_capacity" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="coordinates" class="form-label">Tọa độ</label>
                                    <input type="text" class="form-control" id="coordinates" name="coordinates" placeholder="Ví dụ: A1-L">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="locationDescription" class="form-label">Mô tả vị trí</label>
                            <textarea class="form-control" id="locationDescription" name="location_description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal chi tiết kệ -->
    <div class="modal fade" id="shelfDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi tiết kệ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="shelfDetailsContent">
                    <!-- Nội dung sẽ được load bằng AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Biến global
let areaModal, shelfModal, shelfDetailsModal;
let warehouseChart;

document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo modal
    areaModal = new bootstrap.Modal(document.getElementById('areaModal'));
    shelfModal = new bootstrap.Modal(document.getElementById('shelfModal'));
    shelfDetailsModal = new bootstrap.Modal(document.getElementById('shelfDetailsModal'));
    
    // Khởi tạo biểu đồ
    initWarehouseChart();
    
    // Khởi tạo sơ đồ kho
    initWarehouseMap();
});

// Biểu đồ sử dụng kho
function initWarehouseChart() {
    const ctx = document.getElementById('warehouseUtilizationChart').getContext('2d');
    const areas = <?= json_encode($areas) ?>;
    
    const labels = areas.map(area => area.area_name);
    const utilizationData = areas.map(area => {
        return area.total_capacity > 0 ? (area.used_capacity / area.total_capacity * 100) : 0;
    });
    
    warehouseChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Tỷ lệ sử dụng (%)',
                data: utilizationData,
                backgroundColor: utilizationData.map(val => {
                    if (val > 80) return '#dc3545';
                    if (val > 60) return '#ffc107';
                    return '#28a745';
                }),
                borderColor: '#dee2e6',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

// Sơ đồ kho trực quan
function initWarehouseMap() {
    const mapContainer = document.getElementById('warehouseMap');
    const areas = <?= json_encode($areas) ?>;
    const shelves = <?= json_encode($shelves) ?>;
    
    let mapHTML = '<div class="warehouse-grid">';
    
    areas.forEach(area => {
        const areaShelves = shelves.filter(shelf => shelf.area_id == area.area_id);
        mapHTML += `
            <div class="warehouse-area" data-area-id="${area.area_id}">
                <h6 class="area-title">${area.area_name}</h6>
                <div class="shelves-grid">
        `;
        
        areaShelves.forEach(shelf => {
            const utilizationClass = shelf.utilization_percent > 80 ? 'high' : 
                                   shelf.utilization_percent > 60 ? 'medium' : 'low';
            mapHTML += `
                <div class="shelf-item ${utilizationClass}" 
                     data-shelf-id="${shelf.shelf_id}"
                     onclick="viewShelfDetails(${shelf.shelf_id})"
                     title="${shelf.shelf_code} - ${shelf.utilization_percent.toFixed(1)}%">
                    <div class="shelf-code">${shelf.shelf_code}</div>
                    <div class="shelf-utilization">${shelf.utilization_percent.toFixed(1)}%</div>
                </div>
            `;
        });
        
        mapHTML += `
                </div>
            </div>
        `;
    });
    
    mapHTML += '</div>';
    mapContainer.innerHTML = mapHTML;
}

// Quản lý khu vực
function openAreaModal(areaId = null) {
    document.getElementById('areaForm').reset();
    document.getElementById('areaId').value = '';
    
    if (areaId) {
        // Load dữ liệu khu vực để chỉnh sửa
        fetch(`api/warehouse_handler.php?action=get_area&id=${areaId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('areaId').value = data.area.area_id;
                    document.getElementById('areaName').value = data.area.area_name;
                    document.getElementById('areaDescription').value = data.area.description || '';
                    document.getElementById('areaModalTitle').textContent = 'Sửa khu vực';
                }
            });
    } else {
        document.getElementById('areaModalTitle').textContent = 'Thêm khu vực mới';
    }
    
    areaModal.show();
}

function saveArea(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'save_area');
    
    fetch('api/warehouse_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            areaModal.hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('error', data.message);
        }
    });
}

function editArea(areaId) {
    openAreaModal(areaId);
}

function deleteArea(areaId) {
    if (confirm('Bạn có chắc chắn muốn xóa khu vực này?')) {
        fetch('api/warehouse_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_area&area_id=${areaId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('error', data.message);
            }
        });
    }
}

// Quản lý kệ
function openShelfModal(shelfId = null) {
    document.getElementById('shelfForm').reset();
    document.getElementById('shelfId').value = '';
    
    if (shelfId) {
        // Load dữ liệu kệ để chỉnh sửa
        fetch(`api/warehouse_handler.php?action=get_shelf&id=${shelfId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const shelf = data.shelf;
                    document.getElementById('shelfId').value = shelf.shelf_id;
                    document.getElementById('shelfCode').value = shelf.shelf_code;
                    document.getElementById('shelfAreaId').value = shelf.area_id;
                    document.getElementById('maxCapacity').value = shelf.max_capacity;
                    document.getElementById('coordinates').value = shelf.coordinates || '';
                    document.getElementById('locationDescription').value = shelf.location_description || '';
                    document.getElementById('shelfModalTitle').textContent = 'Sửa kệ';
                }
            });
    } else {
        document.getElementById('shelfModalTitle').textContent = 'Thêm kệ mới';
    }
    
    shelfModal.show();
}

function saveShelf(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'save_shelf');
    
    fetch('api/warehouse_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            shelfModal.hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('error', data.message);
        }
    });
}

function editShelf(shelfId) {
    openShelfModal(shelfId);
}

function deleteShelf(shelfId) {
    if (confirm('Bạn có chắc chắn muốn xóa kệ này?')) {
        fetch('api/warehouse_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_shelf&shelf_id=${shelfId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('error', data.message);
            }
        });
    }
}

function viewShelfDetails(shelfId) {
    fetch(`api/warehouse_handler.php?action=get_shelf_details&id=${shelfId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('shelfDetailsContent').innerHTML = data.html;
                shelfDetailsModal.show();
            }
        });
}

// Lọc kệ
function filterShelves() {
    const areaFilter = document.getElementById('filterArea').value;
    const utilizationFilter = document.getElementById('filterUtilization').value;
    const rows = document.querySelectorAll('#shelvesTable tbody tr');
    
    rows.forEach(row => {
        const areaId = row.dataset.area;
        const utilization = parseFloat(row.dataset.utilization);
        
        let showRow = true;
        
        if (areaFilter && areaId !== areaFilter) {
            showRow = false;
        }
        
        if (utilizationFilter) {
            switch(utilizationFilter) {
                case 'low':
                    if (utilization >= 50) showRow = false;
                    break;
                case 'medium':
                    if (utilization < 50 || utilization > 80) showRow = false;
                    break;
                case 'high':
                    if (utilization <= 80) showRow = false;
                    break;
            }
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

// Toast notification
function showToast(type, message) {
    const toastContainer = document.querySelector('.toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        </div>
        <div class="toast-message">${message}</div>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}
</script>