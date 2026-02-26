<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

// Yêu cầu đăng nhập
if (empty($_SESSION['user_id']) || $_SESSION['role'] === 'guest') {
    header("Location: " . _HOST_URL . "/?module=auth&action=login");
    exit();
}

$user_id   = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'];

$success_msg = '';
$error_msg   = '';

// ===================== XỬ LÝ ĐỔI MẬT KHẨU =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_pw  = trim($_POST['old_password'] ?? '');
    $new_pw  = trim($_POST['new_password'] ?? '');
    $conf_pw = trim($_POST['confirm_password'] ?? '');

    $tk_r = mysqli_query($conn, "SELECT matKhau FROM taikhoan WHERE idTK = $user_id LIMIT 1");
    $tk   = mysqli_fetch_assoc($tk_r);

    if (empty($old_pw) || empty($new_pw) || empty($conf_pw)) {
        $error_msg = 'Vui lòng điền đầy đủ thông tin.';
    } elseif ($old_pw !== $tk['matKhau'] && !password_verify($old_pw, $tk['matKhau'])) {
        $error_msg = 'Mật khẩu cũ không chính xác.';
    } elseif ($new_pw !== $conf_pw) {
        $error_msg = 'Mật khẩu mới không khớp.';
    } elseif (strlen($new_pw) < 6) {
        $error_msg = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    } else {
        $new_hash = password_hash($new_pw, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE taikhoan SET matKhau = '" . mysqli_real_escape_string($conn, $new_hash) . "' WHERE idTK = $user_id");
        $_SESSION['flash_msg']  = 'Đổi mật khẩu thành công!';
        $_SESSION['flash_type'] = 'success';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// ===================== LẤY THÔNG TIN TÀI KHOẢN =====================
$sql_tk = "
    SELECT tk.idTK, tk.tenTK, tk.idLoaiTK, tk.isActive, tk.ngayTao,
           ltk.tenLoaiTK,
           COALESCE(sv.tenSV, gv.tenGV, '') AS hoTen,
           sv.MSV, sv.GPA, sv.DRL,
           COALESCE(lp.tenLop, '') AS tenLop,
           COALESCE(kh1.tenKhoa, kh2.tenKhoa, '') AS tenKhoa,
           gv.gioiTinh
    FROM taikhoan tk
    LEFT JOIN loaitaikhoan ltk ON tk.idLoaiTK = ltk.idLoaiTK
    LEFT JOIN sinhvien sv ON tk.idTK = sv.idTK
    LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
    LEFT JOIN lop lp ON sv.idLop = lp.idLop
    LEFT JOIN khoa kh1 ON sv.idKhoa = kh1.idKhoa
    LEFT JOIN khoa kh2 ON gv.idKhoa = kh2.idKhoa
    WHERE tk.idTK = $user_id
    LIMIT 1
";
$tk_res  = mysqli_query($conn, $sql_tk);
$profile = mysqli_fetch_assoc($tk_res);

if (!$profile) {
    header("Location: " . _HOST_URL);
    exit();
}

// Lấy quyền của tài khoản
$q_res = mysqli_query($conn, "
    SELECT q.tenQuyen, q.maQuyen
    FROM taikhoan_quyen tq
    JOIN quyen q ON tq.idQuyen = q.idQuyen
    WHERE tq.idTK = $user_id
");
$quyens = [];
while ($r = mysqli_fetch_assoc($q_res)) $quyens[] = $r;

// Lấy sự kiện đã tham gia
$sql_sk = "
    SELECT DISTINCT sk.idSK, sk.tenSK, sk.ngayBatDau, sk.ngayKetThuc, ct.tenCap
    FROM thanhviennhom tvn
    JOIN nhom nh ON tvn.idnhom = nh.idnhom
    JOIN sukien sk ON nh.idSK = sk.idSK
    LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
    WHERE tvn.idtk = $user_id AND sk.isActive = 1
    ORDER BY sk.ngayBatDau DESC
    LIMIT 20
";
$sk_res = mysqli_query($conn, $sql_sk);
$my_sks = [];
if ($sk_res) while ($r = mysqli_fetch_assoc($sk_res)) $my_sks[] = $r;

// Lấy chứng nhận
$cn_res = mysqli_query($conn, "
    SELECT cn.*, sk.tenSK
    FROM chungnhan cn
    LEFT JOIN sukien sk ON cn.idSK = sk.idSK
    WHERE cn.idTK = $user_id
    ORDER BY cn.ngayCap DESC
");
$chungnhans = [];
if ($cn_res) while ($r = mysqli_fetch_assoc($cn_res)) $chungnhans[] = $r;

// Bảng lichsu_dangnhap không tồn tại trong CSDL - bỏ qua
$login_logs = [];

$data = ['page_title' => 'Thông tin cá nhân - ' . $profile['tenTK']];
layout('header', $data);
layout('navbar');
?>

<main class="main">

    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Thông tin cá nhân</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?= _HOST_URL ?>">Home</a></li>
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
                            <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/education/showcase-4.webp" alt="Background"
                                class="img-fluid">
                            <div class="hero-overlay"></div>
                        </div>
                        <div class="hero-content">
                            <div class="instructor-avatar">
                                <?php
                                $initials = mb_strtoupper(mb_substr(!empty($profile['hoTen']) ? $profile['hoTen'] : $profile['tenTK'], 0, 1));
                                ?>
                                <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/user.jpg"
                                    alt="<?= htmlspecialchars($profile['hoTen'] ?: $profile['tenTK']) ?>"
                                    onerror="this.style.display='none'; document.getElementById('avatar-fallback').style.display='flex';">
                                <div id="avatar-fallback" style="display:none; width:180px; height:180px; border-radius:50%; border:6px solid #fff;
                                            background: linear-gradient(135deg, #0d6efd, #0a58ca);
                                            color:#fff; font-size:4rem; font-weight:700;
                                            align-items:center; justify-content:center;
                                            box-shadow: 0 20px 40px rgba(0,0,0,0.3);">
                                    <?= $initials ?>
                                </div>
                                <div class="status-badge">
                                    <i class="bi bi-patch-check-fill"></i>
                                    <span><?= $profile['isActive'] ? 'Verified' : 'Locked' ?></span>
                                </div>
                            </div>
                            <div class="instructor-info">
                                <h2><?= htmlspecialchars(!empty($profile['hoTen']) ? $profile['hoTen'] : $profile['tenTK']) ?>
                                </h2>
                                <p class="title">
                                    <?= htmlspecialchars(!empty($profile['tenKhoa']) ? $profile['tenKhoa'] : (!empty($profile['tenLop']) ? $profile['tenLop'] : 'Hệ thống NCKH')) ?>
                                </p>
                                <div class="credentials">
                                    <?php
                                    $rc = [1 => 'danger', 2 => 'warning', 3 => 'primary'][$profile['idLoaiTK']] ?? 'secondary';
                                    ?>
                                    <span class="credential" style="background:var(--bs-<?= $rc ?>)">
                                        <?= htmlspecialchars($profile['tenLoaiTK']) ?>
                                    </span>
                                    <?php if (!empty($profile['MSV'])): ?>
                                        <span class="credential">MSV: <?= htmlspecialchars($profile['MSV']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="rating-overview">
                                    <p class="rating-text">Ngày tạo tài khoản:
                                        <?= date('d/m/Y', strtotime($profile['ngayTao'])) ?></p>
                                </div>
                                <div class="contact-actions">
                                    <span class="btn-contact">
                                        <i class="bi bi-person-badge"></i>
                                        @<?= htmlspecialchars($profile['tenTK']) ?>
                                    </span>
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
                                <button class="nav-link active" data-bs-toggle="tab"
                                    data-bs-target="#instructor-profile-experience" type="button" role="tab">
                                    <i class="bi bi-calendar-event"></i>
                                    Sự kiện đã tham gia
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#instructor-profile-courses" type="button" role="tab">
                                    <i class="bi bi-award"></i>
                                    Giấy chứng nhận
                                    <?php if (count($chungnhans) > 0): ?>
                                        <span class="badge bg-primary ms-1"><?= count($chungnhans) ?></span>
                                    <?php endif; ?>
                                </button>
                            </li>
                            <?php if ($profile['idLoaiTK'] == 3 && (!empty($profile['GPA']) || !empty($profile['DRL']))): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-bs-toggle="tab"
                                        data-bs-target="#instructor-profile-ketqua" type="button" role="tab">
                                        <i class="bi bi-bar-chart"></i>
                                        Kết quả học tập
                                    </button>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <div class="tab-content custom-tab-content">

                            <!-- Tab: Sự kiện đã tham gia -->
                            <div class="tab-pane fade show active" id="instructor-profile-experience" role="tabpanel">
                                <?php if (empty($my_sks)): ?>
                                    <div class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                                        Chưa tham gia sự kiện nào.
                                    </div>
                                <?php else: ?>
                                    <div class="experience-grid">
                                        <?php foreach ($my_sks as $sk):
                                            $now_ts = time();
                                            $start  = $sk['ngayBatDau'] ? strtotime($sk['ngayBatDau']) : null;
                                            $end    = $sk['ngayKetThuc'] ? strtotime($sk['ngayKetThuc']) : null;
                                            if (!$start) {
                                                $label = 'Chưa có lịch';
                                                $badge_c = 'bg-secondary';
                                            } elseif ($start > $now_ts) {
                                                $label = 'Sắp diễn ra';
                                                $badge_c = 'bg-info text-dark';
                                            } elseif (!$end || $end >= $now_ts) {
                                                $label = 'Đang diễn ra';
                                                $badge_c = 'bg-success';
                                            } else {
                                                $label = 'Đã kết thúc';
                                                $badge_c = 'bg-secondary';
                                            }
                                        ?>
                                            <div class="experience-card">
                                                <div class="timeline-marker">
                                                    <?= $sk['ngayBatDau'] ? date('Y', strtotime($sk['ngayBatDau'])) : '--' ?>
                                                </div>
                                                <div class="experience-details">
                                                    <h5>
                                                        <a href="<?= _HOST_URL ?>/?module=event&action=view&id=<?= $sk['idSK'] ?>"
                                                            class="text-decoration-none">
                                                            <?= htmlspecialchars($sk['tenSK']) ?>
                                                        </a>
                                                    </h5>
                                                    <p class="institution">
                                                        <?= !empty($sk['tenCap']) ? htmlspecialchars($sk['tenCap']) : 'Không rõ cấp tổ chức' ?>
                                                    </p>
                                                    <p class="text-muted small mb-1">
                                                        <?php if ($sk['ngayBatDau']): ?>
                                                            <i class="bi bi-calendar3 me-1"></i>
                                                            <?= date('d/m/Y', strtotime($sk['ngayBatDau'])) ?>
                                                            <?= $sk['ngayKetThuc'] ? ' → ' . date('d/m/Y', strtotime($sk['ngayKetThuc'])) : '' ?>
                                                        <?php endif; ?>
                                                    </p>
                                                    <span class="badge <?= $badge_c ?>"><?= $label ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab: Giấy chứng nhận -->
                            <div class="tab-pane fade" id="instructor-profile-courses" role="tabpanel">
                                <?php if (empty($chungnhans)): ?>
                                    <div class="text-center py-5 text-muted">
                                        <i class="bi bi-award fs-2 d-block mb-2"></i>
                                        Chưa có chứng nhận nào.
                                    </div>
                                <?php else: ?>
                                    <div class="courses-grid">
                                        <?php foreach ($chungnhans as $cn): ?>
                                            <div class="course-item">
                                                <div class="course-thumb">
                                                    <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/education/courses-5.webp"
                                                        alt="Course" class="img-fluid">
                                                </div>
                                                <div class="course-info">
                                                    <h5><?= htmlspecialchars($cn['tenSK'] ?? 'Sự kiện') ?></h5>
                                                    <p class="text-muted small mb-1">
                                                        <i
                                                            class="bi bi-upc me-1"></i><?= htmlspecialchars($cn['maChungNhan'] ?? '—') ?>
                                                    </p>
                                                    <p class="text-muted small mb-1">
                                                        <?= htmlspecialchars($cn['loaiChungNhan'] ?? '—') ?>
                                                    </p>
                                                    <?php if (!empty($cn['filePDF'])): ?>
                                                        <a href="<?= htmlspecialchars($cn['filePDF']) ?>" class="price"
                                                            target="_blank">Tải về</a>
                                                    <?php else: ?>
                                                        <p class="price text-muted">Chưa có file</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab: Kết quả học tập (chỉ sinh viên) -->
                            <?php if ($profile['idLoaiTK'] == 3): ?>
                                <div class="tab-pane fade" id="instructor-profile-ketqua" role="tabpanel">
                                    <div class="row g-4 pt-3">
                                        <div class="col-6">
                                            <div class="card border-0 bg-light rounded-4 text-center p-4">
                                                <div class="fw-bold text-primary" style="font-size:2.5rem">
                                                    <?= number_format($profile['GPA'] ?? 0, 2) ?>
                                                </div>
                                                <div class="fw-semibold">GPA Tích lũy</div>
                                                <div class="text-muted small">Thang điểm 4.0</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="card border-0 bg-light rounded-4 text-center p-4">
                                                <div class="fw-bold text-success" style="font-size:2.5rem">
                                                    <?= $profile['DRL'] ?? 0 ?>
                                                </div>
                                                <div class="fw-semibold">Điểm Rèn Luyện</div>
                                                <div class="text-muted small">Thang điểm 100</div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="card border-0 bg-light rounded-4 p-3">
                                                <div class="fw-semibold mb-2">
                                                    <i class="bi bi-building me-2 text-primary"></i>Thông tin học tập
                                                </div>
                                                <div class="row g-2 text-muted small">
                                                    <div class="col-6">
                                                        <i class="bi bi-people me-1"></i>Lớp:
                                                        <strong><?= htmlspecialchars($profile['tenLop'] ?: '—') ?></strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <i class="bi bi-building me-1"></i>Khoa:
                                                        <strong><?= htmlspecialchars($profile['tenKhoa'] ?: '—') ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="sidebar-widgets" data-aos="fade-left" data-aos-delay="300">

                        <!-- Cài đặt -->
                        <div class="stats-widget">
                            <h4>Cài đặt</h4>
                            <div class="stats-grid">
                                <div class="stat-box" style="cursor:pointer" data-bs-toggle="modal"
                                    data-bs-target="#modalDoiMatKhau">
                                    <div class="stat-content">
                                        <h5><i class="bi bi-lock me-2 text-primary"></i>Đổi mật khẩu</h5>
                                    </div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-content">
                                        <h5><i class="bi bi-chat-dots me-2 text-success"></i>Góp ý</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Thông tin tài khoản -->
                        <div class="achievements-widget mt-3">
                            <h4>Thông tin tài khoản</h4>
                            <div class="achievement-list">
                                <div class="achievement-item">
                                    <div class="achievement-icon"><i class="bi bi-person-badge"></i></div>
                                    <div class="achievement-text">
                                        <h6 class="mb-0">@<?= htmlspecialchars($profile['tenTK']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($profile['tenLoaiTK']) ?></small>
                                    </div>
                                </div>
                                <div class="achievement-item">
                                    <div class="achievement-icon">
                                        <i class="bi bi-circle-fill <?= $profile['isActive'] ? 'text-success' : 'text-danger' ?>"
                                            style="font-size:.6rem"></i>
                                    </div>
                                    <div class="achievement-text">
                                        <h6 class="mb-0"><?= $profile['isActive'] ? 'Đang hoạt động' : 'Đã bị khóa' ?>
                                        </h6>
                                        <small class="text-muted">Trạng thái</small>
                                    </div>
                                </div>
                                <?php if (!empty($profile['tenLop'])): ?>
                                    <div class="achievement-item">
                                        <div class="achievement-icon"><i class="bi bi-people"></i></div>
                                        <div class="achievement-text">
                                            <h6 class="mb-0"><?= htmlspecialchars($profile['tenLop']) ?></h6>
                                            <small class="text-muted">Lớp</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($profile['tenKhoa'])): ?>
                                    <div class="achievement-item">
                                        <div class="achievement-icon"><i class="bi bi-building"></i></div>
                                        <div class="achievement-text">
                                            <h6 class="mb-0"><?= htmlspecialchars($profile['tenKhoa']) ?></h6>
                                            <small class="text-muted">Khoa</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quyền hệ thống -->
                        <?php if (!empty($quyens)): ?>
                            <div class="achievements-widget mt-3">
                                <h4>Quyền hệ thống</h4>
                                <div class="achievement-list">
                                    <?php foreach ($quyens as $q): ?>
                                        <div class="achievement-item">
                                            <div class="achievement-icon"><i class="bi bi-shield-check"></i></div>
                                            <div class="achievement-text">
                                                <h6 class="mb-0"><?= htmlspecialchars($q['tenQuyen']) ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($q['maQuyen']) ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Thông tin đăng nhập -->
                        <div class="achievements-widget mt-3">
                            <h4>Thông tin đăng nhập</h4>
                            <div class="achievement-list">
                                <?php if (!empty($login_logs)): ?>
                                    <?php foreach ($login_logs as $log): ?>
                                        <div class="achievement-item">
                                            <div class="achievement-text">
                                                <h6>Đăng nhập ngày <?= date('d/m/Y', strtotime($log['ngayDangNhap'])) ?></h6>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="achievement-item">
                                        <div class="achievement-text">
                                            <h6 class="text-muted">Chưa có lịch sử</h6>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>

    </section><!-- /Instructor Profile Section -->

</main>

<!-- Modal Đổi mật khẩu -->
<div class="modal fade" id="modalDoiMatKhau" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-lock me-2"></i>Đổi mật khẩu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mật khẩu cũ</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="old_password" id="old_pw" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('old_pw',this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mật khẩu mới</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="new_password" id="new_pw" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('new_pw',this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Xác nhận mật khẩu mới</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="confirm_password" id="conf_pw" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('conf_pw',this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="bi bi-lock me-1"></i>Đổi mật khẩu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php layout('footer') ?>

<script>
    function togglePwd(id, btn) {
        const inp = document.getElementById(id);
        inp.type = inp.type === 'password' ? 'text' : 'password';
        btn.querySelector('i').classList.toggle('bi-eye');
        btn.querySelector('i').classList.toggle('bi-eye-slash');
    }
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($error_msg)): ?>
            new bootstrap.Modal(document.getElementById('modalDoiMatKhau')).show();
            showToast(<?= json_encode(htmlspecialchars($error_msg), JSON_UNESCAPED_UNICODE) ?>, 'danger');
        <?php endif; ?>
    });
</script>