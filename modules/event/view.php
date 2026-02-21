<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
global $conn;

require_once _PATH_URL . '/modules/functions/quan_ly_su_kien.php';
require_once _PATH_URL . '/modules/functions/quan_ly_nhom.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { require_once _PATH_URL . '/modules/errors/404.php'; exit; }

$event = btc_lay_chi_tiet_su_kien($conn, $id);
if (!$event) { require_once _PATH_URL . '/modules/errors/404.php'; exit; }

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// ================== XỬ LÝ TẠO NHÓM ==================
if (isset($_POST['create_group']) && $userId > 0) {
    $tennhom = mysqli_real_escape_string($conn, trim($_POST['tennhom'] ?? ''));
    $mota    = mysqli_real_escape_string($conn, trim($_POST['mota'] ?? ''));
    if (!empty($tennhom)) {
        mysqli_query($conn, "INSERT INTO nhom (idSK, idnhomtruong, ngaytao, isActive) VALUES ($id, $userId, NOW(), 1)");
        $idNhom = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO thongtinnhom (idnhom, tennhom, mota, soluongtoida, dangtuyen) VALUES ($idNhom, '$tennhom', '$mota', 5, 1)");
        mysqli_query($conn, "INSERT INTO thanhviennhom (idnhom, idtk, idvaitronhom, trangthai) VALUES ($idNhom, $userId, 1, 1)");
        header("Location: " . $_SERVER['REQUEST_URI']); exit();
    }
}

// ================== XỬ LÝ DUYỆT YÊU CẦU (POST thường) ==================
if (isset($_POST['duyet_yeucau']) && $userId > 0) {
    $idYeuCau   = (int)($_POST['idYeuCau'] ?? 0);
    $trangThai  = (int)($_POST['trangThai'] ?? 2);
    duyet_yeu_cau_nhom($conn, $userId, $idYeuCau, $trangThai);
    header("Location: " . $_SERVER['REQUEST_URI']); exit();
}

// ================== XỬ LÝ RỜI/XÓA THÀNH VIÊN ==================
if (isset($_POST['xoa_thanh_vien']) && $userId > 0) {
    $idNhomXoa = (int)($_POST['idNhom'] ?? 0);
    $idTKXoa   = (int)($_POST['idTK']   ?? 0);
    roi_nhom($conn, $userId, $idNhomXoa, $idTKXoa);
    header("Location: " . $_SERVER['REQUEST_URI']); exit();
}

// ================== AJAX: MỜI THÀNH VIÊN / GVHD ==================
if (isset($_POST['ajax_action']) && $userId > 0) {
    header('Content-Type: application/json; charset=utf-8');
    $action  = $_POST['ajax_action'];
    $idNhom  = (int)($_POST['idNhom'] ?? 0);
    $loiNhan = trim($_POST['loiNhan'] ?? '');

    if ($action === 'moi_thanh_vien') {
        $idSV   = (int)($_POST['idSV'] ?? 0);
        $result = gui_yeu_cau_nhom($conn, $idNhom, $idSV, 0, $loiNhan);
    } elseif ($action === 'moi_gvhd') {
        $idGV   = (int)($_POST['idGV'] ?? 0);
        $result = gui_yeu_cau_nhom($conn, $idNhom, $idGV, 0, $loiNhan);
    } elseif ($action === 'phan_hoi_loi_moi') {
        // Người được mời tự duyệt lời mời của chính mình
        $idYeuCau  = (int)($_POST['idYeuCau'] ?? 0);
        $trangThai = (int)($_POST['trangThai'] ?? 2); // 1=chấp nhận, 2=từ chối
        $result = duyet_yeu_cau_nhom($conn, $userId, $idYeuCau, $trangThai);
    } else {
        $result = ['status' => false, 'message' => 'Hành động không hợp lệ'];
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit();
}

// ================== XỬ LÝ NỘP BÀI ==================
if (isset($_POST['nop_bai']) && $userId > 0) {
    $idNhomNop = (int)($_POST['idNhom'] ?? 0);
    $tenDeTai  = mysqli_real_escape_string($conn, trim($_POST['tenDeTai'] ?? ''));
    $idChuDeRaw = (int)($_POST['idChuDe'] ?? 0);
    // Tránh lỗi FK: chỉ dùng idChuDe nếu > 0
    $idChuDe = $idChuDeRaw > 0 ? $idChuDeRaw : 'NULL';

    $uploadDir = _PATH_URL . '/uploads/sanpham/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $allowed = ['pdf','doc','docx','zip','rar','pptx'];
    $uploaded = [];

    if (!empty($_FILES['files']['name'][0])) {
        $fileCount = count($_FILES['files']['name']);
        $docIndex = 1;
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($_FILES['files']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) continue;
            if ($_FILES['files']['size'][$i] > 20 * 1024 * 1024) continue;

            if (in_array($ext, ['zip','rar'])) {
                $idLoai = 3;
            } else {
                $idLoai = $docIndex <= 1 ? 1 : 2;
                $docIndex++;
            }

            $newName = 'loai'.$idLoai.'_nhom'.$idNhomNop.'_'.time().'_'.$i.'.'.$ext;
            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $uploadDir.$newName)) {
                $uploaded[$idLoai] = mysqli_real_escape_string($conn, 'uploads/sanpham/'.$newName);
            }
        }
    }

    if (!empty($tenDeTai)) {
        // Cập nhật tên đề tài / chủ đề cho TẤT CẢ bản ghi đã có TRƯỚC
        $chuDeSQL = $idChuDeRaw > 0 ? $idChuDeRaw : 'NULL';
        mysqli_query($conn,
            "UPDATE sanpham SET tensanpham='$tenDeTai', idChuDeSK=$chuDeSQL
            WHERE idNhom=$idNhomNop AND idSK=$id");

        // Sau đó mới xử lý từng file upload — chỉ update đúng idloaitailieu
        foreach ($uploaded as $idLoai => $filePath) {
            $check = mysqli_query($conn,
                "SELECT idSanPham FROM sanpham
                WHERE idNhom=$idNhomNop AND idSK=$id AND idloaitailieu=$idLoai LIMIT 1");
            if (mysqli_num_rows($check) > 0) {
                $row = mysqli_fetch_assoc($check);
                // Chỉ cập nhật moTataiLieu của đúng loại này, KHÔNG đụng loại khác
                mysqli_query($conn,
                    "UPDATE sanpham SET moTataiLieu='$filePath'
                    WHERE idSanPham={$row['idSanPham']}");
            } else {
                mysqli_query($conn,
                    "INSERT INTO sanpham
                        (idNhom,idSK,idChuDeSK,idloaitailieu,moTataiLieu,TrangThai,isActive,tensanpham)
                    VALUES
                        ($idNhomNop,$id,$chuDeSQL,$idLoai,'$filePath','Chờ duyệt',1,'$tenDeTai')");
            }
        }

        $_SESSION['flash_msg']  = 'nop_bai_thanh_cong';
        $_SESSION['flash_type'] = 'success';
    }
    header("Location: " . $_SERVER['REQUEST_URI']); exit();
}
// ================== LẤY DS SINH VIÊN (Để mời) ==================
$res_sv = mysqli_query($conn, "SELECT sv.idTK, sv.tenSV, sv.MSV, l.tenLop FROM sinhvien sv JOIN taikhoan tk ON sv.idTK=tk.idTK LEFT JOIN lop l ON sv.idLop=l.idLop WHERE tk.isActive=1 ORDER BY sv.tenSV");
$sv_list = $res_sv ? mysqli_fetch_all($res_sv, MYSQLI_ASSOC) : [];

// ================== LẤY DS GIẢNG VIÊN ==================
$res_gv = mysqli_query($conn, "SELECT gv.idTK, gv.tenGV, k.tenKhoa FROM giangvien gv JOIN taikhoan tk ON gv.idTK=tk.idTK LEFT JOIN khoa k ON gv.idKhoa=k.idKhoa WHERE tk.isActive=1 ORDER BY gv.tenGV");
$gv_list = $res_gv ? mysqli_fetch_all($res_gv, MYSQLI_ASSOC) : [];

// ================== LẤY DS CHỦ ĐỀ ==================
$res_cd = mysqli_query($conn, "SELECT cs.idChuDeSK, c.tenChuDe FROM chude_sukien cs JOIN chude c ON cs.idchude=c.idChuDe WHERE cs.idSK=$id AND cs.isActive=1");
$chude_list = $res_cd ? mysqli_fetch_all($res_cd, MYSQLI_ASSOC) : [];

// ================== LẤY TẤT CẢ NHÓM ==================
$sql_all = "
    SELECT n.idnhom, t.tennhom, t.mota, t.soluongtoida, t.dangtuyen,
           COUNT(tv.idtk) AS soThanhVien,
           COALESCE(sv.tenSV, gv.tenGV, tk_truong.tenTK, '') AS tenNhomTruong
    FROM nhom n
    LEFT JOIN thongtinnhom  t         ON n.idnhom = t.idnhom
    LEFT JOIN thanhviennhom tv        ON n.idnhom = tv.idnhom AND tv.trangthai = 1
    LEFT JOIN taikhoan      tk_truong ON n.idnhomtruong = tk_truong.idTK
    LEFT JOIN sinhvien      sv        ON tk_truong.idTK = sv.idTK
    LEFT JOIN giangvien     gv        ON tk_truong.idTK = gv.idTK
    WHERE n.idSK = $id AND n.isActive = 1
    GROUP BY n.idnhom, t.tennhom, t.mota, t.soluongtoida, t.dangtuyen, tk_truong.tenTK, sv.tenSV, gv.tenGV
    ORDER BY n.ngaytao DESC";
$result = mysqli_query($conn, $sql_all);
$groups = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

// ================== LẤY NHÓM CỦA USER + CHI TIẾT ==================
$myGroups = [];
if ($userId > 0) {
    $sql_my = "
        SELECT n.idnhom, t.tennhom, t.mota, t.soluongtoida, t.dangtuyen,
               COUNT(tv2.idtk) AS soThanhVien,
               COALESCE(sv.tenSV, gv.tenGV, tk_truong.tenTK, '') AS tenNhomTruong,
               vn.tenvaitronhom AS vaiTroToi, tv_me.idvaitronhom, n.idnhomtruong
        FROM nhom n
        JOIN  thanhviennhom tv_me     ON n.idnhom = tv_me.idnhom AND tv_me.idtk = $userId AND tv_me.trangthai = 1
        LEFT JOIN thongtinnhom  t     ON n.idnhom = t.idnhom
        LEFT JOIN thanhviennhom tv2   ON n.idnhom = tv2.idnhom AND tv2.trangthai = 1
        LEFT JOIN taikhoan  tk_truong ON n.idnhomtruong = tk_truong.idTK
        LEFT JOIN sinhvien  sv        ON tk_truong.idTK = sv.idTK
        LEFT JOIN giangvien gv        ON tk_truong.idTK = gv.idTK
        LEFT JOIN vaitronhom vn       ON tv_me.idvaitronhom = vn.id
        WHERE n.idSK = $id AND n.isActive = 1
        GROUP BY n.idnhom, t.tennhom, t.mota, t.soluongtoida, t.dangtuyen,
                 tk_truong.tenTK, sv.tenSV, gv.tenGV, vn.tenvaitronhom, tv_me.idvaitronhom, n.idnhomtruong
        ORDER BY n.ngaytao DESC";
    $res_my   = mysqli_query($conn, $sql_my);
    $myGroups = $res_my ? mysqli_fetch_all($res_my, MYSQLI_ASSOC) : [];

    foreach ($myGroups as &$g) {
        $gId = $g['idnhom'];

        // ---- Thành viên (không bao gồm GVHD để tách riêng) ----
        $res_tv = mysqli_query($conn, "
            SELECT tv.idtk, tv.idvaitronhom,
                   COALESCE(sv.tenSV, gv2.tenGV, tk.tenTK) AS tenTV,
                   vn.tenvaitronhom AS vaiTro,
                   tk.idLoaiTK
            FROM thanhviennhom tv
            JOIN taikhoan tk   ON tv.idtk = tk.idTK
            LEFT JOIN sinhvien sv  ON tk.idTK = sv.idTK
            LEFT JOIN giangvien gv2 ON tk.idTK = gv2.idTK
            LEFT JOIN vaitronhom vn ON tv.idvaitronhom = vn.id
            WHERE tv.idnhom = $gId AND tv.trangthai = 1
            ORDER BY tv.idvaitronhom ASC
        ");
        $g['thanhVien'] = $res_tv ? mysqli_fetch_all($res_tv, MYSQLI_ASSOC) : [];
        $g['dsMaTK'] = array_column($g['thanhVien'], 'idtk');

        // ---- GVHD (idvaitronhom = 3) ----
        $res_gvhd = mysqli_query($conn, "
            SELECT COALESCE(gv2.tenGV, tk.tenTK) AS tenGVHD, tv.idtk
            FROM thanhviennhom tv
            JOIN taikhoan tk ON tv.idtk = tk.idTK
            LEFT JOIN giangvien gv2 ON tk.idTK = gv2.idTK
            WHERE tv.idnhom = $gId AND tv.idvaitronhom = 3 AND tv.trangthai = 1
            LIMIT 1
        ");
        $g['gvhd'] = ($res_gvhd && mysqli_num_rows($res_gvhd) > 0) ? mysqli_fetch_assoc($res_gvhd) : null;

        // ---- Yêu cầu GVHD đang chờ ----
        $res_pgv = mysqli_query($conn, "
            SELECT yc.idYeuCau FROM yeucau_thamgia yc
            JOIN taikhoan tk ON yc.idTK = tk.idTK
            WHERE yc.idNhom = $gId AND yc.trangThai = 0 AND tk.idLoaiTK = 2
            LIMIT 1
        ");
        $g['pendingGVHD'] = ($res_pgv && mysqli_num_rows($res_pgv) > 0);

        // ---- Yêu cầu xin vào nhóm đang chờ (ChieuMoi=1 = SV xin vào) ----
        $res_req = mysqli_query($conn, "
            SELECT yc.idYeuCau, yc.ChieuMoi, yc.loiNhan, yc.ngayGui,
                   COALESCE(sv.tenSV, gv2.tenGV, tk.tenTK) AS tenNguoiGui,
                   tk.idLoaiTK
            FROM yeucau_thamgia yc
            JOIN taikhoan tk ON yc.idTK = tk.idTK
            LEFT JOIN sinhvien sv   ON tk.idTK = sv.idTK
            LEFT JOIN giangvien gv2 ON tk.idTK = gv2.idTK
            WHERE yc.idNhom = $gId AND yc.trangThai = 0
            ORDER BY yc.ngayGui DESC
        ");
        $g['yeuCauCho'] = $res_req ? mysqli_fetch_all($res_req, MYSQLI_ASSOC) : [];
        // ---- Bài nộp (lấy tất cả loại tài liệu của nhóm) ----
        $res_sp = mysqli_query($conn,
            "SELECT sp.*, l.loaitailieu AS tenLoaiTL
            FROM sanpham sp
            LEFT JOIN loaitailieu l ON sp.idloaitailieu = l.idtailieu
            WHERE sp.idNhom = $gId AND sp.idSK = $id
            ORDER BY sp.idloaitailieu ASC");
        $spRows = $res_sp ? mysqli_fetch_all($res_sp, MYSQLI_ASSOC) : [];
        // Indexed theo idloaitailieu để dễ tra cứu
        $g['sanPhamTheoLoai'] = [];
        foreach ($spRows as $row) {
            $g['sanPhamTheoLoai'][$row['idloaitailieu']] = $row;
        }
        // Giữ sanPham = bản ghi đầu tiên để hiển thị tên đề tài, trạng thái
        $g['sanPham'] = !empty($spRows) ? $spRows[0] : null;
    }
    unset($g);
}

// ================== LẤY LỜI MỜI CỦA USER (ChieuMoi=0 = nhóm mời user) ==================
$loiMoiList = [];
if ($userId > 0) {
    $res_lm = mysqli_query($conn, "
        SELECT yc.idYeuCau, yc.idNhom, yc.loiNhan, yc.ngayGui, yc.ChieuMoi,
               t.tennhom, t.mota, t.soluongtoida,
               COUNT(tv.idtk) AS soThanhVien,
               COALESCE(sv_t.tenSV, gv_t.tenGV, tk_truong.tenTK, '') AS tenNhomTruong,
               n.idnhomtruong
        FROM yeucau_thamgia yc
        JOIN nhom n           ON yc.idNhom = n.idnhom
        JOIN taikhoan tk_truong ON n.idnhomtruong = tk_truong.idTK
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
                 tk_truong.tenTK, sv_t.tenSV, gv_t.tenGV, n.idnhomtruong
        ORDER BY yc.ngayGui DESC
    ");
    $loiMoiList = $res_lm ? mysqli_fetch_all($res_lm, MYSQLI_ASSOC) : [];
}
$soLoiMoi = count($loiMoiList);
$flashMsg  = $_SESSION['flash_msg']  ?? '';
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

// ================== RENDER ==================
$data = ['page_title' => htmlspecialchars($event['tenSK'])];
layout('header', $data);
layout('navbar');
?>

<style>
/* ===== MY GROUP CARDS ===== */
.my-groups-topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap:12px; flex-wrap:wrap; padding-top:16px; }

.nhom-card { background:#fff; border-radius:14px; padding:20px; margin-bottom:18px; border:2px solid #eef0f5; box-shadow:0 2px 10px rgba(0,0,0,.05); transition:box-shadow .2s,border-color .2s; }
.nhom-card:hover { box-shadow:0 6px 24px rgba(0,0,0,.10); border-color:#c5cef8; }
.nhom-card-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; flex-wrap:wrap; gap:6px; }
.nhom-card-header h5 { font-size:17px; font-weight:700; color:#1a1f36; margin:0; }

.badge-cong-khai { background:#e6f9ee; color:#16a34a; border:1px solid #bbf7d0; border-radius:20px; padding:3px 12px; font-size:12px; font-weight:600; }
.badge-rieng-tu  { background:#fff7e6; color:#d97706; border:1px solid #fde68a; border-radius:20px; padding:3px 12px; font-size:12px; font-weight:600; }

.tv-chips { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px; }
.tv-chip { display:inline-flex; align-items:center; gap:4px; background:#f1f3fb; border-radius:20px; padding:4px 12px; font-size:13px; color:#3b4a85; font-weight:500; }
.tv-chip.leader { background:#ede9fe; color:#6d28d9; }

.gvhd-row { background:#4f46e5; color:#fff; border-radius:8px; padding:7px 14px; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; margin-bottom:10px; }
.gvhd-alert { background:#fffbeb; border:1px solid #fcd34d; border-radius:8px; padding:10px 14px; font-size:13px; color:#92400e; display:flex; align-items:center; gap:8px; margin-bottom:10px; }
.gvhd-alert i { font-size:16px; color:#f59e0b; }
.gvhd-pending { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 14px; font-size:13px; color:#1e40af; display:flex; align-items:center; gap:8px; margin-bottom:10px; }

.nhom-detai { font-size:13px; color:#555; margin-bottom:12px; }

.nhom-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
.btn-nhom { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:8px; font-size:13px; font-weight:600; border:none; cursor:pointer; transition:opacity .2s,transform .1s; text-decoration:none; }
.btn-nhom:hover { opacity:.88; transform:translateY(-1px); }
.btn-nhom-manage { background:#4f46e5; color:#fff; }
.btn-nhom-invite { background:#2563eb; color:#fff; }
.btn-nhom-gvhd   { background:#f59e0b; color:#fff; }
.btn-nhom-submit { background:#16a34a; color:#fff; }

/* Modal header gradient */
.modal-header-grad { background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; border-radius:12px 12px 0 0; padding:16px 20px; }
.modal-header-grad .btn-close { filter:brightness(0) invert(1); }
.modal-content { border-radius:12px; border:none; }

/* Modal tabs */
.modal-nav .nav-link { color:#555; font-weight:500; padding:10px 18px; border:none; border-bottom:3px solid transparent; background:none; border-radius:0; }
.modal-nav .nav-link.active { color:#4f46e5; border-bottom-color:#4f46e5; background:none; }
.modal-nav .nav-link:hover { color:#4f46e5; }

/* Member row */
.member-row { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f3f4f6; }
.member-row:last-child { border-bottom:none; }
.member-chip { display:inline-flex; align-items:center; gap:6px; padding:5px 14px; border-radius:20px; font-size:13px; font-weight:600; }
.member-chip.truong { background:#ede9fe; color:#5b21b6; }
.member-chip.thanh-vien { background:#f1f3fb; color:#3b4a85; }
.member-chip.gvhd-chip { background:#ecfdf5; color:#065f46; }

/* Request row */
.req-row { background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:12px 14px; margin-bottom:10px; }
.req-row .req-meta { font-size:12px; color:#9ca3af; margin-top:4px; }

/* Info alert */
.info-alert-box { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 14px; font-size:13px; color:#1e40af; margin-bottom:16px; display:flex; align-items:flex-start; gap:8px; }
.info-alert-box i { font-size:16px; margin-top:1px; flex-shrink:0; }

/* ===== LỜI MỜI NHÓM ===== */
.lm-empty { text-align:center; padding:60px 20px; color:#9ca3af; }
.lm-empty i { font-size:56px; color:#c7d2fe; }

.lm-card { background:#fff; border:2px solid #eef0f5; border-radius:14px; padding:18px; transition:box-shadow .2s, border-color .2s; }
.lm-card:hover { box-shadow:0 6px 20px rgba(0,0,0,.08); border-color:#c5cef8; }

.lm-card-header { display:flex; align-items:center; gap:12px; margin-bottom:12px; }
.lm-icon { width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg,#4f46e5,#7c3aed); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.lm-icon i { font-size:20px; color:#fff; }
.lm-info { flex:1; min-width:0; }
.lm-info h6 { font-size:15px; font-weight:700; color:#1a1f36; margin:0 0 3px 0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.lm-meta { font-size:12px; color:#6b7280; }
.lm-count { background:#f1f3fb; color:#4f46e5; font-size:12px; font-weight:700; padding:4px 10px; border-radius:20px; white-space:nowrap; }

.lm-mota { font-size:13px; color:#555; margin-bottom:8px; line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

.lm-loinhan { background:#faf5ff; border-left:3px solid #7c3aed; border-radius:0 8px 8px 0; padding:8px 12px; font-size:13px; color:#5b21b6; font-style:italic; margin-bottom:8px; }

.lm-time { font-size:12px; color:#9ca3af; margin-bottom:14px; }

.lm-actions { display:flex; gap:8px; }
.btn-lm { display:inline-flex; align-items:center; gap:5px; padding:8px 18px; border-radius:8px; font-size:13px; font-weight:600; border:none; cursor:pointer; transition:opacity .2s, transform .1s; flex:1; justify-content:center; }
.btn-lm:hover { opacity:.88; transform:translateY(-1px); }
.btn-lm-accept { background:linear-gradient(135deg,#16a34a,#15803d); color:#fff; }
.btn-lm-decline { background:#fff; color:#dc2626; border:2px solid #fca5a5; }
.btn-lm-decline:hover { background:#fef2f2; }
/* ===== UPLOAD DROPZONE ===== */
.upload-dropzone {
    border: 2px dashed #c5cef8;
    border-radius: 12px;
    padding: 36px 20px;
    text-align: center;
    background: #f8f9ff;
    transition: background .2s, border-color .2s;
}
.upload-dropzone.dragover { background: #eef0fd; border-color: #4f46e5; }
.file-list .file-item {
    display:flex; align-items:center; gap:10px;
    padding:8px 12px; background:#fff;
    border:1px solid #e5e7eb; border-radius:8px;
    margin-bottom:6px; font-size:13px;
}
.file-item .file-name { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.file-item .file-size { color:#9ca3af; font-size:12px; white-space:nowrap; }
.submitted-files-box .flex-1 { flex:1; min-width:0; }

/* ===== POPUP NỘP BÀI THÀNH CÔNG ===== */
.submit-success-overlay {
    position:fixed; inset:0; background:rgba(0,0,0,.45);
    z-index:9999; display:flex; align-items:center; justify-content:center;
}
.submit-success-box {
    background:#fff; border-radius:20px; padding:40px 48px;
    text-align:center; max-width:420px; width:90%;
    box-shadow:0 20px 60px rgba(0,0,0,.2);
    animation: popIn .35s cubic-bezier(.34,1.56,.64,1);
}
@keyframes popIn {
    from { transform:scale(.7); opacity:0; }
    to   { transform:scale(1);  opacity:1; }
}
.submit-success-box .success-icon {
    width:80px; height:80px; border-radius:50%;
    background:linear-gradient(135deg,#16a34a,#15803d);
    display:flex; align-items:center; justify-content:center;
    margin:0 auto 20px;
}
.submit-success-box .success-icon i { font-size:40px; color:#fff; }
.submit-success-box h4 { font-weight:700; color:#1a1f36; margin-bottom:8px; }
.submit-success-box p  { color:#6b7280; margin-bottom:24px; font-size:14px; }
/* Chỉ cần thêm animation cho popup */
@keyframes popIn {
    from { transform:scale(.7); opacity:0; }
    to   { transform:scale(1);  opacity:1; }
}
</style>

<main class="main">

    <?php if ($flashMsg): ?>
    <div class="container pt-3">
        <div class="alert alert-<?= $flashType ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flashMsg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

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
                                <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/person/person-m-8.webp" alt="Instructor" class="instructor-image">
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
                            <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/education/courses-8.webp" alt="Ảnh sự kiện" class="img-fluid">
                            <div class="play-overlay">
                                <button class="play-btn"><i class="bi bi-play-fill"></i></button>
                                <span>Ảnh sự kiện</span>
                            </div>
                        </div>
                    </div>

                    <div class="course-nav-tabs" data-aos="fade-up" data-aos-delay="300">
                        <ul class="nav nav-tabs" id="EventDetails" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#event-info" type="button">
                                    <i class="bi bi-layout-text-window-reverse"></i> Thông tin
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#event-groups" type="button">
                                    <i class="bi bi-list-ul"></i> Nhóm thi
                                </button>
                            </li>
                            <?php if ($userId > 0 && !empty($myGroups)): ?>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#my-submissions" type="button">
                                    <i class="bi bi-folder2-open"></i> Bài nộp của tôi
                                    <?php
                                    $totalSP = 0;
                                    foreach ($myGroups as $g) { if (!empty($g['sanPham'])) $totalSP++; }
                                    if ($totalSP > 0): ?>
                                        <span class="badge bg-success ms-1"><?= $totalSP ?></span>
                                    <?php endif; ?>
                                </button>
                            </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#event-config" type="button">
                                    <i class="bi bi-gear"></i> Cấu hình sự kiện
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="EventDetailsContent">

                            <!-- Tab Thông tin -->
                            <div class="tab-pane fade show active" id="event-info" role="tabpanel">
                                <div class="overview-section">
                                    <h3>Chi tiết sự kiện</h3>
                                    <p><?= nl2br(htmlspecialchars($event['moTa'])) ?></p>
                                </div>
                                <div class="requirements-section mt-4">
                                    <h3>Thông tin thời gian</h3>
                                    <ul class="requirements-list">
                                        <li><i class="bi bi-check2"></i><strong>Mở đăng ký:</strong> <?= $event['ngayMoDangKy'] ? date('d/m/Y H:i', strtotime($event['ngayMoDangKy'])) : '—' ?></li>
                                        <li><i class="bi bi-check2"></i><strong>Đóng đăng ký:</strong> <?= $event['ngayDongDangKy'] ? date('d/m/Y H:i', strtotime($event['ngayDongDangKy'])) : '—' ?></li>
                                        <li><i class="bi bi-check2"></i><strong>Bắt đầu:</strong> <?= $event['ngayBatDau'] ? date('d/m/Y H:i', strtotime($event['ngayBatDau'])) : '—' ?></li>
                                        <li><i class="bi bi-check2"></i><strong>Kết thúc:</strong> <?= $event['ngayKetThuc'] ? date('d/m/Y H:i', strtotime($event['ngayKetThuc'])) : '—' ?></li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Tab Nhóm thi -->
                            <div class="tab-pane fade" id="event-groups" role="tabpanel">
                                <div class="course-nav-tabs mt-3">
                                    <ul class="nav nav-tabs" id="GroupTabs" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#all-groups" type="button">
                                                <i class="bi bi-grid"></i> Tất cả nhóm
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#my-groups" type="button">
                                                <i class="bi bi-person-check"></i> Nhóm của tôi
                                                <?php if (!empty($myGroups)): ?>
                                                    <span class="badge bg-primary ms-1"><?= count($myGroups) ?></span>
                                                <?php endif; ?>
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#loi-moi" type="button">
                                                <i class="bi bi-envelope"></i> Lời mời nhóm
                                                <?php if ($soLoiMoi > 0): ?>
                                                    <span class="badge bg-danger ms-1"><?= $soLoiMoi ?></span>
                                                <?php endif; ?>
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content" id="GroupTabsContent">

                                        <!-- Tất cả nhóm -->
                                        <div class="tab-pane fade show active" id="all-groups" role="tabpanel">
                                            <section class="courses-2 section">
                                                <div class="container" data-aos="fade-up" data-aos-delay="100">
                                                    <div class="row"><div class="col-lg-12">
                                                        <div class="courses-header" data-aos="fade-left" data-aos-delay="100">
                                                            <div class="search-box">
                                                                <i class="bi bi-search"></i>
                                                                <input type="text" id="search-all-groups" placeholder="Tìm kiếm nhóm...">
                                                            </div>
                                                            <div class="sort-dropdown">
                                                                <select id="sort-all-groups">
                                                                    <option value="">Sắp xếp: Tất cả</option>
                                                                    <option value="dang_tuyen">Đang tuyển</option>
                                                                    <option value="day">Đã đủ thành viên</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="courses-grid" data-aos="fade-up" data-aos-delay="200">
                                                            <div class="row" id="all-groups-grid">
                                                                <?php if (!empty($groups)): ?>
                                                                    <?php foreach ($groups as $g): ?>
                                                                        <?php $isFull = $g['soThanhVien'] >= $g['soluongtoida']; ?>
                                                                        <div class="col-lg-6 col-md-6 group-item"
                                                                            data-tuyen="<?= $g['dangtuyen'] ? '1':'0' ?>"
                                                                            data-ten="<?= htmlspecialchars(strtolower($g['tennhom']) ?? '') ?>">
                                                                            <div class="course-card">
                                                                                <div class="course-image">
                                                                                    <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/education/courses-3.webp" alt="Nhóm" class="img-fluid">
                                                                                    <?php if ($isFull): ?>
                                                                                        <div class="course-badge">Đã đủ thành viên</div>
                                                                                    <?php elseif ($g['dangtuyen']): ?>
                                                                                        <div class="course-badge badge-free">Đang tuyển</div>
                                                                                    <?php endif; ?>
                                                                                    <div class="course-price"><?= $g['soThanhVien'] ?>/<?= $g['soluongtoida'] ?></div>
                                                                                </div>
                                                                                <div class="course-content">
                                                                                    <h3><?= htmlspecialchars($g['tennhom']) ?></h3>
                                                                                    <p class="text-muted small mb-1"><?= htmlspecialchars($g['mota'] ?? 'Chưa có mô tả') ?></p>
                                                                                    <div class="course-stats">
                                                                                        <div class="stat"><i class="bi bi-people"></i><span><?= $g['soThanhVien'] ?>/<?= $g['soluongtoida'] ?> thành viên</span></div>
                                                                                    </div>
                                                                                    <?php if (!empty($g['tenNhomTruong'])): ?>
                                                                                        <div class="instructor-info mt-2">
                                                                                            <span class="instructor-name"><i class="bi bi-person-badge me-1"></i>Nhóm trưởng: <?= htmlspecialchars($g['tenNhomTruong']) ?></span>
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                    <?php if (!$isFull && $g['dangtuyen']): ?>
                                                                                        <a href="<?= _HOST_URL ?>?module=group&action=join&id=<?= $g['idnhom'] ?>" class="btn-course mt-2">Xin vào nhóm</a>
                                                                                    <?php else: ?>
                                                                                        <span class="btn-course mt-2 disabled" style="opacity:.6;cursor:not-allowed">Đã đủ thành viên</span>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <div class="col-12 text-center py-5 text-muted"><i class="bi bi-people fs-2 d-block mb-2"></i>Chưa có nhóm nào.</div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div></div>
                                                </div>
                                            </section>
                                        </div>

                                        <!-- ===== NHÓM CỦA TÔI ===== -->
                                        <div class="tab-pane fade" id="my-groups" role="tabpanel">
                                            <div class="my-groups-topbar">
                                                <div class="search-box">
                                                    <i class="bi bi-search"></i>
                                                    <input type="text" id="search-my-groups" placeholder="Tìm nhóm của tôi...">
                                                </div>
                                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                                                    <i class="bi bi-plus-circle me-1"></i>Tạo nhóm mới
                                                </button>
                                            </div>

                                            <?php if (empty($myGroups)): ?>
                                                <div class="text-center py-5 text-muted">
                                                    <i class="bi bi-person-x fs-2 d-block mb-2"></i>
                                                    Bạn chưa tham gia nhóm nào trong sự kiện này.
                                                </div>
                                            <?php else: ?>
                                                <div class="row" id="my-groups-grid">
                                                <?php foreach ($myGroups as $idx => $g): ?>
                                                    <?php
                                                    $gId      = $g['idnhom'];
                                                    $isTruong = ($g['idvaitronhom'] == 1);
                                                    $hasGVHD  = !is_null($g['gvhd']);
                                                    $hasPendingGV = $g['pendingGVHD'];
                                                    $soYC     = count($g['yeuCauCho']);
                                                    ?>
                                                    <div class="col-lg-6 col-md-6 my-group-item" data-ten="<?= htmlspecialchars(strtolower($g['tennhom'])) ?>">
                                                        <div class="nhom-card">

                                                            <div class="nhom-card-header">
                                                                <h5><?= htmlspecialchars($g['tennhom']) ?></h5>
                                                                <?php if ($g['dangtuyen']): ?>
                                                                    <span class="badge-cong-khai">Công khai</span>
                                                                <?php else: ?>
                                                                    <span class="badge-rieng-tu">Riêng tư</span>
                                                                <?php endif; ?>
                                                            </div>

                                                            <!-- Thành viên chips -->
                                                            <div class="tv-chips">
                                                                <?php foreach ($g['thanhVien'] as $tv): ?>
                                                                    <?php if ($tv['idvaitronhom'] == 3) continue; ?>
                                                                    <span class="tv-chip <?= $tv['idvaitronhom'] == 1 ? 'leader' : '' ?>">
                                                                        <i class="bi bi-<?= $tv['idvaitronhom'] == 1 ? 'shield-fill-check' : 'person' ?>"></i>
                                                                        <?= htmlspecialchars($tv['tenTV']) ?>
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>

                                                            <!-- GVHD -->
                                                            <?php if ($hasGVHD): ?>
                                                                <div class="gvhd-row">
                                                                    <i class="bi bi-person-workspace"></i>
                                                                    GVHD: <?= htmlspecialchars($g['gvhd']['tenGVHD']) ?>
                                                                </div>
                                                            <?php elseif ($hasPendingGV): ?>
                                                                <div class="gvhd-pending">
                                                                    <i class="bi bi-clock-fill" style="color:#3b82f6"></i>
                                                                    Đang chờ GVHD xác nhận lời mời
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="gvhd-alert">
                                                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                                                    Nhóm cần có GVHD
                                                                </div>
                                                            <?php endif; ?>

                                                            <!-- Đề tài -->
                                                            <?php if ($g['sanPham']): ?>
                                                                <div class="nhom-detai">
                                                                    <strong>Đề tài:</strong> <?= htmlspecialchars($g['sanPham']['tensanpham']) ?>
                                                                    <span class="badge ms-1 bg-<?= $g['sanPham']['TrangThai'] == 'Đã duyệt' ? 'success' : 'warning text-dark' ?>">
                                                                        <?= htmlspecialchars($g['sanPham']['TrangThai']) ?>
                                                                    </span>
                                                                </div>
                                                            <?php endif; ?>

                                                            <!-- Action buttons -->
                                                            <div class="nhom-actions">
                                                                <?php if ($isTruong): ?>
                                                                    <button type="button" class="btn-nhom btn-nhom-manage"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#manageModal-<?= $gId ?>">
                                                                        <i class="bi bi-gear"></i> Quản lý
                                                                        <?php if ($soYC > 0): ?>
                                                                            <span class="badge bg-danger ms-1"><?= $soYC ?></span>
                                                                        <?php endif; ?>
                                                                    </button>

                                                                    <button type="button" class="btn-nhom btn-nhom-invite"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#inviteModal-<?= $gId ?>">
                                                                        <i class="bi bi-person-plus"></i> Mời
                                                                    </button>

                                                                    <?php if (!$hasGVHD && !$hasPendingGV): ?>
                                                                        <button type="button" class="btn-nhom btn-nhom-gvhd"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#gvhdModal-<?= $gId ?>">
                                                                            <i class="bi bi-person-badge"></i> Mời GVHD
                                                                        </button>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>

                                                                <button type="button" class="btn-nhom btn-nhom-submit"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#submitModal-<?= $gId ?>">
                                                                    <i class="bi bi-cloud-upload"></i> Nộp bài
                                                                </button>
                                                            </div>

                                                        </div>
                                                    </div>

                                                    <!-- ====== MODAL QUẢN LÝ (per group) ====== -->
                                                    <div class="modal fade" id="manageModal-<?= $gId ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                                            <div class="modal-content">
                                                                <div class="modal-header-grad d-flex justify-content-between align-items-center">
                                                                    <h5 class="modal-title mb-0">
                                                                        <i class="bi bi-gear me-2"></i>Quản lý nhóm: <?= htmlspecialchars($g['tennhom']) ?>
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body p-0">
                                                                    <ul class="nav modal-nav border-bottom px-3" role="tablist">
                                                                        <li class="nav-item">
                                                                            <button class="nav-link active" data-bs-toggle="tab"
                                                                                data-bs-target="#tab-tv-<?= $gId ?>" type="button">
                                                                                Thành viên (<?= count(array_filter($g['thanhVien'], fn($tv) => $tv['idvaitronhom'] != 3)) ?>)
                                                                            </button>
                                                                        </li>
                                                                        <li class="nav-item">
                                                                            <button class="nav-link" data-bs-toggle="tab"
                                                                                data-bs-target="#tab-yc-<?= $gId ?>" type="button">
                                                                                Yêu cầu tham gia
                                                                                <?php if ($soYC > 0): ?>
                                                                                    <span class="badge bg-danger ms-1"><?= $soYC ?></span>
                                                                                <?php endif; ?>
                                                                            </button>
                                                                        </li>
                                                                        <li class="nav-item">
                                                                            <button class="nav-link" data-bs-toggle="tab"
                                                                                data-bs-target="#tab-set-<?= $gId ?>" type="button">
                                                                                Cài đặt
                                                                            </button>
                                                                        </li>
                                                                    </ul>
                                                                    <div class="tab-content p-4">

                                                                        <!-- Tab: Thành viên -->
                                                                        <div class="tab-pane fade show active" id="tab-tv-<?= $gId ?>">
                                                                            <p class="text-muted small mb-3">Danh sách thành viên đang hoạt động trong nhóm.</p>
                                                                            <?php if (empty($g['thanhVien'])): ?>
                                                                                <p class="text-muted">Chưa có thành viên.</p>
                                                                            <?php else: ?>
                                                                                <?php foreach ($g['thanhVien'] as $tv): ?>
                                                                                    <div class="member-row">
                                                                                        <div class="d-flex align-items-center gap-2">
                                                                                            <?php
                                                                                            $chipClass = match((int)$tv['idvaitronhom']) {
                                                                                                1 => 'truong',
                                                                                                3 => 'gvhd-chip',
                                                                                                default => 'thanh-vien'
                                                                                            };
                                                                                            $icon = match((int)$tv['idvaitronhom']) {
                                                                                                1 => 'shield-fill-check',
                                                                                                3 => 'person-workspace',
                                                                                                default => 'person'
                                                                                            };
                                                                                            ?>
                                                                                            <span class="member-chip <?= $chipClass ?>">
                                                                                                <i class="bi bi-<?= $icon ?>"></i>
                                                                                                <?= htmlspecialchars($tv['tenTV']) ?>
                                                                                            </span>
                                                                                            <span class="text-muted small">(<?= htmlspecialchars($tv['vaiTro'] ?? '—') ?>)</span>
                                                                                        </div>
                                                                                        <?php if ($tv['idvaitronhom'] != 1): ?>
                                                                                            <form method="POST" onsubmit="return confirm('Xác nhận xóa thành viên này?')">
                                                                                                <input type="hidden" name="xoa_thanh_vien" value="1">
                                                                                                <input type="hidden" name="idNhom" value="<?= $gId ?>">
                                                                                                <input type="hidden" name="idTK" value="<?= $tv['idtk'] ?>">
                                                                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                                                    <i class="bi bi-x-circle"></i> Xóa
                                                                                                </button>
                                                                                            </form>
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            <?php endif; ?>
                                                                        </div>

                                                                        <!-- Tab: Yêu cầu tham gia -->
                                                                        <div class="tab-pane fade" id="tab-yc-<?= $gId ?>">
                                                                            <?php if (empty($g['yeuCauCho'])): ?>
                                                                                <div class="text-center py-4 text-muted">
                                                                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                                                                    Không có yêu cầu nào đang chờ duyệt.
                                                                                </div>
                                                                            <?php else: ?>
                                                                                <p class="text-muted small mb-3">Duyệt hoặc từ chối các yêu cầu tham gia nhóm.</p>
                                                                                <?php foreach ($g['yeuCauCho'] as $yc): ?>
                                                                                    <div class="req-row">
                                                                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                                                                            <div>
                                                                                                <strong><?= htmlspecialchars($yc['tenNguoiGui']) ?></strong>
                                                                                                <span class="badge bg-<?= $yc['ChieuMoi'] == 1 ? 'info text-dark' : 'secondary' ?> ms-2 small">
                                                                                                    <?= $yc['ChieuMoi'] == 1 ? 'Xin tham gia' : 'Được nhóm mời' ?>
                                                                                                </span>
                                                                                                <?php if (!empty($yc['loiNhan'])): ?>
                                                                                                    <div class="text-muted small mt-1 fst-italic">"<?= htmlspecialchars($yc['loiNhan']) ?>"</div>
                                                                                                <?php endif; ?>
                                                                                                <div class="req-meta"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($yc['ngayGui'])) ?></div>
                                                                                            </div>
                                                                                            <div class="d-flex gap-2">
                                                                                                <form method="POST">
                                                                                                    <input type="hidden" name="duyet_yeucau" value="1">
                                                                                                    <input type="hidden" name="idYeuCau" value="<?= $yc['idYeuCau'] ?>">
                                                                                                    <input type="hidden" name="trangThai" value="1">
                                                                                                    <button type="submit" class="btn btn-sm btn-success">
                                                                                                        <i class="bi bi-check-lg"></i> Duyệt
                                                                                                    </button>
                                                                                                </form>
                                                                                                <form method="POST">
                                                                                                    <input type="hidden" name="duyet_yeucau" value="1">
                                                                                                    <input type="hidden" name="idYeuCau" value="<?= $yc['idYeuCau'] ?>">
                                                                                                    <input type="hidden" name="trangThai" value="2">
                                                                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                                                        <i class="bi bi-x-lg"></i> Từ chối
                                                                                                    </button>
                                                                                                </form>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            <?php endif; ?>
                                                                        </div>

                                                                        <!-- Tab: Cài đặt -->
                                                                        <div class="tab-pane fade" id="tab-set-<?= $gId ?>">
                                                                            <form method="POST">
                                                                                <input type="hidden" name="cap_nhat_nhom" value="1">
                                                                                <input type="hidden" name="idNhom" value="<?= $gId ?>">
                                                                                <div class="mb-3">
                                                                                    <label class="form-label fw-semibold">Tên nhóm</label>
                                                                                    <input type="text" name="tennhom" class="form-control" value="<?= htmlspecialchars($g['tennhom']) ?>">
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label class="form-label fw-semibold">Mô tả</label>
                                                                                    <textarea name="mota" class="form-control" rows="3"><?= htmlspecialchars($g['mota']) ?></textarea>
                                                                                </div>
                                                                                <div class="mb-3 form-check">
                                                                                    <input type="checkbox" class="form-check-input" id="dt-<?= $gId ?>" name="dangtuyen" value="1" <?= $g['dangtuyen'] ? 'checked' : '' ?>>
                                                                                    <label class="form-check-label" for="dt-<?= $gId ?>">Đang tuyển thành viên (Công khai)</label>
                                                                                </div>
                                                                                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Lưu cài đặt</button>
                                                                            </form>
                                                                        </div>

                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div><!-- End manageModal -->

                                                    <!-- ====== MODAL MỜI THÀNH VIÊN (per group) ====== -->
                                                    <div class="modal fade" id="inviteModal-<?= $gId ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <div class="modal-header-grad d-flex justify-content-between align-items-center">
                                                                    <h5 class="modal-title mb-0"><i class="bi bi-person-plus me-2"></i>Mời thành viên vào nhóm</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body p-4">
                                                                    <div class="info-alert-box">
                                                                        <i class="bi bi-info-circle-fill"></i>
                                                                        Sinh viên được mời sẽ nhận thông báo và có thể chấp nhận hoặc từ chối.
                                                                    </div>
                                                                    <!-- Kết quả thông báo -->
                                                                    <div id="invite-result-<?= $gId ?>" style="display:none" class="mb-3"></div>
                                                                    <div id="invite-form-<?= $gId ?>">
                                                                        <div class="mb-3">
                                                                            <label class="form-label fw-semibold">Chọn sinh viên <span class="text-danger">*</span></label>
                                                                            <input type="text" id="sv-search-<?= $gId ?>" class="form-control mb-2" placeholder="Tìm theo tên hoặc mã SV...">
                                                                            <select class="form-select" id="sv-select-<?= $gId ?>" size="5" style="height:auto">
                                                                                <?php foreach ($sv_list as $sv):
                                                                                    if (in_array($sv['idTK'], $g['dsMaTK'])) continue; ?>
                                                                                    <option value="<?= $sv['idTK'] ?>"
                                                                                        data-search="<?= strtolower($sv['tenSV'] . ' ' . $sv['MSV']) ?>">
                                                                                        <?= htmlspecialchars($sv['tenSV']) ?> (<?= htmlspecialchars($sv['MSV']) ?>)
                                                                                        <?= !empty($sv['tenLop']) ? ' — ' . htmlspecialchars($sv['tenLop']) : '' ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label fw-semibold">Lời nhắn</label>
                                                                            <textarea id="sv-loinhan-<?= $gId ?>" class="form-control" rows="3" placeholder="Lời nhắn kèm theo lời mời..."></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer" id="invite-footer-<?= $gId ?>">
                                                                    <button type="button" class="btn btn-primary"
                                                                        onclick="submitMoiSV(<?= $gId ?>)">
                                                                        <i class="bi bi-send me-1"></i>Gửi lời mời
                                                                    </button>
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div><!-- End inviteModal -->

                                                    <!-- ====== MODAL MỜI GVHD (per group) ====== -->
                                                    <div class="modal fade" id="gvhdModal-<?= $gId ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <div class="modal-header-grad d-flex justify-content-between align-items-center">
                                                                    <h5 class="modal-title mb-0"><i class="bi bi-person-badge me-2"></i>Mời Giảng viên hướng dẫn</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body p-4">
                                                                    <div class="info-alert-box">
                                                                        <i class="bi bi-info-circle-fill"></i>
                                                                        Bạn chỉ gửi được 1 lời mời GVHD tại một thời điểm. Nếu GVHD từ chối, bạn có thể mời người khác.
                                                                    </div>
                                                                    <!-- Kết quả thông báo -->
                                                                    <div id="gvhd-result-<?= $gId ?>" style="display:none" class="mb-3"></div>
                                                                    <div id="gvhd-form-<?= $gId ?>">
                                                                        <div class="mb-3">
                                                                            <label class="form-label fw-semibold">Chọn Giảng viên <span class="text-danger">*</span></label>
                                                                            <select class="form-select" id="gv-select-<?= $gId ?>">
                                                                                <option value="">-- Chọn giảng viên --</option>
                                                                                <?php foreach ($gv_list as $gv): ?>
                                                                                    <option value="<?= $gv['idTK'] ?>">
                                                                                        <?= htmlspecialchars($gv['tenGV']) ?>
                                                                                        <?= !empty($gv['tenKhoa']) ? ' — ' . htmlspecialchars($gv['tenKhoa']) : '' ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label fw-semibold">Lời nhắn gửi GVHD</label>
                                                                            <textarea id="gv-loinhan-<?= $gId ?>" class="form-control" rows="4" placeholder="Giới thiệu về đề tài và lý do mời GVHD..."></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer" id="gvhd-footer-<?= $gId ?>">
                                                                    <button type="button" class="btn btn-primary"
                                                                        onclick="submitMoiGVHD(<?= $gId ?>)">
                                                                        <i class="bi bi-send me-1"></i>Gửi lời mời
                                                                    </button>
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div><!-- End gvhdModal -->
                                                    <!-- ====== MODAL NỘP BÀI (per group) ====== -->
                                                    <div class="modal fade" id="submitModal-<?= $gId ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                                            <div class="modal-content">
                                                                <div class="modal-header-grad d-flex justify-content-between align-items-center">
                                                                    <h5 class="modal-title mb-0">
                                                                        <i class="bi bi-cloud-upload me-2"></i>
                                                                        <?= $g['sanPham'] ? 'Cập nhật bài nộp' : 'Nộp bài' ?> — <?= htmlspecialchars($g['tennhom']) ?>
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="POST" enctype="multipart/form-data">
                                                                    <input type="hidden" name="nop_bai" value="1">
                                                                    <input type="hidden" name="idNhom" value="<?= $gId ?>">
                                                                    <div class="modal-body p-4">

                                                                        <?php if ($g['sanPham']): ?>
                                                                            <div class="alert alert-info py-2 mb-3">
                                                                                <strong>Trạng thái:</strong>
                                                                                <span class="badge ms-1 bg-<?= $g['sanPham']['TrangThai']=='Đã duyệt'?'success':'warning text-dark' ?>">
                                                                                    <?= htmlspecialchars($g['sanPham']['TrangThai']) ?>
                                                                                </span>
                                                                            </div>
                                                                        <?php endif; ?>

                                                                        <div class="mb-3">
                                                                            <label class="form-label fw-semibold">Tên đề tài <span class="text-danger">*</span></label>
                                                                            <input type="text" name="tenDeTai" class="form-control" required
                                                                                placeholder="Nhập tên đề tài nghiên cứu..."
                                                                                value="<?= htmlspecialchars($g['sanPham']['tensanpham'] ?? '') ?>">
                                                                        </div>

                                                                        <div class="mb-4">
                                                                            <label class="form-label fw-semibold">Chủ đề nghiên cứu</label>
                                                                            <select name="idChuDe" class="form-select">
                                                                                <option value="0">-- Chọn chủ đề --</option>
                                                                                <?php foreach ($chude_list as $cd): ?>
                                                                                    <option value="<?= $cd['idChuDeSK'] ?>"
                                                                                        <?= (($g['sanPham']['idChuDeSK'] ?? 0)==$cd['idChuDeSK'])?'selected':'' ?>>
                                                                                        <?= htmlspecialchars($cd['tenChuDe']) ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>

                                                                        <hr class="my-3">
                                                                        <label class="form-label fw-semibold mb-2">
                                                                            <i class="bi bi-paperclip me-1"></i>Tệp bài nộp
                                                                        </label>

                                                                        <?php if (!empty($g['sanPhamTheoLoai'])): ?>
                                                                        <div class="submitted-files-box mb-3 p-3 rounded border bg-light">
                                                                            <p class="fw-semibold small text-muted mb-2">
                                                                                <i class="bi bi-check2-circle text-success me-1"></i>Tập tin đã nộp:
                                                                            </p>
                                                                            <?php
                                                                            $loaiLabel=[1=>['icon'=>'file-earmark-text','label'=>'Báo cáo tóm tắt','color'=>'primary'],
                                                                                        2=>['icon'=>'file-earmark-richtext','label'=>'Báo cáo toàn văn','color'=>'info'],
                                                                                        3=>['icon'=>'github','label'=>'Source Code','color'=>'dark']];
                                                                            foreach ($loaiLabel as $idLoai=>$meta):
                                                                                $sp=$g['sanPhamTheoLoai'][$idLoai]??null;
                                                                                if(!$sp) continue; ?>
                                                                                <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                                                                                    <i class="bi bi-<?= $meta['icon'] ?> text-<?= $meta['color'] ?> fs-5"></i>
                                                                                    <span class="badge bg-<?= $meta['color'] ?> small"><?= $meta['label'] ?></span>
                                                                                    <a href="<?= strpos($sp['moTataiLieu'],'http')===0
                                                                                        ?htmlspecialchars($sp['moTataiLieu'])
                                                                                        :_HOST_URL.'/'.htmlspecialchars($sp['moTataiLieu']) ?>"
                                                                                        target="_blank" class="small text-truncate flex-1">
                                                                                        <?= htmlspecialchars(basename($sp['moTataiLieu'])) ?>
                                                                                    </a>
                                                                                    <span class="badge bg-success">Đã nộp</span>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                            <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Upload mới sẽ thay thế file cũ.</p>
                                                                        </div>
                                                                        <?php endif; ?>

                                                                        <!-- Dropzone -->
                                                                        <div id="dropzone-<?= $gId ?>"
                                                                            class="border border-2 border-dashed rounded-3 p-4 text-center bg-light"
                                                                            style="border-style:dashed!important;cursor:pointer"
                                                                            onclick="document.getElementById('fileInput-<?= $gId ?>').click()">
                                                                            <i class="bi bi-cloud-arrow-up fs-1 text-primary d-block mb-2"></i>
                                                                            <p class="fw-semibold mb-1">
                                                                                Kéo thả file vào đây hoặc <span class="text-primary text-decoration-underline">chọn file</span>
                                                                            </p>
                                                                            <p class="text-muted small mb-1">PDF, DOC, DOCX (báo cáo) · ZIP, RAR (source code)</p>
                                                                            <p class="text-muted small mb-0">Tối đa 20MB/file</p>
                                                                            <input type="file" name="files[]" id="fileInput-<?= $gId ?>"
                                                                                multiple accept=".pdf,.doc,.docx,.zip,.rar,.pptx"
                                                                                style="display:none"
                                                                                onchange="handleFileSelect(this, '<?= $gId ?>')">
                                                                        </div>

                                                                        <div id="fileList-<?= $gId ?>" class="mt-3" style="display:none">
                                                                            <p class="fw-semibold small text-muted mb-2">
                                                                                <i class="bi bi-list-ul me-1"></i>Tập tin sẽ nộp:
                                                                            </p>
                                                                            <div id="fileItems-<?= $gId ?>"></div>
                                                                        </div>
                                                                        <div id="fileList-<?= $gId ?>" class="file-list mt-3" style="display:none">
                                                                            <p class="fw-semibold small text-muted mb-2"><i class="bi bi-list-ul me-1"></i>Tập tin sẽ nộp:</p>
                                                                            <div id="fileItems-<?= $gId ?>"></div>
                                                                        </div>

                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="submit" class="btn btn-success">
                                                                            <i class="bi bi-cloud-check me-1"></i><?= $g['sanPham']?'Cập nhật bài nộp':'Nộp bài' ?>
                                                                        </button>
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div><!-- End submitModal -->                                                                                                       
                                                                        
                                                <?php endforeach; ?>
                                                </div><!-- End my-groups-grid -->
                                            <?php endif; ?>
                                        </div><!-- End my-groups tab -->

                                        <!-- ===== TAB: LỜI MỜI NHÓM ===== -->
                                        <div class="tab-pane fade" id="loi-moi" role="tabpanel">
                                            <div class="py-3">
                                                <?php if (empty($loiMoiList)): ?>
                                                    <div class="lm-empty">
                                                        <i class="bi bi-envelope-open"></i>
                                                        <p class="fw-semibold mt-3 mb-1">Không có lời mời</p>
                                                        <p class="text-muted small">Bạn chưa có lời mời tham gia nhóm nào trong sự kiện này.</p>
                                                    </div>
                                                <?php else: ?>
                                                    <p class="text-muted small mb-3">Bạn có <strong><?= $soLoiMoi ?></strong> lời mời đang chờ phản hồi.</p>
                                                    <div class="row g-3" id="loi-moi-grid">
                                                    <?php foreach ($loiMoiList as $lm): ?>
                                                        <div class="col-md-6" id="lm-card-<?= $lm['idYeuCau'] ?>">
                                                            <div class="lm-card">
                                                                <div class="lm-card-header">
                                                                    <div class="lm-icon"><i class="bi bi-people-fill"></i></div>
                                                                    <div class="lm-info">
                                                                        <h6><?= htmlspecialchars($lm['tennhom']) ?></h6>
                                                                        <span class="lm-meta">
                                                                            <i class="bi bi-person-badge me-1"></i>
                                                                            Nhóm trưởng: <?= htmlspecialchars($lm['tenNhomTruong'] ?: '—') ?>
                                                                        </span>
                                                                    </div>
                                                                    <span class="lm-count"><?= $lm['soThanhVien'] ?>/<?= $lm['soluongtoida'] ?></span>
                                                                </div>

                                                                <?php if (!empty($lm['mota'])): ?>
                                                                    <p class="lm-mota"><?= htmlspecialchars($lm['mota']) ?></p>
                                                                <?php endif; ?>

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

                                                                <!-- Kết quả sau khi phản hồi -->
                                                                <div id="lm-result-<?= $lm['idYeuCau'] ?>" style="display:none" class="mt-2"></div>

                                                                <div class="lm-actions" id="lm-actions-<?= $lm['idYeuCau'] ?>">
                                                                    <button class="btn-lm btn-lm-accept"
                                                                        onclick="phanHoiLoiMoi(<?= $lm['idYeuCau'] ?>, 1, '<?= htmlspecialchars(addslashes($lm['tennhom'])) ?>')">
                                                                        <i class="bi bi-check-lg"></i> Chấp nhận
                                                                    </button>
                                                                    <button class="btn-lm btn-lm-decline"
                                                                        onclick="phanHoiLoiMoi(<?= $lm['idYeuCau'] ?>, 2, '<?= htmlspecialchars(addslashes($lm['tennhom'])) ?>')">
                                                                        <i class="bi bi-x-lg"></i> Từ chối
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div><!-- End loi-moi tab -->

                                    </div>
                                </div>
                            </div><!-- End event-groups -->

                            <!-- Tab Cấu hình -->
                            <div class="tab-pane fade" id="event-config" role="tabpanel">
                                <div class="event-config-content" data-aos="fade-up" data-aos-delay="100">
                                    <h3>Cấu hình sự kiện</h3>
                                    <p>Chọn khu vực cấu hình phù hợp để thiết lập quy chế, vòng thi và bộ tiêu chí.</p>
                                    <div class="d-flex flex-column gap-2 mt-3">
                                        <a class="btn btn-primary" href="<?= _HOST_URL ?>/?module=event&action=config_rounds&id=<?= $id ?>"><i class="bi bi-sliders me-2"></i>Cấu hình cơ bản</a>
                                        <a class="btn btn-primary" href="<?= _HOST_URL ?>/?module=event&action=config_rules&id=<?= $id ?>"><i class="bi bi-journal-text me-2"></i>Quy chế &amp; Điều kiện</a>
                                        <a class="btn btn-primary" href="<?= _HOST_URL ?>/?module=event&action=config_criteria&id=<?= $id ?>"><i class="bi bi-clipboard-check me-2"></i>Bộ tiêu chí &amp; Chấm điểm</a>
                                        <a class="btn btn-primary" href="<?= _HOST_URL ?>/?module=event&action=config_assign&id=<?= $id ?>"><i class="bi bi-person-lines-fill me-2"></i>Phân công chấm</a>
                                        <a class="btn btn-primary" href="<?= _HOST_URL ?>/?module=event&action=config_schedule&id=<?= $id ?>"><i class="bi bi-calendar3 me-2"></i>Lập lịch tổ chức</a>
                                    </div>
                                </div>
                            </div>
                            <!-- Tab Bài nộp của tôi -->
                            <?php if ($userId > 0): ?>
                            <div class="tab-pane fade" id="my-submissions" role="tabpanel">
                                <div class="pt-3">
                                    <h5 class="fw-bold mb-3"><i class="bi bi-folder2-open me-2 text-success"></i>Bài nộp của tôi</h5>
                                    <?php
                                    $hasAnySP = false;
                                    foreach ($myGroups as $g) { if (!empty($g['sanPhamTheoLoai'])) { $hasAnySP=true; break; } }
                                    ?>
                                    <?php if (!$hasAnySP): ?>
                                        <div class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            Bạn chưa nộp bài cho sự kiện này.
                                        </div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table align-middle mb-0" style="border-radius:12px;overflow:hidden">
                                            <thead>
                                                <tr style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff">
                                                    <th class="py-3 px-4" style="font-weight:600">Nhóm</th>
                                                    <th class="py-3 px-4" style="font-weight:600">Tên đề tài</th>
                                                    <th class="py-3 px-4" style="font-weight:600">Ngày nộp</th>
                                                    <th class="py-3 px-4 text-center" style="font-weight:600">Tài liệu</th>
                                                    <th class="py-3 px-4 text-center" style="font-weight:600">Trạng thái</th>
                                                    <th class="py-3 px-4 text-center" style="font-weight:600">Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($myGroups as $g):
                                                if (empty($g['sanPhamTheoLoai'])) continue;
                                                $sp = $g['sanPham'];
                                                $loaiIcons=[1=>'file-earmark-text',2=>'file-earmark-richtext',3=>'github'];
                                                $loaiColors=[1=>'primary',2=>'info',3=>'dark'];
                                                $loaiLabels=[1=>'Tóm tắt',2=>'Toàn văn',3=>'Source'];
                                                $ngayNop = $sp['ngaytao'] ?? ($sp['ngayCapNhat'] ?? null);
                                            ?>
                                            <tr class="border-bottom">
                                                <td class="px-4 py-3 fw-semibold"><?= htmlspecialchars($g['tennhom']) ?></td>
                                                <td class="px-4 py-3"><strong><?= htmlspecialchars($sp['tensanpham']??'—') ?></strong></td>
                                                <td class="px-4 py-3 text-muted small">
                                                    <?php
                                                    // Lấy ngày từ bản ghi sanpham — cần thêm vào query nếu chưa có
                                                    // Tạm dùng thời gian hiện tại nếu không có
                                                    echo '—';
                                                    ?>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <div class="d-flex gap-2 justify-content-center">
                                                    <?php foreach ($loaiIcons as $idLoai=>$icon):
                                                        $spL=$g['sanPhamTheoLoai'][$idLoai]??null; ?>
                                                        <?php if ($spL): ?>
                                                            <a href="<?= strpos($spL['moTataiLieu'],'http')===0
                                                                ?htmlspecialchars($spL['moTataiLieu'])
                                                                :_HOST_URL.'/'.htmlspecialchars($spL['moTataiLieu']) ?>"
                                                                target="_blank"
                                                                class="btn btn-sm btn-outline-<?= $loaiColors[$idLoai] ?>"
                                                                title="<?= $loaiLabels[$idLoai] ?>">
                                                                <i class="bi bi-<?= $icon ?>"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="btn btn-sm btn-light disabled text-muted" title="Chưa nộp <?= $loaiLabels[$idLoai] ?>">
                                                                <i class="bi bi-<?= $icon ?>"></i>
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span class="badge rounded-pill bg-<?= ($sp['TrangThai']??'')==='Đã duyệt'?'success':'warning text-dark' ?> px-3 py-2">
                                                        <?= htmlspecialchars($sp['TrangThai']??'Chờ duyệt') ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#submitModal-<?= $g['idnhom'] ?>">
                                                        <i class="bi bi-pencil-square me-1"></i>Cập nhật
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <!-- Tab Cấu hình -->

                        </div>
                    </div>

                </div><!-- col-lg-8 -->

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="course-details-card" data-aos="fade-up" data-aos-delay="300">
                        <h4>Thông tin sự kiện</h4>
                        <div class="detail-grid">
                            <div class="detail-row"><span class="detail-label">Cấp tổ chức</span><span class="detail-value"><?= htmlspecialchars($event['tenCap'] ?? '—') ?></span></div>
                            <div class="detail-row"><span class="detail-label">Mở đăng ký</span><span class="detail-value"><?= $event['ngayMoDangKy'] ? date('d/m/Y', strtotime($event['ngayMoDangKy'])) : '—' ?></span></div>
                            <div class="detail-row"><span class="detail-label">Đóng đăng ký</span><span class="detail-value"><?= $event['ngayDongDangKy'] ? date('d/m/Y', strtotime($event['ngayDongDangKy'])) : '—' ?></span></div>
                            <div class="detail-row"><span class="detail-label">Ngày bắt đầu</span><span class="detail-value"><?= $event['ngayBatDau'] ? date('d/m/Y', strtotime($event['ngayBatDau'])) : '—' ?></span></div>
                            <div class="detail-row"><span class="detail-label">Ngày kết thúc</span><span class="detail-value"><?= $event['ngayKetThuc'] ? date('d/m/Y', strtotime($event['ngayKetThuc'])) : '—' ?></span></div>
                            <div class="detail-row"><span class="detail-label">Tổng số nhóm</span><span class="detail-value"><?= count($groups) ?></span></div>
                            <div class="detail-row">
                                <span class="detail-label">Trạng thái</span>
                                <span class="detail-value">
                                    <?php
                                    $now = time();
                                    $start = $event['ngayBatDau'] ? strtotime($event['ngayBatDau']) : null;
                                    $end   = $event['ngayKetThuc'] ? strtotime($event['ngayKetThuc']) : null;
                                    if (!$start) echo '<span class="badge bg-secondary">Chưa có lịch</span>';
                                    elseif ($start > $now) echo '<span class="badge bg-info text-dark">Sắp diễn ra</span>';
                                    elseif (!$end || $end >= $now) echo '<span class="badge bg-success">Đang diễn ra</span>';
                                    else echo '<span class="badge bg-secondary">Đã kết thúc</span>';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
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

<!-- MODAL TẠO NHÓM -->
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
                        <label class="form-label fw-semibold">Mô tả nhóm</label>
                        <textarea name="mota" class="form-control" rows="3" placeholder="Mô tả ngắn về nhóm..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                    <button type="submit" name="create_group" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Tạo nhóm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php layout('footer'); ?>

<script>
const AJAX_URL = window.location.href;

// ---- Tìm kiếm nhóm ----
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

// ---- Tìm kiếm SV trong dropdown ----
document.querySelectorAll('[id^="sv-search-"]').forEach(input => {
    const gId = input.id.replace('sv-search-', '');
    const select = document.getElementById('sv-select-' + gId);
    if (!select) return;
    input.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        Array.from(select.options).forEach(opt => {
            opt.style.display = !q || opt.dataset.search?.includes(q) ? '' : 'none';
        });
    });
});

// ================================================================
// AJAX: MỜI SINH VIÊN
// ================================================================
function submitMoiSV(gId) {
    const select   = document.getElementById('sv-select-' + gId);
    const loinhan  = document.getElementById('sv-loinhan-' + gId);
    const resultEl = document.getElementById('invite-result-' + gId);
    const formEl   = document.getElementById('invite-form-' + gId);
    const footerEl = document.getElementById('invite-footer-' + gId);

    if (!select.value) {
        showResult(resultEl, false, 'Vui lòng chọn sinh viên cần mời.');
        return;
    }

    // Disable button
    const btn = footerEl.querySelector('.btn-primary');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang gửi...';

    const body = new URLSearchParams({
        ajax_action: 'moi_thanh_vien',
        idNhom: gId,
        idSV: select.value,
        loiNhan: loinhan.value
    });

    fetch(AJAX_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (data.status) {
                // Thành công: ẩn form, hiện thông báo xanh lá + tên người được mời
                const tenSV = select.options[select.selectedIndex]?.text || '';
                formEl.style.display = 'none';
                footerEl.querySelector('.btn-primary').style.display = 'none';
                showResult(resultEl, true,
                    `<i class="bi bi-check-circle-fill me-2"></i>
                     <strong>Đã gửi lời mời thành công!</strong><br>
                     <span class="text-muted small">Lời mời đã được gửi tới <strong>${escHtml(tenSV)}</strong>. 
                     Họ sẽ nhận được thông báo và có thể chấp nhận hoặc từ chối.</span>`
                );
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send me-1"></i>Gửi lời mời';
                showResult(resultEl, false, data.message);
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send me-1"></i>Gửi lời mời';
            showResult(resultEl, false, 'Lỗi kết nối, vui lòng thử lại.');
        });
}

// ================================================================
// AJAX: MỜI GVHD
// ================================================================
function submitMoiGVHD(gId) {
    const select   = document.getElementById('gv-select-' + gId);
    const loinhan  = document.getElementById('gv-loinhan-' + gId);
    const resultEl = document.getElementById('gvhd-result-' + gId);
    const formEl   = document.getElementById('gvhd-form-' + gId);
    const footerEl = document.getElementById('gvhd-footer-' + gId);

    if (!select.value) {
        showResult(resultEl, false, 'Vui lòng chọn giảng viên cần mời.');
        return;
    }

    const btn = footerEl.querySelector('.btn-primary');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang gửi...';

    const body = new URLSearchParams({
        ajax_action: 'moi_gvhd',
        idNhom: gId,
        idGV: select.value,
        loiNhan: loinhan.value
    });

    fetch(AJAX_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (data.status) {
                const tenGV = select.options[select.selectedIndex]?.text || '';
                formEl.style.display = 'none';
                footerEl.querySelector('.btn-primary').style.display = 'none';
                showResult(resultEl, true,
                    `<i class="bi bi-check-circle-fill me-2"></i>
                     <strong>Đã gửi lời mời thành công!</strong><br>
                     <span class="text-muted small">Lời mời hướng dẫn đã được gửi tới 
                     <strong>${escHtml(tenGV)}</strong>. 
                     Giảng viên sẽ nhận thông báo và xác nhận hoặc từ chối.</span>`
                );
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send me-1"></i>Gửi lời mời';
                showResult(resultEl, false, data.message);
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send me-1"></i>Gửi lời mời';
            showResult(resultEl, false, 'Lỗi kết nối, vui lòng thử lại.');
        });
}

// ================================================================
// Helper: hiển thị alert trong modal
// ================================================================
function showResult(el, success, html) {
    el.style.display = '';
    el.innerHTML = `
        <div class="alert alert-${success ? 'success' : 'warning'} py-3 mb-0" role="alert">
            ${html}
        </div>`;
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Reset modal về trạng thái ban đầu khi đóng
document.querySelectorAll('[id^="inviteModal-"], [id^="gvhdModal-"]').forEach(modal => {
    modal.addEventListener('hidden.bs.modal', function() {
        const suffix = this.id.replace('inviteModal-', '').replace('gvhdModal-', '');
        const isInvite = this.id.startsWith('inviteModal');
        const prefix  = isInvite ? 'invite' : 'gvhd';

        const resultEl = document.getElementById(prefix + '-result-' + suffix);
        const formEl   = document.getElementById(prefix + '-form-'   + suffix);
        const footerEl = document.getElementById(prefix + '-footer-' + suffix);
        if (!resultEl) return;

        resultEl.style.display = 'none';
        resultEl.innerHTML = '';
        if (formEl) formEl.style.display = '';
        if (footerEl) {
            const btn = footerEl.querySelector('.btn-primary');
            if (btn) { btn.style.display = ''; btn.disabled = false; btn.innerHTML = '<i class="bi bi-send me-1"></i>Gửi lời mời'; }
        }
        // Reset select và textarea
        if (isInvite) {
            const sel = document.getElementById('sv-select-' + suffix);
            const ta  = document.getElementById('sv-loinhan-' + suffix);
            const srch = document.getElementById('sv-search-' + suffix);
            if (sel) sel.value = '';
            if (ta) ta.value = '';
            if (srch) srch.value = '';
        } else {
            const sel = document.getElementById('gv-select-' + suffix);
            const ta  = document.getElementById('gv-loinhan-' + suffix);
            if (sel) sel.value = '';
            if (ta) ta.value = '';
        }
    });
});
// ================================================================
// AJAX: PHẢN HỒI LỜI MỜI NHÓM (Chấp nhận / Từ chối)
// ================================================================
function phanHoiLoiMoi(idYeuCau, trangThai, tenNhom) {
    const actionsEl = document.getElementById('lm-actions-' + idYeuCau);
    const resultEl  = document.getElementById('lm-result-'  + idYeuCau);

    // Disable buttons
    actionsEl.querySelectorAll('button').forEach(b => { b.disabled = true; });

    const body = new URLSearchParams({
        ajax_action: 'phan_hoi_loi_moi',
        idYeuCau: idYeuCau,
        trangThai: trangThai
    });

    fetch(AJAX_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            actionsEl.style.display = 'none';
            resultEl.style.display  = '';
            if (data.status && trangThai == 1) {
                resultEl.innerHTML = `
                    <div class="alert alert-success py-2 mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <div>
                            <strong>Đã tham gia nhóm!</strong><br>
                            <span class="small text-muted">Bạn đã chấp nhận lời mời vào nhóm <strong>${escHtml(tenNhom)}</strong>.</span>
                        </div>
                    </div>`;
                // Cập nhật badge số lời mời
                updateLoiMoiBadge(-1);
            } else if (data.status && trangThai == 2) {
                resultEl.innerHTML = `
                    <div class="alert alert-secondary py-2 mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-x-circle fs-5"></i>
                        <div>
                            <strong>Đã từ chối.</strong><br>
                            <span class="small text-muted">Bạn đã từ chối lời mời từ nhóm <strong>${escHtml(tenNhom)}</strong>.</span>
                        </div>
                    </div>`;
                updateLoiMoiBadge(-1);
            } else {
                resultEl.innerHTML = `<div class="alert alert-danger py-2 mb-0">${escHtml(data.message)}</div>`;
                actionsEl.style.display = '';
                actionsEl.querySelectorAll('button').forEach(b => { b.disabled = false; });
            }
        })
        .catch(() => {
            actionsEl.style.display = '';
            actionsEl.querySelectorAll('button').forEach(b => { b.disabled = false; });
            resultEl.style.display = '';
            resultEl.innerHTML = '<div class="alert alert-danger py-2 mb-0">Lỗi kết nối, vui lòng thử lại.</div>';
        });
}

function updateLoiMoiBadge(delta) {
    // Cập nhật badge trên tab "Lời mời nhóm"
    const tabBtn = document.querySelector('[data-bs-target="#loi-moi"]');
    if (!tabBtn) return;
    let badge = tabBtn.querySelector('.badge');
    const current = badge ? parseInt(badge.textContent) : 0;
    const newVal  = current + delta;
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
// ================================================================
// UPLOAD MULTI-FILE
// ================================================================
const fileStores = {};

function getStore(gId) {
    if (!fileStores[gId]) fileStores[gId] = [];
    return fileStores[gId];
}

function addFilesToStore(gId, newFiles) {
    const store = getStore(gId);
    Array.from(newFiles).forEach(newFile => {
        const exists = store.some(f => f.name === newFile.name && f.size === newFile.size);
        if (!exists) store.push(newFile);
    });
    renderFileList(gId);
}

function removeFileFromStore(gId, index) {
    getStore(gId).splice(index, 1);
    renderFileList(gId);
}

function handleFileSelect(input, gId) {
    if (input.files.length) {
        addFilesToStore(gId, input.files);
    }
    // KHÔNG reset input.value ở đây — để form vẫn giữ files
}

function renderFileList(gId) {
    const store   = getStore(gId);
    const listEl  = document.getElementById('fileList-' + gId);
    const itemsEl = document.getElementById('fileItems-' + gId);

    if (!store.length) {
        if (listEl) listEl.style.display = 'none';
        if (itemsEl) itemsEl.innerHTML = '';
        return;
    }
    if (listEl) listEl.style.display = '';
    if (itemsEl) itemsEl.innerHTML = '';

    store.forEach((file, index) => {
        const ext = file.name.split('.').pop().toLowerCase();
        let iconCls;
        if (['zip','rar'].includes(ext))         iconCls = 'bi-file-earmark-zip text-warning';
        else if (ext === 'pdf')                   iconCls = 'bi-file-earmark-pdf text-danger';
        else if (['doc','docx'].includes(ext))    iconCls = 'bi-file-earmark-word text-primary';
        else                                      iconCls = 'bi-file-earmark text-secondary';

        const sz = file.size < 1048576
            ? (file.size / 1024).toFixed(1) + ' KB'
            : (file.size / 1048576).toFixed(1) + ' MB';

        const div = document.createElement('div');
        div.className = 'd-flex align-items-center gap-2 p-2 mb-2 border rounded bg-white';
        div.innerHTML = `
            <i class="bi ${iconCls} fs-5 flex-shrink-0"></i>
            <span class="flex-grow-1 text-truncate small" title="${escHtml(file.name)}">${escHtml(file.name)}</span>
            <span class="text-muted small flex-shrink-0">${sz}</span>
            <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0 py-0 px-1"
                onclick="removeFileFromStore('${gId}', ${index})">
                <i class="bi bi-x"></i>
            </button>`;
        itemsEl.appendChild(div);
    });

    // Đồng bộ vào input ẩn để form submit được
    syncStoreToInput(gId);
}

function syncStoreToInput(gId) {
    const input = document.getElementById('fileInput-' + gId);
    if (!input) return;
    const store = getStore(gId);
    try {
        const dt = new DataTransfer();
        store.forEach(f => dt.items.add(f));
        input.files = dt.files;
    } catch(e) {
        console.warn('DataTransfer không hỗ trợ:', e);
    }
}

// Drag & drop
document.querySelectorAll('[id^="dropzone-"]').forEach(zone => {
    zone.addEventListener('dragover', e => { e.preventDefault(); e.stopPropagation(); zone.classList.add('border-primary', 'bg-primary', 'bg-opacity-10'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10'));
    zone.addEventListener('drop', e => {
        e.preventDefault(); e.stopPropagation();
        zone.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
        const gId = zone.id.replace('dropzone-', '');
        addFilesToStore(gId, e.dataTransfer.files);
    });
});

// Reset store khi đóng modal
document.querySelectorAll('[id^="submitModal-"]').forEach(modal => {
    modal.addEventListener('hidden.bs.modal', function() {
        const gId = this.id.replace('submitModal-', '');
        fileStores[gId] = [];
        const listEl  = document.getElementById('fileList-'  + gId);
        const itemsEl = document.getElementById('fileItems-' + gId);
        const input   = document.getElementById('fileInput-' + gId);
        if (listEl)  listEl.style.display = 'none';
        if (itemsEl) itemsEl.innerHTML = '';
        if (input)   input.value = '';
    });
});

// POPUP NỘP BÀI THÀNH CÔNG
<?php if (($flashMsg ?? '') === 'nop_bai_thanh_cong'): ?>
document.addEventListener('DOMContentLoaded', () => { showSubmitSuccessPopup(); });
<?php endif; ?>

function showSubmitSuccessPopup() {
    const overlay = document.createElement('div');
    overlay.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
    overlay.style.cssText = 'background:rgba(0,0,0,.5);z-index:9999';
    overlay.innerHTML = `
        <div class="bg-white rounded-4 p-5 text-center shadow-lg" style="max-width:420px;width:90%;animation:popIn .35s cubic-bezier(.34,1.56,.64,1)">
            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 bg-success"
                style="width:80px;height:80px">
                <i class="bi bi-check-lg text-white" style="font-size:2.5rem"></i>
            </div>
            <h4 class="fw-bold mb-2">Nộp bài thành công!</h4>
            <p class="text-muted mb-4">Bài nộp của bạn đã được ghi nhận và đang chờ duyệt.</p>
            <button class="btn btn-success px-4 me-2" id="btnXemBaiNop">
                <i class="bi bi-folder2-open me-2"></i>Xem bài nộp của tôi
            </button>
            <button class="btn btn-light px-4" onclick="this.closest('.position-fixed').remove()">Đóng</button>
        </div>`;
    document.body.appendChild(overlay);

    document.getElementById('btnXemBaiNop').addEventListener('click', () => {
        overlay.remove();
        // Chuyển sang tab ngoài "Bài nộp của tôi"
        const tabEl = document.querySelector('button[data-bs-target="#my-submissions"]');
        if (tabEl) {
            bootstrap.Tab.getOrCreateInstance(tabEl).show();
            tabEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });

    setTimeout(() => { if (overlay.parentNode) overlay.remove(); }, 8000);
}
</script>
