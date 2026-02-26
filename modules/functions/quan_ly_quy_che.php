<?php
/**
 * HÀM HỖ TRỢ KIỂM TRA QUYỀN (MỚI BỔ SUNG)
 * Tự động check quyền hệ thống ('admin_events') hoặc quyền sự kiện ('cauhinh_sukien')
 */
function xac_thuc_quyen_quy_che($conn, $id_user, $id_su_kien = 0) {
    // 1. Check quyền Quản trị hệ thống
    if (function_exists('kiem_tra_quyen_he_thong') && kiem_tra_quyen_he_thong($conn, $id_user, 'admin_events')) {
        return true;
    }
    // 2. Check quyền Ban tổ chức sự kiện
    if ($id_su_kien > 0 && function_exists('kiem_tra_quyen_su_kien')) {
        if (kiem_tra_quyen_su_kien($conn, $id_user, $id_su_kien, 'cauhinh_sukien')) {
            return true;
        }
    }
    return false;
}

function tao_quy_che(
    $conn,
    $id_nguoi_thuc_hien,
    $id_su_kien,
    $ten_quy_che,
    $loai_quy_che,
    $mo_ta = ''
) {
    if (!xac_thuc_quyen_quy_che($conn, $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không đủ quyền tạo quy chế'];
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

// Chú ý: Đã thêm $id_su_kien vào tham số để check quyền cho chuẩn
function tao_dieu_kien_don(
    $conn,
    $id_nguoi_thuc_hien,
    $id_su_kien, 
    $ten_dieu_kien,
    $id_thuoc_tinh,
    $id_toan_tu,
    $gia_tri_so_sanh,
    $mo_ta = ''
) {
    if (!xac_thuc_quyen_quy_che($conn, $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không đủ quyền tạo điều kiện'];
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
            return ['status' => false, 'message' => 'Lỗi lưu giá trị điều kiện'];
        }

        mysqli_commit($conn);
        return ['status' => true, 'idDieuKien' => $id_dieu_kien];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo điều kiện'];
    }
}

// Chú ý: Đã thêm $id_su_kien
function tao_to_hop_dieu_kien(
    $conn,
    $id_nguoi_thuc_hien,
    $id_su_kien,
    $id_dieu_kien_trai,
    $id_toan_tu_logic,
    $id_dieu_kien_phai,
    $ten_to_hop,
    $mo_ta = ''
) {
    if (!xac_thuc_quyen_quy_che($conn, $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không đủ quyền tạo tổ hợp'];
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
            return ['status' => false, 'message' => 'Lỗi tạo vỏ tổ hợp'];
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
            return ['status' => false, 'message' => 'Lỗi liên kết tổ hợp'];
        }

        mysqli_commit($conn);
        return ['status' => true, 'idDieuKien' => $id_to_hop];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo tổ hợp'];
    }
}

// Đã cập nhật kiểm tra quyền
function gan_dieu_kien_cho_quy_che(
    $conn,
    $id_nguoi_thuc_hien,
    $id_su_kien,
    $id_quy_che,
    $id_dieu_kien_cuoi
) {
    if (!xac_thuc_quyen_quy_che($conn, $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không đủ quyền gán quy chế'];
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


// ========================================================
// CORE ENGINE: DUYỆT CÂY LOGIC VÀ AUTO-FETCH DATA
// (Giữ nguyên toàn bộ phần Engine hoàn hảo chúng ta vừa làm ở bước trước)
// ========================================================
function xet_duyet_quy_che_su_kien($conn, $idSK, $loaiQuyChe, $id_doi_tuong) {
    $loaiQuyChe = mysqli_real_escape_string($conn, $loaiQuyChe);
    $sql_qc = "SELECT dk.idDieuKienCuoi 
               FROM quyche q 
               JOIN quyche_dieukien dk ON q.idQuyChe = dk.idQuyChe 
               WHERE q.idSK = $idSK AND q.loaiQuyChe = '$loaiQuyChe' LIMIT 1";
    
    $res = mysqli_query($conn, $sql_qc);
    if (!$res || mysqli_num_rows($res) == 0) {
        return true; 
    }
    
    $row = mysqli_fetch_assoc($res);
    $idDieuKienCuoi = $row['idDieuKienCuoi'];
    
    return kiem_tra_dieu_kien($conn, $idDieuKienCuoi, $id_doi_tuong);
}

function kiem_tra_dieu_kien($conn, $id_dieu_kien, $id_doi_tuong) {
    $dk = truy_van_mot_ban_ghi($conn, 'dieukien', 'idDieuKien', $id_dieu_kien);
    if (!$dk) return false;

    if ($dk['loaiDieuKien'] == 'DON') return kiem_tra_dieu_kien_don($conn, $id_dieu_kien, $id_doi_tuong);
    if ($dk['loaiDieuKien'] == 'TOHOP') return kiem_tra_to_hop_dieu_kien($conn, $id_dieu_kien, $id_doi_tuong);
    return false;
}

function kiem_tra_to_hop_dieu_kien($conn, $id_dieu_kien, $id_doi_tuong) {
    $to_hop = truy_van_mot_ban_ghi($conn, 'tohop_dieukien', 'idDieuKien', $id_dieu_kien);
    if (!$to_hop) return false;

    $ket_qua_trai = kiem_tra_dieu_kien($conn, $to_hop['idDieuKienTrai'], $id_doi_tuong);
    $ket_qua_phai = kiem_tra_dieu_kien($conn, $to_hop['idDieuKienPhai'], $id_doi_tuong);

    $toan_tu = truy_van_mot_ban_ghi($conn, 'toantu', 'idToanTu', $to_hop['idToanTu']);
    if (!$toan_tu) return false;

    if (strtoupper($toan_tu['kyHieu']) == 'AND') return $ket_qua_trai && $ket_qua_phai;
    if (strtoupper($toan_tu['kyHieu']) == 'OR') return $ket_qua_trai || $ket_qua_phai;
    return false;
}

function kiem_tra_dieu_kien_don($conn, $id_dieu_kien, $id_doi_tuong) {
    $dk = truy_van_mot_ban_ghi($conn, 'dieukien_don', 'idDieuKien', $id_dieu_kien);
    if (!$dk) return false;

    $gia_tri_thuc_te = lay_du_lieu_dong($conn, $dk['idThuocTinhKiemTra'], $id_doi_tuong);
    $gia_tri_so_sanh = $dk['giaTriSoSanh'];

    if ($gia_tri_thuc_te === null) return false; 

    $toan_tu = truy_van_mot_ban_ghi($conn, 'toantu', 'idToanTu', $dk['idToanTu']);
    if (!$toan_tu) return false;

    $kyHieu = $toan_tu['kyHieu'];

    if (is_numeric($gia_tri_thuc_te) && is_numeric($gia_tri_so_sanh)) {
        $gia_tri_thuc_te = (float)$gia_tri_thuc_te;
        $gia_tri_so_sanh = (float)$gia_tri_so_sanh;
    } else {
        $gia_tri_thuc_te = trim((string)$gia_tri_thuc_te);
        $gia_tri_so_sanh = trim((string)$gia_tri_so_sanh);
    }

    switch ($kyHieu) {
        case '=':  return $gia_tri_thuc_te == $gia_tri_so_sanh;
        case '>':  return $gia_tri_thuc_te > $gia_tri_so_sanh;
        case '<':  return $gia_tri_thuc_te < $gia_tri_so_sanh;
        case '>=': return $gia_tri_thuc_te >= $gia_tri_so_sanh;
        case '<=': return $gia_tri_thuc_te <= $gia_tri_so_sanh;
        case '!=': return $gia_tri_thuc_te != $gia_tri_so_sanh;
        default: return false;
    }
}

function lay_du_lieu_dong($conn, $idThuocTinh, $id_doi_tuong) {
    $tt = truy_van_mot_ban_ghi($conn, 'thuoctinh_kiemtra', 'idThuocTinhKiemTra', $idThuocTinh);
    if (!$tt) return null;

    $bang = $tt['bangDuLieu'];
    $truong = $tt['tenTruongDL'];
    $loaiApDung = $tt['loaiApDung'];

    $sql = "";
    if ($loaiApDung == 'THAMGIA' && $bang == 'sinhvien') {
        $idTK = (int)$id_doi_tuong;
        $sql = "SELECT $truong FROM $bang WHERE idTK = $idTK LIMIT 1";
    } 
    elseif ($loaiApDung == 'SANPHAM' && $bang == 'sanpham') {
        $idSP = (int)$id_doi_tuong;
        $sql = "SELECT $truong FROM $bang WHERE idSanPham = $idSP LIMIT 1";
    }
    elseif ($loaiApDung == 'VONGTHI' && $bang == 'sanpham_vongthi') {
         if (is_array($id_doi_tuong) && isset($id_doi_tuong['idSanPham']) && isset($id_doi_tuong['idVongThi'])) {
             $idSP = (int)$id_doi_tuong['idSanPham'];
             $idVT = (int)$id_doi_tuong['idVongThi'];
             $sql = "SELECT $truong FROM $bang WHERE idSanPham = $idSP AND idVongThi = $idVT LIMIT 1"; 
         }
    }
    elseif ($loaiApDung == 'GIAITHUONG' && $bang == 'ketqua') {
        $idNhom = (int)$id_doi_tuong;
        $sql = "SELECT $truong FROM $bang WHERE idNhom = $idNhom LIMIT 1";
    }

    if (empty($sql)) return null;

    $res = mysqli_query($conn, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        return $row[$truong];
    }

    return null;
}
?>