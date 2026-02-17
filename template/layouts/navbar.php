<?php
if (!defined('_AUTHEN')) {
  die('Truy cập không hợp lệ');
}
?>

<body>

    <header id="header" class="header d-flex align-items-center sticky-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center">

            <a href="<?php echo _HOST_URL; ?>" class="logo d-flex align-items-center me-auto">
                <!-- Uncomment the line below if you also wish to use an image logo -->
                <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/logo.webp" alt="">
                <h1 class="sitename">NCKH</h1>
            </a>

            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="<?php echo _HOST_URL; ?>">Trang chủ</a></li>
                    <li><a href="<?php echo _HOST_URL; ?>/?module=event&action=index">Sự kiện</a></li>
                    <li><a href="<?php echo _HOST_URL; ?>/?module=users&action=index">Người dùng</a></li>
                    <li class="dropdown"><a href="#"><span>Quản trị hệ thống</span> <i
                                class="bi bi-chevron-down toggle-dropdown"></i></a>
                        <ul>
                            <li><a href="course-details.html">Course Details</a></li>
                            <li><a href="instructor-profile.html">Instructor Profile</a></li>
                            <li><a href="events.html">Events</a></li>
                            <li><a href="blog-details.html">Blog Details</a></li>
                            <li><a href="terms.html">Terms</a></li>
                            <li><a href="privacy.html">Privacy</a></li>
                            <li><a href="404.html">404</a></li>
                        </ul>
                    </li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>

            </nav>

            <a class="btn-getstarted" href="<?php echo _HOST_URL; ?>/?module=auth&action=login">Đăng nhập</a>

        </div>

    </header>