<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
layout('header');
layout('navbar');
?>
<main class="main">

    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Tên sự kiện</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?php echo _HOST_URL ?>">Home</a></li>
                    <li class="current">Tên sự kiện</li>
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
                            <h1>Tên sự kiện</h1>
                            <p class="course-subtitle">Mô tả: Master modern web development with React, Node.js, and
                                MongoDB in
                                this comprehensive hands-on course</p>

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

                    <!-- Course Navigation Tabs -->
                    <div class="course-nav-tabs" data-aos="fade-up" data-aos-delay="300">
                        <ul class="nav nav-tabs" id="course-detailsCourseTab" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" id="course-detailsoverview-tab" data-bs-toggle="tab"
                                    data-bs-target="#course-detailsoverview" type="button" role="tab">
                                    <i class="bi bi-layout-text-window-reverse"></i>
                                    Thông tin
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="course-detailscurriculum-tab" data-bs-toggle="tab"
                                    data-bs-target="#course-detailscurriculum" type="button" role="tab">
                                    <i class="bi bi-list-ul"></i>
                                    Vòng thi
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="course-detailsreviews-tab" data-bs-toggle="tab"
                                    data-bs-target="#course-detailsreviews" type="button" role="tab">
                                    <i class="bi bi-star"></i>
                                    Reviews
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="course-detailsCourseTabContent">

                            <!-- Overview Tab -->
                            <div class="tab-pane fade show active" id="course-detailsoverview" role="tabpanel">

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

                            </div><!-- End Overview Tab -->

                            <!-- Curriculum Tab -->


                            <div class="tab-pane fade" id="course-detailscurriculum" role="tabpanel">

                                <ul class="nav nav-tabs" id="course-detailsCourseTab" role="tablist">
                                    <li class="nav-item">
                                        <button class="nav-link active" id="course-detailsoverview-tab"
                                            data-bs-toggle="tab" data-bs-target="#course-detailsoverview" type="button"
                                            role="tab">
                                            <i class="bi bi-layout-text-window-reverse"></i>
                                            Thông tin
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link" id="course-detailscurriculum-tab" data-bs-toggle="tab"
                                            data-bs-target="#course-detailscurriculum" type="button" role="tab">
                                            <i class="bi bi-list-ul"></i>
                                            Vòng thi
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link" id="course-detailsreviews-tab" data-bs-toggle="tab"
                                            data-bs-target="#course-detailsreviews" type="button" role="tab">
                                            <i class="bi bi-star"></i>
                                            Reviews
                                        </button>
                                    </li>
                                </ul>



                            </div><!-- End Curriculum Tab -->

                            <!-- Reviews Tab -->
                            <div class="tab-pane fade" id="course-detailsreviews" role="tabpanel">

                                <div class="reviews-summary">
                                    <div class="rating-overview">
                                        <div class="overall-rating">
                                            <div class="rating-number">4.8</div>
                                            <div class="rating-stars">
                                                <i class="bi bi-star-fill"></i>
                                                <i class="bi bi-star-fill"></i>
                                                <i class="bi bi-star-fill"></i>
                                                <i class="bi bi-star-fill"></i>
                                                <i class="bi bi-star-half"></i>
                                            </div>
                                            <div class="rating-text">1,247 reviews</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="reviews-list">
                                    <div class="review-item">
                                        <div class="reviewer-info">
                                            <img src="?/assets/img/person/person-f-12.webp" alt="Reviewer"
                                                class="reviewer-avatar">
                                            <div class="reviewer-details">
                                                <h6>Jessica Chen</h6>
                                                <div class="review-rating">
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star-fill"></i>
                                                </div>
                                            </div>
                                            <span class="review-date">2 weeks ago</span>
                                        </div>
                                        <p class="review-text">Excepteur sint occaecat cupidatat non proident, sunt in
                                            culpa qui officia deserunt mollit anim id est laborum. The instructor
                                            explains complex concepts very clearly.</p>
                                    </div>

                                    <div class="review-item">
                                        <div class="reviewer-info">
                                            <img src="?/assets/img/person/person-m-5.webp" alt="Reviewer"
                                                class="reviewer-avatar">
                                            <div class="reviewer-details">
                                                <h6>David Thompson</h6>
                                                <div class="review-rating">
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star-fill"></i>
                                                    <i class="bi bi-star"></i>
                                                </div>
                                            </div>
                                            <span class="review-date">1 month ago</span>
                                        </div>
                                        <p class="review-text">Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                                            Great practical examples and real-world projects that helped me understand
                                            the concepts better.</p>
                                    </div>

                                </div>

                            </div><!-- End Reviews Tab -->

                        </div>
                    </div><!-- End Course Navigation Tabs -->

                </div>

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

                </div>

            </div>

        </div>

    </section><!-- /Course Details Section -->

</main>
<?php
layout('footer'); ?>