<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/quan_ly_su_kien.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$event = btc_lay_chi_tiet_su_kien($conn, $id_su_kien);

if (!$event) {
    require_once _PATH_URL . '/modules/errors/404.php';
    exit;
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
                                <span class="category">Sự kiện</span>
                                <span class="level">Cấp: <?= htmlspecialchars($event['tenCap'] ?? 'Chưa rõ') ?></span>
                            </div>
                            <h1><?= htmlspecialchars($event['tenSK']) ?></h1>
                            <p class="course-subtitle">Mô tả: <?= htmlspecialchars($event['moTa']) ?></p>

                            <div class="instructor-card">
                                <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/person/person-m-8.webp"
                                    alt="Instructor" class="instructor-image">
                                <div class="instructor-details">
                                    <h5>Hội đồng tổ chức</h5>
                                    <span><?= htmlspecialchars($event['nguoiTaoTen'] ?? 'BTC') ?></span>
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
                                    <p><?= nl2br(htmlspecialchars($event['moTa'])) ?></p>
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
                                                                    <div class="col-lg-6 col-md-6">
                                                                        <div class="course-card">
                                                                            <div class="course-image">
                                                                                <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/education/courses-3.webp"
                                                                                    alt="Course" class="img-fluid">
                                                                                <div class="course-badge">Đã đủ thành
                                                                                    viên
                                                                                </div>
                                                                                <div class="course-price">5/5</div>
                                                                            </div>
                                                                            <div class="course-content">
                                                                                <div class="course-meta">
                                                                                    <span
                                                                                        class="category">Programming</span>
                                                                                    <span
                                                                                        class="level">Intermediate</span>
                                                                                </div>
                                                                                <h3>Tên nhóm</h3>
                                                                                <p>Mô tả nhóm</p>
                                                                                <div class="course-stats">
                                                                                    <div class="stat">
                                                                                        <i class="bi bi-clock"></i>
                                                                                        <span>15 hours</span>
                                                                                    </div>
                                                                                    <div class="stat">
                                                                                        <i class="bi bi-people"></i>
                                                                                        <span>1,245 students</span>
                                                                                    </div>
                                                                                    <div class="rating">
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <span>5 (89 reviews)</span>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="instructor-info">
                                                                                    <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/person/person-m-3.webp"
                                                                                        alt="Instructor"
                                                                                        class="instructor-avatar">
                                                                                    <span class="instructor-name">Giảng
                                                                                        viên hướng dẫn / Nhóm
                                                                                        trưởng</span>
                                                                                </div>
                                                                                <a href="enroll.html"
                                                                                    class="btn-course">Xin vào / Đã
                                                                                    đủ</a>
                                                                            </div>
                                                                        </div><!-- End Course Card -->
                                                                    </div>

                                                                    <div class="col-lg-6 col-md-6">
                                                                        <div class="course-card">
                                                                            <div class="course-image">
                                                                                <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/education/courses-7.webp"
                                                                                    alt="Course" class="img-fluid">
                                                                                <div class="course-badge badge-free">
                                                                                    Free</div>
                                                                            </div>
                                                                            <div class="course-content">
                                                                                <div class="course-meta">
                                                                                    <span class="category">Design</span>
                                                                                    <span class="level">Beginner</span>
                                                                                </div>
                                                                                <h3>UI/UX Design Fundamentals</h3>
                                                                                <p>Mauris blandit aliquet elit, eget
                                                                                    tincidunt nibh pulvinar a.
                                                                                    Vestibulum ac diam sit amet quam
                                                                                    vehicula elementum sed sit amet.</p>
                                                                                <div class="course-stats">
                                                                                    <div class="stat">
                                                                                        <i class="bi bi-clock"></i>
                                                                                        <span>8 hours</span>
                                                                                    </div>
                                                                                    <div class="stat">
                                                                                        <i class="bi bi-people"></i>
                                                                                        <span>2,891 students</span>
                                                                                    </div>
                                                                                    <div class="rating">
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star"></i>
                                                                                        <span>4.6 (156 reviews)</span>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="instructor-info">
                                                                                    <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/person/person-f-7.webp"
                                                                                        alt="Instructor"
                                                                                        class="instructor-avatar">
                                                                                    <span class="instructor-name">Sarah
                                                                                        Johnson</span>
                                                                                </div>
                                                                                <a href="enroll.html"
                                                                                    class="btn-course">Start Free
                                                                                    Course</a>
                                                                            </div>
                                                                        </div><!-- End Course Card -->
                                                                    </div>

                                                                </div>
                                                            </div><!-- End Courses Grid -->

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
                                                                    <div class="col-lg-6 col-md-6">
                                                                        <div class="course-card">
                                                                            <div class="course-image">
                                                                                <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/education/courses-3.webp"
                                                                                    alt="Course" class="img-fluid">
                                                                                <div class="course-badge">Đã đủ thành
                                                                                    viên
                                                                                </div>
                                                                                <div class="course-price">5/5</div>
                                                                            </div>
                                                                            <div class="course-content">
                                                                                <div class="course-meta">
                                                                                    <span
                                                                                        class="category">Programming</span>
                                                                                    <span
                                                                                        class="level">Intermediate</span>
                                                                                </div>
                                                                                <h3>Tên nhóm</h3>
                                                                                <p>Mô tả nhóm</p>
                                                                                <div class="course-stats">
                                                                                    <div class="stat">
                                                                                        <i class="bi bi-clock"></i>
                                                                                        <span>15 hours</span>
                                                                                    </div>
                                                                                    <div class="stat">
                                                                                        <i class="bi bi-people"></i>
                                                                                        <span>1,245 students</span>
                                                                                    </div>
                                                                                    <div class="rating">
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <span>5 (89 reviews)</span>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="instructor-info">
                                                                                    <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/person/person-m-3.webp"
                                                                                        alt="Instructor"
                                                                                        class="instructor-avatar">
                                                                                    <span class="instructor-name">Giảng
                                                                                        viên hướng dẫn / Nhóm
                                                                                        trưởng</span>
                                                                                </div>
                                                                                <a href="enroll.html"
                                                                                    class="btn-course">Xin vào / Đã
                                                                                    đủ</a>
                                                                            </div>
                                                                        </div><!-- End Course Card -->
                                                                    </div>

                                                                    <div class="col-lg-6 col-md-6">
                                                                        <div class="course-card">
                                                                            <div class="course-image">
                                                                                <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/education/courses-7.webp"
                                                                                    alt="Course" class="img-fluid">
                                                                                <div class="course-badge badge-free">
                                                                                    Free</div>
                                                                            </div>
                                                                            <div class="course-content">
                                                                                <div class="course-meta">
                                                                                    <span class="category">Design</span>
                                                                                    <span class="level">Beginner</span>
                                                                                </div>
                                                                                <h3>UI/UX Design Fundamentals</h3>
                                                                                <p>Mauris blandit aliquet elit, eget
                                                                                    tincidunt nibh pulvinar a.
                                                                                    Vestibulum ac diam sit amet quam
                                                                                    vehicula elementum sed sit amet.</p>
                                                                                <div class="course-stats">
                                                                                    <div class="stat">
                                                                                        <i class="bi bi-clock"></i>
                                                                                        <span>8 hours</span>
                                                                                    </div>
                                                                                    <div class="stat">
                                                                                        <i class="bi bi-people"></i>
                                                                                        <span>2,891 students</span>
                                                                                    </div>
                                                                                    <div class="rating">
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                        <i class="bi bi-star"></i>
                                                                                        <span>4.6 (156 reviews)</span>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="instructor-info">
                                                                                    <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/person/person-f-7.webp"
                                                                                        alt="Instructor"
                                                                                        class="instructor-avatar">
                                                                                    <span class="instructor-name">Sarah
                                                                                        Johnson</span>
                                                                                </div>
                                                                                <a href="enroll.html"
                                                                                    class="btn-course">Start Free
                                                                                    Course</a>
                                                                            </div>
                                                                        </div><!-- End Course Card -->
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
                                    <p>Chọn khu vực cấu hình phù hợp để thiết lập quy chế, vòng thi và bộ tiêu chí.</p>

                                    <div class="d-flex flex-column gap-2">
                                        <a class="btn btn-primary" href="<?php echo _HOST_URL ?>/?module=event&action=config_rules&id=<?php echo (int)$id_su_kien; ?>">
                                            Quy chế & Điều kiện
                                        </a>
                                        <a class="btn btn-primary" href="<?php echo _HOST_URL ?>/?module=event&action=config_rounds&id=<?php echo (int)$id_su_kien; ?>">
                                            Vòng thi
                                        </a>
                                        <a class="btn btn-primary" href="<?php echo _HOST_URL ?>/?module=event&action=config_criteria&id=<?php echo (int)$id_su_kien; ?>">
                                            Bộ tiêu chí & Chấm điểm
                                        </a>
                                        <a class="btn btn-primary" href="<?php echo _HOST_URL ?>/?module=event&action=config_assign&id=<?php echo (int)$id_su_kien; ?>">
                                            Phân công chấm
                                        </a>
                                        <a class="btn btn-primary" href="<?php echo _HOST_URL ?>/?module=event&action=config_schedule&id=<?php echo (int)$id_su_kien; ?>">
                                            Lập lịch tổ chức
                                        </a>
                                    </div>
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
<?php
layout('footer');
?>