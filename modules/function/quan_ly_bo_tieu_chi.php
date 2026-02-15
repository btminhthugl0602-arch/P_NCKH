<?php 
    require_once __DIR__ . '/base.php';

    function tao_tieu_chi($conn, $id_nguoi_tao, $ten_tieu_chi, $mo_ta = '') {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_tao, 'criteria.manage')) {
            return ['status' => false, 'message' => 'Không có quyền tạo tiêu chí'];
        }

        if (empty(trim($ten_tieu_chi))) {
            return ['status' => false, 'message' => 'Tên tiêu chí không được trống'];
        }

        $result = _insert_info($conn, 'TIEUCHI', 
            ['tenTieuChi', 'moTa', 'nguoiTao'],
            [$ten_tieu_chi, $mo_ta, $id_nguoi_tao]
        );

        return $result 
            ? ['status' => true, 'message' => 'Đã tạo tiêu chí']
            : ['status' => false, 'message' => 'Lỗi hệ thống'];
    }

    function tao_bo_tieu_chi($conn, $id_nguoi_tao, $id_su_kien, $ten_bo, $mo_ta = '') {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_tao, 'event.manage')) {
            return ['status' => false, 'message' => 'Không có quyền'];
        }

        $su_kien = truy_van_mot_ban_ghi($conn, 'SUKIEN', 'idSK', $id_su_kien);
        if (!$su_kien) {
            return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
        }

        if ($su_kien['trangThai'] !== 'CAU_HINH') {
            return ['status' => false, 'message' => 'Chỉ cấu hình tiêu chí khi sự kiện ở giai đoạn CẤU HÌNH'];
        }

        $result = _insert_info($conn, 'BOTIEUCHI',
            ['idSK', 'tenBo', 'moTa'],
            [$id_su_kien, $ten_bo, $mo_ta]
        );

        return $result ? [
            'status' => true,
            'message' => 'Đã tạo bộ tiêu chí',
            'idBo' => mysqli_insert_id($conn)
        ] : ['status' => false, 'message' => 'Lỗi hệ thống'];
    }

    function them_tieu_chi_vao_bo($conn, $id_nguoi_thuc_hien, $id_bo, $id_tieu_chi, $diem_toi_da, $trong_so, $bat_buoc = 1) {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'event.manage')) {
            return ['status' => false, 'message' => 'Không có quyền'];
        }

        if (!kiem_tra_ton_tai_ban_ghi($conn, 'BOTIEUCHI', 'idBo', $id_bo)) {
             return ['status' => false, 'message' => 'Bộ tiêu chí không tồn tại'];
        }
        if (!kiem_tra_ton_tai_ban_ghi($conn, 'TIEUCHI', 'idTieuChi', $id_tieu_chi)) {
             return ['status' => false, 'message' => 'Tiêu chí không tồn tại'];
        }

        $result = _insert_info($conn, 'BOTIEUCHI_TIEUCHI',
            ['idBo', 'idTieuChi', 'diemToiDa', 'trongSo', 'batBuoc'],
            [$id_bo, $id_tieu_chi, $diem_toi_da, $trong_so, $bat_buoc]
        );

        return $result 
            ? ['status' => true, 'message' => 'Đã thêm tiêu chí vào bộ']
            : ['status' => false, 'message' => 'Lỗi hệ thống'];
    }

    function khoa_bo_tieu_chi($conn, $id_nguoi_thuc_hien, $id_bo) {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'event.manage')) {
            return ['status' => false, 'message' => 'Không có quyền'];
        }

        $conditions = ['idBo' => ['=', $id_bo, '']];
        $result = _update_info($conn, 'BOTIEUCHI', ['isKhoa'], [1], $conditions);

        return $result 
            ? ['status' => true, 'message' => 'Đã khóa bộ tiêu chí']
            : ['status' => false, 'message' => 'Lỗi hệ thống'];
    }

    function btc_gan_btc_cho_su_kien($conn, $id_nguoi_thuc_hien, $id_su_kien, $id_btc) {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'event.manage')) {
            return ['status' => false, 'message' => 'Không đủ quyền'];
        }

     
        $ton_tai = _is_exist($conn, 'SUKIEN_BTC', 'idSK', $id_su_kien);
        
        if ($ton_tai) {
            $conditions = ['idSK' => ['=', $id_su_kien, '']];
            $result = _update_info($conn, 'SUKIEN_BTC', ['idBTC'], [$id_btc], $conditions);
        } else {
            $result = _insert_info($conn, 'SUKIEN_BTC', ['idSK', 'idBTC'], [$id_su_kien, $id_btc]);
        }

        return $result 
            ? ['status' => true, 'message' => 'Đã gán bộ tiêu chí cho sự kiện']
            : ['status' => false, 'message' => 'Lỗi hệ thống'];
    }
?>