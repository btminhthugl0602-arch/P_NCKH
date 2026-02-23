<?php

/**
 * Hàm kiểm tra sinh viên đã có nhóm trong sự kiện này chưa
 */
function kiem_tra_sv_co_nhom($conn, $id_tk, $id_sk) {
    $sql = "SELECT tv.idtk 
            FROM thanhviennhom tv 
            JOIN nhom n ON tv.idnhom = n.idnhom 
            WHERE tv.idtk = ? AND n.idSK = ? AND tv.trangthai = 1 AND n.isActive = 1"; 
    
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
function tao_nhom_moi($conn, $idTK, $idSK, $tenNhom, $moTa, $soLuongToiDa) {
    // 1. Kiểm tra xem người dùng đã là thành viên của một nhóm nào trong sự kiện này chưa
    $check_exist = mysqli_query($conn, "
        SELECT 1 FROM thanhviennhom tv 
        JOIN nhom n ON tv.idnhom = n.idnhom 
        WHERE tv.idtk = $idTK AND n.idSK = $idSK AND tv.trangthai = 1
    ");
    if (mysqli_num_rows($check_exist) > 0) {
        return ['status' => false, 'message' => 'Bạn đã tham gia một nhóm trong sự kiện này rồi.'];
    }

    // 2. Tạo mã nhóm ngẫu nhiên (Ví dụ: GRP_SK1_TIMESTAMP)
    $maNhom = 'GRP_' . $idSK . '_' . time();

    mysqli_begin_transaction($conn);
    try {
        // 3. Insert vào bảng nhom
        $sqlNhom = "INSERT INTO nhom (idSK, idnhomtruong, manhom, ngaytao, isActive) VALUES ($idSK, $idTK, '$maNhom', NOW(), 1)";
        if (!mysqli_query($conn, $sqlNhom)) throw new Exception('Lỗi tạo nhóm (bảng nhom).');
        
        $idNhomMoi = mysqli_insert_id($conn);

        // 4. Insert vào bảng thongtinnhom
        $tenNhomSafe = mysqli_real_escape_string($conn, $tenNhom);
        $moTaSafe = mysqli_real_escape_string($conn, $moTa);
        $sqlThongTin = "INSERT INTO thongtinnhom (idnhom, tennhom, mota, soluongtoida, dangtuyen) VALUES ($idNhomMoi, '$tenNhomSafe', '$moTaSafe', $soLuongToiDa, 1)";
        if (!mysqli_query($conn, $sqlThongTin)) throw new Exception('Lỗi lưu thông tin nhóm.');

        // 5. Insert người tạo vào bảng thanhviennhom với vai trò là Trưởng nhóm (idvaitronhom = 1), trạng thái = 1 (đã duyệt)
        $sqlThanhVien = "INSERT INTO thanhviennhom (idnhom, idtk, idvaitronhom, trangthai, ngaythamgia) VALUES ($idNhomMoi, $idTK, 1, 1, NOW())";
        if (!mysqli_query($conn, $sqlThanhVien)) throw new Exception('Lỗi thêm thành viên trưởng nhóm.');

        mysqli_commit($conn);
        return ['status' => true, 'message' => 'Tạo nhóm thành công', 'idnhom' => $idNhomMoi];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => false, 'message' => $e->getMessage()];
    }
}

/**
 * 2. GỬI YÊU CẦU / MỜI THÀNH VIÊN / ĐĂNG KÝ GVHD
 */
function gui_yeu_cau_nhom($conn, $id_nhom, $id_tk_doi_phuong, $chieu_moi, $loi_nhan = '') {
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
    // Bảng thanhviennhom không có cột isActive, chỉ update trangthai
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