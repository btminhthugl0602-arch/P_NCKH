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
    "SELECT * FROM phien_diemdanh
     WHERE idSK = $id
       AND thoiGianMo IS NOT NULL
       AND NOW() BETWEEN thoiGianMo AND thoiGianDong
     LIMIT 1"
);
if ($res_phien && mysqli_num_rows($res_phien) > 0) {
    $phien_dang_mo = mysqli_fetch_assoc($res_phien);
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
        $idPhienMo = (int)$phien_dang_mo['idPhienDD'];
        $res_check = mysqli_query(
            $conn,
            "SELECT 1 FROM diemdanh WHERE idPhienDD=$idPhienMo AND idTK={$_SESSION['user_id']} LIMIT 1"
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
page('event/view', compact(
    'event', 'id', 'userId', 'isBTC', 'isSV', 'isGV',
    'da_dang_ky', 'dang_ky_con_mo', 'co_nhom_active',
    'can_create_group', 'can_join_group', 'can_join_as_gvhd',
    'can_submit_product', 'is_creator', 'is_giangvien',
    'vongthi_list', 'nhom_list', 'sanpham_list', 'chuong_trinh',
    'phien_dang_mo', 'da_diemdanh', 'idPhienMo',
    'ket_qua_list', 'giaithuong_list', 'thongbao_list'
));
layout('footer');
