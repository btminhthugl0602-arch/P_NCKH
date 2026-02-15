<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
layout('header');
layput('navbar');
?>
<main class="main">

    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Thông tin cá nhân</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="index.html">Home</a></li>
                    <li class="current">Thông tin cá nhân</li>
                </ol>
            </nav>
        </div>
    </div><!-- End Page Title -->

    <!-- Instructor Profile Section -->
    <section id="instructor-profile" class="instructor-profile section">

        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <div class="row">

                <div class="col-lg-12">
                    <div class="instructor-hero-banner" data-aos="zoom-out" data-aos-delay="200">
                        <div class="hero-background">
                            <img src="assets/img/education/showcase-4.webp" alt="Background" class="img-fluid">
                            <div class="hero-overlay"></div>
                        </div>
                        <div class="hero-content">
                            <div class="instructor-avatar">
                                <img src="assets/img/education/teacher-7.webp" alt="Instructor" class="img-fluid">
                                <div class="status-badge">
                                    <i class="bi bi-patch-check-fill"></i>
                                    <span>Verified</span>
                                </div>
                            </div>
                            <div class="instructor-info">
                                <h2>Nguyễn Thanh Huyền</h2>
                                <p class="title">Công nghệ thông tin</p>
                                <div class="credentials">
                                    <span class="credential">Admin</span>
                                </div>
                                <div class="rating-overview">
                                    <p class="rating-text">Ngày tạo tài khoản: </p>
                                </div>
                                <div class="contact-actions">
                                    <a href="#" class="btn-contact">
                                        <i class="bi bi-envelope"></i>
                                        nthanhhuyen@gmail.com
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row gy-5 mt-4">

                <div class="col-lg-8">
                    <div class="content-tabs" data-aos="fade-right" data-aos-delay="300">

                        <ul class="nav nav-tabs custom-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#instructor-profile-experience" type="button" role="tab">
                                    <i class="bi bi-event"></i>
                                    Sự kiện đã tham gia
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#instructor-profile-courses" type="button" role="tab">
                                    <i class="bi bi-book"></i>
                                    Giấy chứng nhận
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content custom-tab-content">
                            <div class="tab-pane fade show active" id="instructor-profile-experience" role="tabpanel">
                                <div class="experience-grid">
                                    <div class="experience-card">
                                        <div class="timeline-marker">2019</div>
                                        <div class="experience-details">
                                            <h5>Nghiên cứu khoa học 2019</h5>
                                            <p class="institution">Sinh viên</p>
                                        </div>
                                    </div>

                                    <div class="experience-card">
                                        <div class="timeline-marker">2020</div>
                                        <div class="experience-details">
                                            <h5>Nguyên cứu khoa học 2020</h5>
                                            <p class="institution">InnovateLabs Corp</p>
                                            <p>Temporibus autem quibusdam et aut officiis debitis aut rerum
                                                necessitatibus saepe eveniet ut et voluptates repudiandae sint et
                                                molestiae non recusandae.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="instructor-profile-courses" role="tabpanel">
                                <div class="courses-grid">
                                    <div class="course-item">
                                        <div class="course-thumb">
                                            <img src="assets/img/education/courses-5.webp" alt="Course"
                                                class="img-fluid">

                                        </div>
                                        <div class="course-info">
                                            <h5>Nghiên cứu khoa học 2019</h5>

                                            <p class="price">Tải về</p>
                                        </div>
                                    </div>

                                    <div class="course-item">
                                        <div class="course-thumb">
                                            <img src="assets/img/education/courses-9.webp" alt="Course"
                                                class="img-fluid">

                                        </div>
                                        <div class="course-info">
                                            <h5>Hội thảo khoa học</h5>
                                            <p class="price">Tải về</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="instructor-profile-reviews" role="tabpanel">
                                <div class="reviews-container">
                                    <div class="review-card">
                                        <div class="review-header">
                                            <img src="assets/img/person/person-f-12.webp" alt="Student"
                                                class="reviewer-avatar">
                                            <div class="reviewer-info">
                                                <h6>Sarah Williams</h6>
                                                <p>Data Scientist at Amazon</p>
                                                <div class="review-rating">
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star-fill"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <p>"Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia
                                            deserunt mollitia animi. The hands-on approach and real-world projects made
                                            all the difference."</p>
                                    </div>

                                    <div class="review-card">
                                        <div class="review-header">
                                            <img src="assets/img/person/person-m-8.webp" alt="Student"
                                                class="reviewer-avatar">
                                            <div class="reviewer-info">
                                                <h6>David Martinez</h6>
                                                <p>ML Engineer at Tesla</p>
                                                <div class="review-rating">
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star-fill"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <p>"Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam
                                            nihil molestiae consequatur. Professor Chen's expertise is unmatched."</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="sidebar-widgets" data-aos="fade-left" data-aos-delay="300">

                        <div class="stats-widget">
                            <h4>Cài đặt</h4>
                            <div class="stats-grid">
                                <div class="stat-box">
                                    <div class="stat-content">
                                        <h5>chỉnh sửa thông tin</h5>
                                    </div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-content">
                                        <h5>Góp ý</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="achievements-widget">
                            <h4>Thông tin đăng nhập</h4>
                            <div class="achievement-list">
                                <div class="achievement-item">
                                    <div class="achievement-text">
                                        <h6>Đăng nhập 15/02/2026</h6>
                                    </div>
                                </div>
                                <div class="achievement-item">
                                    <div class="achievement-text">
                                        <h6>Đăng nhập ngày 19/11/2005</h6>
                                    </div>
                                </div>
                                <div class="achievement-item">
                                    <div class="achievement-text">
                                        <h6>Đăng nhập ngày 12/07/2025</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </section><!-- /Instructor Profile Section -->

</main>
<?php layout('footer') ?>