<?php

if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/quan_ly_su_kien.php';

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$event_created = false;
$event_error = '';

$can_create_event = kiem_tra_quyen_he_thong($conn, $user_id, 'event.manage');

// Xử lý tạo sự kiện mới (chỉ BTC / admin mới được tạo)
if (isPost() && isset($_POST['create_event'])) {
    $filterData = filter();

    $tenSK = trim($filterData['tenSK'] ?? '');
    $moTa = trim($filterData['moTa'] ?? '');
    $idCap = (int)($filterData['idCap'] ?? 0);
    $isActive = isset($filterData['isActive']) ? (int)$filterData['isActive'] : 1;

    $ngayBatDau = !empty($filterData['ngayBatDau']) ? date('Y-m-d', strtotime($filterData['ngayBatDau'])) : '';
    $ngayKetThuc = !empty($filterData['ngayKetThuc']) ? date('Y-m-d', strtotime($filterData['ngayKetThuc'])) : '';

    $errors = [];

    if ($tenSK === '') {
        $errors[] = 'Tên sự kiện không được để trống.';
    }
    if ($idCap <= 0) {
        $errors[] = 'Vui lòng chọn cấp tổ chức.';
    }
    if (empty($ngayBatDau) || empty($ngayKetThuc)) {
        $errors[] = 'Vui lòng nhập ngày bắt đầu và ngày kết thúc.';
    } else {
        $now = strtotime(date('Y-m-d'));
        $bat = strtotime($ngayBatDau);
        $ket = strtotime($ngayKetThuc);

        if ($bat < $now) {
            $errors[] = 'Ngày bắt đầu phải từ hôm nay trở đi.';
        }
        if ($ket < $bat) {
            $errors[] = 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.';
        }
    }

    if (!empty($errors)) {
        $event_error = implode('<br>', $errors);
    } else {
        // Chưa cấu hình ngày mở/đóng đăng ký => để NULL
        $result = btc_tao_su_kien(
            $conn,
            $user_id,
            $tenSK,
            $moTa,
            $idCap,
            null,
            null,
            $ngayBatDau,
            $ngayKetThuc,
            $isActive
        );

        if ($result['status']) {
            $event_created = true;
        } else {
            $event_error = $result['message'] ?? 'Không tạo được sự kiện';
        }
    }
}

// ====== Lọc + Tìm kiếm ======
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_safe = $search !== '' ? chuan_hoa_chuoi_sql($conn, $search) : '';

$filter_loai_cap = isset($_GET['loaiCap']) ? (int)$_GET['loaiCap'] : 0;
$filter_cap = isset($_GET['cap']) ? (int)$_GET['cap'] : 0;

$data = [
    'page_title' => ($user_role == 1) ? 'Quản lý sự kiện' : 'Sự kiện'
];

$active_page = 'event';

// ====== Lấy danh sách sự kiện với tìm kiếm + lọc + sắp xếp ======
$sql = "SELECT sk.*, ct.tenCap, tk.tenTK as nguoiTaoTen
        FROM sukien sk
        LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
        LEFT JOIN taikhoan tk ON sk.nguoiTao = tk.idTK
        WHERE sk.isActive = 1";

if (!empty($search_safe)) {
    $sql .= " AND (sk.tenSK LIKE '%$search_safe%' OR sk.moTa LIKE '%$search_safe%')";
}

if ($filter_loai_cap > 0) {
    $sql .= " AND ct.idLoaiCap = $filter_loai_cap";
}

if ($filter_cap > 0) {
    $sql .= " AND sk.idCap = $filter_cap";
}

// Sắp xếp ưu tiên theo trạng thái đăng ký
$sql .= " ORDER BY 
    CASE
        WHEN sk.ngayMoDangKy IS NOT NULL 
             AND sk.ngayDongDangKy IS NOT NULL
             AND CURDATE() BETWEEN sk.ngayMoDangKy AND sk.ngayDongDangKy THEN 1
        WHEN sk.ngayMoDangKy IS NOT NULL 
             AND CURDATE() < sk.ngayMoDangKy THEN 2
        WHEN sk.ngayDongDangKy IS NOT NULL
             AND CURDATE() > sk.ngayDongDangKy THEN 3
        ELSE 4
    END,
    sk.idSK DESC";

$result = mysqli_query($conn, $sql);
$events = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
}

// ====== Danh sách cấp tổ chức ======
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
                    <?php foreach ($events as $event):
                        $ngayBatDau = !empty($event['ngayBatDau']) ? date('d/m/Y', strtotime($event['ngayBatDau'])) : '';
                        $ngayKetThuc = !empty($event['ngayKetThuc']) ? date('d/m/Y', strtotime($event['ngayKetThuc'])) : '';

                        $now = time();
                        $batDau = !empty($event['ngayBatDau']) ? strtotime($event['ngayBatDau']) : 0;
                        $ketThuc = !empty($event['ngayKetThuc']) ? strtotime($event['ngayKetThuc']) : 0;
                        $moDangKy = !empty($event['ngayMoDangKy']) ? strtotime($event['ngayMoDangKy']) : 0;
                        $dongDangKy = !empty($event['ngayDongDangKy']) ? strtotime($event['ngayDongDangKy']) : 0;

                        $trangThai = '';
                        if (!empty($event['ngayMoDangKy']) && !empty($event['ngayDongDangKy'])) {
                            if ($now < $moDangKy) {
                                $trangThai = 'Sắp mở đăng ký';
                            } elseif ($now >= $moDangKy && $now <= $dongDangKy) {
                                $trangThai = 'Đang mở đăng ký';
                            } elseif ($now > $dongDangKy && $now < $batDau) {
                                $trangThai = 'Đã đóng đăng ký';
                            } elseif ($now >= $batDau && $now <= $ketThuc) {
                                $trangThai = 'Đang diễn ra';
                            } else {
                                $trangThai = 'Đã kết thúc';
                            }
                        } else {
                            $trangThai = 'Chưa cấu hình đăng ký';
                        }
                    ?>
                        <article class="event-card" data-aos="fade-up" data-aos-delay="200">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <div class="event-image">
                                        <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/education/events-3.webp"
                                            class="img-fluid" alt="Event Image">
                                        <div class="date-badge">
                                            <span class="day"><?php echo !empty($event['ngayBatDau']) ? date('d', strtotime($event['ngayBatDau'])) : '--'; ?></span>
                                            <span class="month"><?php echo !empty($event['ngayBatDau']) ? date('M', strtotime($event['ngayBatDau'])) : '--'; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="event-content">
                                        <div class="event-meta">
                                            <span class="time"><i class="bi bi-clock"></i> <?php echo $ngayBatDau ?> -
                                                <?php echo $ngayKetThuc ?></span>
                                            <span class="location"><i class="bi bi-geo-alt"></i> Online Webinar</span>
                                        </div>
                                        <h3 class="event-title">
                                            <a href="<?php echo _HOST_URL ?>/?module=event&action=view&id=<?php echo (int)$event['idSK']; ?>">
                                                <?= htmlspecialchars($event['tenSK']) ?>
                                            </a>
                                        </h3>
                                        <p class="event-description"><?= htmlspecialchars(substr($event['moTa'], 0, 100)) ?>
                                            <?= strlen($event['moTa']) > 100 ? '...' : '' ?></p>
                                        <div class="event-footer">
                                            <div class="instructor">
                                                <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/person/person-f-8.webp"
                                                    alt="Instructor" class="instructor-avatar">
                                                <span><?= htmlspecialchars($event['nguoiTaoTen'] ?? 'BTC') ?></span>
                                            </div>
                                            <div class="event-price">
                                                <span class="price"><?= $trangThai ?></span>
                                            </div>
                                        </div>
                                        <div class="event-actions">
                                            <a href="<?php echo _HOST_URL ?>/?module=event&action=view&id=<?php echo (int)$event['idSK']; ?>"
                                                class="btn btn-primary">Xem
                                                sự kiện</a>
                                            <a href="#" class="btn btn-outline">Learn More</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="sidebar-widget newsletter-widget" data-aos="fade-up" data-aos-delay="500">
                        <h4 class="widget-title">Quản lý sự kiện</h4>
                        <p>Nhấn để tạo sự kiện mới.</p>

                        <?php if ($can_create_event): ?>
                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#createEventModal">
                                Tạo sự kiện
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($can_create_event): ?>
                    <!-- Modal Create Event -->
                    <div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content shadow-sm">
                                <form action="<?php echo _HOST_URL ?>/?module=event&action=index" method="post">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-semibold" id="createEventModalLabel">Tạo sự kiện mới</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                    </div>

                                    <div class="modal-body">
                                        <?php if (!empty($event_error)): ?>
                                            <div class="alert alert-danger" role="alert">
                                                <?php echo $event_error; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($event_created)): ?>
                                            <div class="alert alert-success" role="alert">
                                                Sự kiện đã được tạo thành công!
                                            </div>
                                        <?php endif; ?>

                                        <div class="mb-3">
                                            <label class="form-label">Tên sự kiện</label>
                                            <input type="text" name="tenSK" class="form-control" placeholder="Nhập tên sự kiện..." required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Mô tả ngắn</label>
                                            <textarea name="moTa" class="form-control" rows="3" placeholder="Mô tả sự kiện..."></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Cấp tổ chức</label>
                                            <select name="idCap" class="form-select" required>
                                                <option value="">-- Chọn cấp tổ chức --</option>
                                                <?php foreach ($caps as $cap): ?>
                                                    <option value="<?php echo (int)$cap['idCap']; ?>">
                                                        <?php echo htmlspecialchars($cap['tenCap']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Ngày bắt đầu</label>
                                                <input type="date" name="ngayBatDau" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Ngày kết thúc</label>
                                                <input type="date" name="ngayKetThuc" class="form-control" required>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <label class="form-label">Trạng thái</label>
                                            <select name="isActive" class="form-select">
                                                <option value="1">Kích hoạt</option>
                                                <option value="0">Ẩn</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                                        <button type="submit" name="create_event" value="1" class="btn btn-primary">
                                            Tạo sự kiện
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($event_error) || !empty($event_created)): ?>
                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var modalEl = document.getElementById('createEventModal');
                        if (modalEl && typeof bootstrap !== 'undefined') {
                            var modal = new bootstrap.Modal(modalEl);
                            modal.show();
                        }
                    });
                    </script>
                    <?php endif; ?>
                    <?php endif; ?>

                    <!-- Search Widget -->
                    <div class="sidebar-widget search-widget" data-aos="fade-up" data-aos-delay="200">
                        <h4 class="widget-title">Tìm kiếm sự kiện</h4>
                        <form class="search-form" method="get" action="<?php echo _HOST_URL; ?>">
                            <input type="hidden" name="module" value="event">
                            <input type="hidden" name="action" value="index">
                            <input type="text" name="search" placeholder="Tìm kiếm sự kiện..." class="form-control"
                                value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="search-btn">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>

                        <div class="mt-3">
                            <h6 class="mb-2">Bộ lọc sự kiện</h6>
                            <form method="get" action="<?php echo _HOST_URL; ?>">
                                <input type="hidden" name="module" value="event">
                                <input type="hidden" name="action" value="index">

                                <?php if (!empty($search)): ?>
                                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <?php endif; ?>

                               

                                <div class="mb-2">
                                    <label class="form-label">Khoa / Cấp tổ chức</label>
                                    <select name="cap" class="form-select">
                                        <option value="">-- Tất cả --</option>
                                        <?php foreach ($caps as $cap): ?>
                                            <option value="<?php echo (int)$cap['idCap']; ?>" <?php echo ($filter_cap == (int)$cap['idCap']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cap['tenCap']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-outline-primary">Lọc sự kiện</button>
                                    <a class="btn btn-light" href="<?php echo _HOST_URL; ?>/?module=event&action=index">Xóa lọc</a>
                                </div>
                            </form>
                        </div>
                    </div><!-- End Search Widget -->

                </div>

            </div>

        </div>

    </section><!-- /Courses Events Section -->

</main>

<?php
layout('footer'); ?>