<?php

if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/quan_ly_su_kien.php';

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$event_created = false;
$event_error = '';

// Xử lý tạo sự kiện mới (chỉ BTC / admin mới được tạo)
if (isPost() && isset($_POST['create_event'])) {
    $filterData = filter();
    $tenSK = $filterData['tenSK'] ?? '';
    $moTa = $filterData['moTa'] ?? '';

    // Gọi hàm service chuẩn
    $result = btc_tao_su_kien($conn, $user_id, $tenSK, $moTa);

    if ($result['status']) {
        $event_created = true;
    } else {
        $event_error = $result['message'] ?? 'Không tạo được sự kiện';
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
                        $ngayMoDangKy = !empty($event['ngayMoDangKy']) ? date('d/m/Y', strtotime($event['ngayMoDangKy'])) : '';
                        $ngayDongDangKy = !empty($event['ngayDongDangKy']) ? date('d/m/Y', strtotime($event['ngayDongDangKy'])) : '';
                        $ngayBatDau = !empty($event['ngayBatDau']) ? date('d/m/Y', strtotime($event['ngayBatDau'])) : '';
                        $ngayKetThuc = !empty($event['ngayKetThuc']) ? date('d/m/Y', strtotime($event['ngayKetThuc'])) : '';

                        $now = time();
                        $batDau = !empty($event['ngayBatDau']) ? strtotime($event['ngayBatDau']) : 0;
                        $ketThuc = !empty($event['ngayKetThuc']) ? strtotime($event['ngayKetThuc']) : 0;
                        $moDangKy = !empty($event['ngayMoDangKy']) ? strtotime($event['ngayMoDangKy']) : 0;
                        $dongDangKy = !empty($event['ngayDongDangKy']) ? strtotime($event['ngayDongDangKy']) : 0;

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
                    <!-- Newsletter Widget -->
                    <div class="sidebar-widget newsletter-widget" data-aos="fade-up" data-aos-delay="500">
                        <h4 class="widget-title">Tạo sự kiện mới</h4>
                        <p>Nhập tên sự kiện và bắt đầu tạo sự kiện.</p>
                        <form action="<?php echo _HOST_URL ?>/?module=event&action=index" method="post" class="newsletter-form">
    <input type="text" name="tenSK" placeholder="Tên sự kiện..." required="">
    <textarea name="moTa" placeholder="Mô tả ngắn..." rows="3"></textarea>
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
                                value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="search-btn">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div><!-- End Search Widget -->

                </div>

            </div>

        </div>

    </section><!-- /Courses Events Section -->

</main>

<?php
layout('footer'); ?>