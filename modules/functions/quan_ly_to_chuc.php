<?php

/**
 * 1. Lập lịch trình tổ chức
 */
function tao_lich_trinh($conn, $id_nguoi_tao, $id_sk, $ten_hoat_dong, $thoi_gian, $dia_diem, $id_vong_thi = null) {
    if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_tao, 'event.manage')) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    // CSDL: lichtrinh(idSK, idVongThi, tenHoatDong, thoiGian, diaDiem)
    $result = _insert_info($conn, 'lichtrinh',
        ['idSK', 'idVongThi', 'tenHoatDong', 'thoiGian', 'diaDiem'],
        [$id_sk, $id_vong_thi, $ten_hoat_dong, $thoi_gian, $dia_diem]
    );

    return $result 
        ? ['status' => true, 'message' => 'Đã thêm lịch trình']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * 2. Điểm danh (Cho buổi bảo vệ/hoạt động)
 */
function ghi_nhan_diem_danh($conn, $id_nguoi_check, $id_nhom, $id_tk_sv, $trang_thai_hien_dien, $ghi_chu = '') {
    // Validate: $id_nguoi_check phải là BTC hoặc Trưởng nhóm (tuỳ quy chế)
    
    // CSDL: diemdanh(idNhom, idTK, thoiGianDiemDanh, hienDien, ghiChu)
    $result = _insert_info($conn, 'diemdanh',
        ['idNhom', 'idTK', 'thoiGianDiemDanh', 'hienDien', 'ghiChu'],
        [$id_nhom, $id_tk_sv, date('Y-m-d H:i:s'), $trang_thai_hien_dien ? 1 : 0, $ghi_chu]
    );

    return $result 
        ? ['status' => true, 'message' => 'Đã điểm danh']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * 3. Phân công Ban tổ chức (Hội đồng)
 */
function them_thanh_vien_btc($conn, $id_admin, $id_sk, $id_tk_can_bo, $chuc_vu) {
    if (!kiem_tra_quyen_he_thong($conn, $id_admin, 'event.manage')) return ['status' => false, 'message' => 'Không có quyền'];

    // CSDL: bantochuc(idSK, idTK, chucVu)
    $result = _insert_info($conn, 'bantochuc',
        ['idSK', 'idTK', 'chucVu'],
        [$id_sk, $id_tk_can_bo, $chuc_vu]
    );

    return $result ? ['status' => true, 'message' => 'Đã thêm cán bộ vào BTC'] : ['status' => false, 'message' => 'Lỗi'];
}
?>