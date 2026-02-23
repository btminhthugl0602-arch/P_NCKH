<?php
require_once __DIR__ . '/base.php'; 

function btc_tao_su_kien($conn, $id_nguoi_tao, $ten_su_kien, $mo_ta, $id_cap, $ngay_mo_dk = null, $ngay_dong_dk = null, $ngay_bat_dau = null, $ngay_ket_thuc = null, $is_active = 1) {
    // Đã sửa 'event.manage' thành 'tao_su_kien'
    if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_tao, 'tao_su_kien')) {
        return ['status' => false, 'message' => 'Không có quyền tạo sự kiện'];
    }

    if (empty(trim($ten_su_kien))) {
        return ['status' => false, 'message' => 'Tên sự kiện không được để trống'];
    }

    if ((int)$id_cap <= 0) {
        return ['status' => false, 'message' => 'Cấp tổ chức không hợp lệ'];
    }

    // Chuẩn hóa ngày rỗng => NULL
    $ngay_mo_dk = !empty($ngay_mo_dk) ? $ngay_mo_dk : null;
    $ngay_dong_dk = !empty($ngay_dong_dk) ? $ngay_dong_dk : null;
    $ngay_bat_dau = !empty($ngay_bat_dau) ? $ngay_bat_dau : null;
    $ngay_ket_thuc = !empty($ngay_ket_thuc) ? $ngay_ket_thuc : null;

    // Nếu đã nhập ngày bắt đầu thì phải có ngày kết thúc
    if (($ngay_bat_dau !== null && $ngay_ket_thuc === null) || ($ngay_bat_dau === null && $ngay_ket_thuc !== null)) {
        return ['status' => false, 'message' => 'Thời gian sự kiện không hợp lệ'];
    }

    $result = _insert_info($conn, 'SUKIEN', 
        ['tenSK', 'moTa', 'idCap', 'nguoiTao', 'ngayMoDangKy', 'ngayDongDangKy', 'ngayBatDau', 'ngayKetThuc', 'isActive'], 
        [$ten_su_kien, $mo_ta, $id_cap, $id_nguoi_tao, $ngay_mo_dk, $ngay_dong_dk, $ngay_bat_dau, $ngay_ket_thuc, $is_active]
    );

    if (!$result) {
        return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo sự kiện'];
    }

    $idSK = mysqli_insert_id($conn);

    // =========================================================
    // TỰ ĐỘNG CẤP QUYỀN "BAN TỔ CHỨC" CHO NGƯỜI TẠO
    // =========================================================
    $sql_copy_roles = "INSERT INTO vaitro_sukien (idSK, idVaiTroGoc, tenVaiTro, moTa, isSystem, isActive)
                       SELECT $idSK, idvatro, tenvaitro, mota, 1, 1 FROM vaitro";
    mysqli_query($conn, $sql_copy_roles);

    $sql_get_btc = "SELECT idVaiTroSK FROM vaitro_sukien WHERE idSK = $idSK AND idVaiTroGoc = 1 LIMIT 1";
    $res_btc = mysqli_query($conn, $sql_get_btc);
    
    if ($res_btc && mysqli_num_rows($res_btc) > 0) {
        $row_btc = mysqli_fetch_assoc($res_btc);
        $idVaiTroSK_BTC = (int)$row_btc['idVaiTroSK'];

        $sql_assign = "INSERT INTO taikhoan_vaitro_sukien 
                       (idTK, idSK, idVaiTroSK, idVaiTroGoc, nguonTao, idNguoiCap, ngayCap, isActive)
                       VALUES ($id_nguoi_tao, $idSK, $idVaiTroSK_BTC, 1, 'BTC_THEM', $id_nguoi_tao, NOW(), 1)";
        mysqli_query($conn, $sql_assign);
    }
    // =========================================================

    return [
        'status' => true,
        'message' => 'Đã khởi tạo sự kiện',
        'idSK' => $idSK
    ];
}

function btc_cap_nhat_su_kien($conn, $id_nguoi_thuc_hien, $id_su_kien, $ten_su_kien, $mo_ta, $id_cap, $ngay_mo_dk, $ngay_dong_dk, $ngay_bat_dau, $ngay_ket_thuc, $is_active) {
    // Đã sửa 'event.manage' thành 'tao_su_kien'
    if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'tao_su_kien')) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    $su_kien = truy_van_mot_ban_ghi($conn, 'SUKIEN', 'idSK', $id_su_kien);
    if (!$su_kien) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    $fields = ['tenSK', 'moTa', 'idCap', 'ngayMoDangKy', 'ngayDongDangKy', 'ngayBatDau', 'ngayKetThuc', 'isActive'];
    $values = [$ten_su_kien, $mo_ta, $id_cap, $ngay_mo_dk, $ngay_dong_dk, $ngay_bat_dau, $ngay_ket_thuc, $is_active];

    $conditions = ['idSK' => ['=', $id_su_kien, '']];
    
    $result = _update_info($conn, 'SUKIEN', $fields, $values, $conditions);

    if (!$result) {
         return ['status' => false, 'message' => 'Lỗi cập nhật sự kiện'];
    }

    return ['status' => true, 'message' => 'Cập nhật sự kiện thành công'];
}

function btc_lay_chi_tiet_su_kien($conn, $id_su_kien) {
    $id_su_kien = (int)$id_su_kien;
    if ($id_su_kien <= 0) {
        return null;
    }

    $su_kien = truy_van_mot_ban_ghi($conn, 'SUKIEN', 'idSK', $id_su_kien);
    if (!$su_kien) {
        return null;
    }

    $cap = !empty($su_kien['idCap']) ? truy_van_mot_ban_ghi($conn, 'CAP_TOCHUC', 'idCap', (int)$su_kien['idCap']) : null;
    $nguoi_tao = !empty($su_kien['nguoiTao']) ? truy_van_mot_ban_ghi($conn, 'TAIKHOAN', 'idTK', (int)$su_kien['nguoiTao']) : null;

    $su_kien['tenCap'] = $cap['tenCap'] ?? null;
    $su_kien['nguoiTaoTen'] = $nguoi_tao['tenTK'] ?? null;

    return $su_kien;
}
?>