<?php
function tao_quy_che(
    $conn,
    $id_nguoi_thuc_hien,
    $id_su_kien,
    $ten_quy_che,
    $loai_quy_che,
    $mo_ta = ''
) {
    $has_perm = kiem_tra_quyen_su_kien($conn, (int)$id_nguoi_thuc_hien, (int)$id_su_kien, 'cauhinh_sukien')
             || kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'tao_su_kien');
    if (!$has_perm) {
        return ['status' => false, 'message' => 'Không đủ quyền'];
    }

    $result = _insert_info(
        $conn,
        'quyche',
        ['idSK', 'tenQuyChe', 'moTa', 'loaiQuyChe'],
        [$id_su_kien, $ten_quy_che, $mo_ta, $loai_quy_che]
    );

    if (!$result) {
        return ['status' => false, 'message' => 'Không tạo được quy chế'];
    }

    return [
        'status' => true,
        'idQuyChe' => mysqli_insert_id($conn),
        'message' => 'Đã tạo quy chế'
    ];
}

function tao_dieu_kien_don(
    $conn,
    $id_nguoi_thuc_hien,
    $ten_dieu_kien,
    $id_thuoc_tinh,
    $id_toan_tu,
    $gia_tri_so_sanh,
    $mo_ta = ''
) {
    if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'tao_su_kien')) {
        return ['status' => false, 'message' => 'Không đủ quyền'];
    }

    mysqli_begin_transaction($conn);
    try {
        $ok = _insert_info(
            $conn,
            'dieukien',
            ['loaiDieuKien', 'tenDieuKien', 'moTa'],
            ['DON', $ten_dieu_kien, $mo_ta]
        );

        if (!$ok) {
            mysqli_rollback($conn);
            return ['status' => false, 'message' => 'Lỗi tạo điều kiện'];
        }

        $id_dieu_kien = mysqli_insert_id($conn);

        $ok = _insert_info(
            $conn,
            'dieukien_don',
            ['idDieuKien', 'idThuocTinhKiemTra', 'idToanTu', 'giaTriSoSanh'],
            [$id_dieu_kien, $id_thuoc_tinh, $id_toan_tu, $gia_tri_so_sanh]
        );

        if (!$ok) {
            mysqli_rollback($conn);
            return ['status' => false, 'message' => 'Lỗi tạo điều kiện'];
        }

        mysqli_commit($conn);
        return ['status' => true, 'idDieuKien' => $id_dieu_kien];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => false, 'message' => 'Lỗi tạo điều kiện'];
    }
}

function tao_to_hop_dieu_kien(
    $conn,
    $id_nguoi_thuc_hien,
    $id_dieu_kien_trai,
    $id_toan_tu_logic,
    $id_dieu_kien_phai,
    $ten_to_hop,
    $mo_ta = ''
) {
    if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'tao_su_kien')) {
        return ['status' => false, 'message' => 'Không đủ quyền'];
    }

    mysqli_begin_transaction($conn);
    try {
        $ok = _insert_info(
            $conn,
            'dieukien',
            ['loaiDieuKien', 'tenDieuKien', 'moTa'],
            ['TOHOP', $ten_to_hop, $mo_ta]
        );

        if (!$ok) {
            mysqli_rollback($conn);
            return ['status' => false, 'message' => 'Lỗi tạo tổ hợp'];
        }

        $id_to_hop = mysqli_insert_id($conn);

        $ok = _insert_info(
            $conn,
            'tohop_dieukien',
            ['idDieuKien', 'idDieuKienTrai', 'idDieuKienPhai', 'idToanTu'],
            [$id_to_hop, $id_dieu_kien_trai, $id_dieu_kien_phai, $id_toan_tu_logic]
        );

        if (!$ok) {
            mysqli_rollback($conn);
            return ['status' => false, 'message' => 'Lỗi tạo tổ hợp'];
        }

        mysqli_commit($conn);
        return ['status' => true, 'idDieuKien' => $id_to_hop];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => false, 'message' => 'Lỗi tạo tổ hợp'];
    }
}

function gan_dieu_kien_cho_quy_che(
    $conn,
    $id_nguoi_thuc_hien,
    $id_quy_che,
    $id_dieu_kien_cuoi
) {
    $qc = truy_van_mot_ban_ghi($conn, 'QUYCHE', 'idQuyChe', (int)$id_quy_che);
    $id_sk_qc = $qc ? (int)$qc['idSK'] : 0;
    $has_perm = ($id_sk_qc > 0 && kiem_tra_quyen_su_kien($conn, (int)$id_nguoi_thuc_hien, $id_sk_qc, 'cauhinh_sukien'))
             || kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'tao_su_kien');
    if (!$has_perm) {
        return ['status' => false, 'message' => 'Không đủ quyền'];
    }

    $exists = _is_exist($conn, 'quyche_dieukien', 'idQuyChe', $id_quy_che);

    if ($exists) {
        $conditions = ['idQuyChe' => ['=', $id_quy_che, '']];
        $ok = _update_info(
            $conn,
            'quyche_dieukien',
            ['idDieuKienCuoi'],
            [$id_dieu_kien_cuoi],
            $conditions
        );
    } else {
        $ok = _insert_info(
            $conn,
            'quyche_dieukien',
            ['idQuyChe', 'idDieuKienCuoi'],
            [$id_quy_che, $id_dieu_kien_cuoi]
        );
    }

    return $ok
        ? ['status' => true, 'message' => 'Đã gán điều kiện cho quy chế']
        : ['status' => false, 'message' => 'Không gán được điều kiện'];
}

function kiem_tra_dieu_kien(
    $conn,
    $id_dieu_kien,
    $du_lieu_dau_vao
) {
    $dk = truy_van_mot_ban_ghi($conn, 'DIEUKIEN', 'idDieuKien', $id_dieu_kien);
    if (!$dk) return false;

    if ($dk['loaiDieuKien'] == 'DON') {
        return kiem_tra_dieu_kien_don($conn, $id_dieu_kien, $du_lieu_dau_vao);
    }

    if ($dk['loaiDieuKien'] == 'TOHOP') {
        return kiem_tra_to_hop_dieu_kien($conn, $id_dieu_kien, $du_lieu_dau_vao);
    }

    return false;
}

function kiem_tra_dieu_kien_don(
    $conn,
    $id_dieu_kien,
    $du_lieu
) {
    $dk = truy_van_mot_ban_ghi($conn, 'DIEUKIEN_DON', 'idDieuKien', $id_dieu_kien);
    if (!$dk) return false;

    $gia_tri_thuc_te = $du_lieu[$dk['idThuocTinhKiemTra']] ?? null;
    $gia_tri_so_sanh = $dk['giaTriSoSanh'];

    switch ($dk['idToanTu']) {
        case 1: return $gia_tri_thuc_te == $gia_tri_so_sanh;
        case 2: return $gia_tri_thuc_te > $gia_tri_so_sanh;
        case 3: return $gia_tri_thuc_te < $gia_tri_so_sanh;
        case 4: return $gia_tri_thuc_te >= $gia_tri_so_sanh;
        case 5: return $gia_tri_thuc_te <= $gia_tri_so_sanh;
        case 6: return $gia_tri_thuc_te != $gia_tri_so_sanh;
        default: return false;
    }
}

function kiem_tra_to_hop_dieu_kien(
    $conn,
    $id_dieu_kien,
    $du_lieu
) {
    $to_hop = truy_van_mot_ban_ghi($conn, 'TOHOP_DIEUKIEN', 'idDieuKien', $id_dieu_kien);
    if (!$to_hop) return false;

    $ket_qua_trai = kiem_tra_dieu_kien($conn, $to_hop['idDieuKienTrai'], $du_lieu);
    $ket_qua_phai = kiem_tra_dieu_kien($conn, $to_hop['idDieuKienPhai'], $du_lieu);

    if ($to_hop['idToanTu'] == 6) {
        return $ket_qua_trai && $ket_qua_phai;
    }

    if ($to_hop['idToanTu'] == 7) {
        return $ket_qua_trai || $ket_qua_phai;
    }

    return false;
}
?>