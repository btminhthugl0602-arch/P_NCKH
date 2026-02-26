<?php
if (!defined('_AUTHEN')) die('Truy cập không hợp lệ');

global $conn; // Lấy kết nối CSDL từ global scope

$is_logged_in = !empty($_SESSION['user_id']) && $_SESSION['role'] !== 'guest';
$is_admin     = $is_logged_in && $_SESSION['role'] == 1;
$current_user = $is_logged_in ? htmlspecialchars($_SESSION['ho_ten'] ?? $_SESSION['user_name'] ?? '') : '';

// Đếm thông báo chưa đọc
$bell_count = 0;
if ($is_logged_in) {
    $id_tk_nav  = (int)($_SESSION['idTK'] ?? $_SESSION['user_id'] ?? 0);
    if ($id_tk_nav > 0 && function_exists('dem_chua_doc')) {
        $bell_count = dem_chua_doc($conn, $id_tk_nav);
    }
}
?>

<body>
    <header id="header" class="header d-flex align-items-center sticky-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center">

            <a href="<?= _HOST_URL ?>" class="logo d-flex align-items-center me-auto">
                <h1 class="sitename">NCKH</h1>
            </a>

            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="<?= _HOST_URL ?>">Trang chủ</a></li>
                    <li><a href="<?= _HOST_URL ?>/?module=event&action=index">Sự kiện</a></li>

                    <?php if ($is_admin): ?>
                        <li class="dropdown">
                            <a href="#">
                                <span>Quản trị hệ thống</span>
                                <i class="bi bi-chevron-down toggle-dropdown"></i>
                            </a>
                            <ul>
                                <li><a href="<?= _HOST_URL ?>/?module=admin&action=dashboard">
                                        <i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                                <li><a href="<?= _HOST_URL ?>/?module=admin&action=users">
                                        <i class="bi bi-people me-1"></i>Quản lý người dùng</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if ($is_logged_in): ?>
                        <li class="dropdown">
                            <a href="#">
                                <span><i class="bi bi-person-circle me-1"></i><?= $current_user ?></span>
                                <i class="bi bi-chevron-down toggle-dropdown"></i>
                            </a>
                            <ul>
                                <li>
                                    <a href="<?= _HOST_URL ?>/?module=profile&action=index">
                                        <i class="bi bi-person me-1"></i> Trang cá nhân
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= _HOST_URL ?>/?module=thongbao&action=index">
                                        <i class="bi bi-bell me-1"></i> Thông báo
                                        <?php if ($bell_count > 0): ?>
                                            <span id="bell-badge"
                                                class="badge bg-danger ms-1"><?= $bell_count > 9 ? '9+' : $bell_count ?></span>
                                        <?php else: ?>
                                            <span id="bell-badge" class="badge bg-danger ms-1" style="display:none">0</span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= _HOST_URL ?>/?module=auth&action=logout" style="color:#dc3545">
                                        <i class="bi bi-box-arrow-right me-1"></i> Đăng xuất
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>

                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>

            <?php if (!$is_logged_in): ?>
                <a class="btn-getstarted ms-3" href="<?= _HOST_URL ?>/?module=auth&action=login">Đăng nhập</a>
            <?php endif; ?>

        </div>
    </header>

    <?php if ($is_logged_in): ?>
        <script>
            // Polling badge thông báo mỗi 30 giây
            function fetchBellCount() {
                fetch('<?= _HOST_URL ?>/?module=thongbao&action=count_ajax', {
                        cache: 'no-store'
                    })
                    .then(r => r.json())
                    .then(data => {
                        const badge = document.getElementById('bell-badge');
                        if (!badge) return;
                        if (data.count > 0) {
                            badge.textContent = data.count > 9 ? '9+' : data.count;
                            badge.style.display = 'inline';
                        } else {
                            badge.style.display = 'none';
                        }
                    })
                    .catch(() => {});
            }
            setInterval(fetchBellCount, 30000);
        </script>
    <?php endif; ?>