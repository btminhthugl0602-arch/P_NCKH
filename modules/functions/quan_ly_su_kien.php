<?php
    require_once __DIR__ . '/base.php'; 

    function btc_tao_su_kien($conn, $id_nguoi_tao, $ten_su_kien, $mo_ta = '') {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_tao, 'event.manage')) {
            return ['status' => false, 'message' => 'Không có quyền tạo sự kiện'];
        }

        if (empty(trim($ten_su_kien))) {
            return ['status' => false, 'message' => 'Tên sự kiện không được để trống'];
        }

        $result = _insert_info($conn, 'SUKIEN', 
            ['tenSK', 'moTa', 'nguoiTao', 'isActive'], 
            [$ten_su_kien, $mo_ta, $id_nguoi_tao, 1]
        );

        if (!$result) {
            return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo sự kiện'];
        }

        return [
            'status' => true,
            'message' => 'Đã khởi tạo sự kiện',
            'idSK' => mysqli_insert_id($conn)
        ];
    }

    function btc_cap_nhat_su_kien($conn, $id_nguoi_thuc_hien, $id_su_kien, $ten_su_kien, $mo_ta, $thoi_gian_bat_dau = null, $thoi_gian_ket_thuc = null) {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'event.manage')) {
            return ['status' => false, 'message' => 'Không có quyền'];
        }

        $su_kien = truy_van_mot_ban_ghi($conn, 'SUKIEN', 'idSK', $id_su_kien);
        if (!$su_kien) {
            return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
        }

        if ($su_kien['trangThai'] !== 'NHAP') {
            return ['status' => false, 'message' => 'Chỉ được chỉnh sửa khi sự kiện ở trạng thái NHÁP'];
        }

        $fields = ['tenSK', 'moTa'];
        $values = [$ten_su_kien, $mo_ta];

        if ($thoi_gian_bat_dau !== null) {
            $fields[] = 'thoiGianBatDau'; $values[] = $thoi_gian_bat_dau;
        }
        if ($thoi_gian_ket_thuc !== null) {
            $fields[] = 'thoiGianKetThuc'; $values[] = $thoi_gian_ket_thuc;
        }

        $conditions = ['idSK' => ['=', $id_su_kien, '']]; // Key là cột, Value là mảng [Op, Val, Logic]
        
        $result = _update_info($conn, 'SUKIEN', $fields, $values, $conditions);

        if (!$result) {
             return ['status' => false, 'message' => 'Lỗi cập nhật sự kiện'];
        }

        return ['status' => true, 'message' => 'Cập nhật sự kiện thành công'];
    }

    /**
     * Lấy đầy đủ thông tin sự kiện để hiển thị
     */
    function btc_lay_chi_tiet_su_kien($conn, $id_su_kien) {
        $id_su_kien = (int)$id_su_kien;

        $sql = "SELECT sk.*, ct.tenCap, tk.tenTK as nguoiTaoTen
                FROM sukien sk
                LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
                LEFT JOIN taikhoan tk ON sk.nguoiTao = tk.idTK
                WHERE sk.idSK = ? LIMIT 1";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return null;

        mysqli_stmt_bind_param($stmt, "i", $id_su_kien);

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return null;
        }

        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);
        return $data ?: null;
    }
?>