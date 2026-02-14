<?php

    function btc_tao_su_kien($conn, $id_nguoi_tao, $ten_su_kien, $mo_ta, $ngay_mo_dk, $ngay_dong_dk) {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_tao, 'event.manage')) {
            return ['status' => false, 'message' => 'Không có quyền tạo sự kiện'];
        }

        if (empty(trim($ten_su_kien)) || empty($ngay_mo_dk) || empty($ngay_dong_dk)) {
            return ['status' => false, 'message' => 'Vui lòng nhập tên sự kiện và thời gian đăng ký'];
        }

        $result = _insert_info($conn, 'sukien', 
            ['tenSK', 'moTa', 'nguoiTao', 'ngayMoDangKy', 'ngayDongDangKy', 'isActive'], 
            [$ten_su_kien, $mo_ta, $id_nguoi_tao, $ngay_mo_dk, $ngay_dong_dk, 1]
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

    function btc_cap_nhat_su_kien($conn, $id_nguoi_thuc_hien, $id_su_kien, $ten_su_kien, $mo_ta, $ngay_bat_dau = null, $ngay_ket_thuc = null) {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'event.manage')) {
            return ['status' => false, 'message' => 'Không có quyền'];
        }

        $su_kien = truy_van_mot_ban_ghi($conn, 'sukien', 'idSK', $id_su_kien);
        if (!$su_kien) {
            return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
        }

        $fields = ['tenSK', 'moTa'];
        $values = [$ten_su_kien, $mo_ta];

        if ($ngay_bat_dau !== null) {
            $fields[] = 'ngayBatDau'; $values[] = $ngay_bat_dau;
        }
        if ($ngay_ket_thuc !== null) {
            $fields[] = 'ngayKetThuc'; $values[] = $ngay_ket_thuc;
        }

        $conditions = ['idSK' => ['=', $id_su_kien, '']];
        
        $result = _update_info($conn, 'sukien', $fields, $values, $conditions);

        return $result 
            ? ['status' => true, 'message' => 'Cập nhật sự kiện thành công']
            : ['status' => false, 'message' => 'Lỗi cập nhật sự kiện'];
    }
?>