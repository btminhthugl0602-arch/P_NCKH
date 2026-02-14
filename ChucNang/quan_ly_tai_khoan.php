<?php

    function lay_id_quyen_theo_ma($conn, $ma_quyen) {
        return anh_xa_ma_quyen($conn, $ma_quyen);
    }

    function tao_tai_khoan_sinh_vien($conn, $id_tai_khoan, $ho_ten, $ma_so_sinh_vien, $id_lop) {
        $lop = truy_van_mot_ban_ghi($conn, 'lop', 'idLop', (int)$id_lop);
        if (!$lop) throw new Exception('Lớp không tồn tại');
        
        $id_khoa = $lop['idKhoa'];

        $result = _insert_info($conn, 'sinhvien', 
            ['idTK', 'tenSV', 'MSV', 'idLop', 'idKhoa'],
            [$id_tai_khoan, $ho_ten, $ma_so_sinh_vien, $id_lop, $id_khoa]
        );

        if (!$result) throw new Exception('Không thể tạo hồ sơ sinh viên');
    }

    function tao_tai_khoan_giang_vien($conn, $id_tai_khoan, $ho_ten, $id_khoa) {
        $result = _insert_info($conn, 'giangvien', 
            ['idTK', 'tenGV', 'idKhoa'],
            [$id_tai_khoan, $ho_ten, $id_khoa]
        );
        
        if (!$result) throw new Exception('Không thể tạo hồ sơ giảng viên');
    }

    function admin_tao_tai_khoan($conn, $id_nguoi_thuc_hien, $ten_dang_nhap, $mat_khau, $id_loai_tk, $ho_ten, $id_don_vi, $ma_so_sinh_vien = '') 
    {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'user.manage')) {
            return ['status' => false, 'message' => 'Không đủ quyền thao tác'];
        }

        if (kiem_tra_ton_tai_ban_ghi($conn, 'taikhoan', 'tenTK', $ten_dang_nhap)) {
            return ['status' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
        }

        mysqli_begin_transaction($conn);

        try { 
            
            $res = _insert_info($conn, 'taikhoan', 
                ['tenTK', 'matKhau', 'idLoaiTK', 'isActive'],
                [$ten_dang_nhap, $mat_khau, $id_loai_tk, 1]
            );

            if (!$res) throw new Exception("Lỗi tạo tài khoản chính");

            $id_tai_khoan = mysqli_insert_id($conn);
            if ($id_loai_tk == 3) { 
                tao_tai_khoan_sinh_vien($conn, $id_tai_khoan, $ho_ten, $ma_so_sinh_vien, $id_don_vi); // id_don_vi ở đây là idLop
            }
            elseif ($id_loai_tk == 2) { 
                tao_tai_khoan_giang_vien($conn, $id_tai_khoan, $ho_ten, $id_don_vi); // id_don_vi ở đây là idKhoa
            }

            mysqli_commit($conn);
            return ['status' => true, 'message' => 'Tạo tài khoản thành công'];

        } catch (Exception $e) {
            mysqli_rollback($conn);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    function admin_gan_quyen_cho_tai_khoan($conn, $id_nguoi_thuc_hien, $id_tai_khoan, $ma_quyen) {
        if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'system.assign_role')) {
            return ['status' => false, 'message' => 'Không đủ quyền thao tác'];
        }

        $id_quyen = lay_id_quyen_theo_ma($conn, $ma_quyen);
        if (!$id_quyen) return ['status' => false, 'message' => 'Mã quyền không hợp lệ'];

        $conditions = [
            'WHERE' => [
                'idTK', '=', $id_tai_khoan, 'AND',
                'idQuyen', '=', $id_quyen, ''
            ],
            'LIMIT' => [1, '','','']
        ];
        
        $exists = !empty(_select_info($conn, 'taikhoan_quyen', [], $conditions));
        
        if ($exists) {
            $update_cond = [
                'idTK' => ['=', $id_tai_khoan, 'AND'],
                'idQuyen' => ['=', $id_quyen, '']
            ];
             _update_info($conn, 'taikhoan_quyen', 
                ['isActive', 'thoiGianBatDau'], 
                [1, date('Y-m-d H:i:s')], 
                $update_cond
            );
        } else {
             _insert_info($conn, 'taikhoan_quyen', 
                ['idTK', 'idQuyen', 'isActive', 'thoiGianBatDau'],
                [$id_tai_khoan, $id_quyen, 1, date('Y-m-d H:i:s')]
            );
        }

        return ['status' => true, 'message' => 'Gán quyền thành công'];
    }
?>