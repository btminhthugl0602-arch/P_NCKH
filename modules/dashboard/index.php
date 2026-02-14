<?php
if (!defined('_AUTHEN')) {
  die('Truy cập không hợp lệ');
}
// Kiểm tra đăng nhập
// if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
//   header("Location: " . _HOST_URL . "?module=auth&action=login");
//   exit();
// }
$active_page = 'dashboard';
$data = [
  'page_title' => 'Dashboard'
];
// Include header
layout('header', $data);
layout('navbar', $data);
?>
<main class="main">

    <!-- Courses Hero Section -->
    <section id="courses-hero" class="courses-hero section light-background">

        <div class="hero-content">
            <div class="container">
                <div class="row align-items-center">

                    <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="hero-text">
                            <h1>Nền tảng tổ chức & quản lý sự kiện</h1>
                            <p>Hệ thống hỗ trợ quản lý đăng ký, chấm điểm, phân công giảng viên
                                và theo dõi tiến độ các cuộc thi, sự kiện nghiên cứu khoa học.</p>

                            <div class="hero-stats">
                                <div class="stat-item">
                                    <span class="number purecounter" data-purecounter-start="0"
                                        data-purecounter-end="50000" data-purecounter-duration="2"></span>
                                    <span class="label">Người dùng</span>
                                </div>
                                <div class="stat-item">
                                    <span class="number purecounter" data-purecounter-start="0"
                                        data-purecounter-end="1200" data-purecounter-duration="2"></span>
                                    <span class="label">Sự kiện</span>
                                </div>
                            </div>

                            <div class="hero-buttons">
                                <a href="#courses" class="btn btn-primary">Khoá học</a>
                            </div>

                            <div class="hero-features">
                                <div class="feature">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Certified Programs</span>
                                </div>
                                <div class="feature">
                                    <i class="bi bi-clock"></i>
                                    <span>Lifetime Access</span>
                                </div>
                                <div class="feature">
                                    <i class="bi bi-people"></i>
                                    <span>Expert Instructors</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
                        <div class="hero-image">
                            <div class="main-image">
                                <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/anh1.jpg" alt="Online Learning"
                                    class="img-fluid">
                            </div>

                            <div class="floating-cards">
                                <div class="course-card" data-aos="fade-up" data-aos-delay="300">
                                    <div class="card-icon">
                                        <i class="bi bi-code-slash"></i>
                                    </div>
                                    <div class="card-content">
                                        <h6>Hội nghị ghiên cứu khoa học</h6>
                                    </div>
                                </div>

                                <div class="course-card" data-aos="fade-up" data-aos-delay="400">
                                    <div class="card-icon">
                                        <i class="bi bi-palette"></i>
                                    </div>
                                    <div class="card-content">
                                        <h6>Hội thảo khoa học</h6>
                                    </div>
                                </div>

                                <div class="course-card" data-aos="fade-up" data-aos-delay="500">
                                    <div class="card-icon">
                                        <i class="bi bi-graph-up"></i>
                                    </div>
                                    <div class="card-content">
                                        <h6>Tranh biện</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="hero-background">
            <div class="bg-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>

    </section><!-- /Courses Hero Section -->

    <!-- Featured Courses Section -->
    <section id="featured-courses" class="featured-courses section">

        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up">
            <h2>Sự kiện</h2>
            <p>Các sự kiện được công khai trên web</p>
        </div><!-- End Section Title -->

        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <div class="row gy-4">

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="course-card">
                        <div class="course-image">
                            <img src="assets/img/education/students-9.webp" alt="Course" class="img-fluid">
                            <div class="badge featured">Featured</div>
                            <div class="price-badge">$149</div>
                        </div>
                        <div class="course-content">
                            <div class="course-meta">
                                <span class="level">Beginner</span>
                                <span class="duration">8 Weeks</span>
                            </div>
                            <h3><a href="#">Digital Marketing Fundamentals</a></h3>
                            <p>Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt
                                ut labore et dolore magna aliqua enim ad minim veniam.</p>
                            <div class="instructor">
                                <img src="assets/img/person/person-f-3.webp" alt="Instructor" class="instructor-img">
                                <div class="instructor-info">
                                    <h6>Sarah Johnson</h6>
                                    <span>Marketing Expert</span>
                                </div>
                            </div>
                            <div class="course-stats">
                                <div class="rating">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-half"></i>
                                    <span>(4.5)</span>
                                </div>
                                <div class="students">
                                    <i class="bi bi-people-fill"></i>
                                    <span>342 students</span>
                                </div>
                            </div>
                            <a href="enroll.html" class="btn-course">Enroll Now</a>
                        </div>
                    </div>
                </div><!-- End Course Item -->

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="course-card">
                        <div class="course-image">
                            <img src="assets/img/education/campus-4.webp" alt="Course" class="img-fluid">
                            <div class="badge new">New</div>
                            <div class="price-badge">$89</div>
                        </div>
                        <div class="course-content">
                            <div class="course-meta">
                                <span class="level">Intermediate</span>
                                <span class="duration">6 Weeks</span>
                            </div>
                            <h3><a href="#">Web Development with JavaScript</a></h3>
                            <p>Ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea
                                commodo consequat duis aute irure dolor in reprehenderit.</p>
                            <div class="instructor">
                                <img src="assets/img/person/person-m-5.webp" alt="Instructor" class="instructor-img">
                                <div class="instructor-info">
                                    <h6>Michael Chen</h6>
                                    <span>Full Stack Developer</span>
                                </div>
                            </div>
                            <div class="course-stats">
                                <div class="rating">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <span>(5.0)</span>
                                </div>
                                <div class="students">
                                    <i class="bi bi-people-fill"></i>
                                    <span>156 students</span>
                                </div>
                            </div>
                            <a href="enroll.html" class="btn-course">Enroll Now</a>
                        </div>
                    </div>
                </div><!-- End Course Item -->

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="course-card">
                        <div class="course-image">
                            <img src="assets/img/education/students-7.webp" alt="Course" class="img-fluid">
                            <div class="badge certificate">Certificate</div>
                            <div class="price-badge">Free</div>
                        </div>
                        <div class="course-content">
                            <div class="course-meta">
                                <span class="level">Beginner</span>
                                <span class="duration">4 Weeks</span>
                            </div>
                            <h3><a href="#">Introduction to Data Science</a></h3>
                            <p>Excepteur sint occaecat cupidatat non proident sunt in culpa qui officia deserunt mollit
                                anim id est laborum sed ut perspiciatis unde omnis.</p>
                            <div class="instructor">
                                <img src="assets/img/person/person-f-7.webp" alt="Instructor" class="instructor-img">
                                <div class="instructor-info">
                                    <h6>Dr. Emily Watson</h6>
                                    <span>Data Scientist</span>
                                </div>
                            </div>
                            <div class="course-stats">
                                <div class="rating">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star"></i>
                                    <span>(4.2)</span>
                                </div>
                                <div class="students">
                                    <i class="bi bi-people-fill"></i>
                                    <span>789 students</span>
                                </div>
                            </div>
                            <a href="enroll.html" class="btn-course">Enroll Now</a>
                        </div>
                    </div>
                </div><!-- End Course Item -->

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="course-card">
                        <div class="course-image">
                            <img src="assets/img/education/education-5.webp" alt="Course" class="img-fluid">
                            <div class="badge popular">Popular</div>
                            <div class="price-badge">$199</div>
                        </div>
                        <div class="course-content">
                            <div class="course-meta">
                                <span class="level">Advanced</span>
                                <span class="duration">12 Weeks</span>
                            </div>
                            <h3><a href="#">Business Strategy &amp; Leadership</a></h3>
                            <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque
                                laudantium totam rem aperiam eaque ipsa quae ab illo.</p>
                            <div class="instructor">
                                <img src="assets/img/person/person-m-8.webp" alt="Instructor" class="instructor-img">
                                <div class="instructor-info">
                                    <h6>Robert Anderson</h6>
                                    <span>Business Consultant</span>
                                </div>
                            </div>
                            <div class="course-stats">
                                <div class="rating">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-half"></i>
                                    <span>(4.7)</span>
                                </div>
                                <div class="students">
                                    <i class="bi bi-people-fill"></i>
                                    <span>234 students</span>
                                </div>
                            </div>
                            <a href="enroll.html" class="btn-course">Enroll Now</a>
                        </div>
                    </div>
                </div><!-- End Course Item -->

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="course-card">
                        <div class="course-image">
                            <img src="assets/img/education/activities-3.webp" alt="Course" class="img-fluid">
                            <div class="badge certificate">Certificate</div>
                            <div class="price-badge">$129</div>
                        </div>
                        <div class="course-content">
                            <div class="course-meta">
                                <span class="level">Intermediate</span>
                                <span class="duration">10 Weeks</span>
                            </div>
                            <h3><a href="#">Graphic Design Masterclass</a></h3>
                            <p>Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit sed quia
                                consequuntur magni dolores eos qui ratione voluptatem.</p>
                            <div class="instructor">
                                <img src="assets/img/person/person-f-12.webp" alt="Instructor" class="instructor-img">
                                <div class="instructor-info">
                                    <h6>Lisa Martinez</h6>
                                    <span>Creative Director</span>
                                </div>
                            </div>
                            <div class="course-stats">
                                <div class="rating">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star"></i>
                                    <span>(4.3)</span>
                                </div>
                                <div class="students">
                                    <i class="bi bi-people-fill"></i>
                                    <span>467 students</span>
                                </div>
                            </div>
                            <a href="enroll.html" class="btn-course">Enroll Now</a>
                        </div>
                    </div>
                </div><!-- End Course Item -->

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="course-card">
                        <div class="course-image">
                            <img src="assets/img/education/teacher-6.webp" alt="Course" class="img-fluid">
                            <div class="badge new">New</div>
                            <div class="price-badge">$99</div>
                        </div>
                        <div class="course-content">
                            <div class="course-meta">
                                <span class="level">Beginner</span>
                                <span class="duration">5 Weeks</span>
                            </div>
                            <h3><a href="#">Photography for Beginners</a></h3>
                            <p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium
                                voluptatum deleniti atque corrupti quos dolores et quas.</p>
                            <div class="instructor">
                                <img src="assets/img/person/person-m-11.webp" alt="Instructor" class="instructor-img">
                                <div class="instructor-info">
                                    <h6>James Wilson</h6>
                                    <span>Professional Photographer</span>
                                </div>
                            </div>
                            <div class="course-stats">
                                <div class="rating">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-half"></i>
                                    <span>(4.6)</span>
                                </div>
                                <div class="students">
                                    <i class="bi bi-people-fill"></i>
                                    <span>298 students</span>
                                </div>
                            </div>
                            <a href="enroll.html" class="btn-course">Enroll Now</a>
                        </div>
                    </div>
                </div><!-- End Course Item -->

            </div>

            <div class="more-courses text-center" data-aos="fade-up" data-aos-delay="500">
                <a href="courses.html" class="btn-more">Xem tất cả sự kiện</a>
            </div>

        </div>

    </section><!-- /Featured Courses Section -->

    <!-- Course Categories Section -->
    <section id="course-categories" class="course-categories section">

        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up">
            <h2>Lĩnh vực</h2>
            <p>Các lĩnh vực tổ chức sự kiện</p>
        </div><!-- End Section Title -->

        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <div class="row g-4">

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="100">
                    <a href="courses.html" class="category-card category-tech">
                        <div class="category-icon">
                            <i class="bi bi-laptop"></i>
                        </div>
                        <h5>Computer Science</h5>
                        <span class="course-count">24 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="150">
                    <a href="courses.html" class="category-card category-business">
                        <div class="category-icon">
                            <i class="bi bi-briefcase"></i>
                        </div>
                        <h5>Business</h5>
                        <span class="course-count">18 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="200">
                    <a href="courses.html" class="category-card category-design">
                        <div class="category-icon">
                            <i class="bi bi-palette"></i>
                        </div>
                        <h5>Design</h5>
                        <span class="course-count">15 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="250">
                    <a href="courses.html" class="category-card category-health">
                        <div class="category-icon">
                            <i class="bi bi-heart-pulse"></i>
                        </div>
                        <h5>Health &amp; Medical</h5>
                        <span class="course-count">12 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="300">
                    <a href="courses.html" class="category-card category-language">
                        <div class="category-icon">
                            <i class="bi bi-globe"></i>
                        </div>
                        <h5>Languages</h5>
                        <span class="course-count">21 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="350">
                    <a href="courses.html" class="category-card category-science">
                        <div class="category-icon">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                        <h5>Science</h5>
                        <span class="course-count">16 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="100">
                    <a href="courses.html" class="category-card category-marketing">
                        <div class="category-icon">
                            <i class="bi bi-megaphone"></i>
                        </div>
                        <h5>Marketing</h5>
                        <span class="course-count">19 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="150">
                    <a href="courses.html" class="category-card category-finance">
                        <div class="category-icon">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <h5>Finance</h5>
                        <span class="course-count">14 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="200">
                    <a href="courses.html" class="category-card category-photography">
                        <div class="category-icon">
                            <i class="bi bi-camera"></i>
                        </div>
                        <h5>Photography</h5>
                        <span class="course-count">11 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="250">
                    <a href="courses.html" class="category-card category-music">
                        <div class="category-icon">
                            <i class="bi bi-music-note-beamed"></i>
                        </div>
                        <h5>Music</h5>
                        <span class="course-count">13 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="300">
                    <a href="courses.html" class="category-card category-engineering">
                        <div class="category-icon">
                            <i class="bi bi-gear"></i>
                        </div>
                        <h5>Engineering</h5>
                        <span class="course-count">22 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="350">
                    <a href="courses.html" class="category-card category-law">
                        <div class="category-icon">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <h5>Law &amp; Legal</h5>
                        <span class="course-count">9 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="100">
                    <a href="courses.html" class="category-card category-culinary">
                        <div class="category-icon">
                            <i class="bi bi-cup-hot"></i>
                        </div>
                        <h5>Culinary Arts</h5>
                        <span class="course-count">8 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="150">
                    <a href="courses.html" class="category-card category-sports">
                        <div class="category-icon">
                            <i class="bi bi-trophy"></i>
                        </div>
                        <h5>Sports &amp; Fitness</h5>
                        <span class="course-count">17 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="200">
                    <a href="courses.html" class="category-card category-writing">
                        <div class="category-icon">
                            <i class="bi bi-pen"></i>
                        </div>
                        <h5>Writing</h5>
                        <span class="course-count">10 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="250">
                    <a href="courses.html" class="category-card category-psychology">
                        <div class="category-icon">
                            <i class="bi bi-body-text"></i>
                        </div>
                        <h5>Psychology</h5>
                        <span class="course-count">12 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="300">
                    <a href="courses.html" class="category-card category-environment">
                        <div class="category-icon">
                            <i class="bi bi-tree"></i>
                        </div>
                        <h5>Environment</h5>
                        <span class="course-count">7 Courses</span>
                    </a>
                </div><!-- End Category Item -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="350">
                    <a href="courses.html" class="category-card category-communication">
                        <div class="category-icon">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <h5>Communication</h5>
                        <span class="course-count">15 Courses</span>
                    </a>
                </div><!-- End Category Item -->
            </div>
        </div>
</main>

<?php layout('footer'); ?>