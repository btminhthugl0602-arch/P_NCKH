<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
global $conn;

require_once _PATH_URL . '/modules/functions/quan_ly_su_kien.php';

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

// ================== XỬ LÝ TẠO NHÓM ==================
if (isset($_POST['create_group'])) {

    if (!isset($_SESSION['user_id'])) {
        die("Bạn cần đăng nhập");
    }

    $userId  = (int)$_SESSION['user_id'];
    $tennhom = mysqli_real_escape_string($conn, trim($_POST['tennhom'] ?? ''));
    $mota    = mysqli_real_escape_string($conn, trim($_POST['mota'] ?? ''));

    if (!empty($tennhom)) {

        // 1. Tạo nhóm - gán idnhomtruong ngay
        mysqli_query($conn, "
            INSERT INTO nhom (idSK, idnhomtruong, ngaytao, isActive)
            VALUES ($id, $userId, NOW(), 1)
        ");
        $idNhom = mysqli_insert_id($conn);

        // 2. Thông tin nhóm (không có cột congkhai trong CSDL)
        mysqli_query($conn, "
            INSERT INTO thongtinnhom (idnhom, tennhom, mota, soluongtoida, dangtuyen)
            VALUES ($idNhom, '$tennhom', '$mota', 5, 1)
        ");

        // 3. Thêm nhóm trưởng vào thành viên (idvaitronhom=1 = nhóm trưởng, trangthai=1 = đã tham gia)
        mysqli_query($conn, "
            INSERT INTO thanhviennhom (idnhom, idtk, idvaitronhom, trangthai)
            VALUES ($idNhom, $userId, 1, 1)
        ");

        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// ================== LẤY DANH SÁCH TẤT CẢ NHÓM ==================
// nhom không có idTieuBan nên không JOIN tieuban
$sql_all = "
    SELECT  n.idnhom,
            t.tennhom,
            t.mota,
            t.soluongtoida,
            t.dangtuyen,
            COUNT(tv.idtk) AS soThanhVien,
            COALESCE(sv.tenSV, gv.tenGV, tk_truong.tenTK, '') AS tenNhomTruong
    FROM nhom n
    LEFT JOIN thongtinnhom    t        ON n.idnhom    = t.idnhom
    LEFT JOIN thanhviennhom   tv       ON n.idnhom    = tv.idnhom  AND tv.trangthai = 1
    LEFT JOIN taikhoan        tk_truong ON n.idnhomtruong = tk_truong.idTK
    LEFT JOIN sinhvien        sv       ON tk_truong.idTK = sv.idTK
    LEFT JOIN giangvien       gv       ON tk_truong.idTK = gv.idTK
    WHERE n.idSK = $id AND n.isActive = 1
    GROUP BY n.idnhom, t.tennhom, t.mota, t.soluongtoida, t.dangtuyen,
             tk_truong.tenTK, sv.tenSV, gv.tenGV
    ORDER BY n.ngaytao DESC
";
$result = mysqli_query($conn, $sql_all);
$groups = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

// ================== LẤY NHÓM CỦA USER HIỆN TẠI ==================
$myGroups = [];
if (!empty($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];

    $sql_my = "
        SELECT  n.idnhom,
                t.tennhom,
                t.mota,
                t.soluongtoida,
                t.dangtuyen,
                COUNT(tv2.idtk) AS soThanhVien,
                COALESCE(sv.tenSV, gv.tenGV, tk_truong.tenTK, '') AS tenNhomTruong,
                vn.tenvaitronhom AS vaiTroToi
        FROM nhom n
        JOIN  thanhviennhom   tv_me    ON n.idnhom    = tv_me.idnhom
                                      AND tv_me.idtk  = $userId
                                      AND tv_me.trangthai = 1
        LEFT JOIN thongtinnhom    t        ON n.idnhom    = t.idnhom
        LEFT JOIN thanhviennhom   tv2      ON n.idnhom    = tv2.idnhom  AND tv2.trangthai = 1
        LEFT JOIN taikhoan        tk_truong ON n.idnhomtruong = tk_truong.idTK
        LEFT JOIN sinhvien        sv       ON tk_truong.idTK = sv.idTK
        LEFT JOIN giangvien       gv       ON tk_truong.idTK = gv.idTK
        LEFT JOIN vaitronhom      vn       ON tv_me.idvaitronhom = vn.id
        WHERE n.idSK = $id AND n.isActive = 1
        GROUP BY n.idnhom, t.tennhom, t.mota, t.soluongtoida, t.dangtuyen,
                 tk_truong.tenTK, sv.tenSV, gv.tenGV, vn.tenvaitronhom
        ORDER BY n.ngaytao DESC
    ";
    $res_my  = mysqli_query($conn, $sql_my);
    $myGroups = $res_my ? mysqli_fetch_all($res_my, MYSQLI_ASSOC) : [];
}

// ================== RENDER ==================
$data = ['page_title' => htmlspecialchars($event['tenSK'])];
layout('header', $data);
layout('navbar');
?>
<main class="main">

    <!-- Page Title -->
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
    </div><!-- End Page Title -->

    <!-- Course Details Section -->
    <section id="course-details" class="course-details section">

        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <div class="row">
                <div class="col-lg-8">

                    <!-- Course Hero -->
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
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i>
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
                    </div><!-- End Course Hero -->

                    <!-- Tabs điều hướng sự kiện -->
                    <div class="course-nav-tabs" data-aos="fade-up" data-aos-delay="300">
                        <ul class="nav nav-tabs" id="EventDetails" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#event-info"
                                    type="button" role="tab">
                                    <i class="bi bi-layout-text-window-reverse"></i> Thông tin
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#event-groups"
                                    type="button" role="tab">
                                    <i class="bi bi-list-ul"></i> Nhóm thi
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#event-config"
                                    type="button" role="tab">
                                    <i class="bi bi-gear"></i> Cấu hình sự kiện
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="EventDetailsContent">

                            <!-- ===== Tab: Thông tin ===== -->
                            <div class="tab-pane fade show active" id="event-info" role="tabpanel">
                                <div class="overview-section">
                                    <h3>Chi tiết sự kiện</h3>
                                    <p><?= nl2br(htmlspecialchars($event['moTa'])) ?></p>
                                </div>

                                <!-- Thời gian -->
                                <div class="requirements-section mt-4">
                                    <h3>Thông tin thời gian</h3>
                                    <ul class="requirements-list">
                                        <li>
                                            <i class="bi bi-check2"></i>
                                            <strong>Mở đăng ký:</strong>
                                            <?= $event['ngayMoDangKy'] ? date('d/m/Y H:i', strtotime($event['ngayMoDangKy'])) : '—' ?>
                                        </li>
                                        <li>
                                            <i class="bi bi-check2"></i>
                                            <strong>Đóng đăng ký:</strong>
                                            <?= $event['ngayDongDangKy'] ? date('d/m/Y H:i', strtotime($event['ngayDongDangKy'])) : '—' ?>
                                        </li>
                                        <li>
                                            <i class="bi bi-check2"></i>
                                            <strong>Bắt đầu:</strong>
                                            <?= $event['ngayBatDau'] ? date('d/m/Y H:i', strtotime($event['ngayBatDau'])) : '—' ?>
                                        </li>
                                        <li>
                                            <i class="bi bi-check2"></i>
                                            <strong>Kết thúc:</strong>
                                            <?= $event['ngayKetThuc'] ? date('d/m/Y H:i', strtotime($event['ngayKetThuc'])) : '—' ?>
                                        </li>
                                    </ul>
                                </div>
                            </div><!-- End Thông tin Tab -->

                            <!-- ===== Tab: Nhóm thi ===== -->
                            <div class="tab-pane fade" id="event-groups" role="tabpanel">
                                <div class="course-nav-tabs mt-3">
                                    <ul class="nav nav-tabs" id="GroupTabs" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link active" data-bs-toggle="tab"
                                                data-bs-target="#all-groups" type="button" role="tab">
                                                <i class="bi bi-grid"></i> Tất cả nhóm
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#my-groups"
                                                type="button" role="tab">
                                                <i class="bi bi-person-check"></i> Nhóm của tôi
                                                <?php if (!empty($myGroups)): ?>
                                                    <span class="badge bg-primary ms-1"><?= count($myGroups) ?></span>
                                                <?php endif; ?>
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content" id="GroupTabsContent">

                                        <!-- Tất cả nhóm -->
                                        <div class="tab-pane fade show active" id="all-groups" role="tabpanel">
                                            <section class="courses-2 section">
                                                <div class="container" data-aos="fade-up" data-aos-delay="100">
                                                    <div class="row">
                                                        <div class="col-lg-12">

                                                            <!-- Header tìm kiếm -->
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
                                                                            $isFull    = $g['soThanhVien'] >= $g['soluongtoida'];
                                                                            $isDangTuyen = (bool)$g['dangtuyen'];
                                                                            ?>
                                                                            <div class="col-lg-6 col-md-6 group-item"
                                                                                data-tuyen="<?= $isDangTuyen ? '1' : '0' ?>"
                                                                                data-ten="<?= htmlspecialchars(strtolower($g['tennhom'])) ?>">
                                                                                <div class="course-card">
                                                                                    <div class="course-image">
                                                                                        <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/education/courses-3.webp"
                                                                                            alt="Nhóm" class="img-fluid">
                                                                                        <?php if ($isFull): ?>
                                                                                            <div class="course-badge">Đã đủ thành
                                                                                                viên</div>
                                                                                        <?php elseif ($isDangTuyen): ?>
                                                                                            <div class="course-badge badge-free">
                                                                                                Đang tuyển</div>
                                                                                        <?php endif; ?>
                                                                                        <div class="course-price">
                                                                                            <?= $g['soThanhVien'] ?>/<?= $g['soluongtoida'] ?>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="course-content">
                                                                                        <h3><?= htmlspecialchars($g['tennhom']) ?>
                                                                                        </h3>
                                                                                        <p class="text-muted small mb-1">
                                                                                            <?= htmlspecialchars($g['mota'] ?: 'Chưa có mô tả') ?>
                                                                                        </p>
                                                                                        <div class="course-stats">
                                                                                            <div class="stat">
                                                                                                <i class="bi bi-people"></i>
                                                                                                <span><?= $g['soThanhVien'] ?>/<?= $g['soluongtoida'] ?>
                                                                                                    thành viên</span>
                                                                                            </div>
                                                                                        </div>
                                                                                        <?php if (!empty($g['tenNhomTruong'])): ?>
                                                                                            <div class="instructor-info mt-2">
                                                                                                <span class="instructor-name">
                                                                                                    <i
                                                                                                        class="bi bi-person-badge me-1"></i>
                                                                                                    Nhóm trưởng:
                                                                                                    <?= htmlspecialchars($g['tenNhomTruong']) ?>
                                                                                                </span>
                                                                                            </div>
                                                                                        <?php endif; ?>
                                                                                        <?php if ($isDangTuyen && !$isFull): ?>
                                                                                            <a href="<?= _HOST_URL ?>?module=group&action=join&id=<?= $g['idnhom'] ?>"
                                                                                                class="btn-course mt-2">
                                                                                                Xin vào nhóm
                                                                                            </a>
                                                                                        <?php else: ?>
                                                                                            <span class="btn-course mt-2 disabled"
                                                                                                style="opacity:.6;cursor:not-allowed">
                                                                                                Đã đủ thành viên
                                                                                            </span>
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    <?php else: ?>
                                                                        <div class="col-12 text-center py-5 text-muted">
                                                                            <i class="bi bi-people fs-2 d-block mb-2"></i>
                                                                            Chưa có nhóm nào trong sự kiện này.
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div><!-- End Courses Grid -->

                                                        </div>
                                                    </div>
                                                </div>
                                            </section>
                                        </div><!-- End Tất cả nhóm -->

                                        <!-- Nhóm của tôi -->
                                        <div class="tab-pane fade" id="my-groups" role="tabpanel">
                                            <section class="courses-2 section">
                                                <div class="container" data-aos="fade-up" data-aos-delay="100">
                                                    <div class="row">
                                                        <div class="col-lg-12">

                                                            <!-- Header tìm kiếm + nút tạo nhóm -->
                                                            <div class="courses-header d-flex justify-content-between align-items-center"
                                                                data-aos="fade-left" data-aos-delay="100">
                                                                <div class="search-box">
                                                                    <i class="bi bi-search"></i>
                                                                    <input type="text"
                                                                        placeholder="Tìm kiếm nhóm của tôi...">
                                                                </div>
                                                                <button class="btn btn-primary" data-bs-toggle="modal"
                                                                    data-bs-target="#createGroupModal">
                                                                    <i class="bi bi-plus-circle me-1"></i>Tạo nhóm
                                                                </button>
                                                            </div>

                                                            <div class="courses-grid" data-aos="fade-up"
                                                                data-aos-delay="200">
                                                                <div class="row">
                                                                    <?php if (!empty($myGroups)): ?>
                                                                        <?php foreach ($myGroups as $g): ?>
                                                                            <?php $isFull = $g['soThanhVien'] >= $g['soluongtoida']; ?>
                                                                            <div class="col-lg-6 col-md-6">
                                                                                <div class="course-card">
                                                                                    <div class="course-image">
                                                                                        <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/education/courses-7.webp"
                                                                                            alt="Nhóm" class="img-fluid">
                                                                                        <?php if ($isFull): ?>
                                                                                            <div class="course-badge">Đã đủ thành
                                                                                                viên</div>
                                                                                        <?php else: ?>
                                                                                            <div class="course-badge badge-free">
                                                                                                Đang tuyển</div>
                                                                                        <?php endif; ?>
                                                                                        <div class="course-price">
                                                                                            <?= $g['soThanhVien'] ?>/<?= $g['soluongtoida'] ?>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="course-content">
                                                                                        <h3><?= htmlspecialchars($g['tennhom']) ?>
                                                                                        </h3>
                                                                                        <p class="text-muted small mb-1">
                                                                                            <?= htmlspecialchars($g['mota'] ?: 'Chưa có mô tả') ?>
                                                                                        </p>
                                                                                        <div class="course-stats">
                                                                                            <div class="stat">
                                                                                                <i class="bi bi-people"></i>
                                                                                                <span><?= $g['soThanhVien'] ?>/<?= $g['soluongtoida'] ?>
                                                                                                    thành viên</span>
                                                                                            </div>
                                                                                        </div>
                                                                                        <?php if (!empty($g['vaiTroToi'])): ?>
                                                                                            <div class="mt-1 mb-2">
                                                                                                <span class="badge bg-primary">
                                                                                                    <i
                                                                                                        class="bi bi-shield-check me-1"></i>
                                                                                                    <?= htmlspecialchars($g['vaiTroToi']) ?>
                                                                                                </span>
                                                                                            </div>
                                                                                        <?php endif; ?>
                                                                                        <a href="<?= _HOST_URL ?>?module=group&action=view&id=<?= $g['idnhom'] ?>"
                                                                                            class="btn-course mt-2">
                                                                                            Xem nhóm
                                                                                        </a>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    <?php else: ?>
                                                                        <div class="col-12 text-center py-5 text-muted">
                                                                            <i class="bi bi-person-x fs-2 d-block mb-2"></i>
                                                                            Bạn chưa tham gia nhóm nào trong sự kiện này.
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div><!-- End Courses Grid -->

                                                        </div>
                                                    </div>
                                                </div>
                                            </section>
                                        </div><!-- End Nhóm của tôi -->

                                    </div><!-- End GroupTabsContent -->
                                </div><!-- End course-nav-tabs inner -->
                            </div><!-- End event-groups tab-pane -->

                            <!-- ===== Tab: Cấu hình sự kiện ===== -->
                            <div class="tab-pane fade" id="event-config" role="tabpanel">
                                <div class="event-config-content" data-aos="fade-up" data-aos-delay="100">
                                    <h3>Cấu hình sự kiện</h3>
                                    <p>Chọn khu vực cấu hình phù hợp để thiết lập quy chế, vòng thi và bộ tiêu chí.</p>
                                    <div class="d-flex flex-column gap-2 mt-3">
                                        <a class="btn btn-primary"
                                            href="<?= _HOST_URL ?>/?module=event&action=config_rounds&id=<?= $id ?>">
                                            <i class="bi bi-sliders me-2"></i>Cấu hình cơ bản
                                        </a>
                                        <a class="btn btn-primary"
                                            href="<?= _HOST_URL ?>/?module=event&action=config_rules&id=<?= $id ?>">
                                            <i class="bi bi-journal-text me-2"></i>Quy chế &amp; Điều kiện
                                        </a>
                                        <a class="btn btn-primary"
                                            href="<?= _HOST_URL ?>/?module=event&action=config_criteria&id=<?= $id ?>">
                                            <i class="bi bi-clipboard-check me-2"></i>Bộ tiêu chí &amp; Chấm điểm
                                        </a>
                                        <a class="btn btn-primary"
                                            href="<?= _HOST_URL ?>/?module=event&action=config_assign&id=<?= $id ?>">
                                            <i class="bi bi-person-lines-fill me-2"></i>Phân công chấm
                                        </a>
                                        <a class="btn btn-primary"
                                            href="<?= _HOST_URL ?>/?module=event&action=config_schedule&id=<?= $id ?>">
                                            <i class="bi bi-calendar3 me-2"></i>Lập lịch tổ chức
                                        </a>
                                    </div>
                                </div>
                            </div><!-- End Cấu hình Tab -->

                        </div><!-- End EventDetailsContent -->
                    </div><!-- End course-nav-tabs -->

                </div><!-- End col-lg-8 -->

                <!-- ===== Sidebar ===== -->
                <div class="col-lg-4">

                    <!-- Course Details Card -->
                    <div class="course-details-card" data-aos="fade-up" data-aos-delay="300">
                        <h4>Thông tin sự kiện</h4>
                        <div class="detail-grid">
                            <div class="detail-row">
                                <span class="detail-label">Cấp tổ chức</span>
                                <span class="detail-value"><?= htmlspecialchars($event['tenCap'] ?? '—') ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Mở đăng ký</span>
                                <span class="detail-value">
                                    <?= $event['ngayMoDangKy'] ? date('d/m/Y', strtotime($event['ngayMoDangKy'])) : '—' ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Đóng đăng ký</span>
                                <span class="detail-value">
                                    <?= $event['ngayDongDangKy'] ? date('d/m/Y', strtotime($event['ngayDongDangKy'])) : '—' ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Ngày bắt đầu</span>
                                <span class="detail-value">
                                    <?= $event['ngayBatDau'] ? date('d/m/Y', strtotime($event['ngayBatDau'])) : '—' ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Ngày kết thúc</span>
                                <span class="detail-value">
                                    <?= $event['ngayKetThuc'] ? date('d/m/Y', strtotime($event['ngayKetThuc'])) : '—' ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Tổng số nhóm</span>
                                <span class="detail-value"><?= count($groups) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Trạng thái</span>
                                <span class="detail-value">
                                    <?php
                                    $now = time();
                                    $start = $event['ngayBatDau'] ? strtotime($event['ngayBatDau']) : null;
                                    $end   = $event['ngayKetThuc'] ? strtotime($event['ngayKetThuc']) : null;
                                    if (!$start) {
                                        echo '<span class="badge bg-secondary">Chưa có lịch</span>';
                                    } elseif ($start > $now) {
                                        echo '<span class="badge bg-info text-dark">Sắp diễn ra</span>';
                                    } elseif (!$end || $end >= $now) {
                                        echo '<span class="badge bg-success">Đang diễn ra</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">Đã kết thúc</span>';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div><!-- End Course Details Card -->

                    <!-- Share Card -->
                    <div class="share-course-card mt-4" data-aos="fade-up" data-aos-delay="400">
                        <h4>Chia sẻ sự kiện</h4>
                        <div class="social-links">
                            <a href="#" class="social-link facebook"><i class="bi bi-facebook"></i></a>
                            <a href="#" class="social-link twitter"><i class="bi bi-twitter"></i></a>
                            <a href="#" class="social-link linkedin"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="social-link email"><i class="bi bi-envelope"></i></a>
                        </div>
                    </div><!-- End Share Card -->

                </div><!-- End col-lg-4 -->

            </div><!-- End row -->

        </div><!-- End container -->

    </section><!-- /Course Details Section -->

</main>

<!-- ================= MODAL TẠO NHÓM ================= -->
<div class="modal fade" id="createGroupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tạo nhóm mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên nhóm <span class="text-danger">*</span></label>
                        <input type="text" name="tennhom" class="form-control" required placeholder="Nhập tên nhóm...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả nhóm</label>
                        <textarea name="mota" class="form-control" rows="3"
                            placeholder="Mô tả ngắn về nhóm..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                    <button type="submit" name="create_group" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Tạo nhóm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php layout('footer'); ?>

<script>
    // Tìm kiếm nhóm realtime
    document.getElementById('search-all-groups')?.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#all-groups-grid .group-item').forEach(el => {
            el.style.display = el.dataset.ten.includes(q) ? '' : 'none';
        });
    });

    // Lọc theo trạng thái tuyển
    document.getElementById('sort-all-groups')?.addEventListener('change', function() {
        const val = this.value;
        document.querySelectorAll('#all-groups-grid .group-item').forEach(el => {
            if (!val) {
                el.style.display = '';
            } else if (val === 'dang_tuyen') {
                el.style.display = el.dataset.tuyen === '1' ? '' : 'none';
            } else if (val === 'day') {
                el.style.display = el.dataset.tuyen === '0' ? '' : 'none';
            }
        });
    });
</script>