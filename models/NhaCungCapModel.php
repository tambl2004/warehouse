<?php
// File: models/NhaCungCapModel.php
class NhaCungCapModel {
    private $pdo;

    public function __construct() {
        global $pdo; // Sử dụng biến $pdo từ connect.php
        $this->pdo = $pdo;
    }

    public function layDanhSachNhaCungCap($page = 1, $limit = 10, $search = '', $status = '') {
        $offset = ($page - 1) * $limit;
        $sql_conditions = [];
        $params = [];

        if (!empty($search)) {
            $sql_conditions[] = "(supplier_name LIKE :search OR email LIKE :search OR phone_number LIKE :search OR supplier_code LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($status)) {
            $sql_conditions[] = "status = :status";
            $params[':status'] = $status;
        }

        $where_clause = count($sql_conditions) > 0 ? "WHERE " . implode(" AND ", $sql_conditions) : "";

        $sql = "SELECT * FROM suppliers {$where_clause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function demTongSoNhaCungCap($search = '', $status = '') {
        $sql_conditions = [];
        $params = [];
        if (!empty($search)) {
            $sql_conditions[] = "(supplier_name LIKE :search OR email LIKE :search OR phone_number LIKE :search OR supplier_code LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        if (!empty($status)) {
            $sql_conditions[] = "status = :status";
            $params[':status'] = $status;
        }
        $where_clause = count($sql_conditions) > 0 ? "WHERE " . implode(" AND ", $sql_conditions) : "";
        
        $sql = "SELECT COUNT(*) FROM suppliers {$where_clause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function layNhaCungCapTheoId($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function themNhaCungCap($data) {
        $sql = "INSERT INTO suppliers (supplier_code, supplier_name, email, phone_number, address, tax_code, contact_person, website, notes, status) 
                VALUES (:supplier_code, :supplier_name, :email, :phone_number, :address, :tax_code, :contact_person, :website, :notes, :status)";
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([
                ':supplier_code' => $data['supplier_code'] ?: null,
                ':supplier_name' => $data['supplier_name'],
                ':email' => $data['email'] ?: null,
                ':phone_number' => $data['phone_number'] ?: null,
                ':address' => $data['address'] ?: null,
                ':tax_code' => $data['tax_code'] ?: null,
                ':contact_person' => $data['contact_person'] ?: null,
                ':website' => $data['website'] ?: null,
                ':notes' => $data['notes'] ?: null,
                ':status' => $data['status'] ?? 'active'
            ]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            // Xử lý lỗi trùng lặp (ví dụ: email, tax_code, supplier_code đã UNIQUE)
            if ($e->errorInfo[1] == 1062) { // Mã lỗi cho duplicate entry
                return ['error_type' => 'duplicate', 'message' => 'Lỗi: Thông tin (Mã NCC, Email hoặc MST) có thể đã tồn tại.'];
            }
            error_log("Lỗi Model thêm NCC: " . $e->getMessage());
            return false;
        }
    }

    public function capNhatNhaCungCap($id, $data) {
        $sql = "UPDATE suppliers SET 
                    supplier_code = :supplier_code,
                    supplier_name = :supplier_name, 
                    email = :email, 
                    phone_number = :phone_number, 
                    address = :address, 
                    tax_code = :tax_code, 
                    contact_person = :contact_person, 
                    website = :website, 
                    notes = :notes, 
                    status = :status,
                    updated_at = NOW()
                WHERE supplier_id = :supplier_id";
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([
                ':supplier_code' => $data['supplier_code'] ?: null,
                ':supplier_name' => $data['supplier_name'],
                ':email' => $data['email'] ?: null,
                ':phone_number' => $data['phone_number'] ?: null,
                ':address' => $data['address'] ?: null,
                ':tax_code' => $data['tax_code'] ?: null,
                ':contact_person' => $data['contact_person'] ?: null,
                ':website' => $data['website'] ?: null,
                ':notes' => $data['notes'] ?: null,
                ':status' => $data['status'],
                ':supplier_id' => $id
            ]);
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                return ['error_type' => 'duplicate', 'message' => 'Lỗi: Thông tin (Mã NCC, Email hoặc MST) có thể đã tồn tại.'];
            }
            error_log("Lỗi Model cập nhật NCC: " . $e->getMessage());
            return false;
        }
    }

    public function thayDoiTrangThaiNhaCungCap($id, $new_status) {
        $sql = "UPDATE suppliers SET status = ?, updated_at = NOW() WHERE supplier_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$new_status, $id]);
    }

    public function validateNhaCungCap($data, $id = null) {
        $errors = [];
        if (empty($data['supplier_name'])) {
            $errors['supplier_name'] = 'Tên nhà cung cấp không được để trống.';
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Địa chỉ email không hợp lệ.';
        }
        // Kiểm tra trùng lặp nếu cần thiết (ví dụ: supplier_code, email, tax_code)
        // Ví dụ kiểm tra trùng supplier_code
        if (!empty($data['supplier_code'])) {
            $stmt = $this->pdo->prepare("SELECT supplier_id FROM suppliers WHERE supplier_code = :supplier_code" . ($id ? " AND supplier_id != :id" : ""));
            $params_check = [':supplier_code' => $data['supplier_code']];
            if ($id) $params_check[':id'] = $id;
            $stmt->execute($params_check);
            if ($stmt->fetch()) {
                $errors['supplier_code'] = 'Mã nhà cung cấp đã tồn tại.';
            }
        }
        // Thêm các validation khác nếu cần
        return $errors;
    }
}
?>