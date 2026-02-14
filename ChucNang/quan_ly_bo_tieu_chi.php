<?php 

    function tao_tieu_chi($conn, $id_nguoi_tao, $noi_dung, $diem_toi_da = 10) {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_tao, 'criteria.manage')) {
            return ['status' => false, 'message' => 'Không có quyền tạo tiêu chí'];
        }

        $result = _insert_info($conn, 'tieuchi', 
            ['noiDungTieuChi', 'diemToiDa'],
            [$noi_dung, $diem_toi_da]
        );

        return $result 
            ? ['status' => true, 'message' => 'Đã tạo tiêu chí', 'idTieuChi' => mysqli_insert_id($conn)]
            : ['status' => false, 'message' => 'Lỗi hệ thống'];
    }

    function tao_bo_tieu_chi($conn, $id_nguoi_tao, $ten_bo, $mo_ta = '') {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_tao, 'event.manage')) {
            return ['status' => false, 'message' => 'Không có quyền'];
        }

        $result = _insert_info($conn, 'botieuchi',
            ['tenBoTieuChi', 'moTa'],
            [$ten_bo, $mo_ta]
        );

        return $result ? [
            'status' => true,
            'message' => 'Đã tạo bộ tiêu chí',
            'idBo' => mysqli_insert_id($conn)
        ] : ['status' => false, 'message' => 'Lỗi hệ thống'];
    }

    function them_tieu_chi_vao_bo($conn, $id_nguoi_thuc_hien, $id_bo, $id_tieu_chi, $ty_trong = 1.0) {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'event.manage')) {
            return ['status' => false, 'message' => 'Không có quyền'];
        }

        $result = _insert_info($conn, 'botieuchi_tieuchi',
            ['idBoTieuChi', 'idTieuChi', 'tyTrong'],
            [$id_bo, $id_tieu_chi, $ty_trong]
        );

        return $result 
            ? ['status' => true, 'message' => 'Đã thêm tiêu chí vào bộ']
            : ['status' => false, 'message' => 'Lỗi hệ thống'];
    }

    function gan_btc_cho_vong_thi($conn, $id_nguoi_thuc_hien, $id_su_kien, $id_vong_thi, $id_btc) {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'event.manage')) {
            return ['status' => false, 'message' => 'Không đủ quyền'];
        }

        $conditions = [
            'WHERE' => [
                'idSK', '=', $id_su_kien, 'AND',
                'idVongThi', '=', $id_vong_thi, ''
            ]
        ];
        
        $exists = !empty(_select_info($conn, 'cauhinh_tieuchi_sk', [], $conditions));
        
        if ($exists) {
            $update_cond = [
                'idSK' => ['=', $id_su_kien, 'AND'],
                'idVongThi' => ['=', $id_vong_thi, '']
            ];
            $result = _update_info($conn, 'cauhinh_tieuchi_sk', ['idBoTieuChi'], [$id_btc], $update_cond);
        } else {
            $result = _insert_info($conn, 'cauhinh_tieuchi_sk', 
                ['idSK', 'idVongThi', 'idBoTieuChi'], 
                [$id_su_kien, $id_vong_thi, $id_btc]
            );
        }

        return $result 
            ? ['status' => true, 'message' => 'Đã cấu hình bộ tiêu chí cho vòng thi']
            : ['status' => false, 'message' => 'Lỗi hệ thống'];
    }
?>