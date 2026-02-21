<?php

/**
 * Hàm kiểm tra sinh viên đã có nhóm trong sự kiện này chưa
 */
function kiem_tra_sv_co_nhom($conn, $id_tk, $id_sk) {
    $sql = "SELECT tv.idtk 
            FROM thanhviennhom tv 
            JOIN nhom n ON tv.idnhom = n.idnhom 
            WHERE tv.idtk = ? AND n.idSK = ? AND tv.trangthai = 1"; 
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id_tk, $id_sk);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    return mysqli_stmt_num_rows($stmt) > 0;
}

/**
 * 1. SINH VIÊN TẠO NHÓM MỚI
 * truyền vào $id_nhom_truong để check quyền (Chỉ SV mới được tạo nhóm), $id_sk để liên kết với sự kiện, tên nhóm, mô tả và số lượng thành viên tối đa
 */
function tao_nhom_moi($conn, $id_nhom_truong, $id_sk, $ten_nhom, $mo_ta, $so_luong_max = 5) {
    $su_kien = truy_van_mot_ban_ghi($conn, 'sukien', 'idSK', $id_sk);
    if (!$su_kien || $su_kien['isActive'] == 0) return ['status' => false, 'message' => 'Sự kiện không khả dụng'];
    
    $now = time();
    if ($now < strtotime($su_kien['ngayMoDangKy'])) return ['status' => false, 'message' => 'Cổng đăng ký chưa mở'];
    if ($now > strtotime($su_kien['ngayDongDangKy'])) return ['status' => false, 'message' => 'Đã hết hạn đăng ký'];

    $user = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $id_nhom_truong);
    if ($user['idLoaiTK'] != 3) {
        return ['status' => false, 'message' => 'Chỉ sinh viên mới được quyền tạo nhóm dự thi'];
    }

    if (kiem_tra_sv_co_nhom($conn, $id_nhom_truong, $id_sk)) {
        return ['status' => false, 'message' => 'Bạn đã tham gia một nhóm khác trong cuộc thi này'];
    }

    if (empty(trim($ten_nhom))) return ['status' => false, 'message' => 'Tên nhóm không được để trống'];

    mysqli_begin_transaction($conn);
    try {
        $ma_nhom = 'GRP_' . time() . rand(10, 99);

        // Tạo Nhóm
        $res_nhom = _insert_info($conn, 'nhom', 
            ['idSK', 'idnhomtruong', 'manhom', 'ngaytao', 'isActive'],
            [$id_sk, $id_nhom_truong, $ma_nhom, date('Y-m-d H:i:s'), 1]
        );
        if (!$res_nhom) throw new Exception('Lỗi tạo nhóm');
        $id_nhom = mysqli_insert_id($conn);

        // Tạo Thông tin nhóm
        $res_info = _insert_info($conn, 'thongtinnhom',
            ['idnhom', 'tennhom', 'mota', 'soluongtoida', 'dangtuyen'],
            [$id_nhom, $ten_nhom, $mo_ta, $so_luong_max, 1]
        );
        if (!$res_info) throw new Exception('Lỗi tạo thông tin nhóm');

        // Thêm Trưởng nhóm (Vai trò 1)
        $res_mem = _insert_info($conn, 'thanhviennhom',
            ['idnhom', 'idtk', 'idvaitronhom', 'trangthai', 'ngaythamgia'],
            [$id_nhom, $id_nhom_truong, 1, 1, date('Y-m-d H:i:s')]
        );
        if (!$res_mem) throw new Exception('Lỗi thêm trưởng nhóm');

        mysqli_commit($conn);
        return ['status' => true, 'message' => 'Tạo nhóm thành công', 'idNhom' => $id_nhom];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => false, 'message' => $e->getMessage()];
    }
}

/**
 * 2. GỬI YÊU CẦU / MỜI THÀNH VIÊN / ĐĂNG KÝ GVHD
 */
function gui_yeu_cau_nhom($conn, $id_nhom, $id_tk_doi_phuong, $chieu_moi, $loi_nhan = '') {
    // Kiểm tra đã là thành viên chưa (bảng thanhviennhom dùng cột trangthai, không có isActive)
    $kt_thanhvien = _select_info($conn, 'thanhviennhom', [], [
        'WHERE' => ['idnhom', '=', $id_nhom, 'AND', 'idtk', '=', $id_tk_doi_phuong, 'AND', 'trangthai', '=', 1, '']
    ]);
    if (!empty($kt_thanhvien)) return ['status' => false, 'message' => 'Người này đã là thành viên của nhóm'];

    $kt_yeucau = _select_info($conn, 'yeucau_thamgia', [], [
        'WHERE' => ['idNhom', '=', $id_nhom, 'AND', 'idTK', '=', $id_tk_doi_phuong, 'AND', 'trangThai', '=', 0, '']
    ]);
    if (!empty($kt_yeucau)) return ['status' => false, 'message' => 'Đang có yêu cầu chờ xử lý'];

    $doi_phuong = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $id_tk_doi_phuong);
    
    // Nếu mời GV (idLoaiTK=2) -> Check xem nhóm đã có GVHD chưa
    if ($chieu_moi == 0 && $doi_phuong['idLoaiTK'] == 2) {
        $kt_gv = _select_info($conn, 'thanhviennhom', [], [
            'WHERE' => ['idnhom', '=', $id_nhom, 'AND', 'idvaitronhom', '=', 3, 'AND', 'trangthai', '=', 1, '']
        ]);
        if (!empty($kt_gv)) return ['status' => false, 'message' => 'Nhóm đã có Giảng viên hướng dẫn rồi'];
    }

    $res = _insert_info($conn, 'yeucau_thamgia',
        ['idNhom', 'idTK', 'ChieuMoi', 'loiNhan', 'trangThai', 'ngayGui'],
        [$id_nhom, $id_tk_doi_phuong, $chieu_moi, $loi_nhan, 0, date('Y-m-d H:i:s')]
    );

    if ($res) {
        require_once __DIR__ . '/quan_ly_thong_bao.php';
        $tieu_de = ($doi_phuong['idLoaiTK'] == 2) ? "Lời mời hướng dẫn đề tài" : "Lời mời tham gia nhóm";
        gui_thong_bao($conn, 0, 0, $tieu_de, "Bạn có một yêu cầu mới.", 'CaNhan', 0, [$id_tk_doi_phuong]);
    }

    return $res 
        ? ['status' => true, 'message' => 'Gửi yêu cầu thành công']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * 3. PHÊ DUYỆT YÊU CẦU
 */
function duyet_yeu_cau_nhom($conn, $id_nguoi_duyet, $id_yeu_cau, $trang_thai_moi) {
    $yc = truy_van_mot_ban_ghi($conn, 'yeucau_thamgia', 'idYeuCau', $id_yeu_cau);
    if (!$yc) return ['status' => false, 'message' => 'Yêu cầu không tồn tại'];
    if ($yc['trangThai'] != 0) return ['status' => false, 'message' => 'Yêu cầu này đã được xử lý'];

    // Check quyền
    if ($yc['ChieuMoi'] == 1) { // SV Xin vào -> Trưởng nhóm duyệt
        $nhom = truy_van_mot_ban_ghi($conn, 'nhom', 'idnhom', $yc['idNhom']);
        if ($nhom['idnhomtruong'] != $id_nguoi_duyet) {
            return ['status' => false, 'message' => 'Chỉ trưởng nhóm mới được duyệt'];
        }
    } else { // Nhóm mời -> Người được mời duyệt
        if ($yc['idTK'] != $id_nguoi_duyet) {
            return ['status' => false, 'message' => 'Bạn không chính chủ'];
        }
    }

    mysqli_begin_transaction($conn);
    try {
        $cond = ['idYeuCau' => ['=', $id_yeu_cau, '']];
        _update_info($conn, 'yeucau_thamgia', 
            ['trangThai', 'ngayPhanHoi'], 
            [$trang_thai_moi, date('Y-m-d H:i:s')], 
            $cond
        );

        if ($trang_thai_moi == 1) {
            $user_join = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $yc['idTK']);
            $vai_tro = ($user_join['idLoaiTK'] == 2) ? 3 : 2; // GV -> Mentor (3), SV -> Member (2)

            $res_add = _insert_info($conn, 'thanhviennhom',
                ['idnhom', 'idtk', 'idvaitronhom', 'trangthai', 'ngaythamgia'],
                [$yc['idNhom'], $yc['idTK'], $vai_tro, 1, date('Y-m-d H:i:s')]
            );
            if (!$res_add) throw new Exception('Lỗi thêm thành viên');
        }

        mysqli_commit($conn);
        return ['status' => true, 'message' => ($trang_thai_moi == 1 ? "Đã chấp nhận" : "Đã từ chối")];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => false, 'message' => $e->getMessage()];
    }
}

/**
 * 4. RỜI NHÓM
 */
function roi_nhom($conn, $id_nguoi_thuc_hien, $id_nhom, $id_tk_bi_xoa) {
    if ($id_nguoi_thuc_hien != $id_tk_bi_xoa) {
        $nhom = truy_van_mot_ban_ghi($conn, 'nhom', 'idnhom', $id_nhom);
        if (!$nhom || $nhom['idnhomtruong'] != $id_nguoi_thuc_hien) {
            return ['status' => false, 'message' => 'Bạn không có quyền mời thành viên ra khỏi nhóm'];
        }
    }

    $conditions_select = [
        'WHERE' => ['idnhom', '=', $id_nhom, 'AND', 'idtk', '=', $id_tk_bi_xoa, 'AND', 'trangthai', '=', 1, '']
    ];
    $tv = _select_info($conn, 'thanhviennhom', [], $conditions_select);
    
    if (!empty($tv) && $tv[0]['idvaitronhom'] == 1) {
        return ['status' => false, 'message' => 'Trưởng nhóm không thể rời đi. Hãy chuyển quyền hoặc giải tán nhóm.'];
    }

    $conditions_update = [
        'idnhom' => ['=', $id_nhom, 'AND'],
        'idtk'   => ['=', $id_tk_bi_xoa, '']
    ];
    // Đặt trangthai = 0 để đánh dấu đã rời nhóm (không có cột isActive trong bảng này)
    $result = _update_info($conn, 'thanhviennhom', ['trangthai'], [0], $conditions_update);
    
    return $result ? ['status' => true, 'message' => 'Đã rời khỏi nhóm'] : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * 5. TÌM KIẾM GIẢNG VIÊN (Để mời làm GVHD)
 */
function tim_kiem_giang_vien($conn, $keyword) {
    $keyword = "%" . trim($keyword) . "%";
    $sql = "SELECT tk.idTK, gv.tenGV, gv.idKhoa 
            FROM taikhoan tk 
            JOIN giangvien gv ON tk.idTK = gv.idTK 
            WHERE tk.idLoaiTK = 2 
            AND tk.isActive = 1
            AND (gv.tenGV LIKE ?) 
            LIMIT 10";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $keyword);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * 6. TÌM KIẾM SINH VIÊN (Để mời vào nhóm)
 */
function tim_kiem_sinh_vien($conn, $keyword) {
    $keyword = "%" . trim($keyword) . "%";
    $sql = "SELECT tk.idTK, sv.tenSV, sv.MSV, l.tenLop 
            FROM taikhoan tk 
            JOIN sinhvien sv ON tk.idTK = sv.idTK 
            LEFT JOIN lop l ON sv.idLop = l.idLop
            WHERE tk.idLoaiTK = 3 
            AND tk.isActive = 1
            AND (sv.tenSV LIKE ? OR sv.MSV LIKE ?) 
            LIMIT 10";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $keyword, $keyword);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}
?>