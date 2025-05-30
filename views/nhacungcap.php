
    <h1 class="page-title">Quản lý nhà cung cấp</h1>
    
    <div class="content-header">
        <div class="search-box">
            <input type="text" class="form-control" placeholder="Tìm kiếm nhà cung cấp...">
        </div>
        
        <div class="d-flex gap-3 align-items-center">
            <select class="form-select filter-dropdown">
                <option>Tất cả trạng thái</option>
                <option>Hoạt động</option>
                <option>Tạm dừng</option>
            </select>
            
            <button class="btn btn-add">
                <i class="fas fa-plus me-2"></i>
                Thêm nhà cung cấp mới
            </button>
        </div>
    </div>

    <!-- Data Table -->
    <div class="data-table">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã NCC</th>
                    <th>Tên nhà cung cấp</th>
                    <th>Địa chỉ</th>
                    <th>Số điện thoại</th>
                    <th>Email</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fas fa-truck"></i>
                            <div>
                                <h5>Chưa có nhà cung cấp nào</h5>
                                <p class="mb-0">Hãy thêm nhà cung cấp đầu tiên của bạn</p>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
