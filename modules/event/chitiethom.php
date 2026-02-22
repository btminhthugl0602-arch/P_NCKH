<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
global $conn;

require_once _PATH_URL . '/modules/functions/quan_ly_nhom.php';

$idNhom = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idNhom <= 0) { require_once _PATH_URL . '/modules/errors/404.php'; exit; }

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Lấy thông tin nhóm
$resNhom = mysqli_query($conn, "
    SELECT n.idnhom, n.idSK, n.idnhomtruong, t.tennhom, t.mota, t.soluongtoida, t.dangtuyen,
           sk.tenSK, sk.moTa AS moTaSK, sk.ngayBatDau, sk.ngayKetThuc,
           COALESCE(sv_t.tenSV, gv_t.tenGV, tk_t.tenTK, '') AS tenNhomTruong
    FROM nhom n
    LEFT JOIN thongtinnhom t ON n.idnhom = t.idnhom
    LEFT JOIN sukien sk ON n.idSK = sk.idSK
    LEFT JOIN taikhoan tk_t ON n.idnhomtruong = tk_t.idTK
    LEFT JOIN sinhvien sv_t ON tk_t.idTK = sv_t.idTK
    LEFT JOIN giangvien gv_t ON tk_t.idTK = gv_t.idTK
    WHERE n.idnhom = $idNhom AND n.isActive = 1
    LIMIT 1
");
if (!$resNhom || mysqli_num_rows($resNhom) == 0) { require_once _PATH_URL . '/modules/errors/404.php'; exit; }
$nhom = mysqli_fetch_assoc($resNhom);
$idSK = $nhom['idSK'];

// Kiểm tra quyền
$isMember = false; $isTruong = false;
if ($userId > 0) {
    $resMember = mysqli_query($conn, "SELECT idvaitronhom FROM thanhviennhom WHERE idnhom=$idNhom AND idtk=$userId AND trangthai=1 LIMIT 1");
    if ($resMember && mysqli_num_rows($resMember) > 0) {
        $isMember = true;
        $memberRow = mysqli_fetch_assoc($resMember);
        $isTruong = ($memberRow['idvaitronhom'] == 1);
    }
}

// ================== XỬ LÝ DUYỆT YÊU CẦU ==================
if (isset($_POST['duyet_yeucau']) && $isTruong) {
    $idYeuCau  = (int)($_POST['idYeuCau'] ?? 0);
    $trangThai = (int)($_POST['trangThai'] ?? 2);
    duyet_yeu_cau_nhom($conn, $userId, $idYeuCau, $trangThai);
    header("Location: " . $_SERVER['REQUEST_URI']); exit();
}

// ================== XỬ LÝ XÓA THÀNH VIÊN ==================
if (isset($_POST['xoa_thanh_vien']) && $isTruong) {
    $idTKXoa = (int)($_POST['idTK'] ?? 0);
    roi_nhom($conn, $userId, $idNhom, $idTKXoa);
    header("Location: " . $_SERVER['REQUEST_URI']); exit();
}

// ================== XỬ LÝ RỜI NHÓM ==================
if (isset($_POST['roi_nhom']) && $isMember && !$isTruong) {
    $result = roi_nhom($conn, $userId, $idNhom, $userId);
    if ($result['status']) {
        $_SESSION['flash_msg']  = 'Bạn đã rời nhóm thành công.';
        $_SESSION['flash_type'] = 'success';
        header("Location: " . _HOST_URL . "?module=event&action=view&id=" . $idSK); exit();
    } else {
        $_SESSION['flash_msg']  = $result['message'];
        $_SESSION['flash_type'] = 'danger';
        header("Location: " . $_SERVER['REQUEST_URI']); exit();
    }
}

// ================== XỬ LÝ NHƯỢNG QUYỀN TRƯỞNG NHÓM ==================
if (isset($_POST['nhuong_quyen']) && $isTruong) {
    $idTKMoi = (int)($_POST['idTKMoi'] ?? 0);
    if ($idTKMoi > 0 && $idTKMoi !== $userId) {
        // Kiểm tra người được nhượng quyền phải là thành viên nhóm
        $checkTV = mysqli_query($conn, "SELECT idvaitronhom FROM thanhviennhom WHERE idnhom=$idNhom AND idtk=$idTKMoi AND trangthai=1 LIMIT 1");
        if ($checkTV && mysqli_num_rows($checkTV) > 0) {
            $tvRow = mysqli_fetch_assoc($checkTV);
            if ($tvRow['idvaitronhom'] != 3) { // không nhượng cho GVHD
                mysqli_query($conn, "UPDATE thanhviennhom SET idvaitronhom=2 WHERE idnhom=$idNhom AND idtk=$userId AND trangthai=1");
                mysqli_query($conn, "UPDATE thanhviennhom SET idvaitronhom=1 WHERE idnhom=$idNhom AND idtk=$idTKMoi AND trangthai=1");
                mysqli_query($conn, "UPDATE nhom SET idnhomtruong=$idTKMoi WHERE idnhom=$idNhom");
                $_SESSION['flash_msg']  = 'Đã nhượng quyền trưởng nhóm thành công.';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_msg']  = 'Không thể nhượng quyền cho Giảng viên hướng dẫn.';
                $_SESSION['flash_type'] = 'warning';
            }
        } else {
            $_SESSION['flash_msg']  = 'Người được chọn không phải thành viên nhóm.';
            $_SESSION['flash_type'] = 'danger';
        }
    }
    header("Location: " . $_SERVER['REQUEST_URI']); exit();
}

// ================== XỬ LÝ CẬP NHẬT NHÓM ==================
if (isset($_POST['cap_nhat_nhom']) && $isTruong) {
    $tenNhomMoi  = mysqli_real_escape_string($conn, trim($_POST['tennhom'] ?? ''));
    $motaMoi     = mysqli_real_escape_string($conn, trim($_POST['mota'] ?? ''));
    $dangTuyen   = isset($_POST['dangtuyen']) ? 1 : 0;
    if (!empty($tenNhomMoi)) {
        mysqli_query($conn, "UPDATE thongtinnhom SET tennhom='$tenNhomMoi', mota='$motaMoi', dangtuyen=$dangTuyen WHERE idnhom=$idNhom");
        $_SESSION['flash_msg']  = 'Đã cập nhật thông tin nhóm thành công.';
        $_SESSION['flash_type'] = 'success';
    }
    header("Location: " . $_SERVER['REQUEST_URI']); exit();
}

// ================== AJAX ==================
if (isset($_POST['ajax_action']) && $userId > 0) {
    header('Content-Type: application/json; charset=utf-8');
    $ajax_action = $_POST['ajax_action'];

    if ($ajax_action === 'moi_thanh_vien' && $isTruong) {
        $idSV   = (int)($_POST['idSV'] ?? 0);
        $loiNhan = trim($_POST['loiNhan'] ?? '');
        $result = gui_yeu_cau_nhom($conn, $idNhom, $idSV, 0, $loiNhan);
    } elseif ($ajax_action === 'moi_gvhd' && $isTruong) {
        $idGV   = (int)($_POST['idGV'] ?? 0);
        $loiNhan = trim($_POST['loiNhan'] ?? '');
        $result = gui_yeu_cau_nhom($conn, $idNhom, $idGV, 0, $loiNhan);
    } else {
        $result = ['status' => false, 'message' => 'Không có quyền thực hiện'];
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit();
}

// ================== XỬ LÝ NỘP BÀI ==================
if (isset($_POST['nop_bai']) && $isMember) {
    $tenDeTai  = mysqli_real_escape_string($conn, trim($_POST['tenDeTai'] ?? ''));
    $idChuDeRaw = (int)($_POST['idChuDe'] ?? 0);
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
            if (in_array($ext, ['zip','rar'])) { $idLoai = 3; }
            else { $idLoai = $docIndex <= 1 ? 1 : 2; $docIndex++; }
            $originalName = pathinfo($_FILES['files']['name'][$i], PATHINFO_FILENAME);
            $safeOriginal = preg_replace('/[^a-zA-Z0-9_\-\.\x{0080}-\x{FFFF}]/u', '_', $originalName);
            $newName = $safeOriginal . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $uploadDir.$newName)) {
                $uploaded[$idLoai] = mysqli_real_escape_string($conn, 'uploads/sanpham/'.$newName);
            }
        }
    }

    if (!empty($tenDeTai)) {
        $chuDeSQL = $idChuDeRaw > 0 ? $idChuDeRaw : 'NULL';
        mysqli_query($conn, "UPDATE sanpham SET tensanpham='$tenDeTai', idChuDeSK=$chuDeSQL WHERE idNhom=$idNhom AND idSK=$idSK");
        foreach ($uploaded as $idLoai => $filePath) {
            $check = mysqli_query($conn, "SELECT idSanPham FROM sanpham WHERE idNhom=$idNhom AND idSK=$idSK AND idloaitailieu=$idLoai LIMIT 1");
            if (mysqli_num_rows($check) > 0) {
                $row = mysqli_fetch_assoc($check);
                mysqli_query($conn, "UPDATE sanpham SET moTataiLieu='$filePath' WHERE idSanPham={$row['idSanPham']}");
            } else {
                mysqli_query($conn, "INSERT INTO sanpham (idNhom,idSK,idChuDeSK,idloaitailieu,moTataiLieu,TrangThai,isActive,tensanpham) VALUES ($idNhom,$idSK,$chuDeSQL,$idLoai,'$filePath','Chờ duyệt',1,'$tenDeTai')");
            }
        }
        $_SESSION['flash_msg']  = 'nop_bai_thanh_cong';
        $_SESSION['flash_type'] = 'success';
    }
    header("Location: " . $_SERVER['REQUEST_URI']); exit();
}

// ================== LẤY DỮ LIỆU ==================
// Thành viên
$resTV = mysqli_query($conn, "
    SELECT tv.idtk, tv.idvaitronhom,
           COALESCE(sv.tenSV, gv2.tenGV, tk.tenTK) AS tenTV,
           vn.tenvaitronhom AS vaiTro, tk.idLoaiTK
    FROM thanhviennhom tv
    JOIN taikhoan tk ON tv.idtk = tk.idTK
    LEFT JOIN sinhvien sv ON tk.idTK = sv.idTK
    LEFT JOIN giangvien gv2 ON tk.idTK = gv2.idTK
    LEFT JOIN vaitronhom vn ON tv.idvaitronhom = vn.id
    WHERE tv.idnhom = $idNhom AND tv.trangthai = 1
    ORDER BY tv.idvaitronhom ASC
");
$danhSachTV = $resTV ? mysqli_fetch_all($resTV, MYSQLI_ASSOC) : [];
$soThanhVien = count(array_filter($danhSachTV, fn($tv) => $tv['idvaitronhom'] != 3));

// GVHD
$resGVHD = mysqli_query($conn, "
    SELECT COALESCE(gv2.tenGV, tk.tenTK) AS tenGVHD, tv.idtk
    FROM thanhviennhom tv
    JOIN taikhoan tk ON tv.idtk = tk.idTK
    LEFT JOIN giangvien gv2 ON tk.idTK = gv2.idTK
    WHERE tv.idnhom = $idNhom AND tv.idvaitronhom = 3 AND tv.trangthai = 1
    LIMIT 1
");
$gvhd = ($resGVHD && mysqli_num_rows($resGVHD) > 0) ? mysqli_fetch_assoc($resGVHD) : null;

// Yêu cầu chờ duyệt
$resYC = mysqli_query($conn, "
    SELECT yc.idYeuCau, yc.ChieuMoi, yc.loiNhan, yc.ngayGui,
           COALESCE(sv.tenSV, gv2.tenGV, tk.tenTK) AS tenNguoiGui,
           tk.idLoaiTK
    FROM yeucau_thamgia yc
    JOIN taikhoan tk ON yc.idTK = tk.idTK
    LEFT JOIN sinhvien sv ON tk.idTK = sv.idTK
    LEFT JOIN giangvien gv2 ON tk.idTK = gv2.idTK
    WHERE yc.idNhom = $idNhom AND yc.trangThai = 0
    ORDER BY yc.ngayGui DESC
");
$yeuCauCho = $resYC ? mysqli_fetch_all($resYC, MYSQLI_ASSOC) : [];
$soYC = count($yeuCauCho);

// Pending GVHD
$resPGV = mysqli_query($conn, "
    SELECT yc.idYeuCau FROM yeucau_thamgia yc
    JOIN taikhoan tk ON yc.idTK = tk.idTK
    WHERE yc.idNhom = $idNhom AND yc.trangThai = 0 AND tk.idLoaiTK = 2
    LIMIT 1
");
$pendingGVHD = ($resPGV && mysqli_num_rows($resPGV) > 0);

// Danh sách SV (để mời)
$resSV = mysqli_query($conn, "SELECT sv.idTK, sv.tenSV, sv.MSV, l.tenLop FROM sinhvien sv JOIN taikhoan tk ON sv.idTK=tk.idTK LEFT JOIN lop l ON sv.idLop=l.idLop WHERE tk.isActive=1 ORDER BY sv.tenSV");
$sv_list = $resSV ? mysqli_fetch_all($resSV, MYSQLI_ASSOC) : [];
$dsMaTK = array_column($danhSachTV, 'idtk');

// Danh sách GV
$resGV = mysqli_query($conn, "SELECT gv.idTK, gv.tenGV, k.tenKhoa FROM giangvien gv JOIN taikhoan tk ON gv.idTK=tk.idTK LEFT JOIN khoa k ON gv.idKhoa=k.idKhoa WHERE tk.isActive=1 ORDER BY gv.tenGV");
$gv_list = $resGV ? mysqli_fetch_all($resGV, MYSQLI_ASSOC) : [];

// Chủ đề sự kiện
$resCD = mysqli_query($conn, "SELECT cs.idChuDeSK, c.tenChuDe FROM chude_sukien cs JOIN chude c ON cs.idchude=c.idChuDe WHERE cs.idSK=$idSK AND cs.isActive=1");
$chude_list = $resCD ? mysqli_fetch_all($resCD, MYSQLI_ASSOC) : [];

// Bài nộp
$resSP = mysqli_query($conn, "SELECT sp.*, l.loaitailieu AS tenLoaiTL FROM sanpham sp LEFT JOIN loaitailieu l ON sp.idloaitailieu=l.idtailieu WHERE sp.idNhom=$idNhom AND sp.idSK=$idSK ORDER BY sp.idloaitailieu ASC");
$spRows = $resSP ? mysqli_fetch_all($resSP, MYSQLI_ASSOC) : [];
$sanPhamTheoLoai = [];
foreach ($spRows as $row) { $sanPhamTheoLoai[$row['idloaitailieu']] = $row; }
$sanPham = !empty($spRows) ? $spRows[0] : null;

$flashMsg  = $_SESSION['flash_msg']  ?? '';
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

$data = ['page_title' => 'Chi tiết nhóm: ' . htmlspecialchars($nhom['tennhom'] ?? '')];
layout('header', $data);
layout('navbar');
?>

<style>
/* ===== MODAL STYLES ===== */
.modal-header-grad { background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; border-radius:12px 12px 0 0; padding:16px 20px; }
.modal-header-grad .btn-close { filter:brightness(0) invert(1); }
.modal-content { border-radius:12px; border:none; }
.modal-nav .nav-link { color:#555; font-weight:500; padding:10px 18px; border:none; border-bottom:3px solid transparent; background:none; border-radius:0; }
.modal-nav .nav-link.active { color:#4f46e5; border-bottom-color:#4f46e5; background:none; }
.modal-nav .nav-link:hover { color:#4f46e5; }

/* ===== MEMBER CHIPS ===== */
.member-chip { display:inline-flex; align-items:center; gap:6px; padding:5px 14px; border-radius:20px; font-size:13px; font-weight:600; }
.member-chip.truong { background:#ede9fe; color:#5b21b6; }
.member-chip.thanh-vien { background:#f1f3fb; color:#3b4a85; }
.member-chip.gvhd-chip { background:#ecfdf5; color:#065f46; }

/* ===== MEMBER ROW ===== */
.member-row { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f3f4f6; }
.member-row:last-child { border-bottom:none; }

/* ===== REQUEST ROW ===== */
.req-row { background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:12px 14px; margin-bottom:10px; }
.req-row .req-meta { font-size:12px; color:#9ca3af; margin-top:4px; }

/* ===== INFO ALERT ===== */
.info-alert-box { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 14px; font-size:13px; color:#1e40af; margin-bottom:16px; display:flex; align-items:flex-start; gap:8px; }
.info-alert-box i { font-size:16px; margin-top:1px; flex-shrink:0; }

/* GVHD indicators */
.gvhd-row { background:#4f46e5; color:#fff; border-radius:8px; padding:7px 14px; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; margin-bottom:10px; }
.gvhd-alert { background:#fffbeb; border:1px solid #fcd34d; border-radius:8px; padding:10px 14px; font-size:13px; color:#92400e; display:flex; align-items:center; gap:8px; margin-bottom:10px; }
.gvhd-pending { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 14px; font-size:13px; color:#1e40af; display:flex; align-items:center; gap:8px; margin-bottom:10px; }

/* ===== UPLOAD DROPZONE ===== */
.upload-dropzone { border:2px dashed #c5cef8; border-radius:12px; padding:36px 20px; text-align:center; background:#f8f9ff; transition:background .2s, border-color .2s; cursor:pointer; }
.upload-dropzone:hover, .upload-dropzone.dragover { background:#eef0fd; border-color:#4f46e5; }

/* ===== SUBMITTED FILES ===== */
.submitted-file-row { display:flex; align-items:center; gap:10px; padding:8px 12px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; margin-bottom:6px; font-size:13px; }
.submitted-file-row .file-label { flex:1; min-width:0; }

/* ===== SUCCESS POPUP ===== */
@keyframes popIn { from { transform:scale(.7); opacity:0; } to { transform:scale(1); opacity:1; } }

/* ===== GROUP HERO (inherits course-hero styles) ===== */
.group-stats-row { display:flex; gap:16px; flex-wrap:wrap; margin-top:12px; }
.group-stat { display:flex; align-items:center; gap:6px; font-size:14px; color:#555; }
.group-stat i { color:#4f46e5; }
</style>

<main class="main">
    <?php if ($flashMsg && $flashMsg !== 'nop_bai_thanh_cong'): ?>
    <div class="container pt-3">
        <div class="alert alert-<?= $flashType ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flashMsg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0"><?= htmlspecialchars($nhom['tennhom'] ?? '') ?></h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?= _HOST_URL ?>">Home</a></li>
                    <li><a href="<?= _HOST_URL ?>?module=event&action=view&id=<?= $idSK ?>"><?= htmlspecialchars($nhom['tenSK'] ?? '') ?></a></li>
                    <li class="current"><?= htmlspecialchars($nhom['tennhom'] ?? '') ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <section class="course-details section">
        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row">
                <div class="col-lg-8">

                    <!-- Hero section -->
                    <div class="course-hero" data-aos="fade-up" data-aos-delay="200">
                        <div class="hero-content">
                            <div class="course-badge">
                                <span class="category">Nhóm thi</span>
                                <span class="level"><?= $nhom['dangtuyen'] ? 'Đang tuyển' : 'Đã đủ thành viên' ?></span>
                            </div>
                            <h1><?= htmlspecialchars($nhom['tennhom'] ?? '') ?></h1>
                            <p class="course-subtitle"><?= nl2br(htmlspecialchars($nhom['mota'] ?? 'Chưa có mô tả.')) ?></p>

                            <!-- Sự kiện như "instructor card" -->
                            <div class="instructor-card">
                                <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/education/courses-8.webp" alt="Event" class="instructor-image" style="object-fit:cover;">
                                <div class="instructor-details">
                                    <h5><?= htmlspecialchars($nhom['tenSK'] ?? '') ?></h5>
                                    <span>Nhóm trưởng: <?= htmlspecialchars($nhom['tenNhomTruong'] ?? '') ?></span>
                                    <?php if ($gvhd): ?>
                                        <div class="mt-1">
                                            <span class="badge" style="background:#4f46e5;color:#fff;font-size:11px;">
                                                <i class="bi bi-person-workspace me-1"></i>GVHD: <?= htmlspecialchars($gvhd['tenGVHD']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Stats -->
                            <div class="group-stats-row">
                                <div class="group-stat"><i class="bi bi-people-fill"></i> <?= $soThanhVien ?>/<?= $nhom['soluongtoida'] ?> thành viên</div>
                                <?php if ($sanPham): ?>
                                    <div class="group-stat"><i class="bi bi-journal-text"></i> <?= htmlspecialchars($sanPham['tensanpham']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="hero-image">
                            <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/education/courses-3.webp" alt="Nhóm" class="img-fluid">
                        </div>
                    </div>

                    <!-- Nav tabs -->
                    <div class="course-nav-tabs" data-aos="fade-up" data-aos-delay="300">
                        <ul class="nav nav-tabs" id="GroupDetailTabs" role="tablist">
                            <?php if ($isTruong): ?>
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-quanly" type="button">
                                    <i class="bi bi-gear"></i> Quản lý
                                    <?php if ($soYC > 0): ?><span class="badge bg-danger ms-1"><?= $soYC ?></span><?php endif; ?>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-moi" type="button">
                                    <i class="bi bi-person-plus"></i> Mời tham gia
                                </button>
                            </li>
                            <?php else: ?>
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-thanhvien" type="button">
                                    <i class="bi bi-people"></i> Thành viên
                                </button>
                            </li>
                            <?php endif; ?>
                            <?php if ($isMember): ?>
                            <li class="nav-item">
                                <button class="nav-link <?= !$isTruong ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-nopbai" type="button">
                                    <i class="bi bi-cloud-upload"></i> Nộp bài
                                    <?php if ($sanPham): ?><span class="badge bg-success ms-1">1</span><?php endif; ?>
                                </button>
                            </li>
                            <?php endif; ?>
                        </ul>

                        <div class="tab-content" id="GroupDetailContent">

                            <!-- ===== TAB QUẢN LÝ (chỉ trưởng nhóm) ===== -->
                            <?php if ($isTruong): ?>
                            <div class="tab-pane fade show active" id="tab-quanly" role="tabpanel">
                                <div class="pt-3">
                                    <ul class="nav modal-nav border-bottom mb-3" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#subtab-tv" type="button">
                                                <i class="bi bi-people me-1"></i>Thành viên (<?= $soThanhVien ?>)
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#subtab-yeucau" type="button">
                                                <i class="bi bi-inbox me-1"></i>Yêu cầu tham gia
                                                <?php if ($soYC > 0): ?><span class="badge bg-danger ms-1"><?= $soYC ?></span><?php endif; ?>
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#subtab-caidat" type="button">
                                                <i class="bi bi-sliders me-1"></i>Cài đặt nhóm
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content">
                                        <!-- Sub-tab: Thành viên -->
                                        <div class="tab-pane fade show active" id="subtab-tv">
                                            <p class="text-muted small mb-3">Danh sách thành viên đang hoạt động trong nhóm.</p>
                                            <?php if (empty($danhSachTV)): ?>
                                                <p class="text-muted">Chưa có thành viên.</p>
                                            <?php else: ?>
                                                <?php foreach ($danhSachTV as $tv): ?>
                                                    <div class="member-row">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <?php
                                                            $chipClass = match((int)$tv['idvaitronhom']) { 1=>'truong', 3=>'gvhd-chip', default=>'thanh-vien' };
                                                            $icon = match((int)$tv['idvaitronhom']) { 1=>'shield-fill-check', 3=>'person-workspace', default=>'person' };
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

                                        <!-- Sub-tab: Yêu cầu tham gia -->
                                        <div class="tab-pane fade" id="subtab-yeucau">
                                            <?php if (empty($yeuCauCho)): ?>
                                                <div class="text-center py-4 text-muted">
                                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                                    Không có yêu cầu nào đang chờ duyệt.
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted small mb-3">Duyệt hoặc từ chối các yêu cầu tham gia nhóm.</p>
                                                <?php foreach ($yeuCauCho as $yc): ?>
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
                                                                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Duyệt</button>
                                                                </form>
                                                                <form method="POST">
                                                                    <input type="hidden" name="duyet_yeucau" value="1">
                                                                    <input type="hidden" name="idYeuCau" value="<?= $yc['idYeuCau'] ?>">
                                                                    <input type="hidden" name="trangThai" value="2">
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Từ chối</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Sub-tab: Cài đặt nhóm -->
                                        <div class="tab-pane fade" id="subtab-caidat">
                                            <form method="POST" class="pt-2">
                                                <input type="hidden" name="cap_nhat_nhom" value="1">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Tên nhóm</label>
                                                    <input type="text" name="tennhom" class="form-control" value="<?= htmlspecialchars($nhom['tennhom'] ?? '') ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Mô tả</label>
                                                    <textarea name="mota" class="form-control" rows="3"><?= htmlspecialchars($nhom['mota']) ?></textarea>
                                                </div>
                                                <div class="mb-3 form-check">
                                                    <input type="checkbox" class="form-check-input" id="dt-nhom" name="dangtuyen" value="1" <?= $nhom['dangtuyen'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="dt-nhom">Đang tuyển thành viên (Công khai)</label>
                                                </div>
                                                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Lưu cài đặt</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== TAB THÀNH VIÊN (cho thành viên thường) ===== -->
                            <?php else: ?>
                            <div class="tab-pane fade show active" id="tab-thanhvien" role="tabpanel">
                                <div class="pt-3">
                                    <p class="text-muted small mb-3">Danh sách thành viên trong nhóm.</p>
                                    <?php foreach ($danhSachTV as $tv): ?>
                                        <div class="member-row">
                                            <?php
                                            $chipClass = match((int)$tv['idvaitronhom']) { 1=>'truong', 3=>'gvhd-chip', default=>'thanh-vien' };
                                            $icon = match((int)$tv['idvaitronhom']) { 1=>'shield-fill-check', 3=>'person-workspace', default=>'person' };
                                            ?>
                                            <span class="member-chip <?= $chipClass ?>">
                                                <i class="bi bi-<?= $icon ?>"></i>
                                                <?= htmlspecialchars($tv['tenTV']) ?>
                                            </span>
                                            <span class="text-muted small"><?= htmlspecialchars($tv['vaiTro'] ?? '—') ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- ===== TAB MỜI THAM GIA (chỉ trưởng nhóm) ===== -->
                            <?php if ($isTruong): ?>
                            <div class="tab-pane fade" id="tab-moi" role="tabpanel">
                                <div class="pt-3">
                                    <ul class="nav modal-nav border-bottom mb-3" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#subtab-moiSV" type="button">
                                                <i class="bi bi-person-plus me-1"></i>Mời sinh viên
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#subtab-moiGV" type="button">
                                                <i class="bi bi-person-badge me-1"></i>Mời GVHD
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content">
                                        <!-- Sub-tab: Mời SV -->
                                        <div class="tab-pane fade show active" id="subtab-moiSV">
                                            <div class="info-alert-box mt-2">
                                                <i class="bi bi-info-circle-fill"></i>
                                                Sinh viên được mời sẽ nhận thông báo và có thể chấp nhận hoặc từ chối.
                                            </div>
                                            <div id="sv-invite-result" style="display:none" class="mb-3"></div>
                                            <div id="sv-invite-form">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Chọn sinh viên <span class="text-danger">*</span></label>
                                                    <input type="text" id="sv-search-inline" class="form-control mb-2" placeholder="Tìm theo tên hoặc mã SV...">
                                                    <select class="form-select" id="sv-select-inline" size="6" style="height:auto">
                                                        <?php foreach ($sv_list as $sv):
                                                            if (in_array($sv['idTK'], $dsMaTK)) continue; ?>
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
                                                    <textarea id="sv-loinhan-inline" class="form-control" rows="3" placeholder="Lời nhắn kèm theo lời mời..."></textarea>
                                                </div>
                                                <button type="button" class="btn btn-primary" onclick="submitMoiSVInline()">
                                                    <i class="bi bi-send me-1"></i>Gửi lời mời
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Sub-tab: Mời GVHD -->
                                        <div class="tab-pane fade" id="subtab-moiGV">
                                            <?php if ($gvhd): ?>
                                                <div class="alert alert-success mt-2">
                                                    <i class="bi bi-check-circle-fill me-2"></i>
                                                    Nhóm đã có GVHD: <strong><?= htmlspecialchars($gvhd['tenGVHD']) ?></strong>
                                                </div>
                                            <?php elseif ($pendingGVHD): ?>
                                                <div class="alert alert-info mt-2">
                                                    <i class="bi bi-clock-fill me-2"></i>
                                                    Đang chờ GVHD xác nhận lời mời.
                                                </div>
                                            <?php else: ?>
                                                <div class="info-alert-box mt-2">
                                                    <i class="bi bi-info-circle-fill"></i>
                                                    Bạn chỉ gửi được 1 lời mời GVHD tại một thời điểm.
                                                </div>
                                                <div id="gv-invite-result" style="display:none" class="mb-3"></div>
                                                <div id="gv-invite-form">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Chọn Giảng viên <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="gv-select-inline">
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
                                                        <textarea id="gv-loinhan-inline" class="form-control" rows="4" placeholder="Giới thiệu về đề tài và lý do mời GVHD..."></textarea>
                                                    </div>
                                                    <button type="button" class="btn btn-warning text-white" onclick="submitMoiGVInline()">
                                                        <i class="bi bi-send me-1"></i>Gửi lời mời GVHD
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- ===== TAB NỘP BÀI ===== -->
                            <?php if ($isMember): ?>
                            <div class="tab-pane fade <?= !$isTruong ? 'show active' : '' ?>" id="tab-nopbai" role="tabpanel">
                                <div class="pt-3">
                                    <ul class="nav modal-nav border-bottom mb-3" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#subtab-nopbai" type="button">
                                                <i class="bi bi-cloud-upload me-1"></i><?= $sanPham ? 'Cập nhật bài nộp' : 'Nộp bài' ?>
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#subtab-tatcabainop" type="button">
                                                <i class="bi bi-folder2-open me-1"></i>Tất cả bài nộp
                                                <?php if (!empty($sanPhamTheoLoai)): ?><span class="badge bg-success ms-1"><?= count($sanPhamTheoLoai) ?></span><?php endif; ?>
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content">
                                        <!-- Sub-tab: Form nộp bài -->
                                        <div class="tab-pane fade show active" id="subtab-nopbai">
                                            <form method="POST" enctype="multipart/form-data" class="pt-2">
                                                <input type="hidden" name="nop_bai" value="1">

                                                <?php if ($sanPham): ?>
                                                    <div class="alert alert-info py-2 mb-3">
                                                        <strong>Trạng thái:</strong>
                                                        <span class="badge ms-1 bg-<?= $sanPham['TrangThai']=='Đã duyệt'?'success':'warning text-dark' ?>">
                                                            <?= htmlspecialchars($sanPham['TrangThai']) ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Tên đề tài <span class="text-danger">*</span></label>
                                                    <input type="text" name="tenDeTai" class="form-control" required
                                                        placeholder="Nhập tên đề tài nghiên cứu..."
                                                        value="<?= htmlspecialchars($sanPham['tensanpham'] ?? '') ?>">
                                                </div>

                                                <div class="mb-4">
                                                    <label class="form-label fw-semibold">Chủ đề nghiên cứu</label>
                                                    <select name="idChuDe" class="form-select">
                                                        <option value="0">-- Chọn chủ đề --</option>
                                                        <?php foreach ($chude_list as $cd): ?>
                                                            <option value="<?= $cd['idChuDeSK'] ?>"
                                                                <?= (($sanPham['idChuDeSK'] ?? 0)==$cd['idChuDeSK'])?'selected':'' ?>>
                                                                <?= htmlspecialchars($cd['tenChuDe']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <hr class="my-3">
                                                <label class="form-label fw-semibold mb-2">
                                                    <i class="bi bi-paperclip me-1"></i>Tệp bài nộp
                                                </label>

                                                <!-- Files đã nộp -->
                                                <?php if (!empty($sanPhamTheoLoai)):
                                                    $loaiLabel=[1=>['icon'=>'file-earmark-text','label'=>'Báo cáo tóm tắt','color'=>'primary'],
                                                                2=>['icon'=>'file-earmark-richtext','label'=>'Báo cáo toàn văn','color'=>'info'],
                                                                3=>['icon'=>'github','label'=>'Source Code','color'=>'dark']];
                                                    ?>
                                                    <div class="mb-3 p-3 rounded border bg-light">
                                                        <p class="fw-semibold small text-muted mb-2">
                                                            <i class="bi bi-check2-circle text-success me-1"></i>Tập tin đã nộp:
                                                        </p>
                                                        <?php foreach ($loaiLabel as $idLoai => $meta):
                                                            $sp = $sanPhamTheoLoai[$idLoai] ?? null;
                                                            if (!$sp) continue; ?>
                                                            <div class="submitted-file-row">
                                                                <i class="bi bi-<?= $meta['icon'] ?> text-<?= $meta['color'] ?> fs-5"></i>
                                                                <a href="<?= strpos($sp['moTataiLieu'],'http')===0
                                                                    ?htmlspecialchars($sp['moTataiLieu'])
                                                                    :_HOST_URL.'/'.htmlspecialchars($sp['moTataiLieu']) ?>"
                                                                    target="_blank" class="file-label small text-truncate">
                                                                    <?= htmlspecialchars(basename($sp['moTataiLieu'])) ?>
                                                                </a>
                                                                <span class="badge bg-success">Đã nộp</span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Upload mới sẽ thay thế file cũ.</p>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Dropzone -->
                                                <div id="dropzone-main"
                                                    class="upload-dropzone"
                                                    onclick="document.getElementById('fileInput-main').click()">
                                                    <i class="bi bi-cloud-arrow-up fs-1 text-primary d-block mb-2"></i>
                                                    <p class="fw-semibold mb-1">
                                                        Kéo thả file vào đây hoặc <span class="text-primary text-decoration-underline">chọn file</span>
                                                    </p>
                                                    <p class="text-muted small mb-1">PDF, DOC, DOCX (báo cáo) · ZIP, RAR (source code)</p>
                                                    <p class="text-muted small mb-0">Tối đa 20MB/file</p>
                                                    <input type="file" name="files[]" id="fileInput-main"
                                                        multiple accept=".pdf,.doc,.docx,.zip,.rar,.pptx"
                                                        style="display:none"
                                                        onchange="handleFileSelect(this)">
                                                </div>

                                                <div id="fileList-main" class="mt-3" style="display:none">
                                                    <p class="fw-semibold small text-muted mb-2"><i class="bi bi-list-ul me-1"></i>Tập tin sẽ nộp:</p>
                                                    <div id="fileItems-main"></div>
                                                </div>

                                                <div class="mt-4">
                                                    <button type="submit" class="btn btn-success px-4">
                                                        <i class="bi bi-cloud-check me-1"></i><?= $sanPham?'Cập nhật bài nộp':'Nộp bài' ?>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Sub-tab: Tất cả bài nộp -->
                                        <div class="tab-pane fade" id="subtab-tatcabainop">
                                            <?php if (empty($sanPhamTheoLoai)): ?>
                                                <div class="text-center py-5 text-muted">
                                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                                    Nhóm chưa nộp bài nào.
                                                </div>
                                            <?php else: ?>
                                                <div class="pt-2">
                                                    <p class="text-muted small mb-3">Các tài liệu đã được nộp bởi nhóm.</p>
                                                    <?php
                                                    $loaiInfo = [
                                                        1 => ['icon'=>'file-earmark-text','label'=>'Báo cáo tóm tắt','color'=>'primary'],
                                                        2 => ['icon'=>'file-earmark-richtext','label'=>'Báo cáo toàn văn','color'=>'info'],
                                                        3 => ['icon'=>'github','label'=>'Source Code','color'=>'dark'],
                                                    ];
                                                    foreach ($loaiInfo as $idLoai => $info):
                                                        $sp = $sanPhamTheoLoai[$idLoai] ?? null;
                                                        if (!$sp) continue; ?>
                                                        <div class="p-3 border rounded mb-3">
                                                            <div class="d-flex align-items-center gap-3 mb-2">
                                                                <i class="bi bi-<?= $info['icon'] ?> text-<?= $info['color'] ?> fs-4"></i>
                                                                <div>
                                                                    <span class="badge ms-1 bg-<?= $sp['TrangThai']=='Đã duyệt'?'success':'warning text-dark' ?>">
                                                                        <?= htmlspecialchars($sp['TrangThai']) ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <p class="mb-1 fw-semibold small text-muted"><?= htmlspecialchars(basename($sp['moTataiLieu'])) ?></p>
                                                            <?php if (!empty($sp['tensanpham'])): ?>
                                                                <p class="mb-1 fw-semibold"><?= htmlspecialchars($sp['tensanpham']) ?></p>
                                                            <?php endif; ?>
                                                            <a href="<?= strpos($sp['moTataiLieu'],'http')===0
                                                                ?htmlspecialchars($sp['moTataiLieu'])
                                                                :_HOST_URL.'/'.htmlspecialchars($sp['moTataiLieu']) ?>"
                                                                target="_blank" class="btn btn-sm btn-outline-<?= $info['color'] ?> mt-1">
                                                                <i class="bi bi-download me-1"></i>Tải xuống
                                                            </a>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>

                </div><!-- col-lg-8 -->

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="course-details-card" data-aos="fade-up" data-aos-delay="300">
                        <h4>Thông tin nhóm</h4>
                        <div class="detail-grid">
                            <div class="detail-row">
                                <span class="detail-label">Sự kiện</span>
                                <span class="detail-value">
                                    <a href="<?= _HOST_URL ?>?module=event&action=view&id=<?= $idSK ?>">
                                        <?= htmlspecialchars($nhom['tenSK'] ?? '') ?>
                                    </a>
                                </span>
                            </div>
                            <div class="detail-row"><span class="detail-label">Nhóm trưởng</span><span class="detail-value"><?= htmlspecialchars($nhom['tenNhomTruong'] ?? '') ?></span></div>
                            <div class="detail-row"><span class="detail-label">Thành viên</span><span class="detail-value"><?= $soThanhVien ?>/<?= $nhom['soluongtoida'] ?></span></div>
                            <div class="detail-row">
                                <span class="detail-label">GVHD</span>
                                <span class="detail-value">
                                    <?php if ($gvhd): ?>
                                        <span class="badge" style="background:#4f46e5"><?= htmlspecialchars($gvhd['tenGVHD']) ?></span>
                                    <?php elseif ($pendingGVHD): ?>
                                        <span class="badge bg-warning text-dark">Đang chờ xác nhận</span>
                                    <?php else: ?>
                                        <span class="text-danger small">Chưa có GVHD</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Trạng thái</span>
                                <span class="detail-value">
                                    <?php if ($nhom['dangtuyen']): ?>
                                        <span class="badge bg-success">Đang tuyển</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Đã đủ thành viên</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if ($sanPham): ?>
                            <div class="detail-row">
                                <span class="detail-label">Đề tài</span>
                                <span class="detail-value"><?= htmlspecialchars($sanPham['tensanpham']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Trạng thái bài</span>
                                <span class="detail-value">
                                    <span class="badge bg-<?= $sanPham['TrangThai']=='Đã duyệt'?'success':'warning text-dark' ?>">
                                        <?= htmlspecialchars($sanPham['TrangThai']) ?>
                                    </span>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Danh sách thành viên sidebar -->
                    <div class="course-details-card mt-4" data-aos="fade-up" data-aos-delay="400">
                        <h4>Thành viên nhóm</h4>
                        <?php foreach ($danhSachTV as $tv):
                            $chipClass = match((int)$tv['idvaitronhom']) { 1=>'truong', 3=>'gvhd-chip', default=>'thanh-vien' };
                            $icon = match((int)$tv['idvaitronhom']) { 1=>'shield-fill-check', 3=>'person-workspace', default=>'person' };
                            ?>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="member-chip <?= $chipClass ?>" style="font-size:12px">
                                    <i class="bi bi-<?= $icon ?>"></i>
                                    <?= htmlspecialchars($tv['tenTV']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Hành động của thành viên -->
                    <?php if ($isMember): ?>
                    <div class="course-details-card mt-4" data-aos="fade-up" data-aos-delay="500">
                        <h4>Hành động</h4>
                        <div class="d-flex flex-column gap-2">

                            <?php if ($isTruong): ?>
                            <!-- Nút Nhượng quyền (chỉ trưởng nhóm) -->
                            <?php
                            $svTrongNhom = array_filter($danhSachTV, fn($tv) => $tv['idvaitronhom'] == 2);
                            ?>
                            <?php if (!empty($svTrongNhom)): ?>
                            <button type="button" class="btn btn-outline-warning w-100"
                                data-bs-toggle="modal" data-bs-target="#nhuongQuyenModal">
                                <i class="bi bi-arrow-left-right me-2"></i>Nhượng quyền trưởng nhóm
                            </button>
                            <?php else: ?>
                            <button type="button" class="btn btn-outline-secondary w-100" disabled
                                title="Cần có thành viên khác để nhượng quyền">
                                <i class="bi bi-arrow-left-right me-2"></i>Nhượng quyền trưởng nhóm
                            </button>
                            <?php endif; ?>
                            <?php else: ?>
                            <!-- Nút Rời nhóm (thành viên thường) -->
                            <button type="button" class="btn btn-outline-danger w-100"
                                data-bs-toggle="modal" data-bs-target="#roiNhomModal">
                                <i class="bi bi-box-arrow-left me-2"></i>Rời nhóm
                            </button>
                            <?php endif; ?>

                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>
</main>

<?php layout('footer'); ?>

<!-- MODAL RỜI NHÓM -->
<?php if ($isMember && !$isTruong): ?>
<div class="modal fade" id="roiNhomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-grad d-flex justify-content-between align-items-center">
                <h5 class="modal-title mb-0"><i class="bi bi-box-arrow-left me-2"></i>Rời nhóm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-0">
                    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
                    <div>
                        Bạn có chắc muốn rời khỏi nhóm <strong><?= htmlspecialchars($nhom['tennhom'] ?? '') ?></strong>?<br>
                        <span class="small text-muted">Sau khi rời, bạn cần được mời lại hoặc xin vào nhóm mới để tham gia sự kiện.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                <form method="POST">
                    <input type="hidden" name="roi_nhom" value="1">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-box-arrow-left me-1"></i>Xác nhận rời nhóm
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MODAL NHƯỢNG QUYỀN -->
<?php if ($isTruong): ?>
<?php $svTrongNhom = array_filter($danhSachTV, fn($tv) => $tv['idvaitronhom'] == 2); ?>
<?php if (!empty($svTrongNhom)): ?>
<div class="modal fade" id="nhuongQuyenModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-grad d-flex justify-content-between align-items-center">
                <h5 class="modal-title mb-0"><i class="bi bi-arrow-left-right me-2"></i>Nhượng quyền trưởng nhóm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="nhuong_quyen" value="1">
                <div class="modal-body p-4">
                    <div class="alert alert-info d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-info-circle-fill fs-5 flex-shrink-0 mt-1"></i>
                        <div>
                            Sau khi nhượng quyền, bạn sẽ trở thành thành viên thường.<br>
                            <span class="small">Hành động này không thể hoàn tác.</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Chọn thành viên nhận quyền <span class="text-danger">*</span></label>
                        <select name="idTKMoi" class="form-select" required>
                            <option value="">-- Chọn thành viên --</option>
                            <?php foreach ($svTrongNhom as $tv): ?>
                                <option value="<?= $tv['idtk'] ?>">
                                    <?= htmlspecialchars($tv['tenTV']) ?>
                                    <?= !empty($tv['vaiTro']) ? ' (' . htmlspecialchars($tv['vaiTro']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-warning text-white">
                        <i class="bi bi-arrow-left-right me-1"></i>Nhượng quyền
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
const AJAX_URL = window.location.href;

// ---- Tìm kiếm SV inline ----
document.getElementById('sv-search-inline')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    const sel = document.getElementById('sv-select-inline');
    if (!sel) return;
    Array.from(sel.options).forEach(opt => {
        opt.style.display = !q || opt.dataset.search?.includes(q) ? '' : 'none';
    });
});

// ================================================================
// AJAX: MỜI SINH VIÊN (inline)
// ================================================================
function submitMoiSVInline() {
    const select   = document.getElementById('sv-select-inline');
    const loinhan  = document.getElementById('sv-loinhan-inline');
    const resultEl = document.getElementById('sv-invite-result');
    const formEl   = document.getElementById('sv-invite-form');

    if (!select || !select.value) {
        showInlineResult(resultEl, false, 'Vui lòng chọn sinh viên cần mời.');
        return;
    }

    const btn = formEl.querySelector('button[type="button"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang gửi...';

    const body = new URLSearchParams({
        ajax_action: 'moi_thanh_vien',
        idSV: select.value,
        loiNhan: loinhan?.value || ''
    });

    fetch(AJAX_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (data.status) {
                const tenSV = select.options[select.selectedIndex]?.text || '';
                formEl.style.display = 'none';
                showInlineResult(resultEl, true,
                    `<i class="bi bi-check-circle-fill me-2"></i>
                     <strong>Đã gửi lời mời!</strong><br>
                     <span class="small text-muted">Lời mời đã được gửi tới <strong>${escHtml(tenSV)}</strong>.</span>
                     <br><button class="btn btn-sm btn-outline-primary mt-2" onclick="resetMoiSV()">Mời người khác</button>`
                );
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send me-1"></i>Gửi lời mời';
                showInlineResult(resultEl, false, data.message);
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send me-1"></i>Gửi lời mời';
            showInlineResult(resultEl, false, 'Lỗi kết nối.');
        });
}

function resetMoiSV() {
    const formEl = document.getElementById('sv-invite-form');
    const resultEl = document.getElementById('sv-invite-result');
    formEl.style.display = '';
    resultEl.style.display = 'none';
    document.getElementById('sv-select-inline').value = '';
    document.getElementById('sv-loinhan-inline').value = '';
    const btn = formEl.querySelector('button[type="button"]');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send me-1"></i>Gửi lời mời';
}

// ================================================================
// AJAX: MỜI GVHD (inline)
// ================================================================
function submitMoiGVInline() {
    const select   = document.getElementById('gv-select-inline');
    const loinhan  = document.getElementById('gv-loinhan-inline');
    const resultEl = document.getElementById('gv-invite-result');
    const formEl   = document.getElementById('gv-invite-form');

    if (!select || !select.value) {
        showInlineResult(resultEl, false, 'Vui lòng chọn giảng viên cần mời.');
        return;
    }

    const btn = formEl.querySelector('button[type="button"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang gửi...';

    const body = new URLSearchParams({
        ajax_action: 'moi_gvhd',
        idGV: select.value,
        loiNhan: loinhan?.value || ''
    });

    fetch(AJAX_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (data.status) {
                const tenGV = select.options[select.selectedIndex]?.text || '';
                formEl.style.display = 'none';
                showInlineResult(resultEl, true,
                    `<i class="bi bi-check-circle-fill me-2"></i>
                     <strong>Đã gửi lời mời GVHD!</strong><br>
                     <span class="small text-muted">Lời mời đã được gửi tới GV <strong>${escHtml(tenGV)}</strong>.</span>`
                );
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send me-1"></i>Gửi lời mời GVHD';
                showInlineResult(resultEl, false, data.message);
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send me-1"></i>Gửi lời mời GVHD';
            showInlineResult(resultEl, false, 'Lỗi kết nối.');
        });
}

function showInlineResult(el, success, html) {
    if (!el) return;
    el.style.display = '';
    el.innerHTML = `<div class="alert alert-${success ? 'success' : 'warning'} py-3 mb-0">${html}</div>`;
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ================================================================
// UPLOAD FILES
// ================================================================
let fileStore = [];

function handleFileSelect(input) {
    if (input.files.length) {
        Array.from(input.files).forEach(newFile => {
            const exists = fileStore.some(f => f.name === newFile.name && f.size === newFile.size);
            if (!exists) fileStore.push(newFile);
        });
        renderFileList();
    }
}

function removeFile(index) {
    fileStore.splice(index, 1);
    renderFileList();
}

function renderFileList() {
    const listEl  = document.getElementById('fileList-main');
    const itemsEl = document.getElementById('fileItems-main');
    if (!listEl || !itemsEl) return;

    if (!fileStore.length) { listEl.style.display = 'none'; itemsEl.innerHTML = ''; return; }
    listEl.style.display = '';
    itemsEl.innerHTML = '';

    fileStore.forEach((file, index) => {
        const ext = file.name.split('.').pop().toLowerCase();
        let iconCls = ['zip','rar'].includes(ext) ? 'bi-file-earmark-zip text-warning'
            : ext==='pdf' ? 'bi-file-earmark-pdf text-danger'
            : ['doc','docx'].includes(ext) ? 'bi-file-earmark-word text-primary'
            : 'bi-file-earmark text-secondary';
        const sz = file.size < 1048576 ? (file.size/1024).toFixed(1)+' KB' : (file.size/1048576).toFixed(1)+' MB';
        const div = document.createElement('div');
        div.className = 'd-flex align-items-center gap-2 p-2 mb-2 border rounded bg-white';
        div.innerHTML = `<i class="bi ${iconCls} fs-5 flex-shrink-0"></i>
            <span class="flex-grow-1 text-truncate small">${escHtml(file.name)}</span>
            <span class="text-muted small">${sz}</span>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="removeFile(${index})"><i class="bi bi-x"></i></button>`;
        itemsEl.appendChild(div);
    });
    syncToInput();
}

function syncToInput() {
    const input = document.getElementById('fileInput-main');
    if (!input) return;
    try {
        const dt = new DataTransfer();
        fileStore.forEach(f => dt.items.add(f));
        input.files = dt.files;
    } catch(e) {}
}

// Drag & drop
const dropzone = document.getElementById('dropzone-main');
if (dropzone) {
    dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
    dropzone.addEventListener('drop', e => {
        e.preventDefault(); dropzone.classList.remove('dragover');
        Array.from(e.dataTransfer.files).forEach(newFile => {
            const exists = fileStore.some(f => f.name===newFile.name && f.size===newFile.size);
            if (!exists) fileStore.push(newFile);
        });
        renderFileList();
    });
}

// ================================================================
// POPUP NỘP BÀI THÀNH CÔNG
// ================================================================
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
            <button class="btn btn-success px-4 me-2" onclick="switchToAllSubmissions()">
                <i class="bi bi-folder2-open me-2"></i>Xem bài nộp
            </button>
            <button class="btn btn-light px-4" onclick="this.closest('.position-fixed').remove()">Đóng</button>
        </div>`;
    document.body.appendChild(overlay);
    setTimeout(() => { if (overlay.parentNode) overlay.remove(); }, 8000);
}

function switchToAllSubmissions() {
    document.querySelector('.position-fixed')?.remove();
    const tabEl = document.querySelector('[data-bs-target="#subtab-tatcabainop"]');
    if (tabEl) bootstrap.Tab.getOrCreateInstance(tabEl).show();
}
</script>
