<?php 
require_once __DIR__ . '/base.php';

function tao_tieu_chi($conn, $id_nguoi_tao, $noi_dung, $diem_toi_da = 10.00) {
    if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_tao, 'admin_criteria')) {
        return ['status' => false, 'message' => 'Không có quyền tạo tiêu chí'];
    }

    if (empty(trim($noi_dung))) {
        return ['status' => false, 'message' => 'Nội dung tiêu chí không được trống'];
    }

    $result = _insert_info($conn, 'tieuchi', 
        ['noiDungTieuChi', 'diemToiDa'],
        [$noi_dung, $diem_toi_da]
    );

    return $result 
        ? ['status' => true, 'message' => 'Đã tạo tiêu chí', 'idTieuChi' => mysqli_insert_id($conn)]
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

function tao_bo_tieu_chi($conn, $id_nguoi_tao, $id_su_kien, $ten_bo, $mo_ta = '') {
    $has_perm = kiem_tra_quyen_su_kien($conn, (int)$id_nguoi_tao, (int)$id_su_kien, 'cauhinh_sukien')
             || kiem_tra_quyen_he_thong($conn, $id_nguoi_tao, 'tao_su_kien');
    if (!$has_perm) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    $su_kien = truy_van_mot_ban_ghi($conn, 'SUKIEN', 'idSK', $id_su_kien);
    if (!$su_kien) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    $result = _insert_info($conn, 'botieuchi',
        ['tenBoTieuChi', 'moTa'],
        [$ten_bo, $mo_ta]
    );

    return $result ? [
        'status' => true,
        'message' => 'Đã tạo bộ tiêu chí',
        'idBoTieuChi' => mysqli_insert_id($conn)
    ] : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

function them_tieu_chi_vao_bo($conn, $id_nguoi_thuc_hien, $id_bo, $id_tieu_chi, $ty_trong = 1.00) {
    $bo = truy_van_mot_ban_ghi($conn, 'BOTIEUCHI', 'idBoTieuChi', (int)$id_bo);
    // botieuchi không có idSK trực tiếp — kiểm tra bằng quyền hệ thống hoặc admin_criteria
    if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'tao_su_kien') && !kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'admin_criteria')) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    if (!kiem_tra_ton_tai_ban_ghi($conn, 'BOTIEUCHI', 'idBoTieuChi', $id_bo)) {
        return ['status' => false, 'message' => 'Bộ tiêu chí không tồn tại'];
    }
    if (!kiem_tra_ton_tai_ban_ghi($conn, 'TIEUCHI', 'idTieuChi', $id_tieu_chi)) {
        return ['status' => false, 'message' => 'Tiêu chí không tồn tại'];
    }

    $exists = _select_info($conn, 'BOTIEUCHI_TIEUCHI', [], [
        'WHERE' => [
            'idBoTieuChi', '=', $id_bo, 'AND',
            'idTieuChi', '=', $id_tieu_chi, ''
        ],
        'LIMIT' => [1, '', '', '']
    ]);

    if (!empty($exists)) {
        $conditions = [
            'idBoTieuChi' => ['=', $id_bo, 'AND'],
            'idTieuChi' => ['=', $id_tieu_chi, '']
        ];
        $result = _update_info($conn, 'BOTIEUCHI_TIEUCHI', ['tyTrong'], [$ty_trong], $conditions);
        return $result
            ? ['status' => true, 'message' => 'Đã cập nhật trọng số tiêu chí']
            : ['status' => false, 'message' => 'Lỗi cập nhật'];
    }

    $result = _insert_info($conn, 'BOTIEUCHI_TIEUCHI',
        ['idBoTieuChi', 'idTieuChi', 'tyTrong'],
        [$id_bo, $id_tieu_chi, $ty_trong]
    );

    return $result 
        ? ['status' => true, 'message' => 'Đã thêm tiêu chí vào bộ']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

function gan_bo_tieu_chi_vao_vong($conn, $id_nguoi_thuc_hien, $id_su_kien, $id_vong_thi, $id_bo) {
    $has_perm = kiem_tra_quyen_su_kien($conn, (int)$id_nguoi_thuc_hien, (int)$id_su_kien, 'cauhinh_sukien')
             || kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'tao_su_kien');
    if (!$has_perm) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    $ton_tai = _is_exist($conn, 'CAUHINH_TIEUCHI_SK', 'idSK', $id_su_kien);

    if ($ton_tai) {
        $conditions = ['idSK' => ['=', $id_su_kien, 'AND', 'idVongThi', '=', $id_vong_thi, '']];
        $result = _update_info($conn, 'CAUHINH_TIEUCHI_SK', ['idBoTieuChi'], [$id_bo], $conditions);
    } else {
        $result = _insert_info($conn, 'CAUHINH_TIEUCHI_SK', ['idSK', 'idVongThi', 'idBoTieuChi'], [$id_su_kien, $id_vong_thi, $id_bo]);
    }

    return $result 
        ? ['status' => true, 'message' => 'Đã gán bộ tiêu chí cho vòng']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}
?>