<?php
require_once __DIR__ . '/base.php';

/**
 * Đánh giá quy chế (tree) theo context.
 * $context là mảng dữ liệu đầu vào:
 * [
 *   'idTK' => 4,
 *   'idNhom' => 1,
 *   'idSanPham' => 2,
 *   'idVongThi' => 1,
 *   'idSK' => 1
 * ]
 */
function kiem_tra_quy_che($conn, $id_quy_che, $context = []) {
    $id_quy_che = (int)$id_quy_che;

    $sql = "SELECT idDieuKienCuoi FROM quyche_dieukien WHERE idQuyChe = $id_quy_che LIMIT 1";
    $result = mysqli_query($conn, $sql);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    if (!$row) {
        return false;
    }

    return danh_gia_dieu_kien($conn, (int)$row['idDieuKienCuoi'], $context);
}

/**
 * Đánh giá 1 điều kiện (đơn / tổ hợp).
 */
function danh_gia_dieu_kien($conn, $id_dieu_kien, $context) {
    $id_dieu_kien = (int)$id_dieu_kien;

    $sql = "SELECT loaiDieuKien FROM dieukien WHERE idDieuKien = $id_dieu_kien LIMIT 1";
    $result = mysqli_query($conn, $sql);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    if (!$row) {
        return false;
    }

    if ($row['loaiDieuKien'] == 'DON') {
        return danh_gia_dieu_kien_don($conn, $id_dieu_kien, $context);
    }

    if ($row['loaiDieuKien'] == 'TOHOP') {
        return danh_gia_dieu_kien_tohop($conn, $id_dieu_kien, $context);
    }

    return false;
}

/**
 * Đánh giá điều kiện đơn.
 */
function danh_gia_dieu_kien_don($conn, $id_dieu_kien, $context) {
    $sql = "SELECT dk.idThuocTinhKiemTra, dk.idToanTu, dk.giaTriSoSanh,
                   tt.tenTruongDL, tt.bangDuLieu
            FROM dieukien_don dk
            JOIN thuoctinh_kiemtra tt ON dk.idThuocTinhKiemTra = tt.idThuocTinhKiemTra
            WHERE dk.idDieuKien = $id_dieu_kien LIMIT 1";
    $result = mysqli_query($conn, $sql);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    if (!$row) {
        return false;
    }

    $gia_tri_thuc = lay_gia_tri_thuoc_tinh($conn, $row['bangDuLieu'], $row['tenTruongDL'], $context);
    if ($gia_tri_thuc == null) {
        return false;
    }

    return so_sanh_gia_tri($conn, $row['idToanTu'], $gia_tri_thuc, $row['giaTriSoSanh']);
}

/**
 * Đánh giá điều kiện tổ hợp (AND/OR).
 */
function danh_gia_dieu_kien_tohop($conn, $id_dieu_kien, $context) {
    $sql = "SELECT idDieuKienTrai, idDieuKienPhai, idToanTu
            FROM tohop_dieukien
            WHERE idDieuKien = $id_dieu_kien LIMIT 1";
    $result = mysqli_query($conn, $sql);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    if (!$row) {
        return false;
    }

    $left = danh_gia_dieu_kien($conn, (int)$row['idDieuKienTrai'], $context);
    $right = danh_gia_dieu_kien($conn, (int)$row['idDieuKienPhai'], $context);

    $sql_op = "SELECT kyHieu FROM toantu WHERE idToanTu = " . (int)$row['idToanTu'] . " LIMIT 1";
    $op_result = mysqli_query($conn, $sql_op);
    $op_row = $op_result ? mysqli_fetch_assoc($op_result) : null;

    if (!$op_row) {
        return false;
    }

    $op = $op_row['kyHieu'];

    if ($op == 'AND') {
        return $left && $right;
    }
    if ($op == 'OR') {
        return $left || $right;
    }

    return false;
}

/**
 * Lấy giá trị thực tế theo mapping trong thuoctinh_kiemtra.
 */
function lay_gia_tri_thuoc_tinh($conn, $bang, $cot, $context) {
    $bang = trim($bang);
    $cot = trim($cot);

    // Mapping điều kiện theo bảng
    if ($bang == 'sinhvien') {
        if (empty($context['idTK'])) return null;
        $idTK = (int)$context['idTK'];
        $sql = "SELECT $cot FROM sinhvien WHERE idTK = $idTK LIMIT 1";
    } elseif ($bang == 'nhom') {
        if (empty($context['idNhom'])) return null;
        $idNhom = (int)$context['idNhom'];
        $sql = "SELECT $cot FROM nhom WHERE idnhom = $idNhom LIMIT 1";
    } elseif ($bang == 'sanpham') {
        if (empty($context['idSanPham'])) return null;
        $idSanPham = (int)$context['idSanPham'];
        $sql = "SELECT $cot FROM sanpham WHERE idSanPham = $idSanPham LIMIT 1";
    } elseif ($bang == 'sanpham_vongthi') {
        if (empty($context['idSanPham']) || empty($context['idVongThi'])) return null;
        $idSanPham = (int)$context['idSanPham'];
        $idVongThi = (int)$context['idVongThi'];
        $sql = "SELECT $cot FROM sanpham_vongthi WHERE idSanPham = $idSanPham AND idVongThi = $idVongThi LIMIT 1";
    } elseif ($bang == 'ketqua') {
        if (empty($context['idNhom']) || empty($context['idSK'])) return null;
        $idNhom = (int)$context['idNhom'];
        $idSK = (int)$context['idSK'];
        $sql = "SELECT $cot FROM ketqua WHERE idNhom = $idNhom AND idSK = $idSK LIMIT 1";
    } else {
        return null;
    }

    $result = mysqli_query($conn, $sql);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    return $row ? $row[$cot] : null;
}

/**
 * So sánh giá trị theo toán tử.
 */
function so_sanh_gia_tri($conn, $id_toan_tu, $gia_tri_thuc, $gia_tri_so_sanh) {
    $id_toan_tu = (int)$id_toan_tu;

    $sql = "SELECT kyHieu FROM toantu WHERE idToanTu = $id_toan_tu LIMIT 1";
    $result = mysqli_query($conn, $sql);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    if (!$row) {
        return false;
    }

    $op = $row['kyHieu'];

    // Ép kiểu so sánh nếu là số
    if (is_numeric($gia_tri_thuc) && is_numeric($gia_tri_so_sanh)) {
        $gia_tri_thuc = (float)$gia_tri_thuc;
        $gia_tri_so_sanh = (float)$gia_tri_so_sanh;
    }

    switch ($op) {
        case '=': return $gia_tri_thuc == $gia_tri_so_sanh;
        case '>': return $gia_tri_thuc > $gia_tri_so_sanh;
        case '<': return $gia_tri_thuc < $gia_tri_so_sanh;
        case '>=': return $gia_tri_thuc >= $gia_tri_so_sanh;
        case '<=': return $gia_tri_thuc <= $gia_tri_so_sanh;
        default: return false;
    }
}