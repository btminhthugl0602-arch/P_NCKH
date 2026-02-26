<?php

// ============================================================
// HẰNG SỐ ROLE BTC (DRY — tránh hardcode rải rác)
// ============================================================
if (!defined('VAITRO_BTC')) {
    define('VAITRO_BTC', 1);
}

// ============================================================
// HELPER NỘI BỘ (private — prefix _)
// ============================================================

/**
 * Kiểm tra caller có quyền cấu hình sự kiện.
 * (DRY: dùng chung cho them_vaitro_sukien và thu_hoi_vaitro_btc)
 */
function _kiem_tra_quyen_btc_sukien($conn, int $id_admin, int $id_sk): bool
{
    return kiem_tra_quyen_su_kien($conn, $id_admin, $id_sk, 'cauhinh_sukien')
        || kiem_tra_quyen_he_thong($conn, $id_admin, 'tao_su_kien');
}

/**
 * Lấy idvatro của role BTC từ DB (có cache static để tránh query nhiều lần).
 */
function _lay_id_vaitro_btc($conn): int
{
    static $cached = null;
    if ($cached !== null) return $cached;
    $res     = mysqli_query($conn, "SELECT idvatro FROM vaitro WHERE tenvaitro = 'BTC' LIMIT 1");
    $cached  = ($res && mysqli_num_rows($res) > 0)
        ? (int)mysqli_fetch_assoc($res)['idvatro']
        : VAITRO_BTC;
    return $cached;
}

// ============================================================
// 1. LẬP LỊCH TRÌNH TỔ CHỨC
// ============================================================

function tao_lich_trinh($conn, $id_nguoi_tao, $id_sk, $ten_hoat_dong, $thoi_gian, $dia_diem, $id_vong_thi = null): array
{
    if (!_kiem_tra_quyen_btc_sukien($conn, (int)$id_nguoi_tao, (int)$id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    $result = _insert_info($conn, 'lichtrinh',
        ['idSK', 'idVongThi', 'tenHoatDong', 'thoiGian', 'diaDiem'],
        [$id_sk, $id_vong_thi, $ten_hoat_dong, $thoi_gian, $dia_diem]
    );

    return $result
        ? ['status' => true,  'message' => 'Đã thêm lịch trình']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

// ============================================================
// 2. ĐIỂM DANH
// ============================================================

function ghi_nhan_diem_danh($conn, $id_nguoi_check, $id_nhom, $id_tk_sv, $trang_thai_hien_dien, $ghi_chu = ''): array
{
    $result = _insert_info($conn, 'diemdanh',
        ['idNhom', 'idTK', 'thoiGianDiemDanh', 'hienDien', 'ghiChu'],
        [$id_nhom, $id_tk_sv, date('Y-m-d H:i:s'), $trang_thai_hien_dien ? 1 : 0, $ghi_chu]
    );

    return $result
        ? ['status' => true,  'message' => 'Đã điểm danh']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

// ============================================================
// 3. LUỒNG 4 — BTC_THEM: GÁN ROLE THỦ CÔNG
// ============================================================

/**
 * BTC gán role cho tài khoản trong sự kiện.
 *
 * Quy tắc:
 *  - Chỉ gán role có btcCoTheGan = 1
 *  - Không gán trùng (cùng người + cùng SK + cùng role + isActive=1)
 *
 * @param  int   $id_admin              idTK người thực hiện (BTC)
 * @param  int   $id_sk                 idSK
 * @param  int   $id_tk_nguoi_duoc_gan  idTK người được gán
 * @param  int   $id_vaitro             idvatro (phải btcCoTheGan=1)
 * @return array ['status'=>bool, 'message'=>string]
 */
function them_vaitro_sukien($conn, int $id_admin, int $id_sk, int $id_tk_nguoi_duoc_gan, int $id_vaitro): array
{
    // 1. Quyền
    if (!_kiem_tra_quyen_btc_sukien($conn, $id_admin, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền thực hiện thao tác này.'];
    }

    // 2. Role phải btcCoTheGan = 1 (backend guard dù UI đã lọc)
    $res_vt = mysqli_query($conn,
        "SELECT btcCoTheGan FROM vaitro WHERE idvatro = $id_vaitro LIMIT 1"
    );
    if (!$res_vt || mysqli_num_rows($res_vt) === 0) {
        return ['status' => false, 'message' => 'Vai trò không tồn tại.'];
    }
    if ((int)mysqli_fetch_assoc($res_vt)['btcCoTheGan'] !== 1) {
        return ['status' => false, 'message' => 'Vai trò này không thể gán thủ công.'];
    }

    // 3. Không gán trùng
    $res_chk = mysqli_query($conn,
        "SELECT 1 FROM taikhoan_vaitro_sukien
         WHERE idTK = $id_tk_nguoi_duoc_gan AND idSK = $id_sk AND idVaiTro = $id_vaitro AND isActive = 1
         LIMIT 1"
    );
    if ($res_chk && mysqli_num_rows($res_chk) > 0) {
        return ['status' => false, 'message' => 'Người này đã có vai trò này trong sự kiện.'];
    }

    // 4. INSERT
    $ok = mysqli_query($conn,
        "INSERT INTO taikhoan_vaitro_sukien
             (idTK, idSK, idVaiTro, nguonTao, idNguoiCap, ngayCap, isActive)
         VALUES ($id_tk_nguoi_duoc_gan, $id_sk, $id_vaitro, 'BTC_THEM', $id_admin, NOW(), 1)"
    );

    return $ok
        ? ['status' => true,  'message' => 'Đã gán vai trò thành công.']
        : ['status' => false, 'message' => 'Lỗi hệ thống, không thể gán vai trò.'];
}

/**
 * Alias backward-compatible — code cũ gọi them_thanh_vien_btc() vẫn chạy.
 * Mặc định gán role BTC.
 */
function them_thanh_vien_btc($conn, $id_admin, $id_sk, $id_tk_can_bo, $chuc_vu = null): array
{
    return them_vaitro_sukien($conn, (int)$id_admin, (int)$id_sk, (int)$id_tk_can_bo, _lay_id_vaitro_btc($conn));
}

// ============================================================
// 4. LUỒNG 4 — BTC_THEM: THU HỒI ROLE THỦ CÔNG
// ============================================================

/**
 * BTC thu hồi role đã gán thủ công (nguonTao = 'BTC_THEM').
 *
 * Quy tắc:
 *  - Chỉ thu hồi nguonTao = 'BTC_THEM' (không đụng DANG_KY, QUA_NHOM, PHANCONG_CHAM)
 *  - Không tự thu hồi của mình
 *  - Phải còn ít nhất 1 BTC active trong SK
 *
 * @param  int   $id_admin          idTK BTC thực hiện
 * @param  int   $id_sk             idSK
 * @param  int   $id_tk_bi_thu_hoi  idTK người bị thu hồi
 * @param  int   $id_vaitro         idvatro cần thu hồi
 * @return array ['status'=>bool, 'message'=>string]
 */
function thu_hoi_vaitro_btc($conn, int $id_admin, int $id_sk, int $id_tk_bi_thu_hoi, int $id_vaitro): array
{
    // 1. Quyền
    if (!_kiem_tra_quyen_btc_sukien($conn, $id_admin, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền thực hiện thao tác này.'];
    }

    // 2. Không tự thu hồi
    if ($id_admin === $id_tk_bi_thu_hoi) {
        return ['status' => false, 'message' => 'Không thể thu hồi vai trò của chính mình.'];
    }

    // 3. Tìm bản ghi + kiểm tra nguonTao
    $res = mysqli_query($conn,
        "SELECT id, nguonTao FROM taikhoan_vaitro_sukien
         WHERE idTK = $id_tk_bi_thu_hoi AND idSK = $id_sk AND idVaiTro = $id_vaitro AND isActive = 1
         LIMIT 1"
    );
    if (!$res || mysqli_num_rows($res) === 0) {
        return ['status' => false, 'message' => 'Không tìm thấy vai trò này cho người dùng đã chọn.'];
    }
    $row = mysqli_fetch_assoc($res);
    if ($row['nguonTao'] !== 'BTC_THEM') {
        return ['status' => false, 'message' => 'Chỉ có thể thu hồi vai trò do BTC gán thủ công.'];
    }

    // 4. Bảo vệ BTC cuối
    $id_vaitro_btc = _lay_id_vaitro_btc($conn);
    if ($id_vaitro === $id_vaitro_btc) {
        $res_cnt = mysqli_query($conn,
            "SELECT COUNT(*) AS cnt FROM taikhoan_vaitro_sukien
             WHERE idSK = $id_sk AND idVaiTro = $id_vaitro_btc AND isActive = 1"
        );
        $cnt = ($res_cnt) ? (int)mysqli_fetch_assoc($res_cnt)['cnt'] : 2;
        if ($cnt <= 1) {
            return ['status' => false, 'message' => 'Phải có ít nhất 1 BTC trong sự kiện.'];
        }
    }

    // 5. Deactivate (dùng primary key để chắc chắn đúng bản ghi)
    $id_record = (int)$row['id'];
    $ok = mysqli_query($conn,
        "UPDATE taikhoan_vaitro_sukien SET isActive = 0 WHERE id = $id_record"
    );

    return $ok
        ? ['status' => true,  'message' => 'Đã thu hồi vai trò thành công.']
        : ['status' => false, 'message' => 'Lỗi hệ thống, không thể thu hồi.'];
}

// ============================================================
// 5. QUERY HELPERS CHO UI
// ============================================================

/**
 * Danh sách thành viên active trong sự kiện (dùng cho trang config_btc).
 */
function lay_danh_sach_thanh_vien_sukien($conn, int $id_sk): array
{
    $sql = "
        SELECT
            tvs.id        AS idRecord,
            tvs.idTK,
            tvs.nguonTao,
            tvs.ngayCap,
            tvs.idVaiTro,
            v.tenvaitro,
            v.btcCoTheGan,
            COALESCE(gv.tenGV, sv.tenSV, tk.tenTK) AS tenHienThi,
            tk.idLoaiTK
        FROM taikhoan_vaitro_sukien tvs
        JOIN taikhoan tk  ON tvs.idTK     = tk.idTK
        JOIN vaitro   v   ON tvs.idVaiTro = v.idvatro
        LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
        LEFT JOIN sinhvien  sv ON tk.idTK = sv.idTK
        WHERE tvs.idSK = $id_sk AND tvs.isActive = 1
        ORDER BY v.tenvaitro, tenHienThi
    ";
    $res = mysqli_query($conn, $sql);
    return ($res) ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
}

/**
 * Danh sách role BTC có thể gán thủ công (btcCoTheGan = 1).
 */
function lay_vaitro_btc_co_the_gan($conn): array
{
    $res = mysqli_query($conn,
        "SELECT idvatro, tenvaitro, mota FROM vaitro WHERE btcCoTheGan = 1 ORDER BY tenvaitro"
    );
    return ($res) ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
}
