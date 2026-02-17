<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Xử lý tạo sự kiện mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event']) && $user_role == 1) {
    $tenSK = mysqli_real_escape_string($conn, $_POST['tenSK']);
    $moTa = mysqli_real_escape_string($conn, $_POST['moTa']);
    $idCap = (int)$_POST['idCap'];
    $ngayMoDangKy = mysqli_real_escape_string($conn, $_POST['ngayMoDangKy']);
    $ngayDongDangKy = mysqli_real_escape_string($conn, $_POST['ngayDongDangKy']);
    $ngayBatDau = mysqli_real_escape_string($conn, $_POST['ngayBatDau']);
    $ngayKetThuc = mysqli_real_escape_string($conn, $_POST['ngayKetThuc']);

    $sql_insert = "INSERT INTO sukien (tenSK, moTa, idCap, nguoiTao, ngayMoDangKy, ngayDongDangKy, ngayBatDau, ngayKetThuc, isActive) 
                   VALUES ('$tenSK', '$moTa', $idCap, $user_id, '$ngayMoDangKy', '$ngayDongDangKy', '$ngayBatDau', '$ngayKetThuc', 1)";

    if (mysqli_query($conn, $sql_insert)) {
        $event_created = true;
    }
}

// Lấy từ khóa tìm kiếm
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

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
                        $ngayMoDangKy = date('d/m/Y', strtotime($event['ngayMoDangKy']));
                        $ngayDongDangKy = date('d/m/Y', strtotime($event['ngayDongDangKy']));
                        $ngayBatDau = date('d/m/Y', strtotime($event['ngayBatDau']));
                        $ngayKetThuc = date('d/m/Y', strtotime($event['ngayKetThuc']));

                        $now = time();
                        $batDau = strtotime($event['ngayBatDau']);
                        $ketThuc = strtotime($event['ngayKetThuc']);
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
                                        <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/education/events-3.webp"
                                            class="img-fluid" alt="Event Image">
                                        <div class="date-badge">
                                            <span
                                                class="day"><?php echo date('d', strtotime($events[0]['ngayBatDau'])); ?></span>
                                            <span
                                                class="month"><?php echo date('M', strtotime($events[0]['ngayBatDau'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="event-content">
                                        <div class="event-meta">
                                            <span class="time"><i class="bi bi-clock"></i> <?php echo $ngayBatDau ?> -
                                                <?php $ngayKetThuc ?></span>
                                            <span class="location"><i class="bi bi-geo-alt"></i> Online Webinar</span>
                                        </div>
                                        <h3 class="event-title">
                                            <a href="#"><?= htmlspecialchars($event['tenSK']) ?></a>
                                        </h3>
                                        <p class="event-description"><?= htmlspecialchars(substr($event['moTa'], 0, 100)) ?>
                                            <?= strlen($event['moTa']) > 100 ? '...' : '' ?></p>
                                        <div class="event-footer">
                                            <div class="instructor">
                                                <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/person/person-f-8.webp"
                                                    alt="Instructor" class="instructor-avatar">
                                                <span>Dr. Sarah Johnson</span>
                                            </div>
                                            <div class="event-price">
                                                <span class="price">$49</span>
                                            </div>
                                        </div>
                                        <div class="event-actions">
                                            <a href="<?php echo _HOST_URL ?>/?module=event&action=view"
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
                        <form action="" method="post" class="php-email-form newsletter-form">
                            <input type="email" name="email" placeholder="Tên sự kiện..." required="">
                            <button type="submit">Tạo sự kiện</button>
                            <div class="loading">Đang tạo...</div>
                            <div class="error-message"></div>
                            <div class="sent-message">Sự kiện đã được tạo thành công!</div>
                        </form>
                    </div><!-- End Newsletter Widget -->

                    <!-- Search Widget -->
                    <div class="sidebar-widget search-widget" data-aos="fade-up" data-aos-delay="200">
                        <h4 class="widget-title">Tìm kiếm sự kiện</h4>
                        <form class="search-form">
                            <input type="text" placeholder="Tìm kiếm sự kiện..." class="form-control">
                            <button type="submit" class="search-btn">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div><!-- End Search Widget -->

                    <!-- Filter Widget -->
                    <div class="sidebar-widget filter-widget" data-aos="fade-up" data-aos-delay="300">
                        <h4 class="widget-title">Lọc sự kiện</h4>
                        <div class="filter-content">
                            <div class="filter-group">
                                <label class="filter-label">Loại sự kiện</label>
                                <select class="form-select">
                                    <option value="">Tất cả loại</option>
                                    <option value="webinar">Công nghệ</option>
                                    <option value="workshop">Học tập</option>
                                    <option value="conference">Sắc đẹp</option>
                                    <option value="masterclass">Cấp Trường</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Thời gian</label>
                                <select class="form-select">
                                    <option value="">Mọi thời điểm</option>
                                    <option value="today">Hôm nay</option>
                                    <option value="week">Tuần này</option>
                                    <option value="month">Tháng này</option>
                                    <option value="quarter">3 tháng tới</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Cấp</label>
                                <select class="form-select">
                                    <option value="">Tất cả</option>
                                    <option value="free">Cấp Khoa</option>
                                    <option value="paid">Cấp Trường</option>
                                </select>
                            </div>
                            <button class="btn btn-primary filter-apply-btn">Apply Filters</button>
                        </div>
                    </div><!-- End Filter Widget -->

                    <!-- Upcoming Events Widget -->
                    <div class="sidebar-widget upcoming-widget" data-aos="fade-up" data-aos-delay="400">
                        <h4 class="widget-title">Upcoming Events</h4>
                        <div class="upcoming-list">

                            <div class="upcoming-item">
                                <div class="upcoming-date">
                                    <span class="day">18</span>
                                    <span class="month">Dec</span>
                                </div>
                                <div class="upcoming-content">
                                    <h5 class="upcoming-title">
                                        <a href="#">Python Programming Bootcamp</a>
                                    </h5>
                                    <div class="upcoming-meta">
                                        <span class="time"><i class="bi bi-clock"></i> 3:00 PM</span>
                                        <span class="price">$95</span>
                                    </div>
                                </div>
                            </div>

                            <div class="upcoming-item">
                                <div class="upcoming-date">
                                    <span class="day">25</span>
                                    <span class="month">Dec</span>
                                </div>
                                <div class="upcoming-content">
                                    <h5 class="upcoming-title">
                                        <a href="#">Digital Marketing Strategies</a>
                                    </h5>
                                    <div class="upcoming-meta">
                                        <span class="time"><i class="bi bi-clock"></i> 7:00 PM</span>
                                        <span class="price free">Free</span>
                                    </div>
                                </div>
                            </div>

                            <div class="upcoming-item">
                                <div class="upcoming-date">
                                    <span class="day">02</span>
                                    <span class="month">Jan</span>
                                </div>
                                <div class="upcoming-content">
                                    <h5 class="upcoming-title">
                                        <a href="#">UX Design Fundamentals</a>
                                    </h5>
                                    <div class="upcoming-meta">
                                        <span class="time"><i class="bi bi-clock"></i> 1:00 PM</span>
                                        <span class="price">$125</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div><!-- End Upcoming Events Widget -->



                </div>

            </div>

        </div>

    </section><!-- /Courses Events Section -->

</main>


<?php
layout('footer'); ?>