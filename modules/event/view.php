<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/quan_ly_su_kien.php';
require_once _PATH_URL . '/modules/functions/base.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$event = btc_lay_chi_tiet_su_kien($conn, $id_su_kien);

if (!$event) {
    require_once _PATH_URL . '/modules/errors/404.php';
    exit;
}

// ==========================================
// KIỂM TRA QUYỀN (ROLE-BASED ACCESS CONTROL)
// ==========================================
$user_id = $_SESSION['user_id'] ?? 0; 

$is_btc = kiem_tra_quyen_he_thong($conn, $user_id, 'event.manage');

// Lấy idGV thực tế từ idTK
$id_gv_logged = 0;
$res_gv = mysqli_query($conn, "SELECT idGV FROM giangvien WHERE idTK = $user_id LIMIT 1");
if ($res_gv && mysqli_num_rows($res_gv) > 0) {
    $id_gv_logged = mysqli_fetch_assoc($res_gv)['idGV'];
}

$is_giangvien = false;
$my_subcommittees = []; // Danh sách tiểu ban mà GV này tham gia

if ($id_gv_logged > 0) {
    // 1. Kiểm tra xem có được phân công chấm ở Sự kiện này không
    $sql_check_pc = "
        SELECT 1 FROM phancong_doclap pcd JOIN vongthi v ON pcd.idVongThi = v.idVongThi WHERE pcd.idGV = $id_gv_logged AND v.idSK = $id_su_kien
        UNION
        SELECT 1 FROM tieuban_giangvien tbg JOIN tieuban tb ON tbg.idTieuBan = tb.idTieuBan WHERE tbg.idGV = $id_gv_logged AND tb.idSK = $id_su_kien
    ";
    $res_check = mysqli_query($conn, $sql_check_pc);
    if ($res_check && mysqli_num_rows($res_check) > 0) {
        $is_giangvien = true;
    }

    // 2. Lấy thông tin các tiểu ban mà GV này tham gia trong sự kiện này
    $sql_my_tb = "
        SELECT tb.*, v.tenVongThi 
        FROM tieuban tb 
        JOIN tieuban_giangvien tbg ON tb.idTieuBan = tbg.idTieuBan 
        JOIN vongthi v ON tb.idVongThi = v.idVongThi
        WHERE tbg.idGV = $id_gv_logged AND tb.idSK = $id_su_kien
    ";
    $res_my_tb = mysqli_query($conn, $sql_my_tb);
    if ($res_my_tb) {
        while ($row = mysqli_fetch_assoc($res_my_tb)) {
            $tb_id = $row['idTieuBan'];
            // Lấy danh sách thành viên Hội đồng cùng tiểu ban
            $sql_members = "SELECT gv.tenGV FROM giangvien gv JOIN tieuban_giangvien tbg ON gv.idGV = tbg.idGV WHERE tbg.idTieuBan = $tb_id";
            $row['members'] = mysqli_fetch_all(mysqli_query($conn, $sql_members), MYSQLI_ASSOC);
            
            // Lấy danh sách sản phẩm/bài thi trong tiểu ban
            $sql_prods = "SELECT sp.tensanpham, n.manhom, ttn.tennhom 
                          FROM sanpham sp 
                          JOIN tieuban_sanpham tbs ON sp.idSanPham = tbs.idSanPham 
                          LEFT JOIN nhom n ON sp.idNhom = n.idnhom
                          LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
                          WHERE tbs.idTieuBan = $tb_id";
            $row['products'] = mysqli_fetch_all(mysqli_query($conn, $sql_prods), MYSQLI_ASSOC);
            
            $my_subcommittees[] = $row;
        }
    }
}

layout('header');
layout('navbar');
?>
<main class="main">

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
    </div>

    <section id="course-details" class="course-details section">
        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row">
                <div class="col-lg-8">

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
                        </div>
                    </div>

                    <div class="course-nav-tabs" data-aos="fade-up" data-aos-delay="300">
                        <ul class="nav nav-tabs" id="EventDetails" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" id="event-info-tab" data-bs-toggle="tab"
                                    data-bs-target="#event-info" type="button" role="tab">
                                    <i class="bi bi-layout-text-window-reverse"></i> Thông tin
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="event-groups-tab" data-bs-toggle="tab"
                                    data-bs-target="#event-groups" type="button" role="tab">
                                    <i class="bi bi-list-ul"></i> Nhóm thi
                                </button>
                            </li>
                            
                            <?php if ($is_btc): ?>
                            <li class="nav-item">
                                <button class="nav-link fw-bold text-primary" id="event-config-tab" data-bs-toggle="tab"
                                    data-bs-target="#event-config" type="button" role="tab">
                                    <i class="bi bi-gear-fill"></i> Cấu hình sự kiện
                                </button>
                            </li>
                            <?php endif; ?>

                            <?php if ($is_btc || $is_giangvien): ?>
                            <li class="nav-item">
                                <button class="nav-link fw-bold text-success" id="event-grading-tab" data-bs-toggle="tab"
                                    data-bs-target="#event-grading" type="button" role="tab">
                                    <i class="bi bi-briefcase-fill"></i> Khu vực Giám khảo
                                </button>
                            </li>
                            <?php endif; ?>
                        </ul>

                        <div class="tab-content" id="EventDetailsContent">

                            <div class="tab-pane fade show active" id="event-info" role="tabpanel">
                                <div class="overview-section">
                                    <h3>Chi tiết sự kiện</h3>
                                    <p><?= nl2br(htmlspecialchars($event['moTa'])) ?></p>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="event-groups" role="tabpanel">
                                <div class="alert alert-info mt-3">Khu vực hiển thị danh sách các nhóm đang tham gia sự kiện.</div>
                            </div>

                            <?php if ($is_btc): ?>
                            <div class="tab-pane fade" id="event-config" role="tabpanel">
                                <div class="event-config-content mt-4">
                                    <h3 class="fw-bold text-primary"><i class="bi bi-gear me-2"></i>Bảng điều khiển Sự kiện</h3>
                                    <div class="d-flex flex-column gap-3 mt-4">
                                        <div class="row g-2">
                                            <div class="col-md-4"><a class="btn btn-outline-primary w-100 py-3 fw-bold" href="?module=event&action=config_rounds&id=<?=$id_su_kien?>"><i class="bi bi-layers d-block fs-3 mb-1"></i> Cấu hình cơ bản</a></div>
                                            <div class="col-md-4"><a class="btn btn-outline-primary w-100 py-3 fw-bold" href="?module=event&action=config_rules&id=<?=$id_su_kien?>"><i class="bi bi-file-earmark-ruled d-block fs-3 mb-1"></i> Quy chế</a></div>
                                            <div class="col-md-4"><a class="btn btn-outline-primary w-100 py-3 fw-bold" href="?module=event&action=config_criteria&id=<?=$id_su_kien?>"><i class="bi bi-ui-checks d-block fs-3 mb-1"></i> Bộ tiêu chí</a></div>
                                        </div>
                                        <div class="card border-0 shadow-sm bg-light mt-2">
                                            <div class="card-body">
                                                <h6 class="fw-bold text-dark mb-3 text-uppercase">Nghiệp vụ chấm thi & Đánh giá</h6>
                                                <a class="btn btn-success text-white w-100 text-start p-3 mb-2 rounded-3 shadow-sm" href="?module=event&action=config_grading&id=<?=$id_su_kien?>">
                                                    <i class="bi bi-pencil-square fs-4 me-2 align-middle"></i> <strong>1. Phân công & Quản lý Điểm (Sơ loại)</strong>
                                                </a>
                                                <a class="btn btn-warning text-dark w-100 text-start p-3 rounded-3 shadow-sm" href="?module=event&action=config_subcommittee&id=<?=$id_su_kien?>">
                                                    <i class="bi bi-diagram-3 fs-4 me-2 align-middle"></i> <strong>2. Quản lý Tiểu ban (Bảo vệ Vòng trong)</strong>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($is_btc || $is_giangvien): ?>
                            <div class="tab-pane fade" id="event-grading" role="tabpanel">
                                <div class="event-grading-content mt-4">
                                    <h3 class="fw-bold text-success"><i class="bi bi-briefcase me-2"></i>Khu vực làm việc của Ban Giám Khảo</h3>
                                    <div class="d-flex flex-column gap-3 mt-4">
                                        <a class="btn btn-success text-start p-3 shadow-sm rounded-3" href="?module=event&action=my_grading_tasks&id=<?=$id_su_kien?>">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-white text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;"><i class="bi bi-journal-check fs-4"></i></div>
                                                <div><h5 class="mb-1 fw-bold text-white">Nhiệm vụ Chấm điểm</h5><small class="text-white-50">Truy cập danh sách các bài thi bạn được phân công đánh giá</small></div>
                                            </div>
                                        </a>
                                        
                                        <button class="btn btn-info text-start p-3 shadow-sm rounded-3 text-dark border-0" data-bs-toggle="modal" data-bs-target="#modalSchedule">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-white text-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;"><i class="bi bi-people fs-4"></i></div>
                                                <div><h5 class="mb-1 fw-bold text-dark">Lịch trình Hội đồng / Tiểu ban</h5><small class="text-dark-50">Xem thông tin phòng, thời gian và các thành viên cùng Hội đồng</small></div>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="enrollment-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="card-header bg-primary text-white text-center py-4"><h4 class="m-0 fw-bold">Thông báo nhanh</h4></div>
                        <div class="card-body">
                            <div class="course-highlights">
                                <div class="highlight-item"><i class="bi bi-calendar-check"></i> <span>Đang mở đăng ký</span></div>
                                <div class="highlight-item"><i class="bi bi-diagram-3"></i> <span>Có 3 vòng thi</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="modalSchedule" tabindex="-1" aria-labelledby="modalScheduleLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title fw-bold" id="modalScheduleLabel"><i class="bi bi-calendar3 me-2"></i>Lịch trình Hội đồng của tôi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <?php if (empty($my_subcommittees)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-info-circle fs-1 text-muted d-block mb-2"></i>
                            <p class="text-muted">Bạn chưa được phân công vào Tiểu ban báo cáo nào trong sự kiện này.</p>
                        </div>
                    <?php else: foreach ($my_subcommittees as $tb): ?>
                        <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="text-primary fw-bold mb-1"><?= htmlspecialchars($tb['tenTieuBan']) ?></h5>
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill"><?= htmlspecialchars($tb['tenVongThi']) ?></span>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label class="text-muted small text-uppercase fw-bold d-block mb-1">Thời gian báo cáo</label>
                                        <div class="d-flex align-items-center text-dark fw-bold">
                                            <i class="bi bi-clock-fill me-2 text-info"></i>
                                            <?= $tb['ngayBaoCao'] ? date('d/m/Y', strtotime($tb['ngayBaoCao'])) : 'Chưa xếp lịch' ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small text-uppercase fw-bold d-block mb-1">Địa điểm / Phòng</label>
                                        <div class="d-flex align-items-center text-dark fw-bold">
                                            <i class="bi bi-geo-alt-fill me-2 text-danger"></i>
                                            <?= htmlspecialchars($tb['diaDiem'] ?: 'Chưa xác định') ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4 mb-md-0">
                                        <h6 class="fw-bold mb-3 border-start border-4 border-info ps-2">Thành viên Hội đồng</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($tb['members'] as $m): ?>
                                                <span class="badge bg-light text-dark border p-2 fw-normal">
                                                    <i class="bi bi-person-fill me-1 text-secondary"></i><?= htmlspecialchars($m['tenGV']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-3 border-start border-4 border-success ps-2">Danh sách bài báo cáo (<?= count($tb['products']) ?>)</h6>
                                        <ul class="list-group list-group-flush small">
                                            <?php foreach ($tb['products'] as $p): ?>
                                                <li class="list-group-item bg-transparent px-0 border-0 py-1">
                                                    <i class="bi bi-file-earmark-text me-2 text-success"></i>
                                                    <strong><?= htmlspecialchars($p['tennhom'] ?: $p['manhom']) ?>:</strong> <?= htmlspecialchars($p['tensanpham']) ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
                <div class="modal-footer border-0 bg-white">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

</main>
<?php layout('footer'); ?>