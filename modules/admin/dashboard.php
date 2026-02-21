<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

// =============================================
// THỐNG KÊ TỔNG HỢP
// =============================================
$now = date('Y-m-d H:i:s');

// Người dùng
$total_users    = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM taikhoan"))['c'] ?? 0);
$total_sinhvien = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM sinhvien"))['c'] ?? 0);
$total_giangvien = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM giangvien"))['c'] ?? 0);

// Sự kiện
$total_sk = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM sukien WHERE isActive = 1"))['c'] ?? 0);

$total_sk_sap_toi = (int)(mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) as c FROM sukien WHERE isActive = 1 AND ngayBatDau > '$now'"
))['c'] ?? 0);

$total_sk_dang_dien = (int)(mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) as c FROM sukien WHERE isActive = 1 AND ngayBatDau <= '$now' AND (ngayKetThuc IS NULL OR ngayKetThuc >= '$now')"
))['c'] ?? 0);

$total_sk_ket_thuc = (int)(mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) as c FROM sukien WHERE isActive = 1 AND ngayKetThuc IS NOT NULL AND ngayKetThuc < '$now'"
))['c'] ?? 0);

// Lấy danh sách sự kiện gần đây
$sql_recent_sk = "
    SELECT sk.tenSK, sk.ngayBatDau, sk.ngayKetThuc, sk.isActive,
           ct.tenCap, tk.tenTK as nguoiTaoTen
    FROM sukien sk
    LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
    LEFT JOIN taikhoan tk ON sk.nguoiTao = tk.idTK
    WHERE sk.isActive = 1
    ORDER BY sk.idSK DESC
    LIMIT 5
";
$result_sk = mysqli_query($conn, $sql_recent_sk);
$recent_sk = [];
while ($row = mysqli_fetch_assoc($result_sk)) $recent_sk[] = $row;

// Tài khoản mới đăng ký
$sql_recent_tk = "
    SELECT tk.idTK, tk.tenTK, tk.idLoaiTK, tk.ngayTao, ltk.tenLoaiTK,
           COALESCE(sv.tenSV, gv.tenGV, '') AS hoTen
    FROM taikhoan tk
    LEFT JOIN loaitaikhoan ltk ON tk.idLoaiTK = ltk.idLoaiTK
    LEFT JOIN sinhvien sv ON tk.idTK = sv.idTK
    LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
    ORDER BY tk.ngayTao DESC
    LIMIT 5
";
$result_tk = mysqli_query($conn, $sql_recent_tk);
$recent_tk = [];
while ($row = mysqli_fetch_assoc($result_tk)) $recent_tk[] = $row;

$data = ['page_title' => 'Dashboard - Thống kê'];
layout('header', $data);
layout('navbar');
?>

<main class="main">

    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Dashboard</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?php echo _HOST_URL; ?>">Trang chủ</a></li>
                    <li class="current">Dashboard</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- ===== THỐNG KÊ NGƯỜI DÙNG ===== -->
    <section class="section light-background">
        <div class="container" data-aos="fade-up">

            <div class="container section-title" data-aos="fade-up">
                <h2>Thống kê người dùng</h2>
                <p>Tổng quan về tài khoản trong hệ thống</p>
            </div>

            <div class="row gy-4">

                <!-- Tổng người dùng -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden">
                        <div class="card-body p-0">
                            <div class="d-flex">
                                <div class="d-flex align-items-center justify-content-center bg-primary"
                                    style="width:90px;min-height:100px">
                                    <i class="bi bi-people-fill text-white" style="font-size:2.5rem"></i>
                                </div>
                                <div class="p-4 flex-grow-1">
                                    <div class="text-muted small text-uppercase fw-semibold mb-1">Tổng người dùng</div>
                                    <div class="fw-bold text-primary" style="font-size:2.2rem;line-height:1">
                                        <span class="purecounter" data-purecounter-start="0"
                                            data-purecounter-end="<?= $total_users ?>"
                                            data-purecounter-duration="1"><?= $total_users ?></span>
                                    </div>
                                    <div class="text-muted small mt-1">Tài khoản trong hệ thống</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tổng sinh viên -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden">
                        <div class="card-body p-0">
                            <div class="d-flex">
                                <div class="d-flex align-items-center justify-content-center bg-success"
                                    style="width:90px;min-height:100px">
                                    <i class="bi bi-mortarboard-fill text-white" style="font-size:2.5rem"></i>
                                </div>
                                <div class="p-4 flex-grow-1">
                                    <div class="text-muted small text-uppercase fw-semibold mb-1">Sinh viên</div>
                                    <div class="fw-bold text-success" style="font-size:2.2rem;line-height:1">
                                        <span class="purecounter" data-purecounter-start="0"
                                            data-purecounter-end="<?= $total_sinhvien ?>"
                                            data-purecounter-duration="1"><?= $total_sinhvien ?></span>
                                    </div>
                                    <div class="text-muted small mt-1">Sinh viên đã đăng ký</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tổng giảng viên -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden">
                        <div class="card-body p-0">
                            <div class="d-flex">
                                <div class="d-flex align-items-center justify-content-center bg-warning"
                                    style="width:90px;min-height:100px">
                                    <i class="bi bi-person-workspace text-white" style="font-size:2.5rem"></i>
                                </div>
                                <div class="p-4 flex-grow-1">
                                    <div class="text-muted small text-uppercase fw-semibold mb-1">Giảng viên</div>
                                    <div class="fw-bold text-warning" style="font-size:2.2rem;line-height:1">
                                        <span class="purecounter" data-purecounter-start="0"
                                            data-purecounter-end="<?= $total_giangvien ?>"
                                            data-purecounter-duration="1"><?= $total_giangvien ?></span>
                                    </div>
                                    <div class="text-muted small mt-1">Giảng viên tham gia</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===== THỐNG KÊ SỰ KIỆN (kiểu Course Categories) ===== -->
    <section class="section">
        <div class="container" data-aos="fade-up">

            <div class="container section-title" data-aos="fade-up">
                <h2>Thống kê sự kiện</h2>
                <p>Tổng quan tình trạng các sự kiện đang diễn ra trong hệ thống</p>
            </div>

            <div class="row gy-4">

                <!-- Tổng sự kiện -->
                <div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <a href="<?= _HOST_URL ?>/?module=event&action=index" class="text-decoration-none">
                        <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 category-card position-relative overflow-hidden"
                            style="transition:.3s">
                            <div class="position-absolute top-0 start-0 w-100 h-2"
                                style="height:4px;background:linear-gradient(90deg,#4154f1,#717ff5)"></div>
                            <div class="mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10"
                                style="width:72px;height:72px">
                                <i class="bi bi-calendar-event-fill text-primary" style="font-size:1.8rem"></i>
                            </div>
                            <h3 class="fw-bold text-primary mb-1" style="font-size:2.5rem">
                                <span class="purecounter" data-purecounter-start="0"
                                    data-purecounter-end="<?= $total_sk ?>"
                                    data-purecounter-duration="1"><?= $total_sk ?></span>
                            </h3>
                            <div class="fw-semibold text-dark mb-1">Tổng sự kiện</div>
                            <div class="text-muted small">Tất cả sự kiện đang hiển thị</div>
                        </div>
                    </a>
                </div>

                <!-- Sự kiện sắp tới -->
                <div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <a href="<?= _HOST_URL ?>/?module=event&action=index&filter_time=week" class="text-decoration-none">
                        <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 position-relative overflow-hidden"
                            style="transition:.3s">
                            <div class="position-absolute top-0 start-0 w-100"
                                style="height:4px;background:linear-gradient(90deg,#0ea5e9,#38bdf8)"></div>
                            <div class="mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center bg-info bg-opacity-10"
                                style="width:72px;height:72px">
                                <i class="bi bi-calendar-plus-fill text-info" style="font-size:1.8rem"></i>
                            </div>
                            <h3 class="fw-bold text-info mb-1" style="font-size:2.5rem">
                                <span class="purecounter" data-purecounter-start="0"
                                    data-purecounter-end="<?= $total_sk_sap_toi ?>"
                                    data-purecounter-duration="1"><?= $total_sk_sap_toi ?></span>
                            </h3>
                            <div class="fw-semibold text-dark mb-1">Sự kiện sắp tới</div>
                            <div class="text-muted small">Chưa bắt đầu diễn ra</div>
                        </div>
                    </a>
                </div>

                <!-- Sự kiện đang diễn ra -->
                <div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 position-relative overflow-hidden"
                        style="transition:.3s">
                        <div class="position-absolute top-0 start-0 w-100"
                            style="height:4px;background:linear-gradient(90deg,#22c55e,#4ade80)"></div>
                        <div class="mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center bg-success bg-opacity-10"
                            style="width:72px;height:72px">
                            <i class="bi bi-play-circle-fill text-success" style="font-size:1.8rem"></i>
                        </div>
                        <?php if ($total_sk_dang_dien > 0): ?>
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-success rounded-pill px-2">
                                    <span class="pulse-dot me-1"></span>Live
                                </span>
                            </div>
                        <?php endif; ?>
                        <h3 class="fw-bold text-success mb-1" style="font-size:2.5rem">
                            <span class="purecounter" data-purecounter-start="0"
                                data-purecounter-end="<?= $total_sk_dang_dien ?>"
                                data-purecounter-duration="1"><?= $total_sk_dang_dien ?></span>
                        </h3>
                        <div class="fw-semibold text-dark mb-1">Đang diễn ra</div>
                        <div class="text-muted small">Sự kiện đang trong thời gian hoạt động</div>
                    </div>
                </div>

                <!-- Sự kiện đã kết thúc -->
                <div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 position-relative overflow-hidden"
                        style="transition:.3s">
                        <div class="position-absolute top-0 start-0 w-100"
                            style="height:4px;background:linear-gradient(90deg,#6b7280,#9ca3af)"></div>
                        <div class="mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center bg-secondary bg-opacity-10"
                            style="width:72px;height:72px">
                            <i class="bi bi-check-circle-fill text-secondary" style="font-size:1.8rem"></i>
                        </div>
                        <h3 class="fw-bold text-secondary mb-1" style="font-size:2.5rem">
                            <span class="purecounter" data-purecounter-start="0"
                                data-purecounter-end="<?= $total_sk_ket_thuc ?>"
                                data-purecounter-duration="1"><?= $total_sk_ket_thuc ?></span>
                        </h3>
                        <div class="fw-semibold text-dark mb-1">Đã kết thúc</div>
                        <div class="text-muted small">Sự kiện đã hoàn thành</div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===== BẢNG CHI TIẾT ===== -->
    <section class="section light-background">
        <div class="container" data-aos="fade-up">
            <div class="row gy-4">

                <!-- Sự kiện gần đây -->
                <div class="col-lg-7" data-aos="fade-up" data-aos-delay="100">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div
                            class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0"><i class="bi bi-calendar3 me-2 text-primary"></i>Sự kiện gần đây
                            </h5>
                            <a href="<?= _HOST_URL ?>/?module=event&action=index"
                                class="btn btn-sm btn-outline-primary">
                                Xem tất cả <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <?php if (empty($recent_sk)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>Chưa có sự kiện nào.
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_sk as $sk):
                                    $now_ts  = time();
                                    $start   = $sk['ngayBatDau'] ? strtotime($sk['ngayBatDau']) : null;
                                    $end     = $sk['ngayKetThuc'] ? strtotime($sk['ngayKetThuc']) : null;
                                    if (!$start) {
                                        $label = 'Chưa có lịch';
                                        $badge = 'bg-secondary';
                                    } elseif ($start > $now_ts) {
                                        $label = 'Sắp diễn ra';
                                        $badge = 'bg-info text-dark';
                                    } elseif (!$end || $end >= $now_ts) {
                                        $label = 'Đang diễn ra';
                                        $badge = 'bg-success';
                                    } else {
                                        $label = 'Đã kết thúc';
                                        $badge = 'bg-secondary';
                                    }
                                ?>
                                    <div class="d-flex align-items-start gap-3 py-3 border-bottom last-no-border">
                                        <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                                            style="width:44px;height:44px">
                                            <i class="bi bi-calendar-event text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="fw-semibold text-truncate"><?= htmlspecialchars($sk['tenSK']) ?></div>
                                            <div class="text-muted small mt-1">
                                                <?php if ($sk['ngayBatDau']): ?>
                                                    <i
                                                        class="bi bi-clock me-1"></i><?= date('d/m/Y', strtotime($sk['ngayBatDau'])) ?>
                                                <?php endif; ?>
                                                <?php if ($sk['tenCap']): ?>
                                                    · <i class="bi bi-building me-1"></i><?= htmlspecialchars($sk['tenCap']) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <span class="badge <?= $badge ?> rounded-pill flex-shrink-0"><?= $label ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tài khoản mới -->
                <div class="col-lg-5" data-aos="fade-up" data-aos-delay="200">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div
                            class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0"><i class="bi bi-person-check me-2 text-success"></i>Tài khoản mới
                            </h5>
                            <a href="<?= _HOST_URL ?>/?module=admin&action=users"
                                class="btn btn-sm btn-outline-success">
                                Xem tất cả <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <?php if (empty($recent_tk)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-people fs-3 d-block mb-2"></i>Chưa có tài khoản nào.
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_tk as $tk):
                                    $rc = [1 => 'danger', 2 => 'warning', 3 => 'primary'][$tk['idLoaiTK']] ?? 'secondary';
                                    $ri = [1 => 'bi-shield-fill', 2 => 'bi-person-workspace', 3 => 'bi-mortarboard-fill'][$tk['idLoaiTK']] ?? 'bi-person';
                                ?>
                                    <div class="d-flex align-items-center gap-3 py-2 border-bottom last-no-border">
                                        <div class="rounded-circle bg-<?= $rc ?> bg-opacity-15 d-flex align-items-center justify-content-center flex-shrink-0"
                                            style="width:40px;height:40px">
                                            <i class="bi <?= $ri ?> text-<?= $rc ?>"></i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="fw-semibold text-truncate"><?= htmlspecialchars($tk['tenTK']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($tk['hoTen'] ?: '—') ?></div>
                                        </div>
                                        <div class="text-end flex-shrink-0">
                                            <span
                                                class="badge bg-<?= $rc ?>-subtle text-<?= $rc ?> border border-<?= $rc ?>"><?= htmlspecialchars($tk['tenLoaiTK']) ?></span>
                                            <div class="text-muted small mt-1"><?= date('d/m/Y', strtotime($tk['ngayTao'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

</main>

<?php layout('footer'); ?>

<style>
    /* Hover hiệu ứng card thống kê */
    .card {
        transition: transform .2s, box-shadow .2s;
    }

    .card:hover {
        transform: translateY(-4px);
        box-shadow: 0 .75rem 1.5rem rgba(0, 0, 0, .12) !important;
    }

    .border-bottom.last-no-border:last-child {
        border-bottom: 0 !important;
    }

    /* Pulse live indicator */
    .pulse-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #fff;
        animation: pulse-anim 1.2s infinite;
    }

    @keyframes pulse-anim {

        0%,
        100% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: .5;
            transform: scale(1.4);
        }
    }
</style>