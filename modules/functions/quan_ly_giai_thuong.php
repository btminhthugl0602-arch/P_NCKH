<?php

/**
 * 1. Tạo Giải thưởng
 * CSDL: giaithuong(idSK, tengiaithuong, mota, soluong, giatri, thutu, isActive)
 */
function tao_giai_thuong($conn, $id_nguoi_tao, $id_sk, $ten_giai, $mo_ta, $so_luong, $gia_tri, $thu_tu) {
    if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_tao, 'event.manage')) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    $result = _insert_info($conn, 'giaithuong',
        ['idSK', 'tengiaithuong', 'mota', 'soluong', 'giatri', 'thutu', 'isActive'],
        [$id_sk, $ten_giai, $mo_ta, $so_luong, $gia_tri, $thu_tu, 1]
    );

    return $result 
        ? ['status' => true, 'message' => 'Đã thêm giải thưởng', 'idGiaiThuong' => mysqli_insert_id($conn)]
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * 2. Xóa/Hủy giải thưởng (Soft Delete)
 */
function xoa_giai_thuong($conn, $id_nguoi_xoa, $id_giai_thuong) {
    if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_xoa, 'event.manage')) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    $conditions = ['idGiaiThuong' => ['=', $id_giai_thuong, '']];
    $result = _update_info($conn, 'giaithuong', ['isActive'], [0], $conditions);

    return $result 
        ? ['status' => true, 'message' => 'Đã xóa giải thưởng']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}
?>