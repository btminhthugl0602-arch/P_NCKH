<?php 
    require_once __DIR__ . '/base.php';

    function btc_tao_chu_de($conn, $id_nguoi_tao, $id_su_kien, $ten_chu_de, $mo_ta = '') {
        $has_perm = kiem_tra_quyen_su_kien($conn, (int)$id_nguoi_tao, (int)$id_su_kien, 'cauhinh_sukien')
                 || kiem_tra_quyen_he_thong($conn, $id_nguoi_tao, 'tao_su_kien');
        if (!$has_perm) {
            return ['status' => false, 'message' => 'Không có quyền cấu hình sự kiện'];
        }

        $su_kien = truy_van_mot_ban_ghi($conn, 'SUKIEN', 'idSK', $id_su_kien);
        if (!$su_kien) {
            return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
        }

        if (!in_array($su_kien['trangThai'], ['NHAP', 'CAU_HINH'])) {
            return ['status' => false, 'message' => 'Không thể thêm chủ đề ở trạng thái hiện tại'];
        }

        if (empty(trim($ten_chu_de))) {
            return ['status' => false, 'message' => 'Tên chủ đề không được để trống'];
        }

        $result = _insert_info($conn, 'CHUDE', 
            ['idSK', 'tenChuDe', 'moTa', 'nguoiTao'],
            [$id_su_kien, $ten_chu_de, $mo_ta, $id_nguoi_tao]
        );

        if (!$result) {
            return ['status' => false, 'message' => 'Không thể tạo chủ đề'];
        }

        return [
            'status' => true,
            'message' => 'Đã tạo chủ đề',
            'idChuDe' => mysqli_insert_id($conn)
        ];
    }

    function btc_cap_nhat_chu_de($conn, $id_nguoi_thuc_hien, $id_chu_de, $ten_chu_de, $mo_ta = '') {
        $cd = truy_van_mot_ban_ghi($conn, 'CHUDE', 'idChuDe', (int)$id_chu_de);
        $id_sk_cd = $cd ? (int)$cd['idSK'] : 0;
        $has_perm = ($id_sk_cd > 0 && kiem_tra_quyen_su_kien($conn, (int)$id_nguoi_thuc_hien, $id_sk_cd, 'cauhinh_sukien'))
                 || kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'tao_su_kien');
        if (!$has_perm) {
            return ['status' => false, 'message' => 'Không có quyền'];
        }

        if (!kiem_tra_ton_tai_ban_ghi($conn, 'CHUDE', 'idChuDe', $id_chu_de)) {
             return ['status' => false, 'message' => 'Chủ đề không tồn tại'];
        }

        $conditions = ['idChuDe' => ['=', $id_chu_de, '']];
        $result = _update_info($conn, 'CHUDE', 
            ['tenChuDe', 'moTa'], 
            [$ten_chu_de, $mo_ta], 
            $conditions
        );

        return $result 
            ? ['status' => true, 'message' => 'Cập nhật chủ đề thành công'] 
            : ['status' => false, 'message' => 'Lỗi cập nhật'];
    }

    function btc_kich_hoat_chu_de($conn, $id_nguoi_thuc_hien, $id_chu_de, $trang_thai) {
        $cd = truy_van_mot_ban_ghi($conn, 'CHUDE', 'idChuDe', (int)$id_chu_de);
        $id_sk_cd = $cd ? (int)$cd['idSK'] : 0;
        $has_perm = ($id_sk_cd > 0 && kiem_tra_quyen_su_kien($conn, (int)$id_nguoi_thuc_hien, $id_sk_cd, 'cauhinh_sukien'))
                 || kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'tao_su_kien');
        if (!$has_perm) {
            return ['status' => false, 'message' => 'Không có quyền'];
        }

        $trang_thai_val = $trang_thai ? 1 : 0;
        
        $conditions = ['idChuDe' => ['=', $id_chu_de, '']];
        $result = _update_info($conn, 'CHUDE', 
            ['isActive'], 
            [$trang_thai_val], 
            $conditions
        );

        return $result 
            ? ['status' => true, 'message' => 'Đã cập nhật trạng thái chủ đề']
            : ['status' => false, 'message' => 'Lỗi hệ thống'];
    }

    function btc_danh_sach_chu_de_su_kien($conn, $id_su_kien, $chi_lay_dang_hoat_dong = true) {
        $conditions = [
            'WHERE' => ['idSK', '=', $id_su_kien, '']
        ];
        
        if ($chi_lay_dang_hoat_dong) {
            $conditions['WHERE'][3] = 'AND'; 
            array_push($conditions['WHERE'], 'isActive', '=', 1, '');
        }

        $conditions['ORDER BY'] = ['thoiGianTao', 'ASC', '', ''];

        return _select_info($conn, 'CHUDE', [], $conditions);
    }
?>