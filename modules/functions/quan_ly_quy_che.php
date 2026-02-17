<?php
    function tao_quy_che(
    $conn,
    $id_nguoi_thuc_hien,
    $id_su_kien,
    $ten_quy_che,
    $mo_ta = ''
    ) {
    if (!xac_thuc_quyen_truy_cap($conn, $id_nguoi_thuc_hien, 'event.manage')) {
        return ['status' => false, 'message' => 'Không đủ quyền'];
    }

    $ten_quy_che = chuan_hoa_chuoi_sql($conn, $ten_quy_che);
    $mo_ta = chuan_hoa_chuoi_sql($conn, $mo_ta);

    $sql = "
        INSERT INTO DSQUYCHE (idSK, tenQuyChe, moTa)
        VALUES ('$id_su_kien', '$ten_quy_che', '$mo_ta')
    ";

    if (!mysqli_query($conn, $sql)) {
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
        if (!xac_thuc_quyen_truy_cap($conn, $id_nguoi_thuc_hien, 'event.manage')) {
            return ['status' => false, 'message' => 'Không đủ quyền'];
        }

        mysqli_begin_transaction($conn);
        try {
            $ten_dieu_kien = chuan_hoa_chuoi_sql($conn, $ten_dieu_kien);
            $gia_tri_so_sanh = chuan_hoa_chuoi_sql($conn, $gia_tri_so_sanh);
            $mo_ta = chuan_hoa_chuoi_sql($conn, $mo_ta);

            mysqli_query($conn, "
                INSERT INTO DIEUKIEN (loaiDieuKien, tenDieuKien, moTa)
                VALUES ('DON', '$ten_dieu_kien', '$mo_ta')
            ");

            $id_dieu_kien = mysqli_insert_id($conn);

            mysqli_query($conn, "
                INSERT INTO DIEUKIEN_DON
                (idDieuKien, idThuocTinhKiemTra, idToanTu, giaTriSoSanh)
                VALUES ('$id_dieu_kien', '$id_thuoc_tinh', '$id_toan_tu', '$gia_tri_so_sanh')
            ");

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
        if (!xac_thuc_quyen_truy_cap($conn, $id_nguoi_thuc_hien, 'event.manage')) {
            return ['status' => false, 'message' => 'Không đủ quyền'];
        }

        mysqli_begin_transaction($conn);
        try {
            $ten_to_hop = chuan_hoa_chuoi_sql($conn, $ten_to_hop);
            $mo_ta = chuan_hoa_chuoi_sql($conn, $mo_ta);

            mysqli_query($conn, "
                INSERT INTO DIEUKIEN (loaiDieuKien, tenDieuKien, moTa)
                VALUES ('TOHOP', '$ten_to_hop', '$mo_ta')
            ");

            $id_to_hop = mysqli_insert_id($conn);

            mysqli_query($conn, "
                INSERT INTO TOHOP_DIEUKIEN
                (idDieuKien, idDieuKienTrai, idDieuKienPhai, idToanTu)
                VALUES ('$id_to_hop', '$id_dieu_kien_trai', '$id_dieu_kien_phai', '$id_toan_tu_logic')
            ");

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
        if (!xac_thuc_quyen_truy_cap($conn, $id_nguoi_thuc_hien, 'event.manage')) {
            return ['status' => false, 'message' => 'Không đủ quyền'];
        }

        $sql = "
            INSERT INTO QUYCHE_DIEUKIEN (idQuyChe, idDieuKienCuoi)
            VALUES ('$id_quy_che', '$id_dieu_kien_cuoi')
            ON DUPLICATE KEY UPDATE idDieuKienCuoi = '$id_dieu_kien_cuoi'
        ";

        mysqli_query($conn, $sql);
        return ['status' => true, 'message' => 'Đã gán điều kiện cho quy chế'];
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

        if ($to_hop['idToanTu'] == 1) {
            return $ket_qua_trai && $ket_qua_phai;
        }

        if ($to_hop['idToanTu'] == 2) {
            return $ket_qua_trai || $ket_qua_phai;
        }

        return false;
    }
?>
