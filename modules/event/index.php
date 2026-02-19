<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Xử lý tạo sự kiện mới
$event_created = false;
$create_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event']) && $user_role == 1) {
    $tenSK = mysqli_real_escape_string($conn, trim($_POST['tenSK']));
    $moTa = mysqli_real_escape_string($conn, trim($_POST['moTa']));
    $idCap = !empty($_POST['idCap']) ? (int)$_POST['idCap'] : 'NULL';
    $ngayMoDangKy = mysqli_real_escape_string($conn, $_POST['ngayMoDangKy']);
    $ngayDongDangKy = mysqli_real_escape_string($conn, $_POST['ngayDongDangKy']);
    $ngayBatDau = !empty($_POST['ngayBatDau']) ? "'" . mysqli_real_escape_string($conn, $_POST['ngayBatDau']) . "'" : 'NULL';
    $ngayKetThuc = !empty($_POST['ngayKetThuc']) ? "'" . mysqli_real_escape_string($conn, $_POST['ngayKetThuc']) . "'" : 'NULL';

    if (empty($tenSK)) {
        $create_error = 'Vui lòng nhập tên sự kiện.';
    } elseif (empty($ngayMoDangKy) || empty($ngayDongDangKy)) {
        $create_error = 'Vui lòng điền đầy đủ ngày mở/đóng đăng ký.';
    } else {
        $idCapVal = ($idCap === 'NULL') ? 'NULL' : $idCap;
        $sql_insert = "INSERT INTO sukien (tenSK, moTa, idCap, nguoiTao, ngayMoDangKy, ngayDongDangKy, ngayBatDau, ngayKetThuc, isActive) 
                       VALUES ('$tenSK', '$moTa', $idCapVal, $user_id, '$ngayMoDangKy', '$ngayDongDangKy', $ngayBatDau, $ngayKetThuc, 1)";

        if (mysqli_query($conn, $sql_insert)) {
            $event_created = true;
        } else {
            $create_error = 'Lỗi khi tạo sự kiện: ' . mysqli_error($conn);
        }
    }
}

// Lấy từ khóa tìm kiếm và bộ lọc
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$filter_cap = isset($_GET['filter_cap']) ? (int)$_GET['filter_cap'] : 0;
$filter_time = isset($_GET['filter_time']) ? $_GET['filter_time'] : '';

$data = [
    'page_title' => ($user_role == 1) ? 'Quản lý sự kiện' : 'Sự kiện'
];

$active_page = 'event';

// Lấy danh sách sự kiện với tìm kiếm và lọc
$sql = "SELECT sk.*, ct.tenCap, tk.tenTK as nguoiTaoTen
        FROM sukien sk
        LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
        LEFT JOIN taikhoan tk ON sk.nguoiTao = tk.idTK
        WHERE sk.isActive = 1";

if (!empty($search)) {
    $sql .= " AND (sk.tenSK LIKE '%$search%' OR sk.moTa LIKE '%$search%')";
}

if ($filter_cap > 0) {
    $sql .= " AND sk.idCap = $filter_cap";
}

if ($filter_time === 'today') {
    $sql .= " AND DATE(sk.ngayBatDau) = CURDATE()";
} elseif ($filter_time === 'week') {
    $sql .= " AND sk.ngayBatDau BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)";
} elseif ($filter_time === 'month') {
    $sql .= " AND sk.ngayBatDau BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 MONTH)";
} elseif ($filter_time === 'quarter') {
    $sql .= " AND sk.ngayBatDau BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 MONTH)";
}

$sql .= " ORDER BY sk.idSK DESC";

$result = mysqli_query($conn, $sql);
$events = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
}

// Lấy danh sách cấp tổ chức cho dropdown
$sql_cap = "SELECT * FROM cap_tochuc ORDER BY tenCap";
$result_cap = mysqli_query($conn, $sql_cap);
$caps = [];
if ($result_cap) {
    while ($row = mysqli_fetch_assoc($result_cap)) {
        $caps[] = $row;
    }
}

// Lấy sự kiện sắp diễn ra (upcoming) cho widget sidebar
$sql_upcoming = "SELECT sk.*, ct.tenCap
                 FROM sukien sk
                 LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
                 WHERE sk.isActive = 1 AND sk.ngayBatDau >= NOW()
                 ORDER BY sk.ngayBatDau ASC
                 LIMIT 5";
$result_upcoming = mysqli_query($conn, $sql_upcoming);
$upcoming_events = [];
if ($result_upcoming) {
    while ($row = mysqli_fetch_assoc($result_upcoming)) {
        $upcoming_events[] = $row;
    }
}

layout('header', $data);
layout('navbar');
?>

<!-- Modal tạo sự kiện -->
<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEventModalLabel"><i class="bi bi-calendar-plus me-2"></i>Tạo sự kiện
                    mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post" id="createEventForm">
                <div class="modal-body">
                    <?php if (!empty($create_error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($create_error) ?></div>
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="tenSK" class="form-label fw-semibold">Tên sự kiện <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tenSK" name="tenSK"
                                placeholder="Nhập tên sự kiện..." required
                                value="<?= isset($_POST['tenSK']) ? htmlspecialchars($_POST['tenSK']) : '' ?>">
                        </div>
                        <div class="col-12">
                            <label for="moTa" class="form-label fw-semibold">Mô tả sự kiện</label>
                            <textarea class="form-control" id="moTa" name="moTa" rows="3"
                                placeholder="Nhập mô tả sự kiện..."><?= isset($_POST['moTa']) ? htmlspecialchars($_POST['moTa']) : '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="idCap" class="form-label fw-semibold">Cấp tổ chức</label>
                            <select class="form-select" id="idCap" name="idCap">
                                <option value="">-- Chọn cấp tổ chức --</option>
                                <?php foreach ($caps as $cap): ?>
                                    <option value="<?= $cap['idCap'] ?>"
                                        <?= (isset($_POST['idCap']) && $_POST['idCap'] == $cap['idCap']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cap['tenCap']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <!-- placeholder col -->
                        </div>
                        <div class="col-md-6">
                            <label for="ngayMoDangKy" class="form-label fw-semibold">Ngày mở đăng ký <span
                                    class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="ngayMoDangKy" name="ngayMoDangKy"
                                required
                                value="<?= isset($_POST['ngayMoDangKy']) ? htmlspecialchars($_POST['ngayMoDangKy']) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="ngayDongDangKy" class="form-label fw-semibold">Ngày đóng đăng ký <span
                                    class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="ngayDongDangKy" name="ngayDongDangKy"
                                required
                                value="<?= isset($_POST['ngayDongDangKy']) ? htmlspecialchars($_POST['ngayDongDangKy']) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="ngayBatDau" class="form-label fw-semibold">Ngày bắt đầu</label>
                            <input type="datetime-local" class="form-control" id="ngayBatDau" name="ngayBatDau"
                                value="<?= isset($_POST['ngayBatDau']) ? htmlspecialchars($_POST['ngayBatDau']) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="ngayKetThuc" class="form-label fw-semibold">Ngày kết thúc</label>
                            <input type="datetime-local" class="form-control" id="ngayKetThuc" name="ngayKetThuc"
                                value="<?= isset($_POST['ngayKetThuc']) ? htmlspecialchars($_POST['ngayKetThuc']) : '' ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="create_event" class="btn btn-primary"><i
                            class="bi bi-check-lg me-1"></i>Tạo sự kiện</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast thành công -->
<?php if ($event_created): ?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999">
        <div id="successToast" class="toast show align-items-center text-bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body"><i class="bi bi-check-circle me-2"></i>Sự kiện đã được tạo thành công!</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<main class="main">

    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Events</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?php echo _HOST_URL ?>">Home</a></li>
                    <li class="current">Events</li>
                </ol>
            </nav>
        </div>
    </div><!-- End Page Title -->

    <!-- Courses Events Section -->
    <section id="courses-events" class="courses-events section">

        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <div class="row">

                <div class="col-lg-8">
                    <?php if (empty($events)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="mt-3 text-muted">Không tìm thấy sự kiện
                                nào<?= !empty($search) ? ' cho từ khóa "' . htmlspecialchars($search) . '"' : '' ?>.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($events as $event):
                        $ngayMoDangKy_f = date('d/m/Y', strtotime($event['ngayMoDangKy']));
                        $ngayDongDangKy_f = date('d/m/Y', strtotime($event['ngayDongDangKy']));
                        $ngayBatDau_f = $event['ngayBatDau'] ? date('d/m/Y', strtotime($event['ngayBatDau'])) : 'N/A';
                        $ngayKetThuc_f = $event['ngayKetThuc'] ? date('d/m/Y', strtotime($event['ngayKetThuc'])) : 'N/A';

                        $now = time();
                        $batDau = $event['ngayBatDau'] ? strtotime($event['ngayBatDau']) : 0;
                        $ketThuc = $event['ngayKetThuc'] ? strtotime($event['ngayKetThuc']) : 0;
                        $moDangKy = strtotime($event['ngayMoDangKy']);
                        $dongDangKy = strtotime($event['ngayDongDangKy']);

                        if ($now < $moDangKy) {
                            $trangThai = 'Sắp mở đăng ký';
                            $badgeClass = 'bg-gradient-secondary';
                        } elseif ($now >= $moDangKy && $now <= $dongDangKy) {
                            $trangThai = 'Đang mở đăng ký';
                            $badgeClass = 'bg-gradient-success';
                        } elseif ($now > $dongDangKy && ($batDau == 0 || $now < $batDau)) {
                            $trangThai = 'Đã đóng đăng ký';
                            $badgeClass = 'bg-gradient-warning';
                        } elseif ($batDau > 0 && $now >= $batDau && ($ketThuc == 0 || $now <= $ketThuc)) {
                            $trangThai = 'Đang diễn ra';
                            $badgeClass = 'bg-gradient-info';
                        } else {
                            $trangThai = 'Đã kết thúc';
                            $badgeClass = 'bg-gradient-dark';
                        }
                    ?>
                        <article class="event-card" data-aos="fade-up" data-aos-delay="200">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <div class="event-image">
                                        <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/education/events-3.webp"
                                            class="img-fluid" alt="Event Image">
                                        <div class="date-badge">
                                            <span
                                                class="day"><?= $event['ngayBatDau'] ? date('d', strtotime($event['ngayBatDau'])) : '--' ?></span>
                                            <span
                                                class="month"><?= $event['ngayBatDau'] ? date('M', strtotime($event['ngayBatDau'])) : '--' ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="event-content">
                                        <div class="event-meta">
                                            <span class="time"><i class="bi bi-clock"></i> <?= $ngayBatDau_f ?> -
                                                <?= $ngayKetThuc_f ?></span>
                                            <?php if (!empty($event['tenCap'])): ?>
                                                <span class="location"><i class="bi bi-building"></i>
                                                    <?= htmlspecialchars($event['tenCap']) ?></span>
                                            <?php else: ?>
                                                <span class="location"><i class="bi bi-geo-alt"></i> Online Webinar</span>
                                            <?php endif; ?>
                                        </div>
                                        <h3 class="event-title">
                                            <a
                                                href="<?php echo _HOST_URL ?>/?module=event&action=view&id=<?= $event['idSK'] ?>"><?= htmlspecialchars($event['tenSK']) ?></a>
                                        </h3>
                                        <p class="event-description">
                                            <?= htmlspecialchars(substr($event['moTa'] ?? '', 0, 100)) ?><?= strlen($event['moTa'] ?? '') > 100 ? '...' : '' ?>
                                        </p>
                                        <div class="event-footer">
                                            <div class="instructor">
                                                <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/person/person-f-8.webp"
                                                    alt="Instructor" class="instructor-avatar">
                                                <span><?= !empty($event['nguoiTaoTen']) ? htmlspecialchars($event['nguoiTaoTen']) : 'Admin' ?></span>
                                            </div>
                                            <div class="event-price">
                                                <span class="badge <?= $badgeClass ?>"
                                                    style="font-size:0.75rem;"><?= $trangThai ?></span>
                                            </div>
                                        </div>
                                        <div class="event-actions">
                                            <a href="<?php echo _HOST_URL ?>/?module=event&action=view&id=<?= $event['idSK'] ?>"
                                                class="btn btn-primary">Xem sự kiện</a>
                                            <a href="<?php echo _HOST_URL ?>/?module=event&action=view&id=<?= $event['idSK'] ?>"
                                                class="btn btn-outline">Learn More</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">

                    <!-- Newsletter Widget (Tạo sự kiện - chỉ admin) -->
                    <?php if ($user_role == 1): ?>
                        <div class="sidebar-widget newsletter-widget" data-aos="fade-up" data-aos-delay="500">
                            <h4 class="widget-title">Tạo sự kiện mới</h4>
                            <p>Nhập tên sự kiện và bắt đầu tạo sự kiện.</p>
                            <div class="newsletter-form">
                                <input type="text" id="quickEventName" placeholder="Tên sự kiện..."
                                    class="form-control mb-2">
                                <button type="button" id="btnOpenCreateModal" class="btn btn-primary w-100">Tạo sự
                                    kiện</button>
                            </div>
                        </div><!-- End Newsletter Widget -->
                    <?php endif; ?>

                    <!-- Search Widget -->
                    <div class="sidebar-widget search-widget" data-aos="fade-up" data-aos-delay="200">
                        <h4 class="widget-title">Tìm kiếm sự kiện</h4>
                        <form action="" method="get" class="search-form">
                            <input type="hidden" name="module" value="event">
                            <?php if ($filter_cap): ?><input type="hidden" name="filter_cap"
                                    value="<?= $filter_cap ?>"><?php endif; ?>
                            <?php if ($filter_time): ?><input type="hidden" name="filter_time"
                                    value="<?= htmlspecialchars($filter_time) ?>"><?php endif; ?>
                            <input type="text" name="search" placeholder="Tìm kiếm sự kiện..." class="form-control"
                                value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="search-btn">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div><!-- End Search Widget -->

                    <!-- Filter Widget -->
                    <div class="sidebar-widget filter-widget" data-aos="fade-up" data-aos-delay="300">
                        <h4 class="widget-title">Lọc sự kiện</h4>
                        <form action="" method="get" id="filterForm">
                            <input type="hidden" name="module" value="event">
                            <?php if (!empty($search)): ?>
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                            <?php endif; ?>
                            <div class="filter-content">
                                <div class="filter-group">
                                    <label class="filter-label">Cấp tổ chức</label>
                                    <select class="form-select" name="filter_cap">
                                        <option value="">Tất cả cấp</option>
                                        <?php foreach ($caps as $cap): ?>
                                            <option value="<?= $cap['idCap'] ?>"
                                                <?= ($filter_cap == $cap['idCap']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cap['tenCap']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Thời gian</label>
                                    <select class="form-select" name="filter_time">
                                        <option value="">Mọi thời điểm</option>
                                        <option value="today" <?= ($filter_time === 'today') ? 'selected' : '' ?>>Hôm
                                            nay</option>
                                        <option value="week" <?= ($filter_time === 'week') ? 'selected' : '' ?>>Tuần này
                                        </option>
                                        <option value="month" <?= ($filter_time === 'month') ? 'selected' : '' ?>>Tháng
                                            này</option>
                                        <option value="quarter" <?= ($filter_time === 'quarter') ? 'selected' : '' ?>>3
                                            tháng tới</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary filter-apply-btn flex-fill">Apply
                                        Filters</button>
                                    <a href="<?= _HOST_URL ?>/?module=event"
                                        class="btn btn-outline-secondary flex-fill">Xóa lọc</a>
                                </div>
                            </div>
                        </form>
                    </div><!-- End Filter Widget -->

                    <!-- Upcoming Events Widget -->
                    <div class="sidebar-widget upcoming-widget" data-aos="fade-up" data-aos-delay="400">
                        <h4 class="widget-title">Upcoming Events</h4>
                        <div class="upcoming-list">
                            <?php if (empty($upcoming_events)): ?>
                                <p class="text-muted" style="font-size:0.9rem;">Không có sự kiện sắp diễn ra.</p>
                            <?php endif; ?>
                            <?php foreach ($upcoming_events as $ue): ?>
                                <div class="upcoming-item">
                                    <div class="upcoming-date">
                                        <span
                                            class="day"><?= $ue['ngayBatDau'] ? date('d', strtotime($ue['ngayBatDau'])) : '--' ?></span>
                                        <span
                                            class="month"><?= $ue['ngayBatDau'] ? date('M', strtotime($ue['ngayBatDau'])) : '--' ?></span>
                                    </div>
                                    <div class="upcoming-content">
                                        <h5 class="upcoming-title">
                                            <a
                                                href="<?= _HOST_URL ?>/?module=event&action=view&id=<?= $ue['idSK'] ?>"><?= htmlspecialchars($ue['tenSK']) ?></a>
                                        </h5>
                                        <div class="upcoming-meta">
                                            <span class="time"><i class="bi bi-clock"></i>
                                                <?= $ue['ngayBatDau'] ? date('H:i', strtotime($ue['ngayBatDau'])) : '' ?>
                                            </span>
                                            <?php if (!empty($ue['tenCap'])): ?>
                                                <span class="price"><?= htmlspecialchars($ue['tenCap']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div><!-- End Upcoming Events Widget -->

                </div>

            </div>

        </div>

    </section><!-- /Courses Events Section -->

</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mở modal và điền sẵn tên sự kiện
        const btnOpen = document.getElementById('btnOpenCreateModal');
        if (btnOpen) {
            btnOpen.addEventListener('click', function() {
                const tenSK = document.getElementById('quickEventName').value.trim();
                if (!tenSK) {
                    document.getElementById('quickEventName').classList.add('is-invalid');
                    document.getElementById('quickEventName').focus();
                    return;
                }
                document.getElementById('quickEventName').classList.remove('is-invalid');
                document.getElementById('tenSK').value = tenSK;
                var modal = new bootstrap.Modal(document.getElementById('createEventModal'));
                modal.show();
            });
            document.getElementById('quickEventName').addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        }

        // Tự động ẩn toast sau 4 giây
        var toastEl = document.getElementById('successToast');
        if (toastEl) {
            setTimeout(function() {
                var toast = bootstrap.Toast.getInstance(toastEl);
                if (toast) toast.hide();
                else toastEl.style.display = 'none';
            }, 4000);
        }

        // Nếu có lỗi tạo sự kiện, mở lại modal
        <?php if (!empty($create_error)): ?>
            var errModal = new bootstrap.Modal(document.getElementById('createEventModal'));
            errModal.show();
        <?php endif; ?>
    });
</script>

<?php
layout('footer');
?>