<?php
require_once __DIR__ . '/base.php';

function btc_tao_su_kien($conn, $id_nguoi_tao, $ten_su_kien, $mo_ta, $id_cap, $ngay_mo_dk = null, $ngay_dong_dk = null, $ngay_bat_dau = null, $ngay_ket_thuc = null, $is_active = 1)
{

    // ── Kiểm tra quyền ──────────────────────────────────────
    if (!kiem_tra_quyen_he_thong($conn, $id_nguoi_tao, 'tao_su_kien')) {
        return ['status' => false, 'message' => 'Không có quyền tạo sự kiện'];
    }

    // ── Validate tên ────────────────────────────────────────
    if (empty(trim($ten_su_kien))) {
        return ['status' => false, 'message' => 'Tên sự kiện không được để trống'];
    }

    // ── Chuẩn hóa ngày rỗng → NULL ─────────────────────────
    // (DB cần ALTER: ngayMoDangKy / ngayDongDangKy → allow NULL)
    $ngay_mo_dk   = !empty($ngay_mo_dk)   ? $ngay_mo_dk   : null;
    $ngay_dong_dk = !empty($ngay_dong_dk) ? $ngay_dong_dk : null;
    $ngay_bat_dau = !empty($ngay_bat_dau) ? $ngay_bat_dau : null;
    $ngay_ket_thuc = !empty($ngay_ket_thuc) ? $ngay_ket_thuc : null;

    // ── Build INSERT động — chỉ đưa vào field có giá trị ──
    $fields = ['tenSK', 'moTa', 'nguoiTao', 'isActive'];
    $values = [$ten_su_kien, $mo_ta, $id_nguoi_tao, $is_active];

    // idCap tuỳ chọn
    if ($id_cap !== null && (int)$id_cap > 0) {
        $fields[] = 'idCap';
        $values[] = (int)$id_cap;
    }
    // Ngày tuỳ chọn
    $date_fields = [
        'ngayMoDangKy'   => $ngay_mo_dk,
        'ngayDongDangKy' => $ngay_dong_dk,
        'ngayBatDau'     => $ngay_bat_dau,
        'ngayKetThuc'    => $ngay_ket_thuc,
    ];
    foreach ($date_fields as $col => $val) {
        if ($val !== null) {
            $fields[] = $col;
            $values[] = $val;
        }
    }

    $result = _insert_info($conn, 'SUKIEN', $fields, $values);

    if (!$result) {
        return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo sự kiện'];
    }

    $idSK = mysqli_insert_id($conn);

    // =========================================================
    // TỰ ĐỘNG GÁN VAI TRÒ BTC CHO NGƯỜI TẠO
    // Kiến trúc mới: idVaiTro=1 (BTC) trỏ thẳng vào bảng vaitro
    // Không còn copy sang vaitro_sukien, không còn bantochuc
    // =========================================================
    $sql_assign = "INSERT INTO taikhoan_vaitro_sukien
                       (idTK, idSK, idVaiTro, nguonTao, idNguoiCap, ngayCap, isActive)
                   VALUES
                       ($id_nguoi_tao, $idSK, 1, 'BTC_THEM', $id_nguoi_tao, NOW(), 1)";
    mysqli_query($conn, $sql_assign);
    // =========================================================

    // =========================================================
    // GỬI THÔNG BÁO KHI SỰ KIỆN ĐƯỢC CÔNG KHAI NGAY LÚC TẠO
    // =========================================================
    if ((int)$is_active === 1) {
        _gui_thong_bao_su_kien_moi($conn, $idSK, $ten_su_kien, $id_nguoi_tao);
    }
    // =========================================================

    return [
        'status'  => true,
        'message' => 'Đã khởi tạo sự kiện',
        'idSK'    => $idSK,
    ];
}

/**
 * Gửi thông báo sự kiện mới đến toàn bộ sinh viên & giảng viên.
 * Nếu sự kiện có idCap → chỉ gửi cho người thuộc đơn vị đó (nếu có liên kết).
 * Hiện tại gửi cho tất cả tài khoản isActive = 1 (trừ người tạo).
 */
function _gui_thong_bao_su_kien_moi($conn, $idSK, $ten_su_kien, $id_nguoi_tao)
{
    $idSK          = (int)$idSK;
    $id_nguoi_tao  = (int)$id_nguoi_tao;
    $ten_safe      = mysqli_real_escape_string($conn, $ten_su_kien);

    // Tạo bản ghi thông báo
    $sql_tb = "INSERT INTO thongbao (idSK, tieuDe, noiDung, loaiThongBao, nguoiGui, isPublic)
               VALUES ($idSK,
                       'Sự kiện mới: $ten_safe',
                       'Sự kiện \"$ten_safe\" vừa được công bố. Hãy xem chi tiết và đăng ký tham gia!',
                       'su_kien_moi',
                       $id_nguoi_tao,
                       1)";
    if (!mysqli_query($conn, $sql_tb)) return;

    $idThongBao = mysqli_insert_id($conn);

    // Lấy danh sách người nhận: sinh viên (idLoaiTK=3) & giảng viên (idLoaiTK=2), trừ người tạo
    $sql_nhan = "SELECT idTK FROM taikhoan
                 WHERE isActive = 1
                   AND idLoaiTK IN (2, 3)
                   AND idTK != $id_nguoi_tao";
    $res_nhan = mysqli_query($conn, $sql_nhan);
    if (!$res_nhan) return;

    $insert_vals = [];
    while ($row = mysqli_fetch_assoc($res_nhan)) {
        $insert_vals[] = "($idThongBao, " . (int)$row['idTK'] . ", 0)";
    }

    if (!empty($insert_vals)) {
        $sql_insert_nhan = "INSERT INTO thongbao_nguoinhan (idThongBao, idTK, daDoc)
                            VALUES " . implode(',', $insert_vals);
        mysqli_query($conn, $sql_insert_nhan);
    }
}

function btc_cap_nhat_su_kien($conn, $id_nguoi_thuc_hien, $id_su_kien, $ten_su_kien, $mo_ta, $id_cap, $ngay_mo_dk, $ngay_dong_dk, $ngay_bat_dau, $ngay_ket_thuc, $is_active)
{
    // Check quyền: BTC của sự kiện (cauhinh_sukien) HOẶC quyền hệ thống (tao_su_kien)
    $has_perm = kiem_tra_quyen_su_kien($conn, (int)$id_nguoi_thuc_hien, (int)$id_su_kien, 'cauhinh_sukien')
        || kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'tao_su_kien');
    if (!$has_perm) {
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

function btc_lay_chi_tiet_su_kien($conn, $id_su_kien)
{
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