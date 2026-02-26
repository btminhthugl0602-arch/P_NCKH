<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
global $conn;

require_once _PATH_URL . '/modules/functions/quan_ly_su_kien.php';
require_once _PATH_URL . '/modules/functions/quan_ly_nhom.php';
require_once _PATH_URL . '/modules/functions/base.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    require_once _PATH_URL . '/modules/errors/404.php';
    exit;
}

$event = btc_lay_chi_tiet_su_kien($conn, $id);
if (!$event) {
    require_once _PATH_URL . '/modules/errors/404.php';
    exit;
}

// ===== ĐIỂM DANH: Phát hiện phiên đang mở =====
$phien_dang_mo = null;
$da_diemdanh   = false;
$res_phien = mysqli_query(
    $conn,
    "SELECT * FROM lichtrinh
     WHERE idSK = $id
       AND thoiGianMoDiemDanh IS NOT NULL
       AND NOW() BETWEEN thoiGianMoDiemDanh AND thoiGianDongDiemDanh
     LIMIT 1"
);
if ($res_phien && mysqli_num_rows($res_phien) > 0) {
    $phien_dang_mo = mysqli_fetch_assoc($res_phien);
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
        $idLichMo  = (int)$phien_dang_mo['idLichTrinh'];
        $res_check = mysqli_query(
            $conn,
            "SELECT 1 FROM diemdanh WHERE idLichTrinh=$idLichMo AND idTK={$_SESSION['user_id']} LIMIT 1"
        );
        $da_diemdanh = ($res_check && mysqli_num_rows($res_check) > 0);
    }
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// ================== NGHIỆP VỤ NHÓM ==================
$userLoaiTK = 0;
if ($userId > 0) {
    $u = truy_van_mot_ban_ghi($conn, 'TAIKHOAN', 'idTK', $userId);
    $userLoaiTK = $u ? (int)$u['idLoaiTK'] : 0;
}

$isSV = ($userId > 0 && $userLoaiTK === 3);
$isGV = ($userId > 0 && $userLoaiTK === 2);

// ================== TRẠNG THÁI ĐĂNG KÝ (LUỒNG 1) ==================
$da_dang_ky = false;
if ($userId > 0 && ($isSV || $isGV)) {
    $da_dang_ky = kiem_tra_da_dang_ky_sukien($conn, $userId, $id);
}

// Dùng hàm chuẩn từ base.php (Phụ lục A.1)
$co_nhom_active = ($userId > 0) ? kiem_tra_co_nhom_active($conn, $userId, $id) : false;

$dang_ky_con_mo = false;
$now_ts  = time();
$mo_dk   = $event['ngayMoDangKy']   ?? null;
$dong_dk = $event['ngayDongDangKy'] ?? null;
if ($event['isActive']) {
    $sau_mo     = (!$mo_dk   || strtotime($mo_dk)   <= $now_ts);
    $truoc_dong = (!$dong_dk || strtotime($dong_dk) >= $now_ts);
    $dang_ky_con_mo = ($sau_mo && $truoc_dong);
}

$can_create_group   = ($userId > 0) && $da_dang_ky && ($isSV || $isGV);
$can_join_group     = ($userId > 0) && $da_dang_ky && $isSV;
$can_join_as_gvhd   = ($userId > 0) && $da_dang_ky && $isGV;  // GV đã đăng ký SK có thể xin vào nhóm làm GVHD

// ================== PHÂN QUYỀN ==================
$can_admin_events = ($userId > 0) ? kiem_tra_quyen_he_thong($conn, $userId, 'admin_events') : false;

$can_event_config = ($userId > 0) ? kiem_tra_bat_ky_quyen_su_kien($conn, $userId, $id, [
    'cauhinh_sukien',
    'cauhinh_vongthi',
    'cauhinh_tailieu',
    'phan_cong_cham',
]) : false;

$can_grading_area = ($userId > 0) ? kiem_tra_bat_ky_quyen_su_kien($conn, $userId, $id, [
    'xem_bai_phan_cong',
    'nhap_diem',
]) : false;

$can_manage_group = ($userId > 0) ? kiem_tra_quyen_su_kien($conn, $userId, $id, 'quan_ly_nhom') : false;

// Quyền nop_san_pham, xem_bai_cua_nhom: cần CẢ role VÀ nhóm active (Bảng 5.2.4 / Phụ lục A.1)
$has_role_submit = ($userId > 0) ? kiem_tra_quyen_su_kien($conn, $userId, $id, 'nop_san_pham') : false;
$can_submit_product = $has_role_submit && kiem_tra_co_nhom_active($conn, $userId, $id);

// Quyền xem kết quả: không cần nhóm active (SV mất nhóm vẫn xem được)
$can_view_result_before = ($userId > 0) ? kiem_tra_quyen_su_kien($conn, $userId, $id, 'xem_ketqua_truocCB') : false;
$can_view_result_after  = ($userId > 0) ? kiem_tra_quyen_su_kien($conn, $userId, $id, 'xem_ketqua_sauCB') : false;

$is_creator = ($userId > 0 && isset($event['nguoiTao']) && $userId == (int)$event['nguoiTao']);

$is_btc = ($can_event_config || $can_admin_events || $is_creator);
$id_gv_logged = 0;
$res_gv = mysqli_query($conn, "SELECT idGV FROM giangvien WHERE idTK = $userId LIMIT 1");
if ($res_gv && mysqli_num_rows($res_gv) > 0) {
    $id_gv_logged = (int)mysqli_fetch_assoc($res_gv)['idGV'];
}

// [LUỒNG 3] Dùng role system thay vì query trực tiếp phancong_doclap/tieuban_giangvien
// GV có quyền nhap_diem trong SK ↔ đã được phân công chấm (GV_CHAM_DOCLAP hoặc GV_CHAM_TIEUHAN)
$is_giangvien = ($userId > 0) ? kiem_tra_quyen_su_kien($conn, $userId, $id, 'nhap_diem') : false;
$my_subcommittees = [];

if ($id_gv_logged > 0) {
    $sql_my_tb = "
        SELECT tb.*, v.tenVongThi 
        FROM tieuban tb 
        JOIN tieuban_giangvien tbg ON tb.idTieuBan = tbg.idTieuBan 
        JOIN vongthi v ON tb.idVongThi = v.idVongThi
        WHERE tbg.idGV = $id_gv_logged AND tb.idSK = $id
    ";
    $res_my_tb = mysqli_query($conn, $sql_my_tb);
    if ($res_my_tb) {
        while ($row = mysqli_fetch_assoc($res_my_tb)) {
            $tb_id = (int)$row['idTieuBan'];
            $sql_members = "SELECT gv.tenGV FROM giangvien gv JOIN tieuban_giangvien tbg ON gv.idGV = tbg.idGV WHERE tbg.idTieuBan = $tb_id";
            $row['members'] = mysqli_fetch_all(mysqli_query($conn, $sql_members), MYSQLI_ASSOC);
            $sql_prods = "SELECT sp.tensanpham, n.manhom, ttn.tennhom 
                          FROM sanpham sp 
                          JOIN tieuban_sanpham tbs ON sp.idSanPham = tbs.idSanPham 
                          LEFT JOIN nhom n ON sp.idNhom = n.idnhom
                          LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
                          WHERE tbs.idTieuBan = $tb_id";
            $row['products'] = mysqli_fetch_all(mysqli_query($conn, $sql_prods), MYSQLI_ASSOC);
            $my_subcommittees[] = $row;
        }
    }
}

// ================== XỬ LÝ ĐĂNG KÝ THAM GIA SỰ KIỆN (LUỒNG 1) ==================
if (isset($_POST['dang_ky_sukien']) && $userId > 0) {
    if (!($isSV || $isGV)) {
        $_SESSION['flash_msg']  = 'Chỉ sinh viên và giảng viên mới được đăng ký tham gia.';
        $_SESSION['flash_type'] = 'warning';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
    $result = dang_ky_tham_gia_su_kien($conn, $userId, $id, $userLoaiTK);
    $_SESSION['flash_msg']  = $result['message'];
    $_SESSION['flash_type'] = $result['status'] ? 'success' : 'danger';
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// ================== XỬ LÝ HỦY ĐĂNG KÝ (LUỒNG 1) ==================
if (isset($_POST['huy_dang_ky']) && $userId > 0) {
    $result = huy_dang_ky_su_kien($conn, $userId, $id);
    $_SESSION['flash_msg']  = $result['message'];
    $_SESSION['flash_type'] = $result['status'] ? 'success' : 'warning';
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// ================== XỬ LÝ TẠO NHÓM ==================
if (isset($_POST['create_group']) && $userId > 0) {

    if (!$can_create_group) {
        $_SESSION['flash_msg']  = 'Bạn cần đăng ký tham gia sự kiện trước khi tạo nhóm.';
        $_SESSION['flash_type'] = 'warning';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    $tennhom      = trim($_POST['tennhom'] ?? '');
    $mota         = trim($_POST['mota'] ?? '');
    $soluongtoida = max(1, (int)($_POST['soluongtoida'] ?? 5));

    if (!empty($tennhom)) {
        $result = tao_nhom_moi($conn, $userId, $id, $tennhom, $mota, $soluongtoida, $userLoaiTK);
        if ($result['status']) {
            $_SESSION['flash_msg']  = 'Tạo nhóm thành công!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_msg']  = $result['message'];
            $_SESSION['flash_type'] = 'danger';
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

if (isset($_POST['xin_vao_nhom']) && $userId > 0) {
    // SV xin vào nhóm thông thường
    if ($can_join_group) {
        $idNhomXin  = (int)($_POST['idNhom'] ?? 0);
        $loiNhanXin = trim($_POST['loiNhan'] ?? '');
        if ($idNhomXin > 0) {
            $result = gui_yeu_cau_nhom($conn, $idNhomXin, $userId, 1, $loiNhanXin, $userId);
            $_SESSION['flash_msg']  = $result['status']
                ? 'Đã gửi yêu cầu tham gia nhóm thành công! Chờ trưởng nhóm duyệt.'
                : $result['message'];
            $_SESSION['flash_type'] = $result['status'] ? 'success' : 'warning';
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    // GV xin làm GVHD
    if ($can_join_as_gvhd) {
        $idNhomXin  = (int)($_POST['idNhom'] ?? 0);
        $loiNhanXin = trim($_POST['loiNhan'] ?? '');
        if ($idNhomXin > 0) {
            $result = gui_yeu_cau_nhom($conn, $idNhomXin, $userId, 1, $loiNhanXin, $userId);
            $_SESSION['flash_msg']  = $result['status']
                ? 'Đã gửi yêu cầu làm GVHD! Chờ chủ nhóm duyệt.'
                : $result['message'];
            $_SESSION['flash_type'] = $result['status'] ? 'success' : 'warning';
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    $_SESSION['flash_msg']  = 'Bạn không có quyền thực hiện thao tác này.';
    $_SESSION['flash_type'] = 'warning';
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// ================== LẤY TẤT CẢ NHÓM ==================
// Fix: Added DISTINCT in COUNT to ensure accurate member count when JOINs multiply rows.
$sql_all = "
    SELECT n.idnhom, t.tennhom, t.mota, t.soluongtoida, t.dangtuyen,
           COUNT(DISTINCT CASE WHEN tv.idvaitronhom != 3 THEN tv.idtk END) AS soThanhVien,
           COALESCE(sv.tenSV, gv.tenGV, tk_truong.tenTK, '') AS tenNhomTruong,
           MAX(CASE WHEN tv.idvaitronhom = 3 AND tv.trangthai = 1 THEN 1 ELSE 0 END) AS coGVHD
    FROM nhom n
    INNER JOIN thongtinnhom t         ON n.idnhom = t.idnhom
    LEFT JOIN thanhviennhom tv        ON n.idnhom = tv.idnhom AND tv.trangthai = 1
    LEFT JOIN taikhoan      tk_truong ON n.idChuNhom = tk_truong.idTK
    LEFT JOIN sinhvien      sv        ON tk_truong.idTK = sv.idTK
    LEFT JOIN giangvien     gv        ON tk_truong.idTK = gv.idTK
    WHERE n.idSK = $id AND n.isActive = 1
    GROUP BY n.idnhom, t.tennhom, t.mota, t.soluongtoida, t.dangtuyen, tk_truong.tenTK, sv.tenSV, gv.tenGV
    ORDER BY n.ngaytao DESC";
$result  = mysqli_query($conn, $sql_all);
$groups  = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

$myGroupIds      = [];
$pendingGroupIds = [];
if ($userId > 0) {
    $resMy = mysqli_query($conn, "SELECT idnhom FROM thanhviennhom WHERE idtk=$userId AND trangthai=1");
    while ($r = mysqli_fetch_assoc($resMy)) $myGroupIds[] = (int)$r['idnhom'];

    $resPending = mysqli_query($conn, "
        SELECT yc.idNhom FROM yeucau_thamgia yc 
        JOIN nhom n ON yc.idNhom=n.idnhom 
        WHERE yc.idTK=$userId AND yc.trangThai=0 AND n.idSK=$id");
    while ($r = mysqli_fetch_assoc($resPending)) $pendingGroupIds[] = (int)$r['idNhom'];
}

// ================== LẤY NHÓM CỦA USER ==================
$myGroups = [];
if ($userId > 0) {
    // Fix: Added DISTINCT in COUNT
    $sql_my = "
        SELECT n.idnhom, t.tennhom, t.mota, t.soluongtoida, t.dangtuyen,
               COUNT(DISTINCT CASE WHEN tv2.idvaitronhom != 3 THEN tv2.idtk END) AS soThanhVien,
               COALESCE(sv.tenSV, gv.tenGV, tk_truong.tenTK, '') AS tenNhomTruong,
               vn.tenvaitronhom AS vaiTroToi, tv_me.idvaitronhom, n.idChuNhom
        FROM nhom n
        JOIN  thanhviennhom tv_me     ON n.idnhom = tv_me.idnhom AND tv_me.idtk = $userId AND tv_me.trangthai = 1
        LEFT JOIN thongtinnhom  t     ON n.idnhom = t.idnhom
        LEFT JOIN thanhviennhom tv2   ON n.idnhom = tv2.idnhom AND tv2.trangthai = 1
        LEFT JOIN taikhoan  tk_truong ON n.idChuNhom = tk_truong.idTK
        LEFT JOIN sinhvien  sv        ON tk_truong.idTK = sv.idTK
        LEFT JOIN giangvien gv        ON tk_truong.idTK = gv.idTK
        LEFT JOIN vaitronhom vn       ON tv_me.idvaitronhom = vn.id
        WHERE n.idSK = $id AND n.isActive = 1
        GROUP BY n.idnhom, t.tennhom, t.mota, t.soluongtoida, t.dangtuyen,
                 tk_truong.tenTK, sv.tenSV, gv.tenGV, vn.tenvaitronhom, tv_me.idvaitronhom, n.idChuNhom
        ORDER BY n.ngaytao DESC";
    $res_my   = mysqli_query($conn, $sql_my);
    $myGroups = $res_my ? mysqli_fetch_all($res_my, MYSQLI_ASSOC) : [];

    foreach ($myGroups as &$g) {
        $gId = (int)$g['idnhom'];
        $res_sp = mysqli_query(
            $conn,
            "SELECT sp.*, l.loaitailieu AS tenLoaiTL
            FROM sanpham sp
            LEFT JOIN loaitailieu l ON sp.idloaitailieu = l.idtailieu
            WHERE sp.idNhom = $gId AND sp.idSK = $id
            ORDER BY sp.idloaitailieu ASC"
        );
        $spRows = $res_sp ? mysqli_fetch_all($res_sp, MYSQLI_ASSOC) : [];
        $g['sanPhamTheoLoai'] = [];
        foreach ($spRows as $row) {
            $g['sanPhamTheoLoai'][$row['idloaitailieu']] = $row;
        }
        $g['sanPham'] = !empty($spRows) ? $spRows[0] : null;

        $res_gvhd = mysqli_query($conn, "
            SELECT COALESCE(gv2.tenGV, tk.tenTK) AS tenGVHD, tv.idtk
            FROM thanhviennhom tv
            JOIN taikhoan tk ON tv.idtk = tk.idTK
            LEFT JOIN giangvien gv2 ON tk.idTK = gv2.idTK
            WHERE tv.idnhom = $gId AND tv.idvaitronhom = 3 AND tv.trangthai = 1
            LIMIT 1
        ");
        $g['gvhd'] = ($res_gvhd && mysqli_num_rows($res_gvhd) > 0) ? mysqli_fetch_assoc($res_gvhd) : null;
    }
    unset($g);
}

// ================== LẤY LỜI MỜI CỦA USER ==================
$loiMoiList = [];
if ($userId > 0) {
    // Fix: Added DISTINCT in COUNT
    $res_lm = mysqli_query($conn, "
        SELECT yc.idYeuCau, yc.idNhom, yc.loiNhan, yc.ngayGui, yc.ChieuMoi,
               t.tennhom, t.mota, t.soluongtoida,
               COUNT(DISTINCT CASE WHEN tv.idvaitronhom != 3 THEN tv.idtk END) AS soThanhVien,
               COALESCE(sv_t.tenSV, gv_t.tenGV, tk_truong.tenTK, '') AS tenNhomTruong,
               n.idChuNhom
        FROM yeucau_thamgia yc
        JOIN nhom n           ON yc.idNhom = n.idnhom
        JOIN taikhoan tk_truong ON n.idChuNhom = tk_truong.idTK
        LEFT JOIN sinhvien sv_t ON tk_truong.idTK = sv_t.idTK
        LEFT JOIN giangvien gv_t ON tk_truong.idTK = gv_t.idTK
        LEFT JOIN thongtinnhom t ON n.idnhom = t.idnhom
        LEFT JOIN thanhviennhom tv ON n.idnhom = tv.idnhom AND tv.trangthai = 1
        WHERE yc.idTK = $userId
          AND yc.trangThai = 0
          AND yc.ChieuMoi = 0
          AND n.idSK = $id
          AND n.isActive = 1
        GROUP BY yc.idYeuCau, yc.idNhom, yc.loiNhan, yc.ngayGui, yc.ChieuMoi,
                 t.tennhom, t.mota, t.soluongtoida,
                 tk_truong.tenTK, sv_t.tenSV, gv_t.tenGV, n.idChuNhom
        ORDER BY yc.ngayGui DESC
    ");
    $loiMoiList = $res_lm ? mysqli_fetch_all($res_lm, MYSQLI_ASSOC) : [];
}
$soLoiMoi = count($loiMoiList);

// AJAX: phản hồi lời mời
if (isset($_POST['ajax_action']) && $userId > 0) {
    header('Content-Type: application/json; charset=utf-8');
    $ajax_action = $_POST['ajax_action'];
    if ($ajax_action === 'phan_hoi_loi_moi') {
        $idYeuCau  = (int)($_POST['idYeuCau'] ?? 0);
        $trangThai = (int)($_POST['trangThai'] ?? 2);
        $result = duyet_yeu_cau_nhom($conn, $userId, $idYeuCau, $trangThai);
    } else {
        $result = ['status' => false, 'message' => 'Hành động không hợp lệ'];
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit();
}

$data = ['page_title' => htmlspecialchars($event['tenSK'])];
layout('header', $data);
layout('navbar');
?>

<style>
    /* ===== MY GROUP CARDS ===== */
    .my-groups-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 12px;
        flex-wrap: wrap;
        padding-top: 16px;
    }

    .nhom-card {
        background: #fff;
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 18px;
        border: 2px solid #eef0f5;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
        transition: box-shadow .2s, border-color .2s;
    }

    .nhom-card:hover {
        box-shadow: 0 6px 24px rgba(0, 0, 0, .10);
        border-color: #c5cef8;
    }

    .nhom-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
        flex-wrap: wrap;
        gap: 6px;
    }

    .nhom-card-header h5 {
        font-size: 17px;
        font-weight: 700;
        color: #1a1f36;
        margin: 0;
    }

    .badge-cong-khai {
        background: #e6f9ee;
        color: #16a34a;
        border: 1px solid #bbf7d0;
        border-radius: 20px;
        padding: 3px 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-rieng-tu {
        background: #fff7e6;
        color: #d97706;
        border: 1px solid #fde68a;
        border-radius: 20px;
        padding: 3px 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .gvhd-row {
        background: #4f46e5;
        color: #fff;
        border-radius: 8px;
        padding: 7px 14px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 10px;
    }

    .nhom-detai {
        font-size: 13px;
        color: #555;
        margin-bottom: 12px;
    }

    .nhom-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }

    .btn-nhom {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 7px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: opacity .2s, transform .1s;
        text-decoration: none;
    }

    .btn-nhom:hover {
        opacity: .88;
        transform: translateY(-1px);
    }

    .btn-nhom-view {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
    }

    .modal-header-grad {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
        border-radius: 12px 12px 0 0;
        padding: 16px 20px;
    }

    .modal-header-grad .btn-close {
        filter: brightness(0) invert(1);
    }

    .modal-content {
        border-radius: 12px;
        border: none;
    }

    .lm-empty {
        text-align: center;
        padding: 60px 20px;
        color: #9ca3af;
    }

    .lm-empty i {
        font-size: 56px;
        color: #c7d2fe;
    }

    .lm-card {
        background: #fff;
        border: 2px solid #eef0f5;
        border-radius: 14px;
        padding: 18px;
        transition: box-shadow .2s, border-color .2s;
    }

    .lm-card:hover {
        box-shadow: 0 6px 20px rgba(0, 0, 0, .08);
        border-color: #c5cef8;
    }

    .lm-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .lm-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .lm-icon i {
        font-size: 20px;
        color: #fff;
    }

    .lm-info {
        flex: 1;
        min-width: 0;
    }

    .lm-info h6 {
        font-size: 15px;
        font-weight: 700;
        color: #1a1f36;
        margin: 0 0 3px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .lm-meta {
        font-size: 12px;
        color: #6b7280;
    }

    .lm-count {
        background: #f1f3fb;
        color: #4f46e5;
        font-size: 12px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        white-space: nowrap;
    }

    .lm-loinhan {
        background: #faf5ff;
        border-left: 3px solid #7c3aed;
        border-radius: 0 8px 8px 0;
        padding: 8px 12px;
        font-size: 13px;
        color: #5b21b6;
        font-style: italic;
        margin-bottom: 8px;
    }

    .lm-time {
        font-size: 12px;
        color: #9ca3af;
        margin-bottom: 14px;
    }

    .lm-actions {
        display: flex;
        gap: 8px;
    }

    .btn-lm {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: opacity .2s, transform .1s;
        flex: 1;
        justify-content: center;
    }

    .btn-lm:hover {
        opacity: .88;
        transform: translateY(-1px);
    }

    .btn-lm-accept {
        background: linear-gradient(135deg, #16a34a, #15803d);
        color: #fff;
    }

    .btn-lm-decline {
        background: #fff;
        color: #dc2626;
        border: 2px solid #fca5a5;
    }

    .join-modal-info {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 13px;
        color: #1e40af;
        margin-bottom: 16px;
    }

    .btn-pending {
        background: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 6px 14px;
        font-size: 13px;
        font-weight: 600;
        cursor: not-allowed;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-member {
        background: #e6f9ee;
        color: #16a34a;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        padding: 6px 14px;
        font-size: 13px;
        font-weight: 600;
        cursor: not-allowed;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    @keyframes pulse-badge {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: .45;
        }
    }

    .animate-pulse {
        animation: pulse-badge 1.4s ease-in-out infinite;
    }
</style>

<main class="main">

    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0"><?= htmlspecialchars($event['tenSK']) ?></h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?= _HOST_URL ?>">Home</a></li>
                    <li class="current"><?= htmlspecialchars($event['tenSK']) ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <section id="course-details" class="course-details section">
        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row">
                <div class="col-lg-8">

                    <div class="course-hero" data-aos="fade-up" data-aos-delay="200">
                        <div class="hero-content">
                            <div class="course-badge">
                                <span class="category">Sự kiện</span>
                                <span class="level">Cấp: <?= htmlspecialchars($event['tenCap'] ?? 'Chưa rõ') ?></span>
                            </div>
                            <h1><?= htmlspecialchars($event['tenSK']) ?></h1>
                            <p class="course-subtitle"><?= nl2br(htmlspecialchars($event['moTa'])) ?></p>
                            <div class="instructor-card">
                                <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/person/person-m-8.webp" alt="Instructor"
                                    class="instructor-image">
                                <div class="instructor-details">
                                    <h5>Hội đồng tổ chức</h5>
                                    <span><?= htmlspecialchars($event['nguoiTaoTen'] ?? 'BTC') ?></span>
                                    <div class="instructor-rating">
                                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-half"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="hero-image">
                            <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/education/courses-8.webp" alt="Ảnh sự kiện"
                                class="img-fluid">
                            <div class="play-overlay">
                                <button class="play-btn"><i class="bi bi-play-fill"></i></button>
                                <span>Ảnh sự kiện</span>
                            </div>
                        </div>
                    </div>

                    <div class="course-nav-tabs" data-aos="fade-up" data-aos-delay="300">
                        <ul class="nav nav-tabs" id="EventDetails" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#event-info"
                                    type="button">
                                    <i class="bi bi-layout-text-window-reverse"></i> Thông tin
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#event-groups"
                                    type="button">
                                    <i class="bi bi-list-ul"></i> Nhóm thi
                                </button>
                            </li>

                            <?php if ($userId > 0): ?>
                                <li class="nav-item">
                                    <button class="nav-link <?= $phien_dang_mo ? 'fw-bold text-success' : '' ?>"
                                        data-bs-toggle="tab" data-bs-target="#event-attendance" type="button">
                                        <i class="bi bi-person-check<?= $phien_dang_mo ? '-fill' : '' ?>"></i>
                                        Điểm danh
                                        <?php if ($phien_dang_mo && !$da_diemdanh): ?>
                                            <span class="badge bg-danger ms-1 animate-pulse" style="font-size:.6rem">MỞ</span>
                                        <?php elseif ($phien_dang_mo && $da_diemdanh): ?>
                                            <span class="badge bg-success ms-1" style="font-size:.6rem">✓</span>
                                        <?php endif; ?>
                                    </button>
                                </li>
                            <?php endif; ?>

                            <?php if ($is_btc): ?>
                                <li class="nav-item">
                                    <button class="nav-link fw-bold text-primary" data-bs-toggle="tab"
                                        data-bs-target="#event-config" type="button">
                                        <i class="bi bi-gear-fill"></i> Cấu hình sự kiện
                                    </button>
                                </li>
                            <?php endif; ?>

                            <?php if ($is_btc || $is_giangvien || $can_grading_area): ?>
                                <li class="nav-item">
                                    <button class="nav-link fw-bold text-success" data-bs-toggle="tab"
                                        data-bs-target="#event-grading" type="button">
                                        <i class="bi bi-briefcase-fill"></i> Khu vực Giám khảo
                                    </button>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <div class="tab-content" id="EventDetailsContent">
                            <div class="tab-pane fade show active" id="event-info" role="tabpanel">
                                <div class="overview-section">
                                    <h3>Chi tiết sự kiện</h3>
                                    <p><?= nl2br(htmlspecialchars($event['moTa'])) ?></p>
                                </div>
                                <div class="requirements-section mt-4">
                                    <h3>Thông tin thời gian</h3>
                                    <ul class="requirements-list">
                                        <li><i class="bi bi-check2"></i><strong>Mở đăng ký:</strong>
                                            <?= $event['ngayMoDangKy'] ? date('d/m/Y H:i', strtotime($event['ngayMoDangKy'])) : '—' ?>
                                        </li>
                                        <li><i class="bi bi-check2"></i><strong>Đóng đăng ký:</strong>
                                            <?= $event['ngayDongDangKy'] ? date('d/m/Y H:i', strtotime($event['ngayDongDangKy'])) : '—' ?>
                                        </li>
                                        <li><i class="bi bi-check2"></i><strong>Bắt đầu:</strong>
                                            <?= $event['ngayBatDau'] ? date('d/m/Y H:i', strtotime($event['ngayBatDau'])) : '—' ?>
                                        </li>
                                        <li><i class="bi bi-check2"></i><strong>Kết thúc:</strong>
                                            <?= $event['ngayKetThuc'] ? date('d/m/Y H:i', strtotime($event['ngayKetThuc'])) : '—' ?>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="event-groups" role="tabpanel">
                                <div class="course-nav-tabs mt-3">
                                    <ul class="nav nav-tabs" id="GroupTabs" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link active" data-bs-toggle="tab"
                                                data-bs-target="#all-groups" type="button">
                                                <i class="bi bi-grid"></i> Tất cả nhóm
                                                <span class="badge bg-secondary ms-1"><?= count($groups) ?></span>
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#my-groups"
                                                type="button">
                                                <i class="bi bi-person-check"></i> Nhóm của tôi
                                                <?php if (!empty($myGroups)): ?>
                                                    <span class="badge bg-primary ms-1"><?= count($myGroups) ?></span>
                                                <?php endif; ?>
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#loi-moi"
                                                type="button">
                                                <i class="bi bi-envelope"></i> Lời mời nhóm
                                                <?php if ($soLoiMoi > 0): ?>
                                                    <span class="badge bg-danger ms-1"><?= $soLoiMoi ?></span>
                                                <?php endif; ?>
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content" id="GroupTabsContent">
                                        <div class="tab-pane fade show active" id="all-groups" role="tabpanel">
                                            <section class="courses-2 section">
                                                <div class="container" data-aos="fade-up" data-aos-delay="100">
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div class="courses-header" data-aos="fade-left"
                                                                data-aos-delay="100">
                                                                <div class="search-box">
                                                                    <i class="bi bi-search"></i>
                                                                    <input type="text" id="search-all-groups"
                                                                        placeholder="Tìm kiếm nhóm...">
                                                                </div>
                                                                <div class="sort-dropdown">
                                                                    <select id="sort-all-groups">
                                                                        <option value="">Sắp xếp: Tất cả</option>
                                                                        <option value="dang_tuyen">Đang tuyển</option>
                                                                        <option value="day">Đã đủ thành viên</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="courses-grid" data-aos="fade-up"
                                                                data-aos-delay="200">
                                                                <div class="row" id="all-groups-grid">
                                                                    <?php if (!empty($groups)): ?>
                                                                        <?php foreach ($groups as $g): ?>
                                                                            <?php
                                                                            $isFull    = $g['soluongtoida'] > 0 && $g['soThanhVien'] >= $g['soluongtoida'];
                                                                            $isMyGroup = in_array($g['idnhom'], $myGroupIds);
                                                                            $isPending = in_array($g['idnhom'], $pendingGroupIds);
                                                                            ?>
                                                                            <div class="col-lg-6 col-md-6 group-item"
                                                                                data-tuyen="<?= $g['dangtuyen'] ? '1' : '0' ?>"
                                                                                data-ten="<?= htmlspecialchars(strtolower($g['tennhom'] ?? '')) ?>">
                                                                                <div class="course-card">
                                                                                    <div class="course-image">
                                                                                        <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/education/courses-3.webp"
                                                                                            alt="Nhóm" class="img-fluid">
                                                                                        <?php if ($isFull): ?>
                                                                                            <div class="course-badge">Đã đủ thành
                                                                                                viên</div>
                                                                                        <?php elseif ($g['dangtuyen']): ?>
                                                                                            <div class="course-badge badge-free">
                                                                                                Đang tuyển</div>
                                                                                        <?php endif; ?>
                                                                                        <div class="course-price">
                                                                                            <?= $g['soThanhVien'] ?>/<?= $g['soluongtoida'] ?>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="course-content">
                                                                                        <h3><?= htmlspecialchars($g['tennhom'] ?? '') ?>
                                                                                        </h3>
                                                                                        <p class="text-muted small mb-1">
                                                                                            <?= htmlspecialchars($g['mota'] ?? 'Chưa có mô tả') ?>
                                                                                        </p>
                                                                                        <div class="course-stats">
                                                                                            <div class="stat"><i
                                                                                                    class="bi bi-people"></i><span><?= $g['soThanhVien'] ?>/<?= $g['soluongtoida'] ?>
                                                                                                    thành viên</span></div>
                                                                                        </div>
                                                                                        <?php if (!empty($g['tenNhomTruong'])): ?>
                                                                                            <div class="instructor-info mt-2">
                                                                                                <span class="instructor-name"><i
                                                                                                        class="bi bi-person-badge me-1"></i>Nhóm
                                                                                                    trưởng:
                                                                                                    <?= htmlspecialchars($g['tenNhomTruong']) ?></span>
                                                                                            </div>
                                                                                        <?php endif; ?>
                                                                                        <div
                                                                                            class="mt-2 d-flex gap-2 flex-wrap align-items-center">
                                                                                            <?php if ($isMyGroup): ?>
                                                                                                <span class="btn-member"><i
                                                                                                        class="bi bi-check-circle-fill"></i>
                                                                                                    Đã tham gia</span>
                                                                                            <?php elseif ($isPending): ?>
                                                                                                <span class="btn-pending"><i
                                                                                                        class="bi bi-clock-history"></i>
                                                                                                    Đang chờ duyệt</span>
                                                                                            <?php elseif (!$isFull && $g['dangtuyen'] && $userId > 0 && $can_join_group): ?>
                                                                                                <button type="button"
                                                                                                    class="btn-course"
                                                                                                    onclick="openJoinModal(<?= (int)$g['idnhom'] ?>, '<?= htmlspecialchars(addslashes($g['tennhom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')">
                                                                                                    <i
                                                                                                        class="bi bi-person-plus me-1"></i>Xin
                                                                                                    vào nhóm
                                                                                                </button>
                                                                                            <?php elseif ($userId > 0 && $can_join_as_gvhd && !$g['coGVHD'] && !in_array((int)$g['idnhom'], $pendingGroupIds)): ?>
                                                                                                <button type="button"
                                                                                                    class="btn-course"
                                                                                                    style="background:linear-gradient(135deg,#059669,#10b981)"
                                                                                                    onclick="openGvhdModal(<?= (int)$g['idnhom'] ?>, '<?= htmlspecialchars(addslashes($g['tennhom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')">
                                                                                                    <i
                                                                                                        class="bi bi-person-workspace me-1"></i>Xin
                                                                                                    làm GVHD
                                                                                                </button>
                                                                                            <?php elseif ($userId > 0 && $can_join_as_gvhd && !$g['coGVHD'] && in_array((int)$g['idnhom'], $pendingGroupIds)): ?>
                                                                                                <span class="btn-pending"><i
                                                                                                        class="bi bi-clock-history"></i>
                                                                                                    Đang chờ duyệt GVHD</span>
                                                                                            <?php elseif ($isFull): ?>
                                                                                                <span class="btn-course disabled"
                                                                                                    style="opacity:.6;cursor:not-allowed;background:#e5e7eb;color:#374151;">Đã
                                                                                                    đủ thành viên</span>
                                                                                            <?php endif; ?>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    <?php else: ?>
                                                                        <div class="col-12 text-center py-5 text-muted"><i
                                                                                class="bi bi-people fs-2 d-block mb-2"></i>Chưa
                                                                            có nhóm nào.</div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </section>
                                        </div>

                                        <div class="tab-pane fade" id="my-groups" role="tabpanel">
                                            <div class="my-groups-topbar">
                                                <div class="search-box">
                                                    <i class="bi bi-search"></i>
                                                    <input type="text" id="search-my-groups"
                                                        placeholder="Tìm nhóm của tôi...">
                                                </div>

                                                <?php if ($can_create_group): ?>
                                                    <button class="btn btn-primary" data-bs-toggle="modal"
                                                        data-bs-target="#createGroupModal">
                                                        <i class="bi bi-plus-circle me-1"></i>Tạo nhóm mới
                                                    </button>
                                                <?php elseif (($isSV || $isGV) && !$da_dang_ky && $dang_ky_con_mo): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="dang_ky_sukien" value="1">
                                                        <button type="submit" class="btn btn-outline-primary">
                                                            <i class="bi bi-person-plus me-1"></i>Đăng ký tham gia để tạo
                                                            nhóm
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (($isSV || $isGV) && !$da_dang_ky): ?>
                                                <div class="text-center py-5 text-muted">
                                                    <i class="bi bi-info-circle fs-2 d-block mb-2"></i>
                                                    <?php if ($dang_ky_con_mo): ?>
                                                        Bạn cần <strong>đăng ký tham gia sự kiện</strong> trước để tạo nhóm hoặc
                                                        xin vào nhóm.
                                                    <?php else: ?>
                                                        Ngoài thời gian đăng ký hoặc sự kiện chưa mở.
                                                    <?php endif; ?>
                                                </div>
                                            <?php elseif (empty($myGroups)): ?>
                                                <div class="text-center py-5 text-muted">
                                                    <i class="bi bi-person-x fs-2 d-block mb-2"></i>
                                                    Bạn chưa tham gia nhóm nào trong sự kiện này.
                                                </div>
                                            <?php else: ?>
                                                <div class="row" id="my-groups-grid">
                                                    <?php foreach ($myGroups as $g): ?>
                                                        <?php $gId = (int)$g['idnhom']; ?>
                                                        <div class="col-lg-6 col-md-6 my-group-item"
                                                            data-ten="<?= htmlspecialchars(strtolower($g['tennhom'])) ?>">
                                                            <div class="nhom-card">
                                                                <div class="nhom-card-header">
                                                                    <h5><?= htmlspecialchars($g['tennhom']) ?></h5>
                                                                    <?php if ($g['dangtuyen']): ?>
                                                                        <span class="badge-cong-khai">Công khai</span>
                                                                    <?php else: ?>
                                                                        <span class="badge-rieng-tu">Riêng tư</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <?php if ($g['gvhd']): ?>
                                                                    <div class="gvhd-row">
                                                                        <i class="bi bi-person-workspace"></i>
                                                                        GVHD: <?= htmlspecialchars($g['gvhd']['tenGVHD']) ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div
                                                                        class="alert alert-warning d-flex align-items-center gap-2 mb-2 py-2">
                                                                        <i
                                                                            class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                                                                        Nhóm cần có GVHD
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if ($g['sanPham']): ?>
                                                                    <div class="nhom-detai">
                                                                        <strong>Đề tài:</strong>
                                                                        <?= htmlspecialchars($g['sanPham']['tensanpham']) ?>
                                                                        <span
                                                                            class="badge ms-1 bg-<?= $g['sanPham']['TrangThai'] == 'Đã duyệt' ? 'success' : 'warning text-dark' ?>">
                                                                            <?= htmlspecialchars($g['sanPham']['TrangThai']) ?>
                                                                        </span>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="nhom-actions">
                                                                    <a href="<?= _HOST_URL ?>?module=event&action=chitiethom&id=<?= $gId ?>"
                                                                        class="btn-nhom btn-nhom-view">
                                                                        <i class="bi bi-arrow-right-circle"></i> Xem nhóm
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="tab-pane fade" id="loi-moi" role="tabpanel">
                                            <div class="py-3">
                                                <?php if (empty($loiMoiList)): ?>
                                                    <div class="lm-empty">
                                                        <i class="bi bi-envelope-open"></i>
                                                        <p class="fw-semibold mt-3 mb-1">Không có lời mời</p>
                                                        <p class="text-muted small">Bạn chưa có lời mời tham gia nhóm nào
                                                            trong sự kiện này.</p>
                                                    </div>
                                                <?php else: ?>
                                                    <p class="text-muted small mb-3">Bạn có
                                                        <strong><?= $soLoiMoi ?></strong> lời mời đang chờ phản hồi.
                                                    </p>
                                                    <div class="row g-3" id="loi-moi-grid">
                                                        <?php foreach ($loiMoiList as $lm): ?>
                                                            <div class="col-md-6" id="lm-card-<?= $lm['idYeuCau'] ?>">
                                                                <div class="lm-card">
                                                                    <div class="lm-card-header">
                                                                        <div class="lm-icon"><i class="bi bi-people-fill"></i>
                                                                        </div>
                                                                        <div class="lm-info">
                                                                            <h6><?= htmlspecialchars($lm['tennhom']) ?></h6>
                                                                            <span class="lm-meta">
                                                                                <i class="bi bi-person-badge me-1"></i>
                                                                                Nhóm trưởng:
                                                                                <?= htmlspecialchars($lm['tenNhomTruong'] ?: '—') ?>
                                                                            </span>
                                                                        </div>
                                                                        <span
                                                                            class="lm-count"><?= $lm['soThanhVien'] ?>/<?= $lm['soluongtoida'] ?></span>
                                                                    </div>
                                                                    <?php if (!empty($lm['loiNhan'])): ?>
                                                                        <div class="lm-loinhan">
                                                                            <i class="bi bi-chat-quote me-1"></i>
                                                                            "<?= htmlspecialchars($lm['loiNhan']) ?>"
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div class="lm-time">
                                                                        <i class="bi bi-clock me-1"></i>
                                                                        <?= date('d/m/Y H:i', strtotime($lm['ngayGui'])) ?>
                                                                    </div>
                                                                    <div id="lm-result-<?= $lm['idYeuCau'] ?>"
                                                                        class="mt-2 d-none"></div>
                                                                    <div class="lm-actions"
                                                                        id="lm-actions-<?= $lm['idYeuCau'] ?>">
                                                                        <button class="btn-lm btn-lm-accept"
                                                                            onclick="phanHoiLoiMoi(<?= (int)$lm['idYeuCau'] ?>, 1, '<?= htmlspecialchars(addslashes($lm['tennhom']), ENT_QUOTES, 'UTF-8') ?>')">
                                                                            <i class="bi bi-check-lg"></i> Chấp nhận
                                                                        </button>
                                                                        <button class="btn-lm btn-lm-decline"
                                                                            onclick="phanHoiLoiMoi(<?= (int)$lm['idYeuCau'] ?>, 2, '<?= htmlspecialchars(addslashes($lm['tennhom']), ENT_QUOTES, 'UTF-8') ?>')">
                                                                            <i class="bi bi-x-lg"></i> Từ chối
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div><?php if ($userId > 0): ?>
                                <div class="tab-pane fade" id="event-attendance" role="tabpanel">
                                    <div class="mt-4">
                                        <?php if ($phien_dang_mo): ?>
                                            <div
                                                class="alert <?= $da_diemdanh ? 'alert-success' : 'alert-warning' ?> border-0 shadow-sm d-flex align-items-center gap-3 mb-4 p-3">
                                                <i
                                                    class="bi bi-<?= $da_diemdanh ? 'check-circle-fill' : 'unlock-fill' ?> fs-3 flex-shrink-0"></i>
                                                <div class="flex-grow-1">
                                                    <?php if ($da_diemdanh): ?>
                                                        <strong class="d-block">Bạn đã điểm danh buổi này ✓</strong>
                                                        <small class="text-muted">Hoạt động:
                                                            <?= htmlspecialchars($phien_dang_mo['tenHoatDong']) ?></small>
                                                    <?php else: ?>
                                                        <strong class="d-block">Đang có phiên điểm danh mở!</strong>
                                                        <small>
                                                            <?= htmlspecialchars($phien_dang_mo['tenHoatDong']) ?>
                                                            &nbsp;—&nbsp; Đóng lúc
                                                            <?= date('H:i', strtotime($phien_dang_mo['thoiGianDongDiemDanh'])) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!$da_diemdanh): ?>
                                                    <a href="<?= _HOST_URL ?>/?module=diemdanh&action=checkin&lich=<?= (int)$phien_dang_mo['idLichTrinh'] ?>"
                                                        class="btn btn-success fw-bold px-4 flex-shrink-0">
                                                        <i class="bi bi-qr-code-scan me-1"></i>Điểm danh ngay
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-success fs-6 px-3 py-2 flex-shrink-0">
                                                        <i class="bi bi-check-circle me-1"></i>Đã điểm danh
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div
                                                class="alert alert-light border text-muted d-flex align-items-center gap-3 mb-4 p-3">
                                                <i class="bi bi-lock fs-3 flex-shrink-0 text-secondary"></i>
                                                <div>
                                                    <strong class="d-block text-dark">Chưa có phiên điểm danh nào đang
                                                        mở</strong>
                                                    <small>Ban tổ chức sẽ mở điểm danh trước mỗi buổi tổ chức. Bạn sẽ thấy thông
                                                        báo tại đây.</small>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($is_btc): ?>
                                            <div class="card border-0 bg-light">
                                                <div class="card-body d-flex align-items-center justify-content-between gap-3">
                                                    <div>
                                                        <h6 class="fw-bold mb-1">
                                                            <i class="bi bi-gear me-1 text-primary"></i>Quản lý điểm danh (BTC)
                                                        </h6>
                                                        <small class="text-muted">Tạo lịch trình, mở/đóng phiên, điểm danh thủ
                                                            công, xem danh sách</small>
                                                    </div>
                                                    <a href="<?= _HOST_URL ?>/?module=diemdanh&action=index&id=<?= $id ?>"
                                                        class="btn btn-primary btn-sm text-nowrap">
                                                        <i class="bi bi-arrow-right-circle me-1"></i>Vào trang quản lý
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($is_btc): ?>
                                <div class="tab-pane fade" id="event-config" role="tabpanel">
                                    <div class="event-config-content mt-4">
                                        <h3 class="fw-bold text-primary"><i class="bi bi-gear me-2"></i>Bảng điều khiển Sự
                                            kiện</h3>
                                        <div class="d-flex flex-column gap-3 mt-4">
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <a class="btn btn-outline-primary w-100 py-3 fw-bold"
                                                        href="<?= _HOST_URL ?>/?module=event&action=config_rounds&id=<?= $id ?>">
                                                        <i class="bi bi-layers d-block fs-3 mb-1"></i> Cấu hình cơ bản
                                                    </a>
                                                </div>
                                                <div class="col-md-4">
                                                    <a class="btn btn-outline-primary w-100 py-3 fw-bold"
                                                        href="<?= _HOST_URL ?>/?module=event&action=config_rules&id=<?= $id ?>">
                                                        <i class="bi bi-file-earmark-ruled d-block fs-3 mb-1"></i> Quy chế
                                                    </a>
                                                </div>
                                                <div class="col-md-4">
                                                    <a class="btn btn-outline-primary w-100 py-3 fw-bold"
                                                        href="<?= _HOST_URL ?>/?module=event&action=config_criteria&id=<?= $id ?>">
                                                        <i class="bi bi-ui-checks d-block fs-3 mb-1"></i> Bộ tiêu chí
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <a class="btn btn-outline-secondary w-100 py-3 fw-bold"
                                                        href="<?= _HOST_URL ?>/?module=event&action=config_btc&id=<?= $id ?>">
                                                        <i class="bi bi-people-fill d-block fs-3 mb-1"></i> Quản lý thành viên sự kiện
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="card border-0 shadow-sm bg-light mt-2">
                                                <div class="card-body">
                                                    <h6 class="fw-bold text-dark mb-3 text-uppercase">Nghiệp vụ chấm thi &
                                                        Đánh giá</h6>
                                                    <a class="btn btn-success text-white w-100 text-start p-3 mb-2 rounded-3 shadow-sm"
                                                        href="<?= _HOST_URL ?>/?module=event&action=config_grading&id=<?= $id ?>">
                                                        <i class="bi bi-pencil-square fs-4 me-2 align-middle"></i>
                                                        <strong>1. Phân công & Quản lý Điểm (Sơ loại)</strong>
                                                    </a>
                                                    <a class="btn btn-warning text-dark w-100 text-start p-3 rounded-3 shadow-sm"
                                                        href="<?= _HOST_URL ?>/?module=event&action=config_subcommittee&id=<?= $id ?>">
                                                        <i class="bi bi-diagram-3 fs-4 me-2 align-middle"></i>
                                                        <strong>2. Quản lý Tiểu ban (Bảo vệ Vòng trong)</strong>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($is_btc || $is_giangvien || $can_grading_area): ?>
                                <div class="tab-pane fade" id="event-grading" role="tabpanel">
                                    <div class="event-grading-content mt-4">
                                        <h3 class="fw-bold text-success"><i class="bi bi-briefcase me-2"></i>Khu vực làm
                                            việc của Ban Giám Khảo</h3>
                                        <div class="d-flex flex-column gap-3 mt-4">
                                            <a class="btn btn-success text-start p-3 shadow-sm rounded-3"
                                                href="<?= _HOST_URL ?>/?module=event&action=my_grading_tasks&id=<?= $id ?>">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-white text-success rounded-circle d-flex align-items-center justify-content-center me-3"
                                                        style="width:50px;height:50px;">
                                                        <i class="bi bi-journal-check fs-4"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-1 fw-bold text-white">Nhiệm vụ Chấm điểm</h5>
                                                        <small class="text-white-50">Truy cập danh sách các bài thi bạn được
                                                            phân công đánh giá</small>
                                                    </div>
                                                </div>
                                            </a>
                                            <button
                                                class="btn btn-info text-start p-3 shadow-sm rounded-3 text-dark border-0"
                                                data-bs-toggle="modal" data-bs-target="#modalSchedule">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-white text-info rounded-circle d-flex align-items-center justify-content-center me-3"
                                                        style="width:50px;height:50px;">
                                                        <i class="bi bi-people fs-4"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-1 fw-bold text-dark">Lịch trình Hội đồng / Tiểu ban
                                                        </h5>
                                                        <small>Xem thông tin phòng, thời gian và các thành viên cùng Hội
                                                            đồng</small>
                                                    </div>
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                </div>

                <div class="col-lg-4">
                    <div class="course-details-card" data-aos="fade-up" data-aos-delay="300">
                        <h4>Thông tin sự kiện</h4>
                        <div class="detail-grid">
                            <div class="detail-row"><span class="detail-label">Cấp tổ chức</span><span
                                    class="detail-value"><?= htmlspecialchars($event['tenCap'] ?? '—') ?></span></div>
                            <div class="detail-row"><span class="detail-label">Mở đăng ký</span><span
                                    class="detail-value"><?= $event['ngayMoDangKy'] ? date('d/m/Y', strtotime($event['ngayMoDangKy'])) : '—' ?></span>
                            </div>
                            <div class="detail-row"><span class="detail-label">Đóng đăng ký</span><span
                                    class="detail-value"><?= $event['ngayDongDangKy'] ? date('d/m/Y', strtotime($event['ngayDongDangKy'])) : '—' ?></span>
                            </div>
                            <div class="detail-row"><span class="detail-label">Ngày bắt đầu</span><span
                                    class="detail-value"><?= $event['ngayBatDau'] ? date('d/m/Y', strtotime($event['ngayBatDau'])) : '—' ?></span>
                            </div>
                            <div class="detail-row"><span class="detail-label">Ngày kết thúc</span><span
                                    class="detail-value"><?= $event['ngayKetThuc'] ? date('d/m/Y', strtotime($event['ngayKetThuc'])) : '—' ?></span>
                            </div>
                            <div class="detail-row"><span class="detail-label">Tổng số nhóm</span><span
                                    class="detail-value"><?= count($groups) ?></span></div>
                            <div class="detail-row">
                                <span class="detail-label">Trạng thái</span>
                                <span class="detail-value">
                                    <?php
                                    $now   = time();
                                    $start = $event['ngayBatDau']   ? strtotime($event['ngayBatDau'])   : null;
                                    $end   = $event['ngayKetThuc']  ? strtotime($event['ngayKetThuc'])  : null;
                                    if (!$start)            echo '<span class="badge bg-secondary">Chưa có lịch</span>';
                                    elseif ($start > $now)  echo '<span class="badge bg-info text-dark">Sắp diễn ra</span>';
                                    elseif (!$end || $end >= $now) echo '<span class="badge bg-success">Đang diễn ra</span>';
                                    else                    echo '<span class="badge bg-secondary">Đã kết thúc</span>';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- ĐĂNG KÝ THAM GIA (LUỒNG 1) -->
                    <?php if ($isSV || $isGV): ?>
                        <div class="enroll-action-card mt-4" data-aos="fade-up" data-aos-delay="380">
                            <?php if ($da_dang_ky): ?>
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <span class="fw-semibold text-success">Đã đăng ký tham gia</span>
                                </div>
                                <?php if (!$co_nhom_active && $dang_ky_con_mo): ?>
                                    <form method="POST" id="formHuyDangKy">
                                        <input type="hidden" name="huy_dang_ky" value="1">
                                        <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="showConfirm({
                                        title      : 'Xác nhận hủy đăng ký',
                                        message    : 'Bạn sẽ mất trạng thái tham gia sự kiện này. Bạn có chắc chắn?',
                                        type       : 'warning',
                                        confirmText: 'Hủy đăng ký',
                                        onConfirm  : () => document.getElementById('formHuyDangKy').submit()
                                    })">
                                            <i class="bi bi-x-circle me-1"></i>Hủy đăng ký
                                        </button>
                                    </form>
                                <?php elseif ($co_nhom_active): ?>
                                    <small class="text-muted">Rời nhóm trước để hủy đăng ký.</small>
                                <?php endif; ?>
                            <?php elseif ($dang_ky_con_mo): ?>
                                <form method="POST">
                                    <input type="hidden" name="dang_ky_sukien" value="1">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-person-plus me-1"></i>Đăng ký tham gia
                                    </button>
                                </form>
                            <?php elseif (!$event['isActive']): ?>
                                <span class="text-muted small"><i class="bi bi-lock me-1"></i>Sự kiện chưa mở.</span>
                            <?php else: ?>
                                <span class="text-muted small"><i class="bi bi-clock me-1"></i>Ngoài thời gian đăng ký.</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="share-course-card mt-4" data-aos="fade-up" data-aos-delay="400">
                        <h4>Chia sẻ sự kiện</h4>
                        <div class="social-links">
                            <a href="#" class="social-link facebook"><i class="bi bi-facebook"></i></a>
                            <a href="#" class="social-link twitter"><i class="bi bi-twitter"></i></a>
                            <a href="#" class="social-link linkedin"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="social-link email"><i class="bi bi-envelope"></i></a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

</main>

<?php if ($can_create_group): ?>
    <div class="modal fade" id="createGroupModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header-grad d-flex justify-content-between align-items-center">
                        <h5 class="modal-title mb-0"><i class="bi bi-plus-circle me-2"></i>Tạo nhóm mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tên nhóm <span class="text-danger">*</span></label>
                            <input type="text" name="tennhom" class="form-control" required placeholder="Nhập tên nhóm...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Số thành viên tối đa <span
                                    class="text-danger">*</span></label>
                            <input type="number" name="soluongtoida" class="form-control" required min="1" max="20"
                                value="5" placeholder="Ví dụ: 5">
                            <div class="form-text">Số lượng thành viên tối đa (không tính GVHD).</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mô tả nhóm</label>
                            <textarea name="mota" class="form-control" rows="3"
                                placeholder="Mô tả ngắn về nhóm..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                        <button type="submit" name="create_group" class="btn btn-primary"><i
                                class="bi bi-plus-circle me-1"></i>Tạo nhóm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($can_join_group): ?>
    <div class="modal fade" id="joinGroupModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="xin_vao_nhom" value="1">
                    <input type="hidden" name="idNhom" id="joinGroupId" value="">
                    <div class="modal-header-grad d-flex justify-content-between align-items-center">
                        <h5 class="modal-title mb-0"><i class="bi bi-person-plus me-2"></i>Xin vào nhóm</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="join-modal-info mb-3">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            Yêu cầu của bạn sẽ được gửi đến trưởng nhóm <strong id="joinGroupName"></strong> để xét duyệt.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lời nhắn gửi trưởng nhóm</label>
                            <textarea name="loiNhan" class="form-control" rows="3"
                                placeholder="Giới thiệu bản thân và lý do muốn tham gia nhóm..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Gửi yêu cầu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($can_join_as_gvhd): ?>
    <div class="modal fade" id="joinGvhdModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="xin_vao_nhom" value="1">
                    <input type="hidden" name="idNhom" id="gvhdGroupId" value="">
                    <div class="modal-header-grad d-flex justify-content-between align-items-center"
                        style="background:linear-gradient(135deg,#059669,#10b981)">
                        <h5 class="modal-title mb-0"><i class="bi bi-person-workspace me-2"></i>Xin làm Giảng viên hướng dẫn
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info d-flex align-items-start gap-2 mb-3">
                            <i class="bi bi-info-circle-fill flex-shrink-0"></i>
                            <div>Bạn đang xin làm GVHD cho nhóm <strong id="gvhdGroupName"></strong>.
                                Yêu cầu sẽ gửi đến chủ nhóm để duyệt.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lời nhắn gửi nhóm</label>
                            <textarea name="loiNhan" class="form-control" rows="3"
                                placeholder="Giới thiệu về bản thân và hướng nghiên cứu bạn có thể hỗ trợ..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                        <button type="submit" class="btn btn-success"><i class="bi bi-send me-1"></i>Gửi yêu cầu
                            GVHD</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($is_btc || $is_giangvien || $can_grading_area): ?>
    <div class="modal fade" id="modalSchedule" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-calendar3 me-2"></i>Lịch trình Hội đồng của tôi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <?php if (empty($my_subcommittees)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-info-circle fs-1 text-muted d-block mb-2"></i>
                            <p class="text-muted">Bạn chưa được phân công vào Tiểu ban báo cáo nào trong sự kiện này.</p>
                        </div>
                        <?php else: foreach ($my_subcommittees as $tb): ?>
                            <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                                <div class="card-header bg-white border-bottom py-3">
                                    <h5 class="text-primary fw-bold mb-1"><?= htmlspecialchars($tb['tenTieuBan']) ?></h5>
                                    <span
                                        class="badge bg-primary bg-opacity-10 text-primary rounded-pill"><?= htmlspecialchars($tb['tenVongThi']) ?></span>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Thời gian báo
                                                cáo</label>
                                            <div class="d-flex align-items-center text-dark fw-bold">
                                                <i class="bi bi-clock-fill me-2 text-info"></i>
                                                <?= $tb['ngayBaoCao'] ? date('d/m/Y', strtotime($tb['ngayBaoCao'])) : 'Chưa xếp lịch' ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Địa điểm /
                                                Phòng</label>
                                            <div class="d-flex align-items-center text-dark fw-bold">
                                                <i class="bi bi-geo-alt-fill me-2 text-danger"></i>
                                                <?= htmlspecialchars($tb['diaDiem'] ?: 'Chưa xác định') ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-4 mb-md-0">
                                            <h6 class="fw-bold mb-3 border-start border-4 border-info ps-2">Thành viên Hội đồng</h6>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php foreach ($tb['members'] as $m): ?>
                                                    <span class="badge bg-light text-dark border p-2 fw-normal">
                                                        <i
                                                            class="bi bi-person-fill me-1 text-secondary"></i><?= htmlspecialchars($m['tenGV']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="fw-bold mb-3 border-start border-4 border-success ps-2">Danh sách bài báo cáo
                                                (<?= count($tb['products']) ?>)</h6>
                                            <ul class="list-group list-group-flush small">
                                                <?php foreach ($tb['products'] as $p): ?>
                                                    <li class="list-group-item bg-transparent px-0 border-0 py-1">
                                                        <i class="bi bi-file-earmark-text me-2 text-success"></i>
                                                        <strong><?= htmlspecialchars($p['tennhom'] ?: $p['manhom']) ?>:</strong>
                                                        <?= htmlspecialchars($p['tensanpham']) ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <?php endforeach;
                    endif; ?>
                </div>
                <div class="modal-footer border-0 bg-white">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php layout('footer'); ?>

<script>
    const AJAX_URL = window.location.href;

    function openJoinModal(idNhom, tenNhom) {
        document.getElementById('joinGroupId').value = idNhom;
        document.getElementById('joinGroupName').textContent = tenNhom;
        new bootstrap.Modal(document.getElementById('joinGroupModal')).show();
    }

    function openGvhdModal(idNhom, tenNhom) {
        const el = document.getElementById('gvhdGroupId');
        const nm = document.getElementById('gvhdGroupName');
        if (el) el.value = idNhom;
        if (nm) nm.textContent = tenNhom;
        const modal = document.getElementById('joinGvhdModal');
        if (modal) new bootstrap.Modal(modal).show();
    }

    document.getElementById('search-all-groups')?.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#all-groups-grid .group-item').forEach(el => {
            el.style.display = el.dataset.ten?.includes(q) ? '' : 'none';
        });
    });

    document.getElementById('sort-all-groups')?.addEventListener('change', function() {
        const val = this.value;
        document.querySelectorAll('#all-groups-grid .group-item').forEach(el => {
            if (!val) el.style.display = '';
            else if (val === 'dang_tuyen') el.style.display = el.dataset.tuyen === '1' ? '' : 'none';
            else el.style.display = el.dataset.tuyen === '0' ? '' : 'none';
        });
    });

    document.getElementById('search-my-groups')?.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#my-groups-grid .my-group-item').forEach(el => {
            el.style.display = el.dataset.ten?.includes(q) ? '' : 'none';
        });
    });

    function phanHoiLoiMoi(idYeuCau, trangThai, tenNhom) {
        const actionsEl = document.getElementById('lm-actions-' + idYeuCau);
        const resultEl = document.getElementById('lm-result-' + idYeuCau);
        actionsEl.querySelectorAll('button').forEach(b => {
            b.disabled = true;
        });

        fetch(AJAX_URL, {
                method: 'POST',
                body: new URLSearchParams({
                    ajax_action: 'phan_hoi_loi_moi',
                    idYeuCau,
                    trangThai
                })
            })
            .then(r => r.json())
            .then(data => {
                actionsEl.style.display = 'none';
                resultEl.innerHTML = '';
                if (data.status && trangThai == 1) {
                    showToast('Đã chấp nhận lời mời vào nhóm ' + escHtml(tenNhom) + '!', 'success');
                    updateLoiMoiBadge(-1);
                } else if (data.status && trangThai == 2) {
                    showToast('Đã từ chối lời mời từ nhóm ' + escHtml(tenNhom) + '.', 'info');
                    updateLoiMoiBadge(-1);
                } else {
                    showToast(escHtml(data.message), 'danger');
                    actionsEl.style.display = '';
                    actionsEl.querySelectorAll('button').forEach(b => {
                        b.disabled = false;
                    });
                }
            })
            .catch(() => {
                actionsEl.style.display = '';
                actionsEl.querySelectorAll('button').forEach(b => {
                    b.disabled = false;
                });
            });
    }

    function updateLoiMoiBadge(delta) {
        const tabBtn = document.querySelector('[data-bs-target="#loi-moi"]');
        if (!tabBtn) return;
        let badge = tabBtn.querySelector('.badge');
        const newVal = (badge ? parseInt(badge.textContent) : 0) + delta;
        if (newVal <= 0) {
            if (badge) badge.remove();
        } else {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge bg-danger ms-1';
                tabBtn.appendChild(badge);
            }
            badge.textContent = newVal;
        }
    }

    function escHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g,
            '&#039;');
    }
</script>