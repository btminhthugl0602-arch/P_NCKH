<?php
    require_once __DIR__ . '/base.php';

    function lay_ban_ghi_theo_khoa($conn, $bang, $cot_khoa, $gia_tri) {
        return truy_van_mot_ban_ghi($conn, $bang, $cot_khoa, $gia_tri);
    }
    
    function lay_id_quyen_theo_ma($conn, $ma_quyen) {
        return anh_xa_ma_quyen($conn, $ma_quyen);
    }

    function tao_tai_khoan_sinh_vien($conn, $id_tai_khoan, $ho_ten, $ma_so_sinh_vien, $id_lop) {
        if (kiem_tra_ton_tai_ban_ghi($conn, 'SINHVIEN', 'MSV', $ma_so_sinh_vien)) {
            throw new Exception('Mã sinh viên đã tồn tại');
        }

        $lop = lay_ban_ghi_theo_khoa($conn, 'LOP', 'idLop', (int)$id_lop);
        if (!$lop) {
            throw new Exception('Lớp không tồn tại');
        }

        $id_khoa = $lop['idKhoa'];

        $result = _insert_info($conn, 'SINHVIEN', 
            ['idTK', 'tenSV', 'MSV', 'idLop', 'idKhoa'],
            [$id_tai_khoan, $ho_ten, $ma_so_sinh_vien, $id_lop, $id_khoa]
        );

        if (!$result) throw new Exception('Không thể tạo hồ sơ sinh viên');
    }

    function tao_tai_khoan_giang_vien($conn, $id_tai_khoan, $ho_ten, $id_khoa) {
        $result = _insert_info($conn, 'GIANGVIEN', 
            ['idTK', 'tenGV', 'idKhoa'],
            [$id_tai_khoan, $ho_ten, $id_khoa]
        );
        
        if (!$result) throw new Exception('Không thể tạo hồ sơ giảng viên');
    }

    function admin_tao_tai_khoan($conn, $id_nguoi_thuc_hien, $ten_dang_nhap, $mat_khau, $id_loai_tai_khoan, $ho_ten, $id_don_vi, $ma_so_sinh_vien = '') {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'user.manage')) {
            return ['status' => false, 'message' => 'Không đủ quyền thao tác'];
        }

        if (kiem_tra_ton_tai_ban_ghi($conn, 'TAIKHOAN', 'tenTK', $ten_dang_nhap)) {
            return ['status' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
        }

        mysqli_begin_transaction($conn);

        try {
            $mat_khau_ma_hoa = password_hash($mat_khau, PASSWORD_DEFAULT);

            $res = _insert_info($conn, 'TAIKHOAN', 
                ['tenTK', 'matKhau', 'idLoaiTK', 'isActive'],
                [$ten_dang_nhap, $mat_khau_ma_hoa, $id_loai_tai_khoan, 1]
            );

            if (!$res) throw new Exception("Lỗi tạo tài khoản chính");

            $id_tai_khoan = mysqli_insert_id($conn);

            if ($id_loai_tai_khoan == 3) { 
                tao_tai_khoan_sinh_vien($conn, $id_tai_khoan, $ho_ten, $ma_so_sinh_vien, $id_don_vi);
            }
            elseif ($id_loai_tai_khoan == 2) { 
                tao_tai_khoan_giang_vien($conn, $id_tai_khoan, $ho_ten, $id_don_vi);
            }

            mysqli_commit($conn);
            return ['status' => true, 'message' => 'Tạo tài khoản thành công'];

        } catch (Exception $e) {
            mysqli_rollback($conn);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    function admin_khoa_tai_khoan($conn, $id_nguoi_thuc_hien, $id_tai_khoan) {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'user.manage')) {
            return ['status' => false, 'message' => 'Không đủ quyền thao tác'];
        }

        if ($id_nguoi_thuc_hien == $id_tai_khoan) {
            return ['status' => false, 'message' => 'Không thể tự khóa tài khoản'];
        }

        $conditions = ['idTK' => ['=', $id_tai_khoan, '']];
        $result = _update_info($conn, 'TAIKHOAN', ['isActive'], [0], $conditions);

        return $result 
            ? ['status' => true, 'message' => 'Đã khóa tài khoản']
            : ['status' => false, 'message' => 'Lỗi hệ thống'];
    }

    function admin_gan_quyen_cho_tai_khoan($conn, $id_nguoi_thuc_hien, $id_tai_khoan, $ma_quyen) {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'system.assign_role')) {
            return ['status' => false, 'message' => 'Không đủ quyền thao tác'];
        }

        $id_quyen = lay_id_quyen_theo_ma($conn, $ma_quyen);
        if (!$id_quyen) {
            return ['status' => false, 'message' => 'Mã quyền không hợp lệ'];
        }

        $conditions = [
            'WHERE' => [
                'idTK', '=', $id_tai_khoan, 'AND',
                'idQuyen', '=', $id_quyen, ''
            ],
            'LIMIT' => [1, '','','']
        ];
        
        $exists = !empty(_select_info($conn, 'TAIKHOAN_QUYEN', [], $conditions));
        
        if ($exists) {
            $update_cond = [
                'idTK' => ['=', $id_tai_khoan, 'AND'],
                'idQuyen' => ['=', $id_quyen, '']
            ];
             _update_info($conn, 'TAIKHOAN_QUYEN', ['isActive'], [1], $update_cond);
        } else {
             _insert_info($conn, 'TAIKHOAN_QUYEN', 
                ['idTK', 'idQuyen', 'isActive', 'thoiGianBatDau'],
                [$id_tai_khoan, $id_quyen, 1, date('Y-m-d H:i:s')]
            );
        }

        return ['status' => true, 'message' => 'Gán quyền thành công'];
    }

    function admin_cap_nhat_quyen_tai_khoan($conn, $id_nguoi_thuc_hien, $id_tai_khoan, $danh_sach_ma_quyen) {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'system.assign_role')) {
            return ['status' => false, 'message' => 'Không đủ quyền thao tác'];
        }

        mysqli_begin_transaction($conn);

        try {
            $conditions = ['idTK' => ['=', $id_tai_khoan, '']];
            _update_info($conn, 'TAIKHOAN_QUYEN', ['isActive'], [0], $conditions);

            foreach ($danh_sach_ma_quyen as $ma_quyen) {
                admin_gan_quyen_cho_tai_khoan($conn, $id_nguoi_thuc_hien, $id_tai_khoan, $ma_quyen);
            }

            mysqli_commit($conn);
            return ['status' => true, 'message' => 'Cập nhật quyền thành công'];

        } catch (Exception $e) {
            mysqli_rollback($conn);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
?>