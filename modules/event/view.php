<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
global $conn;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die('ID không hợp lệ');
}

$sql = "SELECT * FROM sukien WHERE idSK = $id";
$result = mysqli_query($conn, $sql);
$event = mysqli_fetch_assoc($result);

if (!$event) {
    die('Không tìm thấy sự kiện');
}
// Lấy danh sách nhóm theo sự kiện
$sql = "
    SELECT n.idnhom,
           t.tennhom,
           t.mota,
           t.soluongtoida,
           t.dangtuyen,
           tb.tenTieuBan,
           COUNT(tv.idtk) as soThanhVien
    FROM nhom n
    LEFT JOIN thongtinnhom t ON n.idnhom = t.idnhom
    LEFT JOIN tieuban tb ON n.idTieuBan = tb.idTieuBan
    LEFT JOIN thanhviennhom tv 
           ON n.idnhom = tv.idnhom 
           AND tv.trangthai = 1
    WHERE n.idSK = $id
    GROUP BY n.idnhom,
             t.tennhom,
             t.mota,
             t.soluongtoida,
             t.dangtuyen,
             tb.tenTieuBan
";

// ================== XỬ LÝ TẠO NHÓM ==================
if (isset($_POST['create_group'])) {

    if (!isset($_SESSION['user_id'])) {
        die("Bạn cần đăng nhập");
    }

    $userId = (int)$_SESSION['user_id'];

    $tennhom = mysqli_real_escape_string($conn, $_POST['tennhom']);
    $mota = mysqli_real_escape_string($conn, $_POST['mota']);
    $congkhai = (int)$_POST['congkhai'];

    if (!empty($tennhom)) {

        // 1️⃣ Tạo nhóm
        $sqlInsertNhom = "INSERT INTO nhom (idSK) VALUES ($id)";
        mysqli_query($conn, $sqlInsertNhom);
        $idNhom = mysqli_insert_id($conn);

        // 2️⃣ Thông tin nhóm
        $sqlInfo = "INSERT INTO thongtinnhom 
                    (idnhom, tennhom, mota, soluongtoida, dangtuyen, congkhai)
                    VALUES 
                    ($idNhom, '$tennhom', '$mota', 5, 1, $congkhai)";
        mysqli_query($conn, $sqlInfo);

        // 3️⃣ Thêm trưởng nhóm
        $sqlMember = "INSERT INTO thanhviennhom
                      (idnhom, idtk, trangthai, truongnhom)
                      VALUES
                      ($idNhom, $userId, 1, 1)";
        mysqli_query($conn, $sqlMember);

        // reload trang
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}
$result = mysqli_query($conn, $sql);
$groups = mysqli_fetch_all($result, MYSQLI_ASSOC);
// Lấy nhóm của user hiện tại
$myGroups = [];

if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];

    $sqlMy = "
    SELECT n.idnhom,
           t.tennhom,
           t.mota,
           t.soluongtoida,
           tb.tenTieuBan,
           COUNT(tv2.idtk) as soThanhVien
    FROM nhom n
    JOIN thanhviennhom tv 
         ON n.idnhom = tv.idnhom
    LEFT JOIN thongtinnhom t 
         ON n.idnhom = t.idnhom
    LEFT JOIN tieuban tb 
         ON n.idTieuBan = tb.idTieuBan
    LEFT JOIN thanhviennhom tv2
         ON n.idnhom = tv2.idnhom
         AND tv2.trangthai = 1
    WHERE tv.idtk = $userId
      AND tv.trangthai = 1
      AND n.idSK = $id
    GROUP BY n.idnhom, 
             t.tennhom, 
             t.mota, 
             t.soluongtoida,
             tb.tenTieuBan
";

    $resultMy = mysqli_query($conn, $sqlMy);
    $myGroups = mysqli_fetch_all($resultMy, MYSQLI_ASSOC);
}
layout('header');
layout('navbar');
?>
<main class="main">

    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
           <h1 class="mb-2 mb-lg-0"><?= htmlspecialchars($event['tenSK']) ?></h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?php echo _HOST_URL ?>">Home</a></li>
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
                                <span class="category">Chủ đề</span>
                                <span class="level">Cấp: Khoa</span>
                            </div>
                            <h1><?= htmlspecialchars($event['tenSK']) ?></h1>
                            <p class="course-subtitle">
    <?= nl2br(htmlspecialchars($event['moTa'])) ?>
</p>

                            <div class="instructor-card">
                                <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/person/person-m-8.webp"
                                    alt="Instructor" class="instructor-image">
                                <div class="instructor-details">
                                    <h5>Hội đồng tổ chức</h5>
                                    <span>Khoa CNTT - Trường Đại học Sư phạm Hà Nội</span>
                                    <div class="instructor-rating">
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-half"></i>
                                        <span>4.8 (1,247 reviews)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="hero-image">
                            <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/education/courses-8.webp"
                                alt="Course Preview" class="img-fluid">
                            <div class="play-overlay">
                                <button class="play-btn">
                                    <i class="bi bi-play-fill"></i>
                                </button>
                                <span>Ảnh sự kiện </span>
                            </div>
                        </div>
                    </div><!-- End Course Hero -->

                    <!-- Điều hướng sự kiện -->
                    <div class="course-nav-tabs" data-aos="fade-up" data-aos-delay="300">
                        <ul class="nav nav-tabs" id="EventDetails" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" id="event-info-tab" data-bs-toggle="tab"
                                    data-bs-target="#event-info" type="button" role="tab">
                                    <i class="bi bi-layout-text-window-reverse"></i>
                                    Thông tin
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="event-groups-tab" data-bs-toggle="tab"
                                    data-bs-target="#event-groups" type="button" role="tab">
                                    <i class="bi bi-list-ul"></i>
                                    Nhóm thi
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="event-config-tab" data-bs-toggle="tab"
                                    data-bs-target="#event-config" type="button" role="tab">
                                    <i class="bi bi-star"></i>
                                    Cấu hình sự kiện
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="EventDetailsContent">

                            <!-- Thông tin Tab -->
                            <div class="tab-pane fade show active" id="event-info" role="tabpanel">

                                <div class="overview-section">
                                    <h3>Chi tiết sự kiện</h3>
                                    <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium
                                        doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore
                                        veritatis et quasi architecto beatae vitae dicta sunt explicabo.</p>
                                    <p>Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed
                                        quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.</p>
                                </div>

                                <div class="skills-grid">
                                    <h3>Skills You'll Gain</h3>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="skill-item">
                                                <div class="skill-icon">
                                                    <i class="bi bi-code-slash"></i>
                                                </div>
                                                <div class="skill-content">
                                                    <h5>Frontend Development</h5>
                                                    <p>React, JavaScript ES6+, HTML5 &amp; CSS3</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="skill-item">
                                                <div class="skill-icon">
                                                    <i class="bi bi-server"></i>
                                                </div>
                                                <div class="skill-content">
                                                    <h5>Backend Development</h5>
                                                    <p>Node.js, Express.js, RESTful APIs</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="skill-item">
                                                <div class="skill-icon">
                                                    <i class="bi bi-database"></i>
                                                </div>
                                                <div class="skill-content">
                                                    <h5>Database Management</h5>
                                                    <p>MongoDB, Mongoose, Data Modeling</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="skill-item">
                                                <div class="skill-icon">
                                                    <i class="bi bi-shield-check"></i>
                                                </div>
                                                <div class="skill-content">
                                                    <h5>Security &amp; Testing</h5>
                                                    <p>Authentication, JWT, Unit Testing</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="requirements-section">
                                    <h3>Requirements</h3>
                                    <ul class="requirements-list">
                                        <li><i class="bi bi-check2"></i>Basic understanding of HTML and CSS</li>
                                        <li><i class="bi bi-check2"></i>Familiarity with JavaScript fundamentals</li>
                                        <li><i class="bi bi-check2"></i>Computer with internet connection</li>
                                        <li><i class="bi bi-check2"></i>Text editor or IDE installed</li>
                                    </ul>
                                </div>

                            </div><!-- End Thông tin Tab -->

                            <!-- Nhóm Tab -->
                            <div class="tab-pane fade" id="event-groups" role="tabpanel">

                                <div class="course-nav-tabs" data-aos="fade-up" data-aos-delay="300">
                                    <ul class="nav nav-tabs" id="course-detailsCourseTab" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link active" id="all-groups-tab" data-bs-toggle="tab"
                                                data-bs-target="#all-groups" type="button" role="tab">
                                                <i class="bi bi-layout-text-window-reverse"></i>
                                                Tất cả
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" id="my-groups-tab" data-bs-toggle="tab"
                                                data-bs-target="#my-groups" type="button" role="tab">
                                                <i class="bi bi-list-ul"></i>
                                                Nhóm của tôi
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content" id="course-detailsCourseTabContent">

                                        <!-- Tất cả nhóm Tab -->
                                        <div class="tab-pane fade show active" id="all-groups" role="tabpanel">

                                            <!-- Courses 2 Section -->
                                            <section id="courses-2" class="courses-2 section">

                                                <div class="container" data-aos="fade-up" data-aos-delay="100">

                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div class="courses-header" data-aos="fade-left"
                                                                data-aos-delay="100">
                                                                <div class="search-box">
                                                                    <i class="bi bi-search"></i>
                                                                    <input type="text" placeholder="Tìm kiếm nhóm...">
                                                                </div>
                                                                <div class="sort-dropdown">
                                                                    <select>
                                                                        <option>Sắp xếp theo: Tất cả</option>
                                                                        <option>Nhóm của tôi</option>
                                                                        <option>Thành viên: Ít tới nhiều</option>
                                                                        <option>Thành viên: Nhiều tới ít</option>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="courses-grid" data-aos="fade-up"
                                                                data-aos-delay="200">
                                                                <div class="row">
        <?php if (!empty($groups)): ?>
            <?php foreach ($groups as $group): ?>

                <div class="col-lg-6 col-md-6">
                    <div class="course-card">
                        <div class="course-content">
                            <h3><?= htmlspecialchars($group['tennhom']) ?></h3>

<p class="text-muted mb-1">
    <i class="bi bi-diagram-3"></i>
    Tiểu ban: 
    <?= htmlspecialchars($group['tenTieuBan'] ?? 'Chưa phân') ?>
</p>

<p><?= htmlspecialchars($group['mota']) ?></p>

                            <div class="course-stats">
                                <div class="stat">
                                    <i class="bi bi-people"></i>
                                    <span>
                                        <?= $group['soThanhVien'] ?> /
                                        <?= $group['soluongtoida'] ?> thành viên
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            <?php else: ?>
    <div class="col-12">
        <p>Chưa có nhóm nào.</p>
    </div>
<?php endif; ?>
    </div>
                                                            <div class="pagination-wrapper" data-aos="fade-up"
                                                                data-aos-delay="300">
                                                                <nav aria-label="Courses pagination">
                                                                    <ul class="pagination justify-content-center">
                                                                        <li class="page-item disabled">
                                                                            <a class="page-link" href="#" tabindex="-1"
                                                                                aria-disabled="true">
                                                                                <i class="bi bi-chevron-left"></i>
                                                                            </a>
                                                                        </li>
                                                                        <li class="page-item active">
                                                                            <a class="page-link" href="#">1</a>
                                                                        </li>
                                                                        <li class="page-item">
                                                                            <a class="page-link" href="#">2</a>
                                                                        </li>
                                                                        <li class="page-item">
                                                                            <a class="page-link" href="#">3</a>
                                                                        </li>
                                                                        <li class="page-item">
                                                                            <a class="page-link" href="#">
                                                                                <i class="bi bi-chevron-right"></i>
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </nav>
                                                            </div><!-- End Pagination -->

                                                        </div>
                                                    </div>

                                                </div>

                                            </section><!-- /Courses 2 Section -->

                                        </div><!-- End Tất cả nhóm Tab -->

                                        <!-- Nhóm của tôi Tab -->
                                        <div class="tab-pane fade" id="my-groups" role="tabpanel">

                                            <section id="courses-2" class="courses-2 section">

                                                <div class="container" data-aos="fade-up" data-aos-delay="100">

                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div class="courses-header d-flex justify-content-between align-items-center" 
     data-aos="fade-left" data-aos-delay="100">

    <div class="search-box">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="Tìm kiếm nhóm...">
    </div>

    <div class="d-flex gap-2">
        <div class="sort-dropdown">
            <select>
                <option>Sắp xếp theo: Tất cả</option>
                <option>Thành viên: Ít tới nhiều</option>
                <option>Thành viên: Nhiều tới ít</option>
            </select>
        </div>

        <!-- Nút tạo nhóm -->
        <button class="btn btn-primary"
        data-bs-toggle="modal"
        data-bs-target="#createGroupModal">
    Tạo nhóm
</button>
    </div>

</div>

                                                            
                                                                    <div class="courses-grid" data-aos="fade-up" data-aos-delay="200">
    <div class="row">

        <?php if (!empty($myGroups)): ?>
            <?php foreach ($myGroups as $group): ?>

                <div class="col-lg-6 col-md-6">
                    <div class="course-card">
                        <div class="course-content">
                            <h3><?= htmlspecialchars($group['tennhom']) ?></h3>

<p class="text-muted mb-1">
    <i class="bi bi-diagram-3"></i>
    Tiểu ban: 
    <?= htmlspecialchars($group['tenTieuBan'] ?? 'Chưa phân') ?>
</p>

<p><?= htmlspecialchars($group['mota']) ?></p>

                            <div class="course-stats">
                                <div class="stat">
                                    <i class="bi bi-people"></i>
                                    <span>
                                        <?= $group['soThanhVien'] ?> /
                                        <?= $group['soluongtoida'] ?> thành viên
                                    </span>
                                </div>
                            </div>

                            <a href="<?= _HOST_URL ?>?module=group&action=view&id=<?= $group['idnhom'] ?>"
                               class="btn-course">
                                Xem nhóm
                            </a>

                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

        <?php else: ?>
            <div class="col-12 text-center">
                <p>Bạn chưa tham gia nhóm nào.</p>
            </div>
        <?php endif; ?>
</div><!-- End Course Card -->
                                                                    </div>
<!-- End Course Card -->
                                                                    </div>

                                                                </div>
                                                            </div><!-- End Courses Grid -->

                                                        </div>
                                                    </div>

                                                </div>

                                            </section><!-- /Courses 2 Section -->

                                        </div><!-- End Nhóm của tôi Tab -->

                                    </div><!-- End course-detailsCourseTabContent -->
                                </div><!-- End course-nav-tabs inner -->

                            </div><!-- End event-groups tab-pane -->

                            <!-- Cấu hình Tab -->
                            <div class="tab-pane fade" id="event-config" role="tabpanel">
                                <div class="event-config-content" data-aos="fade-up" data-aos-delay="100">
                                    <h3>Cấu hình sự kiện</h3>
                                    <p>Đây là nơi bạn có thể cấu hình các thiết lập liên quan đến sự kiện, bao gồm:</p>
                                    <ul>
                                        <li><strong>Thông tin cơ bản:</strong> <button
                                                class="btn btn-primary">Nút1</button>
                                        </li>
                                        <li><strong>Phân công phản biện</strong> <button
                                                class="btn btn-primary">Nút2</button></li>
                                        <li><strong>Thống kê bài nộp</strong> <button
                                                class="btn btn-primary">Nút3</button></li>
                                        <li><strong>Quản lý tiểu ban</strong> <button class="btn btn-primary">
                                                Nút4</button></li>
                                        <li><strong>Phân công BGK</strong> <button class="btn btn-primary">Nút5</button>
                                        </li>
                                        <li><strong>Trao giải</strong> <button class="btn btn-primary">Nút6</button>
                                        </li>
                                    </ul>
                                    <p>Sau khi cấu hình xong, đừng quên lưu lại để áp dụng các thay đổi!<button
                                            class="btn btn-primary">Lưu</button></p>


                                </div><!-- End Cấu hình Tab -->

                            </div><!-- End EventDetailsContent -->
                        </div><!-- End course-nav-tabs -->

                    </div><!-- End col-lg-8 -->

                    <div class="col-lg-4">

                        <!-- Enrollment Card -->
                        <div class="enrollment-card d-none" data-aos="fade-up" data-aos-delay="200">

                            <div class="card-header">
                                <div class="price-display">
                                    <span class="current-price">$149</span>
                                    <span class="original-price">$249</span>
                                    <span class="discount">40% OFF</span>
                                </div>
                                <div class="enrollment-count">
                                    <i class="bi bi-people"></i>
                                    <span>3,892 students enrolled</span>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="course-highlights">
                                    <div class="highlight-item">
                                        <i class="bi bi-trophy"></i>
                                        <span>Certificate included</span>
                                    </div>
                                    <div class="highlight-item">
                                        <i class="bi bi-clock-history"></i>
                                        <span>45 hours content</span>
                                    </div>
                                    <div class="highlight-item">
                                        <i class="bi bi-download"></i>
                                        <span>Downloadable resources</span>
                                    </div>
                                    <div class="highlight-item">
                                        <i class="bi bi-infinity"></i>
                                        <span>Lifetime access</span>
                                    </div>
                                    <div class="highlight-item">
                                        <i class="bi bi-phone"></i>
                                        <span>Mobile access</span>
                                    </div>
                                </div>

                                <div class="action-buttons">
                                    <button class="btn-primary">Enroll Now</button>
                                    <button class="btn-secondary">Add to Wishlist</button>
                                </div>

                                <div class="guarantee">
                                    <i class="bi bi-shield-check"></i>
                                    <span>30-day money-back guarantee</span>
                                </div>
                            </div>

                        </div><!-- End Enrollment Card -->

                        <!-- Course Details -->
                        <div class="course-details-card" data-aos="fade-up" data-aos-delay="300">
                            <h4>Course Details</h4>

                            <div class="detail-grid">
                                <div class="detail-row">
                                    <span class="detail-label">Duration</span>
                                    <span class="detail-value">16 weeks</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Skill Level</span>
                                    <span class="detail-value">Intermediate</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Language</span>
                                    <span class="detail-value">English</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Quizzes</span>
                                    <span class="detail-value">24</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Assignments</span>
                                    <span class="detail-value">8 projects</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Updated</span>
                                    <span class="detail-value">December 2024</span>
                                </div>
                            </div>
                        </div><!-- End Course Details -->

                        <!-- Share Course -->
                        <div class="share-course-card" data-aos="fade-up" data-aos-delay="400">
                            <h4>Share This Course</h4>
                            <div class="social-links">
                                <a href="#" class="social-link facebook">
                                    <i class="bi bi-facebook"></i>
                                </a>
                                <a href="#" class="social-link twitter">
                                    <i class="bi bi-twitter"></i>
                                </a>
                                <a href="#" class="social-link linkedin">
                                    <i class="bi bi-linkedin"></i>
                                </a>
                                <a href="#" class="social-link email">
                                    <i class="bi bi-envelope"></i>
                                </a>
                            </div>
                        </div><!-- End Share Course -->

                    </div><!-- End col-lg-4 -->

                </div><!-- End row -->

            </div><!-- End container -->

    </section><!-- /Course Details Section -->

</main>
<!-- ================= MODAL TẠO NHÓM ================= -->
<div class="modal fade" id="createGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <form method="POST">

                <div class="modal-header">
                    <h5 class="modal-title">Tạo nhóm mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Tên nhóm</label>
                        <input type="text" name="tennhom" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mô tả nhóm</label>
                        <textarea name="mota" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Loại nhóm</label>

                        <div class="form-check">
                            <input class="form-check-input" type="radio"
                                   name="congkhai" value="1" checked>
                            <label class="form-check-label">
                                Công khai
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio"
                                   name="congkhai" value="0">
                            <label class="form-check-label">
                                Riêng tư
                            </label>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Huỷ
                    </button>

                    <button type="submit"
                            name="create_group"
                            class="btn btn-primary">
                        Tạo nhóm
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
<?php
layout('footer');
?>