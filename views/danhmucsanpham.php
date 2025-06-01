<?php

$message = '';
$message_type = ''; 
$edit_category_data = null; 

// Xử LÝ THÊM DANH MỤC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    if (empty($category_name)) {
        $message = 'Tên danh mục không được để trống.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
            if ($stmt->execute([$category_name, $description])) {
                $message = 'Thêm danh mục thành công!';
                $message_type = 'success';
                 // Ghi log hành động
                if (function_exists('logUserActivity') && isset($_SESSION['user_id'])) {
                    logUserActivity($_SESSION['user_id'], 'ADD_CATEGORY', "Thêm danh mục mới: " . $category_name);
                }
            } else {
                $message = 'Thêm danh mục thất bại. Vui lòng thử lại.';
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = 'Lỗi CSDL: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// XỬ LÝ CẬP NHẬT DANH MỤC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category'])) {
    $category_id = $_POST['category_id'];
    $category_name = trim($_POST['category_name_edit']);
    $description = trim($_POST['description_edit']);

    if (empty($category_name)) {
        $message = 'Tên danh mục không được để trống.';
        $message_type = 'error';
    } elseif (empty($category_id)) {
        $message = 'ID danh mục không hợp lệ.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET category_name = ?, description = ?, updated_at = NOW() WHERE category_id = ?");
            if ($stmt->execute([$category_name, $description, $category_id])) {
                $message = 'Cập nhật danh mục thành công!';
                $message_type = 'success';
                if (function_exists('logUserActivity') && isset($_SESSION['user_id'])) {
                    logUserActivity($_SESSION['user_id'], 'EDIT_CATEGORY', "Cập nhật danh mục ID: " . $category_id . " - Tên: " . $category_name);
                }
            } else {
                $message = 'Cập nhật danh mục thất bại. Vui lòng thử lại.';
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = 'Lỗi CSDL: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// XỬ LÝ XÓA DANH MỤC
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $category_id_to_delete = $_GET['id'];
    // Trước khi xóa, kiểm tra xem có sản phẩm nào thuộc danh mục này không
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt_check->execute([$category_id_to_delete]);
    $product_count = $stmt_check->fetchColumn();

    if ($product_count > 0) {
        $message = "Không thể xóa danh mục này vì có sản phẩm đang thuộc về nó. (Tìm thấy $product_count sản phẩm)";
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
            if ($stmt->execute([$category_id_to_delete])) {
                $message = 'Xóa danh mục thành công!';
                $message_type = 'success';
                 if (function_exists('logUserActivity') && isset($_SESSION['user_id'])) {
                    logUserActivity($_SESSION['user_id'], 'DELETE_CATEGORY', "Xóa danh mục ID: " . $category_id_to_delete);
                }
            } else {
                $message = 'Xóa danh mục thất bại. Vui lòng thử lại.';
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = 'Lỗi CSDL: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// LẤY DỮ LIỆU DANH MỤC ĐỂ SỬA
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $category_id_to_edit = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->execute([$category_id_to_edit]);
    $edit_category_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$edit_category_data) {
         $message = 'Không tìm thấy danh mục để sửa.';
         $message_type = 'error';
    }
}


// Lấy danh sách tất cả danh mục để hiển thị
$stmt = $pdo->query("SELECT * FROM categories ORDER BY created_at DESC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="function-container">
    <h2 class="page-title">Quản lý Danh mục Sản phẩm</h2>

    <div class="mb-3">
        <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus"></i> Thêm Danh mục mới
        </button>
    </div>

    <?php if ($message): ?>
    <div id="alertMessage" class="alert <?php echo ($message_type == 'success') ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Tên Danh mục</th>
                    <th>Mô tả</th>
                    <th>Ngày tạo</th>
                    <th>Ngày cập nhật</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($categories) > 0): ?>
                    <?php foreach ($categories as $index => $category): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($category['description'] ?: 'N/A')); ?></td>
                        <td><?php echo date("d/m/Y H:i:s", strtotime($category['created_at'])); ?></td>
                        <td><?php echo date("d/m/Y H:i:s", strtotime($category['updated_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-edit btn-sm" data-bs-toggle="modal" data-bs-target="#editCategoryModal" 
                                        data-id="<?php echo $category['category_id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($category['category_name']); ?>"
                                        data-description="<?php echo htmlspecialchars($category['description']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?option=danhmuc&action=delete&id=<?php echo $category['category_id']; ?>" 
                                   class="btn btn-delete btn-sm" 
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này? Hành động này không thể hoàn tác.');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Chưa có danh mục nào.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade custom-modal" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="?option=danhmuc" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Thêm Danh mục mới</h5>
                    <button type="button" class="btn-close modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="category_name" class="form-label">Tên Danh mục <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                        <div class="form-text">Tên danh mục là bắt buộc.</div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="add_category" class="btn btn-primary">Lưu Danh mục</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade custom-modal" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="?option=danhmuc" method="POST">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Chỉnh sửa Danh mục</h5>
                    <button type="button" class="btn-close modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="edit_category_name" class="form-label">Tên Danh mục <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name_edit" required>
                         <div class="form-text">Tên danh mục là bắt buộc.</div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="edit_description" name="description_edit" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="update_category" class="btn btn-primary">Cập nhật Danh mục</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Script để tự động ẩn thông báo sau một khoảng thời gian
    const alertMessage = document.getElementById('alertMessage');
    if (alertMessage) {
        setTimeout(function () {
            const bsAlert = new bootstrap.Alert(alertMessage);
            bsAlert.close();
        }, 5000); // 5 giây
    }

    // Script để truyền dữ liệu vào modal sửa
    const editCategoryModal = document.getElementById('editCategoryModal');
    if (editCategoryModal) {
        editCategoryModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Nút đã trigger modal
            
            const categoryId = button.getAttribute('data-id');
            const categoryName = button.getAttribute('data-name');
            const categoryDescription = button.getAttribute('data-description');
            
            const modalTitle = editCategoryModal.querySelector('.modal-title');
            const categoryIdInput = editCategoryModal.querySelector('#edit_category_id');
            const categoryNameInput = editCategoryModal.querySelector('#edit_category_name');
            const categoryDescriptionInput = editCategoryModal.querySelector('#edit_description');
            
            modalTitle.textContent = 'Chỉnh sửa Danh mục: ' + categoryName;
            categoryIdInput.value = categoryId;
            categoryNameInput.value = categoryName;
            categoryDescriptionInput.value = categoryDescription;
        });
    }

    // Nếu có dữ liệu cần sửa được truyền từ PHP (sau khi submit lỗi chẳng hạn), mở modal sửa
    <?php if ($edit_category_data && !empty($message) && $message_type == 'error' && isset($_POST['update_category'])): ?>
        const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        // Đặt lại giá trị form từ POST nếu có lỗi và form đã submit
        document.getElementById('edit_category_id').value = '<?php echo htmlspecialchars($_POST['category_id'] ?? ''); ?>';
        document.getElementById('edit_category_name').value = '<?php echo htmlspecialchars($_POST['category_name_edit'] ?? ''); ?>';
        document.getElementById('edit_description').value = '<?php echo htmlspecialchars($_POST['description_edit'] ?? ''); ?>';
        editModal.show();
    <?php elseif ($edit_category_data && $message_type != 'success' && !(isset($_POST['add_category']) || isset($_POST['update_category']))): // Trường hợp click nút sửa ?>
        const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        document.getElementById('edit_category_id').value = '<?php echo htmlspecialchars($edit_category_data['category_id']); ?>';
        document.getElementById('edit_category_name').value = '<?php echo htmlspecialchars($edit_category_data['category_name']); ?>';
        document.getElementById('edit_description').value = '<?php echo htmlspecialchars($edit_category_data['description']); ?>';
        editModal.show();
    <?php endif; ?>

    <?php if (!empty($message) && $message_type == 'error' && isset($_POST['add_category'])): ?>
        const addModal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
        // Đặt lại giá trị form từ POST nếu có lỗi và form đã submit
        document.getElementById('category_name').value = '<?php echo htmlspecialchars($_POST['category_name'] ?? ''); ?>';
        document.getElementById('description').value = '<?php echo htmlspecialchars($_POST['description'] ?? ''); ?>';
        addModal.show();
    <?php endif; ?>
});
</script>