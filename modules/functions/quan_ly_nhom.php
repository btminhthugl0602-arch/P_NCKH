<?php

// ============================================================
// HẰNG SỐ ROLE (DRY — không hardcode id rải rác)
// ============================================================
const VAITRO_BTC          = 1;
const VAITRO_GV_PHAN_BIEN = 2;
const VAITRO_GV_HUONG_DAN = 3;  // idvatro trong bảng vaitro
const VAITRO_THAM_GIA     = 4;

const VAITRONHOM_TRUONG   = 1;
const VAITRONHOM_THANH_VIEN = 2;
const VAITRONHOM_GVHD     = 3;

// ============================================================
// LUỒNG 1: ĐĂNG KÝ THAM GIA SỰ KIỆN
// ============================================================

/**
 * Kiểm tra user đã đăng ký tham gia sự kiện chưa (isActive=1)
 */
function kiem_tra_da_dang_ky_sukien($conn, $idTK, $idSK): bool
{
    $idTK = (int)$idTK;
    $idSK = (int)$idSK;
    $res  = mysqli_query(
        $conn,
        "SELECT 1 FROM taikhoan_vaitro_sukien
         WHERE idTK = $idTK AND idSK = $idSK AND idVaiTro = " . VAITRO_THAM_GIA . " AND isActive = 1
         LIMIT 1"
    );
    return ($res && mysqli_num_rows($res) > 0);
}

/**
 * Đăng ký tham gia sự kiện (nguonTao=DANG_KY).
 * Kiểm tra 3 điều kiện:
 *   1. Sự kiện active + trong thời gian đăng ký
 *   2. Chưa đăng ký
 *   3. Quy chế tham gia (nếu sự kiện ở chế độ CO_DIEU_KIEN)
 */
function dang_ky_tham_gia_su_kien($conn, $idTK, $idSK, $loaiTK): array
{
    $idTK = (int)$idTK;
    $idSK = (int)$idSK;

    // --- ĐIỀU KIỆN 1: Sự kiện active + thời gian đăng ký ---
    $resSK = mysqli_query(
        $conn,
        "SELECT isActive, ngayMoDangKy, ngayDongDangKy, cheDoDangKySV, cheDoDangKyGV
         FROM sukien WHERE idSK = $idSK LIMIT 1"
    );
    if (!$resSK || mysqli_num_rows($resSK) === 0) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại.'];
    }
    $sk = mysqli_fetch_assoc($resSK);
    if (!$sk['isActive']) {
        return ['status' => false, 'message' => 'Sự kiện chưa được mở.'];
    }
    $now = time();
    if ($sk['ngayMoDangKy'] && strtotime($sk['ngayMoDangKy']) > $now) {
        return ['status' => false, 'message' => 'Chưa đến thời gian mở đăng ký.'];
    }
    if ($sk['ngayDongDangKy'] && strtotime($sk['ngayDongDangKy']) < $now) {
        return ['status' => false, 'message' => 'Đã hết hạn đăng ký.'];
    }

    // --- ĐIỀU KIỆN 2: Chưa đăng ký ---
    if (kiem_tra_da_dang_ky_sukien($conn, $idTK, $idSK)) {
        return ['status' => false, 'message' => 'Bạn đã đăng ký tham gia sự kiện này rồi.'];
    }

    // --- ĐIỀU KIỆN 3: Quy chế (chỉ khi CO_DIEU_KIEN) ---
    $colCheDo   = ($loaiTK == 2) ? 'cheDoDangKyGV' : 'cheDoDangKySV';
    $loaiQuyche = ($loaiTK == 2) ? 'THAMGIA_GV'    : 'THAMGIA_SV';
    if (($sk[$colCheDo] ?? 'MO') === 'CO_DIEU_KIEN') {
        $resQC = mysqli_query(
            $conn,
            "SELECT idQuyChe FROM quyche
             WHERE idSK = $idSK AND loaiQuyChe = '$loaiQuyche' LIMIT 1"
        );
        if ($resQC && mysqli_num_rows($resQC) > 0) {
            require_once __DIR__ . '/kiem_tra_quy_che.php';
            $qcRow   = mysqli_fetch_assoc($resQC);
            $context = ['idTK' => $idTK, 'idSK' => $idSK];
            if (!kiem_tra_quy_che($conn, (int)$qcRow['idQuyChe'], $context)) {
                return ['status' => false, 'message' => 'Bạn không đáp ứng điều kiện quy chế tham gia sự kiện này.'];
            }
        }
    }

    // --- INSERT role THAM_GIA ---
    $ok = mysqli_query(
        $conn,
        "INSERT INTO taikhoan_vaitro_sukien
             (idTK, idSK, idVaiTro, nguonTao, idNguoiCap, ngayCap, isActive)
         VALUES ($idTK, $idSK, " . VAITRO_THAM_GIA . ", 'DANG_KY', $idTK, NOW(), 1)"
    );
    return $ok
        ? ['status' => true,  'message' => 'Đăng ký tham gia thành công!']
        : ['status' => false, 'message' => 'Lỗi hệ thống, không thể đăng ký.'];
}

/**
 * Hủy đăng ký tham gia sự kiện.
 * Chỉ cho phép hủy khi chưa có nhóm active.
 */
function huy_dang_ky_su_kien($conn, $idTK, $idSK): array
{
    $idTK = (int)$idTK;
    $idSK = (int)$idSK;

    if (!kiem_tra_da_dang_ky_sukien($conn, $idTK, $idSK)) {
        return ['status' => false, 'message' => 'Bạn chưa đăng ký tham gia sự kiện này.'];
    }

    $resNhom = mysqli_query(
        $conn,
        "SELECT 1 FROM thanhviennhom tv
         JOIN nhom n ON tv.idnhom = n.idnhom
         WHERE tv.idtk = $idTK AND n.idSK = $idSK AND tv.trangthai = 1 AND n.isActive = 1
         LIMIT 1"
    );
    if ($resNhom && mysqli_num_rows($resNhom) > 0) {
        return ['status' => false, 'message' => 'Bạn đang có nhóm active. Hãy rời nhóm trước khi hủy đăng ký.'];
    }

    $ok = mysqli_query(
        $conn,
        "UPDATE taikhoan_vaitro_sukien SET isActive = 0
         WHERE idTK = $idTK AND idSK = $idSK AND nguonTao = 'DANG_KY' AND isActive = 1"
    );
    return $ok
        ? ['status' => true,  'message' => 'Đã hủy đăng ký thành công.']
        : ['status' => false, 'message' => 'Lỗi hệ thống, không thể hủy đăng ký.'];
}

// ============================================================
// LUỒNG 1: NHÓM THI
// ============================================================

/**
 * Kiểm tra user đã có nhóm active trong sự kiện chưa.
 */
function kiem_tra_sv_co_nhom($conn, $id_tk, $id_sk): bool
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT 1 FROM thanhviennhom tv
         JOIN nhom n ON tv.idnhom = n.idnhom
         WHERE tv.idtk = ? AND n.idSK = ? AND tv.trangthai = 1 AND n.isActive = 1
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $id_tk, $id_sk);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    return mysqli_stmt_num_rows($stmt) > 0;
}

/**
 * Kiểm tra nhóm đã đủ thành viên tối đa chưa.
 * (KISS: tách hàm nhỏ dùng chung cho tao_nhom và duyet_yeu_cau)
 */
function nhom_da_full($conn, $idNhom, $soLuongToiDa): bool
{
    if ($soLuongToiDa <= 0) return false;
    $res = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS so_luong FROM thanhviennhom
         WHERE idnhom = $idNhom AND trangthai = 1"
    );
    if (!$res) return false;
    $c = (int)mysqli_fetch_assoc($res)['so_luong'];
    return $c >= $soLuongToiDa;
}

/**
 * Helper nội bộ: INSERT role vào taikhoan_vaitro_sukien nếu chưa tồn tại.
 * Dùng cho QUAT_NHOM và DANG_KY — tránh lặp code (DRY).
 * Trả về true nếu thành công (hoặc đã tồn tại).
 */
function _dam_bao_co_role_sk($conn, $idTK, $idSK, $idVaiTro, $nguonTao): bool
{
    $idTK     = (int)$idTK;
    $idSK     = (int)$idSK;
    $idVaiTro = (int)$idVaiTro;
    // Kiểm tra đã có role active chưa — không INSERT trùng
    $res = mysqli_query(
        $conn,
        "SELECT 1 FROM taikhoan_vaitro_sukien
         WHERE idTK = $idTK AND idSK = $idSK AND idVaiTro = $idVaiTro AND isActive = 1
         LIMIT 1"
    );
    if ($res && mysqli_num_rows($res) > 0) return true; // đã có, bỏ qua
    $ok = mysqli_query(
        $conn,
        "INSERT INTO taikhoan_vaitro_sukien
             (idTK, idSK, idVaiTro, nguonTao, idNguoiCap, ngayCap, isActive)
         VALUES ($idTK, $idSK, $idVaiTro, '$nguonTao', $idTK, NOW(), 1)"
    );
    return (bool)$ok;
}

/**
 * 1a. SINH VIÊN TẠO NHÓM MỚI — vai trò Trưởng nhóm (idChuNhom = idTK)
 * Yêu cầu: SV đã đăng ký tham gia sự kiện.
 */
function tao_nhom_sv($conn, $idTK, $idSK, $tenNhom, $moTa, $soLuongToiDa): array
{
    if (!kiem_tra_da_dang_ky_sukien($conn, $idTK, $idSK)) {
        return ['status' => false, 'message' => 'Bạn cần đăng ký tham gia sự kiện trước.'];
    }
    // Đã có nhóm chưa?
    $check = mysqli_query(
        $conn,
        "SELECT 1 FROM thanhviennhom tv JOIN nhom n ON tv.idnhom = n.idnhom
         WHERE tv.idtk = $idTK AND n.idSK = $idSK AND tv.trangthai = 1 AND n.isActive = 1 LIMIT 1"
    );
    if ($check && mysqli_num_rows($check) > 0) {
        return ['status' => false, 'message' => 'Bạn đã tham gia một nhóm trong sự kiện này rồi.'];
    }

    $maNhom = 'GRP_' . $idSK . '_' . time();
    mysqli_begin_transaction($conn);
    try {
        if (!mysqli_query(
            $conn,
            "INSERT INTO nhom (idSK, idChuNhom, manhom, ngaytao, isActive)
             VALUES ($idSK, $idTK, '$maNhom', NOW(), 1)"
        )) throw new Exception('Lỗi tạo nhóm.');
        $idNhomMoi = mysqli_insert_id($conn);

        $tenS  = mysqli_real_escape_string($conn, $tenNhom);
        $motaS = mysqli_real_escape_string($conn, $moTa);
        if (!mysqli_query(
            $conn,
            "INSERT INTO thongtinnhom (idnhom, tennhom, mota, soluongtoida, dangtuyen)
             VALUES ($idNhomMoi, '$tenS', '$motaS', $soLuongToiDa, 1)"
        )) throw new Exception('Lỗi lưu thông tin nhóm.');

        if (!mysqli_query(
            $conn,
            "INSERT INTO thanhviennhom (idnhom, idtk, idvaitronhom, trangthai, ngaythamgia)
             VALUES ($idNhomMoi, $idTK, " . VAITRONHOM_TRUONG . ", 1, NOW())"
        )) throw new Exception('Lỗi thêm thành viên.');

        mysqli_commit($conn);
        return ['status' => true, 'message' => 'Tạo nhóm thành công', 'idnhom' => $idNhomMoi];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => false, 'message' => $e->getMessage()];
    }
}

/**
 * 1b. GIẢNG VIÊN TẠO NHÓM — vai trò GVHD (idvaitronhom=3).
 * idChuNhom=NULL cho đến khi có SV làm trưởng.
 * Yêu cầu: GV đã đăng ký tham gia sự kiện.
 */
function tao_nhom_gv($conn, $idTK, $idSK, $tenNhom, $moTa, $soLuongToiDa): array
{
    if (!kiem_tra_da_dang_ky_sukien($conn, $idTK, $idSK)) {
        return ['status' => false, 'message' => 'Bạn cần đăng ký tham gia sự kiện trước.'];
    }

    $maNhom = 'GRP_' . $idSK . '_' . time();
    mysqli_begin_transaction($conn);
    try {
        // idChuNhom=NULL: nhóm chưa có trưởng SV
        if (!mysqli_query(
            $conn,
            "INSERT INTO nhom (idSK, idChuNhom, manhom, ngaytao, isActive)
             VALUES ($idSK, NULL, '$maNhom', NOW(), 1)"
        )) throw new Exception('Lỗi tạo nhóm.');
        $idNhomMoi = mysqli_insert_id($conn);

        $tenS  = mysqli_real_escape_string($conn, $tenNhom);
        $motaS = mysqli_real_escape_string($conn, $moTa);
        if (!mysqli_query(
            $conn,
            "INSERT INTO thongtinnhom (idnhom, tennhom, mota, soluongtoida, dangtuyen)
             VALUES ($idNhomMoi, '$tenS', '$motaS', $soLuongToiDa, 1)"
        )) throw new Exception('Lỗi lưu thông tin nhóm.');

        if (!mysqli_query(
            $conn,
            "INSERT INTO thanhviennhom (idnhom, idtk, idvaitronhom, trangthai, ngaythamgia)
             VALUES ($idNhomMoi, $idTK, " . VAITRONHOM_GVHD . ", 1, NOW())"
        )) throw new Exception('Lỗi thêm GVHD.');

        mysqli_commit($conn);
        return ['status' => true, 'message' => 'Tạo nhóm thành công', 'idnhom' => $idNhomMoi];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Alias gộp: gọi tao_nhom_sv() hoặc tao_nhom_gv() tùy loại tài khoản. (KISS)
 */
function tao_nhom_moi($conn, $idTK, $idSK, $tenNhom, $moTa, $soLuongToiDa, $loaiTK = 3): array
{
    return ((int)$loaiTK === 2)
        ? tao_nhom_gv($conn, $idTK, $idSK, $tenNhom, $moTa, $soLuongToiDa)
        : tao_nhom_sv($conn, $idTK, $idSK, $tenNhom, $moTa, $soLuongToiDa);
}

// ============================================================
// LUỒNG 2: YÊU CẦU THAM GIA NHÓM (QUA_NHOM)
// ============================================================

/**
 * Kiểm tra GV còn trong giới hạn số nhóm hướng dẫn của sự kiện.
 * Trả về true nếu GV còn có thể nhận thêm nhóm (hoặc không có giới hạn).
 */
function kiem_tra_gioi_han_gvhd($conn, $idGV, $idSK): bool
{
    $idGV = (int)$idGV;
    $idSK = (int)$idSK;

    $resSK = mysqli_query(
        $conn,
        "SELECT soNhomToiDaGVHD FROM sukien WHERE idSK = $idSK LIMIT 1"
    );
    if (!$resSK) return true;
    $sk = mysqli_fetch_assoc($resSK);
    if (!$sk || $sk['soNhomToiDaGVHD'] === null) return true; // NULL = không giới hạn

    $gioi_han = (int)$sk['soNhomToiDaGVHD'];

    $resCnt = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS so_nhom
         FROM thanhviennhom tv
         JOIN nhom n ON tv.idnhom = n.idnhom
         WHERE tv.idtk = $idGV
           AND n.idSK  = $idSK
           AND tv.idvaitronhom = " . VAITRONHOM_GVHD . "
           AND tv.trangthai = 1"
    );
    if (!$resCnt) return true;
    $cnt = (int)mysqli_fetch_assoc($resCnt)['so_nhom'];
    return $cnt < $gioi_han;
}

/**
 * Helper nội bộ: check quy chế THAMGIA_GV cho sự kiện + GV.
 * Trả về ['ok'=>bool, 'message'=>string].
 */
function _kiem_tra_quy_che_thamgia_gv($conn, $idGV, $idSK): array
{
    $idGV = (int)$idGV;
    $idSK = (int)$idSK;

    $resQC = mysqli_query(
        $conn,
        "SELECT idQuyChe FROM quyche
         WHERE idSK = $idSK AND loaiQuyChe = 'THAMGIA_GV' LIMIT 1"
    );
    if (!$resQC || mysqli_num_rows($resQC) === 0) {
        return ['ok' => true, 'message' => ''];  // Không có quy chế → cho qua
    }
    require_once __DIR__ . '/kiem_tra_quy_che.php';
    $qcRow   = mysqli_fetch_assoc($resQC);
    $context = ['idTK' => $idGV, 'idSK' => $idSK];
    if (!kiem_tra_quy_che($conn, (int)$qcRow['idQuyChe'], $context)) {
        return ['ok' => false, 'message' => 'Giảng viên không đáp ứng điều kiện quy chế tham gia sự kiện này.'];
    }
    return ['ok' => true, 'message' => ''];
}

/**
 * Gửi yêu cầu tham gia / lời mời vào nhóm.
 *
 * ChieuMoi = 0: Nhóm MỜI người dùng (SV hoặc GV) — ai duyệt: người được mời
 * ChieuMoi = 1: Người dùng tự XIN vào nhóm   — ai duyệt: idChuNhom
 *
 * Tham số:
 *   $id_tk_nguoi_gui   : idTK của người KHỞI TẠO yêu cầu (người gửi lời mời hoặc người xin)
 *   $id_tk_doi_phuong  : idTK của người nhận / đích đến
 *     - ChieuMoi=0: $id_tk_doi_phuong là GV/SV được mời (lưu vào yeucau_thamgia.idTK)
 *     - ChieuMoi=1: $id_tk_doi_phuong === $id_tk_nguoi_gui (người xin vào)
 */
function gui_yeu_cau_nhom($conn, $id_nhom, $id_tk_doi_phuong, $chieu_moi, $loi_nhan = '', $id_tk_nguoi_gui = null): array
{
    $id_nhom          = (int)$id_nhom;
    $id_tk_doi_phuong = (int)$id_tk_doi_phuong;
    // Mặc định: người gửi = đối phương (backward compatible với code cũ)
    $id_tk_nguoi_gui  = $id_tk_nguoi_gui === null ? $id_tk_doi_phuong : (int)$id_tk_nguoi_gui;

    // --- Lấy thông tin nhóm ---
    $nhom = truy_van_mot_ban_ghi($conn, 'nhom', 'idnhom', $id_nhom);
    if (!$nhom || !$nhom['isActive']) {
        return ['status' => false, 'message' => 'Nhóm không tồn tại hoặc đã bị giải tán'];
    }
    $idSK = (int)$nhom['idSK'];

    // --- Lấy thông tin đối phương ---
    $doi_phuong = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $id_tk_doi_phuong);
    if (!$doi_phuong) {
        return ['status' => false, 'message' => 'Người dùng không tồn tại'];
    }
    $la_gv = ((int)$doi_phuong['idLoaiTK'] === 2);

    // --- Check đã là thành viên chưa ---
    $kt_tv = _select_info($conn, 'thanhviennhom', [], [
        'WHERE' => ['idnhom', '=', $id_nhom, 'AND', 'idtk', '=', $id_tk_doi_phuong, 'AND', 'trangthai', '=', 1, '']
    ]);
    if (!empty($kt_tv)) {
        return ['status' => false, 'message' => 'Người này đã là thành viên của nhóm'];
    }

    // --- Check đang có yêu cầu chờ chưa ---
    $kt_yc = _select_info($conn, 'yeucau_thamgia', [], [
        'WHERE' => ['idNhom', '=', $id_nhom, 'AND', 'idTK', '=', $id_tk_doi_phuong, 'AND', 'trangThai', '=', 0, '']
    ]);
    if (!empty($kt_yc)) {
        return ['status' => false, 'message' => 'Đang có yêu cầu chờ xử lý cho người này'];
    }

    // ================================================================
    // NHÁNH GV (cả chiều 0 — nhóm mời GV và chiều 1 — GV tự xin vào)
    // ================================================================
    if ($la_gv) {
        // Check 1: nhóm đã có GVHD chưa
        $kt_gvhd = _select_info($conn, 'thanhviennhom', [], [
            'WHERE' => ['idnhom', '=', $id_nhom, 'AND', 'idvaitronhom', '=', VAITRONHOM_GVHD, 'AND', 'trangthai', '=', 1, '']
        ]);
        if (!empty($kt_gvhd)) {
            return ['status' => false, 'message' => 'Nhóm đã có Giảng viên hướng dẫn rồi'];
        }

        // Check 2: giới hạn số nhóm GV hướng dẫn
        if (!kiem_tra_gioi_han_gvhd($conn, $id_tk_doi_phuong, $idSK)) {
            return ['status' => false, 'message' => 'Giảng viên đã đạt giới hạn số nhóm hướng dẫn trong sự kiện này'];
        }

        // Check 3: quy chế THAMGIA_GV
        $qc = _kiem_tra_quy_che_thamgia_gv($conn, $id_tk_doi_phuong, $idSK);
        if (!$qc['ok']) {
            return ['status' => false, 'message' => $qc['message']];
        }

        // Check bổ sung: GV tự xin vào phải đã đăng ký SK trước
        if ($chieu_moi == 1) {
            $da_dk = kiem_tra_da_dang_ky_sukien($conn, $id_tk_doi_phuong, $idSK);
            if (!$da_dk) {
                return ['status' => false, 'message' => 'Bạn cần đăng ký tham gia sự kiện trước khi xin vào nhóm'];
            }
        }
    }

    // ================================================================
    // INSERT yêu cầu (áp dụng cho cả SV và GV)
    // ================================================================
    $res = _insert_info(
        $conn,
        'yeucau_thamgia',
        ['idNhom', 'idTK', 'ChieuMoi', 'loiNhan', 'trangThai', 'ngayGui'],
        [$id_nhom, $id_tk_doi_phuong, $chieu_moi, $loi_nhan, 0, date('Y-m-d H:i:s')]
    );
    return $res
        ? ['status' => true,  'message' => 'Gửi yêu cầu thành công']
        : ['status' => false, 'message' => 'Lỗi hệ thống, không gửi được yêu cầu'];
}

/**
 * Phê duyệt yêu cầu tham gia nhóm.
 *
 * Xử lý 4 nhánh:
 *   [A] ChieuMoi=0, GV  : GV chấp nhận lời mời từ nhóm
 *         → re-check GVHD, giới hạn, quy chế
 *         → INSERT thanhviennhom + INSERT taikhoan_vaitro_sukien (QUA_NHOM) nếu GV chưa có role
 *   [B] ChieuMoi=0, SV  : SV chấp nhận lời mời từ nhóm
 *         → INSERT thanhviennhom (không INSERT role vì SV đã có từ DANG_KY)
 *   [C] ChieuMoi=1, GV  : idChuNhom duyệt GV xin vào
 *         → re-check GVHD, giới hạn, quy chế
 *         → INSERT thanhviennhom, KHÔNG INSERT role (GV đã có từ DANG_KY)
 *   [D] ChieuMoi=1, SV  : idChuNhom duyệt SV xin vào
 *         → INSERT thanhviennhom (không INSERT role vì SV đã có từ DANG_KY)
 */
function duyet_yeu_cau_nhom($conn, $id_nguoi_duyet, $id_yeu_cau, $trang_thai_moi): array
{
    $id_nguoi_duyet = (int)$id_nguoi_duyet;
    $id_yeu_cau     = (int)$id_yeu_cau;
    $trang_thai_moi = (int)$trang_thai_moi;

    $yc = truy_van_mot_ban_ghi($conn, 'yeucau_thamgia', 'idYeuCau', $id_yeu_cau);
    if (!$yc) return ['status' => false, 'message' => 'Yêu cầu không tồn tại'];
    if ((int)$yc['trangThai'] !== 0) return ['status' => false, 'message' => 'Yêu cầu này đã được xử lý'];

    $nhom = truy_van_mot_ban_ghi($conn, 'nhom', 'idnhom', $yc['idNhom']);
    if (!$nhom || !$nhom['isActive']) {
        return ['status' => false, 'message' => 'Nhóm không còn tồn tại'];
    }

    $user_join = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $yc['idTK']);
    if (!$user_join) return ['status' => false, 'message' => 'Tài khoản người tham gia không tồn tại'];

    $la_gv = ((int)$user_join['idLoaiTK'] === 2);
    $idSK  = (int)$nhom['idSK'];

    // ── Xác nhận quyền duyệt ────────────────────────────────────────
    if ((int)$yc['ChieuMoi'] == 1) {
        // Người xin vào → chủ nhóm duyệt
        if ((int)$nhom['idChuNhom'] != $id_nguoi_duyet) {
            return ['status' => false, 'message' => 'Chỉ chủ nhóm mới được duyệt'];
        }
    } else {
        // Nhóm mời → người được mời tự xác nhận
        if ((int)$yc['idTK'] != $id_nguoi_duyet) {
            return ['status' => false, 'message' => 'Bạn không phải người được mời'];
        }
    }

    // ── Nếu đồng ý (trang_thai_moi=1) và người vào là GV: re-check điều kiện ──
    if ($trang_thai_moi === 1 && $la_gv) {
        // Re-check 1: nhóm đã có GVHD chưa (có thể nhóm khác vừa mời thành công)
        $kt_gvhd = _select_info($conn, 'thanhviennhom', [], [
            'WHERE' => ['idnhom', '=', $yc['idNhom'], 'AND', 'idvaitronhom', '=', VAITRONHOM_GVHD, 'AND', 'trangthai', '=', 1, '']
        ]);
        if (!empty($kt_gvhd)) {
            return ['status' => false, 'message' => 'Nhóm đã có Giảng viên hướng dẫn rồi'];
        }

        // Re-check 2: giới hạn số nhóm GV hướng dẫn
        if (!kiem_tra_gioi_han_gvhd($conn, $yc['idTK'], $idSK)) {
            return ['status' => false, 'message' => 'Giảng viên đã đạt giới hạn số nhóm hướng dẫn'];
        }

        // Re-check 3: quy chế THAMGIA_GV
        $qc = _kiem_tra_quy_che_thamgia_gv($conn, $yc['idTK'], $idSK);
        if (!$qc['ok']) {
            return ['status' => false, 'message' => $qc['message']];
        }
    }

    mysqli_begin_transaction($conn);
    try {
        // Cập nhật trạng thái yêu cầu
        _update_info(
            $conn,
            'yeucau_thamgia',
            ['trangThai', 'ngayPhanHoi'],
            [$trang_thai_moi, date('Y-m-d H:i:s')],
            ['idYeuCau' => ['=', $id_yeu_cau, '']]
        );

        if ($trang_thai_moi === 1) {
            if ($la_gv) {
                // ── [A] GV chấp nhận lời mời nhóm (ChieuMoi=0) ─────────────
                //    → INSERT role QUA_NHOM chỉ nếu GV chưa có role active trong SK
                // ── [C] Chủ nhóm duyệt GV tự xin vào (ChieuMoi=1) ──────────
                //    → GV đã có role từ DANG_KY → KHÔNG INSERT role mới
                if ((int)$yc['ChieuMoi'] === 0) {
                    // Chiều 0: nhóm mời GV — GV có thể chưa có role trong SK
                    if (!_dam_bao_co_role_sk($conn, $yc['idTK'], $idSK, VAITRO_GV_HUONG_DAN, 'QUA_NHOM')) {
                        throw new Exception('Lỗi gán role giảng viên hướng dẫn.');
                    }
                }
                // Chiều 1: GV đã có role DANG_KY → không INSERT thêm

                $vai_tro_nhom = VAITRONHOM_GVHD;
            } else {
                // ── [B]/[D] SV ──────────────────────────────────────────────
                if (!$nhom['idChuNhom']) {
                    // Nhóm GV tạo, chưa có trưởng SV → SV đầu tiên làm Trưởng
                    $vai_tro_nhom = VAITRONHOM_TRUONG;
                    if (!mysqli_query(
                        $conn,
                        "UPDATE nhom SET idChuNhom = {$yc['idTK']} WHERE idnhom = {$yc['idNhom']}"
                    )) throw new Exception('Lỗi cập nhật chủ nhóm.');
                } else {
                    $vai_tro_nhom = VAITRONHOM_THANH_VIEN;
                }
            }

            $res_add = _insert_info(
                $conn,
                'thanhviennhom',
                ['idnhom', 'idtk', 'idvaitronhom', 'trangthai', 'ngaythamgia'],
                [$yc['idNhom'], $yc['idTK'], $vai_tro_nhom, 1, date('Y-m-d H:i:s')]
            );
            if (!$res_add) throw new Exception('Lỗi thêm thành viên vào nhóm');
        }

        mysqli_commit($conn);
        return ['status' => true, 'message' => ($trang_thai_moi === 1 ? 'Đã chấp nhận' : 'Đã từ chối')];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => false, 'message' => $e->getMessage()];
    }
}

// ============================================================
// LUỒNG 1: RỜI NHÓM / GIẢI TÁN NHÓM
// ============================================================

/**
 * Rời nhóm.
 * Theo thiết kế: role trong taikhoan_vaitro_sukien KHÔNG bị thu hồi.
 * Role chỉ bị thu hồi khi nhóm bị giải tán.
 */
function roi_nhom($conn, $id_nguoi_thuc_hien, $id_nhom, $id_tk_bi_xoa): array
{
    if ($id_nguoi_thuc_hien != $id_tk_bi_xoa) {
        $nhom = truy_van_mot_ban_ghi($conn, 'nhom', 'idnhom', $id_nhom);
        if (!$nhom || (int)$nhom['idChuNhom'] != (int)$id_nguoi_thuc_hien) {
            return ['status' => false, 'message' => 'Bạn không có quyền mời thành viên ra khỏi nhóm'];
        }
    }

    $tv = _select_info($conn, 'thanhviennhom', [], [
        'WHERE' => ['idnhom', '=', $id_nhom, 'AND', 'idtk', '=', $id_tk_bi_xoa, 'AND', 'trangthai', '=', 1, '']
    ]);
    if (!empty($tv) && (int)$tv[0]['idvaitronhom'] === VAITRONHOM_TRUONG) {
        return ['status' => false, 'message' => 'Trưởng nhóm không thể rời đi. Hãy chuyển quyền hoặc giải tán nhóm.'];
    }

    $result = _update_info($conn, 'thanhviennhom', ['trangthai'], [0], [
        'idnhom' => ['=', $id_nhom, 'AND'],
        'idtk'   => ['=', $id_tk_bi_xoa, '']
    ]);
    return $result
        ? ['status' => true,  'message' => 'Đã rời khỏi nhóm']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * Giải tán nhóm (chỉ idChuNhom hoặc Admin).
 * Thu hồi role của TẤT CẢ thành viên trong taikhoan_vaitro_sukien.
 */
function giai_tan_nhom($conn, $id_nguoi_thuc_hien, $id_nhom): array
{
    $id_nhom = (int)$id_nhom;
    $nhom    = truy_van_mot_ban_ghi($conn, 'nhom', 'idnhom', $id_nhom);
    if (!$nhom) return ['status' => false, 'message' => 'Nhóm không tồn tại.'];
    if ($nhom['isActive'] == 0) return ['status' => false, 'message' => 'Nhóm đã bị giải tán rồi.'];

    $tk       = truy_van_mot_ban_ghi($conn, 'TAIKHOAN', 'idTK', $id_nguoi_thuc_hien);
    $is_admin = ($tk && (int)$tk['idLoaiTK'] === 1);
    if (!$is_admin && (int)$nhom['idChuNhom'] !== (int)$id_nguoi_thuc_hien) {
        return ['status' => false, 'message' => 'Chỉ chủ nhóm mới được giải tán nhóm.'];
    }

    $idSK = (int)$nhom['idSK'];
    mysqli_begin_transaction($conn);
    try {
        // Lấy danh sách thành viên active + vai trò nhóm của họ
        $res_tv = mysqli_query(
            $conn,
            "SELECT tv.idtk, tv.idvaitronhom, tk.idLoaiTK
             FROM thanhviennhom tv
             JOIN taikhoan tk ON tv.idtk = tk.idTK
             WHERE tv.idnhom = $id_nhom AND tv.trangthai = 1"
        );
        $ds_tv = $res_tv ? mysqli_fetch_all($res_tv, MYSQLI_ASSOC) : [];

        foreach ($ds_tv as $tv) {
            $idTKtv      = (int)$tv['idtk'];
            $la_gvhd     = ((int)$tv['idvaitronhom'] === VAITRONHOM_GVHD);
            $la_sv       = ((int)$tv['idLoaiTK'] !== 2);   // không phải GV

            if ($la_gvhd) {
                // GV_HUONG_DAN: chỉ thu hồi role nếu GV không còn nhóm active nào khác trong SK
                $res_con = mysqli_query(
                    $conn,
                    "SELECT COUNT(*) AS cnt
                     FROM thanhviennhom tv2
                     JOIN nhom n2 ON tv2.idnhom = n2.idnhom
                     WHERE tv2.idtk = $idTKtv
                       AND n2.idSK  = $idSK
                       AND n2.idnhom <> $id_nhom
                       AND tv2.trangthai = 1
                       AND n2.isActive  = 1"
                );
                $con = ($res_con) ? (int)mysqli_fetch_assoc($res_con)['cnt'] : 1;
                if ($con === 0) {
                    // Không còn nhóm nào → thu hồi role QUA_NHOM GV_HUONG_DAN
                    mysqli_query(
                        $conn,
                        "UPDATE taikhoan_vaitro_sukien SET isActive = 0
                         WHERE idTK = $idTKtv AND idSK = $idSK
                           AND idVaiTro = " . VAITRO_GV_HUONG_DAN . "
                           AND nguonTao = 'QUA_NHOM'
                           AND isActive = 1"
                    );
                }
            } else {
                // SV: thu hồi role THAM_GIA DANG_KY (role tự đăng ký)
                mysqli_query(
                    $conn,
                    "UPDATE taikhoan_vaitro_sukien SET isActive = 0
                     WHERE idTK = $idTKtv AND idSK = $idSK
                       AND idVaiTro = " . VAITRO_THAM_GIA . "
                       AND nguonTao = 'DANG_KY'
                       AND isActive = 1"
                );
            }
        }

        if (!mysqli_query($conn, "UPDATE thanhviennhom SET trangthai = 0 WHERE idnhom = $id_nhom"))
            throw new Exception('Lỗi cập nhật thành viên.');
        if (!mysqli_query($conn, "UPDATE nhom SET isActive = 0 WHERE idnhom = $id_nhom"))
            throw new Exception('Lỗi giải tán nhóm.');

        mysqli_commit($conn);
        return ['status' => true, 'message' => 'Nhóm đã được giải tán.'];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => false, 'message' => $e->getMessage()];
    }
}

// ============================================================
// TÌM KIẾM (dùng cho mời thành viên / GVHD)
// ============================================================

/**
 * Tìm kiếm giảng viên để mời làm GVHD.
 */
function tim_kiem_giang_vien($conn, $keyword): array
{
    $keyword = '%' . trim($keyword) . '%';
    $stmt = mysqli_prepare(
        $conn,
        "SELECT tk.idTK, gv.tenGV, gv.idKhoa
         FROM taikhoan tk JOIN giangvien gv ON tk.idTK = gv.idTK
         WHERE tk.idLoaiTK = 2 AND tk.isActive = 1 AND gv.tenGV LIKE ?
         LIMIT 10"
    );
    mysqli_stmt_bind_param($stmt, 's', $keyword);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

/**
 * Tìm kiếm sinh viên để mời vào nhóm.
 */
function tim_kiem_sinh_vien($conn, $keyword): array
{
    $keyword = '%' . trim($keyword) . '%';
    $stmt = mysqli_prepare(
        $conn,
        "SELECT tk.idTK, sv.tenSV, sv.MSV, l.tenLop
         FROM taikhoan tk
         JOIN sinhvien sv ON tk.idTK = sv.idTK
         LEFT JOIN lop l ON sv.idLop = l.idLop
         WHERE tk.idLoaiTK = 3 AND tk.isActive = 1
           AND (sv.tenSV LIKE ? OR sv.MSV LIKE ?)
         LIMIT 10"
    );
    mysqli_stmt_bind_param($stmt, 'ss', $keyword, $keyword);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

// ============================================================
// QUẢN LÝ QUYỀN SỞ HỮU VÀ TRƯỞNG NHÓM (Đ1 schema v3)
// ============================================================

/**
 * Chuyển nhượng quyền Chủ nhóm (idChuNhom) sang thành viên khác.
 * Chỉ Chủ nhóm hiện tại mới được thực hiện.
 */
function chuyen_nhuong_chu_nhom($conn, int $id_nhom, int $id_chu_hien_tai, int $id_chu_moi): array
{
    // Kiểm tra người thực hiện đang là Chủ nhóm
    $nhom = truy_van_mot_ban_ghi($conn, 'nhom', 'idnhom', $id_nhom);
    if (!$nhom) {
        return ['status' => false, 'message' => 'Nhóm không tồn tại'];
    }
    if ((int)$nhom['idChuNhom'] !== $id_chu_hien_tai) {
        return ['status' => false, 'message' => 'Chỉ Chủ nhóm mới có thể chuyển nhượng'];
    }
    // Người nhận phải là thành viên active của nhóm
    $stmt = mysqli_prepare($conn,
        "SELECT 1 FROM thanhviennhom WHERE idnhom = ? AND idtk = ? AND trangthai = 1 LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $id_nhom, $id_chu_moi);
    mysqli_stmt_execute($stmt);
    $check = mysqli_stmt_get_result($stmt);
    if (!$check || mysqli_num_rows($check) === 0) {
        return ['status' => false, 'message' => 'Người nhận chưa là thành viên active của nhóm'];
    }

    $ok = _update_info($conn, 'nhom', ['idChuNhom'], [$id_chu_moi], ['idnhom' => ['=', $id_nhom, '']]);
    return $ok
        ? ['status' => true,  'message' => 'Chuyển nhượng Chủ nhóm thành công']
        : ['status' => false, 'message' => 'Lỗi hệ thống khi chuyển nhượng'];
}

/**
 * Chỉ định Trưởng nhóm (idTruongNhom) — do BTC thực hiện.
 * idTruongNhom là đại diện SV trong vòng thi, không phải chủ sở hữu.
 */
function chi_dinh_truong_nhom($conn, int $id_su_kien, int $id_nguoi_thuc_hien, int $id_nhom, int $id_truong_moi): array
{
    // Kiểm tra quyền BTC
    $has_perm = kiem_tra_quyen_su_kien($conn, $id_nguoi_thuc_hien, $id_su_kien, 'cauhinh_sukien')
             || kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'tao_su_kien');
    if (!$has_perm) {
        return ['status' => false, 'message' => 'Không đủ quyền chỉ định Trưởng nhóm'];
    }
    // Người được chỉ định phải là thành viên active của nhóm
    $stmt = mysqli_prepare($conn,
        "SELECT 1 FROM thanhviennhom WHERE idnhom = ? AND idtk = ? AND trangthai = 1 LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $id_nhom, $id_truong_moi);
    mysqli_stmt_execute($stmt);
    $check = mysqli_stmt_get_result($stmt);
    if (!$check || mysqli_num_rows($check) === 0) {
        return ['status' => false, 'message' => 'Người được chỉ định chưa là thành viên active của nhóm'];
    }

    $ok = _update_info($conn, 'nhom', ['idTruongNhom'], [$id_truong_moi], ['idnhom' => ['=', $id_nhom, '']]);
    return $ok
        ? ['status' => true,  'message' => 'Chỉ định Trưởng nhóm thành công']
        : ['status' => false, 'message' => 'Lỗi hệ thống khi chỉ định'];
}
