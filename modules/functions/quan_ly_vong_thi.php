<?php

/**
 * 1. Tạo Vòng thi mới
 * truyền vào $id_nguoi_tao để check quyền (BTC mới được tạo vòng thi), $id_sk để liên kết với sự kiện, tên vòng thi, mô tả, thứ tự hiển thị, ngày bắt đầu và ngày kết thúc
 */
function tao_vong_thi($conn, $id_nguoi_tao, $id_sk, $ten_vong, $mo_ta, $thu_tu, $ngay_bd, $ngay_kt) {
    if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_tao, 'event.manage')) {
        return ['status' => false, 'message' => 'Không có quyền cấu hình sự kiện'];
    }

    // Validate ngày tháng
    if (strtotime($ngay_bd) > strtotime($ngay_kt)) {
        return ['status' => false, 'message' => 'Ngày bắt đầu không được lớn hơn ngày kết thúc'];
    }

    $result = _insert_info($conn, 'vongthi',
        ['idSK', 'tenVongThi', 'moTa', 'thuTu', 'ngayBatDau', 'ngayKetThuc'],
        [$id_sk, $ten_vong, $mo_ta, $thu_tu, $ngay_bd, $ngay_kt]
    );

    return $result 
        ? ['status' => true, 'message' => 'Đã tạo vòng thi', 'idVongThi' => mysqli_insert_id($conn)]
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * 2. Cập nhật Vòng thi
 */
function cap_nhat_vong_thi($conn, $id_nguoi_sua, $id_vong_thi, $ten_vong, $mo_ta, $ngay_bd, $ngay_kt) {
    if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_sua, 'event.manage')) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    $conditions = ['idVongThi' => ['=', $id_vong_thi, '']];
    $result = _update_info($conn, 'vongthi',
        ['tenVongThi', 'moTa', 'ngayBatDau', 'ngayKetThuc'],
        [$ten_vong, $mo_ta, $ngay_bd, $ngay_kt],
        $conditions
    );

    return $result 
        ? ['status' => true, 'message' => 'Cập nhật vòng thi thành công']
        : ['status' => false, 'message' => 'Lỗi cập nhật'];
}

/**
 * 3. Lấy danh sách vòng thi của sự kiện (Sắp xếp theo thứ tự)
 */
function lay_ds_vong_thi($conn, $id_sk) {
    $conditions = [
        'WHERE' => ['idSK', '=', $id_sk, ''],
        'ORDER BY' => ['thuTu', 'ASC', '', '']
    ];
    return _select_info($conn, 'vongthi', [], $conditions);
}
?>