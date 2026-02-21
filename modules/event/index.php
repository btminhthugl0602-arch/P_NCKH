<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Xử lý tạo sự kiện mới
$event_created = false;
$event_error = '';

// Xử lý tạo sự kiện mới (chỉ BTC / admin mới được tạo)
if (isPost() && isset($_POST['create_event'])) {
    $filterData = filter();

    $tenSK = trim($filterData['tenSK'] ?? '');
    $moTa = trim($filterData['moTa'] ?? '');
    $idCap = (int)($filterData['idCap'] ?? 0);
    $isActive = isset($filterData['isActive']) ? (int)$filterData['isActive'] : 1;

    $ngayMoDangKy = !empty($filterData['ngayMoDangKy']) ? date('Y-m-d H:i:s', strtotime($filterData['ngayMoDangKy'])) : '';
    $ngayDongDangKy = !empty($filterData['ngayDongDangKy']) ? date('Y-m-d H:i:s', strtotime($filterData['ngayDongDangKy'])) : '';
    $ngayBatDau = !empty($filterData['ngayBatDau']) ? date('Y-m-d H:i:s', strtotime($filterData['ngayBatDau'])) : '';
    $ngayKetThuc = !empty($filterData['ngayKetThuc']) ? date('Y-m-d H:i:s', strtotime($filterData['ngayKetThuc'])) : '';

    $errors = [];

    if ($tenSK === '') {
        $errors[] = 'Tên sự kiện không được để trống.';
    }
    if ($idCap <= 0) {
        $errors[] = 'Vui lòng chọn cấp tổ chức.';
    }
    if (empty($ngayMoDangKy) || empty($ngayDongDangKy) || empty($ngayBatDau) || empty($ngayKetThuc)) {
        $errors[] = 'Vui lòng nhập đầy đủ thời gian.';
    } else {
        $now = strtotime(date('Y-m-d'));
        $mo = strtotime($ngayMoDangKy);
        $dong = strtotime($ngayDongDangKy);
        $bat = strtotime($ngayBatDau);
        $ket = strtotime($ngayKetThuc);

        if ($mo < $now) {
            $errors[] = 'Ngày mở đăng ký phải từ hôm nay trở đi.';
        }
        if ($mo >= $dong) {
            $errors[] = 'Ngày mở đăng ký phải nhỏ hơn ngày đóng đăng ký.';
        }
        if ($bat < $mo) {
            $errors[] = 'Ngày bắt đầu phải lớn hơn hoặc bằng ngày mở đăng ký.';
        }
        if ($ket < $bat) {
            $errors[] = 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.';
        }
    }

    if (!empty($errors)) {
        $event_error = implode('<br>', $errors);
    } else {
        // Gọi hàm service chuẩn
        $result = btc_tao_su_kien(
            $conn,
            $user_id,
            $tenSK,
            $moTa,
            $idCap,
            $ngayMoDangKy,
            $ngayDongDangKy,
            $ngayBatDau,
            $ngayKetThuc,
            $isActive
        );

        if (mysqli_query($conn, $sql_insert)) {
            $event_created = true;
        } else {
            $create_error = 'Lỗi khi tạo sự kiện: ' . mysqli_error($conn);
        }
    }
}

// Lấy từ khóa tìm kiếm
$search = isset($_GET['search']) ? chuan_hoa_chuoi_sql($conn, $_GET['search']) : '';

$data = [
    'page_title' => ($user_role == 1) ? 'Quản lý sự kiện' : 'Sự kiện'
];

$active_page = 'event';

// Lấy danh sách sự kiện với tìm kiếm
$sql = "SELECT sk.*, ct.tenCap, tk.tenTK as nguoiTaoTen
        FROM sukien sk
        LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
        LEFT JOIN taikhoan tk ON sk.nguoiTao = tk.idTK
        WHERE sk.isActive = 1";

if (!empty($search)) {
    $sql .= " AND (sk.tenSK LIKE '%$search%' OR sk.moTa LIKE '%$search%')";
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

layout('header', $data);
layout('navbar');
?>

<!-- Modal tạo sự kiện -->
<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0">

            <div class="modal-body p-0">
                <div class="enrollment-form-wrapper">

                    <!-- Header -->
                    <div class="enrollment-header text-center mb-4 pt-4 px-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                        <h2>Tạo sự kiện mới</h2>
                        <p>Điền đầy đủ thông tin bên dưới để tạo và công bố sự kiện trong hệ thống.</p>
                    </div>

                    <form class="enrollment-form px-4 pb-4" id="createEventForm" action="" method="post" novalidate>

                        <?php if (!empty($create_error)): ?>
                            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <?= htmlspecialchars($create_error) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Tên sự kiện + Mô tả -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="tenSK" class="form-label">Tên sự kiện <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="tenSK" name="tenSK"
                                        placeholder="Nhập tên sự kiện..." required
                                        value="<?= isset($_POST['tenSK']) ? htmlspecialchars($_POST['tenSK']) : '' ?>">
                                    <div class="invalid-feedback">Vui lòng nhập tên sự kiện.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="moTa" class="form-label">Mô tả sự kiện</label>
                                    <textarea class="form-control" id="moTa" name="moTa" rows="3"
                                        placeholder="Chia sẻ mục tiêu và nội dung của sự kiện..."><?= isset($_POST['moTa']) ? htmlspecialchars($_POST['moTa']) : '' ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Cấp tổ chức + Trạng thái -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="idCap" class="form-label">Cấp tổ chức</label>
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
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Trạng thái hiển thị <span
                                            class="text-danger">*</span></label>
                                    <div class="schedule-options">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="isActive" id="isActive1"
                                                value="1"
                                                <?= (!isset($_POST['isActive']) || $_POST['isActive'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="isActive1">
                                                <i class="bi bi-eye me-1 text-success"></i> Công khai
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="isActive" id="isActive2"
                                                value="2"
                                                <?= (isset($_POST['isActive']) && $_POST['isActive'] == 2) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="isActive2">
                                                <i class="bi bi-eye-slash me-1 text-secondary"></i> Ẩn
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ngày mở / đóng đăng ký -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ngayMoDangKy" class="form-label">Ngày mở đăng ký <span
                                            class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="ngayMoDangKy"
                                        name="ngayMoDangKy" required
                                        value="<?= isset($_POST['ngayMoDangKy']) ? htmlspecialchars($_POST['ngayMoDangKy']) : '' ?>">
                                    <div class="invalid-feedback" id="err-ngayMoDangKy"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ngayDongDangKy" class="form-label">Ngày đóng đăng ký <span
                                            class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="ngayDongDangKy"
                                        name="ngayDongDangKy" required
                                        value="<?= isset($_POST['ngayDongDangKy']) ? htmlspecialchars($_POST['ngayDongDangKy']) : '' ?>">
                                    <div class="invalid-feedback" id="err-ngayDongDangKy"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Ngày bắt đầu / kết thúc -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ngayBatDau" class="form-label">Ngày bắt đầu</label>
                                    <input type="datetime-local" class="form-control" id="ngayBatDau" name="ngayBatDau"
                                        value="<?= isset($_POST['ngayBatDau']) ? htmlspecialchars($_POST['ngayBatDau']) : '' ?>">
                                    <div class="invalid-feedback" id="err-ngayBatDau"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ngayKetThuc" class="form-label">Ngày kết thúc</label>
                                    <input type="datetime-local" class="form-control" id="ngayKetThuc"
                                        name="ngayKetThuc"
                                        value="<?= isset($_POST['ngayKetThuc']) ? htmlspecialchars($_POST['ngayKetThuc']) : '' ?>">
                                    <div class="invalid-feedback" id="err-ngayKetThuc"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="row">
                            <div class="col-12 d-flex justify-content-between align-items-center">
                                <button type="button" class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">Hủy</button>
                                <div class="text-center">
                                    <button type="submit" name="create_event" class="btn btn-primary btn-enroll">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Tạo sự kiện
                                    </button>

                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>

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
                        $ngayMoDangKy = !empty($event['ngayMoDangKy']) ? date('d/m/Y', strtotime($event['ngayMoDangKy'])) : '';
                        $ngayDongDangKy = !empty($event['ngayDongDangKy']) ? date('d/m/Y', strtotime($event['ngayDongDangKy'])) : '';
                        $ngayBatDau = !empty($event['ngayBatDau']) ? date('d/m/Y', strtotime($event['ngayBatDau'])) : '';
                        $ngayKetThuc = !empty($event['ngayKetThuc']) ? date('d/m/Y', strtotime($event['ngayKetThuc'])) : '';

                        $batDau = $event['ngayBatDau'] ? strtotime($event['ngayBatDau']) : 0;
                        $ketThuc = $event['ngayKetThuc'] ? strtotime($event['ngayKetThuc']) : 0;
                        $moDangKy = strtotime($event['ngayMoDangKy']);
                        $dongDangKy = strtotime($event['ngayDongDangKy']);

                        $trangThai = '';
                        $badgeClass = '';
                        if ($now < $moDangKy) {
                            $trangThai = 'Sắp mở đăng ký';
                            $badgeClass = 'bg-gradient-secondary';
                        } elseif ($now >= $moDangKy && $now <= $dongDangKy) {
                            $trangThai = 'Đang mở đăng ký';
                            $badgeClass = 'bg-gradient-success';
                        } elseif ($now > $dongDangKy && $now < $batDau) {
                            $trangThai = 'Đã đóng đăng ký';
                            $badgeClass = 'bg-gradient-warning';
                        } elseif ($now >= $batDau && $now <= $ketThuc) {
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
                                        <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/images.png" class="img-fluid">
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

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-wrapper mt-4" data-aos="fade-up" data-aos-delay="300">
                            <nav aria-label="Events pagination">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $cur_page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                            href="<?= event_page_url($cur_page - 1, $search, $filter_cap, $filter_time) ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php
                                    $start_p = max(1, $cur_page - 2);
                                    $end_p   = min($total_pages, $cur_page + 2);
                                    if ($start_p > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="<?= event_page_url(1, $search, $filter_cap, $filter_time) ?>">1</a>
                                        </li>
                                        <?php if ($start_p > 2): ?><li class="page-item disabled"><span
                                                    class="page-link">…</span></li><?php endif; ?>
                                    <?php endif; ?>
                                    <?php for ($p = $start_p; $p <= $end_p; $p++): ?>
                                        <li class="page-item <?= $p == $cur_page ? 'active' : '' ?>">
                                            <a class="page-link"
                                                href="<?= event_page_url($p, $search, $filter_cap, $filter_time) ?>"><?= $p ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <?php if ($end_p < $total_pages): ?>
                                        <?php if ($end_p < $total_pages - 1): ?><li class="page-item disabled"><span
                                                    class="page-link">…</span></li><?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="<?= event_page_url($total_pages, $search, $filter_cap, $filter_time) ?>"><?= $total_pages ?></a>
                                        </li>
                                    <?php endif; ?>
                                    <li class="page-item <?= $cur_page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                            href="<?= event_page_url($cur_page + 1, $search, $filter_cap, $filter_time) ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <p class="text-center text-muted small mt-2">
                                Trang <?= $cur_page ?> / <?= $total_pages ?> &nbsp;·&nbsp; <?= $total_sk ?> sự kiện
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Newsletter Widget -->
                    <div class="sidebar-widget newsletter-widget" data-aos="fade-up" data-aos-delay="500">
                        <h4 class="widget-title">Tạo sự kiện mới</h4>
                        <p>Nhập thông tin sự kiện để tạo mới.</p>
                        <form action="<?php echo _HOST_URL ?>/?module=event&action=index" method="post" class="newsletter-form">
                            <input type="text" name="tenSK" placeholder="Tên sự kiện..." required="">
                            <textarea name="moTa" placeholder="Mô tả ngắn..." rows="3"></textarea>

                            <select name="idCap" required="">
                                <option value="">-- Chọn cấp tổ chức --</option>
                                <?php foreach ($caps as $cap): ?>
                                    <option value="<?php echo (int)$cap['idCap']; ?>">
                                        <?php echo htmlspecialchars($cap['tenCap']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label>Ngày mở đăng ký</label>
                            <input type="datetime-local" name="ngayMoDangKy" required="">

                            <label>Ngày đóng đăng ký</label>
                            <input type="datetime-local" name="ngayDongDangKy" required="">

                            <label>Ngày bắt đầu</label>
                            <input type="datetime-local" name="ngayBatDau" required="">

                            <label>Ngày kết thúc</label>
                            <input type="datetime-local" name="ngayKetThuc" required="">

                            <select name="isActive">
                                <option value="1">Kích hoạt</option>
                                <option value="0">Ẩn</option>
                            </select>

                            <button type="submit" name="create_event" value="1">Tạo sự kiện</button>
                            <div class="loading">Đang tạo...</div>
                            <div class="error-message"><?php echo !empty($event_error) ? $event_error : ''; ?></div>
                            <div class="sent-message"><?php echo $event_created ? 'Sự kiện đã được tạo thành công!' : ''; ?></div>
                        </form>
                    </div><!-- End Newsletter Widget -->

                    <!-- Search Widget -->
                    <div class="sidebar-widget search-widget" data-aos="fade-up" data-aos-delay="200">
                        <h4 class="widget-title">Tìm kiếm sự kiện</h4>
                        <form class="search-form" method="get" action="">
                            <input type="text" name="search" placeholder="Tìm kiếm sự kiện..." class="form-control"
                                value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="search-btn">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div><!-- End Search Widget -->

                </div>

            </div>

        </div>

    </section>

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
                new bootstrap.Modal(document.getElementById('createEventModal')).show();
            });
            document.getElementById('quickEventName').addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        }

        // Validate thời gian client-side
        const form = document.getElementById('createEventForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                let valid = true;
                const mo = document.getElementById('ngayMoDangKy');
                const dong = document.getElementById('ngayDongDangKy');
                const bat = document.getElementById('ngayBatDau');
                const ket = document.getElementById('ngayKetThuc');
                const errMo = document.getElementById('err-ngayMoDangKy');
                const errBat = document.getElementById('err-ngayBatDau');

                [mo, dong, bat, ket].forEach(el => el.classList.remove('is-invalid'));

                if (mo.value && dong.value && new Date(mo.value) >= new Date(dong.value)) {
                    mo.classList.add('is-invalid');
                    dong.classList.add('is-invalid');
                    errMo.textContent = 'Ngày mở đăng ký phải trước ngày đóng đăng ký.';
                    valid = false;
                }

                if (bat.value && ket.value && new Date(bat.value) >= new Date(ket.value)) {
                    bat.classList.add('is-invalid');
                    ket.classList.add('is-invalid');
                    errBat.textContent = 'Ngày bắt đầu phải trước ngày kết thúc.';
                    valid = false;
                }

                if (!valid) {
                    e.preventDefault();
                    const firstErr = form.querySelector('.is-invalid');
                    if (firstErr) firstErr.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            });

            ['ngayMoDangKy', 'ngayDongDangKy', 'ngayBatDau', 'ngayKetThuc'].forEach(function(id) {
                const el = document.getElementById(id);
                if (el) el.addEventListener('change', function() {
                    this.classList.remove('is-invalid');
                });
            });
        }

        // Tự động ẩn toast sau 4 giây
        const toastEl = document.getElementById('successToast');
        if (toastEl) {
            setTimeout(function() {
                const toast = bootstrap.Toast.getInstance(toastEl);
                if (toast) toast.hide();
                else toastEl.style.display = 'none';
            }, 4000);
        }

        // Nếu có lỗi PHP, mở lại modal
        <?php if (!empty($create_error)): ?>
            new bootstrap.Modal(document.getElementById('createEventModal')).show();
        <?php endif; ?>

    });
</script>

<?php
layout('footer');
?>