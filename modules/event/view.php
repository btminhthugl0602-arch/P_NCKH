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
    $tennhom = trim($_POST['tennhom'] ?? '');
    $mota    = trim($_POST['mota'] ?? '');
    $soluongtoida = max(1, (int)($_POST['soluongtoida'] ?? 5));
    if (!empty($tennhom)) {
        $result = tao_nhom_moi($conn, $userId, $id, $tennhom, $mota);
        if ($result['status']) {
            $_SESSION['flash_msg']  = 'Tạo nhóm thành công!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_msg']  = $result['message'];
            $_SESSION['flash_type'] = 'danger';
        }
        header("Location: " . $_SERVER['REQUEST_URI']); exit();
    }
}

// ================== XỬ LÝ XIN VÀO NHÓM (từ tất cả nhóm) ==================
if (isset($_POST['xin_vao_nhom']) && $userId > 0) {
    $idNhomXin = (int)($_POST['idNhom'] ?? 0);
    $loiNhanXin = trim($_POST['loiNhan'] ?? '');
    if ($idNhomXin > 0) {
        // ChieuMoi=1 = người dùng xin vào nhóm (nhóm trưởng duyệt)
        $result = gui_yeu_cau_nhom($conn, $idNhomXin, $userId, 1, $loiNhanXin);
        $_SESSION['flash_msg']  = $result['status']
            ? 'Đã gửi yêu cầu tham gia nhóm thành công! Chờ trưởng nhóm duyệt.'
            : $result['message'];
        $_SESSION['flash_type'] = $result['status'] ? 'success' : 'warning';
    }
    header("Location: " . $_SERVER['REQUEST_URI']); exit();
}

// ================== LẤY TẤT CẢ NHÓM ==================
$sql_all = "
    SELECT n.idnhom, t.tennhom, t.mota, t.soluongtoida, t.dangtuyen,
           COUNT(CASE WHEN tv.idvaitronhom != 3 THEN tv.idtk END) AS soThanhVien,
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

// Lấy danh sách nhóm mà user đã là thành viên hoặc đã xin
$myGroupIds = [];
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
    $sql_my = "
        SELECT n.idnhom, t.tennhom, t.mota, t.soluongtoida, t.dangtuyen,
               COUNT(CASE WHEN tv2.idvaitronhom != 3 THEN tv2.idtk END) AS soThanhVien,
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
        $res_sp = mysqli_query($conn,
            "SELECT sp.*, l.loaitailieu AS tenLoaiTL
            FROM sanpham sp
            LEFT JOIN loaitailieu l ON sp.idloaitailieu = l.idtailieu
            WHERE sp.idNhom = $gId AND sp.idSK = $id
            ORDER BY sp.idloaitailieu ASC");
        $spRows = $res_sp ? mysqli_fetch_all($res_sp, MYSQLI_ASSOC) : [];
        $g['sanPhamTheoLoai'] = [];
        foreach ($spRows as $row) {
            $g['sanPhamTheoLoai'][$row['idloaitailieu']] = $row;
        }
        $g['sanPham'] = !empty($spRows) ? $spRows[0] : null;

        // GVHD
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
    $res_lm = mysqli_query($conn, "
        SELECT yc.idYeuCau, yc.idNhom, yc.loiNhan, yc.ngayGui, yc.ChieuMoi,
               t.tennhom, t.mota, t.soluongtoida,
               COUNT(CASE WHEN tv.idvaitronhom != 3 THEN tv.idtk END) AS soThanhVien,
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

.nhom-detai { font-size:13px; color:#555; margin-bottom:12px; }

.nhom-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
.btn-nhom { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:8px; font-size:13px; font-weight:600; border:none; cursor:pointer; transition:opacity .2s,transform .1s; text-decoration:none; }
.btn-nhom:hover { opacity:.88; transform:translateY(-1px); }
.btn-nhom-view   { background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; }

/* Modal header gradient */
.modal-header-grad { background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; border-radius:12px 12px 0 0; padding:16px 20px; }
.modal-header-grad .btn-close { filter:brightness(0) invert(1); }
.modal-content { border-radius:12px; border:none; }

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

.lm-loinhan { background:#faf5ff; border-left:3px solid #7c3aed; border-radius:0 8px 8px 0; padding:8px 12px; font-size:13px; color:#5b21b6; font-style:italic; margin-bottom:8px; }
.lm-time { font-size:12px; color:#9ca3af; margin-bottom:14px; }
.lm-actions { display:flex; gap:8px; }
.btn-lm { display:inline-flex; align-items:center; gap:5px; padding:8px 18px; border-radius:8px; font-size:13px; font-weight:600; border:none; cursor:pointer; transition:opacity .2s, transform .1s; flex:1; justify-content:center; }
.btn-lm:hover { opacity:.88; transform:translateY(-1px); }
.btn-lm-accept { background:linear-gradient(135deg,#16a34a,#15803d); color:#fff; }
.btn-lm-decline { background:#fff; color:#dc2626; border:2px solid #fca5a5; }

/* Join request modal */
.join-modal-info { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 14px; font-size:13px; color:#1e40af; margin-bottom:16px; }

/* Status badges for join button */
.btn-pending { background:#f3f4f6; color:#6b7280; border:1px solid #d1d5db; border-radius:8px; padding:6px 14px; font-size:13px; font-weight:600; cursor:not-allowed; display:inline-flex; align-items:center; gap:5px; }
.btn-member  { background:#e6f9ee; color:#16a34a; border:1px solid #bbf7d0; border-radius:8px; padding:6px 14px; font-size:13px; font-weight:600; cursor:not-allowed; display:inline-flex; align-items:center; gap:5px; }
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
                                                <span class="badge bg-secondary ms-1"><?= count($groups) ?></span>
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

                                        <!-- ===== TẤT CẢ NHÓM ===== -->
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
                                                                        <?php 
                                                                        $isFull = $g['soluongtoida'] > 0 && $g['soThanhVien'] >= $g['soluongtoida'];
                                                                        $isMyGroup = in_array($g['idnhom'], $myGroupIds);
                                                                        $isPending = in_array($g['idnhom'], $pendingGroupIds);
                                                                        ?>
                                                                        <div class="col-lg-6 col-md-6 group-item"
                                                                            data-tuyen="<?= $g['dangtuyen'] ? '1':'0' ?>"
                                                                            data-ten="<?= htmlspecialchars(strtolower($g['tennhom'] ?? '')) ?>">
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
                                                                                    <h3><?= htmlspecialchars($g['tennhom'] ?? '') ?></h3>
                                                                                    <p class="text-muted small mb-1"><?= htmlspecialchars($g['mota'] ?? 'Chưa có mô tả') ?></p>
                                                                                    <div class="course-stats">
                                                                                        <div class="stat"><i class="bi bi-people"></i><span><?= $g['soThanhVien'] ?>/<?= $g['soluongtoida'] ?> thành viên</span></div>
                                                                                    </div>
                                                                                    <?php if (!empty($g['tenNhomTruong'])): ?>
                                                                                        <div class="instructor-info mt-2">
                                                                                            <span class="instructor-name"><i class="bi bi-person-badge me-1"></i>Nhóm trưởng: <?= htmlspecialchars($g['tenNhomTruong']) ?></span>
                                                                                        </div>
                                                                                    <?php endif; ?>

                                                                                    <div class="mt-2 d-flex gap-2 flex-wrap">
                                                                                        <!-- Nút xem chi tiết nhóm -->
                                                                                        <a href="<?= _HOST_URL ?>?module=event&action=chitiethom&id=<?= $g['idnhom'] ?>" 
                                                                                           class="btn-course">
                                                                                            <i class="bi bi-eye me-1"></i>Xem nhóm
                                                                                        </a>

                                                                                        <?php if ($isMyGroup): ?>
                                                                                            <span class="btn-member"><i class="bi bi-check-circle-fill"></i> Đã tham gia</span>
                                                                                        <?php elseif ($isPending): ?>
                                                                                            <span class="btn-pending"><i class="bi bi-clock-history"></i> Đang chờ duyệt</span>
                                                                                        <?php elseif (!$isFull && $g['dangtuyen'] && $userId > 0): ?>
                                                                                            <button type="button" class="btn-course btn-outline-primary"
                                                                                                style="background:transparent;color:#4f46e5;border:2px solid #4f46e5;"
                                                                                                onclick="openJoinModal(<?= $g['idnhom'] ?>, '<?= htmlspecialchars(addslashes($g['tennhom'])) ?>')">
                                                                                                <i class="bi bi-person-plus me-1"></i>Xin vào nhóm
                                                                                            </button>
                                                                                        <?php elseif ($isFull): ?>
                                                                                            <span class="btn-course disabled" style="opacity:.6;cursor:not-allowed">Đã đủ thành viên</span>
                                                                                        <?php endif; ?>
                                                                                    </div>
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
                                                <?php foreach ($myGroups as $g): ?>
                                                    <?php $gId = $g['idnhom']; ?>
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

                                                            <!-- GVHD -->
                                                            <?php if ($g['gvhd']): ?>
                                                                <div class="gvhd-row">
                                                                    <i class="bi bi-person-workspace"></i>
                                                                    GVHD: <?= htmlspecialchars($g['gvhd']['tenGVHD']) ?>
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

                                                            <!-- Action: chỉ 1 nút Xem nhóm -->
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
                                        </div>

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
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số thành viên tối đa <span class="text-danger">*</span></label>
                        <input type="number" name="soluongtoida" class="form-control" required min="1" max="20" placeholder="Ví dụ: 5" value="5">
                        <div class="form-text">Số lượng thành viên tối đa (không tính GVHD).</div>
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

<!-- MODAL XIN VÀO NHÓM -->
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
                        <textarea name="loiNhan" class="form-control" rows="3" placeholder="Giới thiệu bản thân và lý do muốn tham gia nhóm..."></textarea>
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

<?php layout('footer'); ?>

<script>
const AJAX_URL = window.location.href;

// Mở modal xin vào nhóm
function openJoinModal(idNhom, tenNhom) {
    document.getElementById('joinGroupId').value = idNhom;
    document.getElementById('joinGroupName').textContent = tenNhom;
    new bootstrap.Modal(document.getElementById('joinGroupModal')).show();
}

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

// ================================================================
// AJAX: PHẢN HỒI LỜI MỜI NHÓM
// ================================================================
function phanHoiLoiMoi(idYeuCau, trangThai, tenNhom) {
    const actionsEl = document.getElementById('lm-actions-' + idYeuCau);
    const resultEl  = document.getElementById('lm-result-'  + idYeuCau);
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
                        <div><strong>Đã tham gia nhóm!</strong><br>
                        <span class="small text-muted">Bạn đã chấp nhận lời mời vào nhóm <strong>${escHtml(tenNhom)}</strong>.</span></div>
                    </div>`;
                updateLoiMoiBadge(-1);
            } else if (data.status && trangThai == 2) {
                resultEl.innerHTML = `
                    <div class="alert alert-secondary py-2 mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-x-circle fs-5"></i>
                        <div><strong>Đã từ chối.</strong><br>
                        <span class="small text-muted">Bạn đã từ chối lời mời từ nhóm <strong>${escHtml(tenNhom)}</strong>.</span></div>
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
        });
}

function updateLoiMoiBadge(delta) {
    const tabBtn = document.querySelector('[data-bs-target="#loi-moi"]');
    if (!tabBtn) return;
    let badge = tabBtn.querySelector('.badge');
    const current = badge ? parseInt(badge.textContent) : 0;
    const newVal  = current + delta;
    if (newVal <= 0) { if (badge) badge.remove(); }
    else {
        if (!badge) { badge = document.createElement('span'); badge.className = 'badge bg-danger ms-1'; tabBtn.appendChild(badge); }
        badge.textContent = newVal;
    }
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>