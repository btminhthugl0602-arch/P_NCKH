<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

$is_logged_in  = !empty($_SESSION['user_id']) && $_SESSION['role'] !== 'guest';
$is_admin      = $is_logged_in && $_SESSION['role'] == 1;
$current_user  = $is_logged_in ? htmlspecialchars($_SESSION['user_name'] ?? '') : '';
?>

<body>

    <header id="header" class="header d-flex align-items-center sticky-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center">

            <a href="<?php echo _HOST_URL; ?>" class="logo d-flex align-items-center me-auto">
                <h1 class="sitename">NCKH</h1>
            </a>

            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="<?php echo _HOST_URL; ?>">Trang chủ</a></li>
                    <li><a href="<?php echo _HOST_URL; ?>/?module=event&action=index">Sự kiện</a></li>

                    <?php if ($is_admin): ?>
                        <li class="dropdown"><a href="#"><span>Quản trị hệ thống</span>
                                <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                            <ul>
                                <li><a href="<?php echo _HOST_URL; ?>/?module=admin&action=dashboard">
                                        <i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                                </li>
                                <li><a href="<?php echo _HOST_URL; ?>/?module=admin&action=users">
                                        <i class="bi bi-people me-1"></i>Quản lý người dùng</a>
                                </li>
                                <li><a href="<?php echo _HOST_URL; ?>/?module=admin&action=permissions">
                                        <i class="bi bi-shield-lock me-1"></i>Phân quyền tài khoản</a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>

            <!-- Nút đăng nhập / icon profile -->
            <?php if ($is_logged_in): ?>
                <div class="dropdown ms-3">
                    <a href="#" class="d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown"
                        aria-expanded="false" style="color:inherit">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center"
                            style="width:36px;height:36px;flex-shrink:0">
                            <i class="bi bi-person-fill text-white" style="font-size:1.1rem"></i>
                        </div>
                        <span class="d-none d-md-inline fw-semibold"><?= $current_user ?></span>
                        <i class="bi bi-chevron-down" style="font-size:.75rem"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li class="dropdown-header text-muted small px-3 pb-0">
                            Xin chào, <strong><?= $current_user ?></strong>
                        </li>
                        <li>
                            <hr class="dropdown-divider my-1">
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2"
                                href="<?php echo _HOST_URL; ?>/?module=profile&action=index">
                                <i class="bi bi-person-circle text-primary"></i> Trang cá nhân
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider my-1">
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                href="<?php echo _HOST_URL; ?>/?module=auth&action=logout">
                                <i class="bi bi-box-arrow-right"></i> Đăng xuất
                            </a>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <a class="btn-getstarted ms-3" href="<?php echo _HOST_URL; ?>/?module=auth&action=login">Đăng nhập</a>
            <?php endif; ?>

        </div>
    </header>